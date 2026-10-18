<x-layouts.admin>
<x-slot:title>Loading Manifest</x-slot:title>
<x-slot:header>Loading Manifest</x-slot:header>

@include('admin.cargo.partials.ui')

<div class="py-2 space-y-5">

    <div class="no-print">
        <h1 class="text-2xl font-extrabold text-surface-900 tracking-tight">Loading manifest</h1>
        <p class="text-sm text-surface-600 mt-0.5">Pick a date, then a departure. Only paid bookings appear on the manifest.</p>
    </div>

    {{-- Date + departures --}}
    <div class="no-print bg-white rounded-2xl border border-surface-200 shadow-sm p-4 space-y-3">
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="shiftDate(-1)" class="h-9 w-9 rounded-xl border border-surface-300 hover:bg-surface-100" title="Previous day" aria-label="Previous day"><i class="fas fa-chevron-left text-xs"></i></button>
            <input type="date" id="m-date" value="{{ $date }}" class="px-3 py-2 text-sm rounded-xl border border-surface-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-100 outline-none" aria-label="Departure date">
            <button type="button" onclick="shiftDate(1)" class="h-9 w-9 rounded-xl border border-surface-300 hover:bg-surface-100" title="Next day" aria-label="Next day"><i class="fas fa-chevron-right text-xs"></i></button>
            <button type="button" onclick="setDate(CargoUI.isoDate())" class="px-3 py-2 text-xs font-semibold rounded-xl border border-surface-300 hover:bg-surface-100">Today</button>
        </div>
        <div id="departures" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-2"></div>
    </div>

    {{-- Manifest --}}
    <div id="manifest" class="hidden space-y-4">
        <div id="manifest-print" class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-surface-200 flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                <div>
                    <p class="text-[11px] font-bold text-brand-700 uppercase tracking-widest">Cargo loading manifest</p>
                    <h2 id="m-route" class="text-xl font-extrabold text-surface-900 mt-0.5"></h2>
                    <p id="m-meta" class="text-sm text-surface-700 mt-1"></p>
                    <p id="m-generated" class="hidden print:block text-xs text-surface-500 mt-0.5"></p>
                </div>
                <div class="no-print flex flex-wrap gap-2">
                    <button onclick="window.print()" class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-xl border border-surface-300 hover:bg-surface-100"><i class="fas fa-print"></i> Print</button>
                    <a id="m-pdf" target="_blank" class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-xl border border-surface-300 hover:bg-surface-100"><i class="fas fa-file-pdf text-red-700"></i> PDF</a>
                    <a id="m-excel" class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-xl border border-surface-300 hover:bg-surface-100"><i class="fas fa-file-excel text-emerald-700"></i> Excel</a>
                </div>
            </div>

            <div id="m-warning" class="hidden mx-5 mt-4 rounded-xl bg-amber-50 border border-amber-300 px-4 py-2.5 text-sm text-amber-900"></div>

            <div id="m-kpis" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 p-5"></div>

            {{-- Loading (before the vessel leaves) --}}
            <div id="m-loading" class="no-print hidden mx-5 mb-4 rounded-xl border border-brand-200 bg-brand-50/50 p-3 flex flex-col lg:flex-row lg:items-center gap-3">
                <form id="m-scan" class="flex-1 flex gap-2" autocomplete="off">
                    <label for="m-code" class="sr-only">Scan or type a booking number or item label</label>
                    <input id="m-code" class="flex-1 min-w-0 px-3 py-2 text-sm rounded-xl border border-surface-300 font-mono uppercase focus:border-brand-500 focus:ring-2 focus:ring-brand-100 outline-none"
                           placeholder="Scan or type a booking number / item label, then Enter">
                    <button class="px-3 py-2 text-xs font-bold rounded-xl bg-brand-600 text-white hover:bg-brand-700"><i class="fas fa-barcode mr-1"></i>Load</button>
                </form>
                <div class="flex items-center gap-3 text-xs">
                    <label class="inline-flex items-center gap-2"><input type="checkbox" id="m-all" class="rounded border-surface-300"> All not loaded</label>
                    <button type="button" id="m-load-selected" class="px-3 py-2 font-bold rounded-xl border border-brand-300 text-brand-700 bg-white hover:bg-brand-50 disabled:opacity-50" disabled>
                        <i class="fas fa-dolly mr-1"></i>Mark <span id="m-sel-count">0</span> loaded
                    </button>
                </div>
            </div>
            <p id="m-closed" class="no-print hidden mx-5 mb-4 text-xs text-surface-600"></p>

            <div class="overflow-x-auto border-t border-surface-200">
                <table class="w-full text-sm">
                    <thead class="bg-surface-100 text-[11px] font-bold text-surface-600 uppercase tracking-wider">
                        <tr>
                            <th class="no-print w-8 pl-5 py-2.5"><span class="sr-only">Select</span></th>
                            <th class="text-left px-5 py-2.5">Booking</th>
                            <th class="text-left px-3 py-2.5">Sender</th>
                            <th class="text-left px-3 py-2.5">Receiver</th>
                            <th class="text-left px-3 py-2.5">Cargo</th>
                            <th class="text-right px-3 py-2.5">Weight (kg)</th>
                            <th class="text-left px-5 py-2.5">Status</th>
                        </tr>
                    </thead>
                    <tbody id="m-rows" class="divide-y divide-surface-200"></tbody>
                </table>
            </div>

            <div id="m-bytype-wrap" class="border-t border-surface-200 p-5">
                <h3 class="text-sm font-bold text-surface-900 mb-2">By cargo type</h3>
                <table class="text-sm min-w-[360px]">
                    <thead class="text-[11px] font-bold text-surface-600 uppercase tracking-wider"><tr><th class="text-left pr-6 py-1">Type</th><th class="text-right pr-6 py-1">Pieces</th><th class="text-right pr-6 py-1">Weight (kg)</th><th class="text-right py-1">CBM</th></tr></thead>
                    <tbody id="m-bytype"></tbody>
                </table>
            </div>

            <div class="hidden print:grid px-5 pb-6 pt-8 grid-cols-3 gap-6 text-xs text-surface-700">
                <div class="border-t border-surface-500 pt-1">Prepared by (cargo officer)</div>
                <div class="border-t border-surface-500 pt-1">Checked by (port / loading officer)</div>
                <div class="border-t border-surface-500 pt-1">Received by (master / chief officer)</div>
            </div>
        </div>
    </div>

    <div id="m-empty" class="no-print hidden bg-white rounded-2xl border border-surface-200 p-10 text-center text-sm text-surface-500">
        <i class="fas fa-clipboard-list text-3xl text-surface-300 block mb-2"></i> Choose a departure above to see its manifest.
    </div>
