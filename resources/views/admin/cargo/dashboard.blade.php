<x-layouts.admin>
<x-slot:title>Cargo Dashboard</x-slot:title>
<x-slot:header>Cargo Dashboard</x-slot:header>

<div class="px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-surface-900 tracking-tight">Cargo Operations</h1>
            <p class="text-sm text-surface-500 mt-0.5">Live overview of shipments, revenue, and activity.</p>
        </div>
        <div class="flex items-center gap-2">
            <span id="last-updated" class="text-xs text-surface-400 italic"></span>
            <button onclick="refreshDashboard()" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium rounded-lg border border-surface-300 text-surface-600 hover:bg-surface-100 transition-colors">
                <i class="fas fa-rotate-right text-xs"></i> Refresh
            </button>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        {{-- Today's Consignments --}}
        <div class="bg-white rounded-2xl shadow-sm border border-surface-200/80 p-5 hover-lift relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-brand-50 rounded-full -translate-y-8 translate-x-8 opacity-60"></div>
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-surface-500 uppercase tracking-widest">Today's Consignments</p>
                    <p class="text-3xl font-extrabold text-surface-900 mt-2" id="stat-consignments">—</p>
                    <p class="text-xs text-surface-400 mt-1">Booked today</p>
                </div>
                <div class="h-11 w-11 rounded-xl bg-brand-600 flex items-center justify-center shadow-lg shadow-brand-200/50 shrink-0">
                    <i class="fas fa-box text-white text-base"></i>
                </div>
            </div>
        </div>

        {{-- Revenue --}}
        <div class="bg-white rounded-2xl shadow-sm border border-surface-200/80 p-5 hover-lift relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-50 rounded-full -translate-y-8 translate-x-8 opacity-60"></div>
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-surface-500 uppercase tracking-widest">Today's Revenue</p>
                    <p class="text-3xl font-extrabold text-surface-900 mt-2" id="stat-revenue">—</p>
                    <p class="text-xs text-surface-400 mt-1">TZS collected</p>
                </div>
                <div class="h-11 w-11 rounded-xl bg-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-200/50 shrink-0">
                    <i class="fas fa-money-bill-wave text-white text-base"></i>
                </div>
            </div>
        </div>

        {{-- Active Shipments --}}
        <div class="bg-white rounded-2xl shadow-sm border border-surface-200/80 p-5 hover-lift relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-amber-50 rounded-full -translate-y-8 translate-x-8 opacity-60"></div>
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-surface-500 uppercase tracking-widest">Active Shipments</p>
                    <p class="text-3xl font-extrabold text-surface-900 mt-2" id="stat-active">—</p>
                    <p class="text-xs text-surface-400 mt-1">In transit</p>
                </div>
                <div class="h-11 w-11 rounded-xl bg-amber-500 flex items-center justify-center shadow-lg shadow-amber-200/50 shrink-0">
                    <i class="fas fa-truck text-white text-base"></i>
                </div>
            </div>
        </div>

        {{-- Pending --}}
        <div class="bg-white rounded-2xl shadow-sm border border-surface-200/80 p-5 hover-lift relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-violet-50 rounded-full -translate-y-8 translate-x-8 opacity-60"></div>
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-surface-500 uppercase tracking-widest">Total Consignments</p>
                    <p class="text-3xl font-extrabold text-surface-900 mt-2" id="stat-total">—</p>
                    <p class="text-xs text-surface-400 mt-1">All time</p>
                </div>
                <div class="h-11 w-11 rounded-xl bg-violet-600 flex items-center justify-center shadow-lg shadow-violet-200/50 shrink-0">
                    <i class="fas fa-chart-bar text-white text-base"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Capacity Overview --}}
    <div id="capacity-overview-container" class="hidden bg-white rounded-2xl shadow-sm border border-surface-200/80 mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-surface-200/60">
            <h3 class="text-sm font-bold text-surface-800 flex items-center gap-2"><i class="fas fa-ship text-brand-500"></i> Today's Voyage Capacity</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-surface-200 text-sm">
                <thead class="bg-surface-50/80"><tr>
                    <th class="px-5 py-3 text-left text-[10px] font-bold text-surface-500 uppercase tracking-widest">Ferry</th>
                    <th class="px-5 py-3 text-left text-[10px] font-bold text-surface-500 uppercase tracking-widest">Route</th>
                    <th class="px-5 py-3 text-left text-[10px] font-bold text-surface-500 uppercase tracking-widest">Departure</th>
                    <th class="px-5 py-3 text-left text-[10px] font-bold text-surface-500 uppercase tracking-widest">Lane Meters</th>
                    <th class="px-5 py-3 text-left text-[10px] font-bold text-surface-500 uppercase tracking-widest">Weight (KG)</th>
                    <th class="px-5 py-3 text-left text-[10px] font-bold text-surface-500 uppercase tracking-widest">Volume (CBM)</th>
                </tr></thead>
                <tbody id="capacity-overview-body" class="divide-y divide-surface-200/60">
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent Consignments Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-surface-200/80 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-surface-100">
            <div>
                <h2 class="text-base font-bold text-surface-900">Recent Consignments</h2>
                <p class="text-xs text-surface-400 mt-0.5">Last 10 bookings across all routes</p>
            </div>
            <a href="{{ route('admin.cargo.consignments') }}"
               class="text-sm font-semibold text-brand-600 hover:text-brand-700 flex items-center gap-1 transition-colors">
                View all <i class="fas fa-arrow-right text-xs"></i>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-50 border-b border-surface-100">
                        <th class="text-left px-6 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Consignment No.</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Sender</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Receiver</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Status</th>
                        <th class="text-right px-6 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Amount</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody id="recent-consignments-body">
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-surface-400">
                            <div class="flex flex-col items-center gap-2">
                                <i class="fas fa-circle-notch fa-spin text-2xl text-brand-400"></i>
                                <span class="text-sm">Loading consignments...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

