<x-layouts.admin>
<x-slot:title>{{ $type->name }} — Cargo Type</x-slot:title>
<x-slot:header>Cargo Types</x-slot:header>

@include('admin.cargo.partials.ui')

@php
    $fareJson = fn ($f) => $f ? array_merge($f->only(['id', 'cargo_type_id', 'route_id', 'transport_unit_id', 'version', 'price_tzs', 'price_usd', 'price_per_kg_tzs', 'price_per_kg_usd',
        'price_per_cbm_tzs', 'price_per_cbm_usd', 'min_charge_tzs', 'min_charge_usd', 'value_charge_pct']), [
        'state' => $f->state,
        'effective_from' => $f->effective_from?->toIso8601String(),
        'effective_until' => $f->effective_until?->toIso8601String(),
        'route' => $f->route?->only(['id', 'name', 'code']),
        'transport_unit' => $f->transportUnit?->only(['id', 'name']),
        'price_label' => $label($f),
    ]) : null;
    $endShown = fn ($t) => $t?->copy()->subMinute()->format('d M Y H:i');
    $stateClass = fn ($s) => [
        'current' => 'bg-emerald-100 text-emerald-800 border-emerald-200', 'scheduled' => 'bg-sky-100 text-sky-800 border-sky-200',
        'revoked' => 'bg-red-100 text-red-800 border-red-200',
    ][$s] ?? 'bg-surface-100 text-surface-600 border-surface-200';
    $canFares = auth()->user()->hasPermission('cargo-manage-fares');
    $canTypes = auth()->user()->hasPermission('cargo-manage-types');
@endphp

