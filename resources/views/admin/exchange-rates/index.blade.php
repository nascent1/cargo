<x-layouts.admin>
<x-slot:title>Exchange Rates Management</x-slot:title>
<x-slot:header>Multi-Currency Exchange Rates</x-slot:header>

<div class="px-4 sm:px-6 lg:px-8 py-6">

    @if(session('success'))
        <div class="mb-5 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 flex items-center gap-3">
            <i class="fas fa-check-circle text-emerald-500"></i>
            <p class="text-sm font-semibold text-emerald-800">{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Add/Update Form --}}
        <div class="bg-white rounded-2xl border border-surface-200/80 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-surface-100"
                style="background: linear-gradient(135deg, #0f172a, #0e2240)">
                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 rounded-xl flex items-center justify-center shrink-0"
                        style="background: rgba(250,189,0,0.15); border: 1px solid rgba(250,189,0,0.2)">
                        <i class="fas fa-exchange-alt text-sm" style="color: #FABD00"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white">Add / Update Rate</h3>
                        <p class="text-[10px] mt-0.5" style="color: rgba(255,255,255,0.35)">Set a new exchange rate entry</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('admin.settings.exchange-rates.store') }}" method="POST" class="p-5 space-y-4">
                @csrf
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-surface-600 uppercase tracking-wider">From Currency</label>
                    <select name="from_currency" required
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-surface-50 focus:bg-white">
                        @foreach($currencies as $curr)
                            <option value="{{ $curr->code }}">{{ $curr->code }} — {{ $curr->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-surface-600 uppercase tracking-wider">To Currency (Base)</label>
                    <input type="text" name="to_currency" value="TZS" required readonly
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 bg-surface-100 text-surface-500 font-mono font-semibold outline-none">
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-surface-600 uppercase tracking-wider">Exchange Rate (1 From = X To)</label>
                    <div class="relative">
                        <input type="number" step="0.000001" name="rate" placeholder="e.g. 2600.00" required
                            class="w-full pl-3 pr-14 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all font-mono bg-surface-50 focus:bg-white">
                        <span class="absolute inset-y-0 right-3 flex items-center text-xs text-surface-400 font-bold">TZS</span>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-surface-600 uppercase tracking-wider">Effective From Date</label>
                    <input type="date" name="effective_from" value="{{ date('Y-m-d') }}" required
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-surface-50 focus:bg-white">
                </div>

                <button type="submit"
                    class="w-full py-2.5 text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition-colors shadow-sm flex items-center justify-center gap-2">
                    <i class="fas fa-save text-xs"></i> Save Exchange Rate
                </button>
            </form>
        </div>

        {{-- Rates Table --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-surface-200/80 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-surface-100">
                <div>
                    <h3 class="text-base font-bold text-surface-900">Current & Historical Rates</h3>
                    <p class="text-xs text-surface-400 mt-0.5">All rates are relative to TZS as the base currency.</p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-brand-50 text-brand-700 border border-brand-100">
                    <i class="fas fa-anchor text-xs"></i> Base: TZS
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-50 border-b border-surface-100">
                            <th class="text-left px-6 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Currency Pair</th>
                            <th class="text-right px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Rate</th>
                            <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Effective Date</th>
                            <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Set By</th>
                            <th class="text-center px-5 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rates as $rate)
                        <tr class="border-b border-surface-50 hover:bg-surface-50/80 transition-colors">
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-surface-900 bg-surface-100 px-2 py-0.5 rounded text-xs">{{ $rate->from_currency }}</span>
                                    <i class="fas fa-arrow-right text-surface-400 text-xs"></i>
                                    <span class="font-mono font-bold text-surface-900 bg-brand-50 text-brand-700 px-2 py-0.5 rounded text-xs border border-brand-100">{{ $rate->to_currency }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono font-bold text-emerald-600 tabular-nums">
                                {{ number_format($rate->rate, 2) }}
                            </td>
                            <td class="px-4 py-3.5 text-sm text-surface-600">
                                {{ $rate->effective_from->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-3.5 text-xs text-surface-500">
                                {{ $rate->creator?->name ?? 'System' }}
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <form action="{{ route('admin.settings.exchange-rates.destroy', $rate) }}" method="POST"
                                    class="inline" onsubmit="return confirm('Delete this exchange rate?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 border border-red-100 rounded-lg transition-colors">
                                        <i class="fas fa-trash text-[10px]"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-14 text-center">
                                <i class="fas fa-coins text-3xl text-surface-300 mb-3 block"></i>
                                <p class="text-surface-400 text-sm">No exchange rates recorded yet.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($rates->hasPages())
            <div class="bg-surface-50 px-6 py-4 border-t border-surface-100">
                {{ $rates->links() }}
            </div>
            @endif
        </div>

    </div>
</div>
</x-layouts.admin>
