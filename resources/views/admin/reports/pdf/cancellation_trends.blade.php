@extends('admin.reports.pdf.layout')
@section('content')
    <table style="width: 100%; margin-bottom: 10px;">
        <tr>
            <td style="width: 50%;" class="header-title">CANCELLATION TRENDS REPORT</td>
            <td style="width: 50%; text-align: right;">{{ $filters['date_from'] ?? '30 days ago' }} to {{ $filters['date_to'] ?? 'Today' }}</td>
        </tr>
    </table>

    @if(!empty($data['summary']))
    <table class="summary-table">
        <tr>
            <td>Total Cancellations: <span class="text-orange">{{ number_format($data['summary']['total_cancellations']) }}</span></td>
            <td>Fare Lost: TZS {{ number_format($data['summary']['total_fare_lost']) }}</td>
            <td>Penalties: <span class="text-green">TZS {{ number_format($data['summary']['total_penalties_collected']) }}</span></td>
            <td>Recovery: {{ $data['summary']['recovery_rate_percent'] }}%</td>
        </tr>
    </table>
    @endif

    <div style="margin-top: 10px; font-weight: bold; font-size: 12px; color: #0070c0;">DAILY BREAKDOWN</div>
    <table class="data-table" style="margin-top: 5px;">
        <thead style="background-color: #f2f2f2;">
            <tr><th>Date</th><th>Cancellations</th><th>Fare Lost (TZS)</th><th>Penalties (TZS)</th><th>Refunded (TZS)</th></tr>
        </thead>
        <tbody>
            @forelse($data['daily'] ?? [] as $row)
                <tr>
                    <td>{{ $row['cancel_date'] }}</td>
                    <td style="color: #ff0000; font-weight: bold;">{{ number_format($row['cancellation_count']) }}</td>
                    <td>{{ number_format($row['total_fare_lost']) }}</td>
                    <td class="text-green">{{ number_format($row['total_penalties']) }}</td>
                    <td>{{ number_format($row['total_refunded']) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align: center; padding: 20px;">No cancellation data found.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if(!empty($data['by_route']))
    <div style="margin-top: 15px; font-weight: bold; font-size: 12px; color: #0070c0;">BY ROUTE</div>
    <table class="data-table" style="margin-top: 5px;">
        <thead style="background-color: #f2f2f2;">
            <tr><th class="text-left">Route</th><th>Cancellations</th><th>Penalties (TZS)</th></tr>
        </thead>
        <tbody>
            @foreach($data['by_route'] as $row)
                <tr>
                    <td class="text-left" style="font-weight: bold;">{{ $row['route'] }}</td>
                    <td style="color: #ff0000;">{{ number_format($row['count']) }}</td>
                    <td class="text-green">{{ number_format($row['penalties']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif
@endsection
