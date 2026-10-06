<x-layouts.admin>
<x-slot:title>Loading Manifest</x-slot:title>
<x-slot:header>Loading Manifest</x-slot:header>

<div class="px-4 sm:px-6 lg:px-8 py-6 space-y-5">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-surface-900 tracking-tight">Loading Manifest</h1>
            <p class="text-sm text-surface-500 mt-0.5">Generate a printable cargo manifest for any transport service.</p>
        </div>
    </div>

    {{-- Service Selector --}}
    <div class="bg-white rounded-2xl border border-surface-200/80 shadow-sm px-6 py-5">
        <label class="block text-xs font-bold text-surface-600 uppercase tracking-wider mb-3">Select Transport Service</label>
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1 max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-ship text-surface-400 text-sm"></i>
                </div>
                <select id="service-id"
                    class="w-full pl-9 pr-4 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all appearance-none bg-white">
                    <option value="">Loading services...</option>
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <i class="fas fa-chevron-down text-surface-400 text-xs"></i>
                </div>
            </div>
            <button onclick="loadManifest()" id="generate-btn"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm shadow-brand-200">
                <i class="fas fa-file-alt text-xs"></i> Generate Manifest
            </button>
        </div>
    </div>

    {{-- Manifest Content (hidden until loaded) --}}
    <div id="manifest-content" class="hidden space-y-5">

        {{-- Service Header --}}
        <div class="bg-white rounded-2xl border border-surface-200/80 shadow-sm px-6 py-5">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-[10px] font-bold text-brand-600 uppercase tracking-widest">Manifest</span>
                        <div class="h-px flex-1 bg-brand-100"></div>
                    </div>
                    <h2 id="m-route" class="text-xl font-extrabold text-surface-900"></h2>
                    <div class="flex flex-wrap gap-4 mt-2">
                        <span class="flex items-center gap-1.5 text-sm text-surface-600">
                            <i class="fas fa-ship text-surface-400 text-xs"></i>
                            <span id="m-unit"></span>
                        </span>
                        <span class="flex items-center gap-1.5 text-sm text-surface-600">
                            <i class="fas fa-clock text-surface-400 text-xs"></i>
                            <span id="m-departure"></span>
                        </span>
                    </div>
                </div>
                <button onclick="window.print()"
                    class="inline-flex items-center gap-2 px-4 py-2.5 border border-surface-200 text-surface-600 text-sm font-semibold rounded-xl hover:bg-surface-50 transition-colors shrink-0">
                    <i class="fas fa-print text-xs"></i> Print Manifest
                </button>
            </div>
        </div>

        {{-- KPI Row --}}
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-4" id="printable-area">
            <div class="bg-white rounded-2xl border border-surface-200/80 shadow-sm px-5 py-4 text-center">
                <p class="text-[10px] font-bold text-surface-400 uppercase tracking-widest">Total Consignments</p>
                <p class="text-3xl font-extrabold text-surface-900 mt-2" id="m-total-cons">—</p>
            </div>
            <div class="bg-white rounded-2xl border border-surface-200/80 shadow-sm px-5 py-4 text-center">
                <p class="text-[10px] font-bold text-surface-400 uppercase tracking-widest">Total Weight</p>
                <p class="text-3xl font-extrabold text-surface-900 mt-2"><span id="m-total-wt">—</span> <span class="text-base font-normal text-surface-400">kg</span></p>
            </div>
            <div class="bg-white rounded-2xl border border-surface-200/80 shadow-sm px-5 py-4 text-center">
                <p class="text-[10px] font-bold text-surface-400 uppercase tracking-widest">Total Volume</p>
                <p class="text-3xl font-extrabold text-surface-900 mt-2"><span id="m-total-vol">—</span> <span class="text-base font-normal text-surface-400">cbm</span></p>
            </div>
            <div class="bg-white rounded-2xl border border-red-100/80 shadow-sm px-5 py-4 text-center">
                <p class="text-[10px] font-bold text-red-400 uppercase tracking-widest">⚠️ Hazmat Items</p>
                <p class="text-3xl font-extrabold text-red-600 mt-2" id="m-hazmat">—</p>
            </div>
        </div>

        {{-- Consignment Table --}}
        <div class="bg-white rounded-2xl border border-surface-200/80 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-surface-100">
                <h2 class="text-base font-bold text-surface-900">Consignment Breakdown</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-50 border-b border-surface-100">
                            <th class="text-left px-6 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">C-Number</th>
                            <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Sender</th>
                            <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Receiver</th>
                            <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Status</th>
                            <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Items</th>
                        </tr>
                    </thead>
                    <tbody id="m-table-body" class="divide-y divide-surface-50"></tbody>
                </table>
            </div>
        </div>

    </div>