<div x-data="{ tab: (location.hash || '#prices').slice(1), go(t) { this.tab = t; history.replaceState(null, '', '#' + t); } }"
    x-on:hashchange.window="tab = (location.hash || '#prices').slice(1)"
    x-on:fares-changed.window="setTimeout(() => location.reload(), 400)" x-on:types-changed.window="setTimeout(() => location.reload(), 400)"
    class="px-4 sm:px-6 lg:px-8 py-6 space-y-5">

    <a href="{{ route('admin.cargo.types') }}" class="inline-flex items-center gap-2 text-sm font-medium text-surface-600 hover:text-brand-700"><i class="fas fa-arrow-left text-xs"></i> Cargo types</a>

    {{-- Header --}}
    <div class="bg-white rounded-2xl border border-surface-200/80 shadow-sm p-5 flex flex-col lg:flex-row lg:items-center gap-4">
        <div class="h-14 w-14 rounded-2xl bg-surface-50 border border-surface-200 flex items-center justify-center text-3xl shrink-0">{{ $type->icon ?: '📦' }}</div>
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-extrabold text-surface-900 tracking-tight">{{ $type->name }}</h1>
                <code class="text-xs bg-surface-100 text-surface-700 px-2 py-0.5 rounded font-mono">{{ $type->code }}</code>
                <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold border {{ $type->status === 'active' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-surface-100 text-surface-600 border-surface-200' }}">{{ $type->status }}</span>
                <x-dual-control.pending-badge subject="cargo_type:{{ $type->id }}" />
            </div>
            <p class="text-sm text-surface-600 mt-1">
                {{ \Illuminate\Support\Str::headline($type->category) }} · priced per <strong>{{ $unitLabel }}</strong>
                @if ($type->requires_hazmat_declaration) · <span class="text-amber-700">⚠️ hazmat declaration</span>@endif
                @if ($type->is_fragile) · <span class="text-orange-700">🔶 fragile</span>@endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($canTypes)
                <button type="button" @click="$dispatch('type-editor', { type: @js($type->only(['id', 'name', 'code', 'icon', 'category', 'measurement_unit', 'status', 'lane_meters', 'max_weight_kg', 'requires_hazmat_declaration', 'is_fragile'])) })"
                    class="tp-btn"><i class="fas fa-pen text-xs"></i> Edit type</button>
            @endif
            @if ($canFares)
                <button type="button" @click="$dispatch('fare-editor', { mode: 'create', cargo_type_id: {{ $type->id }}, lock_type: true })"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl shadow-sm"><i class="fas fa-plus text-xs"></i> Add fare</button>
            @endif
        </div>
    </div>

    {{-- Key figures --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="tp-kpi">
            <p class="tp-kpi-label">Routes priced</p>
            <p class="tp-kpi-value {{ $kpis['routes_priced'] < $kpis['routes_total'] ? 'text-amber-700' : '' }}">{{ $kpis['routes_priced'] }} <span class="text-sm font-semibold text-surface-500">of {{ $kpis['routes_total'] }}</span></p>
            @if ($kpis['routes_priced'] < $kpis['routes_total'])<p class="text-xs text-amber-700">Can't be booked on the others</p>@endif
        </div>
        <div class="tp-kpi">
            <p class="tp-kpi-label">Current price</p>
            <p class="tp-kpi-value">
                @if ($kpis['rate_min'] === null) — @elseif ($kpis['rate_min'] == $kpis['rate_max']) {{ number_format($kpis['rate_min']) }} @else {{ number_format($kpis['rate_min']) }}–{{ number_format($kpis['rate_max']) }} @endif
            </p>
            <p class="text-xs text-surface-500">TZS per {{ $unitLabel }}</p>
        </div>
        <div class="tp-kpi">
            <p class="tp-kpi-label">Scheduled changes</p>
            <p class="tp-kpi-value {{ $kpis['upcoming'] ? 'text-sky-700' : '' }}">{{ $kpis['upcoming'] }}</p>
        </div>
        <div class="tp-kpi">
            <p class="tp-kpi-label">Last 30 days</p>
            <p class="tp-kpi-value">{{ number_format($kpis['lines_30d']) }} <span class="text-sm font-semibold text-surface-500">lines</span></p>
            <p class="text-xs text-surface-500">TZS {{ number_format($kpis['revenue_30d']) }}</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-surface-200 flex gap-6 overflow-x-auto" role="tablist">
        @foreach (['prices' => ['Prices', 'fa-tags'], 'history' => ['Price history', 'fa-clock-rotate-left'], 'bookings' => ['Bookings', 'fa-box'], 'details' => ['Details', 'fa-circle-info']] as $key => [$text, $icon])
            <button type="button" role="tab" @click="go('{{ $key }}')" :aria-selected="tab === '{{ $key }}'"
                :class="tab === '{{ $key }}' ? 'border-brand-500 text-brand-700' : 'border-transparent text-surface-500 hover:text-surface-700'"
                class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-semibold"><i class="fas {{ $icon }} mr-1.5"></i>{{ $text }}</button>
        @endforeach
    </div>

    {{-- ── Prices ──────────────────────────────────────────── --}}
    <div x-show="tab === 'prices'" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @foreach ($priceCards as $card)
            @php $r = $card['route']; $cur = $card['current']; $next = $card['next']; @endphp
            <div class="bg-white rounded-2xl border shadow-sm p-5 space-y-3 {{ $cur ? 'border-surface-200/80' : 'border-red-200' }}">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <span class="inline-flex px-2 py-0.5 rounded-md bg-brand-50 text-brand-800 border border-brand-100 text-[11px] font-bold font-mono">{{ $r->code }}</span>
                        <span class="ml-1 font-semibold text-surface-900">{{ $r->name }}</span>
                    </div>
                    <x-dual-control.pending-badge subject="{{ \App\Models\CargoFare::seriesKey($type->id, $r->id, null) }}" />
                </div>

                @if ($cur)
                    <div class="rounded-xl bg-emerald-50/50 border border-emerald-200 px-4 py-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-lg font-bold text-surface-900">{{ $label($cur) }}</p>
                                <p class="text-xs text-surface-600">v{{ $cur->version }} · since {{ $cur->effective_from->format('d M Y H:i') }}{{ $cur->effective_until ? ' · until ' . $endShown($cur->effective_until) : '' }}</p>
                                @if ($cur->basisRate($type->measurement_unit) <= 0)<p class="text-xs font-semibold text-red-700 mt-1"><i class="fas fa-circle-exclamation"></i> No {{ $unitLabel }} rate — this books for free</p>@endif
                            </div>
                            @if ($canFares)
                                <div class="flex gap-1.5 shrink-0">
                                    <button type="button" @click="$dispatch('fare-editor', { mode: 'revise', fare: @js($fareJson($cur)) })" class="tp-icon text-brand-600 bg-brand-50 border-brand-100" title="Change" aria-label="Change fare on {{ $r->name }}"><i class="fas fa-pen text-xs"></i></button>
                                    <button type="button" @click="$dispatch('fare-editor', { mode: 'end', fare: @js($fareJson($cur)) })" class="tp-icon text-red-500 bg-red-50 border-red-100" title="End / revoke" aria-label="End or revoke fare on {{ $r->name }}"><i class="fas fa-stop text-xs"></i></button>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                        <i class="fas fa-ban mr-1"></i>Not priced now — {{ $type->name }} can't be booked on this route{{ $next ? ' until ' . $next->effective_from->format('d M Y H:i') : '' }}.
                    </div>
                @endif

                @if ($next)
                    <div class="rounded-xl bg-sky-50/60 border border-sky-200 px-4 py-2.5 flex items-start justify-between gap-2">
                        <div class="text-sm">
                            <p class="font-semibold text-sky-900"><i class="fas fa-arrow-right text-xs"></i> {{ $label($next) }}</p>
                            <p class="text-xs text-sky-800">from {{ $next->effective_from->format('d M Y H:i') }}{{ $next->effective_until ? ' until ' . $endShown($next->effective_until) : '' }} · v{{ $next->version }}</p>
                        </div>
                        @if ($canFares)
                            <div class="flex gap-1.5 shrink-0">
                                <button type="button" @click="$dispatch('fare-editor', { mode: 'revise', fare: @js($fareJson($next)) })" class="tp-icon text-brand-600 bg-white border-brand-100" title="Change" aria-label="Change scheduled fare"><i class="fas fa-pen text-xs"></i></button>
                                <button type="button" @click="$dispatch('fare-editor', { mode: 'end', fare: @js($fareJson($next)) })" class="tp-icon text-red-500 bg-white border-red-100" title="Withdraw" aria-label="Withdraw scheduled fare"><i class="fas fa-stop text-xs"></i></button>
                            </div>
                        @endif
                    </div>
                @endif

                @foreach ($card['vessels'] as $vf)
                    <div class="rounded-xl border border-surface-200 px-4 py-2 flex items-center justify-between gap-2 text-sm">
                        <div><i class="fas fa-ship text-surface-400 mr-1"></i><strong>{{ $vf->transportUnit?->name }}</strong>: {{ $label($vf) }}
                            <span class="text-xs text-surface-500">({{ $vf->state }}{{ $vf->state === 'scheduled' ? ' from ' . $vf->effective_from->format('d M Y') : '' }})</span></div>
                        @if ($canFares)
                            <button type="button" @click="$dispatch('fare-editor', { mode: 'revise', fare: @js($fareJson($vf)) })" class="tp-icon text-brand-600 bg-brand-50 border-brand-100" title="Change" aria-label="Change vessel fare"><i class="fas fa-pen text-xs"></i></button>
                        @endif
                    </div>
                @endforeach

                @if ($canFares)
                    <div class="flex flex-wrap gap-3 pt-1 text-xs font-semibold">
                        <button type="button" @click="$dispatch('fare-editor', { mode: 'create', cargo_type_id: {{ $type->id }}, route_id: {{ $r->id }}, lock_type: true, lock_route: true })" class="text-brand-700 hover:underline">
                            <i class="fas fa-plus"></i> {{ $cur ? 'New price / seasonal fare' : 'Add fare' }}</button>
                        <button type="button" @click="$dispatch('fare-editor', { mode: 'create', cargo_type_id: {{ $type->id }}, route_id: {{ $r->id }}, lock_type: true, lock_route: true })" class="text-surface-600 hover:underline">
                            <i class="fas fa-ship"></i> Price for one vessel</button>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- ── Price history ───────────────────────────────────── --}}
    <div x-show="tab === 'history'" x-cloak class="bg-white rounded-2xl border border-surface-200/80 shadow-sm overflow-hidden">
        <div class="px-5 py-3 flex items-center justify-between border-b border-surface-100">
            <p class="text-sm text-surface-600">Every version of this type's fares. Booked lines keep the price of the version they used.</p>
            <a href="{{ route('admin.cargo.fares.export', ['cargo_type_id' => $type->id]) }}" class="tp-btn"><i class="fas fa-file-excel text-xs"></i> Export</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-50 border-b border-surface-100 text-[11px] font-bold text-surface-500 uppercase tracking-wider">
                        <th class="text-left px-5 py-3">Route / vessel</th>
                        <th class="text-left px-4 py-3">Price</th>
                        <th class="text-left px-4 py-3">Valid</th>
                        <th class="text-center px-4 py-3">State</th>
                        <th class="text-left px-4 py-3">Who & why</th>
                        <th class="text-right px-4 py-3">Lines</th>
                        <th class="text-right px-4 py-3">Revenue (TZS)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-100">
                    @forelse ($versions as $v)
                        @php
                            $state = $v->trashed() ? 'deleted' : $v->state;
                            $req = $v->dualControlRequest;
                            $late = $req?->commence_at && $v->effective_from->gt($req->commence_at->copy()->addMinute());
                            $lines = (int) ($usage[$v->id]->lines ?? 0);
                        @endphp
                        <tr class="{{ $state === 'current' ? 'bg-emerald-50/40' : '' }}">
                            <td class="px-5 py-3">
                                <span class="inline-flex px-2 py-0.5 rounded-md bg-brand-50 text-brand-800 border border-brand-100 text-[11px] font-bold font-mono">{{ $v->route?->code }}</span>
                                <div class="text-xs text-surface-600 mt-0.5">{{ $v->transportUnit?->name ?? 'All vessels' }} · v{{ $v->version }}</div>
                            </td>
                            <td class="px-4 py-3 font-semibold text-surface-900">{{ $label($v) }}</td>
                            <td class="px-4 py-3 text-xs text-surface-700 whitespace-nowrap">
                                {{ $v->effective_from->format('d M Y H:i') }} →<br>{{ $v->effective_until ? $endShown($v->effective_until) : 'no end' }}
                                @if ($late)<div class="text-amber-700 font-semibold">requested {{ $req->commence_at->format('d M H:i') }} (approved late)</div>@endif
                            </td>
                            <td class="px-4 py-3 text-center"><span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold border {{ $stateClass($state) }}">{{ $state }}</span></td>
                            <td class="px-4 py-3 text-xs text-surface-600">
                                {{ $v->creator?->full_name ?? 'System' }}
                                @if ($req && $req->checker_id && $req->checker_id !== $req->maker_id) · approved by {{ $req->checker?->full_name }}@endif
                                @if ($v->change_reason)<div class="text-surface-500">“{{ $v->change_reason }}”</div>@endif
                                @if ($v->ended_at)<div class="text-surface-500">ended {{ $v->ended_at->format('d M Y H:i') }} by {{ $v->ender?->full_name ?? 'system' }}</div>@endif
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums {{ $state === 'revoked' && $lines ? 'text-red-700 font-bold' : 'text-surface-700' }}">
                                {{ $lines }}@if ($state === 'revoked' && $lines) <i class="fas fa-flag" title="Booked on a revoked fare — review"></i>@endif
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-surface-700">{{ number_format((float) ($usage[$v->id]->revenue ?? 0)) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-10 text-center text-surface-500">No fares yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Bookings ────────────────────────────────────────── --}}
    <div x-show="tab === 'bookings'" x-cloak class="bg-white rounded-2xl border border-surface-200/80 shadow-sm overflow-hidden">
        <p class="px-5 py-3 text-sm text-surface-600 border-b border-surface-100">The latest 50 booked lines of {{ $type->name }}, with the rate each one was charged.</p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-50 border-b border-surface-100 text-[11px] font-bold text-surface-500 uppercase tracking-wider">
                        <th class="text-left px-5 py-3">Booking</th>
                        <th class="text-left px-4 py-3">Route / departure</th>
                        <th class="text-right px-4 py-3">Qty</th>
                        <th class="text-right px-4 py-3">Rate</th>
                        <th class="text-left px-4 py-3">Fare</th>
                        <th class="text-right px-4 py-3">Charged (TZS)</th>
                        <th class="text-center px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-100">
                    @forelse ($bookings as $item)
                        @php $c = $item->consignment; $svc = $c?->transportService; @endphp
                        <tr>
                            <td class="px-5 py-3">
                                @if ($c)<a href="{{ route('admin.cargo.bookings.show', $c) }}" class="font-semibold text-brand-700 hover:underline font-mono text-xs">{{ $c->consignment_number }}</a>@endif
                                <div class="text-[11px] text-surface-500">priced {{ $item->priced_at?->format('d M Y H:i') ?? $item->created_at?->format('d M Y H:i') }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs text-surface-700">
                                <span class="font-mono font-bold">{{ $c?->route?->code }}</span>
                                <div>{{ $svc ? \Carbon\Carbon::parse($svc->departure_date)->format('d M Y') . ' ' . substr((string) $svc->departure_time, 0, 5) : '—' }}</div>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $item->quantity }}@if ($type->measurement_unit === 'kg') <div class="text-[11px] text-surface-500">{{ (float) $item->weight_kg }} kg</div>@endif</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $item->unit_rate !== null ? number_format((float) $item->unit_rate) : '—' }}</td>
                            <td class="px-4 py-3 text-xs text-surface-600">
                                @if ($item->fare_id) #{{ $item->fare_id }}{{ $item->fare_version ? ' v' . $item->fare_version : '' }} @else — @endif
                                @if (($item->pricing_snapshot['source'] ?? null) === 'backfill')<div class="text-[11px] text-surface-400">before snapshots</div>@endif
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums font-semibold">{{ number_format((float) $item->final_fare) }}</td>
                            <td class="px-4 py-3 text-center text-xs">{{ \Illuminate\Support\Str::headline($c?->status ?? $item->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-10 text-center text-surface-500">No bookings of this type yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Details ─────────────────────────────────────────── --}}
    <div x-show="tab === 'details'" x-cloak class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-2xl border border-surface-200/80 shadow-sm p-5">
            <h2 class="text-sm font-bold text-surface-900 mb-3">Attributes</h2>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-xs text-surface-500">Category</dt><dd class="font-medium">{{ \Illuminate\Support\Str::headline($type->category) }}</dd></div>
                <div><dt class="text-xs text-surface-500">Priced per</dt><dd class="font-medium">{{ $unitLabel }}</dd></div>
                <div><dt class="text-xs text-surface-500">Lane metres used</dt><dd class="font-medium">{{ (float) $type->lane_meters }}</dd></div>
                <div><dt class="text-xs text-surface-500">Max weight per piece</dt><dd class="font-medium">{{ number_format((float) $type->max_weight_kg) }} kg</dd></div>
                <div><dt class="text-xs text-surface-500">Hazmat declaration</dt><dd class="font-medium">{{ $type->requires_hazmat_declaration ? 'Required' : 'No' }}</dd></div>
                <div><dt class="text-xs text-surface-500">Fragile</dt><dd class="font-medium">{{ $type->is_fragile ? 'Yes' : 'No' }}</dd></div>
                <div><dt class="text-xs text-surface-500">Created</dt><dd class="font-medium">{{ $type->created_at?->format('d M Y') ?? '—' }}</dd></div>
                <div><dt class="text-xs text-surface-500">Last changed</dt><dd class="font-medium">{{ $type->updated_at?->format('d M Y H:i') ?? '—' }}</dd></div>
            </dl>
        </div>
        <div class="bg-white rounded-2xl border border-surface-200/80 shadow-sm p-5">
            <h2 class="text-sm font-bold text-surface-900 mb-3">Change requests</h2>
            <ul class="divide-y divide-surface-100 text-sm">
                @forelse ($changes as $ch)
                    <li class="py-2.5 flex items-start justify-between gap-3">
                        <div>
                            <a href="{{ route('admin.dual-control.show', $ch) }}" class="font-semibold text-surface-800 hover:text-brand-700">{{ $ch->label() }}</a>
                            <p class="text-xs text-surface-500">#DC-{{ $ch->id }} · {{ $ch->maker?->full_name }} · {{ $ch->created_at->format('d M Y H:i') }}{{ $ch->commence_at ? ' · takes effect ' . $ch->commence_at->format('d M Y H:i') : '' }}</p>
                        </div>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold border {{ \App\Services\DualControl\DualControlPresenter::statusClasses($ch) }}">{{ $ch->statusLabel() }}</span>
                    </li>
                @empty
                    <li class="py-6 text-center text-surface-500">No changes recorded through dual control yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

@include('admin.cargo.partials.fare-editor')
@include('admin.cargo.partials.type-form')

@push('styles')
<style>
    .tp-btn { display: inline-flex; align-items: center; gap: .5rem; padding: .625rem 1rem; font-size: .875rem; font-weight: 600; border-radius: .75rem; border: 1px solid rgb(226 232 240); background: #fff; color: rgb(51 65 85); }
    .tp-btn:hover { background: rgb(248 250 252); }
    .tp-icon { height: 1.75rem; width: 1.75rem; border-radius: .5rem; display: inline-flex; align-items: center; justify-content: center; border-width: 1px; }
    .tp-kpi { background: #fff; border: 1px solid rgb(226 232 240 / .8); border-radius: 1rem; padding: 1rem 1.25rem; box-shadow: 0 1px 2px rgb(0 0 0 / .04); }
    .tp-kpi-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: rgb(100 116 139); }
    .tp-kpi-value { font-size: 1.5rem; font-weight: 800; color: rgb(15 23 42); margin-top: .25rem; }
</style>
@endpush
</x-layouts.admin>
