<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Route;
use App\Models\TransportUnit;
use App\Models\CargoType;
use App\Models\CargoFare;
use App\Services\DualControlService;

class RoutePricingManager extends Component
{
    public Route $route;
    public $units;
    public $selectedUnitId = '';
    
    public $classes = [];
    public $prices = [];
    public $roomPrices = [];
    public $isModalOpen = false;

    public function openModal()
    {
        $this->isModalOpen = true;
    }

    public function mount(Route $route)
    {
        $this->route = $route;
        $this->units = TransportUnit::where('status', 'active')->get();
    }

    public function updatedSelectedUnitId($unitId)
    {
        $this->classes = collect();
        $this->prices = [];
        $this->roomPrices = [];

        if ($unitId === 'default') {
            // Load existing default price
            $existingFare = CargoFare::where('route_id', $this->route->id)
                ->whereNull('transport_unit_id')
                ->whereNull('cargo_type_id')
                ->first();
                
            $this->prices['default'] = [
                'enabled' => $existingFare ? true : false,
                'price_tzs' => $existingFare ? $existingFare->price_tzs : 0,
                'price_per_kg_tzs' => $existingFare ? $existingFare->price_per_kg_tzs : 0,
                'price_tzs_usd' => $existingFare ? $existingFare->price_tzs_usd : 0,
                'price_per_kg_tzs_usd' => $existingFare ? $existingFare->price_per_kg_tzs_usd : 0,
            ];
        } elseif ($unitId) {
            $this->classes = CargoType::with('rooms')->where('transport_unit_id', $unitId)->where('status', 'active')->get();
            
            // Load existing prices for this route and ferry
            $existingFares = CargoFare::where('route_id', $this->route->id)
                ->where('transport_unit_id', $unitId)
                ->get();

            $classFares = $existingFares->whereNull('room_id')->keyBy('cargo_type_id');
            $roomFares = $existingFares->whereNotNull('room_id')->keyBy('room_id');

            foreach ($this->classes as $cls) {
                if ($cls->is_room_based) {
                    foreach ($cls->rooms as $room) {
                        $fare = $roomFares->get($room->id);
                        $this->roomPrices[$room->id] = [
                            'enabled' => $fare ? true : false,
                            'price_tzs' => $fare ? $fare->price_tzs : 0,
                            'price_per_kg_tzs' => $fare ? $fare->price_per_kg_tzs : 0,
                            'price_tzs_usd' => $fare ? $fare->price_tzs_usd : 0,
                            'price_per_kg_tzs_usd' => $fare ? $fare->price_per_kg_tzs_usd : 0,
                            'min_charge_tzs' => $fare ? $fare->min_charge_tzs : 0,
                            'min_charge_tzs_usd' => $fare ? $fare->min_charge_tzs_usd : 0,
                            'cargo_type_id' => $cls->id,
                        ];
                    }
                } else {
                    $fare = $classFares->get($cls->id);
                    $this->prices[$cls->id] = [
                        'enabled' => $fare ? true : false,
                        'price_tzs' => $fare ? $fare->price_tzs : 0,
                        'price_per_kg_tzs' => $fare ? $fare->price_per_kg_tzs : 0,
                        'price_tzs_usd' => $fare ? $fare->price_tzs_usd : 0,
                        'price_per_kg_tzs_usd' => $fare ? $fare->price_per_kg_tzs_usd : 0,
                    ];
                }
            }
        }
    }

    public function savePrices(DualControlService $dcService)
    {
        $this->validate([
            'selectedUnitId' => 'required',
            'prices.*.price_tzs' => 'nullable|numeric|min:0',
            'prices.*.price_per_kg_tzs' => 'nullable|numeric|min:0',
            'prices.*.price_tzs_usd' => 'nullable|numeric|min:0',
            'prices.*.price_per_kg_tzs_usd' => 'nullable|numeric|min:0',
            'roomPrices.*.price_tzs' => 'nullable|numeric|min:0',
            'roomPrices.*.price_per_kg_tzs' => 'nullable|numeric|min:0',
            'roomPrices.*.price_tzs_usd' => 'nullable|numeric|min:0',
            'roomPrices.*.price_per_kg_tzs_usd' => 'nullable|numeric|min:0',
            'roomPrices.*.min_charge_tzs' => 'nullable|numeric|min:0',
            'roomPrices.*.min_charge_tzs_usd' => 'nullable|numeric|min:0',
        ]);

        if ($this->selectedUnitId !== 'default') {
            $this->validate(['selectedUnitId' => 'exists:transport_units,id']);
        }

        // Process standard classes (or default pricing)
        foreach ($this->prices as $classId => $priceData) {
            $fId = $this->selectedUnitId === 'default' ? null : $this->selectedUnitId;
            $cId = $this->selectedUnitId === 'default' ? null : $classId;
            $this->processFare($dcService, $fId, $cId, null, $priceData);
        }

        // Process room-based classes
        if ($this->selectedUnitId !== 'default') {
            foreach ($this->roomPrices as $roomId => $priceData) {
                $this->processFare($dcService, $this->selectedUnitId, $priceData['cargo_type_id'], $roomId, $priceData);
            }
        }

        $this->isModalOpen = false;
        session()->flash('success', 'Route prices updates submitted.');
    }

    private function processFare(DualControlService $dcService, $fId, $cId, $roomId, $priceData)
    {
        $query = CargoFare::where('route_id', $this->route->id);
        
        if ($fId) {
            $query->where('transport_unit_id', $fId)->where('cargo_type_id', $cId);
        } else {
            $query->whereNull('transport_unit_id')->whereNull('cargo_type_id');
        }

        if ($roomId) {
            $query->where('room_id', $roomId);
        } else {
            $query->whereNull('room_id');
        }
        
        $existingFare = $query->first();

        if (!empty($priceData['enabled'])) {
            $payload = [
                'route_id' => $this->route->id,
                'transport_unit_id' => $fId,
                'cargo_type_id' => $cId,
                'room_id' => $roomId,
                'price_tzs' => $priceData['price_tzs'] ?? 0,
                'price_per_kg_tzs' => $priceData['price_per_kg_tzs'] ?? 0,
                'price_tzs_usd' => $priceData['price_tzs_usd'] ?? 0,
                'price_per_kg_tzs_usd' => $priceData['price_per_kg_tzs_usd'] ?? 0,
                'min_charge_tzs' => $priceData['min_charge_tzs'] ?? null,
                'min_charge_tzs_usd' => $priceData['min_charge_tzs_usd'] ?? null,
                'status' => 'active',
            ];

            if ($existingFare) {
                $dcService->submit('fare_update', 'fare', $existingFare->id, [
                    'before' => $existingFare->toArray(),
                    'after' => $payload,
                ]);
            } else {
                $dcService->submit('fare_create', 'fare', 0, [
                    'after' => $payload,
                ]);
            }
        } else {
            if ($existingFare && $existingFare->status !== 'inactive') {
                $payload = $existingFare->toArray();
                $payload['status'] = 'inactive';
                $dcService->submit('fare_update', 'fare', $existingFare->id, [
                    'before' => $existingFare->toArray(),
                    'after' => $payload,
                ]);
            }
        }
    }

    public function render()
    {
        return view('livewire.admin.route-pricing-manager');
    }
}
