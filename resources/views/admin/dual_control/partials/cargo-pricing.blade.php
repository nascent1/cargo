{{-- What a fare request does, for the checker: the prices asked for and the plan shown to the maker --}}
@php
    $after = $req->payload_after ?? [];
    $intent = $after['intent'] ?? [];
    $plan = $after['plan'] ?? [];
    $prices = $intent['prices'] ?? null;
    $before = $req->payload_before;
    $fmt = fn ($v) => $v === null || $v === '' ? '—' : number_format((float) $v, (float) $v == floor((float) $v) ? 0 : 2);
    $priceRows = [
        'price_tzs' => 'Flat price (TZS)', 'price_usd' => 'Flat price (USD)', 'price_per_kg_tzs' => 'Per kg (TZS)', 'price_per_kg_usd' => 'Per kg (USD)',
        'price_per_cbm_tzs' => 'Per cbm (TZS)', 'price_per_cbm_usd' => 'Per cbm (USD)', 'min_charge_tzs' => 'Minimum charge (TZS)', 'min_charge_usd' => 'Minimum charge (USD)',
        'value_charge_pct' => 'Value charge (%)',
    ];
    $levels = ['red' => 'border-red-200 bg-red-50 text-red-800', 'amber' => 'border-amber-200 bg-amber-50 text-amber-900', 'green' => 'border-emerald-200 bg-emerald-50 text-emerald-800'];
@endphp

<div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6 space-y-5">
    <div>
        <h3 class="text-lg font-bold text-slate-800">Fare change</h3>
        <p class="text-sm text-slate-600">
            {{ $after['cargo_type'] ?? '' }}{{ isset($after['route']) ? ' — ' . $after['route'] : '' }}{{ isset($after['vessel']) ? ' (' . $after['vessel'] . ')' : '' }}
            @if (!empty($after['fares_affected'])) · {{ $after['fares_affected'] }} fares @endif
        </p>
        @if (!empty($intent['reason']))<p class="text-sm text-slate-700 mt-1"><span class="font-semibold">Reason:</span> “{{ $intent['reason'] }}”</p>@endif
    </div>

    @if ($prices)
        <table class="w-full text-sm">
            <thead><tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-slate-100">
                <th class="text-left py-2">Price</th>@if ($before)<th class="text-right py-2">Now</th>@endif<th class="text-right py-2">{{ $before ? 'New' : 'Value' }}</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($priceRows as $key => $text)
                    @php $new = $prices[$key] ?? null; $old = $before[$key] ?? null; $changed = $before && abs((float) $old - (float) $new) > 0.0001; @endphp
                    @if (($new !== null && (float) $new != 0) || ($old !== null && (float) $old != 0))
                        <tr class="{{ $changed ? 'bg-amber-50/60' : '' }}">
                            <td class="py-2 text-slate-700">{{ $text }}</td>
                            @if ($before)<td class="py-2 text-right tabular-nums text-slate-500">{{ $fmt($old) }}</td>@endif
                            <td class="py-2 text-right tabular-nums {{ $changed ? 'font-bold text-slate-900' : 'text-slate-700' }}">{{ $fmt($new) }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
        <p class="text-xs text-slate-500">
            Starts {{ !empty($intent['starts_at']) ? \Carbon\Carbon::parse($intent['starts_at'])->format('d M Y H:i') : 'when approved' }}
            · {{ !empty($intent['ends_on']) ? 'last day ' . \Carbon\Carbon::parse($intent['ends_on'])->format('d M Y') : 'no end date' }}
        </p>
    @elseif (isset($intent['value_charge_pct']) && !isset($intent['fare_id']))
        <p class="text-sm text-slate-700">Set the value charge to <strong>{{ $fmt($intent['value_charge_pct']) }}%</strong> of the declared value.</p>
    @elseif (isset($intent['source_route_id']))
        <p class="text-sm text-slate-700">Copy fares from route #{{ $intent['source_route_id'] }} to route #{{ $intent['target_route_id'] }}{{ (float) ($intent['adjust_pct'] ?? 0) ? ', adjusted by ' . $fmt($intent['adjust_pct']) . '%' : '' }}; types already priced there are {{ ($intent['existing'] ?? 'skip') === 'skip' ? 'kept' : 'replaced' }}.</p>
    @elseif (isset($intent['fare_id']) && $req->operation_type === 'cargo_fare_end')
        <p class="text-sm text-slate-700">{{ !empty($intent['revoke']) ? 'Revoke' : 'End' }} fare #{{ $intent['fare_id'] }}{{ !empty($intent['ends_at']) ? ' after ' . \Carbon\Carbon::parse($intent['ends_at'])->subMinute()->format('d M Y H:i') : ' at approval' }}.</p>
    @endif

    @if (!empty($plan['series']))
        <div class="space-y-2">
            <h4 class="text-xs font-bold text-slate-400 uppercase">What the maker was shown</h4>
            <div class="space-y-2 {{ count($plan['series']) > 3 ? 'max-h-96 overflow-y-auto pr-1' : '' }}">
                @foreach ($plan['series'] as $s)
                    <div class="{{ count($plan['series']) > 1 ? 'rounded-xl border border-slate-200 p-3 space-y-1.5' : 'space-y-1.5' }}">
                        @if (count($plan['series']) > 1)<p class="text-xs font-bold text-slate-800">{{ $s['cargo_type'] }} — {{ $s['route'] }}{{ $s['vessel'] ? ' (' . $s['vessel'] . ')' : '' }}</p>@endif
                        @foreach ($s['alerts'] ?? [] as $a)
                            <div class="rounded-xl border px-3 py-2 text-sm {{ $levels[$a['level']] ?? 'border-sky-200 bg-sky-50 text-sky-900' }}">{{ $a['text'] }}</div>
                        @endforeach
                    </div>
                @endforeach
            </div>
            @if ($req->status === 'pending')
                <p class="text-xs text-slate-500"><i class="fas fa-shield-halved mr-1"></i>On approval the plan is worked out again. If the fares changed since it was submitted, approval stops and nothing is applied.</p>
            @endif
        </div>
    @endif
</div>
