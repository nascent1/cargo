<x-layouts.admin>
    <x-slot:title>Agent Performance & Commission</x-slot:title>
    <x-slot:header>Agent Performance</x-slot:header>

    {{-- Breadcrumb & Title --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-slate-500 font-medium mb-1">
            <a href="{{ route('admin.reports.index') }}" class="hover:text-blue-600 transition-colors"><i class="fas fa-chart-line mr-1"></i> Reports Hub</a>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <span class="text-slate-800">Agent Performance</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-800">Agent Performance & Commission</h1>
        <p class="text-sm text-slate-500">Track total sales and estimated commissions by agent.</p>
    </div>

    {{-- Filter Form --}}
    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-6 mb-8" x-data="{ showPdfModal: false, pdfUrl: '' }">
        <form method="GET" action="{{ route('admin.reports.agent_performance') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
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
                @if(request()->has('run'))
                <button type="submit" name="export" value="excel" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2.5 px-4 rounded-xl transition-colors text-sm flex items-center justify-center gap-2">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
                <button type="button" @click="
                    const form = $el.closest('form');
                    const params = new URLSearchParams(new FormData(form));
                    params.set('export', 'pdf');
                    pdfUrl = form.action + '?' + params.toString();
                    showPdfModal = true;
                " class="flex-1 bg-rose-500 hover:bg-rose-600 text-white font-bold py-2.5 px-4 rounded-xl transition-colors text-sm flex items-center justify-center gap-2">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
                <button type="submit" name="export" value="docx" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-bold py-2.5 px-4 rounded-xl transition-colors text-sm flex items-center justify-center gap-2">
                    <i class="fas fa-file-word"></i> Word
                </button>
                @endif
            </div>
        </form>

        <!-- PDF Modal -->
        <div x-show="showPdfModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="display: none;">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showPdfModal" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0" 
                     x-transition:enter-end="opacity-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100" 
                     x-transition:leave-end="opacity-0" 
                     class="fixed inset-0 bg-slate-900/75 transition-opacity" 
                     @click="showPdfModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showPdfModal" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-6xl h-[85vh]">
                    
                    <div class="w-full h-full flex flex-col">
                        <div class="bg-white px-4 py-3 border-b border-slate-200 flex justify-between items-center">
                            <h3 class="text-lg leading-6 font-bold text-slate-900">Report PDF Viewer</h3>
                            <button @click="showPdfModal = false" class="text-slate-400 hover:text-slate-500 transition-colors bg-slate-100 hover:bg-slate-200 rounded-full w-8 h-8 flex items-center justify-center">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="flex-1 w-full bg-slate-50 relative">
                            <div class="absolute inset-0 flex items-center justify-center text-slate-400" x-show="pdfUrl">
                                <i class="fas fa-circle-notch fa-spin text-3xl"></i>
                            </div>
                            <iframe :src="pdfUrl" class="absolute inset-0 w-full h-full border-none z-10" onload="this.style.backgroundColor='white';"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Data Table --}}
    @if(request()->has('run'))
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl print:shadow-none print:border-none print:p-0">
            @if(empty($data))
                <div class="p-12 text-center text-slate-500">
                    <i class="fas fa-search text-4xl mb-4 text-slate-300"></i>
                    <p>No agent performance data found for the selected filters.</p>
                </div>
            @else
                
                {{-- Print Header --}}
                <div class="hidden print:block mb-8 border-b-2 border-slate-800 pb-4">
                    <h2 class="text-3xl font-bold text-center uppercase tracking-widest mb-2">Agent Performance Report</h2>
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

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 text-xs uppercase tracking-wider">
                                <th class="p-4 font-semibold">Agent Name</th>
                                <th class="p-4 font-semibold">Email</th>
                                <th class="p-4 font-semibold text-center">Tickets Sold</th>
                                <th class="p-4 font-semibold text-right">Sales (TZS)</th>
                                <th class="p-4 font-semibold text-right">Sales (USD)</th>
                                <th class="p-4 font-semibold text-right text-emerald-600">Est. Commission TZS (5%)</th>
                                <th class="p-4 font-semibold text-right text-emerald-600">Est. Commission USD (5%)</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            @php $totTix = 0; $totSalesTzs = 0; $totSalesUsd = 0; $totCommTzs = 0; $totCommUsd = 0; @endphp
                            @foreach($data as $row)
                                @php 
                                    $totTix += $row['total_tickets']; 
                                    $totSalesTzs += $row['total_sales_tzs']; 
                                    $totSalesUsd += $row['total_sales_usd']; 
                                    $totCommTzs += $row['estimated_commission_tzs']; 
                                    $totCommUsd += $row['estimated_commission_usd']; 
                                @endphp
                                <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                    <td class="p-4 font-bold text-slate-800">{{ $row['agent_name'] }}</td>
                                    <td class="p-4 text-slate-500">{{ $row['email'] }}</td>
                                    <td class="p-4 text-center font-mono font-medium text-slate-600">{{ number_format($row['total_tickets']) }}</td>
                                    <td class="p-4 text-right font-mono font-medium text-slate-900">{{ number_format($row['total_sales_tzs']) }}</td>
                                    <td class="p-4 text-right font-mono font-medium text-slate-900">{{ number_format($row['total_sales_usd'], 2) }}</td>
                                    <td class="p-4 text-right font-mono font-bold text-emerald-600 bg-emerald-50/30">{{ number_format($row['estimated_commission_tzs']) }}</td>
                                    <td class="p-4 text-right font-mono font-bold text-emerald-600 bg-emerald-50/30">{{ number_format($row['estimated_commission_usd'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-900 text-white">
                            <tr>
                                <td colspan="2" class="p-4 font-black uppercase tracking-wider text-xs text-right">Grand Total</td>
                                <td class="p-4 text-center font-mono font-black">{{ number_format($totTix) }}</td>
                                <td class="p-4 text-right font-mono font-black text-blue-400">{{ number_format($totSalesTzs) }}</td>
                                <td class="p-4 text-right font-mono font-black text-blue-400">{{ number_format($totSalesUsd, 2) }}</td>
                                <td class="p-4 text-right font-mono font-black text-emerald-400">TZS {{ number_format($totCommTzs) }}</td>
                                <td class="p-4 text-right font-mono font-black text-emerald-400">USD {{ number_format($totCommUsd, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    @endif
</x-layouts.admin>
