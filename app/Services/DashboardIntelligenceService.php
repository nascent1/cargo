<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentAccount;
use App\Models\AssociateAgent;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\DualControlRequest;
use App\Models\PendingRefund;
use App\Models\Shift;
use App\Models\SystemConfig;
use App\Models\SystemTransaction;
use App\Models\Ticket;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Models\Voyage;
use App\Models\SeatInventory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardIntelligenceService
{
    // ═══════════════════════════════════════════════════════════════
    //  RESOLVE AGENT — utility shared across methods
    // ═══════════════════════════════════════════════════════════════

    private function resolveAgent(User $user): ?Agent
    {
        // Primary agent: User hasOne Agent
        $agent = Agent::where('user_id', $user->id)->first();
        if ($agent) return $agent;

        // Associate agent: find parent agent via AssociateAgent pivot
        $assoc = AssociateAgent::where('user_id', $user->id)->first();
        if ($assoc) {
            return Agent::find($assoc->agent_id);
        }

        return null;
    }

    private function getParentAgentId(User $user): ?int
    {
        // Primary agent
        $agent = Agent::where('user_id', $user->id)->first();
        if ($agent) return $agent->id;

        // Associate
        $assoc = AssociateAgent::where('user_id', $user->id)->first();
        return $assoc?->agent_id;
    }

    // ═══════════════════════════════════════════════════════════════
    //  SHIFT CONTEXT (Sales Officers Only)
    // ═══════════════════════════════════════════════════════════════

    public function getShiftContext(User $user): array
    {
        $today = now()->toDateString();
        $shift = Shift::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        $shiftStart = $shift?->start_time ?? now()->startOfDay();
        $shiftEnd   = $shift?->end_time ?? now()->endOfDay();
        $remainingMinutes = max(0, (int) now()->diffInMinutes($shiftEnd, false));

        // Tickets booked by this user today
        $myTicketsToday = Ticket::whereDate('created_at', $today)
            ->whereNotIn('status', ['cancelled'])
            ->whereHas('booking', fn($q) => $q->where('booked_by_user_id', $user->id))
            ->count();

        $myRevenueToday = (float) Ticket::whereDate('created_at', $today)
            ->whereNotIn('status', ['cancelled'])
            ->whereHas('booking', fn($q) => $q->where('booked_by_user_id', $user->id))
            ->sum('final_fare');

        // Yesterday's count at this same time for pace comparison
        $yesterdayCutoff = now()->subDay();
        $yesterdayStart  = $yesterdayCutoff->copy()->startOfDay();
        $yesterdayAtThisPoint = Ticket::whereBetween('created_at', [$yesterdayStart, $yesterdayCutoff])
            ->whereNotIn('status', ['cancelled'])
            ->whereHas('booking', fn($q) => $q->where('booked_by_user_id', $user->id))
            ->count();

        // Payment method breakdown for today
        $paymentBreakdown = Ticket::whereDate('tickets.created_at', $today)
            ->whereNotIn('tickets.status', ['cancelled'])
            ->whereHas('booking', fn($q) => $q->where('booked_by_user_id', $user->id))
            ->join('bookings', 'tickets.booking_id', '=', 'bookings.id')
            ->select(
                DB::raw("COALESCE(bookings.payment_channel, 'cash') as method"),
                DB::raw('SUM(tickets.final_fare) as total')
            )
            ->groupBy('method')
            ->pluck('total', 'method')
            ->toArray();

        return [
            'has_active_shift'     => $shift !== null,
            'started_at'           => $shiftStart instanceof Carbon ? $shiftStart->format('Y-m-d H:i') : (string) $shiftStart,
            'ends_at'              => $shiftEnd instanceof Carbon ? $shiftEnd->format('Y-m-d H:i') : (string) $shiftEnd,
            'remaining_minutes'    => $remainingMinutes,
            'tickets_this_shift'   => $myTicketsToday,
            'revenue_this_shift'   => $myRevenueToday,
            'yesterday_at_this_point' => $yesterdayAtThisPoint,
            'payment_breakdown'    => $paymentBreakdown,
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    //  ATTENTION ITEMS (Smart Alert Feed)
    // ═══════════════════════════════════════════════════════════════

    public function getAttentionItems(User $user): array
    {
        $today = now()->toDateString();
        $items = [];

        // 1. Capacity Alerts — voyages today/tomorrow at >85% load
        $upcomingVoyages = Voyage::with(['ferry', 'route'])
            ->whereIn('status', ['scheduled', 'boarding'])
            ->where(function ($q) use ($today) {
                $q->where('departure_date', $today)
                  ->orWhere('departure_date', now()->addDay()->toDateString());
            })
            ->get();

        foreach ($upcomingVoyages as $voyage) {
            $capacity = $voyage->ferry?->capacity ?? 0;
            if ($capacity <= 0) continue;

            $sold = $voyage->bookingItems()->whereNotIn('status', ['cancelled'])->count();
            $loadPercent = round(($sold / $capacity) * 100);

            if ($loadPercent >= 85) {
                $remaining = $capacity - $sold;
                $deptTime = Carbon::parse($voyage->departure_date . ' ' . $voyage->departure_time)->format('H:i');
                $items[] = [
                    'type'       => 'capacity_alert',
                    'severity'   => $loadPercent >= 95 ? 'critical' : 'warning',
                    'message'    => ($voyage->ferry?->name ?? 'Vessel') . ' '
                        . $deptTime . ' ' . ($voyage->route?->name ?? '') 
                        . ' is ' . $loadPercent . '% full — ' . $remaining . ' seats left',
                    'voyage_id'  => $voyage->id,
                    'route_id'   => $voyage->route_id,
                    'departure_date' => $voyage->departure_date,
                    'action_url' => '/fast-booking?voyage_id=' . $voyage->id . '&route_id=' . $voyage->route_id . '&date=' . $voyage->departure_date,
                ];
            }
        }

        // 2. Cross-cancellations — tickets booked by this user but cancelled by someone else today
        $crossCancellations = BookingLog::where('action', 'Ticket Cancelled')
            ->whereDate('created_at', $today)
            ->where('performed_by_user_id', '!=', $user->id)
            ->whereHas('booking', fn($q) => $q->where('booked_by_user_id', $user->id))
            ->with(['performedBy', 'booking', 'bookingItem.passenger'])
            ->limit(10)
            ->get();

        if ($crossCancellations->isNotEmpty()) {
            $details = $crossCancellations->map(fn($log) => [
                'booking_ref'  => $log->booking?->booking_reference ?? 'N/A',
                'passenger'    => $log->bookingItem?->passenger?->full_name ?? 'N/A',
                'cancelled_by' => $log->performedBy?->full_name ?? 'System',
                'at'           => $log->created_at?->format('H:i'),
            ])->toArray();

            $items[] = [
                'type'     => 'cross_cancellation',
                'severity' => 'info',
                'message'  => count($details) . ' of your bookings cancelled by others',
                'count'    => count($details),
                'details'  => $details,
            ];
        }

        // 3. Pending dual-control approvals relevant to this user
        $pendingDC = DualControlRequest::where('maker_id', $user->id)
            ->where('status', 'pending')
            ->count();

        if ($pendingDC > 0) {
            $items[] = [
                'type'     => 'dc_pending',
                'severity' => 'info',
                'message'  => $pendingDC . ' dual-control request(s) awaiting approval',
                'count'    => $pendingDC,
            ];
        }

        // 4. Recently resolved DC requests (approved/rejected in last 24h)
        $resolvedDC = DualControlRequest::where('maker_id', $user->id)
            ->whereIn('status', ['approved', 'rejected'])
            ->where('resolved_at', '>=', now()->subDay())
            ->with('checker')
            ->latest('resolved_at')
            ->limit(5)
            ->get();

        foreach ($resolvedDC as $dc) {
            $items[] = [
                'type'     => 'dc_resolved',
                'severity' => $dc->status === 'approved' ? 'success' : 'warning',
                'message'  => ucfirst($dc->operation_type ?? 'Request') . ' request ' . $dc->status
                    . ' by ' . ($dc->checker?->full_name ?? 'Admin'),
                'resolved_at' => $dc->resolved_at?->format('Y-m-d H:i'),
            ];
        }

        return $items;
    }

    // ═══════════════════════════════════════════════════════════════
    //  VOYAGE BOARD (Real-time Seat Inventory)
    // ═══════════════════════════════════════════════════════════════

    public function getVoyageBoard(User $user): array
    {
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        $query = Voyage::with(['ferry', 'route'])
            ->whereIn('status', ['scheduled', 'boarding'])
            ->where(function ($q) use ($today, $tomorrow) {
                $q->where(function ($sq) use ($today) {
                    $sq->where('departure_date', $today)
                       ->where('departure_time', '>', now()->format('H:i:s'));
                })->orWhere('departure_date', $tomorrow);
            })
            ->orderBy('departure_date')
            ->orderBy('departure_time')
            ->limit(8);

        // For agents — filter to authorized ferries only
        if (in_array($user->domain, ['agent', 'associate_agent'])) {
            $agent = $this->resolveAgent($user);
            if ($agent) {
                $authorizedFerryIds = $agent->ferries()->pluck('ferries.id')->toArray();
                if (!empty($authorizedFerryIds)) {
                    $query->whereIn('ferry_id', $authorizedFerryIds);
                }
            }
        }

        $voyages = $query->get();

        return $voyages->map(function (Voyage $voyage) {
            $capacity = $voyage->ferry?->capacity ?? 0;

            // Get per-class breakdown from seat_inventories
            $classBreakdown = SeatInventory::where('voyage_id', $voyage->id)
                ->with('travelClass')
                ->get()
                ->map(function ($inv) {
                    $total     = (int) $inv->total_seats;
                    $sold      = (int) $inv->sold_seats;
                    $held      = (int) $inv->held_seats;
                    $blocked   = (int) $inv->blocked_seats;
                    $available = max(0, $total - $sold - $held - $blocked);

                    return [
                        'class'     => $inv->travelClass?->name ?? 'General',
                        'class_id'  => $inv->travel_class_id,
                        'total'     => $total,
                        'sold'      => $sold,
                        'held'      => $held,
                        'blocked'   => $blocked,
                        'available' => $available,
                    ];
                })->toArray();

            // Calculate totals from inventory if available, else fall back to ticket count
            if (!empty($classBreakdown)) {
                $totalSold      = array_sum(array_column($classBreakdown, 'sold'));
                $totalHeld      = array_sum(array_column($classBreakdown, 'held'));
                $totalAvailable = array_sum(array_column($classBreakdown, 'available'));
                $totalCapacity  = array_sum(array_column($classBreakdown, 'total'));
            } else {
                $totalSold      = $voyage->bookingItems()->whereNotIn('status', ['cancelled'])->count();
                $totalHeld      = 0;
                $totalAvailable = max(0, $capacity - $totalSold);
                $totalCapacity  = $capacity;
            }

            $loadPercent = $totalCapacity > 0 ? round(($totalSold / $totalCapacity) * 100) : 0;

            $departureDateTime = Carbon::parse($voyage->departure_date . ' ' . $voyage->departure_time);
            $countdownMinutes  = max(0, (int) now()->diffInMinutes($departureDateTime, false));

            return [
                'voyage_id'        => $voyage->id,
                'vessel'           => $voyage->ferry?->name ?? 'Unknown',
                'route'            => $voyage->route?->name ?? 'Unknown',
                'route_id'         => $voyage->route_id,
                'departure_date'   => $voyage->departure_date,
                'departure_time'   => $departureDateTime->format('H:i'),
                'countdown_minutes'=> $countdownMinutes,
                'status'           => $voyage->status,
                'total_capacity'   => $totalCapacity,
                'sold'             => $totalSold,
                'held'             => $totalHeld,
                'available'        => $totalAvailable,
                'load_percent'     => $loadPercent,
                'class_breakdown'  => $classBreakdown,
            ];
        })->toArray();
    }

    // ═══════════════════════════════════════════════════════════════
    //  SESSION FEED (Today's Activity Log)
    // ═══════════════════════════════════════════════════════════════

    public function getSessionFeed(User $user): array
    {
        $today = now()->toDateString();

        $issuedTickets = Ticket::whereDate('tickets.created_at', $today)
            ->whereHas('booking', fn($q) => $q->where('booked_by_user_id', $user->id))
            ->with(['passenger', 'ferryClass', 'voyage.route'])
            ->latest('tickets.created_at')
            ->limit(20)
            ->get()
            ->map(function (Ticket $ticket) {
                return [
                    'type'          => $ticket->status === 'cancelled' ? 'ticket_cancelled' : 'ticket_issued',
                    'time'          => $ticket->created_at?->format('H:i'),
                    'ticket_number' => $ticket->ticket_number,
                    'passenger'     => strtoupper(trim(($ticket->passenger?->first_name ?? '') . ' ' . ($ticket->passenger?->last_name ?? ''))),
                    'class'         => $ticket->ferryClass?->name ?? 'N/A',
                    'fare'          => (float) $ticket->final_fare,
                    'status'        => $ticket->status,
                    'route'         => $ticket->voyage?->route?->name ?? '',
                    'can_reprint'   => $ticket->status !== 'cancelled',
                    'booking_id'    => $ticket->booking_id,
                    'ticket_id'     => $ticket->id,
                ];
            })->toArray();

        return $issuedTickets;
    }

    // ═══════════════════════════════════════════════════════════════
    //  WALLET HEALTH (Agents & Associates)
    // ═══════════════════════════════════════════════════════════════

    public function getWalletHealth(User $user): ?array
    {
        $agent = $this->resolveAgent($user);
        if (!$agent) return null;

        $account = $agent->account;
        if (!$account) return null;

        $balance       = (float) $account->balance;
        $creditLimit   = (float) $account->credit_limit;
        $availableBalance = $account->getAvailableBalance();

        // Calculate daily burn rate from last 7 days of ticket sales
        $last7DaysSpend = (float) Ticket::whereDate('tickets.created_at', '>=', now()->subDays(7)->toDateString())
            ->whereNotIn('tickets.status', ['cancelled'])
            ->whereHas('booking', function ($q) use ($agent) {
                $q->whereHas('bookedBy', fn($sub) =>
                    $sub->where('id', $agent->user_id)
                        ->orWhere(function($sq) use ($agent) {
                            // Include associates
                            $assocIds = AssociateAgent::where('agent_id', $agent->id)->pluck('user_id');
                            $sq->whereIn('id', $assocIds);
                        })
                );
            })
            ->sum('final_fare');

        $dailyBurnRate = $last7DaysSpend > 0 ? round($last7DaysSpend / 7) : 0;
        $runwayDays    = $dailyBurnRate > 0 ? round($availableBalance / $dailyBurnRate, 1) : 999;

        // Determine health status
        $health = 'healthy';
        if ($runwayDays <= 1)      $health = 'critical';
        elseif ($runwayDays <= 3)  $health = 'low';
        elseif ($runwayDays <= 7)  $health = 'moderate';

        // Check for pending wallet top-up requests (search by agent's user)
        $agentUserId = $agent->user_id;
        $pendingTopup = DualControlRequest::where('maker_id', $agentUserId)
            ->where('resource_type', 'agent_account')
            ->where('status', 'pending')
            ->latest()
            ->first();

        $recentApproval = DualControlRequest::where('maker_id', $agentUserId)
            ->where('resource_type', 'agent_account')
            ->where('status', 'approved')
            ->where('resolved_at', '>=', now()->subHours(48))
            ->latest('resolved_at')
            ->first();

        $pendingTopupData = null;
        if ($pendingTopup) {
            $payload = is_array($pendingTopup->payload_after) ? $pendingTopup->payload_after : [];
            $pendingTopupData = [
                'amount'     => $payload['amount'] ?? 0,
                'status'     => 'pending',
                'created_at' => $pendingTopup->created_at?->format('Y-m-d H:i'),
            ];
        } elseif ($recentApproval) {
            $payload = is_array($recentApproval->payload_after) ? $recentApproval->payload_after : [];
            $pendingTopupData = [
                'amount'      => $payload['amount'] ?? 0,
                'status'      => 'approved',
                'approved_at' => $recentApproval->resolved_at?->format('Y-m-d H:i'),
            ];
        }

        $securityDepositRate = (float) SystemConfig::getValue('security_deposit_default_rate', 40.0);

        return [
            'balance'           => $balance,
            'credit_limit'      => $creditLimit,
            'available_balance'  => round($availableBalance, 2),
            'security_deposit'   => (float) ($account->security_deposit_balance ?? 0),
            'security_deposit_rate' => $securityDepositRate,
            'daily_burn_rate'    => $dailyBurnRate,
            'runway_days'        => min($runwayDays, 999),
            'health'             => $health,
            'pending_topup'      => $pendingTopupData,
            'commission_rate'    => $agent->getEffectiveCommissionRate(),
            'commission_method'  => $agent->getEffectiveCommissionMethod(),
            'total_earned_commission' => $agent->getTotalEarnedCommission(),
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    //  AGENCY KPIs (Agents Only)
    // ═══════════════════════════════════════════════════════════════

    public function getAgencyKpis(User $user): ?array
    {
        $agent = Agent::where('user_id', $user->id)->first();
        if (!$agent) return null;

        $today     = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        // Build the "agency scope" query callback — agent + all associates
        $assocUserIds = AssociateAgent::where('agent_id', $agent->id)->pluck('user_id')->toArray();
        $allAgencyUserIds = array_merge([$user->id], $assocUserIds);

        $agencyScope = function ($q) use ($allAgencyUserIds) {
            $q->whereIn('booked_by_user_id', $allAgencyUserIds);
        };

        // Today's revenue
        $todayRevenue = (float) Ticket::whereDate('created_at', $today)
            ->whereNotIn('status', ['cancelled'])
            ->whereHas('booking', $agencyScope)
            ->sum('final_fare');

        // Yesterday's revenue
        $yesterdayRevenue = (float) Ticket::whereDate('created_at', $yesterday)
            ->whereNotIn('status', ['cancelled'])
            ->whereHas('booking', $agencyScope)
            ->sum('final_fare');

        $changePercent = $yesterdayRevenue > 0
            ? round((($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100, 1)
            : ($todayRevenue > 0 ? 100 : 0);

        // This month revenue
        $monthRevenue = (float) Ticket::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->whereNotIn('status', ['cancelled'])
            ->whereHas('booking', $agencyScope)
            ->sum('final_fare');

        // Commission due this month
        $commissionRate = $agent->getEffectiveCommissionRate();
        $commissionDue  = round($monthRevenue * ($commissionRate / 100));

        // Cancellation rate (this month)
        $monthTotal = Ticket::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->whereHas('booking', $agencyScope)
            ->count();
        $monthCancelled = Ticket::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->where('status', 'cancelled')
            ->whereHas('booking', $agencyScope)
            ->count();
        $cancellationRate = $monthTotal > 0 ? round(($monthCancelled / $monthTotal) * 100, 1) : 0;

        // Pending refunds
        $pendingRefunds = 0;
        try {
            $pendingRefunds = (float) PendingRefund::whereHas('bookingItem', function ($q) use ($allAgencyUserIds) {
                $q->whereHas('booking', function ($bq) use ($allAgencyUserIds) {
                    $bq->whereIn('booked_by_user_id', $allAgencyUserIds);
                });
            })->where('status', 'pending')->sum('amount');
        } catch (\Exception $e) {
            // PendingRefund might have different relationship structure
        }

        // Today's tickets count
        $todayTickets = Ticket::whereDate('created_at', $today)
            ->whereNotIn('status', ['cancelled'])
            ->whereHas('booking', $agencyScope)
            ->count();

        return [
            'today_revenue'     => $todayRevenue,
            'today_tickets'     => $todayTickets,
            'yesterday_revenue' => $yesterdayRevenue,
            'change_percent'    => $changePercent,
            'month_revenue'     => $monthRevenue,
            'commission_due'    => $commissionDue,
            'cancellation_rate' => $cancellationRate,
            'pending_refunds'   => $pendingRefunds,
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    //  ASSOCIATE LEADERBOARD (Primary Agents Only)
    // ═══════════════════════════════════════════════════════════════

    public function getAssociateLeaderboard(User $user): ?array
    {
        $agent = Agent::where('user_id', $user->id)->first();
        if (!$agent) return null;

        $today = now()->toDateString();

        // Get associate users via the AssociateAgent pivot
        $associateUserIds = AssociateAgent::where('agent_id', $agent->id)
            ->where('is_active', true)
            ->pluck('user_id')
            ->toArray();

        $associates = User::whereIn('id', $associateUserIds)
            ->get()
            ->map(function (User $assoc) use ($today) {
                $revenue = (float) Ticket::whereDate('created_at', $today)
                    ->whereNotIn('status', ['cancelled'])
                    ->whereHas('booking', fn($q) => $q->where('booked_by_user_id', $assoc->id))
                    ->sum('final_fare');

                $tickets = Ticket::whereDate('created_at', $today)
                    ->whereNotIn('status', ['cancelled'])
                    ->whereHas('booking', fn($q) => $q->where('booked_by_user_id', $assoc->id))
                    ->count();

                // Check consecutive zero-sale days (max 7 lookback)
                $zeroSaleDays = 0;
                for ($i = 1; $i <= 7; $i++) {
                    $date = now()->subDays($i)->toDateString();
                    $hasSales = Ticket::whereDate('created_at', $date)
                        ->whereNotIn('status', ['cancelled'])
                        ->whereHas('booking', fn($q) => $q->where('booked_by_user_id', $assoc->id))
                        ->exists();
                    if (!$hasSales) {
                        $zeroSaleDays++;
                    } else {
                        break;
                    }
                }

                return [
                    'id'             => $assoc->id,
                    'name'           => $assoc->full_name,
                    'revenue'        => $revenue,
                    'tickets'        => $tickets,
                    'zero_sale_days' => $zeroSaleDays,
                    'alert'          => $zeroSaleDays >= 3 ? 'no_sales_' . $zeroSaleDays . '_days' : null,
                ];
            })
            ->sortByDesc('revenue')
            ->values()
            ->toArray();

        // Add rank
        foreach ($associates as $index => &$assoc) {
            $assoc['rank'] = $index + 1;
        }

        return $associates;
    }

    // ═══════════════════════════════════════════════════════════════
    //  ASSOCIATE'S OWN PERFORMANCE STRIP
    // ═══════════════════════════════════════════════════════════════

    public function getAssociatePerformance(User $user): array
    {
        $today = now()->toDateString();
        $weekStart = now()->startOfWeek()->toDateString();

        $todayRevenue = (float) Ticket::whereDate('created_at', $today)
            ->whereNotIn('status', ['cancelled'])
            ->whereHas('booking', fn($q) => $q->where('booked_by_user_id', $user->id))
            ->sum('final_fare');

        $todayTickets = Ticket::whereDate('created_at', $today)
            ->whereNotIn('status', ['cancelled'])
            ->whereHas('booking', fn($q) => $q->where('booked_by_user_id', $user->id))
            ->count();

        $weekRevenue = (float) Ticket::whereDate('created_at', '>=', $weekStart)
            ->whereNotIn('status', ['cancelled'])
            ->whereHas('booking', fn($q) => $q->where('booked_by_user_id', $user->id))
            ->sum('final_fare');

        $weekTickets = Ticket::whereDate('created_at', '>=', $weekStart)
            ->whereNotIn('status', ['cancelled'])
            ->whereHas('booking', fn($q) => $q->where('booked_by_user_id', $user->id))
            ->count();

        // Estimate commission split
        $agent = $this->resolveAgent($user);
        $commissionRate = $agent ? $agent->getEffectiveCommissionRate() : 5.0;
        $estimatedCommission = round($weekRevenue * ($commissionRate / 100));

        return [
            'today_revenue'         => $todayRevenue,
            'today_tickets'         => $todayTickets,
            'week_revenue'          => $weekRevenue,
            'week_tickets'          => $weekTickets,
            'estimated_commission'  => $estimatedCommission,
            'commission_rate'       => $commissionRate,
        ];
    }
}
