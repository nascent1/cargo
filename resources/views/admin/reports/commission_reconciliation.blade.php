<x-layouts.admin>
    <x-slot:title>Commission Reconciliation</x-slot:title>
    <x-slot:header>Commission Reconciliation</x-slot:header>

    {{-- Breadcrumb & Title --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-slate-500 font-medium mb-1">
            <a href="{{ route('admin.reports.index') }}" class="hover:text-blue-600 transition-colors"><i class="fas fa-chart-line mr-1"></i> Reports Hub</a>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <span class="text-slate-800">Commission Reconciliation</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-800">Commission Reconciliation</h1>
        <p class="text-sm text-slate-500">Earned vs paid commissions per agent with discrepancy detection and settlement status.</p>
    </div>

    {{-- Filter Form (date only, no route needed) --}}
    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-6 mb-8">
        <form method="GET" action="{{ url()->current() }}">
            <input type="hidden" name="run" value="1">
            <div class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Date Range</label>
                    <div class="flex items-center space-x-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
                        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? now()->startOfMonth()->toDateString() }}" class="bg-transparent text-slate-800 text-sm outline-none w-32">
                        <span class="text-slate-400 text-xs">to</span>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? now()->toDateString() }}" class="bg-transparent text-slate-800 text-sm outline-none w-32">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Agent</label>
                    <select name="agent_user_id" class="bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-xl px-4 py-2 focus:border-blue-500 outline-none transition-all min-w-[200px]">
                        <option value="">All Agents</option>
                        @foreach($agents ?? [] as $agent)
                            <option value="{{ $agent->user_id }}" {{ ($filters['agent_user_id'] ?? '') == $agent->user_id ? 'selected' : '' }}>
                                {{ $agent->agency_name ?: ($agent->user->full_name ?? 'N/A') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2 ml-auto">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-5 rounded-xl transition-colors text-sm flex items-center justify-center gap-2 shadow-sm shadow-blue-200">
                        <i class="fas fa-play text-xs"></i> Run Report
                    </button>
                    @if(request()->has('run'))
                    <button type="submit" name="export" value="excel" class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2.5 px-4 rounded-xl transition-colors text-sm flex items-center justify-center gap-2">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Data --}}
    @if(request()->has('run'))
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl print:shadow-none print:border-none print:p-0">
            @if(empty($data))
                <div class="p-12 text-center text-slate-500">
                    <i class="fas fa-balance-scale text-4xl mb-4 text-slate-300"></i>
                    <p>No commission data found for the selected criteria.</p>
                </div>
            @else
                {{-- Print Header --}}
                <div class="hidden print:block mb-8 border-b-2 border-slate-800 pb-4">
                    <h2 class="text-3xl font-bold text-center uppercase tracking-widest mb-2">Commission Reconciliation</h2>
                    <div class="flex justify-between text-sm mt-6">
                        <div><strong>Period:</strong> {{ request('date_from', now()->startOfMonth()->format('Y-m-d')) }} to {{ request('date_to', now()->format('Y-m-d')) }}</div>
                        <div class="text-right"><strong>Printed:</strong> {{ now()->format('d M Y H:i:s') }}</div>
                    </div>
                </div>

                @php
                    $balanced = collect($data)->where('status', 'Balanced')->count();
                    $underpaid = collect($data)->where('status', 'Underpaid')->count();
                    $overpaid = collect($data)->where('status', 'Overpaid')->count();
                    $totalDiscrepancy = collect($data)->sum('discrepancy_tzs');
                @endphp

                {{-- Summary Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 px-8 pt-8 print:hidden">
                    <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-5 text-white">
                        <div class="text-emerald-100 text-xs font-bold uppercase tracking-wider">Balanced</div>
                        <div class="text-2xl font-black mt-1">{{ $balanced }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl p-5 text-white">
                        <div class="text-amber-100 text-xs font-bold uppercase tracking-wider">Underpaid</div>
                        <div class="text-2xl font-black mt-1">{{ $underpaid }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-rose-500 to-rose-600 rounded-2xl p-5 text-white">
                        <div class="text-rose-100 text-xs font-bold uppercase tracking-wider">Overpaid</div>
                        <div class="text-2xl font-black mt-1">{{ $overpaid }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-2xl p-5 text-white">
                        <div class="text-slate-300 text-xs font-bold uppercase tracking-wider">Net Discrepancy</div>
                        <div class="text-2xl font-black mt-1 {{ $totalDiscrepancy > 0 ? 'text-amber-400' : ($totalDiscrepancy < 0 ? 'text-rose-400' : '') }}">
                            TZS {{ number_format(abs($totalDiscrepancy)) }}
                        </div>
                    </div>
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
                                <th class="p-4 font-semibold border-b border-slate-200">Agent</th>
                                <th class="p-4 font-semibold border-b border-slate-200">Code</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Rate %</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Tickets</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Sales (TZS)</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Expected Comm</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Paid Comm</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Discrepancy</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-center">Status</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-right">Float Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @foreach($data as $row)
                                <tr class="hover:bg-slate-50/50 transition-colors {{ $row['status'] !== 'Balanced' ? 'bg-amber-50/30' : '' }}">
                                    <td class="p-4 text-slate-800 font-bold">{{ $row['agent_name'] }}</td>
                                    <td class="p-4 text-slate-500 font-mono text-xs">{{ $row['agent_code'] }}</td>
                                    <td class="p-4 font-mono text-right text-slate-600">{{ $row['commission_rate'] }}%</td>
                                    <td class="p-4 font-mono text-right text-slate-800">{{ number_format($row['total_tickets']) }}</td>
                                    <td class="p-4 font-mono text-right font-bold text-slate-800">{{ number_format($row['total_sales_tzs']) }}</td>
                                    <td class="p-4 font-mono text-right text-blue-600 font-bold">{{ number_format($row['expected_commission_tzs']) }}</td>
                                    <td class="p-4 font-mono text-right text-emerald-600 font-bold">{{ number_format($row['paid_commission_tzs']) }}</td>
                                    <td class="p-4 font-mono text-right font-black {{ $row['discrepancy_tzs'] > 0 ? 'text-amber-600' : ($row['discrepancy_tzs'] < 0 ? 'text-rose-600' : 'text-slate-400') }}">
                                        {{ $row['discrepancy_tzs'] >= 0 ? '+' : '' }}{{ number_format($row['discrepancy_tzs']) }}
                                    </td>
                                    <td class="p-4 text-center">
                                        @if($row['status'] === 'Balanced')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                                <i class="fas fa-check-circle mr-1"></i> Balanced
                                            </span>
                                        @elseif($row['status'] === 'Underpaid')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700">
                                                <i class="fas fa-exclamation-circle mr-1"></i> Underpaid
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700">
                                                <i class="fas fa-exclamation-triangle mr-1"></i> Overpaid
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-4 font-mono text-right text-slate-600">{{ number_format($row['float_balance']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</x-layouts.admin>
