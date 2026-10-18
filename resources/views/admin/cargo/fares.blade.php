<x-layouts.admin>
<x-slot:title>Cargo Fares</x-slot:title>
<x-slot:header>Cargo Fares</x-slot:header>

@include('admin.cargo.partials.ui')

<div x-data="faresPage(@js(['missing' => request()->boolean('missing_rate'), 'canExport' => auth()->user()->hasPermission('cargo-manage-fares') || auth()->user()->hasPermission('cargo-export-report')]))"
    x-on:fares-changed.window="refresh()" class="px-4 sm:px-6 lg:px-8 py-6 space-y-5">

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-surface-900 tracking-tight">Cargo Fares</h1>
            <p class="text-sm text-surface-600 mt-0.5">Each cargo type has one price per route. Changes are saved as new versions with a start and end, so past bookings always keep the price they were charged.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" @click="openEditor({ mode: 'copy' })" class="fp-btn"><i class="fas fa-clone text-xs"></i> Copy route fares</button>
            <button type="button" @click="openEditor({ mode: 'bulk', cargo_type_id: filters.type || null, route_id: filters.route || null })" class="fp-btn"><i class="fas fa-shield-halved text-xs"></i> Value charge…</button>
            <a x-show="canExport" :href="exportUrl()" class="fp-btn"><i class="fas fa-file-excel text-xs"></i> Fare history</a>
            <button type="button" @click="openEditor({ mode: 'create', cargo_type_id: filters.type || null, route_id: filters.route || null })" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl shadow-sm">
                <i class="fas fa-plus text-xs"></i> New fare
            </button>
        </div>
    </div>

    {{-- Routes with nothing to book --}}
    <template x-if="card && card.gaps.length && card.is_now">
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-3 text-sm text-red-800 flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex-1"><i class="fas fa-triangle-exclamation mr-1"></i>
                <strong x-text="card.gaps.join(', ')"></strong> <span x-text="card.gaps.length === 1 ? 'has' : 'have'"></span> no fares, so no cargo can be booked there.</div>
            <button type="button" @click="openEditor({ mode: 'copy' })" class="px-3 py-1.5 rounded-lg bg-white border border-red-200 font-semibold hover:bg-red-100">Copy fares from another route</button>
        </div>
    </template>

    {{-- Toolbar --}}
    <div class="bg-white rounded-2xl border border-surface-200/80 shadow-sm px-5 py-4 flex flex-col xl:flex-row xl:items-end gap-4">
        <div class="inline-flex rounded-xl border border-surface-200 p-1 bg-surface-50 self-start" role="tablist" aria-label="View">
            <button type="button" role="tab" :aria-selected="view === 'card'" @click="setView('card')" class="px-3 py-1.5 rounded-lg text-sm font-semibold" :class="view === 'card' ? 'bg-white shadow-sm text-brand-700' : 'text-surface-600'"><i class="fas fa-table-cells mr-1"></i> Rate card</button>
            <button type="button" role="tab" :aria-selected="view === 'list'" @click="setView('list')" class="px-3 py-1.5 rounded-lg text-sm font-semibold" :class="view === 'list' ? 'bg-white shadow-sm text-brand-700' : 'text-surface-600'"><i class="fas fa-list mr-1"></i> List</button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 flex-1">
            <div class="space-y-1">
                <label for="fp-type" class="text-[11px] font-semibold text-surface-500 uppercase tracking-wider">Cargo type</label>
                <select id="fp-type" x-model="filters.type" @change="refresh()" class="fp-field"><option value="">All types</option>
                    <template x-for="t in types" :key="t.id"><option :value="String(t.id)" x-text="t.name"></option></template></select>
            </div>
            <div class="space-y-1">
                <label for="fp-route" class="text-[11px] font-semibold text-surface-500 uppercase tracking-wider">Route</label>
                <select id="fp-route" x-model="filters.route" @change="refresh()" class="fp-field"><option value="">All routes</option>
                    <template x-for="r in routes" :key="r.id"><option :value="String(r.id)" x-text="r.name"></option></template></select>
            </div>
            <div class="space-y-1">
                <label for="fp-at" class="text-[11px] font-semibold text-surface-500 uppercase tracking-wider">Prices at</label>
                <div class="flex gap-2">
                    <input id="fp-at" type="datetime-local" x-model="filters.at" @change="refresh()" class="fp-field">
                    <button type="button" x-show="filters.at" @click="filters.at = ''; refresh()" class="px-2 text-sm text-brand-700 font-semibold whitespace-nowrap">Now</button>
                </div>
            </div>
            <div class="space-y-1" x-show="view === 'list'">
                <label for="fp-state" class="text-[11px] font-semibold text-surface-500 uppercase tracking-wider">Show</label>
                <select id="fp-state" x-model="filters.state" @change="refresh()" :disabled="!!filters.at" class="fp-field">
                    <option value="live">Current + scheduled</option><option value="current">Current</option><option value="scheduled">Scheduled</option>
                    <option value="history">History (ended, cancelled, revoked)</option><option value="all">All versions</option>
                </select>
            </div>
        </div>
        <label x-show="view === 'list'" class="flex items-center gap-2 text-sm text-surface-700 cursor-pointer select-none xl:pb-2">
            <input type="checkbox" x-model="filters.missing" @change="refresh()" class="rounded border-surface-300 text-brand-600"> Only fares with no rate
        </label>
    </div>

    <p x-show="filters.at" class="text-sm text-sky-800 bg-sky-50 border border-sky-200 rounded-xl px-4 py-2"><i class="fas fa-clock-rotate-left mr-1"></i>Showing the prices that apply to bookings made at <strong x-text="CargoUI.dateTime(filters.at)"></strong>.</p>

    {{-- ── Rate card ───────────────────────────────────────── --}}
    <div x-show="view === 'card'" class="bg-white rounded-2xl border border-surface-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-50 border-b border-surface-100">
                        <th class="text-left px-5 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider sticky left-0 bg-surface-50 min-w-[220px]">Cargo type</th>
                        <template x-for="r in (card?.routes || [])" :key="r.id">
                            <th class="text-left px-4 py-3 min-w-[190px]">
                                <span class="inline-flex px-2 py-0.5 rounded-md bg-brand-50 text-brand-800 border border-brand-100 text-[11px] font-bold font-mono" x-text="r.code || '—'"></span>
                                <div class="text-xs font-semibold text-surface-700 mt-1" x-text="r.name"></div>
                            </th>
                        </template>
                    </tr>
                </thead>
                <tbody x-show="loading && !card">
                    <tr><td colspan="4" class="px-6 py-14 text-center"><i class="fas fa-circle-notch fa-spin text-2xl text-brand-400"></i></td></tr>
                </tbody>
                    <template x-for="g in (card?.groups || [])" :key="g.category">
                        <tbody>
                            <tr class="bg-surface-50/70 border-y border-surface-100">
                                <td :colspan="card.routes.length + 1" class="px-5 py-2 text-[11px] font-bold uppercase tracking-wider text-surface-600" x-text="g.category.replace('_', ' ')"></td>
                            </tr>
                            <template x-for="t in g.types" :key="t.id">
                                <tr class="border-b border-surface-100 hover:bg-surface-50/50">
                                    <td class="px-5 py-3 sticky left-0 bg-white">
                                        <a :href="`/admin/cargo/types/${t.id}`" class="font-semibold text-surface-900 hover:text-brand-700" x-text="t.name"></a>
                                        <div class="text-[11px] text-surface-500" x-text="`per ${t.unit_label}`"></div>
                                    </td>
                                    <template x-for="r in card.routes" :key="r.id">
                                        <td class="px-4 py-2.5 align-top">
                                            <template x-if="t.cells[r.id]">
                                                <button type="button" @click="openSeries(t, r)" class="w-full text-left rounded-xl px-2.5 py-2 border transition-colors"
                                                    :class="cellClass(t.cells[r.id])" :aria-label="`${t.name} on ${r.name}: open versions`">
                                                    <template x-if="t.cells[r.id].fare">
                                                        <div>
                                                            <span class="font-bold tabular-nums text-surface-900" x-text="CargoUI.num(t.cells[r.id].fare.rate)"></span>
                                                            <span class="text-[11px] text-surface-500" x-text="`/ ${t.unit_label}`"></span>
                                                            <span x-show="t.cells[r.id].fare.value_charge_pct > 0" class="ml-1 text-[10px] font-bold text-violet-700 bg-violet-50 rounded px-1" x-text="`+${t.cells[r.id].fare.value_charge_pct}%`"></span>
                                                            <div x-show="t.cells[r.id].fare.missing_rate" class="text-[11px] font-semibold text-red-700"><i class="fas fa-circle-exclamation"></i> No rate — books free</div>
                                                            <div x-show="t.cells[r.id].fare.until" class="text-[11px] text-amber-700" x-text="`until ${CargoUI.dateTime(endShown(t.cells[r.id].fare.until))}`"></div>
                                                        </div>
                                                    </template>
                                                    <div x-show="!t.cells[r.id].fare" class="text-[11px] font-semibold text-surface-500">No price now</div>
                                                    <div x-show="t.cells[r.id].next" class="text-[11px] text-sky-800 mt-0.5"><i class="fas fa-arrow-right text-[9px]"></i>
                                                        <span x-text="`${CargoUI.num(t.cells[r.id].next?.rate)} from ${CargoUI.dateTime(t.cells[r.id].next?.from)}`"></span></div>
                                                    <div x-show="t.cells[r.id].vessels.length" class="text-[11px] text-surface-600 mt-0.5"><i class="fas fa-ship text-[9px]"></i>
                                                        <span x-text="`+${t.cells[r.id].vessels.length} vessel price${t.cells[r.id].vessels.length === 1 ? '' : 's'}`"></span></div>
                                                    <div x-show="t.cells[r.id].open_change" class="text-[11px] font-semibold mt-0.5" :class="t.cells[r.id].open_change?.status === 'scheduled' ? 'text-sky-800' : 'text-amber-800'">
                                                        <i class="fas" :class="t.cells[r.id].open_change?.status === 'scheduled' ? 'fa-clock' : 'fa-hourglass-half'"></i>
                                                        <span x-text="t.cells[r.id].open_change?.status === 'scheduled' ? 'Change scheduled' : 'Awaiting approval'"></span></div>
                                                </button>
                                            </template>
                                            <template x-if="!t.cells[r.id]">
                                                <button type="button" @click="openEditor({ mode: 'create', cargo_type_id: t.id, route_id: r.id })"
                                                    class="w-full rounded-xl border border-dashed border-surface-300 px-2.5 py-2 text-[12px] font-semibold text-surface-500 hover:border-brand-400 hover:text-brand-700"
                                                    :aria-label="`Add a fare for ${t.name} on ${r.name}`"><i class="fas fa-plus text-[10px]"></i> Add</button>
                                            </template>
                                        </td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </template>
            </table>
        </div>
    </div>

    {{-- ── List ────────────────────────────────────────────── --}}
    <div x-show="view === 'list'" class="bg-white rounded-2xl border border-surface-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-50 border-b border-surface-100 text-[11px] font-bold text-surface-500 uppercase tracking-wider">
                        <th class="text-left px-5 py-3">Cargo type</th>
                        <th class="text-left px-4 py-3">Route</th>
                        <th class="text-left px-4 py-3">Vessel</th>
                        <th class="text-right px-4 py-3">Rate (TZS)</th>
                        <th class="text-right px-4 py-3">Min charge</th>
                        <th class="text-right px-4 py-3">Value %</th>
                        <th class="text-left px-4 py-3">Valid</th>
                        <th class="text-center px-4 py-3">State</th>
                        <th class="text-right px-4 py-3">Lines</th>
                        <th class="text-center px-4 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr x-show="loading"><td colspan="10" class="px-6 py-10 text-center"><i class="fas fa-circle-notch fa-spin text-2xl text-brand-400"></i></td></tr>
                    <tr x-show="!loading && !list.length"><td colspan="10" class="px-6 py-10 text-center text-surface-500">No fares match.</td></tr>
                    <template x-for="f in (loading ? [] : list)" :key="f.id">
                        <tr class="border-b border-surface-100 hover:bg-surface-50/80" :class="f.missing_rate && f.state === 'current' ? 'bg-red-50/60' : ''">
                            <td class="px-5 py-3">
                                <a :href="`/admin/cargo/types/${f.cargo_type_id}`" class="font-semibold text-surface-900 hover:text-brand-700" x-text="f.cargo_type?.name"></a>
                                <div class="text-[11px] text-surface-500" x-text="`v${f.version} · per ${unitLabel(f.cargo_type?.measurement_unit)}`"></div>
                                <div x-show="f.missing_rate" class="text-[11px] font-semibold text-red-700"><i class="fas fa-circle-exclamation"></i> No rate — books free</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-0.5 rounded-md bg-brand-50 text-brand-800 border border-brand-100 text-[11px] font-bold font-mono" x-text="f.route?.code || '—'"></span>
                                <div class="text-xs text-surface-700 mt-0.5" x-text="f.route?.name"></div>
                            </td>
                            <td class="px-4 py-3 text-sm" :class="f.transport_unit ? 'text-surface-800 font-medium' : 'text-surface-500'" x-text="f.transport_unit?.name || 'All vessels'"></td>
                            <td class="px-4 py-3 text-right tabular-nums font-bold text-surface-900" x-text="CargoUI.num(rateOf(f))"></td>
                            <td class="px-4 py-3 text-right tabular-nums text-surface-600" x-text="f.min_charge_tzs ? CargoUI.num(f.min_charge_tzs) : '—'"></td>
                            <td class="px-4 py-3 text-right tabular-nums" :class="Number(f.value_charge_pct) > 0 ? 'font-semibold' : 'text-surface-400'" x-text="Number(f.value_charge_pct) > 0 ? `${Number(f.value_charge_pct)}%` : '—'"></td>
                            <td class="px-4 py-3 text-xs text-surface-700 whitespace-nowrap">
                                <div x-text="`from ${CargoUI.dateTime(f.effective_from)}`"></div>
                                <div x-text="f.effective_until ? `until ${CargoUI.dateTime(endShown(f.effective_until))}` : 'no end'" :class="f.effective_until ? 'text-amber-700' : 'text-surface-500'"></div>
                            </td>
                            <td class="px-4 py-3 text-center"><span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold border" :class="stateClass(f.state)" x-text="f.state"></span>
                                <div x-show="f.open_change" class="mt-1"><a :href="f.open_change?.url" class="text-[10px] font-semibold text-amber-800 hover:underline" x-text="f.open_change?.status === 'scheduled' ? 'change scheduled' : 'awaiting approval'"></a></div>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-surface-600" x-text="f.items_count"></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" @click="openSeries(f.cargo_type, f.route)" title="All versions" aria-label="All versions" class="fp-icon text-surface-600 bg-surface-50 border-surface-200"><i class="fas fa-clock-rotate-left text-xs"></i></button>
                                    <button type="button" x-show="['current','scheduled'].includes(f.state)" @click="openEditor({ mode: 'revise', fare: f })" title="Change" aria-label="Change fare" class="fp-icon text-brand-600 bg-brand-50 border-brand-100"><i class="fas fa-pen text-xs"></i></button>
                                    <button type="button" x-show="['current','scheduled'].includes(f.state)" @click="openEditor({ mode: 'end', fare: f })" title="End / revoke" aria-label="End or revoke fare" class="fp-icon text-red-500 bg-red-50 border-red-100"><i class="fas fa-stop text-xs"></i></button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Versions side panel ─────────────────────────────── --}}
    <div x-show="panel.open" x-cloak class="fixed inset-0 z-40" role="dialog" aria-modal="true" :aria-label="panel.title">
        <div class="absolute inset-0 bg-surface-950/40" @click="panel.open = false"></div>
        <aside class="absolute right-0 top-0 h-full w-full max-w-xl bg-white shadow-2xl overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-surface-100 px-5 py-4 flex items-start justify-between gap-3 z-10">
                <div>
                    <h2 class="text-base font-bold text-surface-900" x-text="panel.title"></h2>
                    <p class="text-xs text-surface-600">Every version, newest first. Booked lines keep the price of the version they used.</p>
                </div>
                <button type="button" @click="panel.open = false" class="h-8 w-8 rounded-lg flex items-center justify-center hover:bg-surface-100 text-surface-500" aria-label="Close"><i class="fas fa-times"></i></button>
            </div>
            <div class="px-5 py-4 space-y-3">
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="openEditor({ mode: 'create', cargo_type_id: panel.type?.id, route_id: panel.route?.id, lock_type: true, lock_route: true })" class="fp-btn"><i class="fas fa-plus text-xs"></i> New version / seasonal price</button>
                    <button type="button" @click="openEditor({ mode: 'create', cargo_type_id: panel.type?.id, route_id: panel.route?.id, lock_type: true, lock_route: true })" class="fp-btn"><i class="fas fa-ship text-xs"></i> Price for one vessel</button>
                </div>
                <div x-show="panel.loading" class="py-10 text-center"><i class="fas fa-circle-notch fa-spin text-2xl text-brand-400"></i></div>
                <template x-for="v in panel.versions" :key="v.id">
                    <div class="rounded-xl border p-3.5 space-y-1.5" :class="v.state === 'current' ? 'border-emerald-300 bg-emerald-50/40' : (v.state === 'scheduled' ? 'border-sky-200 bg-sky-50/40' : 'border-surface-200')">
                        <div class="flex items-center justify-between gap-2">
                            <div class="font-semibold text-surface-900">
                                <span x-text="v.price_label"></span>
                                <span x-show="v.transport_unit" class="ml-1 text-[11px] font-semibold text-surface-600" x-text="`· ${v.transport_unit?.name}`"></span>
                            </div>
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold border" :class="stateClass(v.deleted ? 'deleted' : v.state)" x-text="v.deleted ? 'deleted' : v.state"></span>
                        </div>
                        <div class="text-xs text-surface-700">
                            <span x-text="`v${v.version} · ${CargoUI.dateTime(v.effective_from)} → ${v.effective_until ? CargoUI.dateTime(endShown(v.effective_until)) : 'no end'}`"></span>
                            <span x-show="v.requested_from && Math.abs(new Date(v.requested_from) - new Date(v.effective_from)) > 60000" class="ml-1 text-amber-700 font-semibold" x-text="`(requested ${CargoUI.dateTime(v.requested_from)} — approved late)`"></span>
                        </div>
                        <div class="text-xs text-surface-600">
                            <span x-text="`By ${v.creator ? (v.creator.first_name + ' ' + v.creator.last_name) : 'system'}`"></span><span x-show="v.approved_by" x-text="` · approved by ${v.approved_by}`"></span>
                            <span x-show="v.change_reason" x-text="` · “${v.change_reason}”`"></span>
                        </div>
                        <div class="text-xs text-surface-600" x-show="v.lines > 0" x-text="`${v.lines} booked line${v.lines === 1 ? '' : 's'} · TZS ${CargoUI.num(v.revenue)}`"></div>
                        <div class="text-xs font-semibold text-red-700" x-show="v.state === 'revoked' && v.lines > 0"><i class="fas fa-flag"></i> Priced bookings on a revoked fare — review them.</div>
                        <div class="flex gap-2 pt-1" x-show="['current','scheduled'].includes(v.state) && !v.deleted">
                            <button type="button" @click="openEditor({ mode: 'revise', fare: v })" class="text-xs font-semibold text-brand-700 hover:underline"><i class="fas fa-pen"></i> Change</button>
                            <button type="button" @click="openEditor({ mode: 'end', fare: v })" class="text-xs font-semibold text-red-600 hover:underline"><i class="fas fa-stop"></i> End / revoke</button>
                            <button type="button" x-show="v.state === 'scheduled' && !v.lines" @click="openEditor({ mode: 'delete', fare: v })" class="text-xs font-semibold text-surface-600 hover:underline"><i class="fas fa-trash"></i> Delete</button>
                        </div>
                    </div>
                </template>
                <p x-show="!panel.loading && !panel.versions.length" class="text-sm text-surface-500 py-6 text-center">No fares yet for this type on this route.</p>
            </div>
        </aside>
    </div>
