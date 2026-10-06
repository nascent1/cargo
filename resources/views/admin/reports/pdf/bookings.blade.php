@extends('admin.reports.pdf.layout')

@section('content')
    <table style="width: 100%; margin-bottom: 10px;">
        <tr>
            <td style="width: 50%;" class="header-title">{{ strtoupper(\App\Models\SystemConfig::getValue('platform_name', config('app.name', 'FERRY BOOKING PLATFORM'))) }}</td>
            <td style="width: 50%; text-align: right;" class="header-title">BOOKINGS REPORT</td>
        </tr>
    </table>

    <table class="summary-table">
        <tr>
            <td style="width: 50%;">
                Date Range: {{ $filters['date_from'] ?? 'All Time' }} to {{ $filters['date_to'] ?? 'Today' }}<br>
                Booking Status: {{ ucfirst($filters['booking_status'] ?? 'All') }}
            </td>
            <td style="width: 50%;">
                Route: {{ !empty($filters['route_id']) ? \App\Models\Route::find($filters['route_id'])?->name : 'All Routes' }}<br>
                Agent: {{ !empty($filters['agent_user_id']) ? \App\Models\User::find($filters['agent_user_id'])?->full_name ?? 'All Agents' : 'All Agents' }}
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead style="background-color: #f2f2f2; font-size: 8px;">
            <tr>
                <th>S/N</th>
                <th>Booking ID</th>
                <th>Passenger</th>
                <th>Gender</th>
                <th>Age</th>
                <th>Nationality</th>
                <th>(TSH)</th>
                <th>(USD)</th>
                <th>Rate</th>
                <th>Status</th>
                <th>Class</th>
                <th>Journey Date</th>
                <th>Booking Date</th>
                <th>Route</th>
                <th>Total (TSH)</th>
                <th>B. Status</th>
            </tr>
        </thead>
        <tbody style="font-size: 8px;">
            @forelse($data as $index => $row)
                @php $rowArray = (array) $row; @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-left font-mono">{{ $rowArray['ticket'] ?? '-' }}</td>
                    <td class="text-left">{{ $rowArray['passenger_name'] ?? '-' }}</td>
                    <td>{{ $rowArray['gender'] ?? 'N/A' }}</td>
                    <td>{{ $rowArray['type'] ?? 'N/A' }}</td>
                    <td>{{ $rowArray['nationality'] ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format($rowArray['tsh_amount'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($rowArray['usd_amount'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($rowArray['rate'] ?? 2800) }}</td>
                    <td>{{ str_replace('_', ' ', $rowArray['status'] ?? '-') }}</td>
                    <td>{{ $rowArray['class'] ?? '-' }}</td>
                    <td>{{ $rowArray['journey_date'] ?? '-' }}</td>
                    <td>{{ $rowArray['booking_date'] ?? '-' }}</td>
                    <td>{{ $rowArray['route'] ?? '-' }}</td>
                    <td class="text-right font-bold">{{ number_format($rowArray['total_tsh'] ?? 0) }}</td>
                    <td>{{ $rowArray['booking_status'] ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="16" style="text-align: center; padding: 20px;">No bookings found for selected criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
