@extends('admin.reports.pdf.layout')

@section('content')
    <table style="width: 100%; margin-bottom: 10px;">
        <tr>
            <td style="width: 50%;" class="header-title">{{ strtoupper(\App\Models\SystemConfig::getValue('platform_name', config('app.name', 'FERRY BOOKING PLATFORM'))) }}</td>
            <td style="width: 50%; text-align: right;" class="header-title">BOARDING REPORT</td>
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
                <th>Voyage</th>
                <th>Total Booked</th>
                <th>Boarded</th>
                <th>No-Shows</th>
                <th>Boarding %</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
                @php $rowArray = (array) $row; @endphp
                <tr>
                    <td>{{ $rowArray['Date'] ?? '-' }}</td>
                    <td class="text-left">{{ $rowArray['Voyage'] ?? '-' }}</td>
                    <td>{{ $rowArray['Total Booked'] ?? 0 }}</td>
                    <td class="text-green">{{ $rowArray['Boarded'] ?? 0 }}</td>
                    <td class="text-orange">{{ $rowArray['No-Shows'] ?? 0 }}</td>
                    <td>{{ $rowArray['Boarding Rate %'] ?? 0 }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">No boarding data found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
