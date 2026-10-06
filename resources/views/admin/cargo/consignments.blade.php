<x-layouts.admin>
<x-slot:title>Cargo Bookings</x-slot:title>
<x-slot:header>Cargo Bookings</x-slot:header>

<div class="px-4 sm:px-6 lg:px-8 py-6 space-y-5">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-surface-900 tracking-tight">Cargo Bookings</h1>
            <p class="text-sm text-surface-500 mt-0.5">Browse and manage all cargo booking records.</p>
        </div>
    </div>

    {{-- Filters + Search Bar --}}
    <div class="bg-white rounded-2xl border border-surface-200/80 shadow-sm px-5 py-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-surface-400 text-sm"></i>
                </div>
                <input type="text" id="search-input"
                    placeholder="Search by booking number, sender or receiver..."
                    class="w-full pl-9 pr-4 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-surface-50 focus:bg-white">
            </div>
            <select id="status-filter"
                class="text-sm rounded-xl border border-surface-200 px-3 py-2.5 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-surface-50 focus:bg-white text-surface-700 min-w-[160px]">
                <option value="">All Statuses</option>
                <option value="pending_payment">Awaiting Payment</option>
                <option value="booked">Booked</option>
                <option value="loaded">Loaded</option>
                <option value="in_transit">In Transit</option>
                <option value="delivered">Delivered</option>
                <option value="collected">Collected</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <button id="search-btn"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm shadow-brand-200">
                <i class="fas fa-filter text-xs"></i> Filter
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-surface-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-50 border-b border-surface-100">
                        <th class="text-left px-6 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Booking Number</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Date</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Sender</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Receiver</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Route</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Status</th>
                        <th class="text-right px-6 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Amount (TZS)</th>
                        <th class="text-center px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="consignments-tbody">
                    <tr>
                        <td colspan="8" class="px-6 py-14 text-center">
                            <div class="flex flex-col items-center gap-2 text-surface-400">
                                <i class="fas fa-circle-notch fa-spin text-2xl text-brand-400"></i>
                                <span class="text-sm">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="flex items-center justify-between px-6 py-3 border-t border-surface-100 bg-surface-50/50">
            <span id="pagination-info" class="text-xs text-surface-400"></span>
            <div id="pagination-controls" class="flex items-center gap-1"></div>
        </div>
    </div>

</div>

{{-- Detail Slide-over Panel --}}
<div id="detail-panel" class="fixed inset-0 z-50 hidden" aria-modal="true">
    <div id="detail-backdrop" class="absolute inset-0 bg-surface-950/40 backdrop-blur-sm transition-opacity"></div>
    <div class="absolute inset-y-0 right-0 flex max-w-full pl-10">
        <div class="w-screen max-w-2xl bg-white shadow-2xl flex flex-col">
            {{-- Panel Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-surface-100 bg-surface-50">
                <div>
                    <h2 class="text-base font-bold text-surface-900">Cargo Booking Details</h2>
                    <p id="panel-consignment-number" class="text-xs font-mono text-brand-600 mt-0.5"></p>
                </div>
                <button onclick="closePanel()" class="h-8 w-8 rounded-lg flex items-center justify-center hover:bg-surface-200 transition-colors text-surface-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            {{-- Panel Body --}}
            <div id="panel-body" class="flex-1 overflow-y-auto px-6 py-5 space-y-6">
                <div class="flex justify-center py-12 text-surface-400">
                    <i class="fas fa-circle-notch fa-spin text-2xl text-brand-400"></i>
                </div>
            </div>
        </div>
    </div>
</div>


@push('scripts')
<script>
const STATUS = {
    pending_payment: { label: 'Awaiting Payment', cls: 'bg-orange-100 text-orange-700 border-orange-200' },
    booked:     { label: 'Booked',      cls: 'bg-amber-100 text-amber-700 border-amber-200' },
    loaded:     { label: 'Loaded',      cls: 'bg-blue-100 text-blue-700 border-blue-200' },
    in_transit: { label: 'In Transit',  cls: 'bg-violet-100 text-violet-700 border-violet-200' },
    delivered:  { label: 'Delivered',   cls: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
    collected:  { label: 'Collected',   cls: 'bg-teal-100 text-teal-700 border-teal-200' },
    cancelled:  { label: 'Cancelled',   cls: 'bg-red-100 text-red-700 border-red-200' },
};

function badge(status) {
    const s = STATUS[status] || { label: status, cls: 'bg-surface-100 text-surface-600 border-surface-200' };
    return `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border ${s.cls}">${s.label}</span>`;
}

function fmt(n) { return new Intl.NumberFormat().format(n || 0); }
function fmtDate(d) { return new Date(d).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }); }

let currentPage = 1;

function fetchConsignments(page = 1) {
    currentPage = page;
    const search = document.getElementById('search-input').value;
    const status = document.getElementById('status-filter').value;
    let url = `/admin/cargo/api/consignments?page=${page}`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    if (status) url += `&status=${encodeURIComponent(status)}`;

    document.getElementById('consignments-tbody').innerHTML = `<tr><td colspan="8" class="px-6 py-14 text-center"><div class="flex flex-col items-center gap-2 text-surface-400"><i class="fas fa-circle-notch fa-spin text-2xl text-brand-400"></i><span class="text-sm">Loading...</span></div></td></tr>`;

    fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => { renderTable(data.data || []); renderPagination(data); })
        .catch(() => {
            document.getElementById('consignments-tbody').innerHTML = `<tr><td colspan="8" class="px-6 py-8 text-center text-red-400 text-sm">Failed to load data.</td></tr>`;
        });
}

