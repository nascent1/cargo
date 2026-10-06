<x-layouts.admin>
    <x-slot:title>Boarding & No-Show Report</x-slot:title>
    <x-slot:header>Boarding & No-Show Report</x-slot:header>

    {{-- Breadcrumb & Title --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-slate-500 font-medium mb-1">
            <a href="{{ route('admin.reports.index') }}" class="hover:text-blue-600 transition-colors"><i class="fas fa-chart-line mr-1"></i> Reports Hub</a>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <span class="text-slate-800">Boarding & No-Show</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-800">Boarding & No-Show Report</h1>
        <p class="text-sm text-slate-500">Analyze passenger attendance, boarded counts, and no-show rates per voyage.</p>
    </div>

    {{-- Filter Form --}}
    <x-report-filters :filters="$filters" :routes="$routes" :showStatus="false" :showDemographics="false" />

    {{-- Data Table --}}
    @if(request()->has('run'))
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl print:shadow-none print:border-none print:p-0">
            @if(empty($data))
                <div class="p-12 text-center text-slate-500">
                    <i class="fas fa-search text-4xl mb-4 text-slate-300"></i>
                    <p>No boarding data found for the selected filters.</p>
                </div>
            @else
                
                {{-- Print Header --}}
                <div class="hidden print:block mb-8 border-b-2 border-slate-800 pb-4">
                    <h2 class="text-3xl font-bold text-center uppercase tracking-widest mb-2">Boarding Report</h2>
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
                                <th class="p-4 font-semibold">Departure</th>
                                <th class="p-4 font-semibold">Route & Vessel</th>
                                <th class="p-4 font-semibold text-center">Total Pax</th>
                                <th class="p-4 font-semibold text-center text-emerald-600">Boarded</th>
                                <th class="p-4 font-semibold text-center text-rose-600">No Shows</th>
                                <th class="p-4 font-semibold text-center">No-Show %</th>
                                <th class="p-4 font-semibold text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            @foreach($data as $row)
                                <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                    <td class="p-4 whitespace-nowrap">
                                        <div class="font-medium text-slate-900">{{ \Carbon\Carbon::parse($row['date'])->format('d M Y') }}</div>
                                        <div class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($row['date'])->format('h:i A') }}</div>
                                    </td>
                                    <td class="p-4">
                                        <div class="font-bold text-slate-800">{{ $row['route'] }}</div>
                                        <div class="text-xs text-slate-500">{{ $row['ferry'] }}</div>
                                    </td>
                                    <td class="p-4 text-center font-mono font-medium text-slate-600 bg-slate-50">{{ number_format($row['total_pax']) }}</td>
                                    <td class="p-4 text-center font-mono font-bold text-emerald-600 bg-emerald-50/30">{{ number_format($row['boarded']) }}</td>
                                    <td class="p-4 text-center font-mono font-bold text-rose-600 bg-rose-50/30">{{ number_format($row['no_shows']) }}</td>
                                    <td class="p-4 text-center">
                                        @if($row['no_show_rate'] > 20)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700">
                                                {{ $row['no_show_rate'] }}%
                                            </span>
                                        @elseif($row['no_show_rate'] > 10)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700">
                                                {{ $row['no_show_rate'] }}%
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
                                                {{ $row['no_show_rate'] }}%
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-center">
                                        @if($row['status'] === 'completed')
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-emerald-100 text-emerald-700">Completed</span>
                                        @elseif($row['status'] === 'departed')
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700">Departed</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-slate-100 text-slate-700">{{ ucfirst($row['status']) }}</span>
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
