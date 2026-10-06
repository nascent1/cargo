@extends('admin.reports.pdf.layout')

@section('content')
    <h2 class="header-title" style="margin-bottom: 15px; text-align: center;">Occupancy Report</h2>

    <div style="margin-bottom: 20px;">
        <strong>Filters Applied:</strong><br>
        Date Range: {{ $filters['start_date'] ?? 'All Time' }} to {{ $filters['end_date'] ?? 'Today' }}
    </div>

    <table class="data-table">
        <thead style="background-color: #f2f2f2;">
            <tr>
                <th>Date</th>
                <th>Voyage</th>
                <th>Vessel</th>
                <th>Capacity</th>
                <th>Booked</th>
                <th>Occupancy %</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
                @php $rowArray = (array) $row; @endphp
                <tr>
                    <td>{{ $rowArray['Date'] ?? '-' }}</td>
                    <td>{{ $rowArray['Voyage'] ?? '-' }}</td>
                    <td>{{ $rowArray['Vessel'] ?? '-' }}</td>
                    <td>{{ $rowArray['Capacity'] ?? 0 }}</td>
                    <td>{{ $rowArray['Booked'] ?? 0 }}</td>
                    <td class="{{ (float)($rowArray['Occupancy %'] ?? 0) >= 80 ? 'text-green' : '' }}">
                        {{ $rowArray['Occupancy %'] ?? 0 }}%
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">No occupancy data found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