</div>

@push('styles')
<style>
    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        body * { visibility: hidden; }
        #manifest-print, #manifest-print * { visibility: visible; }
        #manifest-print { position: absolute; inset: 0 auto auto 0; width: 100%; border: 0; box-shadow: none; }
        .no-print { display: none !important; }
        .overflow-x-auto { overflow: visible !important; }
    }
</style>
@endpush

@push('scripts')
<script>
const { esc, num, date, statusBadge, api, isoDate } = window.CargoUI;
let selectedId = @json($serviceId);
let current = null; // the manifest on screen (the load dialog lists the picked bookings from it)

function setDate(d) { document.getElementById('m-date').value = d; loadDepartures(); }
function shiftDate(days) {
    const d = new Date(document.getElementById('m-date').value + 'T12:00:00');
    d.setDate(d.getDate() + days);
    setDate(isoDate(d));
}

async function loadDepartures() {
    const day = document.getElementById('m-date').value;
    const el = document.getElementById('departures');
    el.innerHTML = '<p class="text-sm text-surface-500 py-2"><i class="fas fa-circle-notch fa-spin text-brand-500"></i> Loading departures…</p>';
    const url = new URL(location.href);
    url.searchParams.set('date', day);
    if (selectedId) url.searchParams.set('service', selectedId); else url.searchParams.delete('service');
    history.replaceState(null, '', url);

    try {
        const list = await api(`/admin/cargo/api/manifest-services?date=${encodeURIComponent(day)}`);
        if (!list.length) {
            el.innerHTML = '<p class="text-sm text-surface-500 py-2">No departures on this date.</p>';
            showManifest(null);
            return;
        }
        el.innerHTML = list.map(s => `
            <button type="button" onclick="selectService(${s.id})" data-id="${s.id}"
                class="dep-card text-left rounded-xl border px-3 py-2.5 transition-colors ${s.id === selectedId ? 'border-brand-500 bg-brand-50 ring-1 ring-brand-300' : 'border-surface-300 hover:border-brand-400'}">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-mono font-bold text-surface-900">${esc(s.time || '—')}</span>
                    <span class="text-xs font-semibold ${s.consignments ? 'text-surface-900' : 'text-surface-500'}">${num(s.consignments)} booking${s.consignments === 1 ? '' : 's'}</span>
                </div>
                <div class="text-sm text-surface-800 truncate">${esc(s.route || '—')}</div>
                <div class="text-xs text-surface-600 truncate">${esc(s.unit || '')}${s.status !== 'scheduled' ? ' · ' + esc(String(s.status).replace(/_/g, ' ')) : ''}${s.awaiting_payment ? ` · <span class="text-amber-800 font-semibold">${s.awaiting_payment} unpaid</span>` : ''}</div>
            </button>`).join('');
        if (selectedId && list.some(s => s.id === selectedId)) loadManifest(selectedId); else showManifest(null);
    } catch (e) {
        el.innerHTML = `<p class="text-sm text-red-700 py-2">Couldn't load departures: ${esc(e.message)}</p>`;
    }
}

