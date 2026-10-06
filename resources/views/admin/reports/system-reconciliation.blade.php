<x-layouts.admin>
    <x-slot:title>System Reconciliation</x-slot:title>
    <x-slot:header>Financial Reconciliation</x-slot:header>

    <div class="mb-6 flex flex-wrap justify-between items-end gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Financial Reconciliation</h2>
            <p class="text-slate-500 text-sm">Run daily system checks to ensure booking amounts, transactions, and wallets balance perfectly.</p>
        </div>
        
        <form method="GET" action="{{ route('admin.reports.system_reconciliation') }}" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="run" value="1">
            <input type="date" name="date" value="{{ $date }}" class="px-4 py-2 rounded-xl border border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm font-semibold text-slate-700">
            <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl shadow-sm transition-colors">
                Run Checks
            </button>
        </form>
    </div>

    @if(request()->has('run'))
        <!-- Summary Stats Banner -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-2xl p-4 border border-slate-200/60 shadow-sm">
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Checks Performed</p>
                <p class="text-2xl font-black text-slate-800">{{ $totalChecks }}</p>
            </div>
            <div class="bg-white rounded-2xl p-4 border border-slate-200/60 shadow-sm">
                <p class="text-xs font-bold text-emerald-500 uppercase mb-1">Passed</p>
                <p class="text-2xl font-black text-emerald-600">{{ $totalChecks - ($issueCount > 0 ? 1 : 0) * min($issueCount, $totalChecks) }}</p>
            </div>
            <div class="bg-white rounded-2xl p-4 border border-slate-200/60 shadow-sm">
                <p class="text-xs font-bold text-red-500 uppercase mb-1">Issues Found</p>
                <p class="text-2xl font-black {{ $issueCount > 0 ? 'text-red-600' : 'text-slate-800' }}">{{ $issueCount }}</p>
            </div>
        </div>

        @if(empty($discrepancies))
            <div class="bg-emerald-50 border border-emerald-200 rounded-3xl p-8 text-center">
                <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-double text-4xl text-emerald-500"></i>
                </div>
                <h3 class="text-2xl font-bold text-emerald-800 mb-2">System is Fully Reconciled</h3>
                <p class="text-emerald-600">No discrepancies were found between bookings, transactions, and agent wallets for {{ $date }}.</p>
            </div>
        @else
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-red-50 flex items-center gap-4">
                    <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-2xl text-red-600"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-red-800">Discrepancies Found</h3>
                        <p class="text-red-600 font-semibold">{{ count($discrepancies) }} issue(s) detected for {{ $date }}</p>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 border-b border-slate-200">
                                <th class="p-4 font-bold">Severity</th>
                                <th class="p-4 font-bold">Type</th>
                                <th class="p-4 font-bold">Entity</th>
                                <th class="p-4 font-bold">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @foreach($discrepancies as $d)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="p-4">
                                        @if($d['severity'] === 'HIGH')
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-bold bg-red-100 text-red-700">HIGH</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-bold bg-amber-100 text-amber-700">MEDIUM</span>
                                        @endif
                                    </td>
                                    <td class="p-4 font-bold text-slate-700">{{ $d['type'] }}</td>
                                    <td class="p-4 font-semibold">{{ $d['entity'] }}</td>
                                    <td class="p-4 text-slate-600">{{ $d['detail'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @else
        <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-12 text-center">
            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                <i class="fas fa-search-dollar text-3xl text-slate-400"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-800 mb-2">Run Financial Reconciliation</h3>
            <p class="text-slate-500 max-w-md mx-auto">Select a date above and click "Run Checks" to automatically verify the integrity of all bookings, payments, and agent wallet balances.</p>
        </div>
    @endif
</x-layouts.admin>
