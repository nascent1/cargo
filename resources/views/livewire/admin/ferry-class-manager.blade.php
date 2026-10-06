<div>
    <div class="mb-8">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-slate-800">Ferry Classes</h3>
            @php $canCreateClass = auth()->user()->hasPermission('ferry_class:create'); @endphp
            @if($canCreateClass)
            <button wire:click="openModal" class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium rounded-lg shadow-sm transition-colors">
                <i class="fas fa-plus mr-2"></i> Add Class
            </button>
            @else
            <button disabled class="px-4 py-2 bg-slate-100 border border-slate-200 text-slate-400 text-sm font-medium rounded-lg cursor-not-allowed" title="Add Class (No Permission)">
                <i class="fas fa-plus mr-2"></i> Add Class
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
                        <th class="px-6 py-4">Class Name</th>
                        <th class="px-6 py-4">Capacity</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($classes as $cls)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-800">{{ $cls->name }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ number_format($cls->capacity) }} Pax</td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center rounded-md {{ match($cls->status) {
                                    'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                    'inactive' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                    default => 'bg-slate-50 text-slate-600 ring-slate-500/10'
                                } }} px-2 py-1 text-xs font-medium ring-1 ring-inset uppercase tracking-wider">
                                    {{ $cls->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right flex justify-end gap-3">
                                @php 
                                    $canUpdateClass = auth()->user()->hasPermission('ferry_class:update'); 
                                    $canDeleteClass = auth()->user()->hasPermission('ferry_class:delete');
                                @endphp
                                @if($canUpdateClass)
                                <button wire:click="editClass({{ $cls->id }})" class="text-slate-400 hover:text-brand-600 transition-colors" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @else
                                <button disabled class="text-slate-200 cursor-not-allowed" title="Edit (No Permission)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @endif
                                
                                @if($cls->status !== 'inactive')
                                    @if($canDeleteClass)
                                    <button @click="$dispatch('open-action-modal', {
                                        id: {{ $cls->id }},
                                        action: 'suspendClass',
                                        title: 'Suspend Class',
                                        message: 'Are you sure you want to suspend this class? This will set its status to inactive.',
                                        buttonText: 'Suspend',
                                        buttonColor: 'amber'
                                    })" class="text-amber-500 hover:text-amber-700 transition-colors" title="Suspend">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                    @else
                                    <button disabled class="text-amber-200 cursor-not-allowed opacity-70" title="Suspend (No Permission)">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-slate-500">No classes configured for this ferry yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Managing Class -->
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
                 class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-slate-200">
                
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-semibold text-slate-800" id="modal-title">
                        <i class="fas fa-couch text-brand-500 mr-2"></i> {{ $class_id ? 'Edit Class' : 'Add New Class' }}
                    </h3>
                    <button @click="open = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="px-6 py-6 bg-white space-y-4 max-h-[60vh] overflow-y-auto">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Global Travel Class <span class="text-rose-500">*</span></label>
                        <select wire:model="travel_class_id" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 bg-white">
                            <option value="">Select a Global Class...</option>
                            @foreach($globalClasses as $gClass)
                                <option value="{{ $gClass->id }}">{{ $gClass->name }}</option>
                            @endforeach
                        </select>
                        @error('travel_class_id') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        @if($globalClasses->isEmpty())
                            <p class="text-xs text-slate-500 mt-1">No global classes found. <a href="{{ route('admin.settings.travel-classes.index') }}" class="text-brand-500 underline">Manage Classes</a></p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Capacity <span class="text-rose-500">*</span></label>
                        <input type="number" wire:model="capacity" placeholder="e.g. 150" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        @error('capacity') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="pt-2 border-t border-slate-100">
                        <label class="flex items-center gap-2 cursor-pointer mb-2">
                            <input type="checkbox" wire:model.live="is_room_based" class="rounded text-brand-500 focus:ring-brand-500 border-slate-300 shadow-sm h-4 w-4">
                            <span class="text-sm font-semibold text-slate-700">Is this Class Room-Based? (e.g. Royal Class with private rooms)</span>
                        </label>
                    </div>

                    @if($is_room_based)
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
                        <div class="flex justify-between items-center mb-2">
                            <h4 class="text-sm font-bold text-slate-700"><i class="fas fa-bed text-brand-500 mr-1"></i> Rooms Configuration</h4>
                            <button type="button" wire:click="addRoom" class="text-xs bg-white border border-slate-300 px-3 py-1.5 rounded-lg text-slate-600 hover:bg-slate-50 shadow-sm font-semibold">
                                <i class="fas fa-plus mr-1 text-emerald-500"></i> Add Room
                            </button>
                        </div>
                        
                        @forelse($rooms as $index => $room)
                            <div class="flex gap-2 items-start bg-white p-2.5 rounded-lg border border-slate-200 shadow-sm">
                                <div class="flex-1">
                                    <input type="text" wire:model="rooms.{{ $index }}.name" placeholder="Room Name (e.g. Room 1)" class="w-full text-sm rounded-lg border-slate-300 focus:ring-brand-500 focus:border-brand-500">
                                    @error('rooms.'.$index.'.name') <span class="text-[10px] text-rose-500">{{ $message }}</span> @enderror
                                </div>
                                <div class="w-32">
                                    <input type="number" wire:model="rooms.{{ $index }}.capacity" placeholder="Pax" class="w-full text-sm rounded-lg border-slate-300 focus:ring-brand-500 focus:border-brand-500">
                                    @error('rooms.'.$index.'.capacity') <span class="text-[10px] text-rose-500">{{ $message }}</span> @enderror
                                </div>
                                <button type="button" wire:click="removeRoom({{ $index }})" class="mt-1 w-8 h-8 flex items-center justify-center text-rose-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">
                                    <i class="fas fa-trash text-sm"></i>
                                </button>
                            </div>
                        @empty
                            <div class="text-center py-4 text-sm text-slate-500 italic">No rooms added yet. Click "Add Room" to configure private rooms for this class.</div>
                        @endforelse
                    </div>
                    @endif
                </div>

                <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end gap-3">
                    <button type="button" @click="$dispatch('close-modal', 'manage-class-modal')" class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 shadow-sm transition-colors">
                        Close
                    </button>
                    <button type="button" wire:click="saveClass" class="px-5 py-2.5 text-sm font-medium text-white bg-brand-500 hover:bg-brand-600 rounded-xl shadow-sm transition-colors flex items-center gap-2">
                        <i class="fas fa-check"></i> Submit
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reusable Action Modal -->
    <x-admin.action-modal />
</div>
