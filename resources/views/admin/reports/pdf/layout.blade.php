<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Passenger Manifest</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 10px;
        }
        .header-title {
            color: #0070c0;
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
        }
        .summary-table {
            width: 100%;
            border: 1px solid #000;
            border-collapse: collapse;
            margin-bottom: 15px;
            margin-top: 5px;
        }
        .summary-table td {
            border: none;
            padding: 3px 5px;
            font-weight: bold;
        }
        .text-green { color: #00b050; }
        .text-orange { color: #e36c09; }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th, .data-table td {
            border: 1px solid #999;
            padding: 4px;
            text-align: center;
        }
        .data-table th {
            color: #666;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }
        .text-left { text-align: left !important; }
    </style>
</head>
<body>
    @php
        $companyName = \App\Models\SystemConfig::where('key_name', 'company_name')->value('value') ?? config('app.name', 'Ferry Booking Platform');
        $companyLogo = \App\Models\SystemConfig::where('key_name', 'company_logo_url')->value('value') ?? '/images/logo.png';
        // Base64 encode logo for DOMPDF if it's a local file, though public_path() is better for DOMPDF
        $logoPath = public_path(ltrim($companyLogo, '/'));
        $logoSrc = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';
    @endphp

    <div style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0070c0; padding-bottom: 10px;">
        @if($logoSrc)
            <img src="{{ $logoSrc }}" alt="{{ $companyName }}" style="max-height: 60px; margin-bottom: 10px;">
        @endif
        <div class="header-title" style="font-size: 18px;">{{ $companyName }}</div>
        <div style="font-size: 12px; color: #666;">Generated on {{ now()->format('d M Y H:i:s') }}</div>
    </div>

    @yield('content')
</body>
</html>
