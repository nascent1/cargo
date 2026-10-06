<x-layouts.admin>
    <x-slot:title>Payment Channel Analysis</x-slot:title>
    <x-slot:header>Payment Channel Analysis</x-slot:header>

    {{-- Breadcrumb & Title --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-slate-500 font-medium mb-1">
            <a href="{{ route('admin.reports.index') }}" class="hover:text-blue-600 transition-colors"><i class="fas fa-chart-line mr-1"></i> Reports Hub</a>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <span class="text-slate-800">Payment Channel Analysis</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-800">Payment Channel Analysis</h1>
        <p class="text-sm text-slate-500">Revenue breakdown by payment method with period-over-period trend comparison.</p>
    </div>

    {{-- Filter Form --}}
    <x-report-filters :filters="$filters" :routes="$routes" :agents="$agents" :system-users="$systemUsers ?? []" :agents-with-associates="$agentsWithAssociates ?? []" :showStatus="false" :showDemographics="false" />

    {{-- Data --}}
    @if(request()->has('run'))
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl print:shadow-none print:border-none print:p-0">
            @if(empty($data))
                <div class="p-12 text-center text-slate-500">
                    <i class="fas fa-credit-card text-4xl mb-4 text-slate-300"></i>
                    <p>No payment data found for the selected criteria.</p>
                </div>
            @else
                {{-- Print Header --}}
                <div class="hidden print:block mb-8 border-b-2 border-slate-800 pb-4">
                    <h2 class="text-3xl font-bold text-center uppercase tracking-widest mb-2">Payment Channel Analysis</h2>
                    <div class="flex justify-between text-sm mt-6">
                        <div><strong>Date Range:</strong> {{ request('date_from', 'Start') }} to {{ request('date_to', 'End') }}</div>
                        <div class="text-right"><strong>Printed:</strong> {{ now()->format('d M Y H:i:s') }}</div>
                    </div>
                </div>

                {{-- Summary Cards --}}
                @php
                    $totalTickets = array_sum(array_column($data, 'ticket_count'));
                    $totalRevTzs = array_sum(array_column($data, 'revenue_tzs'));
                    $totalRevUsd = array_sum(array_column($data, 'revenue_usd'));
                @endphp
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 px-8 pt-8 print:hidden">
                    <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-5 text-white">
                        <div class="text-emerald-100 text-xs font-bold uppercase tracking-wider">Total Revenue (TZS)</div>
                        <div class="text-2xl font-black mt-1">{{ number_format($totalRevTzs) }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-5 text-white">
                        <div class="text-blue-100 text-xs font-bold uppercase tracking-wider">Total Revenue (USD)</div>
                        <div class="text-2xl font-black mt-1">${{ number_format($totalRevUsd, 2) }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-2xl p-5 text-white">
                        <div class="text-slate-300 text-xs font-bold uppercase tracking-wider">Total Tickets</div>
                        <div class="text-2xl font-black mt-1">{{ number_format($totalTickets) }}</div>
                    </div>
                </div>

                {{-- Chart --}}
                <div class="px-8 pt-6 print:hidden">
                    <canvas id="paymentChart" height="80"></canvas>
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
                                <th class="p-4 font-semibold border-b border-slate-200">Payment Channel</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Tickets</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Revenue (TZS)</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Revenue (USD)</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Avg Transaction</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Share %</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Growth vs Prev</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @foreach($data as $row)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="p-4 text-slate-800 font-bold capitalize">
                                        @php
                                            $channelIcon = match($row['channel']) {
                                                'cash' => 'fa-money-bill-wave text-emerald-500',
                                                'mobile_money' => 'fa-mobile-alt text-blue-500',
                                                'agent_float' => 'fa-wallet text-violet-500',
                                                'card' => 'fa-credit-card text-amber-500',
                                                default => 'fa-coins text-slate-400',
                                            };
                                        @endphp
                                        <i class="fas {{ $channelIcon }} mr-2"></i>
                                        {{ str_replace('_', ' ', $row['channel']) }}
                                    </td>
                                    <td class="p-4 font-mono text-right text-slate-800">{{ number_format($row['ticket_count']) }}</td>
                                    <td class="p-4 font-mono text-right font-bold text-emerald-600">{{ number_format($row['revenue_tzs']) }}</td>
                                    <td class="p-4 font-mono text-right font-bold text-blue-600">{{ number_format($row['revenue_usd'], 2) }}</td>
                                    <td class="p-4 font-mono text-right text-slate-600">{{ number_format($row['avg_transaction']) }}</td>
                                    <td class="p-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <div class="w-16 bg-slate-200 rounded-full h-2">
                                                <div class="bg-blue-500 h-2 rounded-full" style="width: {{ min($row['revenue_share_percent'], 100) }}%"></div>
                                            </div>
                                            <span class="font-mono text-slate-700 font-bold">{{ $row['revenue_share_percent'] }}%</span>
                                        </div>
                                    </td>
                                    <td class="p-4 text-right">
                                        @if($row['growth_percent'] > 0)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                                <i class="fas fa-arrow-up mr-1"></i>{{ $row['growth_percent'] }}%
                                            </span>
                                        @elseif($row['growth_percent'] < 0)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700">
                                                <i class="fas fa-arrow-down mr-1"></i>{{ abs($row['growth_percent']) }}%
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-500">0%</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @php
                    $chartLabels = array_map(function($r) { return str_replace('_', ' ', ucfirst($r['channel'])); }, $data);
                    $chartData = array_column($data, 'revenue_tzs');
                @endphp
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Chart === 'undefined') return;
                    const ctx = document.getElementById('paymentChart');
                    if (!ctx) return;
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: @json($chartLabels),
                            datasets: [{
                                data: @json($chartData),
                                backgroundColor: ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ef4444', '#6b7280'],
                                borderWidth: 0,
                                borderRadius: 4,
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { position: 'bottom' }
                            }
                        }
                    });
                });
                </script>
            @endif
        </div>
    @endif
</x-layouts.admin>
