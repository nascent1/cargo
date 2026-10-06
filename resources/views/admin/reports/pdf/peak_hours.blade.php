@extends('admin.reports.pdf.layout')
@section('content')
    <table style="width: 100%; margin-bottom: 10px;">
        <tr>
            <td style="width: 50%;" class="header-title">PEAK HOUR ANALYSIS</td>
            <td style="width: 50%; text-align: right;">{{ $filters['date_from'] ?? 'Today' }} to {{ $filters['date_to'] ?? 'Today' }}</td>
        </tr>
    </table>
    <table class="data-table">
        <thead style="background-color: #f2f2f2;">
            <tr><th>Hour</th><th>Intensity</th><th>Tickets</th><th>Revenue (TZS)</th><th>Revenue (USD)</th><th>Avg Fare</th></tr>
        </thead>
        <tbody>
            @forelse($data as $row)
                @if($row['ticket_count'] > 0)
                <tr style="{{ $row['intensity'] === 'peak' ? 'background-color: #fff2f2;' : '' }}">
                    <td>{{ $row['hour_label'] }}</td>
                    <td style="color: {{ $row['intensity'] === 'peak' ? '#e36c09' : '#666' }}; font-weight: bold;">{{ ucfirst($row['intensity']) }}</td>
                    <td>{{ number_format($row['ticket_count']) }}</td>
                    <td>{{ number_format($row['revenue_tzs']) }}</td>
                    <td>{{ number_format($row['revenue_usd'], 2) }}</td>
                    <td>{{ number_format($row['avg_fare']) }}</td>
                </tr>
                @endif
            @empty
                <tr><td colspan="6" style="text-align: center; padding: 20px;">No data found.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
