{{--
    Shared cargo type form (types list and type profile). Changes go through dual control
    (cargo_type_create / cargo_type_update), so the result may be applied, scheduled or awaiting approval.

    window.dispatchEvent(new CustomEvent('type-editor', { detail: { type } }))   // omit type to create
    Fires `types-changed` on the window after a save. Requires admin.cargo.partials.ui.
--}}
@once
@php
    $dc = app(\App\Services\DualControlService::class);
    $typeRules = ['create' => $dc->requiresDualControl('cargo_type_create'), 'update' => $dc->requiresDualControl('cargo_type_update')];
@endphp
<div x-data="typeEditor(@js($typeRules))" x-on:type-editor.window="open($event.detail)" x-on:keydown.escape.window="isOpen && close()"
    x-show="isOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" :aria-label="title">
    <div class="absolute inset-0 bg-surface-950/50 backdrop-blur-sm" @click="close()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-surface-100 bg-surface-50 sticky top-0">
            <h2 class="text-base font-bold text-surface-900" x-text="title"></h2>
            <button type="button" @click="close()" class="h-8 w-8 rounded-lg flex items-center justify-center hover:bg-surface-200 text-surface-500" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>

        <form @submit.prevent="save()" class="p-6 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-6 gap-4">
                <div class="sm:col-span-3 space-y-1.5">
                    <label for="tf-name" class="block text-xs font-semibold text-surface-700">Name <span class="text-red-500">*</span></label>
                    <input id="tf-name" type="text" x-model="t.name" required maxlength="100" class="tf-field">
                    <p class="tf-error" x-show="errors.name" x-text="errors.name"></p>
                </div>
                <div class="sm:col-span-2 space-y-1.5">
                    <label for="tf-code" class="block text-xs font-semibold text-surface-700">Code <span class="text-red-500">*</span></label>
                    <input id="tf-code" type="text" x-model="t.code" :disabled="!!id" required maxlength="20" placeholder="e.g. VEH-SEDAN" class="tf-field font-mono uppercase">
                    <p class="tf-error" x-show="errors.code" x-text="errors.code"></p>
                </div>
                <div class="sm:col-span-1 space-y-1.5">
                    <label for="tf-icon" class="block text-xs font-semibold text-surface-700">Icon</label>
                    <input id="tf-icon" type="text" x-model="t.icon" maxlength="50" placeholder="📦" class="tf-field text-center text-lg">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="space-y-1.5">
                    <label for="tf-cat" class="block text-xs font-semibold text-surface-700">Category <span class="text-red-500">*</span></label>
                    <select id="tf-cat" x-model="t.category" required class="tf-field">
                        <template x-for="(label, key) in categories" :key="key"><option :value="key" x-text="label"></option></template>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label for="tf-unit" class="block text-xs font-semibold text-surface-700">Priced per <span class="text-red-500">*</span></label>
                    <select id="tf-unit" x-model="t.measurement_unit" required class="tf-field">
                        <option value="kg">Kilogram (kg)</option>
                        <option value="cbm">Cubic metre (cbm)</option>
                        <option value="unit">Piece (flat)</option>
                        <option value="lane_meter">Vehicle (lane metres)</option>
                    </select>
                    <p class="text-xs text-amber-700" x-show="id && original.measurement_unit !== t.measurement_unit">Existing fares were set for the old unit — review them after this change.</p>
                </div>
                <div class="space-y-1.5">
                    <label for="tf-status" class="block text-xs font-semibold text-surface-700">Status <span class="text-red-500">*</span></label>
                    <select id="tf-status" x-model="t.status" required class="tf-field"><option value="active">Active</option><option value="inactive">Inactive</option></select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label for="tf-lane" class="block text-xs font-semibold text-surface-700">Lane metres used <span class="text-red-500">*</span></label>
                    <input id="tf-lane" type="number" step="0.01" min="0" x-model="t.lane_meters" required class="tf-field">
                </div>
                <div class="space-y-1.5">
                    <label for="tf-weight" class="block text-xs font-semibold text-surface-700">Max weight per piece (kg) <span class="text-red-500">*</span></label>
                    <input id="tf-weight" type="number" step="0.01" min="0" x-model="t.max_weight_kg" required class="tf-field">
                </div>
            </div>

            <div class="bg-surface-50 rounded-xl border border-surface-100 p-4 flex flex-col sm:flex-row gap-5 text-sm text-surface-700">
                <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" x-model="t.requires_hazmat_declaration" class="rounded border-surface-300 text-amber-500"> ⚠️ Requires hazmat declaration</label>
                <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" x-model="t.is_fragile" class="rounded border-surface-300 text-orange-500"> 🔶 Fragile</label>
            </div>

            {{-- When --}}
            <div class="rounded-xl border px-4 py-3 space-y-2" :class="needsApproval() ? 'border-amber-200 bg-amber-50/60' : 'border-surface-200 bg-surface-50'">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2" role="radiogroup" aria-label="Takes effect">
                    <span class="text-xs font-semibold text-surface-700">Takes effect</span>
                    <label class="inline-flex items-center gap-1.5 text-sm cursor-pointer"><input type="radio" value="now" x-model="takesEffect" class="text-brand-600"> <span x-text="needsApproval() ? 'When approved' : 'Immediately'"></span></label>
                    <label class="inline-flex items-center gap-1.5 text-sm cursor-pointer"><input type="radio" value="at" x-model="takesEffect" class="text-brand-600"> On</label>
                    <input type="datetime-local" x-model="takesEffectAt" x-show="takesEffect === 'at'" :required="takesEffect === 'at'" aria-label="Date and time it takes effect" class="tf-field w-auto py-1.5">
                </div>
                <p class="text-xs" :class="needsApproval() ? 'text-amber-800' : 'text-surface-600'" x-text="needsApproval() ? 'Needs a checker\'s approval.' : 'Applies without approval.'"></p>
                <p class="tf-error" x-show="errors.takes_effect_at" x-text="errors.takes_effect_at"></p>
            </div>

            <div x-show="message" class="rounded-xl bg-red-50 border border-red-200 px-4 py-2.5 text-sm text-red-700" x-text="message"></div>

            <div class="flex justify-end gap-3 pt-2 border-t border-surface-100">
                <button type="button" @click="close()" class="px-5 py-2.5 text-sm font-semibold text-surface-600 rounded-xl border border-surface-200 hover:bg-surface-50">Cancel</button>
                <button type="submit" :disabled="busy" class="px-6 py-2.5 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-xl flex items-center gap-2 disabled:opacity-50">
                    <i class="fas text-xs" :class="busy ? 'fa-circle-notch fa-spin' : 'fa-save'"></i>
                    <span x-text="needsApproval() ? 'Submit for approval' : 'Save cargo type'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('styles')
