<div>
    <!-- Route Prices Table (Read Only View) -->
    <div class="mb-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Route Fare Configurations</h1>
            @php $canUpdatePricing = auth()->user()->hasPermission('fare:update'); @endphp
            @if($canUpdatePricing)
            <button wire:click="openModal" class="px-5 py-2.5 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium rounded-xl shadow-sm transition-colors flex items-center gap-2">
                <i class="fas fa-tags"></i> Manage Prices
            </button>
            @else
            <button disabled class="px-5 py-2.5 bg-slate-100 border border-slate-200 text-slate-400 text-sm font-medium rounded-xl cursor-not-allowed flex items-center gap-2" title="Manage Prices (No Permission)">
                <i class="fas fa-tags"></i> Manage Prices
            </button>
            @endif
        </div>

        @if (session()->has('success'))
            <div class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start gap-3">
                <i class="fas fa-check-circle text-emerald-500 mt-0.5"></i>
                <div class="flex-1">
                    <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif
        
        <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-sm">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">Transport Unit</th>
                        <th class="px-6 py-4">Cargo Type</th>
                        <th class="px-6 py-4 text-right">Base Price (TZS)</th>
                        <th class="px-6 py-4 text-right">Per KG (TZS)</th>
                        <th class="px-6 py-4 text-right">USD (Base/KG)</th>
                        <th class="px-6 py-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($route->cargoFares()->with(['transportUnit', 'cargoType'])->get() as $fare)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-slate-800">{{ $fare->transportUnit->name ?? 'Any Transport Unit' }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $fare->cargoType->name ?? 'All Cargo Types' }}</td>
                            <td class="px-6 py-4 text-right font-medium text-slate-900">{{ number_format($fare->price_tzs, 2) }} TZS</td>
                            <td class="px-6 py-4 text-right text-slate-600">{{ number_format($fare->price_per_kg_tzs, 2) }} TZS</td>
                            <td class="px-6 py-4 text-right text-slate-500">${{ number_format($fare->price_usd, 2) }} / ${{ number_format($fare->price_per_kg_usd, 2) }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                    {{ ucfirst($fare->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500">No prices configured for this route yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Managing Prices -->
    <div x-data="{ open: $wire.entangle('isModalOpen') }" 
         x-show="open" 
         @keydown.escape.window="open = false"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
         
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="open" 
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="open = false" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="open" 
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-slate-200">
                
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-semibold text-slate-800" id="modal-title">
                        <i class="fas fa-money-bill-wave text-brand-500 mr-2"></i> Route Prices Management
                    </h3>
                    <button @click="open = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="px-6 py-6 bg-white max-h-[70vh] overflow-y-auto">
                    <!-- Ferry Selection -->
                    <div class="mb-8">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 block">Select Transport Unit to Manage</label>
                        <select wire:model.live="selectedUnitId" class="w-full rounded-xl border-slate-200 bg-slate-50 hover:bg-white focus:bg-white px-4 py-3.5 text-sm font-medium text-slate-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 transition-colors">
                            <option value="">-- Choose Transport Unit --</option>
                            <option value="default">Default Prices (All Transport Units & Cargo Types)</option>
                            @foreach($units as $f)
                                <option value="{{ $f->id }}">{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Price Configuration Grid -->
                    @if($selectedUnitId === 'default')
                        <div class="space-y-4">
                            <div class="grid grid-cols-12 gap-4 text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 px-2">
                                <div class="col-span-3">Configuration</div>
                                <div class="col-span-2 text-right">Adult Price (TZS)</div>
                                <div class="col-span-2 text-right">Child Price (TZS)</div>
                                <div class="col-span-2 text-right">Adult Price (USD)</div>
                                <div class="col-span-2 text-right">Child Price (USD)</div>
                            </div>
                            
                            <div class="grid grid-cols-12 gap-4 items-center bg-slate-50 p-3 rounded-xl border border-slate-200">
                                <div class="col-span-3 flex items-center gap-3">
                                    <input type="checkbox" wire:model.live="prices.default.enabled" class="rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                                    <span class="text-sm font-semibold text-slate-800">Apply Default Price</span>
                                </div>
                                <div class="col-span-2">
                                    <input type="number" wire:model="prices.default.price_tzs" class="w-full rounded-xl border-slate-200 bg-slate-50 hover:bg-white focus:bg-white px-4 py-3.5 text-sm font-medium text-slate-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 transition-colors" {{ empty($prices['default']['enabled']) ? 'disabled' : '' }}>
                                </div>
                                <div class="col-span-2">
                                    <input type="number" wire:model="prices.default.price_per_kg_tzs" class="w-full rounded-xl border-slate-200 bg-slate-50 hover:bg-white focus:bg-white px-4 py-3.5 text-sm font-medium text-slate-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 transition-colors" {{ empty($prices['default']['enabled']) ? 'disabled' : '' }}>
                                </div>
                                <div class="col-span-2">
                                    <input type="number" wire:model="prices.default.price_usd" class="w-full rounded-xl border-slate-200 bg-slate-50 hover:bg-white focus:bg-white px-4 py-3.5 text-sm font-medium text-slate-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 transition-colors" {{ empty($prices['default']['enabled']) ? 'disabled' : '' }}>
                                </div>
                                <div class="col-span-2">
                                    <input type="number" wire:model="prices.default.price_per_kg_usd" class="w-full rounded-xl border-slate-200 bg-slate-50 hover:bg-white focus:bg-white px-4 py-3.5 text-sm font-medium text-slate-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 transition-colors" {{ empty($prices['default']['enabled']) ? 'disabled' : '' }}>
                                </div>
                            </div>
                        </div>
                    @elseif($selectedUnitId && count($classes) > 0)
                        <div class="space-y-4">
                            @foreach($classes as $cls)
                                @if($cls->is_room_based)
                                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                                        <div class="font-bold text-slate-800 mb-3">{{ $cls->name }} <span class="text-xs font-normal text-slate-500">(Room Based)</span></div>
                                        <div class="space-y-3">
                                            @foreach($cls->rooms as $room)
                                                <div class="grid grid-cols-12 gap-3 items-center bg-white p-3 rounded-lg border border-slate-200 shadow-sm">
                                                    <div class="col-span-12 md:col-span-2 flex items-center gap-2">
                                                        <input type="checkbox" wire:model.live="roomPrices.{{ $room->id }}.enabled" class="rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                                                        <span class="text-sm font-semibold text-slate-700">{{ $room->name }} <span class="text-xs text-slate-500 font-normal">({{ $room->capacity }} Seats)</span></span>
                                                    </div>
                                                    <div class="col-span-12 md:col-span-10 grid grid-cols-2 sm:grid-cols-6 gap-3">
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Adult (TZS)</label>
                                                            <input type="number" wire:model="roomPrices.{{ $room->id }}.price_tzs" class="w-full text-sm rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:opacity-50" {{ empty($roomPrices[$room->id]['enabled']) ? 'disabled' : '' }}>
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Child (TZS)</label>
                                                            <input type="number" wire:model="roomPrices.{{ $room->id }}.price_per_kg_tzs" class="w-full text-sm rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:opacity-50" {{ empty($roomPrices[$room->id]['enabled']) ? 'disabled' : '' }}>
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] text-brand-600 font-black uppercase mb-1">Room (TZS)</label>
                                                            <input type="number" wire:model="roomPrices.{{ $room->id }}.min_charge_tzs" class="w-full text-sm rounded-lg border-brand-300 bg-brand-50 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:opacity-50" {{ empty($roomPrices[$room->id]['enabled']) ? 'disabled' : '' }}>
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Adult (USD)</label>
                                                            <input type="number" wire:model="roomPrices.{{ $room->id }}.price_usd" class="w-full text-sm rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:opacity-50" {{ empty($roomPrices[$room->id]['enabled']) ? 'disabled' : '' }}>
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Child (USD)</label>
                                                            <input type="number" wire:model="roomPrices.{{ $room->id }}.price_per_kg_usd" class="w-full text-sm rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:opacity-50" {{ empty($roomPrices[$room->id]['enabled']) ? 'disabled' : '' }}>
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] text-brand-600 font-black uppercase mb-1">Room (USD)</label>
                                                            <input type="number" wire:model="roomPrices.{{ $room->id }}.min_charge_usd" class="w-full text-sm rounded-lg border-brand-300 bg-brand-50 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:opacity-50" {{ empty($roomPrices[$room->id]['enabled']) ? 'disabled' : '' }}>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <div class="grid grid-cols-12 gap-4 items-center bg-slate-50 p-4 rounded-xl border border-slate-200">
                                        <div class="col-span-12 md:col-span-4 flex items-center gap-3">
                                            <input type="checkbox" wire:model.live="prices.{{ $cls->id }}.enabled" class="rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                                            <span class="text-sm font-semibold text-slate-800">{{ $cls->name }}</span>
                                        </div>
                                        <div class="col-span-12 md:col-span-8 grid grid-cols-2 sm:grid-cols-4 gap-3">
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Adult (TZS)</label>
                                                <input type="number" wire:model="prices.{{ $cls->id }}.price_tzs" class="w-full text-sm rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:opacity-50" {{ empty($prices[$cls->id]['enabled']) ? 'disabled' : '' }}>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Child (TZS)</label>
                                                <input type="number" wire:model="prices.{{ $cls->id }}.price_per_kg_tzs" class="w-full text-sm rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:opacity-50" {{ empty($prices[$cls->id]['enabled']) ? 'disabled' : '' }}>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Adult (USD)</label>
                                                <input type="number" wire:model="prices.{{ $cls->id }}.price_usd" class="w-full text-sm rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:opacity-50" {{ empty($prices[$cls->id]['enabled']) ? 'disabled' : '' }}>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Child (USD)</label>
                                                <input type="number" wire:model="prices.{{ $cls->id }}.price_per_kg_usd" class="w-full text-sm rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:opacity-50" {{ empty($prices[$cls->id]['enabled']) ? 'disabled' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @elseif($selectedUnitId)
                        <div class="text-center py-8 text-slate-500">
                            No cargo types found for this transport unit. Please configure cargo types first.
                        </div>
                    @endif
                </div>

                <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end gap-3">
                    <button type="button" @click="open = false" class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 shadow-sm transition-colors">
                        Close
                    </button>
                    @if($selectedUnitId === 'default' || ($selectedUnitId && count($classes) > 0))
                        <button type="button" wire:click="savePrices" class="px-5 py-2.5 text-sm font-medium text-white bg-brand-500 hover:bg-brand-600 rounded-xl shadow-sm transition-colors flex items-center gap-2">
                            <i class="fas fa-check"></i> Submit
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
