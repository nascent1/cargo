<?php

namespace App\Services\Cargo;

use App\Models\AuditLog;
use App\Models\Consignment;
use App\Models\User;
use App\Services\SmsNotifier;
use App\Support\PhoneNumber;
use DomainException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Handing arrived cargo to the person collecting it.
 *
 * The collector's name, ID and mobile are recorded. A name or ID that differs from the booking
 * gives a warning, never a block. The mobile is confirmed with a one-time code texted to it; only
 * when that SMS could not be delivered may the hand-over go ahead with a reason, and the mobile is
 * then recorded as unconfirmed. Codes are kept hashed in the cache, never in plain text.
 */
class CargoHandoverService
{
    public const CODE_MINUTES = 10;
    public const MAX_ATTEMPTS = 5;
    public const RESEND_SECONDS = 60;

    public const ID_TYPES = [
        'national_id' => 'National ID (NIDA)',
        'passport' => 'Passport',
        'driving_licence' => 'Driving licence',
        'voter_id' => 'Voter ID',
        'other' => 'Other',
    ];

    public function __construct(private SmsNotifier $sms, private CargoTrackingService $tracking)
    {
    }

    /**
     * Text a code to the collector's mobile.
     *
     * @return array{sent: bool, phone: string, message: string}
     */
    public function sendCode(Consignment $c, string $phone): array
    {
        $this->assertCollectable($c);
        $to = PhoneNumber::normalize($phone) ?? throw new DomainException('Enter a valid mobile number.');

        $state = Cache::get($this->key($c));
        if ($state && $state['phone'] === $to && now()->timestamp - $state['sent_at'] < self::RESEND_SECONDS) {
            throw new DomainException('A code was just sent to this number. Wait a minute before sending another.');
        }

        $code = (string) random_int(100000, 999999);
        $log = $this->sms->send('cargo_collection_otp', $to, [
            'booking_reference' => $c->consignment_number, 'code' => $code, 'minutes' => self::CODE_MINUTES,
        ]);
        $delivered = $log->status === 'sent';
        Cache::put($this->key($c), [
            'phone' => $to, 'hash' => $this->hash($c, $code), 'attempts' => 0,
            'sent_at' => now()->timestamp, 'delivered' => $delivered,
        ], now()->addMinutes(self::CODE_MINUTES));

        $masked = PhoneNumber::mask($to);

        return [
            'sent' => $delivered,
            'phone' => $masked,
            'message' => $delivered
                ? "Code sent to {$masked}. Ask the collector to read it out."
                : "The code could not be sent to {$masked} (" . ($log->error_message ?: 'SMS failed') . '). You can still hand over and record why the mobile was not confirmed.',
        ];
    }

    /**
     * Hand the cargo over.
     *
     * @param array{collector_name: string, id_type: string, id_number: string, phone: string, code?: ?string, unconfirmed_reason?: ?string} $data
     * @return array{warnings: list<string>, phone_confirmed: bool}
     */
    public function handOver(Consignment $c, array $data, User $by, ?string $ip = null): array
    {
        $this->assertCollectable($c);
        $to = PhoneNumber::normalize($data['phone'] ?? '') ?? throw new DomainException('Enter a valid mobile number.');
        $name = trim(preg_replace('/\s+/', ' ', (string) ($data['collector_name'] ?? '')));
        $idNumber = strtoupper(trim((string) ($data['id_number'] ?? '')));
        if ($name === '' || $idNumber === '' || !isset(self::ID_TYPES[$data['id_type'] ?? ''])) {
            throw new DomainException('Enter the collector\'s name, ID type and ID number.');
        }

        $confirmed = $this->confirmPhone($c, $to, $data['code'] ?? null, $data['unconfirmed_reason'] ?? null);
        $warnings = $this->warnings($c, $name, $idNumber, $to);
        if (!$confirmed) {
            $warnings[] = 'Mobile not confirmed (code not delivered): ' . trim((string) $data['unconfirmed_reason']);
        }

        DB::transaction(function () use ($c, $name, $data, $idNumber, $to, $confirmed, $by, $warnings) {
            $c->fill([
                'collected_by_name' => $name,
                'collected_id_type' => $data['id_type'],
                'collected_id_number' => $idNumber,
                'collected_phone' => $to,
                'collected_phone_verified' => $confirmed,
                'handed_over_by' => $by->id,
                'collection_warnings' => $warnings ?: null,
            ])->save();
            $this->tracking->logEvent($c, 'collected', "Handed over to {$name}" . ($warnings ? ' (with warnings)' : ''), $by->id);
        });
        Cache::forget($this->key($c));

        AuditLog::createEntry($by->id, 'cargo_handover', 'consignment', $c->id, null, [
            'collector' => $name, 'id_type' => $data['id_type'], 'id_last4' => substr($idNumber, -4),
            'phone' => PhoneNumber::mask($to), 'phone_confirmed' => $confirmed, 'warnings' => $warnings,
        ], $ip);

        return ['warnings' => $warnings, 'phone_confirmed' => $confirmed];
    }

