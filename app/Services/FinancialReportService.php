<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use App\Services\Traits\ReportFilterTrait;

class FinancialReportService
{
    use ReportFilterTrait;

    /**
     * Revenue Report: breakdown by route, ferry, class, or agent.
     */
    public function revenueReport(array $filters): array
    {
        $query = Ticket::query()
            ->whereNotIn('tickets.status', ['cancelled'])
            ->join('voyages', 'tickets.voyage_id', '=', 'voyages.id')
            ->join('routes', 'voyages.route_id', '=', 'routes.id')
            ->join('ferries', 'voyages.ferry_id', '=', 'ferries.id')
            ->join('ferry_classes', 'tickets.ferry_class_id', '=', 'ferry_classes.id')
            ->join('bookings', 'tickets.booking_id', '=', 'bookings.id')
            ->leftJoin('passenger_types', 'tickets.passenger_type_id', '=', 'passenger_types.id');

        $this->applyUniversalFilters($query, $filters);

        $groupBy = $filters['group_by'] ?? 'route';
        $groupField = match ($groupBy) {
            'ferry' => 'ferries.name',
            'class' => 'ferry_classes.name',
            'agent' => DB::raw("CONCAT(u.first_name, ' ', u.last_name)"),
            default => 'routes.name',
        };

        if ($groupBy === 'agent') {
            $query->join('users as u', 'bookings.booked_by_user_id', '=', 'u.id');
        }

        return $query->select(
            DB::raw("$groupField as group_label"),
            DB::raw('COUNT(*) as total_tickets'),
            DB::raw("SUM(CASE WHEN tickets.currency = 'TZS' THEN tickets.final_fare ELSE 0 END) as total_revenue_tzs"),
            DB::raw("SUM(CASE WHEN tickets.currency = 'USD' THEN tickets.final_fare ELSE 0 END) as total_revenue_usd"),
            DB::raw("SUM(CASE WHEN passenger_types.code = 'adult' THEN 1 ELSE 0 END) as adults"),
            DB::raw("SUM(CASE WHEN passenger_types.code = 'child' THEN 1 ELSE 0 END) as children")
        )
        ->groupBy(DB::raw("$groupField"))
        ->orderByDesc('total_revenue_tzs')
        ->get()
        ->toArray();
    }

    /**
     * Sales Summary: daily aggregation with source breakdown.
     */
    public function salesSummary(array $filters): array
    {
        $query = Ticket::whereNotIn('tickets.status', ['cancelled'])
            ->join('bookings', 'tickets.booking_id', '=', 'bookings.id')
            ->join('users', 'bookings.booked_by_user_id', '=', 'users.id');

        $this->applyDateFilter($query, $filters, 'tickets.created_at');

        return $query->select(
            DB::raw('DATE(tickets.created_at) as sale_date'),
            'users.domain as source',
            DB::raw('COUNT(*) as tickets'),
            DB::raw("SUM(CASE WHEN tickets.currency = 'TZS' THEN tickets.final_fare ELSE 0 END) as revenue_tzs"),
            DB::raw("SUM(CASE WHEN tickets.currency = 'USD' THEN tickets.final_fare ELSE 0 END) as revenue_usd")
        )
        ->groupBy('sale_date', 'users.domain')
        ->orderByDesc('sale_date')
        ->get()
        ->toArray();
    }

