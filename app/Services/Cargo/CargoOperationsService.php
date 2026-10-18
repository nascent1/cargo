<?php

namespace App\Services\Cargo;

use App\Models\AuditLog;
use App\Models\Consignment;
use App\Models\TrackingEvent;
use App\Models\TransportService;
use App\Models\User;
use App\Services\SmsNotifier;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The physical journey of a paid booking, one step at a time:
 *
 *   booked → loaded (port officer, before the vessel leaves)
 *          → in transit (the vessel departs; paid bookings not loaded are LEFT BEHIND)
 *          → arrived / "delivered" (the vessel arrives)
 *          → collected (hand-over with ID and an OTP-confirmed mobile: CargoHandoverService)
 *
 * Departing and arriving come from the journey (Operations › Journeys), so the whole vessel moves
 * together. Supervisors can correct a wrong status with a reason. Every change is a tracking event.
 * Refusals throw DomainException with a message for the person on screen.
 */
class CargoOperationsService
{
    /** Journey states after which nothing more can be loaded. */
    public const CLOSED_JOURNEY = ['departed', 'arrived', 'cancelled'];

    /** Statuses a supervisor may set when correcting a mistake. */
    public const CORRECTABLE = ['booked', 'loaded', 'in_transit', 'delivered', 'unclaimed', 'collected'];

    public function __construct(private CargoTrackingService $tracking)
    {
    }

    // ── Loading ──────────────────────────────────────────────

    public function load(Consignment $c, ?int $userId, string $how = 'manifest'): void
    {
        $c->loadMissing('transportService.transportUnit');
        $service = $c->transportService;

        if ($c->status !== 'booked') {
            throw new DomainException(match ($c->status) {
                'pending_payment' => "{$c->consignment_number} is awaiting payment and can't be loaded.",
                'cancelled' => "{$c->consignment_number} was cancelled.",
                'loaded' => "{$c->consignment_number} is already loaded.",
                default => "{$c->consignment_number} is already " . str_replace('_', ' ', $c->status) . '.',
            });
        }
        if ($service && in_array($service->status, self::CLOSED_JOURNEY, true)) {
            throw new DomainException("{$c->consignment_number}: the vessel has already " . ($service->status === 'cancelled' ? 'been cancelled' : 'left') . '.');
        }

        $vessel = $service?->transportUnit?->name;
        $this->tracking->logEvent($c, 'loaded', 'Loaded' . ($vessel ? " on {$vessel}" : '') . " ({$how})", $userId);
    }

    /**
     * One item scanned onto the vessel. When every (not cancelled) item of the booking is loaded,
     * the booking itself becomes loaded. Returns true when that happened.
     */
    public function loadItem(\App\Models\CargoItem $item, ?int $userId): bool
    {
        return DB::transaction(function () use ($item, $userId) {
            $c = Consignment::whereKey($item->consignment_id)->lockForUpdate()->with('transportService.transportUnit')->first();
            if ($c->status !== 'booked') {
                $this->load($c, $userId, 'scanned'); // throws the right message (unpaid, already loaded, …)
            }
            if (in_array($c->transportService?->status, self::CLOSED_JOURNEY, true)) {
                throw new DomainException("{$c->consignment_number}: the vessel has already left.");
            }
            if ($item->status === 'loaded') {
                throw new DomainException("Item {$item->item_number} is already loaded.");
            }

            $this->tracking->logItemEvent($item, 'loaded', 'Item loaded (scanned)', $userId);

            $waiting = $c->items()->whereNotIn('status', ['loaded', 'cancelled'])->exists();
            if ($waiting) {
                return false;
            }
            $c->update(['status' => 'loaded']);
            TrackingEvent::create([
                'consignment_id' => $c->id, 'event_type' => 'state_change', 'from_status' => 'booked', 'to_status' => 'loaded',
                'notes' => 'All items scanned onto ' . ($c->transportService?->transportUnit?->name ?? 'the vessel'),
                'performed_by_id' => $userId, 'created_at' => now(),
            ]);

            return true;
        });
    }

