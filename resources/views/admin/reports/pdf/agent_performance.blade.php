@extends('admin.reports.pdf.layout')

@section('content')
    <table style="width: 100%; margin-bottom: 10px;">
        <tr>
            <td style="width: 50%;" class="header-title">{{ strtoupper(\App\Models\SystemConfig::getValue('platform_name', config('app.name', 'FERRY BOOKING PLATFORM'))) }}</td>
            <td style="width: 50%; text-align: right;" class="header-title">AGENT PERFORMANCE REPORT</td>
        </tr>
    </table>

    <table class="summary-table">
        <tr>
            <td style="width: 50%;">
                Date Range: {{ $filters['date_from'] ?? 'All Time' }} to {{ $filters['date_to'] ?? 'Today' }}
            </td>
            <td style="width: 50%;">
                Agent: {{ !empty($filters['agent_user_id']) ? \App\Models\User::find($filters['agent_user_id'])?->full_name ?? 'All Agents' : 'All Agents' }}
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead style="background-color: #f2f2f2;">
            <tr>
                <th>Agent Name</th>
                <th>Total Bookings</th>
                <th>Revenue (TZS)</th>
                <th>Commission (TZS)</th>
                <th>Deposit Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
                @php $rowArray = (array) $row; @endphp
                <tr>
                    <td class="text-left">{{ $rowArray['Agent'] ?? '-' }}</td>
                    <td>{{ $rowArray['Total Bookings'] ?? 0 }}</td>
                    <td class="text-right">{{ $rowArray['Revenue (TZS)'] ?? 0 }}</td>
                    <td class="text-right text-orange">{{ $rowArray['Commission Earned'] ?? 0 }}</td>
                    <td>{{ $rowArray['Deposit Status'] ?? 'Healthy' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px;">No agent performance data found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
