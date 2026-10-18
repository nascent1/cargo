<?php

namespace Tests\Feature;

use App\Models\BusinessDayClosure;
use App\Models\CargoInventory;
use App\Models\CargoType;
use App\Models\Consignment;
use App\Models\DualControlRequest;
use App\Models\IntegrationConfig;
use App\Models\NotificationLog;
use App\Models\PaymentMethod;
use App\Models\SystemConfig;
use App\Models\TransportService;
use App\Models\User;
use App\Notifications\AccountCredentialsNotification;
use App\Services\Cargo\CargoBookingService;
use App\Services\Cargo\CargoPaymentService;
use App\Services\Cargo\CloseOfBusinessService;
use App\Services\SmsNotifier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Who gets an SMS and when: booking details to sender and receiver once paid, cancellation,
 * account details (always, password hidden in the log), approval requests (throttled) and the
 * close-of-business summary (once a day, to permission holders).
 *
 * Runs against the configured DB inside a transaction (DatabaseTransactions) — never refreshes it.
 * Only a faked test gateway is live; no SMS leaves the machine.
 */
class CargoSmsPolicyTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::whereHas('roles', fn ($q) => $q->where('slug', 'admin'))->first()
            ?? $this->markTestSkipped('Needs an admin user.');

        IntegrationConfig::where('channel', 'sms')->update(['is_active' => false]);
        IntegrationConfig::create([
            'code' => 'TEST_' . strtoupper(Str::random(8)), 'name' => 'Test gateway', 'provider' => 'nextsms', 'channel' => 'sms',
            'environment' => 'production', 'production_url' => 'https://sms-test.test', 'access_token' => 'test-token',
            'extra_credentials' => ['sender_id' => 'TEST'], 'is_active' => true, 'sort_order' => 1,
        ]);
        Http::fake(['sms-test.test/*' => Http::response(['messages' => [['messageId' => 'M-1', 'status' => ['groupName' => 'PENDING']]]], 200)]);

        foreach (['sms_enabled' => '1', 'sms_booking_confirmation_sender' => '1', 'sms_booking_confirmation_receiver' => '1',
            'default_locale' => 'en', 'cargo_payment_simulator_enabled' => '0'] as $key => $value) {
            SystemConfig::setValue($key, $value);
        }
    }

    private function sentTexts(): array
    {
        return Http::recorded()->map(fn ($pair) => $pair[0]['messages'][0])->all();
    }

    private function logs(string $event)
    {
        return NotificationLog::where('event', $event)->where('created_at', '>=', now()->subMinute())->orderBy('id')->get();
    }

    /** A booking awaiting payment (same shape as CargoPaymentFlowTest). */
    private function pendingConsignment(): Consignment
    {
        $service = TransportService::whereHas('cargoInventory')->first() ?? $this->markTestSkipped('Needs a transport service with cargo inventory.');
        $type = CargoType::first() ?? $this->markTestSkipped('Needs a cargo type.');
        CargoInventory::where('transport_service_id', $service->id)->increment('used_weight_kg', 100);

        $c = Consignment::create([
            'uuid' => (string) Str::uuid(), 'consignment_number' => 'CGSMS' . strtoupper(Str::random(6)),
            'tracking_number' => strtoupper(Str::random(10)), 'qr_code_token' => 'CQ-' . strtoupper(Str::random(12)),
            'transport_service_id' => $service->id, 'route_id' => $service->route_id,
            'sender_name' => 'Asha Sender', 'sender_phone' => '0754000001',
            'receiver_name' => 'Juma Receiver', 'receiver_phone' => '0754000002',
            'total_amount_tzs' => 50000, 'net_amount' => 50000, 'currency' => 'TZS', 'total_weight_kg' => 100,
            'status' => 'pending_payment', 'payment_status' => 'unpaid', 'booked_by_user_id' => $this->admin->id,
        ]);
        $c->items()->create([
            'cargo_type_id' => $type->id, 'item_number' => $c->consignment_number . '-01', 'quantity' => 1,
            'weight_kg' => 100, 'final_fare' => 50000, 'status' => 'pending_payment', 'qr_code_token' => 'CI' . strtoupper(Str::random(12)),
        ]);

        return $c;
    }

    private function payCash(Consignment $c): void
    {
        app(CargoPaymentService::class)->start($c, PaymentMethod::where('slug', 'cash')->firstOrFail(), null, $this->admin);
    }

    public function test_a_paid_booking_texts_the_sender_and_the_receiver(): void
    {
        $c = $this->pendingConsignment();
        $this->assertSame([], $this->sentTexts(), 'nothing is sent while the booking is unpaid');

        $this->payCash($c);

        $this->assertSame('sent', $this->logs('cargo_booking_sender')->last()?->status);
        $this->assertSame('+255754000001', $this->logs('cargo_booking_sender')->last()->recipient);
        $this->assertSame('sent', $this->logs('cargo_booking_receiver')->last()?->status);
        $texts = collect($this->sentTexts());
        $this->assertCount(2, $texts);
        $this->assertTrue($texts->contains(fn ($m) => $m['to'] === ['255754000001'] && str_contains($m['text'], $c->consignment_number) && str_contains($m['text'], 'Receiver: Juma Receiver')));
        $this->assertTrue($texts->contains(fn ($m) => $m['to'] === ['255754000002'] && str_contains($m['text'], 'Asha Sender has sent you cargo')));
    }

    public function test_switches_turn_booking_sms_off(): void
    {
        SystemConfig::setValue('sms_booking_confirmation_receiver', '0');
        $this->payCash($this->pendingConsignment());

        $this->assertSame('sent', $this->logs('cargo_booking_sender')->last()?->status);
        $receiver = $this->logs('cargo_booking_receiver')->last();
        $this->assertSame('skipped', $receiver?->status);
        $this->assertStringContainsString('sms_booking_confirmation_receiver', $receiver->error_message);

        SystemConfig::setValue('sms_enabled', '0');
        Http::fake(); // reset the record
        $this->payCash($this->pendingConsignment());
        $this->assertSame('skipped', $this->logs('cargo_booking_sender')->last()->status);
        $this->assertSame([], $this->sentTexts(), 'master switch off: no booking SMS');
    }

    public function test_cancelling_a_paid_booking_texts_the_sender(): void
    {
        $c = $this->pendingConsignment();
        $this->payCash($c);

        app(CargoBookingService::class)->cancelBooking($c->fresh(), $this->admin->id, 'Vessel unavailable');

        $log = $this->logs('cargo_cancelled')->last();
        $this->assertSame('sent', $log?->status);
        $this->assertSame('+255754000001', $log->recipient);
    }

    public function test_account_details_always_go_out_and_the_password_stays_out_of_the_log(): void
    {
        SystemConfig::setValue('sms_enabled', '0');
        $user = User::forceCreate([
            'email' => 'sms-' . Str::random(8) . '@example.test', 'password' => bcrypt('x'), 'first_name' => 'New', 'last_name' => 'Clerk',
            'phone' => '+2557' . random_int(10000000, 99999999), 'domain' => 'system_user', 'status' => 'active',
        ]);

        AccountCredentialsNotification::sendAfterCommit($user, 'Temp#Pass123');

        $log = $this->logs('account_credentials')->last();
        $this->assertSame('sent', $log?->status, 'not gated by the master switch');
        $this->assertStringNotContainsString('Temp#Pass123', (string) $log->content);
        $this->assertStringContainsString('Temp#Pass123', $this->sentTexts()[0]['text'], 'the user does receive it');
    }

    public function test_approval_sms_is_one_per_checker_per_window_then_bundled(): void
    {
        $checker = User::find(1) ?? $this->markTestSkipped('Needs user 1.');
        $checker->forceFill(['phone' => $checker->phone ?: '+255700000001'])->save();
        $request = fn () => DualControlRequest::create([
            'maker_id' => $this->admin->id, 'operation_type' => 'setting_update', 'resource_type' => 'system_config', 'resource_id' => 1,
            'payload_before' => [], 'payload_after' => ['value' => 'x'], 'status' => 'pending', 'expires_at' => now()->addDay(),
        ]);
        $notifier = app(SmsNotifier::class);

        $first = $notifier->approvalNeeded($checker, $request());
        $second = $notifier->approvalNeeded($checker, $request());

        $this->assertSame('sent', $first->status);
        $this->assertSame('skipped', $second->status);
        $this->assertStringStartsWith('Bundled:', $second->error_message);
        $this->assertCount(1, $this->sentTexts());

        $this->travel(11)->minutes();
        $this->assertGreaterThanOrEqual(1, $notifier->sendBundledApprovals());
        $this->assertCount(2, $this->sentTexts(), 'the bundled request goes out once the window passes');
    }

    public function test_close_of_business_goes_once_a_day_to_permission_holders(): void
    {
        BusinessDayClosure::whereDate('business_date', today())->delete();
        $service = app(CloseOfBusinessService::class);
        $holders = $service->recipients();
        if ($holders->isEmpty()) {
            $this->markTestSkipped('Nobody holds close_of_business:receive with a phone.');
        }
        $this->assertTrue($holders->every(fn (User $u) => $u->hasPermission('close_of_business:receive')));

        $closure = $service->closeIfDue(force: true, closedBy: $this->admin->id);

        $this->assertNotNull($closure);
        $this->assertSame('manual', $closure->trigger);
        $this->assertSame($holders->count(), $closure->recipients_count);
        $this->assertSame($holders->count(), $closure->sent_count);
        $this->assertCount($holders->count(), $this->sentTexts());
        $this->assertStringContainsString('close of business', $this->sentTexts()[0]['text']);
        $this->assertNull($service->closeIfDue(force: true), 'a day closes once');

        $service->deliver($closure->fresh());
        $this->assertCount($holders->count(), $this->sentTexts(), 'resend skips those who already got it');
    }
}
