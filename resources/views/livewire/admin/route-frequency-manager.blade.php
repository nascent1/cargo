<div>
    <!-- Route Trips Table -->
    <div class="mb-8">
        @if (session()->has('error'))
            <div class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200 flex items-start gap-3">
                <i class="fas fa-circle-exclamation text-red-500 mt-0.5"></i>
                <p class="flex-1 text-sm font-medium text-red-800">{{ session('error') }}</p>
            </div>
        @endif
        @if (session()->has('success'))
            <div class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-sm font-medium text-emerald-800"><i class="fas fa-check-circle mr-1"></i>{{ session('success') }}</div>
        @endif
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-slate-800">Trips Management</h3>
            @php $canUpdateRoute = auth()->user()->hasPermission('route:update'); @endphp
            @if($canUpdateRoute)
            <button x-data @click="$wire.resetForm(); $dispatch('open-modal', 'manage-frequency-modal')" class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium rounded-lg shadow-sm transition-colors">
                <i class="fas fa-plus mr-2"></i> Add New Trip
            </button>
            @else
            <button disabled class="px-4 py-2 bg-slate-100 border border-slate-200 text-slate-400 text-sm font-medium rounded-lg cursor-not-allowed" title="Add New Trip (No Permission)">
                <i class="fas fa-plus mr-2"></i> Add New Trip
            </button>
            @endif
        </div>
        
        <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-sm">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">Departure Time</th>
                        <th class="px-6 py-4">Arrival Time</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($trips as $trip)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-slate-800">{{ \Carbon\Carbon::parse($trip->departure_time)->format('H:i') }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ \Carbon\Carbon::parse($trip->arrival_time)->format('H:i') }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                    {{ ucfirst($trip->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right flex justify-end gap-3">
                                @php $canUpdateRoute = auth()->user()->hasPermission('route:update'); @endphp
                                @if($canUpdateRoute)
                                <button wire:click="editTrip({{ $trip->id }})" class="text-slate-400 hover:text-blue-600 transition-colors" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @php $approveDelete ??= app(\App\Services\DualControlService::class)->requiresDualControl('trip_delete'); @endphp
                                <button type="button"
                                    x-on:click="if (await confirmDialog(@js([
                                        'title' => 'Delete the ' . \Carbon\Carbon::parse($trip->departure_time)->format('H:i') . ' trip?',
                                        'message' => $approveDelete ? 'A checker must approve it first.' : 'It is removed from this route.',
                                        'tone' => 'danger',
                                        'confirmText' => $approveDelete ? 'Request deletion' : 'Delete',
                                    ]))) $wire.deleteTrip({{ $trip->id }})"
                                    class="text-rose-400 hover:text-rose-600 transition-colors" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @else
                                <button disabled class="text-slate-200 cursor-not-allowed" title="Edit (No Permission)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button disabled class="text-rose-200 cursor-not-allowed" title="Delete (No Permission)">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500">No trips configured for this route yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Managing Frequency -->
    <div x-data="{ open: false }" 
         x-show="open" 
         @open-modal.window="if ($event.detail === 'manage-frequency-modal') open = true"
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
                 class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200">
                
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-semibold text-slate-800" id="modal-title">
                        <i class="fas fa-clock text-brand-500 mr-2"></i> {{ $trip_id ? 'Edit Trip' : 'Register New Trip' }}
                    </h3>
                    <button @click="open = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="px-6 py-6 bg-white">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 block">Departure Time <span class="text-rose-500">*</span></label>
                            <input type="time" wire:model="departure_time" required class="w-full rounded-xl border-slate-200 bg-slate-50 hover:bg-white focus:bg-white px-4 py-3.5 text-sm font-medium text-slate-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 transition-colors">
                            @error('departure_time') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 block">Arrival Time <span class="text-rose-500">*</span></label>
                            <input type="time" wire:model="arrival_time" required class="w-full rounded-xl border-slate-200 bg-slate-50 hover:bg-white focus:bg-white px-4 py-3.5 text-sm font-medium text-slate-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 transition-colors">
                            @error('arrival_time') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <x-dual-control.takes-effect :op="$trip_id ? 'trip_update' : 'trip_create'" wire class="mt-4" />
                </div>

                <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end gap-3">
                    <button type="button" @click="$dispatch('close-modal', 'manage-frequency-modal')" class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 shadow-sm transition-colors">
                        Close
                    </button>
                    <button type="button" wire:click="saveTrip" class="px-5 py-2.5 text-sm font-medium text-white bg-brand-500 hover:bg-brand-600 rounded-xl shadow-sm transition-colors flex items-center gap-2">
                        <i class="fas fa-check"></i> Save
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
