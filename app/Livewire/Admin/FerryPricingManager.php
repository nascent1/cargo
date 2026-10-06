<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Ferry;
use App\Models\FerryClass;
use App\Models\Route;
use App\Models\Fare;
use App\Services\DualControlService;

class FerryPricingManager extends Component
{
    public Ferry $ferry;
    public $routes;
    
    public $selectedRouteId = '';
    public $classes = [];
    public $prices = [];
    public $roomPrices = [];
    public $isModalOpen = false;

    public function openModal()
    {
        $this->isModalOpen = true;
    }

    public function mount(Ferry $ferry)
    {
        $this->ferry = $ferry;
        $this->routes = Route::where('status', 'active')->get();
        // Pre-load classes since they are fixed for this ferry
        $this->classes = FerryClass::with('rooms')->where('ferry_id', $this->ferry->id)->where('status', 'active')->get();
    }

    public function updatedSelectedRouteId($routeId)
    {
        if ($routeId) {
            $this->prices = [];
            $this->roomPrices = [];
            
            // Load existing prices for this route and ferry
            $existingFares = Fare::where('route_id', $routeId)
                ->where('ferry_id', $this->ferry->id)
                ->get();

            $classFares = $existingFares->whereNull('room_id')->keyBy('ferry_class_id');
            $roomFares = $existingFares->whereNotNull('room_id')->keyBy('room_id');

            foreach ($this->classes as $cls) {
                if ($cls->is_room_based) {
                    foreach ($cls->rooms as $room) {
                        $fare = $roomFares->get($room->id);
                        $this->roomPrices[$room->id] = [
                            'enabled' => $fare ? true : false,
                            'adult_price' => $fare ? $fare->adult_price : 0,
                            'child_price' => $fare ? $fare->child_price : 0,
                            'adult_price_usd' => $fare ? $fare->adult_price_usd : 0,
                            'child_price_usd' => $fare ? $fare->child_price_usd : 0,
                            'whole_room_price' => $fare ? $fare->whole_room_price : 0,
                            'whole_room_price_usd' => $fare ? $fare->whole_room_price_usd : 0,
                            'ferry_class_id' => $cls->id,
                        ];
                    }
                } else {
                    $fare = $classFares->get($cls->id);
                    $this->prices[$cls->id] = [
                        'enabled' => $fare ? true : false,
                        'adult_price' => $fare ? $fare->adult_price : 0,
                        'child_price' => $fare ? $fare->child_price : 0,
                        'adult_price_usd' => $fare ? $fare->adult_price_usd : 0,
                        'child_price_usd' => $fare ? $fare->child_price_usd : 0,
                    ];
                }
            }
        } else {
            $this->prices = [];
            $this->roomPrices = [];
        }
    }

    public function savePrices(DualControlService $dcService)
    {
        $this->validate([
            'selectedRouteId' => 'required|exists:routes,id',
            'prices.*.adult_price' => 'nullable|numeric|min:0',
            'prices.*.child_price' => 'nullable|numeric|min:0',
            'prices.*.adult_price_usd' => 'nullable|numeric|min:0',
            'prices.*.child_price_usd' => 'nullable|numeric|min:0',
            'roomPrices.*.adult_price' => 'nullable|numeric|min:0',
            'roomPrices.*.child_price' => 'nullable|numeric|min:0',
            'roomPrices.*.adult_price_usd' => 'nullable|numeric|min:0',
            'roomPrices.*.child_price_usd' => 'nullable|numeric|min:0',
            'roomPrices.*.whole_room_price' => 'nullable|numeric|min:0',
            'roomPrices.*.whole_room_price_usd' => 'nullable|numeric|min:0',
        ]);

        // Process standard classes
        foreach ($this->prices as $classId => $priceData) {
            $this->processFare($dcService, $classId, null, $priceData);
        }

        // Process room-based classes
        foreach ($this->roomPrices as $roomId => $priceData) {
            $this->processFare($dcService, $priceData['ferry_class_id'], $roomId, $priceData);
        }

        $this->isModalOpen = false;
        session()->flash('success', 'Ferry prices updates submitted.');
    }

    private function processFare(DualControlService $dcService, $classId, $roomId, $priceData)
    {
        $query = Fare::where('route_id', $this->selectedRouteId)
            ->where('ferry_id', $this->ferry->id)
            ->where('ferry_class_id', $classId);
            
        if ($roomId) {
            $query->where('room_id', $roomId);
        } else {
            $query->whereNull('room_id');
        }
        
        $existingFare = $query->first();

        if (!empty($priceData['enabled'])) {
            $payload = [
                'route_id' => $this->selectedRouteId,
                'ferry_id' => $this->ferry->id,
                'ferry_class_id' => $classId,
                'room_id' => $roomId,
                'adult_price' => $priceData['adult_price'] ?? 0,
                'child_price' => $priceData['child_price'] ?? 0,
                'adult_price_usd' => $priceData['adult_price_usd'] ?? 0,
                'child_price_usd' => $priceData['child_price_usd'] ?? 0,
                'whole_room_price' => $priceData['whole_room_price'] ?? null,
                'whole_room_price_usd' => $priceData['whole_room_price_usd'] ?? null,
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
        return view('livewire.admin.ferry-pricing-manager');
    }
}