    /**
     * Cancellation Report: all cancelled tickets with penalty info.
     */
    public function cancellationReport(array $filters): array
    {
        $query = Ticket::where('tickets.status', 'cancelled')
            ->with(['booking.bookedBy', 'voyage.route', 'ferryClass', 'passenger.passengerType']);

        if (empty($filters['date_target'])) {
            $filters['date_target'] = 'updated_at';
        }

        $this->applyUniversalFilters($query, $filters);

        if (($filters['view_mode'] ?? 'detailed') === 'summary') {
            return $query->join('voyages', 'tickets.voyage_id', '=', 'voyages.id')
                ->join('routes', 'voyages.route_id', '=', 'routes.id')
                ->select(
                    'routes.name as label',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(tickets.cancellation_fee_amount) as total_penalty'),
                    DB::raw('SUM(tickets.final_fare - IFNULL(tickets.cancellation_fee_amount, 0)) as total_refund')
                )
                ->groupBy('routes.name')
                ->orderByDesc('count')
                ->get()
                ->toArray();
        }

        return $query->orderByDesc('tickets.updated_at')
            ->limit(500)->get()
            ->map(function($item) {
                $nationalityName = 'N/A';
                if ($item->nationality_type == 1) $nationalityName = 'Resident';
                elseif ($item->nationality_type == 2) $nationalityName = 'Non-resident';
                
                $currency = $item->currency ?? 'TZS';
                $fare = (float) $item->final_fare;
                $tsh = $currency === 'TZS' ? $fare : 0;
                $usd = $currency === 'USD' ? $fare : 0;
                $rate = $item->exchange_rate ?? \App\Services\ExchangeRateService::getCurrentRate('USD', 'TZS');
                $totalTsh = $currency === 'USD' ? ($fare * $rate) : $fare;

                return [
                    'ticket'         => $item->ticket_number,
                    'booking_ref'    => $item->booking?->booking_reference,
                    'passenger_name' => $item->passenger ? trim($item->passenger->first_name . ' ' . $item->passenger->last_name) : ($item->passenger_name ?: '(N/A)'),
                    'gender'         => $item->passenger ? $item->passenger->gender : ($item->passenger_gender ?: '(N/A)'),
                    'type'           => $item->passenger ? $item->passenger->passengerType?->name : '(N/A)',
                    'nationality'    => $nationalityName,
                    'tsh_amount'     => $tsh,
                    'usd_amount'     => $usd,
                    'rate'           => $rate,
                    'status'         => $item->status,
                    'class'          => $item->ferryClass?->name,
                    'journey_date'   => $item->voyage?->departure_date ? \Carbon\Carbon::parse($item->voyage->departure_date)->format('d/m/Y') : '',
                    'booking_date'   => $item->created_at?->format('d/m/Y'),
                    'route'          => $item->voyage?->route?->name,
                    'total_tsh'      => $totalTsh,
                    'booking_status' => $item->booking?->status ? strtoupper($item->booking->status) : 'N/A',
                    'fare'           => $fare,
                    'penalty'        => $item->cancellation_fee_amount ?? 0,
                    'refund'         => $fare - ($item->cancellation_fee_amount ?? 0),
                    'cancelled_at'   => $item->updated_at?->format('Y-m-d H:i'),
                    'cancelled_by'   => $this->getCancellerName($item),
                ];
            })
            ->toArray();
    }
    
    /**
     * Helper to compute the cash ledger amount for a ticket from a specific user's (or agency's) perspective.
     */
    public static function calculateLedgerAmount($item, array $userIds, bool $isAdminViewAll = false)
    {
        $isBooker = $isAdminViewAll || ($item->booking && in_array($item->booking->booked_by_user_id, $userIds));
        
        if ($item->status !== 'cancelled') {
            return $isBooker ? (float)$item->final_fare : 0.0;
        }

        $cancellerId = null;
        if ($item->booking && $item->booking->relationLoaded('logs') && $item->booking->logs) {
            $cancelLog = $item->booking->logs->whereIn('action', ['Ticket Cancelled', 'Booking Cancelled'])
                ->where('ticket_id', $item->id)->first();
            if (!$cancelLog) {
                $cancelLog = $item->booking->logs->where('action', 'Booking Cancelled')->first();
            }
            if ($cancelLog) {
                $cancellerId = $cancelLog->performed_by_user_id;
            }
        } else {
            $cancelLog = \App\Models\BookingLog::where('booking_id', $item->booking_id)
                ->where(function($q) use ($item) {
                    $q->where('ticket_id', $item->id)->where('action', 'Ticket Cancelled')
                      ->orWhere('action', 'Booking Cancelled');
                })
                ->latest('id')->first();
            if ($cancelLog) {
                $cancellerId = $cancelLog->performed_by_user_id;
            }
        }

        $isCanceller = in_array($cancellerId, $userIds);
        $penalty = (float)($item->cancellation_fee_amount ?? 0);
        $refundAmount = ((float)$item->final_fare) - $penalty;

        if ($isBooker && $isCanceller) {
            return $penalty;
        } elseif ($isBooker && !$isCanceller) {
            return (float)$item->final_fare;
        } elseif (!$isBooker && $isCanceller) {
            return -$refundAmount;
        }

        return 0.0;
    }

