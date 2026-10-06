<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\DualControlService;
use Illuminate\Support\Facades\Log;

use App\Models\Ferry;

class FerryWizard extends Component
{
    use WithFileUploads;

    public ?Ferry $ferry = null;
    public bool $isEdit = false;
    public int $step = 1;

    // Step 1: Basic Info
    public string $name = '';
    public string $type = 'Passenger Ferry';
    public string $port_of_registry = '';
    public $capacity = ''; // Use loosely typed to prevent TypeError on empty string
    public bool $has_layout = false;
    
    // Step 2: Classes
    public array $classes = [
        ['name' => 'Economy', 'capacity' => 0, 'is_room_based' => false, 'rooms' => []]
    ];

    // Step 3: Multi-Deck Layout Upload
    public int $deck_count = 1;
    public array $decks_data = [
        ['name' => 'Deck 1', 'image' => null, 'image_path' => null]
    ];

    public function mount(?Ferry $ferry = null)
    {
        if ($ferry && $ferry->exists) {
            $this->ferry = $ferry;
            $this->isEdit = true;

            $this->name = $ferry->name;
            $this->type = $ferry->type;
            $this->port_of_registry = $ferry->port_of_registry ?? '';
            $this->capacity = $ferry->capacity;
            $this->has_layout = $ferry->has_layout;

            // Load classes and rooms
            $ferryClasses = $ferry->classes()->with('rooms')->get();
            if ($ferryClasses->isNotEmpty()) {
                $this->classes = $ferryClasses->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'name' => $c->name,
                        'capacity' => $c->capacity,
                        'is_room_based' => $c->is_room_based,
                        'rooms' => $c->rooms->map(function ($r) {
                            return [
                                'id' => $r->id,
                                'name' => $r->name,
                                'capacity' => $r->capacity
                            ];
                        })->toArray()
                    ];
                })->toArray();
            }

            // Load decks if version 2 layout exists
            if ($ferry->has_layout && isset($ferry->seat_layout_json['version']) && $ferry->seat_layout_json['version'] === 2) {
                $decks = $ferry->seat_layout_json['decks'] ?? [];
                if (!empty($decks)) {
                    $this->deck_count = count($decks);
                    $this->decks_data = array_map(function ($deck) {
                        return [
                            'name' => $deck['name'] ?? '',
                            'image' => null,
                            'image_path' => str_replace(asset('storage/'), '', $deck['backgroundImage'] ?? '')
                        ];
                    }, $decks);
                }
            } else if ($ferry->has_layout && $ferry->layout_image_path) {
                // Legacy support
                $this->deck_count = 1;
                $this->decks_data = [
                    ['name' => 'Deck 1', 'image' => null, 'image_path' => $ferry->layout_image_path]
                ];
            }
        }
    }

    public function updatedDeckCount()
    {
        $this->deck_count = max(1, min(5, (int) $this->deck_count));
        $current = count($this->decks_data);

        if ($this->deck_count > $current) {
            for ($i = $current; $i < $this->deck_count; $i++) {
                $this->decks_data[] = ['name' => 'Deck ' . ($i + 1), 'image' => null, 'image_path' => null];
            }
        } elseif ($this->deck_count < $current) {
            $this->decks_data = array_slice($this->decks_data, 0, $this->deck_count);
        }
    }

    public function addClass()
    {
        $this->classes[] = ['name' => '', 'capacity' => 0, 'is_room_based' => false, 'rooms' => []];
    }

    public function removeClass($index)
    {
        unset($this->classes[$index]);
        $this->classes = array_values($this->classes);
    }

    public function toggleRoomBased($index)
    {
        $this->classes[$index]['is_room_based'] = !$this->classes[$index]['is_room_based'];
        if ($this->classes[$index]['is_room_based'] && empty($this->classes[$index]['rooms'])) {
            $this->addRoom($index);
        }
        $this->recalculateClassCapacity($index);
    }

    public function addRoom($classIndex)
    {
        $this->classes[$classIndex]['rooms'][] = ['name' => '', 'capacity' => 1];
        $this->recalculateClassCapacity($classIndex);
    }

    public function removeRoom($classIndex, $roomIndex)
    {
        unset($this->classes[$classIndex]['rooms'][$roomIndex]);
        $this->classes[$classIndex]['rooms'] = array_values($this->classes[$classIndex]['rooms']);
        $this->recalculateClassCapacity($classIndex);
    }

    public function recalculateClassCapacity($classIndex)
    {
        if ($this->classes[$classIndex]['is_room_based']) {
            $total = 0;
            foreach ($this->classes[$classIndex]['rooms'] as $room) {
                $total += (int) $room['capacity'];
            }
            $this->classes[$classIndex]['capacity'] = $total;
        }
    }

    public function updatedClasses()
    {
        // Auto-recalculate capacities if room capacities change
        foreach ($this->classes as $index => $classData) {
            if ($classData['is_room_based']) {
                $this->recalculateClassCapacity($index);
            }
        }
    }

    public function nextStep()
    {
        $this->validateStep1();
        $this->step = 2;
    }

    public function previousStep()
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function goToStep3()
    {
        $this->validateStep2();
        
        if (!$this->has_layout) {
            // If no layout is needed, just submit
            $this->submit(app(DualControlService::class));
            return;
        }
        
        $this->step = 3;
    }

    private function validateStep1()
    {
        $this->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|string|max:50',
            'port_of_registry' => 'nullable|string|max:100',
            'capacity' => 'required|integer|min:1',
            'has_layout' => 'boolean',
        ]);
    }

    private function validateStep2()
    {
        $this->validate([
            'classes' => 'required|array|min:1',
            'classes.*.name' => 'required|string|max:50',
            'classes.*.capacity' => 'required|integer|min:1',
            'classes.*.is_room_based' => 'boolean',
            'classes.*.rooms' => 'array',
        ], [
            'classes.*.name.required' => 'Class name is required.',
            'classes.*.capacity.required' => 'Class capacity must be at least 1.',
        ]);

        foreach ($this->classes as $index => $classData) {
            if ($classData['is_room_based']) {
                if (empty($classData['rooms'])) {
                    $this->addError("classes.$index.rooms", "Room-based classes must have at least one room.");
                    throw \Illuminate\Validation\ValidationException::withMessages(["classes.$index.rooms" => 'No rooms defined']);
                }
                foreach ($classData['rooms'] as $roomIndex => $room) {
                    if (empty($room['name'])) {
                        $this->addError("classes.$index.rooms.$roomIndex.name", "Room name is required.");
                        throw \Illuminate\Validation\ValidationException::withMessages(["classes.$index.rooms.$roomIndex.name" => 'Room name is required']);
                    }
                    if ((int)$room['capacity'] < 1) {
                        $this->addError("classes.$index.rooms.$roomIndex.capacity", "Room capacity must be at least 1.");
                        throw \Illuminate\Validation\ValidationException::withMessages(["classes.$index.rooms.$roomIndex.capacity" => 'Invalid room capacity']);
                    }
                }
            }
        }

        $totalClassCapacity = collect($this->classes)->sum(function($c) {
            return (int) $c['capacity'];
        });

        if ($totalClassCapacity !== (int)$this->capacity) {
            $this->addError('capacity_mismatch', "Total class capacity ({$totalClassCapacity}) must equal the total vessel capacity ({$this->capacity}).");
            $this->step = 2; // ensure we stay on step 2 if error happens during goToStep3
            throw \Illuminate\Validation\ValidationException::withMessages(['capacity_mismatch' => 'Capacity mismatch']);
        }
    }

    public function submit(DualControlService $dcService)
    {
        $this->validateStep1();
        $this->validateStep2();
        
        if ($this->has_layout && $this->step === 3) {
            $rules = [
                'deck_count'          => 'required|integer|min:1|max:5',
                'decks_data.0.name'   => 'required|string|max:100',
            ];
            
            if (!$this->isEdit || empty($this->decks_data[0]['image_path'])) {
                $rules['decks_data.0.image'] = 'required|image|max:5120';
            } else {
                $rules['decks_data.0.image'] = 'nullable|image|max:5120';
            }

            // Validate deck 1 must have an image (unless editing and already has one)
            $this->validate($rules, [
                'decks_data.0.image.required' => 'Please upload a layout image for Deck 1.',
            ]);

            // Store each deck image that was provided
            foreach ($this->decks_data as $i => $deck) {
                if (!empty($deck['image'])) {
                    $this->decks_data[$i]['image_path'] = $this->decks_data[$i]['image']->store('seat_layouts', 'public');
                }
            }
        }

        // Build Payload
        $payload = [
            'name' => $this->name,
            'type' => $this->type,
            'port_of_registry' => $this->port_of_registry,
            'capacity' => $this->capacity,
            'has_layout' => $this->has_layout,
            'status' => 'active',
            'classes' => $this->classes,
        ];

        if ($this->has_layout) {
            // Build v2 multi-deck layout JSON
            $decksPayload = [];
            foreach ($this->decks_data as $i => $deck) {
                $deckNum = $i + 1;
                $decksPayload[] = [
                    'deck_number'     => $deckNum,
                    'name'            => $deck['name'] ?? "Deck {$deckNum}",
                    'width'           => 1200,
                    'height'          => 800,
                    'backgroundImage' => !empty($deck['image_path'])
                        ? asset('storage/' . $deck['image_path'])
                        : null,
                    'walls' => [],
                    'seats' => [],
                    'zones' => [],
                ];
            }

            // Keep deck 1 image path for legacy compatibility
            $payload['layout_image_path'] = $this->decks_data[0]['image_path'] ?? null;
            $payload['seat_layout_json']  = [
                'version' => 2,
                'decks'   => $decksPayload,
            ];
        }

        try {
            if ($this->isEdit) {
                $payloadBefore = $this->ferry->toArray();
                $request = $dcService->submit('ferry_update', 'ferry', $this->ferry->id, [
                    'before' => $payloadBefore,
                    'after' => $payload
                ]);
                $successMessage = 'Ferry update submitted for approval.';
                $flashMessage = 'Ferry updated successfully.';
            } else {
                $request = $dcService->submit('ferry_create', 'ferry', 0, ['after' => $payload]);
                $successMessage = 'Ferry registration submitted for approval.';
                $flashMessage = 'Ferry registered successfully.';
            }
            
            if ($request->status === 'approved') {
                $ferry = $this->isEdit ? $this->ferry : \App\Models\Ferry::where('name', $this->name)->first();
                session()->flash('success', $flashMessage);
                
                if ($this->has_layout && !$this->isEdit) {
                    return redirect()->route('admin.ferries.editor', $ferry->id);
                } else {
                    return redirect()->route('admin.ferries.show', $ferry->id);
                }
            } else {
                session()->flash('success', $successMessage);
                return $this->isEdit ? redirect()->route('admin.ferries.show', $this->ferry->id) : redirect()->route('admin.ferries.index');
            }
        } catch (\Exception $e) {
            Log::error('Ferry form dual control failed: ' . $e->getMessage());
            $this->addError('submit_error', 'An error occurred while submitting the request: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $ports = \App\Models\Port::where('status', 'active')->orderBy('name')->get();
        return view('admin.livewire.ferry-wizard', compact('ports'));
    }
}
