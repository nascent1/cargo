@extends('admin.reports.pdf.layout')

@section('content')
    @php
        // Try to get Voyage from the first data record
        $voyageId = $data[0]['voyage_id'] ?? null;
        $inferredVoyage = $voyageId ? \App\Models\Voyage::find($voyageId) : null;

        $selectedRoute = $inferredVoyage ? $inferredVoyage->route : (!empty($filters['route_id']) ? \App\Models\Route::find($filters['route_id']) : null);
        $selectedFerry = $inferredVoyage ? $inferredVoyage->ferry : (!empty($filters['ferry_id']) ? \App\Models\Ferry::find($filters['ferry_id']) : null);
        
        $depDateStr = $inferredVoyage ? \Carbon\Carbon::parse($inferredVoyage->departure_date)->format('d M Y') : (!empty($filters['departure_date']) ? \Carbon\Carbon::parse($filters['departure_date'])->format('d M Y') : 'All Dates');
        $depTimeStr = $inferredVoyage ? \Carbon\Carbon::parse($inferredVoyage->departure_time)->format('H:i') : '--:--';

        
        // Sums
        $adults = 0;
        $children = 0;
        $infants = 0;
        foreach ($data as $row) {
            $cat = strtolower($row['age_category'] ?? '');
            if ($cat === 'child') $children++;
            elseif ($cat === 'infant') $infants++;
            else $adults++;
        }
        $totalPax = count($data);
        $cert = $selectedFerry ? $selectedFerry->certificates()->latest('issued_date')->first() : null;
    @endphp

    <table style="width: 100%; margin-bottom: 10px;">
        <tr>
            <td style="width: 50%;" class="header-title">{{ strtoupper(\App\Models\SystemConfig::getValue('platform_name', 'COMPANY NAME')) }}</td>
            <td style="width: 50%; text-align: right;" class="header-title">PASSENGER MANIFEST</td>
        </tr>
    </table>

    <table class="summary-table">
        <tr>
            <td style="width: 50%;">
                Departure Date: {{ $depDateStr }}<br>
                Departure Time: {{ $depTimeStr }}<br>
                <span class="text-green">From: {{ $selectedRoute?->origin?->name ?? 'Any' }}</span><br>
                <span class="text-green">To: {{ $selectedRoute?->destination?->name ?? 'Any' }}</span>
            </td>
            <td style="width: 50%;">
                <span class="text-blue" style="font-size: 14px; font-weight: bold;">Vessel: {{ $selectedFerry?->name ?? 'Any' }}</span><br>
                Registration No: {{ $selectedFerry?->port_of_registry ?? 'ZMA SMD 100256' }}<br>
                Port Of Registration: {{ $selectedFerry?->port_of_registry ?? 'Zanzibar' }}
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding-top: 10px;">
                <table style="width: 100%;">
                    <tr>
                        <td class="text-orange" style="width: 25%;">No. Adults: {{ $adults }}</td>
                        <td class="text-orange" style="width: 25%;">No. Children: {{ $children }}</td>
                        <td class="text-orange" style="width: 25%;">No. Infants: {{ $infants }}</td>
                        <td class="text-orange" style="width: 25%; text-align: right;">Total Passengers: {{ $totalPax }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="border-top: 1px solid #999; padding-top: 5px; margin-top: 5px;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 33%;">No. of Crews: {{ $cert->max_crew ?? '-' }}</td>
                        <td style="width: 33%;">Safe Manning Certificate Issued: {{ $cert && $cert->issued_date ? $cert->issued_date->format('d-m-Y') : '-' }}</td>
                        <td style="width: 33%; text-align: right;">Expires: {{ $cert && $cert->expiry_date ? $cert->expiry_date->format('d-m-Y') : '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No.</th>
                <th style="width: 18%;">TICKET No</th>
                <th style="width: 28%;" class="text-left">PASSENGER</th>
                <th style="width: 8%;">GENDER</th>
                <th style="width: 8%;">AGE</th>
                <th style="width: 12%;">NATIONALITY</th>
                <th style="width: 12%;">RESIDENT STATUS</th>
                <th style="width: 10%;">BOARDED</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row['ticket'] }}</td>
                    <td class="text-left">{{ strtoupper($row['passenger_name']) }}</td>
                    <td>{{ $row['gender'] }}</td>
                    <td>{{ $row['age_category'] ?? 'Adult' }}</td>
                    <td>{{ $row['nationality'] === 'Resident' ? 'Tanzanian' : 'Foreigner' }}</td>
                    <td>{{ $row['nationality'] }}</td>
                    <td>{{ $row['boarded'] ? 'YES' : 'NO' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No passengers found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