function selectService(id) {
    selectedId = id;
    document.querySelectorAll('.dep-card').forEach(c => {
        const on = Number(c.dataset.id) === id;
        c.classList.toggle('border-brand-500', on); c.classList.toggle('bg-brand-50', on); c.classList.toggle('ring-1', on); c.classList.toggle('ring-brand-300', on);
        c.classList.toggle('border-surface-300', !on);
    });
    const url = new URL(location.href); url.searchParams.set('service', id); history.replaceState(null, '', url);
    loadManifest(id);
}

function showManifest(on) {
    document.getElementById('manifest').classList.toggle('hidden', !on);
    document.getElementById('m-empty').classList.toggle('hidden', !!on);
}

async function loadManifest(id) {
    try {
        const m = await api(`/admin/cargo/api/manifest/${id}`);
        current = m;
        renderManifest(m);
        renderLoading(m);
        showManifest(true);
    } catch (e) {
        window.notify?.(`Couldn't load the manifest: ${e.message}`, 'error');
    }
}

function renderManifest(m) {
    document.getElementById('m-route').textContent = m.route_name || m.route;
    document.getElementById('m-meta').innerHTML = `<i class="fas fa-ship text-surface-400 mr-1"></i>${esc(m.unit_name)}${m.unit_registration ? ' (' + esc(m.unit_registration) + ')' : ''}
        <span class="mx-2 text-surface-300">|</span><i class="fas fa-clock text-surface-400 mr-1"></i>${date(m.departure_date)} ${esc(m.departure_time || '')}
        ${m.service_number ? `<span class="mx-2 text-surface-300">|</span><span class="font-mono">${esc(m.service_number)}</span>` : ''}`;
    document.getElementById('m-generated').textContent = `Generated ${m.generated_at}${m.generated_by ? ' by ' + m.generated_by : ''}`;
    document.getElementById('m-pdf').href = `/admin/cargo/manifest/${m.service_id}/export?format=pdf`;
    document.getElementById('m-excel').href = `/admin/cargo/manifest/${m.service_id}/export?format=excel`;

    const warn = document.getElementById('m-warning');
    warn.classList.toggle('hidden', !m.awaiting_payment);
    warn.innerHTML = m.awaiting_payment ? `<i class="fas fa-triangle-exclamation mr-1"></i><strong>${m.awaiting_payment}</strong> booking${m.awaiting_payment === 1 ? ' is' : 's are'} awaiting payment on this departure and ${m.awaiting_payment === 1 ? 'is' : 'are'} not on the manifest. Don't load ${m.awaiting_payment === 1 ? 'it' : 'them'} until paid.` : '';

    const cap = m.capacity || {};
    const of = (total, unit) => total ? `of ${num(total)} ${unit} capacity` : '';
    const kpi = (label, value, sub = '') => `<div class="rounded-xl border border-surface-200 px-3 py-2.5"><p class="text-[11px] font-semibold text-surface-600 uppercase tracking-wider">${label}</p><p class="text-lg font-extrabold text-surface-900 mt-0.5 whitespace-nowrap">${value}</p>${sub ? `<p class="text-[11px] text-surface-500">${sub}</p>` : ''}</div>`;
    document.getElementById('m-kpis').innerHTML = [
        kpi('Bookings', num(m.total_consignments)),
        kpi('Pieces', num(m.total_pieces)),
        kpi('Weight', num(m.total_weight_kg, 1) + ' kg', of(cap.weight_kg, 'kg')),
        kpi('Volume', num(m.total_volume_cbm, 2) + ' cbm', of(cap.volume_cbm, 'cbm')),
        kpi('Vehicles · lane m', `${num(m.vehicle_count)} · ${num(m.total_lane_meters, 1)}`),
        kpi('Hazmat · fragile', `<span class="${m.hazmat_count ? 'text-red-700' : ''}">${num(m.hazmat_count)}</span> · ${num(m.fragile_count)}`),
    ].join('');

    const rows = document.getElementById('m-rows');
    if (!m.consignments.length) {
        rows.innerHTML = `<tr><td colspan="7" class="px-5 py-10 text-center text-sm text-surface-500">No paid bookings on this departure yet.</td></tr>`;
    } else {
        rows.innerHTML = m.consignments.map(c => `
            <tr class="align-top">
                <td class="no-print pl-5 py-3">${m.can_load && c.status === 'booked' ? `<input type="checkbox" class="m-pick rounded border-surface-300" value="${c.id}" aria-label="Select ${esc(c.consignment_number)}">` : ''}</td>
                <td class="px-5 py-3"><a href="/admin/cargo/bookings/${c.id}" class="font-mono text-xs font-bold text-brand-700 hover:underline">${esc(c.consignment_number)}</a>
                    ${c.special_instructions ? `<div class="text-[11px] text-amber-800 mt-1 max-w-[180px]"><i class="fas fa-note-sticky mr-1"></i>${esc(c.special_instructions)}</div>` : ''}</td>
                <td class="px-3 py-3"><div class="text-surface-900">${esc(c.sender)}</div><div class="text-xs font-mono text-surface-600">${esc(c.sender_phone || '')}</div></td>
                <td class="px-3 py-3"><div class="text-surface-900">${esc(c.receiver)}</div><div class="text-xs font-mono text-surface-600">${esc(c.receiver_phone || '')}</div></td>
                <td class="px-3 py-3">
                    <ul class="space-y-1">${c.items.map(i => `
                        <li class="text-xs text-surface-800">
                            <span class="font-semibold">${i.quantity > 1 ? i.quantity + ' × ' : ''}${esc(i.type)}</span>
                            ${i.description ? `<span class="text-surface-600">— ${esc(i.description)}</span>` : ''}
                            ${i.vehicle_reg_number ? `<span class="font-mono font-semibold ml-1">${esc(i.vehicle_reg_number)}</span>` : ''}
                            ${i.is_hazmat ? '<span class="ml-1 px-1.5 rounded bg-red-50 text-red-700 border border-red-200 font-bold">HAZMAT</span>' : ''}
                            ${i.is_fragile ? '<span class="ml-1 px-1.5 rounded bg-amber-50 text-amber-800 border border-amber-200 font-bold">FRAGILE</span>' : ''}
                            <span class="text-surface-500 font-mono ml-1">${esc(i.barcode || '')}</span>
                        </li>`).join('')}</ul>
                </td>
                <td class="px-3 py-3 text-right tabular-nums">${num(c.weight_kg, 1)}</td>
                <td class="px-5 py-3">${c.left_behind ? '<span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">Left behind</span>' : statusBadge(c.status)}</td>
            </tr>`).join('');
    }

    document.getElementById('m-bytype-wrap').classList.toggle('hidden', !m.by_type.length);
    document.getElementById('m-bytype').innerHTML = m.by_type.map(t => `
        <tr><td class="pr-6 py-1 text-surface-800">${esc(t.type)}</td><td class="pr-6 py-1 text-right tabular-nums">${num(t.pieces)}</td>
            <td class="pr-6 py-1 text-right tabular-nums">${num(t.weight_kg, 1)}</td><td class="py-1 text-right tabular-nums">${num(t.volume_cbm, 2)}</td></tr>`).join('');
}

