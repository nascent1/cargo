<x-layouts.admin>
    <x-slot:title>Add Transport Unit</x-slot:title>
    <x-slot:header>Transport Units</x-slot:header>

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Add Transport Unit</h2>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm max-w-2xl overflow-hidden">
        <form action="{{ route('admin.cargo.transport-units.store') }}" method="POST" class="p-8 space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Name *</label>
                    <input type="text" name="name" class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 shadow-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Registration # *</label>
                    <input type="text" name="registration_number" class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 shadow-sm" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Model/Type *</label>
                    <input type="text" name="type" class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 shadow-sm" placeholder="e.g. Scania R500" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Transport Class *</label>
                    <select name="transport_type" class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 shadow-sm" required>
                        <option value="ferry">Ferry</option>
                        <option value="bus">Bus</option>
                        <option value="truck">Truck</option>
                    </select>
                </div>
            </div>

            <div class="border-t border-b border-slate-100 py-6 my-6 bg-slate-50/50 -mx-8 px-8">
                <label class="flex items-center gap-3 mb-4 cursor-pointer">
                    <input type="checkbox" name="has_cargo_deck" value="1" class="rounded w-5 h-5 text-blue-600 border-slate-300 focus:ring-blue-500" onchange="document.getElementById('capacity_fields').classList.toggle('hidden', !this.checked)">
                    <span class="font-bold text-slate-800">Has Cargo Deck / Cargo Capacity</span>
                </label>
                
                <div id="capacity_fields" class="grid grid-cols-1 md:grid-cols-3 gap-6 hidden mt-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Lane Meters</label>
                        <input type="number" step="0.01" name="capacity_lane_meters" class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Weight (Kg)</label>
                        <input type="number" step="0.1" name="capacity_weight_kg" class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Volume (CBM)</label>
                        <input type="number" step="0.1" name="capacity_volume_cbm" class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 shadow-sm">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Status</label>
                <select name="status" class="w-full rounded-xl border-slate-200 focus:border-brand-500 focus:ring-brand-500 shadow-sm">
                    <option value="active">Active</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="pt-6 flex justify-end gap-3">
                <a href="{{ route('admin.cargo.transport-units.index') }}" class="px-5 py-2.5 text-slate-600 font-medium hover:bg-slate-100 rounded-xl transition-colors">Cancel</a>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl shadow-sm transition-colors">Submit for Approval</button>
            </div>
        </form>
    </div>
</x-layouts.admin>