<style>
    .tf-field { width: 100%; padding: .625rem .75rem; font-size: .875rem; border-radius: .75rem; border: 1px solid rgb(226 232 240); background: #fff; outline: none; }
    .tf-field:focus { border-color: rgb(96 165 250); box-shadow: 0 0 0 2px rgb(219 234 254); }
    .tf-field:disabled { background: rgb(248 250 252); color: rgb(71 85 105); }
    .tf-error { font-size: .75rem; color: rgb(185 28 28); }
</style>
@endpush

@push('scripts')
<script>
function typeEditor(rules) {
    const blank = () => ({ name: '', code: '', icon: '', category: 'vehicle', measurement_unit: 'unit', status: 'active', lane_meters: 0, max_weight_kg: 0, requires_hazmat_declaration: false, is_fragile: false });
    return {
        rules, isOpen: false, busy: false, id: null, t: blank(), original: {}, errors: {}, message: '', takesEffect: 'now', takesEffectAt: '',
        categories: { vehicle: 'Vehicle', container: 'Container', loose_cargo: 'Loose cargo', livestock: 'Livestock', hazmat: 'Hazmat', passenger_luggage: 'Passenger luggage' },
        get title() { return this.id ? `Edit: ${this.original.name}` : 'New cargo type'; },
        needsApproval() { return this.id ? this.rules.update : this.rules.create; },
        open(d = {}) {
            const type = d.type || null;
            this.id = type?.id || null;
            this.t = type ? Object.assign(blank(), { ...type, lane_meters: Number(type.lane_meters || 0), max_weight_kg: Number(type.max_weight_kg || 0) }) : blank();
            this.original = { ...this.t };
            this.errors = {}; this.message = ''; this.takesEffect = 'now'; this.takesEffectAt = '';
            this.isOpen = true;
        },
        close() { this.isOpen = false; },
        async save() {
            this.busy = true; this.errors = {}; this.message = '';
            const body = {
                name: this.t.name, category: this.t.category, measurement_unit: this.t.measurement_unit, status: this.t.status,
                lane_meters: Number(this.t.lane_meters), max_weight_kg: Number(this.t.max_weight_kg),
                requires_hazmat_declaration: !!this.t.requires_hazmat_declaration, is_fragile: !!this.t.is_fragile, icon: this.t.icon || null,
                takes_effect: this.takesEffect, takes_effect_at: this.takesEffect === 'at' ? this.takesEffectAt : null,
            };
            if (!this.id) body.code = this.t.code;
            try {
                const res = await CargoUI.api(this.id ? `/admin/cargo/api/types/${this.id}` : '/admin/cargo/api/types', { method: this.id ? 'PUT' : 'POST', body });
                CargoUI.notifyChange(res);
                window.dispatchEvent(new CustomEvent('types-changed', { detail: res }));
                this.close();
            } catch (e) {
                this.errors = Object.fromEntries(Object.entries(e.errors || {}).map(([k, v]) => [k, v[0]]));
                if (!Object.keys(this.errors).length) this.message = e.message;
            } finally { this.busy = false; }
        },
    };
}
</script>
@endpush
@endonce
