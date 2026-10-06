@extends('admin.reports.pdf.layout')

@section('content')
    <h2 class="header-title" style="margin-bottom: 15px; text-align: center;">Float Ledger Report</h2>

    <div style="margin-bottom: 20px;">
        <strong>Filters Applied:</strong><br>
        Date Range: {{ $filters['start_date'] ?? 'All Time' }} to {{ $filters['end_date'] ?? 'Today' }}
    </div>

    <table class="data-table">
        <thead style="background-color: #f2f2f2;">
            <tr>
                <th>Date</th>
                <th>Agent</th>
                <th>Transaction Ref</th>
                <th>Type</th>
                <th>Amount (TZS)</th>
                <th>Balance (TZS)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
                @php $rowArray = (array) $row; @endphp
                <tr>
                    <td>{{ $rowArray['Date'] ?? '-' }}</td>
                    <td class="text-left">{{ $rowArray['Agent'] ?? '-' }}</td>
                    <td>{{ $rowArray['Reference'] ?? '-' }}</td>
                    <td>{{ $rowArray['Type'] ?? '-' }}</td>
                    <td class="text-right {{ ($rowArray['Type'] ?? '') === 'Credit' ? 'text-green' : 'text-orange' }}">
                        {{ $rowArray['Amount'] ?? 0 }}
                    </td>
                    <td class="text-right">{{ $rowArray['Running Balance'] ?? 0 }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">No ledger data found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
