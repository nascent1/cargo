<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Ferry;
use App\Models\FerryClass;
use App\Models\TravelClass;
use App\Services\DualControlService;

class FerryClassManager extends Component
{
    public Ferry $ferry;
    
    public $class_id; // the ferry_class id
    public $travel_class_id; // the selected global travel class
    public $capacity;
    public $is_room_based = false;
    public $rooms = [];
    public $isModalOpen = false;

    public function openModal()
    {
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function mount(Ferry $ferry)
    {
        $this->ferry = $ferry;
    }

    public function addRoom()
    {
        $this->rooms[] = ['name' => '', 'capacity' => 1];
    }

    public function removeRoom($index)
    {
        unset($this->rooms[$index]);
        $this->rooms = array_values($this->rooms);
    }

    public function editClass($id)
    {
        $cls = FerryClass::with('rooms')->findOrFail($id);
        $this->class_id = $cls->id;
        $this->travel_class_id = $cls->class_id;
        $this->capacity = $cls->capacity;
        $this->is_room_based = $cls->is_room_based;
        $this->rooms = $cls->rooms->map(function ($room) {
            return ['name' => $room->name, 'capacity' => $room->capacity];
        })->toArray();
        $this->isModalOpen = true;
    }

    public function saveClass(DualControlService $dcService)
    {
        $this->validate([
            'travel_class_id' => 'required|exists:travel_classes,id',
            'capacity' => 'required|integer|min:1',
            'is_room_based' => 'boolean',
            'rooms' => 'array',
            'rooms.*.name' => 'required_if:is_room_based,true|string',
            'rooms.*.capacity' => 'required_if:is_room_based,true|integer|min:1',
        ], [
            'travel_class_id.required' => 'Please select a global Travel Class.'
        ]);

        $payload = [
            'ferry_id' => $this->ferry->id,
            'class_id' => $this->travel_class_id,
            'no_seats' => $this->capacity,
            'is_room_based' => $this->is_room_based,
            'rooms' => $this->is_room_based ? $this->rooms : [],
            'status' => 'active',
        ];

        if ($this->class_id) {
            $cls = FerryClass::findOrFail($this->class_id);
            $dcService->submit('class_update', 'ferry_class', $cls->id, [
                'before' => $cls->toArray(),
                'after' => array_merge($cls->toArray(), $payload),
            ]);
            session()->flash('success', 'Ferry class update submitted.');
        } else {
            $dcService->submit('class_create', 'ferry_class', 0, [
                'after' => $payload,
            ]);
            session()->flash('success', 'Ferry class creation submitted.');
        }

        $this->resetForm();
        $this->isModalOpen = false;
    }

    public function suspendClass(DualControlService $dcService, $id)
    {
        $cls = FerryClass::findOrFail($id);
        $payload = $cls->toArray();
        $payload['status'] = 'inactive';

        $dcService->submit('class_update', 'ferry_class', $cls->id, [
            'before' => $cls->toArray(),
            'after' => $payload,
        ]);
        session()->flash('success', 'Ferry class suspension submitted.');
    }

    public function resetForm()
    {
        $this->reset(['class_id', 'travel_class_id', 'capacity', 'is_room_based', 'rooms']);
    }

    public function render()
    {
        $globalClasses = TravelClass::where('status', 'active')->orderBy('name')->get();
        return view('livewire.admin.ferry-class-manager', [
            'classes' => $this->ferry->classes()->orderBy('sort_order')->get(),
            'globalClasses' => $globalClasses
        ]);
    }
}
