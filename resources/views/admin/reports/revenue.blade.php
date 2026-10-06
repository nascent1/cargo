<x-layouts.admin>
    <x-slot:title>Revenue Report</x-slot:title>
    <x-slot:header>Revenue Report</x-slot:header>

    {{-- Breadcrumb & Title --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-slate-500 font-medium mb-1">
            <a href="{{ route('admin.reports.index') }}" class="hover:text-blue-600 transition-colors"><i class="fas fa-chart-line mr-1"></i> Reports Hub</a>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <span class="text-slate-800">Revenue Report</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-800">Revenue Report</h1>
        <p class="text-sm text-slate-500">Breakdown of revenue by route, ferry, class, or agent.</p>
    </div>

    {{-- Filter Form --}}
    <x-report-filters :filters="$filters" :routes="$routes" :agents="$agents" :system-users="$systemUsers ?? []" :agents-with-associates="$agentsWithAssociates ?? []">
        {{-- Custom Slot for Group By --}}
        <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Group By</label>
            <select name="group_by" class="bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-xl px-4 py-2 focus:border-blue-500 outline-none transition-all min-w-[150px]">
                <option value="route" {{ ($filters['group_by'] ?? '') == 'route' ? 'selected' : '' }}>Route</option>
                <option value="ferry" {{ ($filters['group_by'] ?? '') == 'ferry' ? 'selected' : '' }}>Ferry</option>
                <option value="class" {{ ($filters['group_by'] ?? '') == 'class' ? 'selected' : '' }}>Class</option>
                <option value="agent" {{ ($filters['group_by'] ?? '') == 'agent' ? 'selected' : '' }}>Agent</option>
            </select>
        </div>
    </x-report-filters>

    {{-- Data Table --}}
    @if(request()->has('run'))
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl print:shadow-none print:border-none print:p-0">
            @if(empty($data))
                <div class="p-12 text-center text-slate-500">
                    <i class="fas fa-folder-open text-4xl mb-4 text-slate-300"></i>
                    <p>No revenue data found for the selected criteria.</p>
                </div>
            @else
                
                {{-- Print Header --}}
                <div class="hidden print:block mb-8 border-b-2 border-slate-800 pb-4">
                    <h2 class="text-3xl font-bold text-center uppercase tracking-widest mb-2">Revenue Report</h2>
                    <div class="flex justify-between text-sm mt-6">
                        <div>
                            <strong>Date Range:</strong> {{ request('date_from', 'Start') }} to {{ request('date_to', 'End') }}
                        </div>
                        <div class="text-right">
                            <strong>Printed:</strong> {{ now()->format('d M Y H:i:s') }}
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mb-4 print:hidden px-8 pt-8">
                    <button onclick="window.print()" class="bg-slate-800 hover:bg-slate-900 text-white text-sm font-bold py-2 px-4 rounded-lg flex items-center gap-2 transition-colors">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>

                <div class="overflow-x-auto p-4">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                                <th class="p-4 font-semibold border-b border-slate-200">
                                    {{ ucfirst($filters['group_by'] ?? 'Route') }}
                                </th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Adults</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Children</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Total Tickets</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Total Revenue (TZS)</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Total Revenue (USD)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @php 
                                $totAdults = 0;
                                $totChildren = 0;
                                $totTix = 0; 
                                $totFareTzs = 0; 
                                $totFareUsd = 0; 
                            @endphp
                            @foreach($data as $row)
                                @php 
                                    $totAdults += $row['adults'];
                                    $totChildren += $row['children'];
                                    $totTix += $row['total_tickets']; 
                                    $totFareTzs += $row['total_revenue_tzs']; 
                                    $totFareUsd += $row['total_revenue_usd']; 
                                @endphp
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="p-4 text-slate-800 font-medium">{{ $row['group_label'] ?? 'N/A' }}</td>
                                    <td class="p-4 font-mono text-right text-slate-800">{{ number_format($row['adults']) }}</td>
                                    <td class="p-4 font-mono text-right text-slate-800">{{ number_format($row['children']) }}</td>
                                    <td class="p-4 font-mono text-right text-slate-800">{{ number_format($row['total_tickets']) }}</td>
                                    <td class="p-4 font-mono text-right font-bold text-emerald-600">{{ number_format($row['total_revenue_tzs']) }}</td>
                                    <td class="p-4 font-mono text-right font-bold text-blue-600">{{ number_format($row['total_revenue_usd'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-900 text-white">
                            <tr>
                                <td class="p-4 font-black uppercase tracking-wider text-xs">Grand Total</td>
                                <td class="p-4 font-mono font-black text-right">{{ number_format($totAdults) }}</td>
                                <td class="p-4 font-mono font-black text-right">{{ number_format($totChildren) }}</td>
                                <td class="p-4 font-mono font-black text-right">{{ number_format($totTix) }}</td>
                                <td class="p-4 font-mono font-black text-right text-emerald-400">TZS {{ number_format($totFareTzs) }}</td>
                                <td class="p-4 font-mono font-black text-right text-blue-400">USD {{ number_format($totFareUsd, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    @endif
</x-layouts.admin>
