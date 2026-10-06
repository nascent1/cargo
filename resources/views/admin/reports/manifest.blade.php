<x-layouts.admin>
    <x-slot:title>Passenger Manifest</x-slot:title>
    <x-slot:header>Passenger Manifest</x-slot:header>

    {{-- Breadcrumb & Title --}}
    <div x-data="{ showPdfModal: false, pdfUrl: '' }">
        <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-slate-500 font-medium mb-1">
            <a href="{{ route('admin.reports.index') }}" class="hover:text-blue-600 transition-colors"><i class="fas fa-chart-line mr-1"></i> Reports Hub</a>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <span class="text-slate-800">Passenger Manifest</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-800">Passenger Manifest</h1>
        <p class="text-sm text-slate-500">Official passenger list per voyage for port clearance.</p>
    </div>

    {{-- Filter Form --}}
    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-6 mb-8" x-data="manifestCascade()">
        <form method="GET" action="{{ route('admin.reports.manifest') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
            <input type="hidden" name="run" value="1">
            
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Departure Date <span class="text-rose-500">*</span></label>
                <input type="date" name="departure_date" required x-model="date" @change="fetchRoutes()" class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-xl px-4 py-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Route <span class="text-rose-500">*</span></label>
                <select name="route_id" id="route_id" required x-model="route" x-tomselect class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-xl outline-none transition-all">
                    <option value="">-- Select Route --</option>
                    @if(!empty($filters['route_id']))
                        <option value="{{ $filters['route_id'] }}" selected>{{ $routes->firstWhere('id', $filters['route_id'])?->name }}</option>
                    @endif
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Time <span class="text-rose-500">*</span></label>
                <select name="departure_time" id="departure_time" required x-model="time" x-tomselect class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-xl outline-none transition-all">
                    <option value="">-- Select Time --</option>
                    @if(!empty($filters['departure_time']))
                        <option value="{{ $filters['departure_time'] }}" selected>{{ \Carbon\Carbon::parse($filters['departure_time'])->format('H:i') }}</option>
                    @endif
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Vessel <span class="text-rose-500">*</span></label>
                <select name="ferry_id" id="ferry_id" required x-model="ferry" x-tomselect class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-xl outline-none transition-all">
                    <option value="">-- Select Vessel --</option>
                    @if(!empty($filters['ferry_id']))
                        <option value="{{ $filters['ferry_id'] }}" selected>{{ $ferries->firstWhere('id', $filters['ferry_id'])?->name }}</option>
                    @endif
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Limit</label>
                <select name="limit" class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-xl px-4 py-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all">
                    <option value="all" {{ ($filters['limit'] ?? 'all') == 'all' ? 'selected' : '' }}>All</option>
                    <option value="50" {{ ($filters['limit'] ?? '') == '50' ? 'selected' : '' }}>First 50</option>
                    <option value="100" {{ ($filters['limit'] ?? '') == '100' ? 'selected' : '' }}>First 100</option>
                    <option value="150" {{ ($filters['limit'] ?? '') == '150' ? 'selected' : '' }}>First 150</option>
                    <option value="200" {{ ($filters['limit'] ?? '') == '200' ? 'selected' : '' }}>First 200</option>
                    <option value="300" {{ ($filters['limit'] ?? '') == '300' ? 'selected' : '' }}>First 300</option>
                    <option value="500" {{ ($filters['limit'] ?? '') == '500' ? 'selected' : '' }}>First 500</option>
                </select>
            </div>
            
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Boarding</label>
                <select name="boarding_status" class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-xl px-4 py-2.5 focus:border-blue-500 outline-none transition-all">
                    <option value="">All</option>
                    <option value="boarded" {{ ($filters['boarding_status'] ?? '') == 'boarded' ? 'selected' : '' }}>Boarded</option>
                    <option value="not_boarded" {{ ($filters['boarding_status'] ?? '') == 'not_boarded' ? 'selected' : '' }}>Not Boarded</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Nationality</label>
                <select name="nationality_type" class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-xl px-4 py-2.5 focus:border-blue-500 outline-none transition-all">
                    <option value="">All</option>
                    <option value="resident" {{ ($filters['nationality_type'] ?? '') == 'resident' ? 'selected' : '' }}>Resident</option>
                    <option value="foreigner" {{ ($filters['nationality_type'] ?? '') == 'foreigner' ? 'selected' : '' }}>Foreigner</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-xl transition-colors text-sm flex items-center justify-center gap-2">
                    <i class="fas fa-search"></i> Run
                </button>
                @if(request()->has('run'))
                <button type="submit" name="export" value="excel" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2.5 px-4 rounded-xl transition-colors text-sm flex items-center justify-center gap-2">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
                <button type="button" @click="
                    const form = $el.closest('form');
                    const params = new URLSearchParams(new FormData(form));
                    params.set('export', 'pdf');
                    pdfUrl = form.action + '?' + params.toString();
                    showPdfModal = true;
                " class="flex-1 bg-rose-500 hover:bg-rose-600 text-white font-bold py-2.5 px-4 rounded-xl transition-colors text-sm flex items-center justify-center gap-2">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
                <button type="submit" name="export" value="docx" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-bold py-2.5 px-4 rounded-xl transition-colors text-sm flex items-center justify-center gap-2">
                    <i class="fas fa-file-word"></i> Word
                </button>
                @endif
            </div>
        </form>
    </div>

    {{-- Data Table --}}
    @if(request()->has('run'))
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-8 print:shadow-none print:border-none print:p-0">
            @if(empty($data))
                <div class="p-12 text-center text-slate-500">
                    <i class="fas fa-user-slash text-4xl mb-4 text-slate-300"></i>
                    <p>No confirmed passengers found for this voyage.</p>
                </div>
            @else
                
                {{-- Print Header --}}
                <div class="hidden print:block mb-8 border-b-2 border-slate-800 pb-4">
                    <h2 class="text-3xl font-bold text-center uppercase tracking-widest mb-2">Passenger Manifest</h2>
                    @php
                        $selectedRoute = !empty($filters['route_id']) ? $routes->firstWhere('id', $filters['route_id']) : null;
                        $selectedFerry = !empty($filters['ferry_id']) ? $ferries->firstWhere('id', $filters['ferry_id']) : null;
                        $selectedDate = !empty($filters['departure_date']) ? \Carbon\Carbon::parse($filters['departure_date'])->format('D, d M Y') : 'All Dates';
                    @endphp
                    <div class="flex justify-between text-sm mt-6">
                        <div>
                            <strong>Vessel:</strong> {{ $selectedFerry?->name ?? 'All Vessels' }}<br>
                            <strong>Route:</strong> {{ $selectedRoute?->name ?? 'All Routes' }}
                        </div>
                        <div class="text-right">
                            <strong>Departure:</strong> {{ $selectedDate }}<br>
                            <strong>Printed:</strong> {{ now()->format('d M Y H:i:s') }}
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mb-4 print:hidden">
                    <button onclick="window.print()" class="bg-slate-800 hover:bg-slate-900 text-white text-sm font-bold py-2 px-4 rounded-lg flex items-center gap-2 transition-colors">
                        <i class="fas fa-print"></i> Print Manifest
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse border border-slate-300 print:border-slate-800">
                        <thead>
                            <tr class="bg-slate-100 text-slate-800 text-xs uppercase tracking-wider print:bg-slate-200">
                                <th class="p-3 border border-slate-300 font-bold text-center w-12">S/N</th>
                                <th class="p-3 border border-slate-300 font-bold">Passenger Name</th>
                                <th class="p-3 border border-slate-300 font-bold text-center">Gender</th>
                                <th class="p-3 border border-slate-300 font-bold">Ticket No.</th>
                                <th class="p-3 border border-slate-300 font-bold">Class</th>
                                <th class="p-3 border border-slate-300 font-bold">Nationality</th>
                                <th class="p-3 border border-slate-300 font-bold text-center">Boarded</th>
                                <th class="p-3 border border-slate-300 font-bold text-center">Seat</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            @foreach($data as $row)
                                <tr>
                                    <td class="p-2 border border-slate-300 text-center text-slate-600">{{ $row['sn'] }}</td>
                                    <td class="p-2 border border-slate-300 font-medium text-slate-900">{{ $row['passenger_name'] }}</td>
                                    <td class="p-2 border border-slate-300 text-center">{{ substr(strtoupper($row['gender'] ?? '-'), 0, 1) }}</td>
                                    <td class="p-2 border border-slate-300 font-mono">{{ $row['ticket'] }}</td>
                                    <td class="p-2 border border-slate-300">{{ $row['class'] }}</td>
                                    <td class="p-2 border border-slate-300">{{ ucfirst($row['nationality']) }}</td>
                                    <td class="p-2 border border-slate-300 text-center text-xs">{!! $row['boarded'] ? '<span class="text-green-600 font-bold"><i class="fas fa-check"></i> Yes</span>' : '<span class="text-slate-400">No</span>' !!}</td>
                                    <td class="p-2 border border-slate-300 text-center font-bold">{{ $row['seat'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Print Footer --}}
                <div class="hidden print:flex justify-between mt-16 pt-8 border-t border-slate-400">
                    <div class="text-center w-1/3">
                        <div class="border-b border-slate-600 h-8 mb-2"></div>
                        <span class="text-xs uppercase">Captain's Signature</span>
                    </div>
                    <div class="text-center w-1/3">
                        <div class="border-b border-slate-600 h-8 mb-2"></div>
                        <span class="text-xs uppercase">Port Authority Stamp</span>
                    </div>
                </div>
            @endif
        </div>
    @endif



    <!-- PDF Modal -->
    <div x-show="showPdfModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
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

    @push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.default.min.css" rel="stylesheet">
    <style>
        .ts-wrapper .ts-control { border-radius: 0.75rem; border-color: #e2e8f0; padding: 0.625rem 1rem; font-size: 0.875rem; background-color: #f8fafc; border-width: 1px; box-shadow: none; min-height: 42px; display: flex; align-items: center; }
        .ts-wrapper.focus .ts-control { border-color: #3b82f6; box-shadow: 0 0 0 1px #3b82f6; }
        .ts-dropdown { border-radius: 0.5rem; overflow: hidden; border-color: #e2e8f0; font-size: 0.875rem; }
    </style>
    @endpush

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.directive('tomselect', (el, { expression }, { evaluate, cleanup }) => {
                let isInternalChange = false;
                const ts = new TomSelect(el, {
                    create: false,
                    sortField: { field: 'text', direction: 'asc' },
                    onChange: function(value) {
                        isInternalChange = true;
                        el.dispatchEvent(new Event('input', { bubbles: true }));
                        setTimeout(() => isInternalChange = false, 0);
                    }
                });
                el.tomselect = ts;

                cleanup(() => {
                    if (el.tomselect) {
                        el.tomselect.destroy();
                        delete el.tomselect;
                    }
                });
            });
        });

        function manifestCascade() {
            return {
                date: '{{ $filters['departure_date'] ?? '' }}',
                route: '{{ $filters['route_id'] ?? '' }}',
                time: '{{ $filters['departure_time'] ?? '' }}',
                ferry: '{{ $filters['ferry_id'] ?? '' }}',
                
                init() {
                    // Watchers handle the downstream resets automatically
                    this.$watch('date', () => this.fetchRoutes(true));
                    this.$watch('route', () => this.fetchTimes(true));
                    this.$watch('time', () => this.fetchFerries(true));

                    // Initial load if date is present (for populating options if none exist)
                    if (this.date) {
                        this.fetchRoutes(false).then(() => {
                            if (this.route) {
                                this.fetchTimes(false).then(() => {
                                    if (this.time) {
                                        this.fetchFerries(false);
                                    }
                                });
                            }
                        });
                    }
                },
                
                fetchRoutes(reset = false) {
                    if (reset) {
                        this.route = '';
                        const tsRoute = document.getElementById('route_id')?.tomselect;
                        if (tsRoute) tsRoute.clear(true); // silent clear
                    }
                    const ts = document.getElementById('route_id')?.tomselect;
                    if (!this.date) { 
                        if (ts) { ts.clear(true); ts.clearOptions(); }
                        return Promise.resolve(); 
                    }
                    
                    return fetch(`/admin/reports/manifest/options?fetch=routes&date=${this.date}`)
                        .then(r => r.json())
                        .then(d => { 
                            if (ts) {
                                ts.clearOptions();
                                d.routes.forEach(r => ts.addOption({value: r.id, text: r.name}));
                                if (!reset && this.route) ts.setValue(this.route, true);
                            }
                        });
                },
                
                fetchTimes(reset = false) {
                    if (reset) {
                        this.time = '';
                        const tsTime = document.getElementById('departure_time')?.tomselect;
                        if (tsTime) tsTime.clear(true);
                    }
                    const ts = document.getElementById('departure_time')?.tomselect;
                    if (!this.route) { 
                        if (ts) { ts.clear(true); ts.clearOptions(); }
                        return Promise.resolve(); 
                    }
                    
                    return fetch(`/admin/reports/manifest/options?fetch=times&date=${this.date}&route_id=${this.route}`)
                        .then(r => r.json())
                        .then(d => { 
                            if (ts) {
                                ts.clearOptions();
                                d.times.forEach(t => ts.addOption({value: t, text: t}));
                                if (!reset && this.time) ts.setValue(this.time, true);
                            }
                        });
                },
                
                fetchFerries(reset = false) {
                    if (reset) {
                        this.ferry = '';
                        const tsFerry = document.getElementById('ferry_id')?.tomselect;
                        if (tsFerry) tsFerry.clear(true);
                    }
                    const ts = document.getElementById('ferry_id')?.tomselect;
                    if (!this.time) { 
                        if (ts) { ts.clear(true); ts.clearOptions(); }
                        return Promise.resolve(); 
                    }
                    
                    return fetch(`/admin/reports/manifest/options?fetch=ferries&date=${this.date}&route_id=${this.route}&time=${this.time}`)
                        .then(r => r.json())
                        .then(d => { 
                            if (ts) {
                                ts.clearOptions();
                                d.ferries.forEach(f => ts.addOption({value: f.id, text: f.name}));
                                if (!reset && this.ferry) ts.setValue(this.ferry, true);
                            }
                        });
                }
            }
        }
    </script>
    @endpush
</x-layouts.admin>