// ── Loading ─────────────────────────────────────────────
function renderLoading(m) {
    const notLoaded = m.consignments.filter(c => c.status === 'booked' && !c.left_behind).length;
    document.getElementById('m-loading').classList.toggle('hidden', !m.can_load);
    const closed = document.getElementById('m-closed');
    const leftBehind = m.consignments.filter(c => c.left_behind).length;
    closed.classList.toggle('hidden', m.can_load);
    closed.textContent = ['departed', 'arrived'].includes(m.service_status)
        ? `This departure has ${m.service_status}; loading is closed.${leftBehind ? ` ${leftBehind} booking(s) were left behind.` : ''}`
        : (m.service_status === 'cancelled' ? 'This departure was cancelled.' : '');
    document.getElementById('m-all').checked = false;
    document.getElementById('m-all').disabled = !notLoaded;
    updateSelection();
    if (m.can_load) document.getElementById('m-code').focus();
}

function updateSelection() {
    const n = document.querySelectorAll('.m-pick:checked').length;
    document.getElementById('m-sel-count').textContent = n;
    document.getElementById('m-load-selected').disabled = !n;
}

async function loadBookings(body) {
    try {
        const r = await api(`/admin/cargo/api/manifest/${selectedId}/load`, { method: 'POST', body });
        window.notify?.(r.message + (Object.keys(r.skipped || {}).length ? ' Skipped: ' + Object.values(r.skipped).join(' ') : ''), Object.keys(r.skipped || {}).length ? 'info' : 'success');
    } catch (e) {
        const skipped = Object.values(e.data?.skipped || {});
        window.notify?.(skipped.length ? skipped.join(' ') : e.message, 'error');
    }
    await loadManifest(selectedId);
}

