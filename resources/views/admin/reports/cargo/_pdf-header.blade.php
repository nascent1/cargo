@php
    $companyName = \App\Models\SystemConfig::getValue('company_name') ?? \App\Models\SystemConfig::getValue('platform_name', config('app.name'));
    $logoPath = public_path(ltrim(\App\Models\SystemConfig::getValue('company_logo_url', '/images/logo.png'), '/'));
    $logoSrc = is_file($logoPath) ? 'data:' . (mime_content_type($logoPath) ?: 'image/png') . ';base64,' . base64_encode(file_get_contents($logoPath)) : null;
@endphp
<table class="head">
    <tr>
        @if($logoSrc)
            <td style="width: 60px;"><img src="{{ $logoSrc }}" alt=""></td>
        @endif
        <td>
            <div class="company">{{ $companyName }}</div>
            <div class="title">{{ $title }}</div>
            @if(!empty($subtitle))<div class="subtitle">{{ $subtitle }}</div>@endif
        </td>
        <td class="meta">
            Generated {{ now()->format('d M Y H:i') }}<br>
            by {{ auth()->user()?->full_name ?? 'System' }}
        </td>
    </tr>
</table>