    /**
     * Load several bookings of one departure. Returns [loaded => [numbers], skipped => [number => reason]].
     */
    public function loadMany(TransportService $service, iterable $consignments, ?int $userId, string $how = 'manifest'): array
    {
        $result = ['loaded' => [], 'skipped' => []];
        foreach ($consignments as $c) {
            if ((int) $c->transport_service_id !== (int) $service->id) {
                $result['skipped'][$c->consignment_number] = 'Booked on a different departure.';
                continue;
            }
            try {
                DB::transaction(fn () => $this->load(Consignment::whereKey($c->id)->lockForUpdate()->first(), $userId, $how));
                $result['loaded'][] = $c->consignment_number;
            } catch (DomainException $e) {
                $result['skipped'][$c->consignment_number] = $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * Find a booking on this departure from what was typed or scanned: booking number, tracking
     * number or an item label (qr_code_token).
     */
    public function findOnDeparture(TransportService $service, string $code): ?Consignment
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return null;
        }

        return Consignment::where('transport_service_id', $service->id)
            ->where(fn ($q) => $q->where('consignment_number', $code)->orWhere('tracking_number', $code)
                ->orWhereHas('items', fn ($i) => $i->where('qr_code_token', $code)))
            ->first();
    }

    // ── Departure and arrival (from the journey) ─────────────

    /**
     * The vessel left: loaded cargo goes in transit (SMS to sender and receiver); paid bookings
     * still "booked" were not loaded and are marked left behind. Safe to run more than once.
     *
     * @return array{in_transit: int, left_behind: int}
     */
    public function departed(TransportService $service, ?int $userId, ?CarbonInterface $at = null): array
    {
        $service->loadMissing(['transportUnit', 'route.origin']);
        $vessel = $service->transportUnit?->name ?? 'the vessel';
        $origin = $service->route?->origin?->name;
        $at ??= now();

        $moved = Consignment::with('items')->where('transport_service_id', $service->id)->where('status', 'loaded')->get();
        foreach ($moved as $c) {
            $this->tracking->logEvent($c, 'in_transit', "Departed" . ($origin ? " {$origin}" : '') . " on {$vessel}", $userId);
        }

        $left = Consignment::where('transport_service_id', $service->id)->where('status', 'booked')->whereNull('left_behind_at')->get();
        foreach ($left as $c) {
            $c->update(['left_behind_at' => $at]);
            TrackingEvent::create([
                'consignment_id' => $c->id, 'event_type' => 'left_behind', 'from_status' => 'booked',
                'notes' => "Not loaded when {$vessel} departed — left behind", 'performed_by_id' => $userId, 'created_at' => now(),
            ]);
        }

        $this->notifyLater($moved, 'cargo_in_transit');

        return ['in_transit' => $moved->count(), 'left_behind' => $left->count()];
    }

    /**
     * The vessel arrived: cargo in transit is ready for collection (SMS to sender and receiver).
     *
     * @return array{arrived: int}
     */
    public function arrived(TransportService $service, ?int $userId): array
    {
        $service->loadMissing('route.destination');
        $destination = $service->route?->destination?->name;

        $arrived = Consignment::with('items')->where('transport_service_id', $service->id)->where('status', 'in_transit')->get();
        foreach ($arrived as $c) {
            $this->tracking->logEvent($c, 'delivered', 'Arrived' . ($destination ? " at {$destination}" : '') . ' — ready for collection', $userId);
        }

        $this->notifyLater($arrived, 'cargo_arrived');

        return ['arrived' => $arrived->count()];
    }

    /**
     * What a departure would do if it left now (for the Depart dialog).
     *
     * @return array{loaded: int, not_loaded: int, unpaid: int, in_transit: int}
     */
    public function departurePreview(TransportService $service): array
    {
        $counts = Consignment::where('transport_service_id', $service->id)
            ->selectRaw('status, COUNT(*) AS n')->groupBy('status')->pluck('n', 'status');

        return [
            'loaded' => (int) ($counts['loaded'] ?? 0),
            'not_loaded' => (int) ($counts['booked'] ?? 0),
            'unpaid' => (int) ($counts['pending_payment'] ?? 0),
            'in_transit' => (int) ($counts['in_transit'] ?? 0),
        ];
    }

    // ── Per-booking actions ──────────────────────────────────

    /**
     * Buttons a user gets on a booking: load (before departure), hand over (arrived), correct.
     *
     * @return array{load: bool, handover: bool, correct: bool}
     */
    public function actions(Consignment $c, User $user): array
    {
        $c->loadMissing('transportService');
        $journeyOpen = !in_array($c->transportService?->status, self::CLOSED_JOURNEY, true);

        return [
            'load' => $c->status === 'booked' && $journeyOpen && $user->hasPermission('cargo-load'),
            'handover' => in_array($c->status, ['delivered', 'unclaimed'], true) && $user->hasPermission('cargo-collect'),
            'correct' => !in_array($c->status, ['pending_payment', 'cancelled'], true) && $user->hasPermission('cargo-status-correct'),
        ];
    }

    /**
     * Supervisor fix for a wrong status (e.g. marked loaded by mistake). Needs a reason; audited.
     */
    public function correct(Consignment $c, string $status, string $reason, User $by, ?string $ip = null): void
    {
        if (!in_array($status, self::CORRECTABLE, true)) {
            throw new DomainException('That status can only be reached through payment or cancellation.');
        }
        if (in_array($c->status, ['pending_payment', 'cancelled'], true)) {
            throw new DomainException('This booking is ' . str_replace('_', ' ', $c->status) . '; its status can\'t be corrected here.');
        }
        if ($c->status === $status) {
            throw new DomainException('The booking already has that status.');
        }
        $reason = trim($reason);
        if (mb_strlen($reason) < 5) {
            throw new DomainException('Give a reason for the correction (at least 5 characters).');
        }

        $before = ['status' => $c->status, 'left_behind_at' => $c->left_behind_at?->toDateTimeString()];
        DB::transaction(function () use ($c, $status, $reason, $by) {
            $old = $c->status;
            $this->tracking->logEvent($c, $status, "Status corrected from {$old} to {$status}. Reason: {$reason}", $by->id);
            if ($status !== 'booked' && $c->left_behind_at) {
                $c->update(['left_behind_at' => null]);
            }
        });
        AuditLog::createEntry($by->id, 'cargo_status_correct', 'consignment', $c->id, $before, ['status' => $status, 'reason' => $reason], $ip);
    }

    /**
     * Texts go out after the change is committed and after the response (no queue worker needed),
     * so departing a full vessel doesn't wait on dozens of SMS.
     */
    private function notifyLater(Collection $consignments, string $event): void
    {
        $ids = $consignments->pluck('id')->all();
        if (!$ids) {
            return;
        }

        DB::afterCommit(fn () => defer(function () use ($ids, $event) {
            $sms = app(SmsNotifier::class);
            Consignment::with(['route.origin', 'route.destination', 'transportService.transportUnit', 'items.cargoType'])
                ->whereIn('id', $ids)->get()
                ->each(fn (Consignment $c) => $sms->cargoStatus($c, $event));
        }));
    }
}
