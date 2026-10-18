<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $definition['title'] }}</title>
    @include('admin.reports.cargo._pdf-styles')
</head>
<body>
    @include('admin.reports.cargo._pdf-header', ['title' => $definition['title'], 'subtitle' => $filterSummary])

    @php $numericTypes = ['int', 'num', 'kg', 'money', 'pct']; @endphp

    <table class="summary">
        <tr>
            @foreach($result['summary'] as $card)
                <td>
                    <div class="summary-label">{{ $card['label'] }}</div>
                    <div class="summary-value">@if($card['type'] === 'money')TZS @endif{!! trim(view('admin.reports.cargo._cell', ['value' => $card['value'], 'type' => $card['type'], 'row' => [], 'pdf' => true])->render()) !!}</div>
                </td>
            @endforeach
        </tr>
    </table>

    @foreach($result['sections'] as $section)
        <h2>{{ $section['title'] }}</h2>
        <table class="data">
            <thead>
                <tr>
                    @foreach($section['columns'] as $col)
                        <th class="{{ in_array($col['type'], $numericTypes) ? 'num' : '' }}">{{ $col['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($section['rows'] as $row)
                    <tr>
                        @foreach($section['columns'] as $col)
                            <td class="{{ in_array($col['type'], $numericTypes) ? 'num' : '' }}">@include('admin.reports.cargo._cell', ['value' => $row[$col['key']] ?? null, 'type' => $col['type'], 'row' => $row, 'pdf' => true])</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($section['columns']) }}" class="empty">Nothing matches these filters.</td></tr>
                @endforelse
            </tbody>
            @if(!empty($section['totals']) && count($section['rows']))
                <tfoot>
                    <tr>
                        @foreach($section['columns'] as $col)
                            <td class="{{ in_array($col['type'], $numericTypes) ? 'num' : '' }}">
                                @if(array_key_exists($col['key'], $section['totals']))
                                    @include('admin.reports.cargo._cell', ['value' => $section['totals'][$col['key']], 'type' => in_array($col['type'], $numericTypes) ? $col['type'] : 'text', 'row' => [], 'pdf' => true])
                                @endif
                            </td>
                        @endforeach
                    </tr>
                </tfoot>
            @endif
        </table>
    @endforeach

    @foreach($result['notes'] ?? [] as $note)
        <p class="note">{{ $note }}</p>
    @endforeach
</body>
</html>
