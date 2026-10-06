@props(['filters', 'routes' => [], 'agents' => [], 'bookingUsers' => [], 'systemUsers' => [], 'agentsWithAssociates' => [], 'showStatus' => true, 'showDemographics' => true, 'showViewToggle' => false])

<div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-6 mb-8" 
     x-data="reportFiltersComponent(@js($filters))">
    
    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('reportFiltersComponent', (initialFilters) => ({
            expanded: false, 
            showPdfModal: false, 
            pdfUrl: '',
            activeChips: {},
            selectedFilterToAdd: '',
            hierarchyType: '{{ request('agent_id') ? 'agent' : (request('booked_by_user_id') ? 'system_user' : '') }}',
            hierarchyAgentId: '{{ request('agent_id') ?? '' }}',
            associatesMap: {
                @foreach($agentsWithAssociates ?? [] as $agent)
                    '{{ $agent->id }}': [
                        { id: '{{ $agent->user_id }}', name: 'Main Agent ({!! addslashes($agent->user?->full_name ?? "") !!})' },
                        @foreach($agent->associates as $assoc)
                            { id: '{{ $assoc->user_id }}', name: '{!! addslashes($assoc->user?->full_name ?? $assoc->associate_name) !!}' },
                        @endforeach
                    ],
                @endforeach
            },
            availableFilters: {
                booked_by_user_id: { 
                    label: 'Hierarchy / Agent', 
                    options: [] // Managed via custom template 
                },
                booking_status: { 
                    label: 'Booking Status', 
                    options: [ 
                        {value: 'confirmed', label: 'Confirmed'}, 
                        {value: 'pending_payment', label: 'Pending Payment'}, 
                        {value: 'cancelled', label: 'Cancelled'} 
                    ] 
                },
                nationality_type: { 
                    label: 'Nationality', 
                    options: [ 
                        {value: 'resident', label: 'Resident'}, 
                        {value: 'foreigner', label: 'Foreigner'} 
                    ] 
                }
            },
            allowedKeys: [
                'booked_by_user_id',
                @if($showStatus) 'booking_status', @endif
                @if($showDemographics) 'nationality_type', @endif
            ],
            get availableToAdd() {
                let available = {};
                this.allowedKeys.forEach(k => {
                    if (this.activeChips[k] === undefined) {
                        available[k] = this.availableFilters[k];
                    }
                });
                return available;
            },
            addFilter() {
                if (this.selectedFilterToAdd) {
                    this.activeChips[this.selectedFilterToAdd] = '';
                    this.selectedFilterToAdd = '';
                }
            },
            removeFilter(key) {
                delete this.activeChips[key];
            },
            init() {
                if (initialFilters) {
                    this.allowedKeys.forEach(k => {
                        if (initialFilters[k] !== undefined && initialFilters[k] !== null && initialFilters[k] !== '') {
                            this.activeChips[k] = initialFilters[k];
                        }
                    });
                }
            }
        }));
    });
    </script>
    
    <form method="GET" action="{{ url()->current() }}">
        <input type="hidden" name="run" value="1">
        
        <!-- Hidden inputs removed: we now use :name directly on the selects -->
        
        <div class="flex flex-wrap gap-4 items-end">
            @if($showViewToggle)
            <div class="w-full mb-2">
                <div class="inline-flex bg-slate-100 p-1 rounded-xl shadow-inner border border-slate-200/60">
                    <label class="cursor-pointer">
                        <input type="radio" name="view_mode" value="detailed" class="peer sr-only" {{ ($filters['view_mode'] ?? 'detailed') === 'detailed' ? 'checked' : '' }}>
                        <div class="px-4 py-2 text-sm font-bold rounded-lg transition-all peer-checked:bg-white peer-checked:text-slate-800 peer-checked:shadow-sm peer-checked:border-slate-200 text-slate-500 hover:text-slate-700 border border-transparent">
                            <i class="fas fa-list-ul mr-1"></i> Detailed
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="view_mode" value="summary" class="peer sr-only" {{ ($filters['view_mode'] ?? '') === 'summary' ? 'checked' : '' }}>
                        <div class="px-4 py-2 text-sm font-bold rounded-lg transition-all peer-checked:bg-white peer-checked:text-slate-800 peer-checked:shadow-sm peer-checked:border-slate-200 text-slate-500 hover:text-slate-700 border border-transparent">
                            <i class="fas fa-chart-bar mr-1"></i> Summary
                        </div>
                    </label>
                </div>
            </div>
            @endif

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Date Target</label>
                <select name="date_target" class="bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-xl px-4 py-2 focus:border-blue-500 outline-none transition-all">
                    <option value="created_at" {{ ($filters['date_target'] ?? '') == 'created_at' ? 'selected' : '' }}>Booking Date</option>
                    <option value="departure_date" {{ ($filters['date_target'] ?? '') == 'departure_date' ? 'selected' : '' }}>Departure Date</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Date Range</label>
                <div class="flex items-center space-x-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? now()->toDateString() }}" class="bg-transparent text-slate-800 text-sm outline-none w-32">
                    <span class="text-slate-400 text-xs">to</span>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? now()->toDateString() }}" class="bg-transparent text-slate-800 text-sm outline-none w-32">
                </div>
            </div>
            
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Route</label>
                <select name="route_id" class="bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-xl px-4 py-2 focus:border-blue-500 outline-none transition-all min-w-[150px]">
                    <option value="">All Routes</option>
                    @foreach($routes as $route)
                        <option value="{{ $route->id }}" {{ ($filters['route_id'] ?? '') == $route->id ? 'selected' : '' }}>
                            {{ $route->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            {{ $slot ?? '' }}

            <!-- Add Filter Dropdown -->
            <div class="relative flex-1" x-show="Object.keys(availableToAdd).length > 0">
                <div class="flex items-center">
                    <select x-model="selectedFilterToAdd" @change="addFilter" class="bg-blue-50 border border-blue-200 text-blue-700 font-bold rounded-xl px-4 py-2 text-sm outline-none appearance-none pr-8 cursor-pointer hover:bg-blue-100 transition-colors">
                        <option value="" disabled selected>+ Add Filter</option>
                        <template x-for="(config, key) in availableToAdd" :key="key">
                            <option :value="key" x-text="config.label"></option>
                        </template>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-blue-600">
                        <i class="fas fa-plus text-xs"></i>
                    </div>
                </div>
            </div>
            
            <div class="flex gap-2 ml-auto">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-5 rounded-xl transition-colors text-sm flex items-center justify-center gap-2 shadow-sm shadow-blue-200">
                    <i class="fas fa-play text-xs"></i> Run Report
                </button>
                @if(request()->has('run'))
                <button type="submit" name="export" value="excel" class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2.5 px-4 rounded-xl transition-colors text-sm flex items-center justify-center gap-2">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
                <button type="button" @click="
                    const form = $el.closest('form');
                    const params = new URLSearchParams(new FormData(form));
                    params.set('export', 'pdf');
                    pdfUrl = form.action + '?' + params.toString();
                    showPdfModal = true;
                " class="bg-rose-500 hover:bg-rose-600 text-white font-bold py-2.5 px-4 rounded-xl transition-colors text-sm flex items-center justify-center gap-2">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
                <button type="submit" name="export" value="docx" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2.5 px-4 rounded-xl transition-colors text-sm flex items-center justify-center gap-2">
                    <i class="fas fa-file-word"></i> Word
                </button>
                @endif
            </div>
        </div>

        <!-- Filter Chips Row -->
        <div x-show="Object.keys(activeChips).length > 0" class="flex flex-wrap gap-3 mt-4 pt-4 border-t border-slate-100" style="display: none;">
            <template x-for="(val, key) in activeChips" :key="key">
                <div class="flex items-center bg-slate-100 border border-slate-200 rounded-lg px-1 py-1 group hover:border-slate-300 transition-colors">
                    <span class="text-xs font-bold text-slate-600 px-2" x-text="availableFilters[key].label + ':'"></span>
                    
                    <!-- Standard Select -->
                    <template x-if="key !== 'booked_by_user_id'">
                        <select x-model="activeChips[key]" :name="key" class="bg-white border border-slate-200 text-slate-800 rounded text-sm px-2 py-1 outline-none min-w-[120px] focus:ring-1 focus:ring-blue-500">
                            <option value="">All</option>
                            <template x-for="opt in availableFilters[key].options" :key="opt.value">
                                <option :value="opt.value" x-text="opt.label"></option>
                            </template>
                        </select>
                    </template>

                    <!-- Custom Hierarchy Cascading Select -->
                    <template x-if="key === 'booked_by_user_id'">
                        <div class="flex items-center gap-1">
                            <select x-model="hierarchyType" @change="activeChips[key] = ''; hierarchyAgentId = '';" class="bg-white border border-slate-200 text-slate-800 rounded text-sm px-2 py-1 outline-none focus:ring-1 focus:ring-blue-500">
                                <option value="">All</option>
                                <option value="system_user">System User</option>
                                <option value="agent">Agent</option>
                            </select>

                            <select x-show="hierarchyType === 'system_user'" x-model="activeChips[key]" :name="hierarchyType === 'system_user' ? 'booked_by_user_id' : ''" class="bg-white border border-slate-200 text-slate-800 rounded text-sm px-2 py-1 outline-none focus:ring-1 focus:ring-blue-500">
                                <option value="">All System Users</option>
                                @foreach($systemUsers ?? [] as $su)
                                    <option value="{{ $su->user_id }}">{!! addslashes($su->full_name) !!} ({{ $su->position }})</option>
                                @endforeach
                            </select>

                            <select x-show="hierarchyType === 'agent'" x-model="hierarchyAgentId" :name="hierarchyType === 'agent' ? 'agent_id' : ''" @change="activeChips[key] = ''" class="bg-white border border-slate-200 text-slate-800 rounded text-sm px-2 py-1 outline-none focus:ring-1 focus:ring-blue-500">
                                <option value="">All Agents</option>
                                @foreach($agentsWithAssociates ?? [] as $agent)
                                    <option value="{{ $agent->id }}">{!! addslashes($agent->agency_name) !!}</option>
                                @endforeach
                            </select>

                            <select x-show="hierarchyType === 'agent' && hierarchyAgentId" x-model="activeChips[key]" :name="(hierarchyType === 'agent' && hierarchyAgentId) ? 'booked_by_user_id' : ''" class="bg-white border border-slate-200 text-slate-800 rounded text-sm px-2 py-1 outline-none focus:ring-1 focus:ring-blue-500">
                                <option value="">All Associates</option>
                                <template x-for="assoc in (associatesMap[hierarchyAgentId] || [])" :key="assoc.id">
                                    <option :value="assoc.id" x-text="assoc.name"></option>
                                </template>
                            </select>
                        </div>
                    </template>

                    <button type="button" @click="removeFilter(key)" class="ml-2 w-6 h-6 flex items-center justify-center rounded-md text-slate-400 hover:bg-rose-100 hover:text-rose-600 transition-colors">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            </template>
        </div>
        
    </form>
    
    <!-- PDF Modal -->
    <div x-show="showPdfModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showPdfModal" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 bg-slate-900/75 transition-opacity" 
                 @click="showPdfModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showPdfModal" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-6xl h-[85vh]">
                
                <div class="w-full h-full flex flex-col">
                    <div class="bg-white px-4 py-3 border-b border-slate-200 flex justify-between items-center">
                        <h3 class="text-lg leading-6 font-bold text-slate-900">Report PDF Viewer</h3>
                        <button @click="showPdfModal = false" class="text-slate-400 hover:text-slate-500 transition-colors bg-slate-100 hover:bg-slate-200 rounded-full w-8 h-8 flex items-center justify-center">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="flex-1 w-full bg-slate-50 relative">
                        <div class="absolute inset-0 flex items-center justify-center text-slate-400" x-show="pdfUrl">
                            <i class="fas fa-circle-notch fa-spin text-3xl"></i>
                        </div>
                        <iframe :src="pdfUrl" class="absolute inset-0 w-full h-full border-none z-10" onload="this.style.backgroundColor='white';"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
