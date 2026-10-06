@extends('admin.reports.pdf.layout')
@section('content')
    <table style="width: 100%; margin-bottom: 10px;">
        <tr>
            <td style="width: 50%;" class="header-title">PAYMENT CHANNEL ANALYSIS</td>
            <td style="width: 50%; text-align: right;">{{ $filters['date_from'] ?? 'Today' }} to {{ $filters['date_to'] ?? 'Today' }}</td>
        </tr>
    </table>
    <table class="data-table">
        <thead style="background-color: #f2f2f2;">
            <tr><th class="text-left">Channel</th><th>Tickets</th><th>Revenue (TZS)</th><th>Revenue (USD)</th><th>Share %</th><th>Growth</th></tr>
        </thead>
        <tbody>
            @forelse($data as $row)
                <tr>
                    <td class="text-left" style="text-transform: capitalize;">{{ str_replace('_', ' ', $row['channel']) }}</td>
                    <td>{{ number_format($row['ticket_count']) }}</td>
                    <td>{{ number_format($row['revenue_tzs']) }}</td>
                    <td>{{ number_format($row['revenue_usd'], 2) }}</td>
                    <td>{{ $row['revenue_share_percent'] }}%</td>
                    <td style="color: {{ $row['growth_percent'] >= 0 ? '#00b050' : '#e36c09' }}">{{ $row['growth_percent'] >= 0 ? '+' : '' }}{{ $row['growth_percent'] }}%</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align: center; padding: 20px;">No data found.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
