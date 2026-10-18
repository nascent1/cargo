<x-layouts.admin>
<x-slot:title>Cargo Bookings</x-slot:title>
<x-slot:header>Cargo Bookings</x-slot:header>

@include('admin.cargo.partials.ui')

@php
    $field = 'w-full px-3 py-2 text-sm rounded-xl border border-surface-300 bg-white focus:border-brand-500 focus:ring-2 focus:ring-brand-100 outline-none';
    $label = 'block text-[11px] font-semibold text-surface-600 uppercase tracking-wider mb-1';
@endphp

<div class="py-2 space-y-4">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-surface-900 tracking-tight">Cargo bookings</h1>
            <p class="text-sm text-surface-600 mt-0.5">Search, filter and open any booking. <span id="result-count" class="font-semibold text-surface-800"></span></p>
        </div>
        <div class="flex items-center gap-2">
            @if($canExport)
                <a id="export-link" href="{{ route('admin.cargo.consignments.export') }}" class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-xl border border-surface-300 bg-white text-surface-800 hover:bg-surface-100">
                    <i class="fas fa-file-excel text-emerald-700"></i> Export to Excel
                </a>
            @endif
            @if(auth()->user()->hasPermission('cargo-book'))
                <a href="{{ route('admin.cargo.booking') }}" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl bg-brand-600 hover:bg-brand-700 text-white shadow-sm">
                    <i class="fas fa-plus"></i> New booking
                </a>
            @endif
        </div>
    </div>

    {{-- Status tabs --}}
    <div class="flex gap-1 overflow-x-auto pb-1 -mx-1 px-1" role="tablist" aria-label="Status" id="status-tabs">
        <button type="button" data-status="" class="status-tab">All <span class="tab-count" data-count=""></span></button>
        @foreach($statuses as $s)
            <button type="button" data-status="{{ $s }}" class="status-tab">{{ $s === 'pending_payment' ? 'Awaiting payment' : \Illuminate\Support\Str::headline($s) }} <span class="tab-count" data-count="{{ $s }}"></span></button>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-4 space-y-3">
        <div class="relative">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-surface-400 text-sm"></i>
            <input type="search" id="f-search" placeholder="Booking or tracking number, sender or receiver name, phone number…"
                class="{{ $field }} pl-9 py-2.5" autocomplete="off">
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-7 gap-3">
            <div>
                <label class="{{ $label }}" for="f-date_field">Dates are</label>
                <select id="f-date_field" class="{{ $field }}">
                    <option value="booked">Booking date</option>
                    <option value="departure">Departure date</option>
                </select>
            </div>
            <div>
                <label class="{{ $label }}" for="f-date_from">From</label>
                <input type="date" id="f-date_from" class="{{ $field }}">
            </div>
            <div>
                <label class="{{ $label }}" for="f-date_to">To</label>
                <input type="date" id="f-date_to" class="{{ $field }}">
            </div>
            <div>
                <label class="{{ $label }}" for="f-route_id">Route</label>
                <select id="f-route_id" class="{{ $field }}">
                    <option value="">All routes</option>
                    @foreach($routes as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="{{ $label }}" for="f-payment_status">Payment</label>
                <select id="f-payment_status" class="{{ $field }}">
                    <option value="">Paid or unpaid</option>
                    <option value="paid">Paid</option>
                    <option value="unpaid">Unpaid</option>
                </select>
            </div>
            <div>
                <label class="{{ $label }}" for="f-payment_method">Channel</label>
                <select id="f-payment_method" class="{{ $field }}">
                    <option value="">All channels</option>
                    @foreach($paymentMethods as $m)<option value="{{ $m->slug }}">{{ $m->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="{{ $label }}" for="f-booked_by">Booked by</label>
                <select id="f-booked_by" class="{{ $field }}">
                    <option value="">Everyone</option>
                    @foreach($bookers as $u)<option value="{{ $u->id }}">{{ $u->full_name }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="flex items-center justify-between">
            <div class="flex flex-wrap gap-1.5" id="quick-dates">
                <button type="button" class="quick-date" data-days="0">Today</button>
                <button type="button" class="quick-date" data-days="6">Last 7 days</button>
                <button type="button" class="quick-date" data-days="29">Last 30 days</button>
            </div>
            <button type="button" id="clear-filters" class="text-xs font-semibold text-surface-700 hover:text-brand-700 hover:underline">Clear filters</button>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-surface-100 text-[11px] font-bold text-surface-600 uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-5 py-3">Booking</th>
                        <th class="text-left px-3 py-3">Departure</th>
                        <th class="text-left px-3 py-3">Sender → Receiver</th>
                        <th class="text-left px-3 py-3">Cargo</th>
                        <th class="text-left px-3 py-3">Status</th>
                        <th class="text-left px-3 py-3">Payment</th>
                        <th class="text-right px-3 py-3">Amount</th>
                        <th class="text-left px-5 py-3">Booked</th>
                    </tr>
                </thead>
                <tbody id="rows" class="divide-y divide-surface-200"></tbody>
            </table>
        </div>
        <div class="flex flex-col sm:flex-row items-center justify-between gap-2 px-5 py-3 border-t border-surface-200 bg-surface-100/60">
            <div class="flex items-center gap-2 text-xs text-surface-600">
                <span id="page-info"></span>
                <select id="f-per_page" class="px-2 py-1 text-xs rounded-lg border border-surface-300 bg-white" aria-label="Rows per page">
                    <option value="20">20 per page</option><option value="50">50 per page</option><option value="100">100 per page</option>
                </select>
            </div>
            <div id="pager" class="flex items-center gap-1"></div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .status-tab { white-space: nowrap; padding: .45rem .8rem; border-radius: .75rem; font-size: .75rem; font-weight: 600; color: #495057; border: 1px solid transparent; }
    .status-tab:hover { background: #fff; border-color: #dee2e6; }
    .status-tab[aria-selected="true"] { background: #2563eb; color: #fff; }
    .tab-count { margin-left: .25rem; font-variant-numeric: tabular-nums; opacity: .75; }
    .quick-date { padding: .25rem .6rem; font-size: .75rem; border-radius: .5rem; border: 1px solid #dee2e6; color: #495057; }
    .quick-date:hover { background: #f8f9fa; }
</style>
@endpush

@push('scripts')
<script>
const { esc, num, money, date, dateTime, isoDate, statusBadge, paymentBadge, api, debounce } = window.CargoUI;
const FILTERS = ['search', 'status', 'date_field', 'date_from', 'date_to', 'route_id', 'payment_status', 'payment_method', 'booked_by', 'per_page'];
const state = Object.fromEntries(FILTERS.map(k => [k, '']));
state.page = 1;

// Initial filters from the URL (dashboard links, shared links, back button)
const qs = new URLSearchParams(location.search);
FILTERS.forEach(k => { if (qs.has(k)) state[k] = qs.get(k); });
state.page = parseInt(qs.get('page') || '1', 10) || 1;

function syncInputs() {
    FILTERS.forEach(k => {
        const el = document.getElementById('f-' + k);
        if (el) el.value = state[k] || (k === 'date_field' ? 'booked' : k === 'per_page' ? '20' : '');
    });
    document.querySelectorAll('.status-tab').forEach(t => t.setAttribute('aria-selected', t.dataset.status === (state.status || '')));
}

function queryString(extra = {}) {
    const p = new URLSearchParams();
    Object.entries({ ...state, ...extra }).forEach(([k, v]) => { if (v !== '' && v !== null && v !== undefined && !(k === 'page' && v == 1)) p.set(k, v); });
    return p.toString();
}

let controller = null;
async function load() {
    const q = queryString();
    history.replaceState(null, '', location.pathname + (q ? '?' + q : ''));
    const exportLink = document.getElementById('export-link');
    if (exportLink) exportLink.href = '{{ route('admin.cargo.consignments.export') }}' + (q ? '?' + q : '');

    document.getElementById('rows').innerHTML = `<tr><td colspan="8" class="px-5 py-12 text-center text-surface-500"><i class="fas fa-circle-notch fa-spin text-brand-500 text-xl"></i></td></tr>`;
    controller?.abort();
    controller = new AbortController();
    try {
        const data = await api('/admin/cargo/api/consignments?' + q, { signal: controller.signal });
        renderRows(data.data);
        renderPager(data);
        renderCounts(data.status_counts || {});
    } catch (e) {
        if (e.name === 'AbortError') return;
        document.getElementById('rows').innerHTML = `<tr><td colspan="8" class="px-5 py-10 text-center text-sm text-red-700">Couldn't load bookings: ${esc(e.message)}</td></tr>`;
    }
}

function renderRows(rows) {
    const el = document.getElementById('rows');
    if (!rows.length) {
        el.innerHTML = `<tr><td colspan="8" class="px-5 py-14 text-center text-sm text-surface-500"><i class="fas fa-box-open text-2xl text-surface-300 block mb-2"></i>No bookings match these filters.</td></tr>`;
        return;
    }
    el.innerHTML = rows.map(c => `
        <tr class="hover:bg-brand-50/40 cursor-pointer" onclick="if(!event.target.closest('a')) location.href='/admin/cargo/bookings/${c.id}'">
            <td class="px-5 py-3 align-top whitespace-nowrap">
                <a href="/admin/cargo/bookings/${c.id}" class="font-mono text-xs font-bold text-brand-700 hover:underline">${esc(c.consignment_number)}</a>
                <div class="text-[11px] text-surface-500 font-mono">${esc(c.tracking_number || '')}</div>
            </td>
            <td class="px-3 py-3 align-top whitespace-nowrap">
                <div class="text-surface-900">${c.departure_date ? date(c.departure_date) : '—'} <span class="font-mono text-xs">${esc(c.departure_time || '')}</span></div>
                <div class="text-xs text-surface-600">${esc(c.route || '')}${c.unit ? ' · ' + esc(c.unit) : ''}</div>
            </td>
            <td class="px-3 py-3 align-top">
                <div class="text-surface-900 font-medium">${esc(c.sender_name)} <span class="text-surface-400">→</span> ${esc(c.receiver_name)}</div>
                <div class="text-xs text-surface-600 font-mono">${esc(c.sender_phone || '')} · ${esc(c.receiver_phone || '')}</div>
            </td>
            <td class="px-3 py-3 align-top text-xs text-surface-700 whitespace-nowrap">${num(c.items_count)} item${c.items_count === 1 ? '' : 's'}<br>${num(c.weight_kg, c.weight_kg % 1 ? 1 : 0)} kg</td>
            <td class="px-3 py-3 align-top">${statusBadge(c.status)}</td>
            <td class="px-3 py-3 align-top">${paymentBadge(c.payment_status)}<div class="text-[11px] text-surface-600 mt-0.5">${esc(c.payment_method || '')}</div></td>
            <td class="px-3 py-3 align-top text-right tabular-nums font-semibold text-surface-900 whitespace-nowrap">${money(c.amount, c.currency)}</td>
            <td class="px-5 py-3 align-top text-xs text-surface-600 whitespace-nowrap">${dateTime(c.created_at)}<br>${esc(c.booked_by || '')}</td>
        </tr>`).join('');
}

function renderPager(meta) {
    document.getElementById('result-count').textContent = `${num(meta.total)} booking${meta.total === 1 ? '' : 's'} found.`;
    document.getElementById('page-info').textContent = meta.total ? `${num(meta.from)}–${num(meta.to)} of ${num(meta.total)}` : '';
    const pager = document.getElementById('pager');
    const last = meta.last_page || 1, cur = meta.current_page || 1;
    if (last <= 1) { pager.innerHTML = ''; return; }
    const pages = [...new Set([1, cur - 1, cur, cur + 1, last].filter(p => p >= 1 && p <= last))].sort((a, b) => a - b);
    const btn = (label, page, { disabled = false, active = false } = {}) =>
        `<button type="button" ${disabled ? 'disabled' : `onclick="goPage(${page})"`} class="min-w-[2rem] px-2 py-1 text-xs font-semibold rounded-lg border ${active ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-surface-800 border-surface-300 hover:border-brand-500'} ${disabled ? 'opacity-40 cursor-not-allowed' : ''}">${label}</button>`;
    let html = btn('‹', cur - 1, { disabled: cur <= 1 });
    pages.forEach((p, i) => {
        if (i && p - pages[i - 1] > 1) html += '<span class="px-1 text-surface-400">…</span>';
        html += btn(p, p, { active: p === cur });
    });
    pager.innerHTML = html + btn('›', cur + 1, { disabled: cur >= last });
}

function renderCounts(counts) {
    const total = Object.values(counts).reduce((a, b) => a + Number(b), 0);
    document.querySelectorAll('.tab-count').forEach(el => {
        const n = el.dataset.count ? (counts[el.dataset.count] || 0) : total;
        el.textContent = num(n);
    });
}

function goPage(p) { state.page = p; load(); window.scrollTo({ top: 0, behavior: 'smooth' }); }
function setFilter(k, v) { state[k] = v; state.page = 1; syncInputs(); load(); }

document.querySelectorAll('.status-tab').forEach(t => t.addEventListener('click', () => setFilter('status', t.dataset.status)));
FILTERS.filter(k => !['search', 'status'].includes(k)).forEach(k => {
    document.getElementById('f-' + k)?.addEventListener('change', e => setFilter(k, e.target.value === 'booked' && k === 'date_field' ? '' : e.target.value));
});
// Don't write back into the search box while the user is typing
document.getElementById('f-search').addEventListener('input', debounce(e => { state.search = e.target.value.trim(); state.page = 1; load(); }, 350));
document.querySelectorAll('.quick-date').forEach(b => b.addEventListener('click', () => {
    const from = new Date(); from.setDate(from.getDate() - Number(b.dataset.days));
    state.date_from = isoDate(from); state.date_to = isoDate(); state.page = 1; syncInputs(); load();
}));
document.getElementById('clear-filters').addEventListener('click', () => {
    FILTERS.forEach(k => { if (k !== 'per_page') state[k] = ''; });
    state.page = 1; syncInputs(); load();
});

syncInputs();
load();
</script>
@endpush
</x-layouts.admin>
