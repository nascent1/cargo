@extends('admin.reports.pdf.layout')

@section('content')
    <table style="width: 100%; margin-bottom: 10px;">
        <tr>
            <td style="width: 50%;" class="header-title">{{ strtoupper(\App\Models\SystemConfig::getValue('platform_name', config('app.name', 'FERRY BOOKING PLATFORM'))) }}</td>
            <td style="width: 50%; text-align: right;" class="header-title">REVENUE REPORT</td>
        </tr>
    </table>

    <table class="summary-table">
        <tr>
            <td style="width: 50%;">
                Date Range: {{ $filters['date_from'] ?? 'All Time' }} to {{ $filters['date_to'] ?? 'Today' }}
            </td>
            <td style="width: 50%;">
                Route: {{ !empty($filters['route_id']) ? \App\Models\Route::find($filters['route_id'])?->name : 'All Routes' }}
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead style="background-color: #f2f2f2;">
            <tr>
                <th>Date</th>
                <th>Route</th>
                <th>Class</th>
                <th>Tickets Sold</th>
                <th>Revenue (TZS)</th>
            </tr>
        </thead>
        <tbody>
            @php $totalTickets = 0; $totalRevenue = 0; @endphp
            @forelse($data as $row)
                @php
                    // Depending on what ReportController provides, mapping generic objects/arrays
                    $rowArray = (array) $row;
                    $totalTickets += $rowArray['Tickets Sold'] ?? 0;
                    $totalRevenue += str_replace(',', '', $rowArray['Revenue (TZS)'] ?? 0);
                @endphp
                <tr>
                    <td>{{ $rowArray['Date'] ?? '-' }}</td>
                    <td>{{ $rowArray['Route'] ?? '-' }}</td>
                    <td>{{ $rowArray['Class'] ?? '-' }}</td>
                    <td>{{ $rowArray['Tickets Sold'] ?? 0 }}</td>
                    <td class="text-right">{{ $rowArray['Revenue (TZS)'] ?? 0 }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px;">No revenue data found for selected period.</td>
                </tr>
            @endforelse
        </tbody>
        @if(count($data) > 0)
        <tfoot>
            <tr style="background-color: #e6e6e6; font-weight: bold;">
                <td colspan="3" style="text-align: right;">TOTALS:</td>
                <td>{{ number_format($totalTickets) }}</td>
                <td class="text-right">{{ number_format($totalRevenue) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
@endsection