document.getElementById('m-rows').addEventListener('change', e => { if (e.target.classList.contains('m-pick')) updateSelection(); });
document.getElementById('m-all').addEventListener('change', e => {
    document.querySelectorAll('.m-pick').forEach(cb => { cb.checked = e.target.checked; });
    updateSelection();
});
document.getElementById('m-load-selected').addEventListener('click', async () => {
    const ids = [...document.querySelectorAll('.m-pick:checked')].map(cb => Number(cb.value));
    if (!ids.length) return;
    const picked = (current?.consignments || []).filter(c => ids.includes(c.id));
    const pieces = c => c.items.reduce((n, i) => n + Number(i.quantity || 1), 0);
    const pcs = n => `${num(n)} ${n === 1 ? 'pc' : 'pcs'}`;
    const n = ids.length, s = n === 1 ? '' : 's';
    const ok = await confirmDialog({
        title: current?.unit_name && current.unit_name !== 'Unknown' ? `Load ${n} booking${s} onto ${current.unit_name}?` : `Load ${n} booking${s}?`,
        message: [current?.route_name || current?.route, current && `${date(current.departure_date)} ${current.departure_time || ''}`.trim()].filter(Boolean).join(' · '),
        icon: 'fa-dolly',
        confirmText: `Mark ${n} loaded`,
        cancelText: 'Not now',
        items: picked.map(c => ({
            title: c.consignment_number,
            tags: c.items.flatMap(i => [i.is_hazmat && 'hazmat', i.is_fragile && 'fragile']).filter((t, k, all) => t && all.indexOf(t) === k)
                .map(t => ({ text: t, tone: t === 'hazmat' ? 'danger' : 'warning' })),
            meta: pcs(pieces(c)),
            value: `${num(c.weight_kg, 1)} kg`,
        })),
        total: picked.length > 1 ? {
            title: 'Total',
            value: `${pcs(picked.reduce((t, c) => t + pieces(c), 0))} · ${num(picked.reduce((t, c) => t + Number(c.weight_kg || 0), 0), 1)} kg`,
        } : null,
    });
    if (ok) loadBookings({ ids });
});
document.getElementById('m-scan').addEventListener('submit', async e => {
    e.preventDefault();
    const input = document.getElementById('m-code');
    const code = input.value.trim();
    if (!code) return;
    input.value = '';
    await loadBookings({ code });
    input.focus();
});

document.getElementById('m-date').addEventListener('change', () => { selectedId = null; loadDepartures(); });
loadDepartures();
</script>
@endpush
</x-layouts.admin>
