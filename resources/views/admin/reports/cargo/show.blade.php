<x-layouts.admin>
<x-slot:title>{{ $definition['title'] }}</x-slot:title>
<x-slot:header>Reports</x-slot:header>

@php
    $uses = fn ($f) => in_array($f, $definition['filters'], true);
    $exportUrl = fn ($format) => request()->fullUrlWithQuery(['export' => $format]);
    $numericTypes = ['int', 'num', 'kg', 'money', 'pct'];
    $field = 'w-full px-3 py-2 text-sm rounded-xl border border-surface-300 bg-white focus:border-brand-500 focus:ring-2 focus:ring-brand-100 outline-none';
    $label = 'block text-[11px] font-semibold text-surface-600 uppercase tracking-wider mb-1';
    $today = today();
    $presets = [
        'Today' => [$today->toDateString(), $today->toDateString()],
        'This week' => [$today->copy()->startOfWeek()->toDateString(), $today->toDateString()],
        'This month' => [$today->copy()->startOfMonth()->toDateString(), $today->toDateString()],
        'Last month' => [$today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(), $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString()],
        'Last 30 days' => [$today->copy()->subDays(29)->toDateString(), $today->toDateString()],
    ];
@endphp

<div class="py-2 space-y-5">
    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
            <a href="{{ route('admin.reports.index') }}" class="text-xs font-semibold text-brand-700 hover:underline"><i class="fas fa-arrow-left text-[10px]"></i> All reports</a>
            <h1 class="text-2xl font-extrabold text-surface-900 tracking-tight mt-1 flex items-center gap-2"><i class="fas {{ $definition['icon'] }} text-brand-600 text-xl"></i> {{ $definition['title'] }}</h1>
            <p class="text-sm text-surface-600 mt-0.5 max-w-2xl">{{ $definition['description'] }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <select onchange="location.href=this.value" class="{{ $field }} w-56" aria-label="Switch report">
                @foreach($reports as $k => $r)
                    <option value="{{ route('admin.reports.show', $k) }}" @selected($k === $key)>{{ $r['title'] }}</option>
                @endforeach
            </select>
            @if($canExport)
                <a href="{{ $exportUrl('excel') }}" class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-xl border border-surface-300 bg-white text-surface-800 hover:bg-surface-100"><i class="fas fa-file-excel text-emerald-700"></i> Excel</a>
                <a href="{{ $exportUrl('pdf') }}" target="_blank" class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-xl border border-surface-300 bg-white text-surface-800 hover:bg-surface-100"><i class="fas fa-file-pdf text-red-700"></i> PDF</a>
            @endif
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.reports.show', $key) }}" class="bg-white rounded-2xl border border-surface-200 shadow-sm p-4 space-y-3">
        <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-3">
            @if($uses('dates'))
                <div>
                    <label class="{{ $label }}" for="date_from">From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="{{ $field }}">
                </div>
                <div>
                    <label class="{{ $label }}" for="date_to">To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="{{ $field }}">
                </div>
            @endif
            @if($uses('date_field'))
                <div>
                    <label class="{{ $label }}" for="date_field">Dates are</label>
                    <select id="date_field" name="date_field" class="{{ $field }}">
                        <option value="booked" @selected(($filters['date_field'] ?? '') !== 'departure')>Booking date</option>
                        <option value="departure" @selected(($filters['date_field'] ?? '') === 'departure')>Departure date</option>
                    </select>
                </div>
            @endif
            @if($uses('group_by'))
                <div>
                    <label class="{{ $label }}" for="group_by">Group by</label>
                    <select id="group_by" name="group_by" class="{{ $field }}">
                        @foreach($definition['group_by'] as $k => $v)
                            <option value="{{ $k }}" @selected(($filters['group_by'] ?? '') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if($uses('route_id'))
                <div>
                    <label class="{{ $label }}" for="route_id">Route</label>
                    <select id="route_id" name="route_id" class="{{ $field }}">
                        <option value="">All routes</option>
                        @foreach($options['routes'] as $id => $name)
                            <option value="{{ $id }}" @selected((string) ($filters['route_id'] ?? '') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if($uses('transport_unit_id'))
                <div>
                    <label class="{{ $label }}" for="transport_unit_id">Vessel</label>
                    <select id="transport_unit_id" name="transport_unit_id" class="{{ $field }}">
                        <option value="">All vessels</option>
                        @foreach($options['units'] as $id => $name)
                            <option value="{{ $id }}" @selected((string) ($filters['transport_unit_id'] ?? '') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if($uses('status'))
                <div>
                    <label class="{{ $label }}" for="status">Status</label>
                    <select id="status" name="status" class="{{ $field }}">
                        <option value="">Any status</option>
                        @foreach($options['statuses'] as $k => $v)
                            <option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if($uses('payment_status'))
                <div>
                    <label class="{{ $label }}" for="payment_status">Payment</label>
                    <select id="payment_status" name="payment_status" class="{{ $field }}">
                        <option value="">Paid or unpaid</option>
                        <option value="paid" @selected(($filters['payment_status'] ?? '') === 'paid')>Paid</option>
                        <option value="unpaid" @selected(($filters['payment_status'] ?? '') === 'unpaid')>Unpaid</option>
                    </select>
                </div>
            @endif
            @if($uses('attempt_status'))
                <div>
                    <label class="{{ $label }}" for="payment_status">Attempt status</label>
                    <select id="payment_status" name="payment_status" class="{{ $field }}">
                        <option value="">Any status</option>
                        @foreach($options['attemptStatuses'] as $k => $v)
                            <option value="{{ $k }}" @selected(($filters['payment_status'] ?? '') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if($uses('payment_method'))
                <div>
                    <label class="{{ $label }}" for="payment_method">Channel</label>
                    <select id="payment_method" name="payment_method" class="{{ $field }}">
                        <option value="">All channels</option>
                        @foreach($options['paymentMethods'] as $slug => $name)
                            <option value="{{ $slug }}" @selected(($filters['payment_method'] ?? '') === $slug)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if($uses('booked_by'))
                <div>
                    <label class="{{ $label }}" for="booked_by">Staff</label>
                    <select id="booked_by" name="booked_by" class="{{ $field }}">
                        <option value="">Everyone</option>
                        @foreach($options['staff'] as $id => $name)
                            <option value="{{ $id }}" @selected((string) ($filters['booked_by'] ?? '') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($uses('dates'))
                <span class="text-[11px] font-semibold text-surface-600 uppercase tracking-wider mr-1">Quick:</span>
                @foreach($presets as $name => [$from, $to])
                    <button type="button" onclick="setRange('{{ $from }}', '{{ $to }}')"
                        class="px-2.5 py-1 text-xs rounded-lg border {{ ($filters['date_from'] ?? '') === $from && ($filters['date_to'] ?? '') === $to ? 'border-brand-500 bg-brand-50 text-brand-800 font-semibold' : 'border-surface-300 text-surface-700 hover:bg-surface-100' }}">{{ $name }}</button>
                @endforeach
            @endif
            <div class="flex-1"></div>
            <a href="{{ route('admin.reports.show', $key) }}" class="px-3 py-2 text-xs font-semibold rounded-xl text-surface-700 hover:bg-surface-100">Reset</a>
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl bg-brand-600 hover:bg-brand-700 text-white"><i class="fas fa-play text-[10px]"></i> Run report</button>
        </div>
        @if($errors->any())
            <p class="text-sm text-red-700"><i class="fas fa-circle-exclamation"></i> {{ $errors->first() }}</p>
        @endif
    </form>

    {{-- Summary --}}
    <div class="grid grid-cols-2 md:grid-cols-{{ min(count($result['summary']), 5) }} gap-3">
        @foreach($result['summary'] as $card)
            <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-4">
                <p class="text-[11px] font-semibold text-surface-600 uppercase tracking-wider">{{ $card['label'] }}</p>
                <p class="text-2xl font-extrabold text-surface-900 mt-1">
                    @if($card['type'] === 'money')<span class="text-sm font-semibold text-surface-500">TZS</span> @endif{!! trim(view('admin.reports.cargo._cell', ['value' => $card['value'], 'type' => $card['type'], 'row' => [], 'pdf' => true])->render()) !!}
                </p>
            </div>
        @endforeach
    </div>

    {{-- Chart (single series) --}}
    @if(!empty($result['chart']) && count($result['chart']['labels']) > 1)
        <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-5">
            <h2 class="text-sm font-bold text-surface-900">{{ $result['chart']['title'] }}</h2>
            @php $chartHeight = !empty($result['chart']['horizontal']) ? max(160, count($result['chart']['labels']) * 32 + 40) : 240; @endphp
            <div class="relative mt-3" style="height: {{ $chartHeight }}px"><canvas id="report-chart" role="img" aria-label="{{ $result['chart']['title'] }}"></canvas></div>
        </div>
    @endif

    {{-- Tables --}}
    @foreach($result['sections'] as $section)
        <div class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b border-surface-200 flex items-baseline justify-between gap-3">
                <h2 class="text-sm font-bold text-surface-900">{{ $section['title'] }}</h2>
                <span class="text-xs text-surface-600">
                    {{ number_format(count($section['rows'])) }} {{ \Illuminate\Support\Str::plural('row', count($section['rows'])) }}
                    @if(isset($result['total_rows']) && $result['total_rows'] > count($section['rows']))
                        of {{ number_format($result['total_rows']) }} — export to see them all
                    @endif
                </span>
            </div>
            <div class="overflow-x-auto max-h-[70vh]">
                <table class="w-full text-sm">
                    <thead class="bg-surface-100 text-[11px] font-bold text-surface-600 uppercase tracking-wider sticky top-0 z-10">
                        <tr>
                            @foreach($section['columns'] as $col)
                                <th class="px-3 py-2.5 first:pl-5 last:pr-5 whitespace-nowrap {{ in_array($col['type'], $numericTypes) ? 'text-right' : 'text-left' }}">{{ $col['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-200">
                        @forelse($section['rows'] as $row)
                            <tr class="hover:bg-surface-100/60">
                                @foreach($section['columns'] as $col)
                                    <td class="px-3 py-2.5 first:pl-5 last:pr-5 {{ in_array($col['type'], $numericTypes) ? 'text-right tabular-nums' : '' }} {{ $col['type'] === 'text' && in_array($col['key'], ['reason', 'failure_reason']) ? 'max-w-xs' : 'whitespace-nowrap' }}">
                                        @include('admin.reports.cargo._cell', ['value' => $row[$col['key']] ?? null, 'type' => $col['type'], 'row' => $row])
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($section['columns']) }}" class="px-5 py-12 text-center text-sm text-surface-500"><i class="fas fa-inbox text-2xl text-surface-300 block mb-2"></i>Nothing matches these filters.</td></tr>
                        @endforelse
                    </tbody>
                    @if(!empty($section['totals']) && count($section['rows']))
                        <tfoot class="bg-surface-100 font-bold text-surface-900 border-t-2 border-surface-300">
                            <tr>
                                @foreach($section['columns'] as $col)
                                    <td class="px-3 py-2.5 first:pl-5 last:pr-5 {{ in_array($col['type'], $numericTypes) ? 'text-right tabular-nums' : '' }} whitespace-nowrap">
                                        @if(array_key_exists($col['key'], $section['totals']))
                                            @include('admin.reports.cargo._cell', ['value' => $section['totals'][$col['key']], 'type' => in_array($col['type'], $numericTypes) ? $col['type'] : 'text', 'row' => [], 'pdf' => true])
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @endforeach

    @foreach($result['notes'] ?? [] as $note)
        <p class="text-xs text-surface-600"><i class="fas fa-circle-info text-surface-400 mr-1"></i>{{ $note }}</p>
    @endforeach
</div>

@push('scripts')
<script>
function setRange(from, to) {
    document.getElementById('date_from').value = from;
    document.getElementById('date_to').value = to;
    document.getElementById('date_from').form.submit();
}

@if(!empty($result['chart']) && count($result['chart']['labels']) > 1)
(() => {
    const chart = @json($result['chart']);
    const fmt = v => (chart.type === 'money' ? 'TZS ' : '') + Number(v || 0).toLocaleString('en-US', { maximumFractionDigits: 0 });
    const horizontal = !!chart.horizontal;
    const valueAxis = { beginAtZero: true, grid: { color: '#e1e0d9', drawTicks: false }, border: { display: false }, ticks: { color: '#898781', font: { size: 11 }, padding: 8, maxTicksLimit: 5, callback: v => Number(v).toLocaleString('en-US') } };
    const categoryAxis = { grid: { display: false }, border: { color: '#e1e0d9' }, ticks: { color: '#52514e', font: { size: 11 }, autoSkip: !horizontal, maxRotation: 0 } };

    new Chart(document.getElementById('report-chart'), {
        type: 'bar',
        data: { labels: chart.labels, datasets: [{ data: chart.values, backgroundColor: '#2a78d6', hoverBackgroundColor: '#256abf', maxBarThickness: 24,
            borderRadius: horizontal ? { topRight: 4, bottomRight: 4 } : { topLeft: 4, topRight: 4 }, borderSkipped: horizontal ? 'left' : 'bottom' }] },
        options: {
            indexAxis: horizontal ? 'y' : 'x',
            responsive: true, maintainAspectRatio: false, animation: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false }, tooltip: { displayColors: false, backgroundColor: '#212529', padding: 10, callbacks: { label: c => fmt(horizontal ? c.parsed.x : c.parsed.y) } } },
            scales: horizontal ? { x: valueAxis, y: categoryAxis } : { x: categoryAxis, y: valueAxis },
        },
    });
})();
@endif
</script>
@endpush
</x-layouts.admin>
