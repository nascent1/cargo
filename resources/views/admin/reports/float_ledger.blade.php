<x-layouts.admin>
    <x-slot:title>Agent Float Ledger</x-slot:title>
    <x-slot:header>Agent Float Ledger</x-slot:header>

    {{-- Breadcrumb & Title --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 font-medium mb-1">
                <a href="{{ route('admin.reports.index') }}" class="hover:text-blue-600 transition-colors"><i class="fas fa-chart-line mr-1"></i> Reports Hub</a>
                <i class="fas fa-chevron-right text-[10px]"></i>
                <span class="text-slate-800">Float Ledger</span>
            </div>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Agent Float Ledger</h2>
        </div>
        @if(request()->has('run'))
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="bg-violet-600 hover:bg-violet-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-violet-500/30 transition-all flex items-center gap-2">
                <i class="fas fa-download"></i> Export CSV
            </a>
        @endif
    </div>

    {{-- ═══ FILTER PANEL ═══ --}}
    <div class="bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm mb-8 relative z-20">
        <form action="{{ route('admin.reports.float_ledger') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            <input type="hidden" name="run" value="1">
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">From Date</label>
                <div class="relative">
                    <i class="fas fa-calendar absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition-all">
                </div>
            </div>
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">To Date</label>
                <div class="relative">
                    <i class="fas fa-calendar absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition-all">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Agent</label>
                <select name="agent_user_id" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition-all appearance-none">
                    <option value="">All Agents</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" @selected(($filters['agent_user_id'] ?? '') == $agent->id)>{{ $agent->agency_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-violet-600 hover:bg-violet-700 text-white px-4 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-violet-500/30 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-filter"></i> Run
                </button>
                <a href="{{ route('admin.reports.float_ledger') }}" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-bold transition-colors flex items-center justify-center">
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
                                <th class="py-4 px-5 text-left">Ref</th>
                                <th class="py-4 px-5 text-left">User/Agent</th>
                                <th class="py-4 px-5 text-left">Category</th>
                                <th class="py-4 px-5 text-right">Amount (TZS)</th>
                                <th class="py-4 px-5 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @php $netAmt = 0; @endphp
                            @foreach($data as $row)
                                @php
                                    $catSlug = $row['category']['slug'] ?? '';
                                    $isCredit = in_array($catSlug, ['float_topup', 'security_deposit', 'adjustment_credit', 'commission', 'refund']);
                                    $isDebit = in_array($catSlug, ['ticket_sale', 'adjustment_debit', 'cancellation_penalty']);
                                    if ($isCredit) {
                                        $netAmt += $row['amount_tzs'];
                                    } elseif ($isDebit) {
                                        $netAmt -= $row['amount_tzs'];
                                    }
                                @endphp
                                <tr class="hover:bg-violet-50/50 transition-colors">
                                    <td class="py-4 px-5 text-slate-600">{{ \Carbon\Carbon::parse($row['created_at'])->format('Y-m-d H:i') }}</td>
                                    <td class="py-4 px-5 font-mono text-xs font-bold text-slate-800">{{ $row['transaction_reference'] }}</td>
                                    <td class="py-4 px-5 font-bold text-slate-700">{{ $row['user']['full_name'] ?? 'System' }}</td>
                                    <td class="py-4 px-5 text-slate-600">{{ $row['category']['name'] ?? '' }}</td>
                                    <td class="py-4 px-5 text-right font-black font-mono {{ $isCredit ? 'text-emerald-600' : ($isDebit ? 'text-rose-600' : 'text-slate-800') }}">
                                        {{ $isCredit ? '+' : ($isDebit ? '-' : '') }}{{ number_format($row['amount_tzs']) }}
                                    </td>
                                    <td class="py-4 px-5 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ ($row['status']['slug'] ?? '') === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $row['status']['name'] ?? 'Unknown' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-900 text-white">
                            <tr>
                                <td colspan="4" class="py-4 px-5 font-black uppercase tracking-wider text-xs text-right">Net Flow</td>
                                <td class="py-4 px-5 text-right font-black font-mono {{ $netAmt >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                    {{ $netAmt >= 0 ? '+' : '' }}{{ number_format($netAmt) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endif
    @else
        <div class="bg-white rounded-3xl p-12 border border-slate-200/60 shadow-sm text-center border-dashed">
            <div class="h-20 w-20 mx-auto bg-violet-50 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-hand-pointer text-3xl text-violet-400"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800">Select filters to begin</h3>
            <p class="text-sm text-slate-500 mt-1">Choose your parameters and click Run to generate the report.</p>
        </div>
    @endif
</x-layouts.admin>