</div>

@include('admin.cargo.partials.fare-editor')

@push('styles')
<style>
    .fp-field { width: 100%; padding: .5rem .75rem; font-size: .875rem; border-radius: .75rem; border: 1px solid rgb(226 232 240); background: #fff; }
    .fp-btn { display: inline-flex; align-items: center; gap: .5rem; padding: .625rem 1rem; font-size: .875rem; font-weight: 600; border-radius: .75rem; border: 1px solid rgb(226 232 240); background: #fff; color: rgb(51 65 85); }
    .fp-btn:hover { background: rgb(248 250 252); }
    .fp-icon { height: 1.75rem; width: 1.75rem; border-radius: .5rem; display: inline-flex; align-items: center; justify-content: center; border-width: 1px; }
</style>
@endpush

@push('scripts')
<script>
function faresPage(cfg) {
    const { api } = window.CargoUI;
    const UNIT = { kg: 'kg', cbm: 'cbm', unit: 'piece', lane_meter: 'vehicle' };
    const BASIS = { kg: 'price_per_kg_tzs', cbm: 'price_per_cbm_tzs' };
    let viewPref = 'card';
    try { viewPref = localStorage.getItem('cargo-fares-view') || (cfg.missing ? 'list' : 'card'); } catch (e) {}

    return {
        view: cfg.missing ? 'list' : viewPref, canExport: cfg.canExport, loading: false,
        types: [], routes: [], card: null, list: [],
        filters: { type: '', route: '', at: '', state: 'live', missing: cfg.missing },
        panel: { open: false, title: '', type: null, route: null, versions: [], loading: false },

        async init() {
            try {
                const [types, routes] = await Promise.all([api('/admin/cargo/api/types'), api('/admin/cargo/api/routes')]);
                this.types = types; this.routes = routes;
            } catch (e) { window.notify?.(e.message, 'error'); }
            this.refresh();
        },
        setView(v) { this.view = v; try { localStorage.setItem('cargo-fares-view', v); } catch (e) {} this.refresh(); },
        params(extra = {}) {
            const p = new URLSearchParams();
            if (this.filters.type) p.set('cargo_type_id', this.filters.type);
            if (this.filters.route) p.set('route_id', this.filters.route);
            if (this.filters.at) p.set('at', this.filters.at);
            Object.entries(extra).forEach(([k, v]) => v !== '' && v !== null && v !== undefined && p.set(k, v));
            return p;
        },
        async refresh() {
            this.loading = true;
            try {
                if (this.view === 'card') {
                    const card = await api('/admin/cargo/api/fares/rate-card?' + this.params());
                    if (this.filters.type) card.groups = card.groups.map(g => ({ ...g, types: g.types.filter(t => String(t.id) === this.filters.type) })).filter(g => g.types.length);
                    this.card = card;
                } else {
                    this.list = await api('/admin/cargo/api/fares?' + this.params({ state: this.filters.at ? '' : this.filters.state, missing_rate: this.filters.missing ? 1 : '' }));
                }
                if (!this.card && this.view === 'list') {
                    this.card = await api('/admin/cargo/api/fares/rate-card'); // for the gaps banner
                }
            } catch (e) { window.notify?.(e.message, 'error'); }
            finally { this.loading = false; }
            if (this.panel.open) this.loadSeries();
        },
        exportUrl() { return '/admin/cargo/fares/export?' + this.params(); },
        unitLabel: u => UNIT[u] || u,
        rateOf(f) { return f[BASIS[f.cargo_type?.measurement_unit] || 'price_tzs']; },
        // Windows end at the first excluded moment; show the last minute they cover
        endShown: iso => new Date(new Date(iso).getTime() - 60000).toISOString(),
        cellClass(c) {
            if (c.fare?.missing_rate) return 'border-red-200 bg-red-50/60 hover:bg-red-50';
            if (!c.fare) return 'border-surface-200 bg-surface-50 hover:bg-surface-100';
            return 'border-transparent hover:border-brand-200 hover:bg-brand-50/40';
        },
        stateClass: s => ({
            current: 'bg-emerald-100 text-emerald-800 border-emerald-200', scheduled: 'bg-sky-100 text-sky-800 border-sky-200',
            expired: 'bg-surface-100 text-surface-700 border-surface-200', cancelled: 'bg-surface-100 text-surface-600 border-surface-200',
            revoked: 'bg-red-100 text-red-800 border-red-200', deleted: 'bg-surface-100 text-surface-500 border-surface-200',
        }[s] || 'bg-surface-100 text-surface-600 border-surface-200'),
        openEditor(detail) {
            window.dispatchEvent(new CustomEvent('fare-editor', { detail }));
        },
        openSeries(type, route) {
            this.panel = { open: true, title: `${type.name} — ${route.name}`, type, route, versions: [], loading: true };
            this.loadSeries();
        },
        async loadSeries() {
            this.panel.loading = true;
            try {
                this.panel.versions = await api(`/admin/cargo/api/fares/series?cargo_type_id=${this.panel.type.id}&route_id=${this.panel.route.id}`);
            } catch (e) { window.notify?.(e.message, 'error'); }
            finally { this.panel.loading = false; }
        },
    };
}
</script>
@endpush
</x-layouts.admin>