@push('scripts')
<script>
const statusConfig = {
    booked:     { label: 'Booked',     cls: 'bg-amber-100 text-amber-700 border-amber-200' },
    loaded:     { label: 'Loaded',     cls: 'bg-blue-100 text-blue-700 border-blue-200' },
    in_transit: { label: 'In Transit', cls: 'bg-violet-100 text-violet-700 border-violet-200' },
    delivered:  { label: 'Delivered',  cls: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
    collected:  { label: 'Collected',  cls: 'bg-teal-100 text-teal-700 border-teal-200' },
    cancelled:  { label: 'Cancelled',  cls: 'bg-red-100 text-red-700 border-red-200' },
};

function statusBadge(status) {
    const s = statusConfig[status] || { label: status, cls: 'bg-surface-100 text-surface-600 border-surface-200' };
    return `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border ${s.cls}">${s.label}</span>`;
}

function refreshDashboard() {
    document.getElementById('stat-consignments').innerHTML = '<i class="fas fa-circle-notch fa-spin text-brand-400"></i>';
    document.getElementById('stat-revenue').innerHTML      = '<i class="fas fa-circle-notch fa-spin text-emerald-400"></i>';
    document.getElementById('stat-active').innerHTML       = '<i class="fas fa-circle-notch fa-spin text-amber-400"></i>';
    document.getElementById('stat-total').innerHTML        = '<i class="fas fa-circle-notch fa-spin text-violet-400"></i>';
    loadStats();
    loadRecent();
}

function loadStats() {
    fetch('/admin/cargo/api/dashboard', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            document.getElementById('stat-consignments').textContent = data.consignments_today ?? '—';
            document.getElementById('stat-revenue').textContent      = data.revenue_tzs_today != null
                ? new Intl.NumberFormat('en-TZ', { style: 'currency', currency: 'TZS', maximumFractionDigits: 0 }).format(data.revenue_tzs_today)
                : '—';
            document.getElementById('stat-active').textContent = data.active_shipments ?? '—';
            document.getElementById('stat-total').textContent  = data.total_consignments ?? '—';
            document.getElementById('last-updated').textContent = 'Updated ' + new Date().toLocaleTimeString();
            
            // Render Capacity Overview
            if (data.capacityOverview && data.capacityOverview.length > 0) {
                document.getElementById('capacity-overview-container').classList.remove('hidden');
                document.getElementById('capacity-overview-body').innerHTML = data.capacityOverview.map(v => {
                    const metrics = ['lane_meters', 'weight_kg', 'volume_cbm'].map(m => {
                        const pct = v[m].total > 0 ? Math.round((v[m].used / v[m].total) * 100) : 0;
                        const colorClass = pct > 80 ? 'bg-rose-500' : (pct > 50 ? 'bg-amber-500' : 'bg-emerald-500');
                        return `
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-2 bg-surface-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full ${colorClass}" style="width:${pct}%"></div>
                                </div>
                                <span class="text-[10px] font-mono font-bold text-surface-500 w-10 text-right">${pct}%</span>
                            </div>
                        </td>`;
                    }).join('');
                    
                    return `
                    <tr class="hover:bg-surface-50/60">
                        <td class="px-5 py-3 font-bold text-surface-800">${v.ferry || '—'}</td>
                        <td class="px-5 py-3 text-surface-600">${v.route || '—'}</td>
                        <td class="px-5 py-3 font-mono text-surface-600">${v.departure || '—'}</td>
                        ${metrics}
                    </tr>`;
                }).join('');
            } else {
                document.getElementById('capacity-overview-container').classList.add('hidden');
            }
        })
        .catch(() => {
            ['stat-consignments','stat-revenue','stat-active','stat-total'].forEach(id => {
                document.getElementById(id).textContent = 'N/A';
            });
        });
}

function loadRecent() {
    fetch('/admin/cargo/api/consignments?per_page=10', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('recent-consignments-body');
            const rows = (data.data || []);
            if (rows.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-12 text-center text-surface-400 text-sm">No consignments found.</td></tr>`;
                return;
            }
            tbody.innerHTML = rows.map(c => `
                <tr class="border-b border-surface-50 hover:bg-surface-50/80 transition-colors">
                    <td class="px-6 py-3.5">
                        <span class="font-mono text-xs font-bold text-brand-600 bg-brand-50 px-2 py-1 rounded">${c.consignment_number}</span>
                    </td>
                    <td class="px-4 py-3.5 text-surface-700 font-medium">${c.sender_name}</td>
                    <td class="px-4 py-3.5 text-surface-600">${c.receiver_name}</td>
                    <td class="px-4 py-3.5">${statusBadge(c.status)}</td>
                    <td class="px-6 py-3.5 text-right font-semibold text-surface-800 tabular-nums">${new Intl.NumberFormat().format(c.total_amount_tzs)} <span class="text-xs font-normal text-surface-400">TZS</span></td>
                    <td class="px-4 py-3.5 text-surface-500 text-xs">${new Date(c.created_at).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' })}</td>
                </tr>
            `).join('');
        })
        .catch(() => {
            document.getElementById('recent-consignments-body').innerHTML = `<tr><td colspan="6" class="px-6 py-8 text-center text-red-400 text-sm">Failed to load data.</td></tr>`;
        });
}

document.addEventListener('DOMContentLoaded', () => {
    loadStats();
    loadRecent();
});
</script>
@endpush
</x-layouts.admin>
