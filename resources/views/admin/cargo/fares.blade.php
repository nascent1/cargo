<x-layouts.admin>
<x-slot:title>Cargo Fares</x-slot:title>
<x-slot:header>Cargo Fares</x-slot:header>

<div class="px-4 sm:px-6 lg:px-8 py-6 space-y-5">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-surface-900 tracking-tight">Cargo Fares</h1>
            <p class="text-sm text-surface-500 mt-0.5">Configure pricing rules by cargo type, route, and transport unit.</p>
        </div>
        <button onclick="openCreateFareModal()"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm shadow-brand-200">
            <i class="fas fa-plus text-xs"></i> New Fare Rule
        </button>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl border border-surface-200/80 shadow-sm px-5 py-4">
        <div class="flex flex-col sm:flex-row gap-3 items-end">
            <div class="space-y-1.5 flex-1">
                <label class="text-xs font-semibold text-surface-500 uppercase tracking-wider">Filter by Type</label>
                <select id="filter-type" onchange="fetchFares()"
                    class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-surface-50 focus:bg-white text-surface-700">
                    <option value="">All Types</option>
                </select>
            </div>
            <div class="space-y-1.5 flex-1">
                <label class="text-xs font-semibold text-surface-500 uppercase tracking-wider">Filter by Route</label>
                <select id="filter-route" onchange="fetchFares()"
                    class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-surface-50 focus:bg-white text-surface-700">
                    <option value="">All Routes</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-surface-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-50 border-b border-surface-100">
                        <th class="text-left px-5 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Cargo Type</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Route</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Transport Unit</th>
                        <th class="text-right px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Flat TZS</th>
                        <th class="text-right px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Flat USD</th>
                        <th class="text-right px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Per Kg TZS</th>
                        <th class="text-right px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Per CBM TZS</th>
                        <th class="text-right px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Min Charge</th>
                        <th class="text-center px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Status</th>
                        <th class="text-center px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="fares-tbody">
                    <tr>
                        <td colspan="10" class="px-6 py-14 text-center">
                            <div class="flex flex-col items-center gap-2 text-surface-400">
                                <i class="fas fa-circle-notch fa-spin text-2xl text-brand-400"></i>
                                <span class="text-sm">Loading fares...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Create / Edit Fare Modal --}}
