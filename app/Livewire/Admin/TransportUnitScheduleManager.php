<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\SubmitsDualControlChanges;
use Livewire\Component;
use App\Models\TransportUnit;
use App\Models\Schedule;
use App\Models\Trip;
use App\Services\DualControlService;

class TransportUnitScheduleManager extends Component
{
    use SubmitsDualControlChanges;

    public TransportUnit $transportUnit;

    public $schedule_id;
    public $trip_id = '';
    public $start_date;
    public $end_date;
    public $frequency_pattern = [];
    public $isModalOpen = false;
    public $activeTab = 'current';
    public $status_filter = 'active';



    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function openModal()
    {
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function mount(TransportUnit $transportUnit)
    {
        $this->transportUnit = $transportUnit;
    }

    public function editSchedule($id)
    {
        $schedule = Schedule::findOrFail($id);
        $this->schedule_id = $schedule->id;
        $this->trip_id = $schedule->trip_id;
        $this->start_date = \Carbon\Carbon::parse($schedule->start_date)->format('Y-m-d');
        $this->end_date = \Carbon\Carbon::parse($schedule->end_date)->format('Y-m-d');
        $this->frequency_pattern = $schedule->frequency_pattern ? array_map('trim', explode(',', $schedule->frequency_pattern)) : [];
        $this->isModalOpen = true;
    }

    public function saveSchedule(DualControlService $dcService)
    {
        $this->validate([
            'trip_id' => 'required|exists:trips,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'frequency_pattern' => 'nullable|array',
            'frequency_pattern.*' => 'string|in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
        ]);

        $pattern = !empty($this->frequency_pattern) ? implode(', ', $this->frequency_pattern) : null;

        // Check for existing ACTIVE schedule to prevent double entry
        if (!$this->schedule_id) {
            $existingSchedule = Schedule::where('ferry_id', $this->transportUnit->id)
                ->where('trip_id', $this->trip_id)
                ->where('status', 'active')
                ->first();
                
            if ($existingSchedule) {
                $this->schedule_id = $existingSchedule->id;
                
                // Expand dates to cover both the existing and new requested dates
                $existingStart = \Carbon\Carbon::parse($existingSchedule->start_date);
                $newStart = \Carbon\Carbon::parse($this->start_date);
                $this->start_date = $newStart->isBefore($existingStart) ? $newStart->format('Y-m-d') : $existingStart->format('Y-m-d');
                
                $existingEnd = \Carbon\Carbon::parse($existingSchedule->end_date);
                $newEnd = \Carbon\Carbon::parse($this->end_date);
                $this->end_date = $newEnd->isAfter($existingEnd) ? $newEnd->format('Y-m-d') : $existingEnd->format('Y-m-d');
            }
        }

        $payload = [
            'ferry_id' => $this->transportUnit->id,
            'trip_id' => $this->trip_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'frequency_pattern' => $pattern,
        ];

        if ($this->schedule_id) {
            $schedule = Schedule::findOrFail($this->schedule_id);
            $payload['status'] = $schedule->status; // preserve existing status

            $ok = $this->submitDualControl('schedule_update', 'schedule', $schedule->id, [
                'before' => $schedule->toArray(),
                'after' => array_merge($schedule->toArray(), $payload),
            ], 'Schedule updated.');
        } else {
            $payload['status'] = 'active'; // default status for new schedules

            $ok = $this->submitDualControl('schedule_create', 'schedule', 0, ['after' => $payload], 'Schedule added.');
        }
        if (!$ok) {
            return; // refused (another change is open): keep the form so nothing typed is lost
        }

        $this->resetForm();
        $this->isModalOpen = false;
    }

    public function suspendSchedule(DualControlService $dcService, $id)
    {
        $schedule = Schedule::findOrFail($id);
        $payload = $schedule->toArray();
        $payload['status'] = 'inactive';

        $this->submitDualControl('schedule_update', 'schedule', $schedule->id, [
            'before' => $schedule->toArray(),
            'after' => $payload,
        ], 'Schedule suspended.', false);
    }
    
    public function activateSchedule(DualControlService $dcService, $id)
    {
        $schedule = Schedule::findOrFail($id);
        $payload = $schedule->toArray();
        $payload['status'] = 'active';

        $this->submitDualControl('schedule_update', 'schedule', $schedule->id, [
            'before' => $schedule->toArray(),
            'after' => $payload,
        ], 'Schedule activated.', false);
    }

    public function resetForm()
    {
        $this->reset(['schedule_id', 'trip_id', 'start_date', 'end_date', 'frequency_pattern']);
        $this->resetValidation();
    }

    public function render()
    {
        $allSchedules = Schedule::with(['trip.route', 'latestAction.maker', 'latestAction.checker'])
            ->where('ferry_id', $this->transportUnit->id)
            ->orderBy('start_date', 'desc')
            ->get();

        $currentSchedules = $allSchedules->filter(function ($schedule) {
            $isNotExpired = \Carbon\Carbon::parse($schedule->end_date)->isAfter(now()->subDay());
            
            if ($this->status_filter === 'active') {
                return $schedule->status === 'active' && $isNotExpired;
            } elseif ($this->status_filter === 'inactive') {
                return $schedule->status === 'inactive' && $isNotExpired;
            } else {
                return $isNotExpired;
            }
        });

        $scheduleIds = Schedule::withTrashed()->where('ferry_id', $this->transportUnit->id)->pluck('id')->toArray();
        $historyActions = \App\Models\DualControlRequest::with(['maker', 'checker'])
            ->where('resource_type', 'schedule')
            ->whereIn('resource_id', $scheduleIds)
            ->orderBy('created_at', 'desc')
            ->get();
            
        $historyTripIds = $historyActions->map(function($action) {
            return $action->payload_after['trip_id'] ?? $action->payload_before['trip_id'] ?? null;
        })->filter()->unique()->toArray();
        
        $historyTrips = Trip::with('route')->whereIn('id', $historyTripIds)->get()->keyBy('id');

        $activeTripIds = $allSchedules->where('status', 'active')->pluck('trip_id')->toArray();
        $availableTrips = Trip::with('route')
            ->whereNotIn('id', $activeTripIds)
            ->get();
            
        $trips = Trip::with('route')->get();

        return view('livewire.admin.transport-unit-schedule-manager', [
            'currentSchedules' => $currentSchedules,
            'historyActions' => $historyActions,
            'historyTrips' => $historyTrips,
            'availableTrips' => $availableTrips,
            'trips' => $trips,
        ]);
    }
}
