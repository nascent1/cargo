<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\ShiftOvertimeRequest;
use App\Models\SystemConfig;
use App\Models\User;
use Carbon\Carbon;

class ShiftService
{
    public function isShiftManagementEnabled(): bool
    {
        return (bool) SystemConfig::getValue('shift_management_enabled', true);
    }

    public function getDefaultShiftHours(): float
    {
        return (float) SystemConfig::getValue('default_shift_hours', 8.0);
    }

    public function getActiveShift(User $user): ?Shift
    {
        return Shift::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();
    }

    public function startShift(User $user): Shift
    {
        // First check if there's already an active shift
        $active = $this->getActiveShift($user);
        if ($active) {
            return $active;
        }

        $hours = $this->getDefaultShiftHours();
        $startTime = now();
        $endTime = $startTime->copy()->addMinutes((int)($hours * 60));

        return Shift::create([
            'user_id' => $user->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'active',
        ]);
    }

    public function endShift(Shift $shift): void
    {
        $shift->update([
            'status' => 'ended',
            'actual_end_time' => now(),
        ]);
    }

    public function requestOvertime(User $user, float $hours, ?string $reason = null): ShiftOvertimeRequest
    {
        return ShiftOvertimeRequest::create([
            'user_id' => $user->id,
            'requested_hours' => $hours,
            'reason' => $reason,
            'status' => 'pending',
        ]);
    }
}