<div id="fareModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-surface-950/50 backdrop-blur-sm" onclick="closeFareModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-surface-100 bg-surface-50 sticky top-0">
            <h2 id="fareModalTitle" class="text-base font-bold text-surface-900">New Cargo Fare</h2>
            <button onclick="closeFareModal()" class="h-8 w-8 rounded-lg flex items-center justify-center hover:bg-surface-200 transition-colors text-surface-500">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="fareForm" onsubmit="saveFare(event)" class="p-6 space-y-5">
            <input type="hidden" id="fare-id">

            {{-- Selectors --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-surface-700">Cargo Type <span class="text-red-500">*</span></label>
                    <select id="fare-cargo-type" required
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-white truncate"></select>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-surface-700">Route <span class="text-xs font-normal text-surface-400">(blank = all)</span></label>
                    <select id="fare-route"
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-white truncate">
                        <option value="">All Routes (Generic)</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-surface-700">Transport Unit <span class="text-xs font-normal text-surface-400">(blank = all)</span></label>
                    <select id="fare-transport-unit"
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-white truncate">
                        <option value="">All Units (Generic)</option>
                    </select>
                </div>
            </div>

            {{-- Section: Flat Pricing --}}
            <div class="rounded-xl border border-surface-100 overflow-hidden">
                <div class="bg-surface-50 px-4 py-2.5 border-b border-surface-100">
                    <h3 class="text-xs font-bold text-surface-600 uppercase tracking-wider">Flat Pricing</h3>
                </div>
                <div class="p-4 grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-surface-700">Price TZS <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-3 flex items-center text-xs text-surface-400 font-semibold">TZS</span>
                            <input type="number" step="0.01" id="fare-price-tzs" required value="0"
                                class="w-full pl-10 pr-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all">
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-surface-700">Price USD <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-3 flex items-center text-xs text-surface-400 font-semibold">USD</span>
                            <input type="number" step="0.01" id="fare-price-usd" required value="0"
                                class="w-full pl-10 pr-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section: Weight-Based --}}
            <div class="rounded-xl border border-surface-100 overflow-hidden">
                <div class="bg-surface-50 px-4 py-2.5 border-b border-surface-100">
                    <h3 class="text-xs font-bold text-surface-600 uppercase tracking-wider">Weight-Based Pricing</h3>
                </div>
                <div class="p-4 grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-surface-700">Per Kg (TZS)</label>
                        <input type="number" step="0.01" id="fare-per-kg-tzs" value="0"
                            class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all">
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-surface-700">Per Kg (USD)</label>
                        <input type="number" step="0.01" id="fare-per-kg-usd" value="0"
                            class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all">
                    </div>
                </div>
            </div>

            {{-- Section: Volume-Based --}}
            <div class="rounded-xl border border-surface-100 overflow-hidden">
                <div class="bg-surface-50 px-4 py-2.5 border-b border-surface-100">
                    <h3 class="text-xs font-bold text-surface-600 uppercase tracking-wider">Volume-Based Pricing</h3>
                </div>
                <div class="p-4 grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-surface-700">Per CBM (TZS)</label>
                        <input type="number" step="0.01" id="fare-per-cbm-tzs" value="0"
                            class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all">
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-surface-700">Per CBM (USD)</label>
                        <input type="number" step="0.01" id="fare-per-cbm-usd" value="0"
                            class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all">
                    </div>
                </div>
            </div>

            {{-- Min Charges + Status --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-surface-700">Min Charge (TZS)</label>
                    <input type="number" step="0.01" id="fare-min-tzs" value="0"
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-surface-700">Min Charge (USD)</label>
                    <input type="number" step="0.01" id="fare-min-usd" value="0"
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-surface-700">Status <span class="text-red-500">*</span></label>
                    <select id="fare-status" required
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-white">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2 border-t border-surface-100">
                <button type="button" onclick="closeFareModal()"
                    class="px-5 py-2.5 text-sm font-semibold text-surface-600 rounded-xl border border-surface-200 hover:bg-surface-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" id="save-fare-btn"
                    class="px-6 py-2.5 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition-colors shadow-sm shadow-brand-200 flex items-center gap-2">
                    <i class="fas fa-save text-xs"></i> Save Fare
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Delete Confirmation --}}
<div id="deleteFareModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-surface-950/50 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
        <div class="bg-red-50 border-b border-red-100 px-6 py-4 flex items-center gap-3">
            <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 shrink-0">
                <i class="fas fa-trash-alt"></i>
            </div>
            <div>
                <h3 class="font-bold text-red-900">Delete Fare Rule</h3>
                <p class="text-xs text-red-600 mt-0.5">This action cannot be undone.</p>
            </div>
        </div>
        <div class="px-6 py-5">
            <p class="text-sm text-surface-600">Are you sure you want to delete this fare rule? This may affect future bookings.</p>
            <input type="hidden" id="delete-fare-id">
        </div>
        <div class="flex gap-3 px-6 pb-5 justify-end">
            <button onclick="closeDeleteModal()" class="px-5 py-2.5 text-sm font-semibold text-surface-600 rounded-xl border border-surface-200 hover:bg-surface-50 transition-colors">Cancel</button>
            <button onclick="confirmDeleteFare()" class="px-5 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-colors flex items-center gap-2">
                <i class="fas fa-trash-alt text-xs"></i> Delete
            </button>
        </div>
    </div>
</div>


@push('scripts')
<script>
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
const fmt  = v => Number(v || 0).toLocaleString();

function loadDropdowns() {
    // Load cargo types
    fetch('/admin/cargo/api/types', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(types => {
            ['filter-type', 'fare-cargo-type'].forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                types.forEach(t => {
                    const o = document.createElement('option');
                    o.value = t.id;
                    o.textContent = `${t.icon || ''} ${t.name} (${t.code})`;
                    el.appendChild(o);
                });
            });
        }).catch(e => console.warn('Types load error:', e));

    // Load routes for filter and fare form
    fetch('/admin/cargo/api/routes', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(routes => {
            ['filter-route', 'fare-route'].forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                routes.forEach(rt => {
                    const o = document.createElement('option');
                    o.value = rt.id;
                    o.textContent = rt.name;
                    el.appendChild(o);
                });
            });
        }).catch(e => console.warn('Routes load error:', e));

    // Load transport units
    fetch('/admin/cargo/api/transport-units', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(units => {
            const el = document.getElementById('fare-transport-unit');
            if (!el) return;
            units.forEach(u => {
                const o = document.createElement('option');
                o.value = u.id;
                o.textContent = `${u.name} (${u.registration_number || 'N/A'})`;
                el.appendChild(o);
            });
        }).catch(e => console.warn('Transport units load error:', e));
}


function fetchFares() {
    const typeId  = document.getElementById('filter-type').value;
    const routeId = document.getElementById('filter-route').value;
    let url = '/admin/cargo/api/fares?';
    if (typeId)  url += `cargo_type_id=${typeId}&`;
    if (routeId) url += `route_id=${routeId}&`;

    document.getElementById('fares-tbody').innerHTML = `<tr><td colspan="10" class="px-6 py-14 text-center"><div class="flex flex-col items-center gap-2 text-surface-400"><i class="fas fa-circle-notch fa-spin text-2xl text-brand-400"></i><span class="text-sm">Loading...</span></div></td></tr>`;

    fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(fares => {
            const tbody = document.getElementById('fares-tbody');
            if (fares.length === 0) {
                tbody.innerHTML = `<tr><td colspan="10" class="px-6 py-14 text-center text-surface-400 text-sm"><i class="fas fa-money-bill text-2xl mb-2 block text-surface-300"></i>No fare rules configured yet.</td></tr>`;
                return;
            }
            tbody.innerHTML = fares.map(f => {
                const statusCls = f.status === 'active'
                    ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
                    : 'bg-surface-100 text-surface-500 border-surface-200';
                return `
                <tr class="border-b border-surface-50 hover:bg-surface-50/80 transition-colors">
                    <td class="px-5 py-3.5 font-semibold text-surface-900">${f.cargo_type?.icon || '📦'} ${f.cargo_type?.name || 'N/A'}</td>
                    <td class="px-4 py-3.5 text-sm text-surface-600">${f.route ? f.route.name : '<em class="text-surface-400 not-italic">All Routes</em>'}</td>
                    <td class="px-4 py-3.5 text-sm text-surface-600">${f.transport_unit ? f.transport_unit.name : '<em class="text-surface-400 not-italic">All Units</em>'}</td>
                    <td class="px-4 py-3.5 text-right tabular-nums text-surface-700 font-medium">${fmt(f.price_tzs)}</td>
                    <td class="px-4 py-3.5 text-right tabular-nums text-surface-700">${fmt(f.price_usd)}</td>
                    <td class="px-4 py-3.5 text-right tabular-nums text-surface-600">${fmt(f.price_per_kg_tzs)}</td>
                    <td class="px-4 py-3.5 text-right tabular-nums text-surface-600">${fmt(f.price_per_cbm_tzs)}</td>
                    <td class="px-4 py-3.5 text-right tabular-nums text-surface-600">${fmt(f.min_charge_tzs)}</td>
                    <td class="px-4 py-3.5 text-center"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border ${statusCls}">${f.status}</span></td>
                    <td class="px-4 py-3.5">
                        <div class="flex items-center justify-center gap-1.5">
                            <button onclick='editFare(${JSON.stringify(f)})'
                                class="h-7 w-7 rounded-lg flex items-center justify-center text-brand-600 bg-brand-50 hover:bg-brand-100 border border-brand-100 transition-colors">
                                <i class="fas fa-pen text-xs"></i>
                            </button>
                            <button onclick="deleteFare(${f.id})"
                                class="h-7 w-7 rounded-lg flex items-center justify-center text-red-500 bg-red-50 hover:bg-red-100 border border-red-100 transition-colors">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
            }).join('');
        })
        .catch(() => {
            document.getElementById('fares-tbody').innerHTML = `<tr><td colspan="10" class="px-6 py-8 text-center text-red-400 text-sm">Failed to load fares.</td></tr>`;
        });
}

function openCreateFareModal() {
    document.getElementById('fareModalTitle').textContent = 'New Cargo Fare';
    document.getElementById('fare-id').value = '';
    document.getElementById('fareForm').reset();
    showFareModal();
}

function editFare(f) {
    document.getElementById('fareModalTitle').textContent = 'Edit Fare';
    document.getElementById('fare-id').value = f.id;
    document.getElementById('fare-cargo-type').value = f.cargo_type_id;
    document.getElementById('fare-route').value = f.route_id || '';
    document.getElementById('fare-transport-unit').value = f.transport_unit_id || '';
    document.getElementById('fare-price-tzs').value = f.price_tzs;
    document.getElementById('fare-price-usd').value = f.price_usd;
    document.getElementById('fare-per-kg-tzs').value = f.price_per_kg_tzs || 0;
    document.getElementById('fare-per-kg-usd').value = f.price_per_kg_usd || 0;
    document.getElementById('fare-per-cbm-tzs').value = f.price_per_cbm_tzs || 0;
    document.getElementById('fare-per-cbm-usd').value = f.price_per_cbm_usd || 0;
    document.getElementById('fare-min-tzs').value = f.min_charge_tzs || 0;
    document.getElementById('fare-min-usd').value = f.min_charge_usd || 0;
    document.getElementById('fare-status').value = f.status;
    showFareModal();
}

function saveFare(e) {
    e.preventDefault();
    const id  = document.getElementById('fare-id').value;
    const btn = document.getElementById('save-fare-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin text-xs"></i> Saving...';

    const payload = {
        cargo_type_id:    document.getElementById('fare-cargo-type').value,
        route_id:         document.getElementById('fare-route').value || null,
        transport_unit_id: document.getElementById('fare-transport-unit').value || null,
        price_tzs:        parseFloat(document.getElementById('fare-price-tzs').value),
        price_usd:        parseFloat(document.getElementById('fare-price-usd').value),
        price_per_kg_tzs: parseFloat(document.getElementById('fare-per-kg-tzs').value) || null,
        price_per_kg_usd: parseFloat(document.getElementById('fare-per-kg-usd').value) || null,
        price_per_cbm_tzs: parseFloat(document.getElementById('fare-per-cbm-tzs').value) || null,
        price_per_cbm_usd: parseFloat(document.getElementById('fare-per-cbm-usd').value) || null,
        min_charge_tzs:   parseFloat(document.getElementById('fare-min-tzs').value) || null,
        min_charge_usd:   parseFloat(document.getElementById('fare-min-usd').value) || null,
        status:           document.getElementById('fare-status').value,
    };

    fetch(id ? `/admin/cargo/api/fares/${id}` : '/admin/cargo/api/fares', {
        method: id ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify(payload)
    })
    .then(r => { if (!r.ok) return r.json().then(d => { throw d; }); return r.json(); })
    .then(() => { closeFareModal(); fetchFares(); })
    .catch(err => { alert(err.message || (err.errors ? Object.values(err.errors).flat().join('\n') : 'Save failed')); })
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save text-xs"></i> Save Fare'; });
}

function deleteFare(id) {
    document.getElementById('delete-fare-id').value = id;
    const m = document.getElementById('deleteFareModal');
    m.classList.remove('hidden'); m.classList.add('flex');
}

function confirmDeleteFare() {
    const id = document.getElementById('delete-fare-id').value;
    fetch(`/admin/cargo/api/fares/${id}`, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() }
    })
    .then(r => { if (!r.ok) throw new Error('Delete failed'); return r.json(); })
    .then(() => { closeDeleteModal(); fetchFares(); })
    .catch(err => alert(err.message));
}

function showFareModal() {
    const m = document.getElementById('fareModal');
    m.classList.remove('hidden'); m.classList.add('flex');
}
function closeFareModal() {
    const m = document.getElementById('fareModal');
    m.classList.add('hidden'); m.classList.remove('flex');
}
function closeDeleteModal() {
    const m = document.getElementById('deleteFareModal');
    m.classList.add('hidden'); m.classList.remove('flex');
}

document.addEventListener('DOMContentLoaded', () => { loadDropdowns(); fetchFares(); });
</script>
@endpush
</x-layouts.admin>
