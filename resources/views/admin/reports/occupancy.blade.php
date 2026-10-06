<x-layouts.admin>
    <x-slot:title>Ferry Utilization Report</x-slot:title>
    <x-slot:header>Vessel Occupancy Report</x-slot:header>

    {{-- Breadcrumb & Title --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 font-medium mb-1">
                <a href="{{ route('admin.reports.index') }}" class="hover:text-blue-600 transition-colors"><i class="fas fa-chart-line mr-1"></i> Reports Hub</a>
                <i class="fas fa-chevron-right text-[10px]"></i>
                <span class="text-slate-800">Ferry Utilization Report</span>
            </div>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Load Factors & Occupancy</h2>
        </div>
        @if(request()->has('run'))
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-blue-500/30 transition-all flex items-center gap-2">
                <i class="fas fa-download"></i> Export CSV
            </a>
        @endif
    </div>

    {{-- ═══ FILTER PANEL ═══ --}}
    <div class="bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm mb-8 relative z-20">
        <form action="{{ route('admin.reports.occupancy') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            <input type="hidden" name="run" value="1">
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">From Date</label>
                <div class="relative">
                    <i class="fas fa-calendar absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                </div>
            </div>
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">To Date</label>
                <div class="relative">
                    <i class="fas fa-calendar absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Route</label>
                <select name="route_id" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all appearance-none">
                    <option value="">All Routes</option>
                    @foreach($routes as $route)
                        <option value="{{ $route->id }}" @selected(($filters['route_id'] ?? '') == $route->id)>{{ $route->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-blue-500/30 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-filter"></i> Run
                </button>
                <a href="{{ route('admin.reports.occupancy') }}" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-bold transition-colors flex items-center justify-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- ═══ REPORT RESULTS ═══ --}}
    @if(request()->has('run'))
        @if(empty($data))
            <div class="bg-white rounded-3xl p-12 border border-slate-200/60 shadow-sm text-center">
                <div class="h-20 w-20 mx-auto bg-slate-50 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-search text-3xl text-slate-400"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800">No data found</h3>
                <p class="text-sm text-slate-500 mt-1">Try adjusting your filters to see results.</p>
            </div>
        @else
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                <th class="py-4 px-5 text-left">Date</th>
                                <th class="py-4 px-5 text-left">Route</th>
                                <th class="py-4 px-5 text-left">Ferry</th>
                                <th class="py-4 px-5 text-center">Status</th>
                                <th class="py-4 px-5 text-center">Capacity</th>
                                <th class="py-4 px-5 text-center">Sold</th>
                                <th class="py-4 px-5 text-right">Load Factor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($data as $row)
                                <tr class="hover:bg-blue-50/50 transition-colors">
                                    <td class="py-4 px-5 font-bold text-slate-800">{{ $row['date'] }}</td>
                                    <td class="py-4 px-5 text-slate-600">{{ $row['route'] }}</td>
                                    <td class="py-4 px-5 font-medium">{{ $row['ferry'] }}</td>
                                    <td class="py-4 px-5 text-center">
                                        <span class="px-2.5 py-1 rounded-xl text-[10px] font-bold uppercase {{ match($row['status']) { 'completed','arrived' => 'bg-emerald-100 text-emerald-700', 'departed' => 'bg-blue-100 text-blue-700', 'cancelled' => 'bg-rose-100 text-rose-700', default => 'bg-amber-100 text-amber-700' } }}">
                                            {{ $row['status'] }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-5 text-center font-mono">{{ $row['capacity'] }}</td>
                                    <td class="py-4 px-5 text-center font-mono font-bold">{{ $row['sold'] }}</td>
                                    <td class="py-4 px-5 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <span class="font-black {{ $row['load_factor'] > 80 ? 'text-emerald-600' : ($row['load_factor'] > 50 ? 'text-amber-600' : 'text-rose-600') }}">
                                                {{ $row['load_factor'] }}%
                                            </span>
                                            <div class="w-16 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                <div class="h-full {{ $row['load_factor'] > 80 ? 'bg-emerald-500' : ($row['load_factor'] > 50 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ min(100, $row['load_factor']) }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @else
        <div class="bg-white rounded-3xl p-12 border border-slate-200/60 shadow-sm text-center border-dashed">
            <div class="h-20 w-20 mx-auto bg-blue-50 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-hand-pointer text-3xl text-blue-400"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800">Select filters to begin</h3>
            <p class="text-sm text-slate-500 mt-1">Choose your parameters and click Run to generate the report.</p>
        </div>
    @endif
</x-layouts.admin>
