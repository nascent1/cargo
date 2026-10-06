<?php

namespace App\Services;

use App\Models\SystemTransaction;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use App\Services\Traits\ReportFilterTrait;

class AgentReportService
{
    use ReportFilterTrait;

    /**
     * Float / Agent Transaction Report.
     */
    public function floatReport(array $filters): array
    {
        $query = SystemTransaction::with(['category', 'status', 'user'])
            ->whereHas('category', fn($q) => $q->whereIn('slug', [
                'float_topup','security_deposit','ticket_sale','commission',
                'cancellation_penalty','refund','adjustment_credit','adjustment_debit',
            ]));

        $this->applyDateFilter($query, $filters);
        $this->applyRoleBasedRestrictions($query);
        
        if (!empty($filters['agent_user_id'])) $query->where('user_id', $filters['agent_user_id']);

        return $query->orderByDesc('created_at')->limit(500)->get()->toArray();
    }

    /**
     * Agent Performance & Commission Report
     */
    public function agentPerformanceReport(array $filters): array
    {
        $query = Ticket::query()
            ->whereNotIn('tickets.status', ['cancelled'])
            ->join('bookings', 'tickets.booking_id', '=', 'bookings.id')
            ->join('users', 'bookings.booked_by_user_id', '=', 'users.id')
            ->leftJoin('agents', 'agents.user_id', '=', 'users.id')
            ->leftJoin('associate_agents', 'associate_agents.user_id', '=', 'users.id')
            ->leftJoin('agents as parent_agents', 'parent_agents.id', '=', 'associate_agents.agent_id');

        $this->applyDateFilter($query, $filters, 'tickets.created_at');
        
        if (!empty($filters['agent_user_id'])) {
            $query->where('users.id', $filters['agent_user_id']);
        }

        return $query->select(
            DB::raw("CONCAT(users.first_name, ' ', users.last_name) as agent_name"),
            'users.email',
            DB::raw('COUNT(tickets.id) as total_tickets'),
            DB::raw("SUM(CASE WHEN tickets.currency = 'TZS' THEN tickets.final_fare ELSE 0 END) as total_sales_tzs"),
            DB::raw("SUM(CASE WHEN tickets.currency = 'USD' THEN tickets.final_fare ELSE 0 END) as total_sales_usd"),
            DB::raw("SUM(CASE WHEN tickets.currency = 'TZS' THEN tickets.final_fare * COALESCE(agents.commission_rate, parent_agents.commission_rate, 5) / 100 ELSE 0 END) as estimated_commission_tzs"),
            DB::raw("SUM(CASE WHEN tickets.currency = 'USD' THEN tickets.final_fare * COALESCE(agents.commission_rate, parent_agents.commission_rate, 5) / 100 ELSE 0 END) as estimated_commission_usd")
        )
        ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.email')
        ->orderByDesc('total_sales_tzs')
        ->get()
        ->toArray();
    }

    /**
     * Commission Reconciliation: Earned vs paid commissions per agent with discrepancy detection.
     */
    public function commissionReconciliation(array $filters): array
    {
        $agentQuery = \App\Models\Agent::where('is_active', true)->with(['user', 'account']);

        if (!empty($filters['agent_user_id'])) {
            $agentQuery->where('user_id', $filters['agent_user_id']);
        }

        $agents = $agentQuery->get();
        $result = [];

        $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $filters['date_to'] ?? now()->toDateString();

        foreach ($agents as $agent) {
            $userIds = [$agent->user_id];
            // Include associate IDs
            $assocIds = \App\Models\AssociateAgent::where('agent_id', $agent->id)->pluck('user_id')->toArray();
            $userIds = array_merge($userIds, $assocIds);

            // Tickets sold by this agent/associates in the period
            $salesData = Ticket::whereNotIn('tickets.status', ['cancelled'])
                ->join('bookings', 'tickets.booking_id', '=', 'bookings.id')
                ->whereIn('bookings.booked_by_user_id', $userIds)
                ->whereDate('tickets.created_at', '>=', $dateFrom)
                ->whereDate('tickets.created_at', '<=', $dateTo)
                ->selectRaw("
                    COUNT(*) as total_tickets,
                    SUM(CASE WHEN tickets.currency = 'TZS' THEN tickets.final_fare ELSE 0 END) as total_sales_tzs,
                    SUM(CASE WHEN tickets.currency = 'USD' THEN tickets.final_fare ELSE 0 END) as total_sales_usd
                ")
                ->first();

            $commissionRate = $agent->getEffectiveCommissionRate();
            $expectedCommTzs = ((float) ($salesData->total_sales_tzs ?? 0)) * ($commissionRate / 100);
            $expectedCommUsd = ((float) ($salesData->total_sales_usd ?? 0)) * ($commissionRate / 100);

            // Actual commissions paid (from transactions)
            $commCatId = \App\Models\TransactionCategory::where('slug', 'commission')->value('id');
            $paidComm = 0;
            if ($commCatId) {
                $paidComm = (float) \App\Models\SystemTransaction::where('user_id', $agent->user_id)
                    ->where('transaction_category_id', $commCatId)
                    ->whereDate('created_at', '>=', $dateFrom)
                    ->whereDate('created_at', '<=', $dateTo)
                    ->sum('amount_tzs');
            }

            $discrepancy = round($expectedCommTzs - $paidComm, 2);
            $status = abs($discrepancy) < 1 ? 'Balanced' : ($discrepancy > 0 ? 'Underpaid' : 'Overpaid');

            $result[] = [
                'agent_name' => $agent->agency_name ?: ($agent->user?->full_name ?? 'Unknown'),
                'agent_code' => $agent->agent_code,
                'commission_rate' => $commissionRate,
                'total_tickets' => (int) ($salesData->total_tickets ?? 0),
                'total_sales_tzs' => (float) ($salesData->total_sales_tzs ?? 0),
                'total_sales_usd' => (float) ($salesData->total_sales_usd ?? 0),
                'expected_commission_tzs' => round($expectedCommTzs, 2),
                'expected_commission_usd' => round($expectedCommUsd, 2),
                'paid_commission_tzs' => $paidComm,
                'discrepancy_tzs' => $discrepancy,
                'status' => $status,
                'float_balance' => (float) ($agent->account?->balance ?? 0),
            ];
        }

        usort($result, fn($a, $b) => abs($b['discrepancy_tzs']) <=> abs($a['discrepancy_tzs']));

        return $result;
    }
}