</div>


@push('scripts')
<script>
const STATUS = {
    booked:     { label: 'Booked',     cls: 'bg-amber-100 text-amber-700 border-amber-200' },
    loaded:     { label: 'Loaded',     cls: 'bg-blue-100 text-blue-700 border-blue-200' },
    in_transit: { label: 'In Transit', cls: 'bg-violet-100 text-violet-700 border-violet-200' },
    delivered:  { label: 'Delivered',  cls: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
    collected:  { label: 'Collected',  cls: 'bg-teal-100 text-teal-700 border-teal-200' },
    cancelled:  { label: 'Cancelled',  cls: 'bg-red-100 text-red-700 border-red-200' },
};

function badge(status) {
    const s = STATUS[status] || { label: status, cls: 'bg-surface-100 text-surface-600 border-surface-200' };
    return `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border ${s.cls}">${s.label}</span>`;
}

function loadManifest() {
    const sid = document.getElementById('service-id').value;
    if (!sid) return alert('Please select a Transport Service.');

    const btn = document.getElementById('generate-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin text-xs"></i> Loading...';
    document.getElementById('manifest-content').classList.add('hidden');

    fetch(`/admin/cargo/api/manifest/${sid}`, { headers: { 'Accept': 'application/json' } })
        .then(r => {
            if (!r.ok) throw new Error('Manifest not found or unauthorized');
            return r.json();
        })
        .then(data => {
            document.getElementById('m-route').textContent     = data.route;
            document.getElementById('m-unit').textContent      = data.unit_name;
            document.getElementById('m-departure').textContent = data.departure;
            document.getElementById('m-total-cons').textContent = data.total_consignments;
            document.getElementById('m-total-wt').textContent   = data.total_weight_kg;
            document.getElementById('m-total-vol').textContent  = data.total_volume_cbm;
            document.getElementById('m-hazmat').textContent     = data.hazmat_count;

            const tbody = document.getElementById('m-table-body');
            tbody.innerHTML = data.consignments.map(c => {
                const items = c.items.map(i => {
                    const flags = [
                        i.is_fragile ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-orange-100 text-orange-600 border border-orange-200 ml-1">🔶 Fragile</span>' : ''
                    ].join('');
                    return `<li class="text-xs text-surface-600 py-0.5 flex items-center gap-1">
                        <code class="bg-surface-100 px-1 rounded text-[10px]">${i.barcode}</code>
                        <span>${i.type}</span> — <span class="font-medium">${i.weight}kg</span>
                        ${badge(i.status)} ${flags}
                    </li>`;
                }).join('');

                return `
                <tr class="hover:bg-surface-50/80 transition-colors">
                    <td class="px-6 py-3.5 align-top">
                        <span class="font-mono text-xs font-bold text-brand-600 bg-brand-50 px-2 py-0.5 rounded">${c.consignment_number}</span>
                    </td>
                    <td class="px-4 py-3.5 font-medium text-surface-800 align-top text-sm">${c.sender}</td>
                    <td class="px-4 py-3.5 text-surface-600 align-top text-sm">${c.receiver}</td>
                    <td class="px-4 py-3.5 align-top">${badge(c.status)}</td>
                    <td class="px-4 py-3.5 align-top"><ul class="space-y-0.5">${items}</ul></td>
                </tr>`;
            }).join('');

            document.getElementById('manifest-content').classList.remove('hidden');
        })
        .catch(err => alert(err.message))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-file-alt text-xs"></i> Generate Manifest';
        });
}

// Allow pressing Enter in the input
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('service-id').addEventListener('keyup', e => {
        if (e.key === 'Enter') loadManifest();
    });
});

// Load transport services for the dropdown on page load
document.addEventListener('DOMContentLoaded', () => {
    fetch('/admin/cargo/api/services', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(res => {
            const services = res.data || res;
            const select = document.getElementById('service-id');
            select.innerHTML = '<option value="">-- Select a Transport Service --</option>';
            services.forEach(s => {
                const depDateStr = s.departure_date + 'T' + (s.departure_time || '00:00:00');
                const date = new Date(depDateStr).toLocaleString('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = `${s.service_number} | ${s.route?.name || 'Route'} | ${s.transport_unit?.name || 'Unit'} | ${date}`;
                select.appendChild(opt);
            });
        })
        .catch(err => {
            console.error('Failed to load services:', err);
            document.getElementById('service-id').innerHTML = '<option value="">Error loading services</option>';
        });
});
</script>

<style>
@media print {
    body * { visibility: hidden; }
    #printable-area, #printable-area *, #m-table-body, #m-table-body * { visibility: visible; }
    #printable-area { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>
@endpush
</x-layouts.admin>
