<div>
    <!-- Progress Bar -->
    <div class="mb-8">
        <div class="flex items-center justify-between relative px-8">
            <div class="absolute left-8 right-8 top-1/2 -translate-y-1/2 h-1 bg-slate-200 rounded-full z-0"></div>
            <div class="absolute left-8 top-1/2 -translate-y-1/2 h-1 bg-blue-600 rounded-full z-0 transition-all duration-500" style="width: {{ $step === 1 ? '0%' : ($step === 2 ? '50%' : '100%') }}"></div>
            
            <!-- Step 1 Indicator -->
            <div class="relative z-10 flex flex-col items-center">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold {{ $step >= 1 ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-500' }} border-4 border-white shadow-sm transition-colors">
                    1
                </div>
                <span class="mt-2 text-sm font-semibold {{ $step >= 1 ? 'text-blue-700' : 'text-slate-500' }}">Vessel Details</span>
            </div>
            
            <!-- Step 2 Indicator -->
            <div class="relative z-10 flex flex-col items-center">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold {{ $step >= 2 ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-500' }} border-4 border-white shadow-sm transition-colors">
                    2
                </div>
                <span class="mt-2 text-sm font-semibold {{ $step >= 2 ? 'text-blue-700' : 'text-slate-500' }}">Seating Classes</span>
            </div>

            <!-- Step 3 Indicator -->
            <div class="relative z-10 flex flex-col items-center {{ !$has_layout ? 'opacity-50' : '' }}">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold {{ $step >= 3 ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-500' }} border-4 border-white shadow-sm transition-colors">
                    3
                </div>
                <span class="mt-2 text-sm font-semibold {{ $step >= 3 ? 'text-blue-700' : 'text-slate-500' }}">Deck Layouts</span>
            </div>
        </div>
    </div>

    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl flex items-start gap-3">
            <i class="fas fa-circle-exclamation mt-1"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @error('submit_error')
        <div class="mb-4 p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl flex items-start gap-3">
            <i class="fas fa-circle-exclamation mt-1"></i>
            <div>{{ $message }}</div>
        </div>
    @enderror

    <!-- Form Content -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-8">
        
        <!-- STEP 1: Basic Info -->
        @if ($step === 1)
        <div>
            <h3 class="text-lg font-bold text-slate-800 mb-6 border-b border-slate-100 pb-3"><i class="fas fa-ship text-blue-500 mr-2"></i> Step 1: Vessel Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Vessel Name <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="name" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g. MV Kilimanjaro VII">
                    @error('name') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Vessel Type <span class="text-rose-500">*</span></label>
                    <select wire:model="type" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="Passenger Ferry">Passenger Ferry</option>
                        <option value="Ro-Pax Ferry">Ro-Pax Ferry (Roll-on/Roll-off Passenger)</option>
                        <option value="Fast Ferry">Fast Ferry (Catamaran)</option>
                        <option value="Cruise Ferry">Cruise Ferry</option>
                    </select>
                    @error('type') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Port of Registry</label>
                    <select wire:model="port_of_registry" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Select a Port...</option>
                        @foreach($ports as $port)
                            <option value="{{ $port->name }}">{{ $port->name }}</option>
                        @endforeach
                    </select>
                    @error('port_of_registry') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Total Capacity (Pax) <span class="text-rose-500">*</span></label>
                    <input type="number" wire:model="capacity" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Total passenger capacity">
                    @error('capacity') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mt-6 p-5 bg-slate-50 border border-slate-200 rounded-xl space-y-4">
                <div class="flex items-center gap-3">
                    <input type="checkbox" wire:model.live="has_layout" id="wizard_has_layout" class="w-5 h-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <label for="wizard_has_layout" class="text-sm font-semibold text-slate-800">Allows Seat Selection (Has Digital Layout)</label>
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <button type="button" wire:click="nextStep" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl shadow-sm transition-colors flex items-center gap-2">
                    Next Step <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
        @endif

        <!-- STEP 2: Classes -->
        @if ($step === 2)
        <div>
            <h3 class="text-lg font-bold text-slate-800 mb-2"><i class="fas fa-couch text-blue-500 mr-2"></i> Step 2: Seating Classes</h3>
            <p class="text-sm text-slate-500 mb-6 border-b border-slate-100 pb-3">Define the passenger classes available on this vessel. Total capacity must equal {{ $capacity }} Pax.</p>
            
            @error('capacity_mismatch')
                <div class="mb-4 p-4 bg-amber-50 border border-amber-200 text-amber-700 rounded-xl flex items-start gap-3">
                    <i class="fas fa-triangle-exclamation mt-1"></i>
                    <div class="font-medium">{{ $message }}</div>
                </div>
            @enderror
            @error('classes')
                <div class="mb-4 p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl">
                    <div class="font-medium">{{ $message }}</div>
                </div>
            @enderror

            <div class="space-y-4">
                @foreach($classes as $index => $class)
                    <div class="flex items-start gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-200 relative group">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Class Name <span class="text-rose-500">*</span></label>
                            <input type="text" wire:model="classes.{{ $index }}.name" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g. VIP, Economy">
                            @error('classes.'.$index.'.name') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror

                            <!-- Room-Based Toggle -->
                            <div class="mt-4 flex items-center gap-2">
                                <input type="checkbox" wire:click="toggleRoomBased({{ $index }})" id="is_room_based_{{ $index }}" @if(!empty($class['is_room_based'])) checked @endif class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <label for="is_room_based_{{ $index }}" class="text-xs font-bold text-slate-600">Is Room-Based? (e.g. Royal Rooms)</label>
                            </div>

                            <!-- Room Builder -->
                            @if(!empty($class['is_room_based']))
                                <div class="mt-4 pl-4 border-l-2 border-blue-200 space-y-3">
                                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Configure Rooms</h4>
                                    @if(isset($class['rooms']))
                                        @foreach($class['rooms'] as $roomIndex => $room)
                                            <div class="flex items-end gap-3 bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
                                                <div class="flex-1">
                                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Room Name</label>
                                                    <input type="text" wire:model="classes.{{ $index }}.rooms.{{ $roomIndex }}.name" class="w-full text-sm rounded-lg border-slate-300 py-1.5 focus:border-blue-500 focus:ring-blue-500" placeholder="e.g. Room A">
                                                    @error("classes.{$index}.rooms.{$roomIndex}.name") <span class="text-rose-500 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                                                </div>
                                                <div class="w-24">
                                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Seats</label>
                                                    <input type="number" wire:model.live="classes.{{ $index }}.rooms.{{ $roomIndex }}.capacity" class="w-full text-sm rounded-lg border-slate-300 py-1.5 focus:border-blue-500 focus:ring-blue-500" placeholder="Pax">
                                                    @error("classes.{$index}.rooms.{$roomIndex}.capacity") <span class="text-rose-500 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                                                </div>
                                                <button wire:click="removeRoom({{ $index }}, {{ $roomIndex }})" type="button" class="p-1.5 text-slate-400 hover:text-rose-500 transition-colors" title="Remove Room">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        @endforeach
                                    @endif
                                    <button wire:click="addRoom({{ $index }})" type="button" class="text-xs text-blue-600 font-bold flex items-center gap-1 hover:text-blue-700">
                                        <i class="fas fa-plus-circle"></i> Add Another Room
                                    </button>
                                    @error('classes.'.$index.'.rooms') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            @endif
                        </div>
                        <div class="w-1/3">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Capacity <span class="text-rose-500">*</span></label>
                            <input type="number" wire:model="classes.{{ $index }}.capacity" @if(!empty($class['is_room_based'])) disabled class="w-full rounded-lg border-slate-100 bg-slate-100 text-slate-500 cursor-not-allowed shadow-none" @else class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" @endif placeholder="Pax limit">
                            @if(!empty($class['is_room_based']))
                                <p class="text-[10px] text-slate-400 mt-1">Auto-calculated from rooms.</p>
                            @endif
                            @error('classes.'.$index.'.capacity') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        
                        @if(count($classes) > 1)
                            <button wire:click="removeClass({{ $index }})" type="button" class="mt-6 p-2.5 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-colors">
                                <i class="fas fa-trash"></i>
                            </button>
                        @else
                            <div class="w-10 h-10 mt-6"></div> <!-- Spacer -->
                        @endif
                    </div>
                @endforeach
            </div>

            <button wire:click="addClass" type="button" class="mt-4 px-4 py-2 border-2 border-dashed border-blue-200 text-blue-600 hover:bg-blue-50 rounded-xl text-sm font-bold w-full transition-colors flex items-center justify-center gap-2">
                <i class="fas fa-plus"></i> Add Another Class
            </button>

            <div class="mt-8 flex justify-between items-center border-t border-slate-100 pt-6">
                <button wire:click="previousStep" class="px-6 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium rounded-xl shadow-sm transition-colors flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-xs font-bold text-slate-500 uppercase">Allocated Capacity</p>
                        <p class="text-lg font-black {{ collect($classes)->sum('capacity') == $capacity ? 'text-emerald-600' : 'text-amber-600' }}">
                            {{ collect($classes)->sum('capacity') }} / {{ $capacity }}
                        </p>
                    </div>
                    @if ($has_layout)
                    <button wire:click="goToStep3" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl shadow-sm transition-colors flex items-center gap-2">
                        Next Step <i class="fas fa-arrow-right"></i>
                    </button>
                    @else
                    <button wire:click="submit" wire:loading.attr="disabled" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-medium rounded-xl shadow-sm transition-colors flex items-center gap-2">
                        <span wire:loading.remove wire:target="submit"><i class="fas fa-check"></i> {{ $isEdit ? 'Update Vessel' : 'Submit Vessel' }}</span>
                        <span wire:loading wire:target="submit"><i class="fas fa-circle-notch fa-spin"></i> Processing...</span>
                    </button>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- STEP 3: Multi-Deck Layout Upload -->
        @if ($step === 3 && $has_layout)
        <div>
            <h3 class="text-lg font-bold text-slate-800 mb-2"><i class="fas fa-layer-group text-purple-500 mr-2"></i> Step 3: Deck Layout Upload</h3>
            <p class="text-sm text-slate-500 mb-6 border-b border-slate-100 pb-3">Specify how many decks this vessel has, then upload a floor plan image for each deck. You'll use these images to map seats in the Layout Studio.</p>

            @error('decks_data.0.image')
                <div class="mb-4 p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl flex items-start gap-2">
                    <i class="fas fa-circle-exclamation mt-0.5"></i>
                    <div class="font-medium">{{ $message }}</div>
                </div>
            @enderror

            {{-- Deck Count Stepper --}}
            <div class="mb-8 p-5 bg-purple-50 border border-purple-200 rounded-2xl flex items-center gap-6">
                <div class="flex-shrink-0">
                    <i class="fas fa-ship text-3xl text-purple-400"></i>
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-bold text-purple-800 mb-1">How many decks does this vessel have?</label>
                    <p class="text-xs text-purple-600 mb-3">Between 1 and 5. Each deck gets its own layout image and seat map.</p>
                    <div class="flex items-center gap-3">
                        <button type="button" wire:click="$set('deck_count', {{ max(1, $deck_count - 1) }})"
                            class="w-9 h-9 rounded-full bg-white border border-purple-300 text-purple-700 font-bold text-lg flex items-center justify-center hover:bg-purple-100 transition-colors shadow-sm {{ $deck_count <= 1 ? 'opacity-40 cursor-not-allowed' : '' }}"
                            {{ $deck_count <= 1 ? 'disabled' : '' }}>
                            <i class="fas fa-minus text-xs"></i>
                        </button>
                        <input type="number" wire:model.live="deck_count" min="1" max="5"
                            class="w-16 text-center text-2xl font-black text-purple-800 border-0 bg-transparent focus:ring-0 focus:outline-none"
                            style="appearance: textfield; -moz-appearance: textfield;">
                        <button type="button" wire:click="$set('deck_count', {{ min(5, $deck_count + 1) }})"
                            class="w-9 h-9 rounded-full bg-white border border-purple-300 text-purple-700 font-bold text-lg flex items-center justify-center hover:bg-purple-100 transition-colors shadow-sm {{ $deck_count >= 5 ? 'opacity-40 cursor-not-allowed' : '' }}"
                            {{ $deck_count >= 5 ? 'disabled' : '' }}>
                            <i class="fas fa-plus text-xs"></i>
                        </button>
                        <span class="text-sm font-semibold text-purple-600">{{ $deck_count === 1 ? 'Deck' : 'Decks' }}</span>
                    </div>
                </div>
            </div>

            {{-- Per-Deck Upload Cards --}}
            <div class="space-y-5">
                @foreach($decks_data as $i => $deck)
                <div class="border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                    {{-- Deck Header --}}
                    <div class="flex items-center gap-4 px-5 py-3 bg-slate-800">
                        <div class="w-8 h-8 rounded-full bg-white/10 text-white font-black text-sm flex items-center justify-center">
                            {{ $i + 1 }}
                        </div>
                        <input type="text" wire:model="decks_data.{{ $i }}.name"
                            class="flex-1 bg-transparent border-0 text-white font-bold text-sm placeholder-white/40 focus:ring-0 focus:outline-none"
                            placeholder="Deck name (e.g. Deck {{ $i + 1 }})">
                        <span class="text-white/40 text-xs font-medium uppercase tracking-wider">
                            <i class="fas fa-pencil-alt mr-1"></i> Rename
                        </span>
                    </div>

                    {{-- Upload Zone --}}
                    <div class="p-5 bg-white">
                        <div class="relative border-2 border-dashed {{ !empty($decks_data[$i]['image']) || !empty($decks_data[$i]['image_path']) ? 'border-emerald-400 bg-emerald-50' : 'border-slate-300 bg-slate-50 hover:bg-slate-100' }} rounded-xl p-8 text-center transition-colors">
                            <input type="file" wire:model="decks_data.{{ $i }}.image" accept="image/*"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">

                            @if(!empty($decks_data[$i]['image']) || !empty($decks_data[$i]['image_path']))
                                <i class="fas fa-check-circle text-4xl text-emerald-500 mb-3"></i>
                                <p class="font-bold text-emerald-700">Floor plan uploaded!</p>
                                <p class="text-xs text-emerald-600 mt-1">Click to replace</p>
                            @else
                                <i class="fas fa-cloud-upload-alt text-4xl text-slate-400 mb-3"></i>
                                <p class="font-bold text-slate-700">Drop floor plan for <span class="text-purple-600">{{ $deck['name'] ?? 'Deck ' . ($i + 1) }}</span></p>
                                <p class="text-xs text-slate-500 mt-1">JPG or PNG, max 5 MB{{ $isEdit || $i > 0 ? ' (optional)' : ' (required)' }}</p>
                            @endif
                        </div>

                        @error("decks_data.{$i}.image")
                            <p class="text-rose-500 text-xs mt-2 flex items-center gap-1">
                                <i class="fas fa-circle-exclamation"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mt-8 flex justify-between items-center border-t border-slate-100 pt-6">
                <button wire:click="previousStep" class="px-6 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium rounded-xl shadow-sm transition-colors flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <button wire:click="submit" wire:loading.attr="disabled" class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold rounded-xl shadow-sm transition-colors flex items-center gap-2">
                    <span wire:loading.remove wire:target="submit"><i class="fas fa-check"></i> {{ $isEdit ? 'Update & Go to Editor' : 'Submit & Go to Editor' }}</span>
                    <span wire:loading wire:target="submit"><i class="fas fa-circle-notch fa-spin"></i> Processing...</span>
                </button>
            </div>
        </div>
        @endif

    </div>
</div>
