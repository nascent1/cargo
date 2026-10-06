<x-layouts.admin>
    <x-slot:title>Peak Hour Analysis</x-slot:title>
    <x-slot:header>Peak Hour Analysis</x-slot:header>

    {{-- Breadcrumb & Title --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-slate-500 font-medium mb-1">
            <a href="{{ route('admin.reports.index') }}" class="hover:text-blue-600 transition-colors"><i class="fas fa-chart-line mr-1"></i> Reports Hub</a>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <span class="text-slate-800">Peak Hour Analysis</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-800">Peak Hour Analysis</h1>
        <p class="text-sm text-slate-500">Hour-by-hour booking distribution for demand forecasting and dynamic pricing decisions.</p>
    </div>

    {{-- Filter Form --}}
    <x-report-filters :filters="$filters" :routes="$routes" :agents="$agents" :system-users="$systemUsers ?? []" :agents-with-associates="$agentsWithAssociates ?? []" :showStatus="false" :showDemographics="false" />

    {{-- Data --}}
    @if(request()->has('run'))
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl print:shadow-none print:border-none print:p-0">
            @if(empty($data) || collect($data)->sum('ticket_count') === 0)
                <div class="p-12 text-center text-slate-500">
                    <i class="fas fa-clock text-4xl mb-4 text-slate-300"></i>
                    <p>No booking data found for the selected criteria.</p>
                </div>
            @else
                {{-- Print Header --}}
                <div class="hidden print:block mb-8 border-b-2 border-slate-800 pb-4">
                    <h2 class="text-3xl font-bold text-center uppercase tracking-widest mb-2">Peak Hour Analysis</h2>
                    <div class="flex justify-between text-sm mt-6">
                        <div><strong>Date Range:</strong> {{ request('date_from', 'Start') }} to {{ request('date_to', 'End') }}</div>
                        <div class="text-right"><strong>Printed:</strong> {{ now()->format('d M Y H:i:s') }}</div>
                    </div>
                </div>

                @php
                    $peakHours = collect($data)->where('intensity', 'peak');
                    $totalTickets = collect($data)->sum('ticket_count');
                    $peakTickets = $peakHours->sum('ticket_count');
                @endphp

                {{-- Summary Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 px-8 pt-8 print:hidden">
                    <div class="bg-gradient-to-br from-rose-500 to-rose-600 rounded-2xl p-5 text-white">
                        <div class="text-rose-100 text-xs font-bold uppercase tracking-wider">Peak Hours</div>
                        <div class="text-2xl font-black mt-1">{{ $peakHours->count() }}</div>
                        <div class="text-rose-200 text-xs mt-1">{{ $peakHours->pluck('hour_label')->implode(', ') ?: 'None' }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl p-5 text-white">
                        <div class="text-amber-100 text-xs font-bold uppercase tracking-wider">Peak Hour Tickets</div>
                        <div class="text-2xl font-black mt-1">{{ number_format($peakTickets) }} <span class="text-sm font-normal">({{ $totalTickets > 0 ? round(($peakTickets / $totalTickets) * 100) : 0 }}%)</span></div>
                    </div>
                    <div class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-2xl p-5 text-white">
                        <div class="text-slate-300 text-xs font-bold uppercase tracking-wider">Total Bookings</div>
                        <div class="text-2xl font-black mt-1">{{ number_format($totalTickets) }}</div>
                    </div>
                </div>

                {{-- Chart --}}
                <div class="px-8 pt-6 print:hidden">
                    <canvas id="peakHourChart" height="70"></canvas>
                </div>

                <div class="flex justify-end mb-4 print:hidden px-8 pt-4">
                    <button onclick="window.print()" class="bg-slate-800 hover:bg-slate-900 text-white text-sm font-bold py-2 px-4 rounded-lg flex items-center gap-2 transition-colors">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>

                <div class="overflow-x-auto p-4">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                                <th class="p-4 font-semibold border-b border-slate-200">Hour</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-center">Intensity</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Tickets</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Revenue (TZS)</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Revenue (USD)</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Avg Fare</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @foreach($data as $row)
                                @if($row['ticket_count'] > 0)
                                <tr class="hover:bg-slate-50/50 transition-colors {{ $row['intensity'] === 'peak' ? 'bg-rose-50/30' : '' }}">
                                    <td class="p-4 text-slate-800 font-bold font-mono">{{ $row['hour_label'] }}</td>
                                    <td class="p-4 text-center">
                                        @if($row['intensity'] === 'peak')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700">
                                                <i class="fas fa-fire mr-1"></i> Peak
                                            </span>
                                        @elseif($row['intensity'] === 'normal')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">Normal</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-500">Off-Peak</span>
                                        @endif
                                    </td>
                                    <td class="p-4 font-mono text-right text-slate-800 font-bold">{{ number_format($row['ticket_count']) }}</td>
                                    <td class="p-4 font-mono text-right font-bold text-emerald-600">{{ number_format($row['revenue_tzs']) }}</td>
                                    <td class="p-4 font-mono text-right font-bold text-blue-600">{{ number_format($row['revenue_usd'], 2) }}</td>
                                    <td class="p-4 font-mono text-right text-slate-600">{{ number_format($row['avg_fare']) }}</td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Chart === 'undefined') return;
                    const ctx = document.getElementById('peakHourChart');
                    if (!ctx) return;
                    const rawData = @json($data);
                    const colors = rawData.map(r => r.intensity === 'peak' ? '#ef4444' : (r.intensity === 'normal' ? '#3b82f6' : '#e2e8f0'));
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: rawData.map(r => r.hour_label.substring(0, 5)),
                            datasets: [{
                                label: 'Tickets',
                                data: rawData.map(r => r.ticket_count),
                                backgroundColor: colors,
                                borderRadius: 6,
                                borderSkipped: false,
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: { y: { beginAtZero: true } },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        afterLabel: function(ctx) {
                                            const d = rawData[ctx.dataIndex];
                                            return 'Revenue: TZS ' + Number(d.revenue_tzs).toLocaleString();
                                        }
                                    }
                                }
                            }
                        }
                    });
                });
                </script>
            @endif
        </div>
    @endif
</x-layouts.admin>
