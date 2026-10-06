<x-layouts.admin>
<x-slot:title>Cargo Types</x-slot:title>
<x-slot:header>Cargo Types</x-slot:header>

<div class="px-4 sm:px-6 lg:px-8 py-6 space-y-5">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-surface-900 tracking-tight">Cargo Types</h1>
            <p class="text-sm text-surface-500 mt-0.5">Define the categories of cargo the platform supports.</p>
        </div>
        <button onclick="openCreateModal()"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm shadow-brand-200">
            <i class="fas fa-plus text-xs"></i> New Cargo Type
        </button>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-surface-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-50 border-b border-surface-100">
                        <th class="text-center px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider w-12">Icon</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Name</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Code</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Category</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Unit</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Max Weight</th>
                        <th class="text-center px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Flags</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Status</th>
                        <th class="text-center px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="types-tbody">
                    <tr>
                        <td colspan="9" class="px-6 py-14 text-center">
                            <div class="flex flex-col items-center gap-2 text-surface-400">
                                <i class="fas fa-circle-notch fa-spin text-2xl text-brand-400"></i>
                                <span class="text-sm">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Create / Edit Modal --}}
<div id="typeModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-surface-950/50 backdrop-blur-sm" onclick="closeTypeModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-surface-100 bg-surface-50 sticky top-0">
            <h2 id="typeModalTitle" class="text-base font-bold text-surface-900">New Cargo Type</h2>
            <button onclick="closeTypeModal()" class="h-8 w-8 rounded-lg flex items-center justify-center hover:bg-surface-200 transition-colors text-surface-500">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="typeForm" onsubmit="saveType(event)" class="p-6 space-y-5">
            <input type="hidden" id="type-id">

            {{-- Name / Code / Icon --}}
            <div class="grid grid-cols-1 sm:grid-cols-6 gap-4">
                <div class="sm:col-span-3 space-y-1.5">
                    <label class="block text-xs font-semibold text-surface-700">Name <span class="text-red-500">*</span></label>
                    <input type="text" id="type-name" required
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all">
                </div>
                <div class="sm:col-span-2 space-y-1.5">
                    <label class="block text-xs font-semibold text-surface-700">Code <span class="text-red-500">*</span></label>
                    <input type="text" id="type-code" required placeholder="e.g. VEH_SEDAN"
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all font-mono uppercase">
                </div>
                <div class="sm:col-span-1 space-y-1.5">
                    <label class="block text-xs font-semibold text-surface-700">Icon</label>
                    <input type="text" id="type-icon" placeholder="📦"
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all text-center text-lg">
                </div>
            </div>

            {{-- Category / Measurement / Status --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-surface-700">Category <span class="text-red-500">*</span></label>
                    <select id="type-category" required
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-white">
                        <option value="vehicle">Vehicle</option>
                        <option value="container">Container</option>
                        <option value="loose_cargo">Loose Cargo</option>
                        <option value="livestock">Livestock</option>
                        <option value="hazmat">Hazmat</option>
                        <option value="passenger_luggage">Passenger Luggage</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-surface-700">Measurement Unit <span class="text-red-500">*</span></label>
                    <select id="type-measurement" required
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-white">
                        <option value="kg">Per Kilogram (kg)</option>
                        <option value="cbm">Per Cubic Meter (cbm)</option>
                        <option value="unit">Per Unit (flat)</option>
                        <option value="lane_meter">Per Lane Meter</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-surface-700">Status <span class="text-red-500">*</span></label>
                    <select id="type-status" required
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-white">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            {{-- Lane Meters / Max Weight --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-surface-700">Lane Meters <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" id="type-lane-meters" required value="0"
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-surface-700">Max Weight (kg) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" id="type-max-weight" required value="0"
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all">
                </div>
            </div>

            {{-- Flags --}}
            <div class="bg-surface-50 rounded-xl border border-surface-100 p-4 flex flex-col sm:flex-row gap-5">
                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <div class="relative">
                        <input type="checkbox" id="type-hazmat" class="sr-only peer">
                        <div class="w-9 h-5 rounded-full bg-surface-300 peer-checked:bg-amber-500 transition-colors"></div>
                        <div class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></div>
                    </div>
                    <span class="text-sm font-medium text-surface-700">⚠️ Requires Hazmat Declaration</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <div class="relative">
                        <input type="checkbox" id="type-fragile" class="sr-only peer">
                        <div class="w-9 h-5 rounded-full bg-surface-300 peer-checked:bg-orange-400 transition-colors"></div>
                        <div class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></div>
                    </div>
                    <span class="text-sm font-medium text-surface-700">🔶 Fragile</span>
                </label>
            </div>

            {{-- Footer --}}
            <div class="flex justify-end gap-3 pt-2 border-t border-surface-100">
                <button type="button" onclick="closeTypeModal()"
                    class="px-5 py-2.5 text-sm font-semibold text-surface-600 rounded-xl border border-surface-200 hover:bg-surface-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" id="save-type-btn"
                    class="px-6 py-2.5 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition-colors shadow-sm shadow-brand-200 flex items-center gap-2">
                    <i class="fas fa-save text-xs"></i> Save Cargo Type
                </button>
            </div>
        </form>
    </div>
</div>


@push('scripts')
<script>
const CATEGORY_LABELS = {
    vehicle: 'Vehicle', container: 'Container', loose_cargo: 'Loose Cargo',
    livestock: 'Livestock', hazmat: 'Hazmat', passenger_luggage: 'Luggage'
};
const UNIT_LABELS = { kg: 'Per kg', cbm: 'Per cbm', unit: 'Per unit', lane_meter: 'Per lane m' };

function fetchTypes() {
    document.getElementById('types-tbody').innerHTML = `<tr><td colspan="9" class="px-6 py-14 text-center"><div class="flex flex-col items-center gap-2 text-surface-400"><i class="fas fa-circle-notch fa-spin text-2xl text-brand-400"></i><span class="text-sm">Loading...</span></div></td></tr>`;

    fetch('/admin/cargo/api/types', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(types => {
            const tbody = document.getElementById('types-tbody');
            if (types.length === 0) {
                tbody.innerHTML = `<tr><td colspan="9" class="px-6 py-14 text-center text-surface-400 text-sm"><i class="fas fa-tags text-2xl mb-2 block text-surface-300"></i>No cargo types defined yet.</td></tr>`;
                return;
            }
            tbody.innerHTML = types.map(t => {
                const statusCls = t.status === 'active'
                    ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
                    : 'bg-surface-100 text-surface-500 border-surface-200';
                const catCls = {
                    vehicle:'bg-blue-50 text-blue-700', container:'bg-violet-50 text-violet-700',
                    loose_cargo:'bg-amber-50 text-amber-700', livestock:'bg-lime-50 text-lime-700',
                    hazmat:'bg-red-50 text-red-700', passenger_luggage:'bg-sky-50 text-sky-700'
                }[t.category] || 'bg-surface-100 text-surface-600';

                return `
                <tr class="border-b border-surface-50 hover:bg-surface-50/80 transition-colors">
                    <td class="px-4 py-3.5 text-center text-2xl">${t.icon || '📦'}</td>
                    <td class="px-4 py-3.5 font-semibold text-surface-900">${t.name}</td>
                    <td class="px-4 py-3.5"><code class="text-xs bg-surface-100 text-surface-700 px-2 py-0.5 rounded font-mono">${t.code}</code></td>
                    <td class="px-4 py-3.5"><span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium ${catCls}">${CATEGORY_LABELS[t.category] || t.category}</span></td>
                    <td class="px-4 py-3.5 text-xs text-surface-500">${UNIT_LABELS[t.measurement_unit] || t.measurement_unit}</td>
                    <td class="px-4 py-3.5 text-sm tabular-nums text-surface-700">${t.max_weight_kg} kg</td>
                    <td class="px-4 py-3.5 text-center">
                        <div class="flex justify-center gap-1.5">
                            ${t.requires_hazmat_declaration ? '<span title="Hazmat" class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-amber-100 text-amber-600 text-xs">⚠️</span>' : ''}
                            ${t.is_fragile ? '<span title="Fragile" class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-orange-100 text-orange-500 text-xs">🔶</span>' : ''}
                            ${!t.requires_hazmat_declaration && !t.is_fragile ? '<span class="text-xs text-surface-300">—</span>' : ''}
                        </div>
                    </td>
                    <td class="px-4 py-3.5"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border ${statusCls}">${t.status}</span></td>
                    <td class="px-4 py-3.5 text-center">
                        <button onclick='editType(${JSON.stringify(t)})'
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-brand-600 bg-brand-50 hover:bg-brand-100 rounded-lg transition-colors border border-brand-100">
                            <i class="fas fa-pen text-xs"></i> Edit
                        </button>
                    </td>
                </tr>`;
            }).join('');
        })
        .catch(() => {
            document.getElementById('types-tbody').innerHTML = `<tr><td colspan="9" class="px-6 py-8 text-center text-red-400 text-sm">Failed to load data.</td></tr>`;
        });
}

function openCreateModal() {
    document.getElementById('typeModalTitle').textContent = 'New Cargo Type';
    document.getElementById('type-id').value = '';
    document.getElementById('type-code').disabled = false;
    document.getElementById('typeForm').reset();
    showModal();
}

function editType(t) {
    document.getElementById('typeModalTitle').textContent = 'Edit: ' + t.name;
    document.getElementById('type-id').value = t.id;
    document.getElementById('type-name').value = t.name;
    document.getElementById('type-code').value = t.code;
    document.getElementById('type-code').disabled = true;
    document.getElementById('type-icon').value = t.icon || '';
    document.getElementById('type-category').value = t.category;
    document.getElementById('type-measurement').value = t.measurement_unit;
    document.getElementById('type-status').value = t.status;
    document.getElementById('type-lane-meters').value = t.lane_meters;
    document.getElementById('type-max-weight').value = t.max_weight_kg;
    document.getElementById('type-hazmat').checked = t.requires_hazmat_declaration;
    document.getElementById('type-fragile').checked = t.is_fragile;
    showModal();
}

function saveType(e) {
    e.preventDefault();
    const id = document.getElementById('type-id').value;
    const btn = document.getElementById('save-type-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin text-xs"></i> Saving...';

    const payload = {
        name: document.getElementById('type-name').value,
        code: document.getElementById('type-code').value,
        category: document.getElementById('type-category').value,
        measurement_unit: document.getElementById('type-measurement').value,
        status: document.getElementById('type-status').value,
        lane_meters: parseFloat(document.getElementById('type-lane-meters').value),
        max_weight_kg: parseFloat(document.getElementById('type-max-weight').value),
        requires_hazmat_declaration: document.getElementById('type-hazmat').checked,
        is_fragile: document.getElementById('type-fragile').checked,
        icon: document.getElementById('type-icon').value || null,
    };

    fetch(id ? `/admin/cargo/api/types/${id}` : '/admin/cargo/api/types', {
        method: id ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
        body: JSON.stringify(payload)
    })
    .then(r => { if (!r.ok) return r.json().then(d => { throw d; }); return r.json(); })
    .then(() => { closeTypeModal(); fetchTypes(); })
    .catch(err => { alert(err.message || (err.errors ? Object.values(err.errors).flat().join('\n') : 'Save failed')); })
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save text-xs"></i> Save Cargo Type'; });
}

function showModal() {
    const m = document.getElementById('typeModal');
    m.classList.remove('hidden');
    m.classList.add('flex');
}
function closeTypeModal() {
    const m = document.getElementById('typeModal');
    m.classList.add('hidden');
    m.classList.remove('flex');
}

document.addEventListener('DOMContentLoaded', fetchTypes);
</script>
@endpush
</x-layouts.admin>