    /**
     * Booking Report: detailed list of individual tickets.
     */
    public function bookingReport(array $filters): array
    {
        $query = Ticket::with(['booking.bookedBy', 'voyage.route', 'ferryClass', 'passenger.passengerType', 'booking.logs']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $this->applyUniversalFilters($query, $filters);

        $user = auth()->user();
        $userIds = [$user->id];
        $isAdminViewAll = method_exists($user, 'hasPermission') ? $user->hasPermission('report:view_all') : false;

        if ($user->domain === 'agent') {
            $agentId = $user->agent_id ?? \App\Models\Agent::where('user_id', $user->id)->value('id');
            if ($agentId) {
                $assocIds = \App\Models\AssociateAgent::where('agent_id', $agentId)->pluck('user_id')->toArray();
                $userIds = array_merge($userIds, $assocIds);
            }
        }

        if (($filters['view_mode'] ?? 'detailed') === 'summary') {
            $tickets = $query->join('voyages', 'tickets.voyage_id', '=', 'voyages.id')
                ->join('routes', 'voyages.route_id', '=', 'routes.id')
                ->select('routes.name as label', 'tickets.*', 'bookings.booked_by_user_id', 'voyages.route_id')
                ->join('bookings', 'tickets.booking_id', '=', 'bookings.id')
                ->get();
            
            $summary = [];
            foreach ($tickets as $ticket) {
                $route = $ticket->label;
                if (!isset($summary[$route])) {
                    $summary[$route] = [
                        'label' => $route,
                        'count' => 0,
                        'total_fare_tzs' => 0,
                        'total_fare_usd' => 0,
                    ];
                }
                
                $fare = self::calculateLedgerAmount($ticket, $userIds, $isAdminViewAll);
                
                $summary[$route]['count']++;
                if ($ticket->currency === 'USD') {
                    $summary[$route]['total_fare_usd'] += $fare;
                } else {
                    $summary[$route]['total_fare_tzs'] += $fare;
                }
            }
            
            $summaryList = array_values($summary);
            usort($summaryList, fn($a, $b) => $b['count'] <=> $a['count']);
            return $summaryList;
        }

        return $query->orderByDesc('tickets.created_at')
            ->limit(1000)->get()
            ->map(function($item) use ($userIds, $isAdminViewAll) {
                $fare = self::calculateLedgerAmount($item, $userIds, $isAdminViewAll);

                $nationalityName = 'N/A';
                if ($item->nationality_type == 1) $nationalityName = 'Resident';
                elseif ($item->nationality_type == 2) $nationalityName = 'Non-resident';
                
                $currency = $item->currency ?? 'TZS';
                $tsh = $currency === 'TZS' ? $fare : 0;
                $usd = $currency === 'USD' ? $fare : 0;
                $rate = $item->exchange_rate ?? \App\Services\ExchangeRateService::getCurrentRate('USD', 'TZS');
                $totalTsh = $currency === 'USD' ? ($fare * $rate) : $fare;

                return [
                    'date'           => $item->created_at?->format('Y-m-d H:i'),
                    'ticket'         => $item->ticket_number,
                    'booking_ref'    => $item->booking?->booking_reference,
                    'passenger_name' => $item->passenger ? trim($item->passenger->first_name . ' ' . $item->passenger->last_name) : ($item->passenger_name ?: '(N/A)'),
                    'gender'         => $item->passenger ? $item->passenger->gender : ($item->passenger_gender ?: '(N/A)'),
                    'type'           => $item->passenger ? $item->passenger->passengerType?->name : '(N/A)',
                    'route'          => $item->voyage?->route?->name,
                    'class'          => $item->ferryClass?->name,
                    'fare'           => $fare,
                    'currency'       => $currency,
                    'status'         => $item->status,
                    'booked_by'      => $item->booking?->bookedBy?->full_name ?? 'System',
                    'nationality'    => $nationalityName,
                    'tsh_amount'     => $tsh,
                    'usd_amount'     => $usd,
                    'rate'           => $rate,
                    'journey_date'   => $item->voyage?->departure_date ? \Carbon\Carbon::parse($item->voyage->departure_date)->format('d/m/Y') : '',
                    'booking_date'   => $item->created_at?->format('d/m/Y'),
                    'total_tsh'      => $totalTsh,
                    'booking_status' => $item->booking?->status ? strtoupper($item->booking->status) : 'N/A',
                ];
            })
            ->toArray();
    }

    /**
     * Look up the actual canceller of a ticket (not the booker).
     * Checks cancelled_by_id first, then falls back to BookingLog.
     */
    protected function getCancellerName(Ticket $item): string
    {
        // First check the direct column (Fix 7 populates this)
        if ($item->cancelled_by_id) {
            $canceller = \App\Models\User::find($item->cancelled_by_id);
            if ($canceller) {
                return $canceller->full_name;
            }
        }

        // Fall back to BookingLog
        $log = \App\Models\BookingLog::where('booking_id', $item->booking_id)
            ->where(function ($q) use ($item) {
                $q->where(function ($q2) use ($item) {
                    $q2->where('ticket_id', $item->id)->where('action', 'Ticket Cancelled');
                })->orWhere('action', 'Booking Cancelled');
            })
            ->whereNotNull('performed_by_user_id')
            ->latest('id')->first();

        if ($log && $log->performed_by_user_id) {
            $actor = \App\Models\User::find($log->performed_by_user_id);
            return $actor?->full_name ?? 'System';
        }

        return 'System';
    }

    /**
     * Payment Channel Analysis: Revenue breakdown by payment method with trend comparison.
     */
    public function paymentChannelAnalysis(array $filters): array
    {
        $query = Ticket::query()
            ->whereNotIn('tickets.status', ['cancelled'])
            ->join('bookings', 'tickets.booking_id', '=', 'bookings.id');

        $this->applyUniversalFilters($query, $filters);

        $current = (clone $query)->select(
            DB::raw("COALESCE(bookings.payment_channel, 'cash') as channel"),
            DB::raw('COUNT(*) as ticket_count'),
            DB::raw("SUM(CASE WHEN tickets.currency = 'TZS' THEN tickets.final_fare ELSE 0 END) as revenue_tzs"),
            DB::raw("SUM(CASE WHEN tickets.currency = 'USD' THEN tickets.final_fare ELSE 0 END) as revenue_usd"),
            DB::raw('AVG(tickets.final_fare) as avg_transaction')
        )
        ->groupBy('channel')
        ->orderByDesc('revenue_tzs')
        ->get()
        ->toArray();

        // Previous period for comparison
        $dateFrom = $filters['date_from'] ?? now()->toDateString();
        $dateTo = $filters['date_to'] ?? now()->toDateString();
        $daySpan = max(1, \Carbon\Carbon::parse($dateFrom)->diffInDays(\Carbon\Carbon::parse($dateTo)) + 1);

        $prevFrom = \Carbon\Carbon::parse($dateFrom)->subDays($daySpan)->toDateString();
        $prevTo = \Carbon\Carbon::parse($dateFrom)->subDay()->toDateString();

        $prevQuery = Ticket::query()
            ->whereNotIn('tickets.status', ['cancelled'])
            ->join('bookings', 'tickets.booking_id', '=', 'bookings.id')
            ->whereDate('tickets.created_at', '>=', $prevFrom)
            ->whereDate('tickets.created_at', '<=', $prevTo);

        $previous = $prevQuery->select(
            DB::raw("COALESCE(bookings.payment_channel, 'cash') as channel"),
            DB::raw("SUM(CASE WHEN tickets.currency = 'TZS' THEN tickets.final_fare ELSE 0 END) as revenue_tzs")
        )
        ->groupBy('channel')
        ->pluck('revenue_tzs', 'channel')
        ->toArray();

        $totalRevenue = array_sum(array_column($current, 'revenue_tzs'));

        return array_map(function ($row) use ($previous, $totalRevenue) {
            $prevRev = $previous[$row['channel']] ?? 0;
            $growth = $prevRev > 0 ? round((($row['revenue_tzs'] - $prevRev) / $prevRev) * 100, 1) : ($row['revenue_tzs'] > 0 ? 100.0 : 0.0);
            $share = $totalRevenue > 0 ? round(($row['revenue_tzs'] / $totalRevenue) * 100, 1) : 0;

            return array_merge($row, [
                'prev_revenue_tzs' => (float) $prevRev,
                'growth_percent' => $growth,
                'revenue_share_percent' => $share,
            ]);
        }, $current);
    }

    /**
     * Peak Hour Analysis: Hourly booking distribution for demand forecasting.
     */
    public function peakHourAnalysis(array $filters): array
    {
        $query = Ticket::query()
            ->whereNotIn('tickets.status', ['cancelled'])
            ->join('bookings', 'tickets.booking_id', '=', 'bookings.id');

        $this->applyUniversalFilters($query, $filters);

        $hourly = $query->select(
            DB::raw('EXTRACT(HOUR FROM tickets.created_at)::int as hour'),
            DB::raw('COUNT(*) as ticket_count'),
            DB::raw("SUM(CASE WHEN tickets.currency = 'TZS' THEN tickets.final_fare ELSE 0 END) as revenue_tzs"),
            DB::raw("SUM(CASE WHEN tickets.currency = 'USD' THEN tickets.final_fare ELSE 0 END) as revenue_usd"),
            DB::raw('AVG(tickets.final_fare) as avg_fare')
        )
        ->groupBy('hour')
        ->orderBy('hour')
        ->get()
        ->keyBy('hour')
        ->toArray();

        // Fill all 24 hours
        $result = [];
        $maxTickets = 0;
        for ($h = 0; $h < 24; $h++) {
            $data = $hourly[$h] ?? [
                'hour' => $h,
                'ticket_count' => 0,
                'revenue_tzs' => 0,
                'revenue_usd' => 0,
                'avg_fare' => 0,
            ];
            $data['hour_label'] = sprintf('%02d:00 - %02d:59', $h, $h);
            if ($data['ticket_count'] > $maxTickets) {
                $maxTickets = $data['ticket_count'];
            }
            $result[] = $data;
        }

        // Tag peak/off-peak
        foreach ($result as &$row) {
            if ($maxTickets > 0 && $row['ticket_count'] >= ($maxTickets * 0.7)) {
                $row['intensity'] = 'peak';
            } elseif ($row['ticket_count'] > 0) {
                $row['intensity'] = 'normal';
            } else {
                $row['intensity'] = 'off_peak';
            }
        }

        return $result;
    }
}
