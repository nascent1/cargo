<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\SubmitsDualControlChanges;
use Livewire\Component;
use App\Models\Route;
use App\Models\Trip;
use App\Services\DualControlService;

class RouteFrequencyManager extends Component
{
    use SubmitsDualControlChanges;

    public Route $route;
    
    public $trip_id;
    public $departure_time;
    public $arrival_time;

    public function mount(Route $route)
    {
        $this->route = $route;
    }

    public function editTrip($id)
    {
        $trip = Trip::findOrFail($id);
        $this->trip_id = $trip->id;
        $this->departure_time = \Carbon\Carbon::parse($trip->departure_time)->format('H:i');
        $this->arrival_time = \Carbon\Carbon::parse($trip->arrival_time)->format('H:i');
        $this->dispatch('open-modal', 'manage-frequency-modal');
    }

    public function saveTrip(DualControlService $dcService)
    {
        $this->validate([
            'departure_time' => 'required|date_format:H:i',
            'arrival_time' => 'required|date_format:H:i',
        ]);

        $payload = [
            'route_id' => $this->route->id,
            'departure_time' => $this->departure_time,
            'arrival_time' => $this->arrival_time,
        ];

        if ($this->trip_id) {
            $trip = Trip::findOrFail($this->trip_id);
            $ok = $this->submitDualControl('trip_update', 'trip', $trip->id, [
                'before' => $trip->toArray(),
                'after' => array_merge($trip->toArray(), $payload),
            ], 'Trip updated.');
        } else {
            $ok = $this->submitDualControl('trip_create', 'trip', 0, ['after' => $payload], 'Trip added.');
        }
        if (!$ok) {
            return;
        }

        $this->reset(['trip_id', 'departure_time', 'arrival_time']);
        $this->dispatch('close-modal', 'manage-frequency-modal');
    }

    public function resetForm()
    {
        $this->reset(['trip_id', 'departure_time', 'arrival_time']);
    }

    public function deleteTrip(DualControlService $dcService, $tripId)
    {
        $trip = Trip::findOrFail($tripId);
        
        $this->submitDualControl('trip_delete', 'trip', $trip->id, [
            'before' => $trip->toArray(),
            'after' => [],
        ], 'Trip deleted.', false);
    }

    public function render()
    {
        return view('livewire.admin.route-frequency-manager', [
            'trips' => Trip::where('route_id', $this->route->id)->latest()->get()
        ]);
    }
}