function renderTable(rows) {
    const tbody = document.getElementById('consignments-tbody');
    if (rows.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="px-6 py-14 text-center text-surface-400 text-sm"><i class="fas fa-box-open text-2xl mb-2 block text-surface-300"></i>No bookings found.</td></tr>`;
        return;
    }
    tbody.innerHTML = rows.map(c => `
        <tr class="border-b border-surface-50 hover:bg-brand-50/30 transition-colors">
            <td class="px-6 py-3.5">
                <span class="font-mono text-xs font-bold text-brand-600 bg-brand-50 px-2 py-0.5 rounded">${c.consignment_number}</span>
            </td>
            <td class="px-4 py-3.5 text-xs text-surface-500">${fmtDate(c.created_at)}</td>
            <td class="px-4 py-3.5 font-medium text-surface-800">${c.sender_name}</td>
            <td class="px-4 py-3.5 text-surface-600">${c.receiver_name}</td>
            <td class="px-4 py-3.5 text-surface-500 text-xs">${c.route?.name || '—'}</td>
            <td class="px-4 py-3.5">${badge(c.status)}</td>
            <td class="px-6 py-3.5 text-right font-semibold tabular-nums text-surface-800">${fmt(c.total_amount_tzs)}</td>
            <td class="px-4 py-3.5 text-center">
                <button onclick="window.location.href='/admin/cargo/bookings/' + ${c.id}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-brand-600 bg-brand-50 hover:bg-brand-100 rounded-lg transition-colors border border-brand-100">
                    <i class="fas fa-eye text-xs"></i> View
                </button>
            </td>
        </tr>
    `).join('');
}

function renderPagination(meta) {
    const info = document.getElementById('pagination-info');
    const ctrl = document.getElementById('pagination-controls');
    info.textContent = `Showing ${meta.from ?? 0}–${meta.to ?? 0} of ${meta.total ?? 0}`;
    ctrl.innerHTML = '';
    if ((meta.last_page || 1) <= 1) return;

    const btn = (label, page, disabled = false) =>
        `<button onclick="fetchConsignments(${page})" ${disabled ? 'disabled' : ''}
            class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-colors ${disabled ? 'opacity-40 cursor-not-allowed bg-surface-50 text-surface-400 border-surface-200' : 'bg-white text-surface-700 border-surface-200 hover:border-brand-400 hover:text-brand-600'}">${label}</button>`;

    ctrl.innerHTML += btn('← Prev', meta.current_page - 1, meta.current_page <= 1);
    ctrl.innerHTML += `<span class="px-3 py-1.5 text-xs text-surface-500">Page ${meta.current_page} / ${meta.last_page}</span>`;
    ctrl.innerHTML += btn('Next →', meta.current_page + 1, meta.current_page >= meta.last_page);
}

function viewDetails(id) {
    openPanel();
    document.getElementById('panel-body').innerHTML = `<div class="flex justify-center py-12"><i class="fas fa-circle-notch fa-spin text-2xl text-brand-400"></i></div>`;

    fetch(`/admin/cargo/api/consignments/${id}`, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(c => {
            document.getElementById('panel-consignment-number').textContent = c.consignment_number;
            const currency = c.total_amount_usd > 0 ? 'USD' : 'TZS';
            const amount = currency === 'USD' ? c.total_amount_usd : c.total_amount_tzs;

            const itemRows = (c.items || []).map(i => `
                <tr class="border-b border-surface-50">
                    <td class="py-2 pr-3"><code class="text-xs bg-surface-100 px-1.5 py-0.5 rounded">${i.qr_code_token || 'N/A'}</code></td>
                    <td class="py-2 pr-3 text-xs text-surface-700">${i.cargo_type?.name || 'N/A'}</td>
                    <td class="py-2 pr-3 text-xs text-surface-500">${i.description || '—'}</td>
                    <td class="py-2 pr-3 text-xs tabular-nums">${i.weight_kg || 0} kg</td>
                    <td class="py-2 pr-3 text-xs tabular-nums font-semibold">${fmt(i.final_fare)}</td>
                    <td class="py-2">${badge(i.status)}</td>
                </tr>
            `).join('');

            const eventRows = (c.tracking_events || []).slice(0, 10).map(e => `
                <div class="flex items-start gap-3 py-2.5 border-b border-surface-50 last:border-0">
                    <div class="h-5 w-5 rounded-full bg-brand-100 flex items-center justify-center mt-0.5 shrink-0">
                        <div class="h-2 w-2 rounded-full bg-brand-600"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-surface-800">${e.status}</p>
                        <p class="text-xs text-surface-500 mt-0.5">${e.description || ''}</p>
                    </div>
                    <span class="text-xs text-surface-400 shrink-0">${new Date(e.created_at).toLocaleString()}</span>
                </div>
            `).join('');

            document.getElementById('panel-body').innerHTML = `
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div>${badge(c.status)}</div>
                    <div class="text-right flex items-center gap-4">
                        <button onclick="window.open('/portal/cargo/receipt/' + ${c.id}, '_blank')" class="inline-flex items-center gap-2 px-4 py-2 border border-surface-200 text-surface-600 text-sm font-semibold rounded-xl hover:bg-surface-50 transition-colors">
                            <i class="fas fa-print text-xs"></i> Print Receipt
                        </button>
                        <div>
                            <p class="text-2xl font-extrabold text-surface-900 tabular-nums">${fmt(amount)}</p>
                            <p class="text-xs text-surface-400">${currency}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-surface-50 rounded-xl p-4 border border-surface-100">
                        <p class="text-[10px] font-bold text-surface-400 uppercase tracking-widest mb-2">Sender</p>
                        <p class="font-bold text-surface-900">${c.sender_name}</p>
                        <p class="text-sm text-surface-500 mt-0.5">${c.sender_phone || 'N/A'}</p>
                    </div>
                    <div class="bg-surface-50 rounded-xl p-4 border border-surface-100">
                        <p class="text-[10px] font-bold text-surface-400 uppercase tracking-widest mb-2">Receiver</p>
                        <p class="font-bold text-surface-900">${c.receiver_name}</p>
                        <p class="text-sm text-surface-500 mt-0.5">${c.receiver_phone || 'N/A'}</p>
                    </div>
                </div>

                <div>
                    <h3 class="text-xs font-bold text-surface-500 uppercase tracking-wider mb-3">Cargo Items</h3>
                    <div class="overflow-x-auto rounded-xl border border-surface-100">
                        <table class="w-full text-sm">
                            <thead class="bg-surface-50">
                                <tr>
                                    <th class="text-left py-2 px-3 text-[10px] font-bold text-surface-400 uppercase">Barcode</th>
                                    <th class="text-left py-2 px-3 text-[10px] font-bold text-surface-400 uppercase">Type</th>
                                    <th class="text-left py-2 px-3 text-[10px] font-bold text-surface-400 uppercase">Desc</th>
                                    <th class="text-left py-2 px-3 text-[10px] font-bold text-surface-400 uppercase">Weight</th>
                                    <th class="text-left py-2 px-3 text-[10px] font-bold text-surface-400 uppercase">Fare</th>
                                    <th class="text-left py-2 px-3 text-[10px] font-bold text-surface-400 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-50 px-3">${itemRows || '<tr><td colspan="6" class="py-4 text-center text-xs text-surface-400">No items</td></tr>'}</tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <h3 class="text-xs font-bold text-surface-500 uppercase tracking-wider mb-3">Tracking History</h3>
                    <div class="rounded-xl border border-surface-100 divide-y divide-surface-50 px-4">
                        ${eventRows || '<p class="py-4 text-center text-xs text-surface-400">No tracking events.</p>'}
                    </div>
                </div>
            `;
        })
        .catch(() => {
            document.getElementById('panel-body').innerHTML = `<p class="text-center text-red-400 text-sm py-8">Failed to load details.</p>`;
        });
}

function openPanel() {
    const panel = document.getElementById('detail-panel');
    panel.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closePanel() {
    document.getElementById('detail-panel').classList.add('hidden');
    document.body.style.overflow = '';
}

document.getElementById('detail-backdrop').addEventListener('click', closePanel);
document.getElementById('search-btn').addEventListener('click', () => fetchConsignments(1));
document.getElementById('status-filter').addEventListener('change', () => fetchConsignments(1));
document.getElementById('search-input').addEventListener('keyup', e => { if (e.key === 'Enter') fetchConsignments(1); });

document.addEventListener('DOMContentLoaded', () => fetchConsignments());
</script>
@endpush
</x-layouts.admin>
