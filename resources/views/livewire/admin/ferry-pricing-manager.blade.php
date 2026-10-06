<div>
    <!-- Route Prices Table (Read Only View) -->
    <div class="mb-8">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-slate-800">Assigned Route Prices</h3>
            @php $canUpdatePricing = auth()->user()->hasPermission('fare:update'); @endphp
            @if($canUpdatePricing)
            <button wire:click="openModal" class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium rounded-lg shadow-sm transition-colors">
                <i class="fas fa-edit mr-2"></i> Manage Route Prices
            </button>
            @else
            <button disabled class="px-4 py-2 bg-slate-100 border border-slate-200 text-slate-400 text-sm font-medium rounded-lg cursor-not-allowed" title="Manage Route Prices (No Permission)">
                <i class="fas fa-edit mr-2"></i> Manage Route Prices
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
                        <th class="px-6 py-4">Route</th>
                        <th class="px-6 py-4">Class</th>
                        <th class="px-6 py-4 text-right">Adult Price</th>
                        <th class="px-6 py-4 text-right">Child Price</th>
                        <th class="px-6 py-4 text-right">USD (Adult/Child)</th>
                        <th class="px-6 py-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($ferry->fares()->with(['route.origin', 'route.destination', 'ferryClass'])->get() as $fare)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-slate-800">{{ $fare->route->name ?? 'Any Route' }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $fare->ferryClass ? $fare->ferryClass->name : 'All Classes' }}</td>
                            <td class="px-6 py-4 text-right font-medium text-slate-900">{{ number_format($fare->adult_price, 2) }} TZS</td>
                            <td class="px-6 py-4 text-right text-slate-600">{{ number_format($fare->child_price, 2) }} TZS</td>
                            <td class="px-6 py-4 text-right text-slate-500">${{ number_format($fare->adult_price_usd, 2) }} / ${{ number_format($fare->child_price_usd, 2) }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                    {{ ucfirst($fare->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500">No route prices configured for this ferry yet.</td>
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
                        <i class="fas fa-tags text-brand-500 mr-2"></i> Configure Prices by Route
                    </h3>
                    <button @click="open = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="px-6 py-6 bg-white max-h-[70vh] overflow-y-auto">
                    <!-- Route Selection -->
                    <div class="mb-8">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Select Route to Manage Prices For</label>
                        <select wire:model.live="selectedRouteId" class="w-full md:w-1/2 rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">-- Choose Route --</option>
                            @foreach($routes as $r)
                                <option value="{{ $r->id }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Price Configuration Grid -->
                    @if($selectedRouteId && count($classes) > 0)
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
                                                            <input type="number" wire:model="roomPrices.{{ $room->id }}.adult_price" class="w-full text-sm rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:opacity-50" {{ empty($roomPrices[$room->id]['enabled']) ? 'disabled' : '' }}>
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Child (TZS)</label>
                                                            <input type="number" wire:model="roomPrices.{{ $room->id }}.child_price" class="w-full text-sm rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:opacity-50" {{ empty($roomPrices[$room->id]['enabled']) ? 'disabled' : '' }}>
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] text-brand-600 font-black uppercase mb-1">Room (TZS)</label>
                                                            <input type="number" wire:model="roomPrices.{{ $room->id }}.whole_room_price" class="w-full text-sm rounded-lg border-brand-300 bg-brand-50 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:opacity-50" {{ empty($roomPrices[$room->id]['enabled']) ? 'disabled' : '' }}>
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Adult (USD)</label>
                                                            <input type="number" wire:model="roomPrices.{{ $room->id }}.adult_price_usd" class="w-full text-sm rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:opacity-50" {{ empty($roomPrices[$room->id]['enabled']) ? 'disabled' : '' }}>
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Child (USD)</label>
                                                            <input type="number" wire:model="roomPrices.{{ $room->id }}.child_price_usd" class="w-full text-sm rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:opacity-50" {{ empty($roomPrices[$room->id]['enabled']) ? 'disabled' : '' }}>
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] text-brand-600 font-black uppercase mb-1">Room (USD)</label>
                                                            <input type="number" wire:model="roomPrices.{{ $room->id }}.whole_room_price_usd" class="w-full text-sm rounded-lg border-brand-300 bg-brand-50 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:opacity-50" {{ empty($roomPrices[$room->id]['enabled']) ? 'disabled' : '' }}>
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
                                                <input type="number" wire:model="prices.{{ $cls->id }}.adult_price" class="w-full text-sm rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:opacity-50" {{ empty($prices[$cls->id]['enabled']) ? 'disabled' : '' }}>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Child (TZS)</label>
                                                <input type="number" wire:model="prices.{{ $cls->id }}.child_price" class="w-full text-sm rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:opacity-50" {{ empty($prices[$cls->id]['enabled']) ? 'disabled' : '' }}>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Adult (USD)</label>
                                                <input type="number" wire:model="prices.{{ $cls->id }}.adult_price_usd" class="w-full text-sm rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:opacity-50" {{ empty($prices[$cls->id]['enabled']) ? 'disabled' : '' }}>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Child (USD)</label>
                                                <input type="number" wire:model="prices.{{ $cls->id }}.child_price_usd" class="w-full text-sm rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:opacity-50" {{ empty($prices[$cls->id]['enabled']) ? 'disabled' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @elseif($selectedRouteId)
                        <div class="text-center py-8 text-slate-500">
                            No classes found for this ferry. Please configure ferry classes first.
                        </div>
                    @endif
                </div>

                <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end gap-3">
                    <button type="button" @click="open = false" class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 shadow-sm transition-colors">
                        Close
                    </button>
                    @if($selectedRouteId && count($classes) > 0)
                        <button type="button" wire:click="savePrices" @click="open = false" class="px-5 py-2.5 text-sm font-medium text-white bg-brand-500 hover:bg-brand-600 rounded-xl shadow-sm transition-colors flex items-center gap-2">
                            <i class="fas fa-check"></i> Submit
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
