<x-layouts.admin>
    <x-slot:title>Cancellation Trends</x-slot:title>
    <x-slot:header>Cancellation Trends</x-slot:header>

    {{-- Breadcrumb & Title --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-slate-500 font-medium mb-1">
            <a href="{{ route('admin.reports.index') }}" class="hover:text-blue-600 transition-colors"><i class="fas fa-chart-line mr-1"></i> Reports Hub</a>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <span class="text-slate-800">Cancellation Trends</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-800">Cancellation Trends</h1>
        <p class="text-sm text-slate-500">Daily cancellation patterns, penalty recovery rates, and route-level breakdown.</p>
    </div>

    {{-- Filter Form --}}
    <x-report-filters :filters="$filters" :routes="$routes" :agents="$agents" :system-users="$systemUsers ?? []" :agents-with-associates="$agentsWithAssociates ?? []" :showStatus="false" :showDemographics="false" />

    {{-- Data --}}
    @if(request()->has('run'))
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl print:shadow-none print:border-none print:p-0">
            @if(empty($data) || empty($data['daily']))
                <div class="p-12 text-center text-slate-500">
                    <i class="fas fa-ban text-4xl mb-4 text-slate-300"></i>
                    <p>No cancellation data found for the selected criteria.</p>
                </div>
            @else
                {{-- Print Header --}}
                <div class="hidden print:block mb-8 border-b-2 border-slate-800 pb-4">
                    <h2 class="text-3xl font-bold text-center uppercase tracking-widest mb-2">Cancellation Trends</h2>
                    <div class="flex justify-between text-sm mt-6">
                        <div><strong>Date Range:</strong> {{ request('date_from', 'Start') }} to {{ request('date_to', 'End') }}</div>
                        <div class="text-right"><strong>Printed:</strong> {{ now()->format('d M Y H:i:s') }}</div>
                    </div>
                </div>

                @php $summary = $data['summary']; @endphp

                {{-- Summary Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 px-8 pt-8 print:hidden">
                    <div class="bg-gradient-to-br from-rose-500 to-rose-600 rounded-2xl p-5 text-white">
                        <div class="text-rose-100 text-xs font-bold uppercase tracking-wider">Total Cancellations</div>
                        <div class="text-2xl font-black mt-1">{{ number_format($summary['total_cancellations']) }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl p-5 text-white">
                        <div class="text-amber-100 text-xs font-bold uppercase tracking-wider">Fare Lost</div>
                        <div class="text-2xl font-black mt-1">{{ number_format($summary['total_fare_lost']) }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-5 text-white">
                        <div class="text-emerald-100 text-xs font-bold uppercase tracking-wider">Penalties Collected</div>
                        <div class="text-2xl font-black mt-1">{{ number_format($summary['total_penalties_collected']) }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-5 text-white">
                        <div class="text-blue-100 text-xs font-bold uppercase tracking-wider">Refunded</div>
                        <div class="text-2xl font-black mt-1">{{ number_format($summary['total_refunded']) }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-2xl p-5 text-white">
                        <div class="text-slate-300 text-xs font-bold uppercase tracking-wider">Recovery Rate</div>
                        <div class="text-2xl font-black mt-1">{{ $summary['recovery_rate_percent'] }}%</div>
                    </div>
                </div>

                {{-- Chart --}}
                <div class="px-8 pt-6 print:hidden">
                    <canvas id="cancellationChart" height="60"></canvas>
                </div>

                <div class="flex justify-end mb-4 print:hidden px-8 pt-4">
                    <button onclick="window.print()" class="bg-slate-800 hover:bg-slate-900 text-white text-sm font-bold py-2 px-4 rounded-lg flex items-center gap-2 transition-colors">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>

                {{-- Daily Table --}}
                <div class="px-8 pt-4">
                    <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-3">Daily Breakdown</h3>
                </div>
                <div class="overflow-x-auto p-4">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                                <th class="p-4 font-semibold border-b border-slate-200">Date</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Cancellations</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Fare Lost (TZS)</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Penalties (TZS)</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Refunded (TZS)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @foreach($data['daily'] as $row)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="p-4 text-slate-800 font-medium">{{ \Carbon\Carbon::parse($row['cancel_date'])->format('D, d M Y') }}</td>
                                    <td class="p-4 font-mono text-right font-bold text-rose-600">{{ number_format($row['cancellation_count']) }}</td>
                                    <td class="p-4 font-mono text-right text-amber-600">{{ number_format($row['total_fare_lost']) }}</td>
                                    <td class="p-4 font-mono text-right text-emerald-600 font-bold">{{ number_format($row['total_penalties']) }}</td>
                                    <td class="p-4 font-mono text-right text-blue-600">{{ number_format($row['total_refunded']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- By Route --}}
                @if(!empty($data['by_route']))
                <div class="px-8 pt-6">
                    <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-3">Cancellations by Route</h3>
                </div>
                <div class="overflow-x-auto p-4 pt-0">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                                <th class="p-4 font-semibold border-b border-slate-200">Route</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Cancellations</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Penalties (TZS)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @foreach($data['by_route'] as $row)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="p-4 text-slate-800 font-bold">{{ $row['route'] }}</td>
                                    <td class="p-4 font-mono text-right font-bold text-rose-600">{{ number_format($row['count']) }}</td>
                                    <td class="p-4 font-mono text-right text-emerald-600">{{ number_format($row['penalties']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Chart === 'undefined') return;
                    const ctx = document.getElementById('cancellationChart');
                    if (!ctx) return;
                    const rawData = @json($data['daily']);
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: rawData.map(r => r.cancel_date),
                            datasets: [
                                { label: 'Cancellations', data: rawData.map(r => r.cancellation_count), borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.1)', fill: true, tension: 0.3 },
                                { label: 'Penalties', data: rawData.map(r => r.total_penalties), borderColor: '#10b981', backgroundColor: 'transparent', tension: 0.3, borderDash: [5, 5] }
                            ]
                        },
                        options: {
                            responsive: true,
                            scales: { y: { beginAtZero: true } },
                            plugins: { legend: { position: 'bottom' } }
                        }
                    });
                });
                </script>
            @endif
        </div>
    @endif
</x-layouts.admin>
