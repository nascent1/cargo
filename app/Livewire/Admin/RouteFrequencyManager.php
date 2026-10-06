<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Route;
use App\Models\Trip;
use App\Services\DualControlService;

class RouteFrequencyManager extends Component
{
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
            $dcService->submit('trip_update', 'trip', $trip->id, [
                'before' => $trip->toArray(),
                'after' => array_merge($trip->toArray(), $payload),
            ]);
            session()->flash('success', 'Trip update submitted for Dual Control approval.');
        } else {
            $dcService->submit('trip_create', 'trip', 0, [
                'after' => $payload,
            ]);
            session()->flash('success', 'Route trip submitted for Dual Control approval.');
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
        
        $dcService->submit('trip_delete', 'trip', $trip->id, [
            'before' => $trip->toArray(),
            'after' => [],
        ]);

        session()->flash('success', 'Trip deletion submitted for Dual Control approval.');
    }

    public function render()
    {
        return view('livewire.admin.route-frequency-manager', [
            'trips' => Trip::where('route_id', $this->route->id)->latest()->get()
        ]);
    }
}