    /** Warnings to show before handing over (same checks as handOver). */
    public function warnings(Consignment $c, string $name, string $idNumber, ?string $phone): array
    {
        $warnings = [];
        $norm = fn ($s) => mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $s)));
        $receiver = $norm($c->receiver_name);
        $collector = $norm($name);
        // Same person if one name contains the other ("Juma" vs "Juma Ali")
        if ($receiver !== '' && !str_contains($receiver, $collector) && !str_contains($collector, $receiver)) {
            $warnings[] = "The collector ({$name}) is not the receiver on the booking ({$c->receiver_name}).";
        }
        if (filled($c->receiver_id_number) && strtoupper(trim($c->receiver_id_number)) !== $idNumber) {
            $warnings[] = 'The ID number differs from the receiver\'s ID on the booking.';
        }
        $receiverPhone = PhoneNumber::normalize($c->receiver_phone);
        if ($phone && $receiverPhone && $receiverPhone !== $phone) {
            $warnings[] = 'The mobile differs from the receiver\'s number on the booking (' . PhoneNumber::mask($receiverPhone) . ').';
        }

        return $warnings;
    }

    private function confirmPhone(Consignment $c, string $to, ?string $code, ?string $reason): bool
    {
        $state = Cache::get($this->key($c));
        if (!$state || $state['phone'] !== $to) {
            throw new DomainException('Send a code to this mobile first.');
        }

        if (blank($code)) {
            if ($state['delivered']) {
                throw new DomainException('Enter the code sent to the collector\'s mobile.');
            }
            if (mb_strlen(trim((string) $reason)) < 5) {
                throw new DomainException('The code could not be delivered: give a reason to hand over without it.');
            }

            return false;
        }

        if ($state['attempts'] >= self::MAX_ATTEMPTS) {
            Cache::forget($this->key($c));
            throw new DomainException('Too many wrong codes. Send a new code.');
        }
        if (!hash_equals($state['hash'], $this->hash($c, trim($code)))) {
            $state['attempts']++;
            Cache::put($this->key($c), $state, now()->addSeconds(max(1, self::CODE_MINUTES * 60 - (now()->timestamp - $state['sent_at']))));
            $left = self::MAX_ATTEMPTS - $state['attempts'];
            throw new DomainException($left > 0 ? "Wrong code ({$left} " . ($left === 1 ? 'try' : 'tries') . ' left).' : 'Wrong code. Send a new code.');
        }

        return true;
    }

    private function assertCollectable(Consignment $c): void
    {
        if (!in_array($c->status, ['delivered', 'unclaimed'], true)) {
            throw new DomainException($c->status === 'collected'
                ? 'This cargo has already been collected.'
                : 'Only cargo that has arrived can be handed over (this booking is ' . str_replace('_', ' ', $c->status) . ').');
        }
    }

    private function key(Consignment $c): string
    {
        return "cargo-handover-code:{$c->id}";
    }

    private function hash(Consignment $c, string $code): string
    {
        return hash_hmac('sha256', "{$c->id}|{$code}", (string) config('app.key'));
    }
}
