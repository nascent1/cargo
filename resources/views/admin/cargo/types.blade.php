<x-layouts.admin>
<x-slot:title>Cargo Types</x-slot:title>
<x-slot:header>Cargo Types</x-slot:header>

@include('admin.cargo.partials.ui')

<div x-data="typesPage()" x-on:types-changed.window="load()" class="px-4 sm:px-6 lg:px-8 py-6 space-y-5">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-surface-900 tracking-tight">Cargo Types</h1>
            <p class="text-sm text-surface-500 mt-0.5">The kinds of cargo the platform carries. Open a type to see its prices on every route and its fare history.</p>
        </div>
        @if (auth()->user()->hasPermission('cargo-manage-types'))
            <button type="button" @click="$dispatch('type-editor', {})"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm shadow-brand-200">
                <i class="fas fa-plus text-xs"></i> New Cargo Type
            </button>
        @endif
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-surface-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-50 border-b border-surface-100 text-[11px] font-bold text-surface-500 uppercase tracking-wider">
                        <th class="text-center px-4 py-3 w-12">Icon</th>
                        <th class="text-left px-4 py-3">Name</th>
                        <th class="text-left px-4 py-3">Code</th>
                        <th class="text-left px-4 py-3">Category</th>
                        <th class="text-left px-4 py-3">Priced per</th>
                        <th class="text-left px-4 py-3">Max weight</th>
                        <th class="text-center px-4 py-3">Flags</th>
                        <th class="text-left px-4 py-3">Fares in force</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-center px-4 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr x-show="loading"><td colspan="10" class="px-6 py-14 text-center"><i class="fas fa-circle-notch fa-spin text-2xl text-brand-400"></i></td></tr>
                    <tr x-show="!loading && !types.length"><td colspan="10" class="px-6 py-14 text-center text-surface-400"><i class="fas fa-tags text-2xl mb-2 block text-surface-300"></i>No cargo types defined yet.</td></tr>
                    <template x-for="t in (loading ? [] : types)" :key="t.id">
                        <tr class="border-b border-surface-50 hover:bg-surface-50/80 transition-colors cursor-pointer" @click="window.location = `/admin/cargo/types/${t.id}`">
                            <td class="px-4 py-3.5 text-center text-2xl" x-text="t.icon || '📦'"></td>
                            <td class="px-4 py-3.5"><a :href="`/admin/cargo/types/${t.id}`" @click.stop class="font-semibold text-surface-900 hover:text-brand-700" x-text="t.name"></a></td>
                            <td class="px-4 py-3.5"><code class="text-xs bg-surface-100 text-surface-700 px-2 py-0.5 rounded font-mono" x-text="t.code"></code></td>
                            <td class="px-4 py-3.5"><span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium" :class="catClass(t.category)" x-text="CATEGORY[t.category] || t.category"></span></td>
                            <td class="px-4 py-3.5 text-xs text-surface-600" x-text="UNIT[t.measurement_unit] || t.measurement_unit"></td>
                            <td class="px-4 py-3.5 text-sm tabular-nums text-surface-700" x-text="`${CargoUI.num(t.max_weight_kg)} kg`"></td>
                            <td class="px-4 py-3.5 text-center">
                                <span x-show="t.requires_hazmat_declaration" title="Hazmat" class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-amber-100 text-xs">⚠️</span>
                                <span x-show="t.is_fragile" title="Fragile" class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-orange-100 text-xs">🔶</span>
                                <span x-show="!t.requires_hazmat_declaration && !t.is_fragile" class="text-xs text-surface-300">—</span>
                            </td>
                            <td class="px-4 py-3.5 text-xs">
                                <span x-show="t.current_fares_count > 0" class="text-surface-700" x-text="`${t.current_fares_count} fare${t.current_fares_count === 1 ? '' : 's'}`"></span>
                                <span x-show="!t.current_fares_count && t.status === 'active'" class="font-semibold text-red-700"><i class="fas fa-ban"></i> Not bookable — no fare</span>
                                <span x-show="!t.current_fares_count && t.status !== 'active'" class="text-surface-400">—</span>
                            </td>
                            <td class="px-4 py-3.5"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border" :class="t.status === 'active' ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-surface-100 text-surface-500 border-surface-200'" x-text="t.status"></span></td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap" @click.stop>
                                <a :href="`/admin/cargo/types/${t.id}`" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-surface-700 bg-white hover:bg-surface-50 rounded-lg border border-surface-200">
                                    <i class="fas fa-eye text-xs"></i> View
                                </a>
                                @if (auth()->user()->hasPermission('cargo-manage-types'))
                                    <button type="button" @click="$dispatch('type-editor', { type: t })" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-brand-600 bg-brand-50 hover:bg-brand-100 rounded-lg border border-brand-100">
                                        <i class="fas fa-pen text-xs"></i> Edit
                                    </button>
                                @endif
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('admin.cargo.partials.type-form')

@push('scripts')
<script>
function typesPage() {
    return {
        types: [], loading: true,
        CATEGORY: { vehicle: 'Vehicle', container: 'Container', loose_cargo: 'Loose Cargo', livestock: 'Livestock', hazmat: 'Hazmat', passenger_luggage: 'Luggage' },
        UNIT: { kg: 'Per kg', cbm: 'Per cbm', unit: 'Per piece', lane_meter: 'Per vehicle' },
        catClass: c => ({ vehicle: 'bg-blue-50 text-blue-700', container: 'bg-violet-50 text-violet-700', loose_cargo: 'bg-amber-50 text-amber-700',
            livestock: 'bg-lime-50 text-lime-700', hazmat: 'bg-red-50 text-red-700', passenger_luggage: 'bg-sky-50 text-sky-700' }[c] || 'bg-surface-100 text-surface-600'),
        init() { this.load(); },
        async load() {
            this.loading = true;
            try { this.types = await CargoUI.api('/admin/cargo/api/types'); }
            catch (e) { window.notify?.(e.message, 'error'); }
            finally { this.loading = false; }
        },
    };
}
</script>
@endpush
</x-layouts.admin>
