<x-layouts.admin>
    <x-slot:title>Route Profitability</x-slot:title>
    <x-slot:header>Route Profitability</x-slot:header>

    {{-- Breadcrumb & Title --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-slate-500 font-medium mb-1">
            <a href="{{ route('admin.reports.index') }}" class="hover:text-blue-600 transition-colors"><i class="fas fa-chart-line mr-1"></i> Reports Hub</a>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <span class="text-slate-800">Route Profitability</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-800">Route Profitability Report</h1>
        <p class="text-sm text-slate-500">Revenue per seat, load factors, and profitability ranking across all routes.</p>
    </div>

    {{-- Filter Form --}}
    <x-report-filters :filters="$filters" :routes="$routes" :showStatus="false" :showDemographics="false" />

    {{-- Data --}}
    @if(request()->has('run'))
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl print:shadow-none print:border-none print:p-0">
            @if(empty($data))
                <div class="p-12 text-center text-slate-500">
                    <i class="fas fa-route text-4xl mb-4 text-slate-300"></i>
                    <p>No profitability data found for the selected criteria.</p>
                </div>
            @else
                {{-- Print Header --}}
                <div class="hidden print:block mb-8 border-b-2 border-slate-800 pb-4">
                    <h2 class="text-3xl font-bold text-center uppercase tracking-widest mb-2">Route Profitability Report</h2>
                    <div class="flex justify-between text-sm mt-6">
                        <div><strong>Date Range:</strong> {{ request('date_from', 'Start') }} to {{ request('date_to', 'End') }}</div>
                        <div class="text-right"><strong>Printed:</strong> {{ now()->format('d M Y H:i:s') }}</div>
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
                                <th class="p-4 font-semibold border-b border-slate-200">#</th>
                                <th class="p-4 font-semibold border-b border-slate-200">Route</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Voyages</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Capacity</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Sold</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Load Factor</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Revenue (TZS)</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Rev/Seat</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Rev/Voyage</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-center">Score</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-center">Rating</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @foreach($data as $i => $row)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="p-4 text-slate-400 font-bold">{{ $i + 1 }}</td>
                                    <td class="p-4 text-slate-800 font-bold">
                                        @if($i === 0)
                                            <i class="fas fa-trophy text-amber-500 mr-1"></i>
                                        @endif
                                        {{ $row['route'] }}
                                    </td>
                                    <td class="p-4 font-mono text-right text-slate-600">{{ number_format($row['voyage_count']) }}</td>
                                    <td class="p-4 font-mono text-right text-slate-600">{{ number_format($row['total_capacity']) }}</td>
                                    <td class="p-4 font-mono text-right text-slate-800 font-bold">{{ number_format($row['total_sold']) }}</td>
                                    <td class="p-4 text-right">
                                        @php $lf = $row['load_factor']; @endphp
                                        <div class="flex items-center justify-end gap-2">
                                            <div class="w-16 bg-slate-200 rounded-full h-2">
                                                <div class="{{ $lf >= 70 ? 'bg-emerald-500' : ($lf >= 40 ? 'bg-amber-500' : 'bg-rose-500') }} h-2 rounded-full" style="width: {{ min($lf, 100) }}%"></div>
                                            </div>
                                            <span class="font-mono font-bold {{ $lf >= 70 ? 'text-emerald-600' : ($lf >= 40 ? 'text-amber-600' : 'text-rose-600') }}">{{ $lf }}%</span>
                                        </div>
                                    </td>
                                    <td class="p-4 font-mono text-right font-bold text-emerald-600">{{ number_format($row['revenue_tzs']) }}</td>
                                    <td class="p-4 font-mono text-right text-slate-700">{{ number_format($row['revenue_per_seat']) }}</td>
                                    <td class="p-4 font-mono text-right text-slate-700">{{ number_format($row['revenue_per_voyage']) }}</td>
                                    <td class="p-4 text-center font-mono font-black text-lg text-slate-800">{{ $row['profitability_score'] }}</td>
                                    <td class="p-4 text-center">
                                        @if($row['rating'] === 'Excellent')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                                <i class="fas fa-star mr-1"></i> Excellent
                                            </span>
                                        @elseif($row['rating'] === 'Good')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">Good</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700">
                                                <i class="fas fa-exclamation-triangle mr-1"></i> Underperforming
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</x-layouts.admin>
