<x-layouts.admin>
<x-slot:title>Cargo Dashboard</x-slot:title>
<x-slot:header>Cargo Dashboard</x-slot:header>

@include('admin.cargo.partials.ui')

<div class="py-2 space-y-6 cargo-dash">

    {{-- Header + period switcher --}}
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-surface-900 tracking-tight">Cargo Operations</h1>
            <p class="text-sm text-surface-600 mt-0.5">Bookings, money collected and today's departures. <span id="last-updated" class="text-surface-500"></span></p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="inline-flex rounded-xl border border-surface-300 bg-white p-1 shadow-sm" role="tablist" aria-label="Period">
                @foreach($periods as $key => $label)
                    <button type="button" role="tab" data-period="{{ $key }}" onclick="setPeriod('{{ $key }}')"
                        class="period-btn px-3 py-1.5 text-xs font-semibold rounded-lg text-surface-700 hover:bg-surface-100 transition-colors">{{ $label }}</button>
                @endforeach
            </div>
            <button onclick="loadDashboard()" class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-xl border border-surface-300 bg-white text-surface-700 hover:bg-surface-100" title="Refresh">
                <i class="fas fa-rotate-right"></i> Refresh
            </button>
            @if(auth()->user()->hasPermission('cargo-book'))
                <a href="{{ route('admin.cargo.booking') }}" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl bg-brand-600 hover:bg-brand-700 text-white shadow-sm">
                    <i class="fas fa-plus"></i> New booking
                </a>
            @endif
        </div>
    </div>

    <div id="load-error" class="hidden rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700"><i class="fas fa-triangle-exclamation mr-1"></i> Couldn't load the dashboard. <button class="underline font-semibold" onclick="loadDashboard()">Try again</button></div>

    {{-- KPI tiles --}}
    <div id="kpis" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
        @for($i = 0; $i < 6; $i++)
            <div class="bg-white rounded-2xl border border-surface-200 p-4 h-[108px] animate-pulse"><div class="h-3 w-20 bg-surface-200 rounded"></div><div class="h-7 w-24 bg-surface-200 rounded mt-3"></div></div>
        @endfor
    </div>

    {{-- Trends: one measure per chart (never a shared or dual axis) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-5">
            <div class="flex items-baseline justify-between">
                <h2 class="text-sm font-bold text-surface-900">Money collected <span class="font-normal text-surface-500">(TZS)</span></h2>
                <span id="trend-collected-total" class="text-sm font-semibold text-surface-800 tabular-nums"></span>
            </div>
            <div class="relative h-56 mt-3"><canvas id="chart-collected" role="img" aria-label="Money collected over the selected period"></canvas></div>
        </div>
        <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-5">
            <div class="flex items-baseline justify-between">
                <h2 class="text-sm font-bold text-surface-900">Bookings made</h2>
                <span id="trend-bookings-total" class="text-sm font-semibold text-surface-800 tabular-nums"></span>
            </div>
            <div class="relative h-56 mt-3"><canvas id="chart-bookings" role="img" aria-label="Bookings made over the selected period"></canvas></div>
        </div>
    </div>

    {{-- Shipment pipeline --}}
    <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-5">
        <div class="flex items-baseline justify-between mb-4">
            <h2 class="text-sm font-bold text-surface-900">Shipments by stage <span class="font-normal text-surface-500">— right now</span></h2>
            <a href="{{ route('admin.cargo.consignments') }}" class="text-xs font-semibold text-brand-700 hover:underline">All bookings <i class="fas fa-arrow-right text-[10px]"></i></a>
        </div>
        <div id="pipeline" class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2"></div>
    </div>

    {{-- Needs attention --}}
    <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-5">
        <h2 class="text-sm font-bold text-surface-900 mb-3">Needs attention</h2>
        <ul id="attention" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2"></ul>
    </div>

    <div>
        {{-- Today's departures --}}
        <div class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-surface-200 flex items-baseline justify-between">
                <h2 class="text-sm font-bold text-surface-900">Today's departures</h2>
                <span class="text-xs text-surface-500">Space used includes active holds</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-surface-100 text-[11px] font-bold text-surface-600 uppercase tracking-wider">
                        <tr>
                            <th class="text-left px-5 py-2.5">Time</th>
                            <th class="text-left px-3 py-2.5 min-w-[190px]">Route / vessel</th>
                            <th class="text-right px-3 py-2.5">Bookings</th>
                            <th class="text-left px-3 py-2.5 min-w-[110px]">Weight</th>
                            <th class="text-left px-3 py-2.5 min-w-[110px]">Volume</th>
                            <th class="text-left px-3 py-2.5 min-w-[110px]">Lane m</th>
                            <th class="px-5 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody id="departures" class="divide-y divide-surface-200"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-5">
            <h2 class="text-sm font-bold text-surface-900">Collected by payment channel <span class="font-normal text-surface-500">(TZS)</span></h2>
            <ul id="by-channel" class="mt-4 space-y-3"></ul>
        </div>
        <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-5">
            <h2 class="text-sm font-bold text-surface-900">Top routes by booking value <span class="font-normal text-surface-500">(TZS)</span></h2>
            <ul id="top-routes" class="mt-4 space-y-3"></ul>
        </div>
    </div>

    {{-- Recent bookings --}}
    <div class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-surface-200">
            <h2 class="text-sm font-bold text-surface-900">Latest bookings</h2>
            <a href="{{ route('admin.cargo.consignments') }}" class="text-xs font-semibold text-brand-700 hover:underline">View all <i class="fas fa-arrow-right text-[10px]"></i></a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-surface-100 text-[11px] font-bold text-surface-600 uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-5 py-2.5">Booking</th>
                        <th class="text-left px-3 py-2.5">Sender → Receiver</th>
                        <th class="text-left px-3 py-2.5">Route</th>
                        <th class="text-left px-3 py-2.5">Status</th>
                        <th class="text-left px-3 py-2.5">Payment</th>
                        <th class="text-right px-3 py-2.5">Amount</th>
                        <th class="text-left px-5 py-2.5">Booked</th>
                    </tr>
                </thead>
                <tbody id="recent" class="divide-y divide-surface-200"></tbody>
            </table>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Chart roles (reference palette, light) */
    .cargo-dash { --series-1: #2a78d6; --grid: #e1e0d9; --axis: #898781; --ink-2: #52514e; }
</style>
@endpush

@push('scripts')
<script>
const { esc, num, money, dateTime, statusBadge, paymentBadge, STATUS, api } = window.CargoUI;
const DASH_STAGES = { pending_payment: 'Awaiting payment', booked: 'Booked', loaded: 'Loaded', in_transit: 'In transit', delivered: 'Arrived', unclaimed: 'Unclaimed', collected: 'Collected' };
const css = getComputedStyle(document.querySelector('.cargo-dash'));
const SERIES = css.getPropertyValue('--series-1').trim() || '#2a78d6';
const GRID = css.getPropertyValue('--grid').trim() || '#e1e0d9';
const AXIS = css.getPropertyValue('--axis').trim() || '#898781';

let period = new URLSearchParams(location.search).get('period') || 'today';
let charts = {};
let refreshTimer = null;

function setPeriod(p) {
    period = p;
    const url = new URL(location.href);
    url.searchParams.set('period', p);
    history.replaceState(null, '', url);
    loadDashboard();
}

async function loadDashboard() {
    document.querySelectorAll('.period-btn').forEach(b => {
        const on = b.dataset.period === period;
        b.classList.toggle('bg-brand-600', on);
        b.classList.toggle('text-white', on);
        b.classList.toggle('hover:bg-surface-100', !on);
        b.setAttribute('aria-selected', on);
    });
    try {
        const d = await api(`/admin/cargo/api/dashboard?period=${encodeURIComponent(period)}`);
        document.getElementById('load-error').classList.add('hidden');
        renderKpis(d);
        renderTrend(d.trend, d.period);
        renderPipeline(d.pipeline);
        renderAttention(d.attention);
        renderDepartures(d.departures);
        renderBars('by-channel', d.by_channel, 'No payments collected in this period.');
        renderBars('top-routes', d.top_routes, 'No bookings in this period.');
        renderRecent(d.recent);
        document.getElementById('last-updated').textContent = '· Updated ' + new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } catch (e) {
        document.getElementById('load-error').classList.remove('hidden');
    }
}

// ── KPI tiles ───────────────────────────────────────
function delta(k, upIsGood = true, prevLabel) {
    if (k.change_pct === null || k.change_pct === undefined) {
        return `<span class="text-surface-500">No ${esc(prevLabel)} figure</span>`;
    }
    if (k.change_pct === 0) return `<span class="text-surface-600">Same as ${esc(prevLabel)}</span>`;
    const up = k.change_pct > 0;
    const good = up === upIsGood;
    return `<span class="${good ? 'text-emerald-700' : 'text-red-700'} font-semibold"><i class="fas fa-arrow-${up ? 'up' : 'down'} text-[10px]"></i> ${Math.abs(k.change_pct)}%</span> <span class="text-surface-500">vs ${esc(prevLabel)}</span>`;
}

const tzs = v => `<span class="text-xs font-semibold text-surface-500 mr-0.5">TZS</span>${num(v)}`;

function renderKpis(d) {
    const k = d.kpis;
    const prev = { today: 'yesterday', '7d': 'prior 7 days', '30d': 'prior 30 days', month: 'same days last month' }[d.period] || 'previous period';
    const tile = (label, value, sub, href) => `
        <${href ? `a href="${href}"` : 'div'} class="block bg-white rounded-2xl border border-surface-200 shadow-sm p-4 ${href ? 'hover:border-brand-400 transition-colors' : ''}">
            <p class="text-[11px] font-semibold text-surface-600 uppercase tracking-wider">${label}</p>
            <p class="text-xl 2xl:text-2xl font-extrabold text-surface-900 mt-1.5 leading-tight whitespace-nowrap">${value}</p>
            <p class="text-xs mt-1.5">${sub}</p>
        </${href ? 'a' : 'div'}>`;

    document.getElementById('kpis').innerHTML = [
        tile('Bookings', num(k.bookings.value), delta(k.bookings, true, prev)),
        tile('Collected', tzs(k.collected.value), delta(k.collected, true, prev)),
        tile('Outstanding', tzs(d.outstanding.value),
            `<span class="text-surface-600">${num(d.outstanding.count)} unpaid · ${num(d.outstanding.awaiting)} awaiting payment</span>`,
            '/admin/cargo/consignments?payment_status=unpaid'),
        tile('Avg booking', tzs(k.avg_value.value), delta(k.avg_value, true, prev)),
        tile('Weight booked', num(k.weight_kg.value) + ' <span class="text-base font-semibold text-surface-500">kg</span>', delta(k.weight_kg, true, prev)),
        tile('Cancelled', num(k.cancellation_rate.value, 1) + '<span class="text-base font-semibold text-surface-500">%</span>', delta(k.cancellation_rate, false, prev)),
    ].join('');
}

// ── Trend charts (Chart.js, one series each) ────────
function renderTrend(t, p) {
    document.getElementById('trend-collected-total').textContent = 'TZS ' + num(t.collected.reduce((a, b) => a + b, 0));
    document.getElementById('trend-bookings-total').textContent = num(t.bookings.reduce((a, b) => a + b, 0)) + ' total';
    drawBars('chart-collected', t.labels, t.collected, v => 'TZS ' + num(v));
    drawBars('chart-bookings', t.labels, t.bookings, v => num(v) + (v === 1 ? ' booking' : ' bookings'));
}

function drawBars(id, labels, values, fmtValue) {
    if (charts[id]) charts[id].destroy();
    charts[id] = new Chart(document.getElementById(id), {
        type: 'bar',
        data: { labels, datasets: [{ data: values, backgroundColor: SERIES, hoverBackgroundColor: '#256abf', borderRadius: { topLeft: 4, topRight: 4 }, borderSkipped: 'bottom', maxBarThickness: 24, categoryPercentage: 0.8 }] },
        options: {
            responsive: true, maintainAspectRatio: false, animation: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: { displayColors: false, backgroundColor: '#212529', padding: 10, callbacks: { label: c => fmtValue(c.parsed.y) } },
            },
            scales: {
                x: { grid: { display: false }, border: { color: GRID }, ticks: { color: AXIS, font: { size: 11 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } },
                y: { beginAtZero: true, grid: { color: GRID, drawTicks: false }, border: { display: false }, ticks: { color: AXIS, font: { size: 11 }, padding: 8, maxTicksLimit: 5, precision: 0, callback: v => num(v) } },
            },
        },
    });
}

// ── Pipeline ────────────────────────────────────────
function renderPipeline(stages) {
    document.getElementById('pipeline').innerHTML = stages.map((s, i) => `
        <a href="/admin/cargo/consignments?status=${encodeURIComponent(s.status)}" class="group rounded-xl border border-surface-200 px-3 py-3 hover:border-brand-400 hover:bg-brand-50/40 transition-colors">
            <p class="text-[11px] font-semibold text-surface-600 uppercase tracking-wider flex items-center gap-1">
                <span class="tabular-nums text-surface-400">${i + 1}</span> ${esc(DASH_STAGES[s.status] || s.status)}
            </p>
            <p class="text-xl font-extrabold text-surface-900 mt-1">${num(s.count)}</p>
            ${s.status === 'collected' ? '<p class="text-[11px] text-surface-500">in this period</p>' : ''}
        </a>`).join('');
}

// ── Needs attention ─────────────────────────────────
function renderAttention(items) {
    const el = document.getElementById('attention');
    if (!items.length) {
        el.innerHTML = `<li class="md:col-span-2 xl:col-span-3 flex items-center gap-2 text-sm text-surface-600 py-1"><i class="fas fa-circle-check text-emerald-600"></i> Nothing needs attention right now.</li>`;
        return;
    }
    const sev = {
        danger:  { icon: 'fa-circle-exclamation', cls: 'text-red-700', label: 'Urgent' },
        warning: { icon: 'fa-triangle-exclamation', cls: 'text-amber-700', label: 'Check' },
        info:    { icon: 'fa-circle-info', cls: 'text-brand-700', label: 'Note' },
    };
    el.innerHTML = items.map(i => {
        const s = sev[i.severity] || sev.info;
        return `<li>
            <a href="${esc(i.url)}" class="h-full flex items-start gap-3 rounded-xl border border-surface-200 px-3 py-2.5 hover:border-brand-400 hover:bg-surface-100/60 transition-colors">
                <i class="fas ${s.icon} ${s.cls} mt-0.5" aria-label="${s.label}"></i>
                <span class="flex-1 text-sm text-surface-800">${esc(i.label)}</span>
                <span class="text-sm font-bold text-surface-900 tabular-nums">${num(i.count)}</span>
            </a></li>`;
    }).join('');
}

// ── Departures ──────────────────────────────────────
function meter(pct) {
    if (pct === null || pct === undefined) return '<span class="text-xs text-surface-400">Not set</span>';
    const width = Math.min(pct, 100);
    const full = pct >= 85;
    return `<div class="flex items-center gap-2">
        <div class="flex-1 h-1.5 rounded-full bg-surface-200 overflow-hidden"><div class="h-full rounded-full" style="width:${width}%;background:${SERIES}"></div></div>
        <span class="text-xs tabular-nums w-10 text-right ${full ? 'font-bold text-red-700' : 'text-surface-700'}">${pct}%</span>
        ${full ? '<i class="fas fa-circle-exclamation text-red-700 text-xs" title="Nearly full"></i>' : ''}
    </div>`;
}

function renderDepartures(rows) {
    const el = document.getElementById('departures');
    if (!rows.length) {
        el.innerHTML = `<tr><td colspan="7" class="px-5 py-10 text-center text-sm text-surface-500">No departures today.</td></tr>`;
        return;
    }
    el.innerHTML = rows.map(r => `
        <tr class="hover:bg-surface-100/60">
            <td class="px-5 py-3 font-mono font-semibold text-surface-900">${esc(r.time || '—')}</td>
            <td class="px-3 py-3"><div class="font-semibold text-surface-900">${esc(r.route || '—')}</div><div class="text-xs text-surface-600">${esc(r.unit || '')} ${r.status !== 'scheduled' ? '· ' + esc(String(r.status).replace(/_/g, ' ')) : ''}</div></td>
            <td class="px-3 py-3 text-right tabular-nums font-semibold">${num(r.bookings)}</td>
            <td class="px-3 py-3">${meter(r.utilisation?.weight)}</td>
            <td class="px-3 py-3">${meter(r.utilisation?.volume)}</td>
            <td class="px-3 py-3">${meter(r.utilisation?.lane)}</td>
            <td class="px-5 py-3 text-right"><a href="/admin/cargo/manifest?service=${r.id}" class="text-xs font-semibold text-brand-700 hover:underline whitespace-nowrap">Manifest</a></td>
        </tr>`).join('');
}

// ── Horizontal bar lists (one series → one colour) ──
function renderBars(id, rows, empty) {
    const el = document.getElementById(id);
    if (!rows.length) { el.innerHTML = `<li class="text-sm text-surface-500 py-4">${empty}</li>`; return; }
    const max = Math.max(...rows.map(r => r.value), 1);
    el.innerHTML = rows.map(r => `
        <li title="${esc(r.label)}: TZS ${num(r.value)} from ${num(r.count)} booking${r.count === 1 ? '' : 's'}">
            <div class="flex items-baseline justify-between gap-3 text-sm">
                <span class="text-surface-800 truncate">${esc(r.label)}</span>
                <span class="tabular-nums font-semibold text-surface-900 whitespace-nowrap">${num(r.value)} <span class="text-xs font-normal text-surface-500">· ${num(r.count)}</span></span>
            </div>
            <div class="mt-1 h-2 rounded bg-surface-100"><div class="h-2 rounded-r" style="width:${Math.max(r.value / max * 100, 1)}%;background:${SERIES}"></div></div>
        </li>`).join('');
}

// ── Recent bookings ─────────────────────────────────
function renderRecent(rows) {
    const el = document.getElementById('recent');
    if (!rows.length) { el.innerHTML = `<tr><td colspan="7" class="px-5 py-10 text-center text-sm text-surface-500">No bookings yet.</td></tr>`; return; }
    el.innerHTML = rows.map(c => `
        <tr class="hover:bg-surface-100/60 cursor-pointer" onclick="location.href='/admin/cargo/bookings/${c.id}'">
            <td class="px-5 py-3"><a href="/admin/cargo/bookings/${c.id}" class="font-mono text-xs font-bold text-brand-700 hover:underline">${esc(c.consignment_number)}</a></td>
            <td class="px-3 py-3 text-surface-800">${esc(c.sender_name)} <span class="text-surface-400">→</span> ${esc(c.receiver_name)}</td>
            <td class="px-3 py-3 text-xs text-surface-600">${esc(c.route || '—')}</td>
            <td class="px-3 py-3">${statusBadge(c.status)}</td>
            <td class="px-3 py-3">${paymentBadge(c.payment_status)}</td>
            <td class="px-3 py-3 text-right tabular-nums font-semibold text-surface-900">${money(c.amount, c.currency)}</td>
            <td class="px-5 py-3 text-xs text-surface-600 whitespace-nowrap">${dateTime(c.created_at)}${c.booked_by ? '<br>' + esc(c.booked_by) : ''}</td>
        </tr>`).join('');
}

document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    refreshTimer = setInterval(() => { if (!document.hidden) loadDashboard(); }, 60000);
});
</script>
@endpush
</x-layouts.admin>
