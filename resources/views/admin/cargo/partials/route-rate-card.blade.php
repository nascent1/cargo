{{--
    A route's prices: every active cargo type with the fare in force, the next change and vessel prices.
    Needs $route and $priceCard (CargoFareService::routeRateCard). Changes open the shared fare editor.
--}}
@php
    $fareService = app(\App\Services\Cargo\CargoFareService::class);
    $canFares = auth()->user()->hasPermission('cargo-manage-fares');
    $unitLabel = ['kg' => 'kg', 'cbm' => 'cbm', 'unit' => 'piece', 'lane_meter' => 'vehicle'];
    $priced = $priceCard->filter(fn ($c) => $c['current'])->count();
    $fareJson = fn ($f, $type) => array_merge($f->only(['id', 'cargo_type_id', 'route_id', 'transport_unit_id', 'version', 'price_tzs', 'price_usd', 'price_per_kg_tzs', 'price_per_kg_usd',
        'price_per_cbm_tzs', 'price_per_cbm_usd', 'min_charge_tzs', 'min_charge_usd', 'value_charge_pct']), [
        'state' => $f->state, 'effective_from' => $f->effective_from?->toIso8601String(), 'effective_until' => $f->effective_until?->toIso8601String(),
        'route' => $route->only(['id', 'name', 'code']), 'transport_unit' => $f->transportUnit?->only(['id', 'name']),
        'price_label' => $fareService->priceLabel($type, $f->prices()),
    ]);
@endphp

<div x-on:fares-changed.window="setTimeout(() => location.reload(), 400)" class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h3 class="font-bold text-slate-800 text-lg">Cargo prices on this route</h3>
            <p class="text-sm text-slate-500">{{ $priced }} of {{ $priceCard->count() }} cargo types have a price in force. Types without one can't be booked here.</p>
        </div>
        @if ($canFares)
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="$dispatch('fare-editor', { mode: 'copy', route_id: {{ $route->id }}, lock_route: true })" class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 rounded-xl font-semibold text-sm border border-slate-200">
                    <i class="fas fa-clone mr-1"></i> Copy prices from another route
                </button>
                <a href="{{ route('admin.cargo.fares') }}" class="px-4 py-2 bg-brand-50 text-brand-700 hover:bg-brand-100 rounded-xl font-semibold text-sm border border-brand-200">
                    <i class="fas fa-table-cells mr-1"></i> Rate card
                </a>
            </div>
        @endif
    </div>

    <div class="overflow-x-auto border border-slate-200 rounded-2xl">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase">
                    <th class="text-left px-5 py-3">Cargo type</th>
                    <th class="text-left px-4 py-3">Price now</th>
                    <th class="text-left px-4 py-3">Next change</th>
                    <th class="text-left px-4 py-3">Vessel prices</th>
                    <th class="text-right px-4 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($priceCard as $row)
                    @php $type = $row['type']; $cur = $row['current']; $next = $row['next']; @endphp
                    <tr class="{{ $cur ? '' : 'bg-red-50/40' }}">
                        <td class="px-5 py-3">
                            <a href="{{ route('admin.cargo.types.show', $type) }}" class="font-semibold text-slate-900 hover:text-brand-700">{{ $type->name }}</a>
                            <div class="text-[11px] text-slate-500">per {{ $unitLabel[$type->measurement_unit] ?? $type->measurement_unit }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if ($cur)
                                <span class="font-bold text-slate-900">{{ $fareService->priceLabel($type, $cur->prices()) }}</span>
                                <div class="text-[11px] text-slate-500">v{{ $cur->version }} since {{ $cur->effective_from->format('d M Y') }}</div>
                                @if ($cur->basisRate($type->measurement_unit) <= 0)<div class="text-[11px] font-semibold text-red-700">No rate — books free</div>@endif
                            @else
                                <span class="text-xs font-semibold text-red-700"><i class="fas fa-ban"></i> Not bookable</span>
                            @endif
                            <x-dual-control.pending-badge subject="{{ \App\Models\CargoFare::seriesKey($type->id, $route->id, null) }}" class="mt-1" />
                        </td>
                        <td class="px-4 py-3 text-xs">
                            @if ($next)
                                <span class="font-semibold text-sky-800">{{ $fareService->priceLabel($type, $next->prices()) }}</span>
                                <div class="text-sky-700">from {{ $next->effective_from->format('d M Y H:i') }}</div>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-700">
                            @forelse ($row['vessels'] as $vf)
                                <div><i class="fas fa-ship text-slate-400"></i> {{ $vf->transportUnit?->name }}: {{ $fareService->priceLabel($type, $vf->prices()) }}</div>
                            @empty
                                <span class="text-slate-400">—</span>
                            @endforelse
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if ($canFares)
                                @if ($cur)
                                    <button type="button" @click="$dispatch('fare-editor', { mode: 'revise', fare: @js($fareJson($cur, $type)) })" class="text-xs font-semibold text-brand-700 hover:underline mr-2"><i class="fas fa-pen"></i> Change</button>
                                    <button type="button" @click="$dispatch('fare-editor', { mode: 'end', fare: @js($fareJson($cur, $type)) })" class="text-xs font-semibold text-red-600 hover:underline"><i class="fas fa-stop"></i> End</button>
                                @else
                                    <button type="button" @click="$dispatch('fare-editor', { mode: 'create', cargo_type_id: {{ $type->id }}, route_id: {{ $route->id }}, lock_type: true, lock_route: true })" class="text-xs font-semibold text-brand-700 hover:underline"><i class="fas fa-plus"></i> Add fare</button>
                                @endif
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
