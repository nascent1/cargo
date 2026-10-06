<x-layouts.admin>
    <x-slot:title>Booking Report</x-slot:title>
    <x-slot:header>Booking Report</x-slot:header>

    {{-- Breadcrumb & Title --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-slate-500 font-medium mb-1">
            <a href="{{ route('admin.reports.index') }}" class="hover:text-blue-600 transition-colors"><i class="fas fa-chart-line mr-1"></i> Reports Hub</a>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <span class="text-slate-800">Booking Report</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-800">Booking Report</h1>
        <p class="text-sm text-slate-500">Detailed ledger of individual tickets and passenger names.</p>
    </div>

    {{-- Filter Form --}}
    <x-report-filters :filters="$filters" :routes="$routes" :agents="$agents" :system-users="$systemUsers ?? []" :agents-with-associates="$agentsWithAssociates ?? []" :show-view-toggle="true" />

    {{-- Data Table --}}
    @if(request()->has('run'))
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl print:shadow-none print:border-none print:p-0">
            @if(empty($data))
                <div class="p-12 text-center text-slate-500">
                    <i class="fas fa-folder-open text-4xl mb-4 text-slate-300"></i>
                    <p>No bookings found for the selected criteria.</p>
                </div>
            @else
                
                {{-- Print Header --}}
                <div class="hidden print:block mb-8 border-b-2 border-slate-800 pb-4">
                    <h2 class="text-3xl font-bold text-center uppercase tracking-widest mb-2">Booking Report</h2>
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

                @if(($filters['view_mode'] ?? 'detailed') === 'summary')
                    <div class="overflow-x-auto p-4">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                                    <th class="p-4 font-semibold border-b border-slate-200">Grouping Metric</th>
                                    <th class="p-4 font-semibold border-b border-slate-200 text-right">Total Tickets</th>
                                    <th class="p-4 font-semibold border-b border-slate-200 text-right">Total Fare (TZS)</th>
                                    <th class="p-4 font-semibold border-b border-slate-200 text-right">Total Fare (USD)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                @php $totTix = 0; $totFareTzs = 0; $totFareUsd = 0; @endphp
                                @foreach($data as $row)
                                    @php $totTix += $row['count']; $totFareTzs += $row['total_fare_tzs']; $totFareUsd += $row['total_fare_usd']; @endphp
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="p-4 text-slate-800 font-medium">{{ $row['label'] }}</td>
                                        <td class="p-4 font-mono text-right text-slate-800">{{ number_format($row['count']) }}</td>
                                        <td class="p-4 font-mono text-right font-bold text-emerald-600">{{ number_format($row['total_fare_tzs']) }}</td>
                                        <td class="p-4 font-mono text-right font-bold text-blue-600">{{ number_format($row['total_fare_usd'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-slate-900 text-white">
                                <tr>
                                    <td class="p-4 font-black uppercase tracking-wider text-xs">Grand Total</td>
                                    <td class="p-4 font-mono font-black text-right">{{ number_format($totTix) }}</td>
                                    <td class="p-4 font-mono font-black text-right text-emerald-400">TZS {{ number_format($totFareTzs) }}</td>
                                    <td class="p-4 font-mono font-black text-right text-blue-400">USD {{ number_format($totFareUsd, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-[11px] uppercase tracking-wider">
                                    <th class="p-3 font-semibold border-b border-slate-200">S/N</th>
                                    <th class="p-3 font-semibold border-b border-slate-200">Booking ID</th>
                                    <th class="p-3 font-semibold border-b border-slate-200">Passenger</th>
                                    <th class="p-3 font-semibold border-b border-slate-200">Gender</th>
                                    <th class="p-3 font-semibold border-b border-slate-200">Age</th>
                                    <th class="p-3 font-semibold border-b border-slate-200">Nationality</th>
                                    <th class="p-3 font-semibold border-b border-slate-200 text-right">(TSH)</th>
                                    <th class="p-3 font-semibold border-b border-slate-200 text-right">(USD)</th>
                                    <th class="p-3 font-semibold border-b border-slate-200 text-right">Rate</th>
                                    <th class="p-3 font-semibold border-b border-slate-200">Ticket Status</th>
                                    <th class="p-3 font-semibold border-b border-slate-200">Class</th>
                                    <th class="p-3 font-semibold border-b border-slate-200">Journey Date</th>
                                    <th class="p-3 font-semibold border-b border-slate-200">Booking Date</th>
                                    <th class="p-3 font-semibold border-b border-slate-200">Route</th>
                                    <th class="p-3 font-semibold border-b border-slate-200 text-right">Total (TSH)</th>
                                    <th class="p-3 font-semibold border-b border-slate-200">Booking Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-[13px]">
                                @php $totFareTzs = 0; @endphp
                                @foreach($data as $index => $row)
                                    @php 
                                        $totFareTzs += $row['total_tsh'];
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="p-3 text-slate-500">{{ $index + 1 }}</td>
                                        <td class="p-3 font-mono text-slate-800">{{ $row['ticket'] }}</td>
                                        <td class="p-3 text-slate-800">{{ $row['passenger_name'] }}</td>
                                        <td class="p-3 text-slate-600">{{ $row['gender'] ?? 'N/A' }}</td>
                                        <td class="p-3 text-slate-600">{{ $row['type'] ?? 'N/A' }}</td>
                                        <td class="p-3 text-slate-600">{{ $row['nationality'] ?? 'N/A' }}</td>
                                        <td class="p-3 text-right font-mono text-slate-800">{{ number_format($row['tsh_amount'] ?? 0) }}</td>
                                        <td class="p-3 text-right font-mono text-slate-800">{{ number_format($row['usd_amount'] ?? 0, 2) }}</td>
                                        <td class="p-3 text-right font-mono text-slate-800">{{ number_format($row['rate'] ?? 2800) }}</td>
                                        <td class="p-3 text-slate-800 capitalize">{{ str_replace('_', ' ', $row['status']) }}</td>
                                        <td class="p-3 text-slate-600">{{ $row['class'] }}</td>
                                        <td class="p-3 text-slate-600">{{ $row['journey_date'] ?? '' }}</td>
                                        <td class="p-3 text-slate-600">{{ $row['booking_date'] ?? '' }}</td>
                                        <td class="p-3 text-slate-800">{{ $row['route'] }}</td>
                                        <td class="p-3 text-right font-mono font-bold text-slate-800">{{ number_format($row['total_tsh']) }}</td>
                                        <td class="p-3 uppercase text-slate-800">{{ $row['booking_status'] ?? 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-slate-900 text-white border-t-2 border-slate-200">
                                <tr>
                                    <td colspan="14" class="p-4 font-black uppercase tracking-wider text-xs text-right">Grand Total</td>
                                    <td class="p-4 font-mono font-black text-right text-emerald-400">{{ number_format($totFareTzs) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            @endif
        </div>
    @endif
</x-layouts.admin>
