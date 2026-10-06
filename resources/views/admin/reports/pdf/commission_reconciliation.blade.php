@extends('admin.reports.pdf.layout')
@section('content')
    <table style="width: 100%; margin-bottom: 10px;">
        <tr>
            <td style="width: 50%;" class="header-title">COMMISSION RECONCILIATION</td>
            <td style="width: 50%; text-align: right;">{{ $filters['date_from'] ?? 'Month Start' }} to {{ $filters['date_to'] ?? 'Today' }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead style="background-color: #f2f2f2;">
            <tr>
                <th class="text-left">Agent</th>
                <th>Code</th>
                <th>Rate</th>
                <th>Tickets</th>
                <th>Sales (TZS)</th>
                <th>Expected</th>
                <th>Paid</th>
                <th>Discrepancy</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
                <tr>
                    <td class="text-left" style="font-weight: bold;">{{ $row['agent_name'] }}</td>
                    <td style="font-size: 9px;">{{ $row['agent_code'] }}</td>
                    <td>{{ $row['commission_rate'] }}%</td>
                    <td>{{ number_format($row['total_tickets']) }}</td>
                    <td>{{ number_format($row['total_sales_tzs']) }}</td>
                    <td style="color: #0070c0;">{{ number_format($row['expected_commission_tzs']) }}</td>
                    <td class="text-green">{{ number_format($row['paid_commission_tzs']) }}</td>
                    <td style="color: {{ $row['discrepancy_tzs'] > 0 ? '#e36c09' : ($row['discrepancy_tzs'] < 0 ? '#ff0000' : '#666') }}; font-weight: bold;">
                        {{ $row['discrepancy_tzs'] >= 0 ? '+' : '' }}{{ number_format($row['discrepancy_tzs']) }}
                    </td>
                    <td style="color: {{ $row['status'] === 'Balanced' ? '#00b050' : ($row['status'] === 'Underpaid' ? '#e36c09' : '#ff0000') }}; font-weight: bold;">
                        {{ $row['status'] }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align: center; padding: 20px;">No commission data found.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
