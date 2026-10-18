<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cargo Manifest {{ $manifest['service_number'] ?? '' }}</title>
    @include('admin.reports.cargo._pdf-styles')
</head>
<body>
    @include('admin.reports.cargo._pdf-header', [
        'title' => 'Cargo Loading Manifest',
        'subtitle' => trim(($manifest['route_name'] ?: $manifest['route']) . ' · ' . $manifest['unit_name']
            . ($manifest['unit_registration'] ? ' (' . $manifest['unit_registration'] . ')' : '')
            . ' · Departs ' . \Carbon\Carbon::parse($manifest['departure_date'])->format('D d M Y') . ' ' . $manifest['departure_time']
            . ($manifest['service_number'] ? ' · ' . $manifest['service_number'] : '')),
    ])

    <table class="summary">
        <tr>
            <td><div class="summary-label">Bookings</div><div class="summary-value">{{ number_format($manifest['total_consignments']) }}</div></td>
            <td><div class="summary-label">Pieces</div><div class="summary-value">{{ number_format($manifest['total_pieces']) }}</div></td>
            <td><div class="summary-label">Weight</div><div class="summary-value">{{ number_format($manifest['total_weight_kg'], 1) }} kg</div>
                @if(!empty($manifest['capacity']['weight_kg']))<div class="small">of {{ number_format($manifest['capacity']['weight_kg']) }} kg</div>@endif</td>
            <td><div class="summary-label">Volume</div><div class="summary-value">{{ number_format($manifest['total_volume_cbm'], 2) }} cbm</div></td>
            <td><div class="summary-label">Vehicles · lane m</div><div class="summary-value">{{ $manifest['vehicle_count'] }} · {{ number_format($manifest['total_lane_meters'], 1) }}</div></td>
            <td><div class="summary-label">Hazmat · fragile</div><div class="summary-value">{{ $manifest['hazmat_count'] }} · {{ $manifest['fragile_count'] }}</div></td>
        </tr>
    </table>

    @if($manifest['awaiting_payment'] > 0)
        <p class="flag">{{ $manifest['awaiting_payment'] }} {{ \Illuminate\Support\Str::plural('booking', $manifest['awaiting_payment']) }} on this departure {{ $manifest['awaiting_payment'] === 1 ? 'is' : 'are' }} awaiting payment and NOT listed — do not load them.</p>
    @endif

    <h2>Cargo</h2>
    <table class="data">
        <thead>
            <tr>
                <th>#</th><th>Booking</th><th>Sender</th><th>Receiver</th><th>Item</th><th>Cargo type / description</th>
                <th class="num">Qty</th><th class="num">Weight (kg)</th><th class="num">CBM</th><th>Flags</th><th>Status</th><th>Loaded ✓</th>
            </tr>
        </thead>
        <tbody>
            @php $n = 0; @endphp
            @forelse($manifest['consignments'] as $c)
                @foreach($c['items'] as $i => $item)
                    <tr>
                        <td>{{ ++$n }}</td>
                        @if($i === 0)
                            <td rowspan="{{ count($c['items']) }}"><strong>{{ $c['consignment_number'] }}</strong>
                                @if($c['special_instructions'])<div class="small">Note: {{ $c['special_instructions'] }}</div>@endif</td>
                            <td rowspan="{{ count($c['items']) }}">{{ $c['sender'] }}<div class="small">{{ $c['sender_phone'] }}</div></td>
                            <td rowspan="{{ count($c['items']) }}">{{ $c['receiver'] }}<div class="small">{{ $c['receiver_phone'] }}</div></td>
                        @endif
                        <td class="small">{{ $item['barcode'] }}</td>
                        <td>{{ $item['type'] }}
                            @if($item['description'])<div class="small">{{ $item['description'] }}</div>@endif
                            @if($item['vehicle_reg_number'])<div class="small">Reg: <strong>{{ $item['vehicle_reg_number'] }}</strong>{{ $item['driver_name'] ? ' · Driver: ' . $item['driver_name'] : '' }}</div>@endif
                        </td>
                        <td class="num">{{ $item['quantity'] }}</td>
                        <td class="num">{{ number_format($item['weight'], 1) }}</td>
                        <td class="num">{{ $item['volume_cbm'] > 0 ? number_format($item['volume_cbm'], 2) : '' }}</td>
                        <td>@if($item['is_hazmat'])<span class="flag">HAZMAT</span> @endif @if($item['is_fragile'])<strong>FRAGILE</strong>@endif</td>
                        <td>{{ \Illuminate\Support\Str::headline($item['status']) }}</td>
                        <td>&#9744;</td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="12" class="empty">No paid bookings on this departure.</td></tr>
            @endforelse
        </tbody>
        @if($manifest['total_items'])
            <tfoot>
                <tr>
                    <td colspan="6">Total — {{ $manifest['total_consignments'] }} bookings, {{ $manifest['total_items'] }} lines</td>
                    <td class="num">{{ number_format($manifest['total_pieces']) }}</td>
                    <td class="num">{{ number_format($manifest['total_weight_kg'], 1) }}</td>
                    <td class="num">{{ number_format($manifest['total_volume_cbm'], 2) }}</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        @endif
    </table>

    @if(count($manifest['by_type']))
        <h2>By cargo type</h2>
        <table class="data" style="width: 55%;">
            <thead><tr><th>Cargo type</th><th class="num">Lines</th><th class="num">Pieces</th><th class="num">Weight (kg)</th><th class="num">CBM</th></tr></thead>
            <tbody>
                @foreach($manifest['by_type'] as $t)
                    <tr><td>{{ $t['type'] }}</td><td class="num">{{ $t['lines'] }}</td><td class="num">{{ $t['pieces'] }}</td><td class="num">{{ number_format($t['weight_kg'], 1) }}</td><td class="num">{{ number_format($t['volume_cbm'], 2) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="sign" style="width: 100%;">
        <tr>
            <td><div class="line">Prepared by (cargo officer)</div></td>
            <td><div class="line">Checked by (port / loading officer)</div></td>
            <td><div class="line">Received by (master / chief officer)</div></td>
        </tr>
    </table>
</body>
</html>
