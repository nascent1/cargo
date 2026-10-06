<?php

namespace App\Services\Traits;

use App\Models\Ticket;
use App\Models\SystemTransaction;
use App\Models\BookingLog;

trait ReportFilterTrait
{
    /**
     * Apply universal filters to the report query.
     */
    public function applyUniversalFilters($query, array $filters): void
    {
        $isTicket = $query->getModel() instanceof Ticket;
        $tablePrefix = $isTicket ? 'tickets.' : '';

        $this->applyRoleBasedRestrictions($query);

        $dateTarget = $filters['date_target'] ?? ($tablePrefix . 'created_at');
        
        if ($tablePrefix && !str_contains($dateTarget, '.')) {
            if ($dateTarget === 'departure_date') {
                $dateTarget = 'voyages.departure_date';
                $joins = $query->getQuery()->joins ?? [];
                $hasVoyagesJoin = collect($joins)->contains(fn($join) => $join->table === 'voyages');
                
                if (!$hasVoyagesJoin) {
                    $query->join('voyages', 'tickets.voyage_id', '=', 'voyages.id');
                }
            } else {
                $dateTarget = $tablePrefix . $dateTarget;
            }
        }
        
        $this->applyDateFilter($query, $filters, $dateTarget);

        if (!empty($filters['booked_by_user_id'])) {
            if ($isTicket) {
                $query->whereHas('booking', fn($q) => $q->where('booked_by_user_id', $filters['booked_by_user_id']));
            } else {
                $query->where('booked_by_user_id', $filters['booked_by_user_id']); 
            }
        } elseif (!empty($filters['agent_id'])) {
            $agentId = $filters['agent_id'];
            $agentUserId = \App\Models\Agent::where('id', $agentId)->value('user_id');
            if ($isTicket) {
                $query->whereHas('booking', function($bq) use ($agentUserId, $agentId) {
                    $bq->where('booked_by_user_id', $agentUserId)
                       ->orWhereHas('bookedBy.associateAgent', fn($sq) => $sq->where('agent_id', $agentId));
                });
            } else {
                $query->where(function($bq) use ($agentUserId, $agentId) {
                    $bq->where('booked_by_user_id', $agentUserId)
                       ->orWhereHas('bookedBy.associateAgent', fn($sq) => $sq->where('agent_id', $agentId));
                });
            }
        }
        if (!empty($filters['booking_source'])) {
            if ($isTicket) {
                $query->whereHas('booking', fn($q) => $q->where('booking_source', $filters['booking_source']));
            } else {
                $query->where('booking_source', $filters['booking_source']);
            }
        }

        if (!empty($filters['route_id'])) {
            if ($isTicket) {
                $query->whereHas('voyage', fn($q) => $q->where('route_id', $filters['route_id']));
            } else {
                $query->where('route_id', $filters['route_id']);
            }
        }
        if (!empty($filters['ferry_id'])) {
            if ($isTicket) {
                $query->whereHas('voyage', fn($q) => $q->where('ferry_id', $filters['ferry_id']));
            } else {
                $query->where('ferry_id', $filters['ferry_id']);
            }
        }
        if (!empty($filters['ferry_class_id'])) {
            $query->where('ferry_class_id', $filters['ferry_class_id']);
        }
        if (!empty($filters['leg_type'])) {
            $query->where('leg_type', $filters['leg_type']);
        }

        if (!empty($filters['booking_status'])) {
            if ($isTicket) {
                $query->whereHas('booking', fn($q) => $q->where('status', $filters['booking_status']));
            } else {
                $query->where('status', $filters['booking_status']);
            }
        }
        if (!empty($filters['ticket_status'])) {
            $query->where('status', $filters['ticket_status']);
        }
        if (!empty($filters['payment_channel'])) {
            if ($isTicket) {
                $query->whereHas('booking', fn($q) => $q->where('payment_channel', $filters['payment_channel']));
            } else {
                $query->where('payment_channel', $filters['payment_channel']);
            }
        }

        if (!empty($filters['nationality_type'])) {
            $natVal = $filters['nationality_type'];
            if ($natVal === 'resident') $natVal = 1;
            if ($natVal === 'foreigner') $natVal = 2;
            $query->where($tablePrefix . 'nationality_type', $natVal);
        }
        if (!empty($filters['passenger_type'])) {
            if ($isTicket) {
                $query->whereHas('passengerType', fn($q) => $q->where('code', strtolower($filters['passenger_type'])));
            } else {
                $query->whereHas('items.passengerType', fn($q) => $q->where('code', strtolower($filters['passenger_type'])));
            }
        }
        if (!empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }
    }

    protected function applyDateFilter($query, array $filters, string $col = 'created_at'): void
    {
        if (!empty($filters['date_from'])) $query->whereDate($col, '>=', $filters['date_from']);
        if (!empty($filters['date_to']))   $query->whereDate($col, '<=', $filters['date_to']);
        if (empty($filters['date_from']) && empty($filters['date_to']) && empty($filters['ignore_default_date'])) {
            $query->whereDate($col, now()->toDateString());
        }
    }

    protected function applyRoleBasedRestrictions($query): void
    {
        if (auth()->check() && auth()->user()->hasPermission('report:view_all')) {
            return;
        }

        if (auth()->check() && auth()->user()->domain === 'system_user') {
            $userId = auth()->id();
            $isTicket = $query->getModel() instanceof Ticket;
            
            if ($isTicket) {
                $query->where(function($q) use ($userId) {
                    $q->where(function($sub1) use ($userId) {
                        $joins = $sub1->getQuery()->joins ?? [];
                        $hasBookingsJoin = collect($joins)->contains(fn($join) => $join->table === 'bookings');
    
                        if ($hasBookingsJoin) {
                            $sub1->where('bookings.booked_by_user_id', $userId);
                        } else {
                            $sub1->whereHas('booking', fn($bq) => $bq->where('booked_by_user_id', $userId));
                        }
                    })
                    ->orWhereExists(function ($sub2) use ($userId) {
                        $sub2->select(\DB::raw(1))
                            ->from('booking_logs')
                            ->where(function($sq) {
                                $sq->whereColumn('booking_logs.ticket_id', 'tickets.id');
                            })
                            ->where('booking_logs.performed_by_user_id', $userId);
                    });
                });
            } elseif ($query->getModel() instanceof SystemTransaction) {
                 $query->where($query->getModel()->getTable() . '.user_id', $userId);
            }
        }
    }
}
