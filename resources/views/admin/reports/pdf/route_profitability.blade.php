@extends('admin.reports.pdf.layout')
@section('content')
    <table style="width: 100%; margin-bottom: 10px;">
        <tr>
            <td style="width: 50%;" class="header-title">ROUTE PROFITABILITY REPORT</td>
            <td style="width: 50%; text-align: right;">{{ $filters['date_from'] ?? 'All Time' }} to {{ $filters['date_to'] ?? 'Today' }}</td>
        </tr>
    </table>
    <table class="data-table">
        <thead style="background-color: #f2f2f2;">
            <tr><th>#</th><th class="text-left">Route</th><th>Voyages</th><th>Capacity</th><th>Sold</th><th>Load %</th><th>Revenue (TZS)</th><th>Rev/Seat</th><th>Score</th><th>Rating</th></tr>
        </thead>
        <tbody>
            @forelse($data as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="text-left" style="font-weight: bold;">{{ $row['route'] }}</td>
                    <td>{{ $row['voyage_count'] }}</td>
                    <td>{{ number_format($row['total_capacity']) }}</td>
                    <td>{{ number_format($row['total_sold']) }}</td>
                    <td style="color: {{ $row['load_factor'] >= 70 ? '#00b050' : ($row['load_factor'] >= 40 ? '#e36c09' : '#ff0000') }}; font-weight: bold;">{{ $row['load_factor'] }}%</td>
                    <td>{{ number_format($row['revenue_tzs']) }}</td>
                    <td>{{ number_format($row['revenue_per_seat']) }}</td>
                    <td style="font-weight: bold;">{{ $row['profitability_score'] }}</td>
                    <td style="color: {{ $row['rating'] === 'Excellent' ? '#00b050' : ($row['rating'] === 'Good' ? '#0070c0' : '#ff0000') }}; font-weight: bold;">{{ $row['rating'] }}</td>
                </tr>
            @empty
                <tr><td colspan="10" style="text-align: center; padding: 20px;">No data found.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
