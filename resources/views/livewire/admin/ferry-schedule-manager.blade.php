<div>
    <div class="mb-8">
        @if (session()->has('success'))
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start gap-3">
                <i class="fas fa-check-circle text-emerald-500 mt-0.5"></i>
                <div class="flex-1">
                    <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif
        
        <!-- Tabs Header -->
        <div class="flex items-center gap-6 border-b border-slate-200 mb-6 px-2">
            <button wire:click="setTab('current')" 
                class="px-4 py-3 font-semibold text-sm transition-colors relative {{ $activeTab === 'current' ? 'text-brand-600' : 'text-slate-500 hover:text-slate-800' }}">
                Current
                @if($activeTab === 'current')
                    <span class="absolute bottom-0 left-0 w-full h-0.5 bg-brand-500 rounded-t"></span>
                @endif
            </button>
            <button wire:click="setTab('available')" 
                class="px-4 py-3 font-semibold text-sm transition-colors relative {{ $activeTab === 'available' ? 'text-brand-600' : 'text-slate-500 hover:text-slate-800' }}">
                Available
                @if($activeTab === 'available')
                    <span class="absolute bottom-0 left-0 w-full h-0.5 bg-brand-500 rounded-t"></span>
                @endif
            </button>
            <button wire:click="setTab('history')" 
                class="px-4 py-3 font-semibold text-sm transition-colors relative {{ $activeTab === 'history' ? 'text-brand-600' : 'text-slate-500 hover:text-slate-800' }}">
                History
                @if($activeTab === 'history')
                    <span class="absolute bottom-0 left-0 w-full h-0.5 bg-brand-500 rounded-t"></span>
                @endif
            </button>
        </div>
        
        <!-- Current Tab -->
        @if($activeTab === 'current')
            <div class="mb-4 flex justify-between items-center px-2">
                <div class="text-sm text-slate-500">Showing schedules that are active or suspended (not expired).</div>
                <div class="w-48">
                    <select wire:model.live="status_filter" class="w-full text-sm rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">All Statuses</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Suspended Only</option>
                    </select>
                </div>
            </div>
            <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-sm bg-white">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 w-12"><input type="checkbox" class="rounded border-slate-300 text-brand-500 focus:ring-brand-500"></th>
                            <th class="px-6 py-4">Name</th>
                            <th class="px-6 py-4">Time</th>
                            <th class="px-6 py-4">Start</th>
                            <th class="px-6 py-4">End</th>
                            <th class="px-6 py-4">Frequency Pattern</th>
                            <th class="px-6 py-4">Active</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($currentSchedules as $schedule)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4"><input type="checkbox" checked class="rounded border-slate-300 text-brand-500 focus:ring-brand-500"></td>
                                <td class="px-6 py-4 font-semibold text-slate-800">{{ $schedule->trip->route->name ?? 'Unknown Route' }}</td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ \Carbon\Carbon::parse($schedule->trip->departure_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->trip->arrival_time)->format('H:i') }}
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ \Carbon\Carbon::parse($schedule->start_date)->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ \Carbon\Carbon::parse($schedule->end_date)->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $schedule->frequency_pattern ?: 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-slate-700 capitalize">{{ $schedule->status }}</span>
                                </td>
                                <td class="px-6 py-4 text-right flex justify-end gap-1.5">
                                    @php 
                                        $canUpdateSchedule = auth()->user()->hasPermission('schedule:update');
                                        $canDeleteSchedule = auth()->user()->hasPermission('schedule:delete');
                                        $isActive = strtolower($schedule->status) === 'active';
                                    @endphp
                                    @if($canUpdateSchedule && $isActive)
                                            <button wire:click="editSchedule({{ $schedule->id }})" class="h-8 w-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors" title="Edit">
                                                <i class="fas fa-edit text-xs"></i>
                                            </button>
                                        @else
                                            <button disabled class="h-8 w-8 rounded-xl bg-slate-50 text-slate-300 cursor-not-allowed flex items-center justify-center" title="Edit ({{ !$isActive ? 'Schedule is suspended' : 'No Permission' }})">
                                                <i class="fas fa-edit text-xs"></i>
                                            </button>
                                        @endif
                                        
                                        @if($canDeleteSchedule && $isActive)
                                            <button @click="$dispatch('open-action-modal', {
                                                id: {{ $schedule->id }},
                                                action: 'suspendSchedule',
                                                title: 'Suspend Schedule',
                                                message: 'Are you sure you want to suspend this schedule?',
                                                buttonText: 'Suspend',
                                                buttonColor: 'amber'
                                            })" class="h-8 w-8 rounded-xl bg-amber-100 hover:bg-amber-200 text-amber-600 flex items-center justify-center transition-colors" title="Suspend">
                                                <i class="fas fa-ban text-xs"></i>
                                            </button>
                                        @else
                                            <button disabled class="h-8 w-8 rounded-xl bg-amber-50 text-amber-300 opacity-70 cursor-not-allowed flex items-center justify-center" title="Suspend{{ !$isActive ? ' (Already suspended)' : ' (No Permission)' }}">
                                                <i class="fas fa-ban text-xs"></i>
                                            </button>
                                        @endif

                                        @if($canDeleteSchedule && !$isActive)
                                            <button @click="$dispatch('open-action-modal', {
                                                id: {{ $schedule->id }},
                                                action: 'activateSchedule',
                                                title: 'Activate Schedule',
                                                message: 'Are you sure you want to activate this schedule?',
                                                buttonText: 'Activate',
                                                buttonColor: 'emerald'
                                            })" class="h-8 w-8 rounded-xl bg-emerald-100 hover:bg-emerald-200 text-emerald-600 flex items-center justify-center transition-colors" title="Activate">
                                                <i class="fas fa-check text-xs"></i>
                                            </button>
                                        @else
                                            <button disabled class="h-8 w-8 rounded-xl bg-emerald-50 text-emerald-300 opacity-70 cursor-not-allowed flex items-center justify-center" title="Activate{{ $isActive ? ' (Already active)' : ' (No Permission)' }}">
                                                <i class="fas fa-check text-xs"></i>
                                            </button>
                                        @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                    No current schedules.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Available Tab -->
        @if($activeTab === 'available')
            <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-sm bg-white">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">Route Name</th>
                            <th class="px-6 py-4">Time</th>
                            <th class="px-6 py-4">Vessel Type</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($availableTrips as $trip)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 font-semibold text-slate-800">{{ $trip->route->name ?? 'Unknown Route' }}</td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ \Carbon\Carbon::parse($trip->departure_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($trip->arrival_time)->format('H:i') }}
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $trip->route->type ?? 'Fast Ferry' }}</td>
                                <td class="px-6 py-4 text-right">
                                    @php $canCreateSchedule = auth()->user()->hasPermission('schedule:create'); @endphp
                                    @if($canCreateSchedule)
                                    <button wire:click="$set('trip_id', {{ $trip->id }}); $set('isModalOpen', true);" class="px-3 py-1.5 text-xs font-semibold rounded bg-emerald-500 hover:bg-emerald-600 text-white transition-colors">
                                        <i class="fas fa-plus mr-1"></i> Add Schedule
                                    </button>
                                    @else
                                    <button disabled class="px-3 py-1.5 text-xs font-semibold rounded bg-slate-200 text-slate-400 cursor-not-allowed" title="Add Schedule (No Permission)">
                                        <i class="fas fa-plus mr-1"></i> Add Schedule
                                    </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                                    No available routes left to assign.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        <!-- History Tab -->
        @if($activeTab === 'history')
            <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-sm bg-white">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">Name</th>
                            <th class="px-6 py-4">Time</th>
                            <th class="px-6 py-4">Action</th>
                            <th class="px-6 py-4">Start</th>
                            <th class="px-6 py-4">End</th>
                            <th class="px-6 py-4">Maker</th>
                            <th class="px-6 py-4">Checker</th>
                            <th class="px-6 py-4 text-right">Action Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($historyActions as $action)
                            @php
                                $tripId = $action->payload_after['trip_id'] ?? $action->payload_before['trip_id'] ?? null;
                                $trip = $tripId ? ($historyTrips[$tripId] ?? null) : null;
                                $startDate = $action->payload_after['start_date'] ?? $action->payload_before['start_date'] ?? null;
                                $endDate = $action->payload_after['end_date'] ?? $action->payload_before['end_date'] ?? null;
                                
                                $actionName = 'Unknown';
                                $actionColor = 'slate';
                                if ($action->operation_type === 'schedule_create') {
                                    $actionName = 'Created';
                                    $actionColor = 'emerald';
                                } elseif ($action->operation_type === 'schedule_update') {
                                    $statusAfter = $action->payload_after['status'] ?? null;
                                    $statusBefore = $action->payload_before['status'] ?? null;
                                    
                                    if ($statusAfter && $statusBefore && $statusAfter !== $statusBefore) {
                                        $actionName = $statusAfter === 'inactive' ? 'Suspended' : 'Activated';
                                        $actionColor = $statusAfter === 'inactive' ? 'amber' : 'emerald';
                                    } else {
                                        $actionName = 'Updated';
                                        $actionColor = 'blue';
                                    }
                                } elseif ($action->operation_type === 'schedule_status_update') {
                                    $status = $action->payload_after['status'] ?? '';
                                    $actionName = $status === 'inactive' ? 'Suspended' : 'Activated';
                                    $actionColor = $status === 'inactive' ? 'amber' : 'emerald';
                                }
                            @endphp
                            <tr class="hover:bg-slate-50 transition-colors opacity-80">
                                <td class="px-6 py-4 font-semibold text-slate-800">{{ $trip?->route?->name ?? 'Unknown Route' }}</td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $trip ? \Carbon\Carbon::parse($trip->departure_time)->format('H:i') . ' - ' . \Carbon\Carbon::parse($trip->arrival_time)->format('H:i') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xs font-semibold px-2 py-1 bg-{{ $actionColor }}-50 text-{{ $actionColor }}-600 rounded uppercase tracking-wider">{{ $actionName }}</span>
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $startDate ? \Carbon\Carbon::parse($startDate)->format('d/m/Y') : 'N/A' }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $endDate ? \Carbon\Carbon::parse($endDate)->format('d/m/Y') : 'N/A' }}</td>
                                <td class="px-6 py-4">
                                    <span class="text-xs font-semibold px-2 py-1 bg-slate-100 text-slate-600 rounded">{{ $action->maker?->full_name ?? 'System' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($action->checker)
                                        <span class="text-xs font-semibold px-2 py-1 bg-brand-50 text-brand-600 rounded">{{ $action->checker->full_name }}</span>
                                    @else
                                        <span class="text-xs text-slate-400 italic">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-slate-500 text-xs">
                                    {{ $action->resolved_at ? \Carbon\Carbon::parse($action->resolved_at)->format('d/m/Y H:i') : 'N/A' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                    No historical schedules.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Modal for Managing Schedule (Assign/Edit) -->
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
                        <i class="fas fa-calendar-plus text-brand-500 mr-2"></i> {{ $schedule_id ? 'Edit Schedule' : 'Assign New Schedule' }}
                    </h3>
                    <button @click="open = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="px-6 py-6 bg-white space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Select Trip <span class="text-rose-500">*</span></label>
                        <select wire:model="trip_id" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">-- Choose a Trip --</option>
                            @foreach($trips as $trip)
                                <option value="{{ $trip->id }}">{{ $trip->route->name ?? 'Unknown Route' }} ({{ \Carbon\Carbon::parse($trip->departure_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($trip->arrival_time)->format('H:i') }})</option>
                            @endforeach
                        </select>
                        @error('trip_id') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Start Date <span class="text-rose-500">*</span></label>
                            <input type="date" wire:model="start_date" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @error('start_date') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">End Date <span class="text-rose-500">*</span></label>
                            <input type="date" wire:model="end_date" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @error('end_date') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-3">Frequency Pattern (Operating Days)</label>
                        <div class="flex flex-wrap gap-3">
                            @php
                                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                            @endphp
                            @foreach($days as $day)
                                <label class="flex items-center space-x-2 cursor-pointer bg-slate-50 border border-slate-200 px-4 py-2.5 rounded-xl hover:bg-slate-100 transition-colors">
                                    <input type="checkbox" wire:model="frequency_pattern" value="{{ $day }}" class="rounded text-brand-600 focus:ring-brand-500 w-4 h-4 border-slate-300">
                                    <span class="text-sm font-medium text-slate-700">{{ $day }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('frequency_pattern') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end gap-3">
                    <button type="button" @click="open = false" class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 shadow-sm transition-colors">
                        Close
                    </button>
                    <button type="button" wire:click="saveSchedule" class="px-5 py-2.5 text-sm font-medium text-white bg-brand-500 hover:bg-brand-600 rounded-xl shadow-sm transition-colors flex items-center gap-2">
                        <i class="fas fa-check"></i> Submit
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reusable Action Modal -->
    <x-admin.action-modal />
</div>
