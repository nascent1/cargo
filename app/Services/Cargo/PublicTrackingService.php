<?php

namespace App\Services\Cargo;

use App\Models\Consignment;
use App\Models\TrackingEvent;
use App\Support\CargoUi;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * What anyone with a booking or tracking number may see: status, a milestone timeline, route,
 * departure, vessel, item count and masked names. Never amounts, phone numbers, item contents or
 * the notes on tracking events (they hold internal details such as discount reasons).
 */
class PublicTrackingService
{
    /** Public milestones in order (status => label). Unclaimed counts as arrived. */
    public const MILESTONES = [
        'booked' => 'Booked',
        'loaded' => 'Loaded on the vessel',
        'in_transit' => 'On the way',
        'delivered' => 'Arrived',
        'collected' => 'Collected',
    ];

    private const REACHED = [
        'pending_payment' => -1, 'booked' => 0, 'loaded' => 1, 'in_transit' => 2,
        'delivered' => 3, 'unclaimed' => 3, 'collected' => 4,
    ];

    public static function isValidNumber(string $number): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9-]{4,40}$/', trim($number));
    }

    public function lookup(string $number): ?array
    {
        $number = strtoupper(trim($number));
        if (!self::isValidNumber($number)) {
            return null;
        }

        $c = Consignment::with(['route.origin', 'route.destination', 'transportService.transportUnit', 'items'])
            ->where(fn ($q) => $q->where('consignment_number', $number)->orWhere('tracking_number', $number))
            ->first();
        if (!$c) {
            return null;
        }

        $from = $c->route?->origin?->name;
        $to = $c->route?->destination?->name;
        $service = $c->transportService;
        $departure = $service ? Carbon::parse(Carbon::parse($service->departure_date)->toDateString() . ' ' . substr((string) $service->departure_time, 0, 5)) : null;
        $items = $c->items->where('status', '!=', 'cancelled');

        return [
            'number' => $c->consignment_number,
            'status' => $c->status,
            'status_label' => $c->isLeftBehind() ? 'Left behind' : (CargoUi::STATUS[$c->status]['label'] ?? Str::headline((string) $c->status)),
            'message' => $c->isLeftBehind()
                ? 'Not on the ' . ($departure ? $departure->format('D d M, H:i') : 'planned') . ' departure (the vessel was full). We will contact you about the next one.'
                : $this->message($c->status, $to, $service?->transportUnit?->name, $departure),
            'cancelled' => $c->status === 'cancelled',
            'left_behind' => $c->isLeftBehind(),
            'route' => ['from' => $from, 'to' => $to],
            'departure' => $departure ? [
                'at' => $departure->toIso8601String(),
                'vessel' => $service?->transportUnit?->name,
            ] : null,
            'estimated_arrival' => $c->estimated_arrival_at ? Carbon::parse($c->estimated_arrival_at)->toIso8601String() : null,
            'items' => (int) $items->sum('quantity') ?: $items->count(),
            'sender' => self::mask($c->sender_name),
            'receiver' => self::mask($c->receiver_name),
            'timeline' => $c->status === 'cancelled' ? [] : $this->timeline($c),
            'updated_at' => $c->updated_at?->toIso8601String(),
        ];
    }

    /** First name, then initials: "Juma Ali Said" → "Juma A*** S***". */
    public static function mask(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
        if (!$parts) {
            return '—';
        }

        return collect($parts)->map(fn ($p, $i) => $i === 0 ? $p : mb_substr($p, 0, 1) . '***')->implode(' ');
    }

    private function timeline(Consignment $c): array
    {
        $reached = self::REACHED[$c->status] ?? -1;
        // When each status was first reached (consignment-level status changes only; notes are never read)
        $times = TrackingEvent::where('consignment_id', $c->id)->whereNull('cargo_item_id')
            ->whereIn('to_status', array_keys(self::MILESTONES))
            ->selectRaw('to_status, MIN(created_at) as at')->groupBy('to_status')->pluck('at', 'to_status');
        $fallback = ['booked' => $c->paid_at, 'delivered' => $c->delivered_at, 'collected' => $c->collected_at];

        return collect(array_keys(self::MILESTONES))->values()->map(function ($status, $i) use ($reached, $times, $fallback) {
            $at = $i <= $reached ? ($times[$status] ?? $fallback[$status] ?? null) : null;

            return [
                'status' => $status,
                'label' => self::MILESTONES[$status],
                'reached' => $i <= $reached,
                'current' => $i === $reached,
                'at' => $at ? Carbon::parse($at)->toIso8601String() : null,
            ];
        })->all();
    }

    private function message(string $status, ?string $to, ?string $vessel, ?Carbon $departure): string
    {
        $when = $departure ? $departure->format('D d M, H:i') : 'its departure';
        $to = $to ?: 'the destination';

        return match ($status) {
            'pending_payment' => 'Waiting for payment. The booking is confirmed once it is paid.',
            'booked' => "Booked for the {$when} departure.",
            'loaded' => 'Loaded' . ($vessel ? " on {$vessel}" : '') . ' and waiting to sail.',
            'in_transit' => "On the way to {$to}.",
            'delivered' => "Arrived at {$to}. The receiver can collect it with an ID and the booking number.",
            'unclaimed' => "Arrived at {$to} and still waiting to be collected. Please contact us.",
            'collected' => 'Collected by the receiver.',
            'cancelled' => 'This booking was cancelled.',
            default => Str::headline($status) . '.',
        };
    }
}
