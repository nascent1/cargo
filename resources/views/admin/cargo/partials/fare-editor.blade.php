{{--
    Shared fare editor: add / change / end / delete a fare, bulk value charge, copy a route's fares.
    Every change is reviewed first (the server's plan and its alerts), then saved — or submitted for
    approval when its dual-control rule is on.

    Open it from anywhere on the page:
        window.dispatchEvent(new CustomEvent('fare-editor', { detail: { mode: 'create', cargo_type_id: 3, lock_type: true } }))
        modes: create | revise | end | delete | bulk | copy   (revise / end / delete need detail.fare)
    It fires `fares-changed` on the window after a save.

    Requires admin.cargo.partials.ui (window.CargoUI).
--}}
@once
<div x-data="fareEditor()" x-on:fare-editor.window="open($event.detail)" x-on:keydown.escape.window="isOpen && close()"
    x-show="isOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" :aria-label="title()">
    <div class="absolute inset-0 bg-surface-950/50 backdrop-blur-sm" @click="close()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[92vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-surface-100 bg-surface-50 sticky top-0 z-10">
            <div>
                <h2 class="text-base font-bold text-surface-900" x-text="title()"></h2>
                <p class="text-xs text-surface-600" x-show="subtitle()" x-text="subtitle()"></p>
            </div>
            <button type="button" @click="close()" class="h-8 w-8 rounded-lg flex items-center justify-center hover:bg-surface-200 text-surface-500" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>

        <form @submit.prevent="step === 'form' ? review() : save()" class="p-6 space-y-5">

            {{-- ── FORM ─────────────────────────────────────────── --}}
            <div x-show="step === 'form'" class="space-y-5">

                {{-- Add / change a fare --}}
                <template x-if="mode === 'create' || mode === 'revise'">
                    <div class="space-y-5">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="space-y-1.5">
                                <label for="fe-type" class="block text-xs font-semibold text-surface-700">Cargo type <span class="text-red-500">*</span></label>
                                <select id="fe-type" x-model="f.cargo_type_id" :disabled="mode === 'revise' || lock.type" required class="fe-field">
                                    <option value="">Select…</option>
                                    <template x-for="t in types" :key="t.id"><option :value="String(t.id)" x-text="`${t.name} — per ${unitLabel(t.measurement_unit)}`"></option></template>
                                </select>
                                <p class="fe-error" x-show="errors.cargo_type_id" x-text="errors.cargo_type_id"></p>
                            </div>
                            <div class="space-y-1.5">
                                <label for="fe-route" class="block text-xs font-semibold text-surface-700">Route <span class="text-red-500">*</span></label>
                                <select id="fe-route" x-model="f.route_id" :disabled="mode === 'revise' || lock.route" required class="fe-field">
                                    <option value="">Select…</option>
                                    <template x-for="r in routes" :key="r.id"><option :value="String(r.id)" x-text="r.name"></option></template>
                                </select>
                                <p class="fe-error" x-show="errors.route_id" x-text="errors.route_id"></p>
                            </div>
                            <div class="space-y-1.5">
                                <label for="fe-unit" class="block text-xs font-semibold text-surface-700">Vessel <span class="font-normal text-surface-500">(blank = all)</span></label>
                                <select id="fe-unit" x-model="f.transport_unit_id" :disabled="mode === 'revise'" class="fe-field">
                                    <option value="">All vessels on the route</option>
                                    <template x-for="u in units" :key="u.id"><option :value="String(u.id)" x-text="u.name"></option></template>
                                </select>
                            </div>
                        </div>

                        <div x-show="type()" class="rounded-xl bg-brand-50 border border-brand-200 px-4 py-2.5 text-sm text-brand-900">
                            <i class="fas fa-circle-info mr-1"></i><strong x-text="type()?.name"></strong> is priced <span x-text="basisText()"></span>
                        </div>

                        {{-- The rate the type is priced by --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label for="fe-rate" class="block text-xs font-semibold text-surface-700"><span x-text="rateLabel()"></span> (TZS) <span class="text-red-500">*</span></label>
                                <input id="fe-rate" type="number" step="0.01" min="0" x-model="f[basisField()]" required class="fe-field text-lg font-bold">
                                <p class="fe-error" x-show="errors[basisField()]" x-text="errors[basisField()]"></p>
                                <p class="text-xs text-surface-600" x-show="mode === 'revise' && fare" x-text="`Now: ${CargoUI.num(fare?.[basisField()])}`"></p>
                            </div>
                            <div class="space-y-1.5">
                                <label for="fe-rate-usd" class="block text-xs font-semibold text-surface-700"><span x-text="rateLabel()"></span> (USD) <span class="font-normal text-surface-500">optional</span></label>
                                <input id="fe-rate-usd" type="number" step="0.01" min="0" x-model="f[basisField().replace('_tzs', '_usd')]" class="fe-field">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="space-y-1.5">
                                <label for="fe-min" class="block text-xs font-semibold text-surface-700">Minimum charge (TZS)</label>
                                <input id="fe-min" type="number" step="0.01" min="0" x-model="f.min_charge_tzs" class="fe-field">
                            </div>
                            <div class="space-y-1.5">
                                <label for="fe-min-usd" class="block text-xs font-semibold text-surface-700">Minimum charge (USD)</label>
                                <input id="fe-min-usd" type="number" step="0.01" min="0" x-model="f.min_charge_usd" class="fe-field">
                            </div>
                            <div class="space-y-1.5">
                                <label for="fe-pct" class="block text-xs font-semibold text-surface-700">Value charge (% of declared value)</label>
                                <div class="relative"><input id="fe-pct" type="number" step="0.01" min="0" max="100" x-model="f.value_charge_pct" class="fe-field pr-8"><span class="absolute inset-y-0 right-3 flex items-center text-sm text-surface-500">%</span></div>
                                <p class="fe-error" x-show="errors.value_charge_pct" x-text="errors.value_charge_pct"></p>
                            </div>
                        </div>

                        {{-- When --}}
                        <div class="rounded-xl border px-4 py-3 space-y-3" :class="needsApproval() ? 'border-amber-200 bg-amber-50/60' : 'border-surface-200 bg-surface-50'">
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-2" role="radiogroup" aria-label="Starts">
                                <span class="text-xs font-semibold text-surface-700 w-16">Starts</span>
                                <label class="inline-flex items-center gap-1.5 text-sm cursor-pointer"><input type="radio" value="now" x-model="f.takes_effect" class="text-brand-600"> <span x-text="needsApproval() ? 'When approved' : 'Immediately'"></span></label>
                                <label class="inline-flex items-center gap-1.5 text-sm cursor-pointer"><input type="radio" value="at" x-model="f.takes_effect" class="text-brand-600"> On</label>
                                <input type="datetime-local" x-model="f.takes_effect_at" x-show="f.takes_effect === 'at'" :min="minStart()" :required="f.takes_effect === 'at'" aria-label="Start date and time" class="fe-field w-auto py-1.5">
                            </div>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                                <span class="text-xs font-semibold text-surface-700 w-16">Ends</span>
                                <label class="inline-flex items-center gap-1.5 text-sm cursor-pointer"><input type="radio" value="" x-model="f.ends_mode" class="text-brand-600"> No end date</label>
                                <label class="inline-flex items-center gap-1.5 text-sm cursor-pointer"><input type="radio" value="at" x-model="f.ends_mode" class="text-brand-600"> Last day</label>
                                <input type="date" x-model="f.ends_on" x-show="f.ends_mode === 'at'" :min="CargoUI.isoDate()" :required="f.ends_mode === 'at'" aria-label="Last day the fare applies" class="fe-field w-auto py-1.5">
                            </div>
                            <label x-show="f.ends_mode === 'at'" class="flex items-center gap-2 text-sm text-surface-700 cursor-pointer">
                                <input type="checkbox" x-model="f.resume" class="rounded border-surface-300 text-brand-600"> Afterwards, go back to the price that applies before it (seasonal fare)
                            </label>
                            <p class="text-xs" :class="needsApproval() ? 'text-amber-800' : 'text-surface-600'">
                                <i class="fas mr-1" :class="needsApproval() ? 'fa-user-shield' : 'fa-bolt'"></i>
                                <span x-text="needsApproval() ? 'Needs a checker\'s approval. Approved before the start, it starts then; approved later, it starts when approved.' : 'Applies without approval.'"></span>
                            </p>
                            <p class="fe-error" x-show="errors.takes_effect_at" x-text="errors.takes_effect_at"></p>
                        </div>

                        <div class="space-y-1.5">
                            <label for="fe-reason" class="block text-xs font-semibold text-surface-700">Reason <span class="text-red-500" x-show="mode === 'revise'">*</span><span class="font-normal text-surface-500" x-show="mode === 'create'">(optional)</span></label>
                            <input id="fe-reason" type="text" maxlength="255" x-model="f.reason" :required="mode === 'revise'" placeholder="e.g. Fuel levy from November" class="fe-field">
                            <p class="fe-error" x-show="errors.reason" x-text="errors.reason"></p>
                        </div>
                    </div>
                </template>

                {{-- End / revoke --}}
                <template x-if="mode === 'end'">
                    <div class="space-y-4">
                        <div class="rounded-xl border border-surface-200 bg-surface-50 px-4 py-3 text-sm text-surface-800" x-text="fareSummary()"></div>
                        <label class="flex items-start gap-2 text-sm cursor-pointer rounded-xl border border-red-200 bg-red-50/60 px-4 py-3">
                            <input type="checkbox" x-model="f.revoke" class="mt-0.5 rounded border-surface-300 text-red-600">
                            <span><strong class="text-red-800">Configured incorrectly — revoke it.</strong> <span class="text-surface-700">It stops at once and is marked revoked; bookings priced on it keep their price and are listed in the history for review.</span></span>
                        </label>
                        <div x-show="!f.revoke" class="flex flex-wrap items-center gap-x-4 gap-y-2">
                            <span class="text-xs font-semibold text-surface-700">Stops</span>
                            <label class="inline-flex items-center gap-1.5 text-sm cursor-pointer"><input type="radio" value="now" x-model="f.end_mode" class="text-brand-600"> <span x-text="needsApproval() ? 'When approved' : 'Now'"></span></label>
                            <label class="inline-flex items-center gap-1.5 text-sm cursor-pointer"><input type="radio" value="at" x-model="f.end_mode" class="text-brand-600"> After the last day</label>
                            <input type="date" x-model="f.ends_on" x-show="f.end_mode === 'at'" :min="CargoUI.isoDate()" :required="f.end_mode === 'at' && !f.revoke" aria-label="Last day the fare applies" class="fe-field w-auto py-1.5">
                        </div>
                        <p class="fe-error" x-show="errors.ends_on" x-text="errors.ends_on"></p>
                        <div class="space-y-1.5">
                            <label for="fe-end-reason" class="block text-xs font-semibold text-surface-700">Reason <span class="text-red-500">*</span></label>
                            <input id="fe-end-reason" type="text" maxlength="255" x-model="f.reason" required class="fe-field">
                            <p class="fe-error" x-show="errors.reason" x-text="errors.reason"></p>
                        </div>
                    </div>
                </template>

                {{-- Delete (unused fares only) --}}
                <template x-if="mode === 'delete'">
                    <div class="space-y-4">
                        <div class="rounded-xl border border-surface-200 bg-surface-50 px-4 py-3 text-sm text-surface-800" x-text="fareSummary()"></div>
                        <p class="text-sm text-surface-700">Only a fare that has not started and was never used can be deleted. Fares that priced bookings are ended instead, so their history stays.</p>
                        <input type="text" maxlength="255" x-model="f.reason" placeholder="Reason (optional)" aria-label="Reason" class="fe-field">
                    </div>
                </template>

                {{-- Bulk value charge --}}
                <template x-if="mode === 'bulk'">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="space-y-1.5">
                                <label for="fe-b-type" class="block text-xs font-semibold text-surface-700">Cargo type</label>
                                <select id="fe-b-type" x-model="f.cargo_type_id" class="fe-field"><option value="">All types</option><template x-for="t in types" :key="t.id"><option :value="String(t.id)" x-text="t.name"></option></template></select>
                            </div>
                            <div class="space-y-1.5">
                                <label for="fe-b-route" class="block text-xs font-semibold text-surface-700">Route</label>
                                <select id="fe-b-route" x-model="f.route_id" class="fe-field"><option value="">All routes</option><template x-for="r in routes" :key="r.id"><option :value="String(r.id)" x-text="r.name"></option></template></select>
                            </div>
                            <div class="space-y-1.5">
                                <label for="fe-b-pct" class="block text-xs font-semibold text-surface-700">% of declared value <span class="text-red-500">*</span></label>
                                <div class="relative"><input id="fe-b-pct" type="number" step="0.01" min="0" max="100" required x-model="f.value_charge_pct" class="fe-field pr-8"><span class="absolute inset-y-0 right-3 flex items-center text-sm text-surface-500">%</span></div>
                            </div>
                        </div>
                        @include('admin.cargo.partials.fare-editor-start')
                        <input type="text" maxlength="255" x-model="f.reason" required placeholder="Reason (required)" aria-label="Reason" class="fe-field">
                        <p class="fe-error" x-show="errors.reason" x-text="errors.reason"></p>
                    </div>
                </template>

                {{-- Copy a route's fares --}}
                <template x-if="mode === 'copy'">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label for="fe-c-from" class="block text-xs font-semibold text-surface-700">Copy the fares of <span class="text-red-500">*</span></label>
                                <select id="fe-c-from" x-model="f.source_route_id" required class="fe-field"><option value="">Select route…</option><template x-for="r in routes" :key="r.id"><option :value="String(r.id)" x-text="r.name"></option></template></select>
                            </div>
                            <div class="space-y-1.5">
                                <label for="fe-c-to" class="block text-xs font-semibold text-surface-700">To <span class="text-red-500">*</span></label>
                                <select id="fe-c-to" x-model="f.target_route_id" :disabled="lock.route" required class="fe-field"><option value="">Select route…</option><template x-for="r in routes" :key="r.id"><option :value="String(r.id)" x-text="r.name"></option></template></select>
                                <p class="fe-error" x-show="errors.target_route_id" x-text="errors.target_route_id"></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label for="fe-c-adj" class="block text-xs font-semibold text-surface-700">Adjust prices by</label>
                                <div class="relative"><input id="fe-c-adj" type="number" step="0.1" min="-90" max="500" x-model="f.adjust_pct" class="fe-field pr-8"><span class="absolute inset-y-0 right-3 flex items-center text-sm text-surface-500">%</span></div>
                                <p class="text-xs text-surface-500">0 = same prices. Vessel-specific fares are not copied.</p>
                            </div>
                            <div class="space-y-1.5">
                                <span class="block text-xs font-semibold text-surface-700">Types already priced on the target route</span>
                                <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="radio" value="skip" x-model="f.existing" class="text-brand-600"> Keep their fares (skip)</label>
                                <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="radio" value="replace" x-model="f.existing" class="text-brand-600"> Replace with the copied price</label>
                            </div>
                        </div>
                        <details class="rounded-xl border border-surface-200 px-4 py-2.5 text-sm">
                            <summary class="cursor-pointer font-semibold text-surface-700" x-text="f.type_ids.length ? `${f.type_ids.length} type(s) selected` : 'All cargo types (choose some instead)'"></summary>
                            <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-1 max-h-48 overflow-y-auto">
                                <template x-for="t in types" :key="t.id">
                                    <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" :value="String(t.id)" x-model="f.type_ids" class="rounded border-surface-300 text-brand-600"> <span x-text="t.name"></span></label>
                                </template>
                            </div>
                        </details>
                        @include('admin.cargo.partials.fare-editor-start')
                        <input type="text" maxlength="255" x-model="f.reason" required placeholder="Reason (required)" aria-label="Reason" class="fe-field">
                        <p class="fe-error" x-show="errors.reason" x-text="errors.reason"></p>
                    </div>
                </template>
            </div>

            {{-- ── REVIEW ───────────────────────────────────────── --}}
            <div x-show="step === 'review'" class="space-y-3">
                <p class="text-sm text-surface-700" x-show="!multi()">Check what happens before saving:</p>
                <p class="text-sm text-surface-700" x-show="multi()" x-text="multiSummary()"></p>

                <div class="space-y-2" :class="multi() && 'max-h-80 overflow-y-auto pr-1'">
                    <template x-for="s in (plan?.series || [])" :key="s.key">
                        <div class="space-y-1.5" :class="multi() && 'rounded-xl border border-surface-200 p-3'">
                            <p x-show="multi()" class="text-xs font-bold text-surface-800" x-text="`${s.cargo_type} — ${s.route}${s.vessel ? ' (' + s.vessel + ')' : ''}`"></p>
                            <template x-for="(a, i) in s.alerts" :key="i">
                                <div class="flex items-start gap-2 rounded-xl border px-3 py-2 text-sm" :class="alertClass(a.level)">
                                    <i class="fas mt-0.5" :class="alertIcon(a.level)"></i><span x-text="a.text"></span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <template x-for="b in (plan?.blocking || [])" :key="b">
                    <div class="flex items-start gap-2 rounded-xl border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-800"><i class="fas fa-ban mt-0.5"></i><span x-text="b"></span></div>
                </template>

                <label x-show="plan?.needs_replace" class="flex items-center gap-2 rounded-xl border border-red-200 bg-red-50/60 px-4 py-2.5 text-sm font-semibold text-red-800 cursor-pointer">
                    <input type="checkbox" x-model="f.replace" @change="review()" class="rounded border-surface-300 text-red-600"> Replace it — the existing fare is cancelled and kept in the history
                </label>

                <p class="text-xs text-surface-600" x-show="plan && !plan.blocking.length">
                    <i class="fas mr-1" :class="plan?.requires_approval ? 'fa-user-shield text-amber-600' : 'fa-bolt'"></i>
                    <span x-text="plan?.requires_approval ? 'This goes to a checker for approval. Nothing changes until it is approved.' : 'This is saved straight away (no approval needed).'"></span>
                </p>
            </div>

            <div x-show="message" class="rounded-xl border px-4 py-2.5 text-sm" :class="messageLevel === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-amber-50 border-amber-200 text-amber-800'" x-text="message"></div>

            <div class="flex justify-end gap-3 pt-2 border-t border-surface-100">
                <button type="button" @click="step === 'review' ? (step = 'form', message = '') : close()" class="px-5 py-2.5 text-sm font-semibold text-surface-600 rounded-xl border border-surface-200 hover:bg-surface-50" x-text="step === 'review' ? 'Back' : 'Cancel'"></button>
                <button type="submit" :disabled="busy || (step === 'review' && !canSave())"
                    class="px-6 py-2.5 text-sm font-semibold text-white rounded-xl flex items-center gap-2 disabled:opacity-50"
                    :class="mode === 'delete' || (mode === 'end' && f.revoke) ? 'bg-red-600 hover:bg-red-700' : 'bg-brand-600 hover:bg-brand-700'">
                    <i class="fas text-xs" :class="busy ? 'fa-circle-notch fa-spin' : (step === 'review' ? (plan?.requires_approval ? 'fa-paper-plane' : 'fa-check') : 'fa-magnifying-glass')"></i>
                    <span x-text="step === 'form' ? 'Review' : (plan?.requires_approval ? 'Submit for approval' : 'Confirm & save')"></span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('styles')
<style>
    .fe-field { width: 100%; padding: .625rem .75rem; font-size: .875rem; border-radius: .75rem; border: 1px solid rgb(226 232 240); background: #fff; outline: none; }
    .fe-field:focus { border-color: rgb(96 165 250); box-shadow: 0 0 0 2px rgb(219 234 254); }
    .fe-field:disabled { background: rgb(248 250 252); color: rgb(71 85 105); }
    .fe-error { font-size: .75rem; color: rgb(185 28 28); }
</style>
@endpush

@push('scripts')
<script>
function fareEditor() {
    const { api, num, isoDate } = window.CargoUI;
    const BASE = '/admin/cargo/api/fares';
    const UNIT = { kg: 'kg', cbm: 'cubic metre', unit: 'piece', lane_meter: 'vehicle' };
    const BASIS = { kg: 'price_per_kg_tzs', cbm: 'price_per_cbm_tzs' };
    const PRICE_FIELDS = ['price_tzs', 'price_usd', 'price_per_kg_tzs', 'price_per_kg_usd', 'price_per_cbm_tzs', 'price_per_cbm_usd', 'min_charge_tzs', 'min_charge_usd', 'value_charge_pct'];
    const KIND = { create: 'cargo_fare_create', revise: 'cargo_fare_revise', end: 'cargo_fare_end', delete: 'cargo_fare_delete', bulk: 'cargo_fare_bulk_update', copy: 'cargo_fare_copy' };
    const localInput = iso => { const d = new Date(iso); return `${isoDate(d)}T${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`; };
    const blank = () => ({
        cargo_type_id: '', route_id: '', transport_unit_id: '', price_tzs: '', price_usd: '', price_per_kg_tzs: '', price_per_kg_usd: '',
        price_per_cbm_tzs: '', price_per_cbm_usd: '', min_charge_tzs: '', min_charge_usd: '', value_charge_pct: 0,
        takes_effect: 'now', takes_effect_at: '', ends_mode: '', ends_on: '', resume: true, replace: false, reason: '',
        end_mode: 'now', revoke: false, source_route_id: '', target_route_id: '', adjust_pct: 0, existing: 'skip', type_ids: [],
    });

    return {
        isOpen: false, mode: 'create', step: 'form', busy: false, plan: null, message: '', messageLevel: 'error', errors: {},
        fare: null, lock: { type: false, route: false }, f: blank(),
        types: [], routes: [], units: [], rules: {}, loaded: false,

        async load() {
            if (this.loaded) return;
            const [types, routes, units, rules] = await Promise.all([
                api('/admin/cargo/api/types'), api('/admin/cargo/api/routes'), api('/admin/cargo/api/transport-units'), api(`${BASE}/rules`),
            ]);
            this.types = types.filter(t => t.status === 'active');
            this.routes = routes; this.units = units; this.rules = rules; this.loaded = true;
        },

        async open(d = {}) {
            try { await this.load(); } catch (e) { window.notify?.(e.message, 'error'); return; }
            this.mode = d.mode || 'create';
            this.fare = d.fare || null;
            this.lock = { type: !!d.lock_type, route: !!d.lock_route };
            this.f = blank();
            this.step = 'form'; this.plan = null; this.message = ''; this.errors = {};

            const fare = this.fare;
            if (fare) {
                this.f.cargo_type_id = String(fare.cargo_type_id); this.f.route_id = String(fare.route_id);
                this.f.transport_unit_id = fare.transport_unit_id ? String(fare.transport_unit_id) : '';
                PRICE_FIELDS.forEach(k => { this.f[k] = fare[k] === null || fare[k] === undefined || Number(fare[k]) === 0 && k !== 'value_charge_pct' ? '' : Number(fare[k]); });
                if (fare.state === 'scheduled' && this.mode === 'revise') { this.f.takes_effect = 'at'; this.f.takes_effect_at = localInput(fare.effective_from); }
                if (fare.effective_until && this.mode === 'revise') { this.f.ends_mode = 'at'; this.f.ends_on = isoDate(new Date(new Date(fare.effective_until).getTime() - 60000)); }
            }
            ['cargo_type_id', 'route_id', 'transport_unit_id', 'value_charge_pct'].forEach(k => { if (d[k] !== undefined && d[k] !== null) this.f[k] = String(d[k]); });
            if (this.mode === 'copy' && d.route_id) { this.f.target_route_id = String(d.route_id); this.f.route_id = ''; }
            this.isOpen = true;
        },
        close() { this.isOpen = false; },

        title() {
            return { create: 'Add a fare', revise: 'Change fare', end: this.f.revoke ? 'Revoke fare' : 'End fare', delete: 'Delete fare', bulk: 'Value charge for several fares', copy: "Copy a route's fares" }[this.mode];
        },
        subtitle() {
            if (this.mode === 'revise') return 'A new version is saved; the current one is kept in the history.';
            if (this.mode === 'bulk') return 'Each fare gets a new version with the new percentage.';
            return '';
        },
        type() { return this.types.find(t => String(t.id) === String(this.f.cargo_type_id)) || null; },
        unitLabel: u => UNIT[u] || u,
        basisField() { return BASIS[this.type()?.measurement_unit] || 'price_tzs'; },
        rateLabel() { return { price_per_kg_tzs: 'Rate per kg', price_per_cbm_tzs: 'Rate per cubic metre' }[this.basisField()] || (this.type()?.measurement_unit === 'lane_meter' ? 'Price per vehicle' : 'Price per piece'); },
        basisText() {
            const u = this.type()?.measurement_unit;
            return u === 'kg' ? 'by weight: the per-kg rate is required; the minimum charge covers light items.'
                : u === 'cbm' ? 'by volume: the per-cubic-metre rate is required.'
                : u === 'lane_meter' ? 'per vehicle: the price is charged for each vehicle.' : 'per piece: the price is charged for each piece.';
        },
        needsApproval() { return !!this.rules[this.mode]?.requires_approval; },
        minStart() { return localInput(new Date(Date.now() + 5 * 60000).toISOString()); },
        multi() { return this.mode === 'bulk' || this.mode === 'copy'; },
        multiSummary() {
            const s = this.plan?.series || [];
            const changing = s.filter(x => x.changes > 0).length, skipped = s.filter(x => x.skipped).length;
            return `${changing} fare${changing === 1 ? '' : 's'} will change` + (skipped ? `, ${skipped} skipped` : '') + '.';
        },
        fareSummary() {
            const x = this.fare; if (!x) return '';
            const t = this.types.find(t => t.id === x.cargo_type_id);
            return `${t?.name || ''} — ${x.route?.name || ''}${x.transport_unit ? ' (' + x.transport_unit.name + ')' : ''}: ${x.price_label || ''} · v${x.version}, ${x.state}`;
        },
        alertClass: l => ({ red: 'border-red-200 bg-red-50 text-red-800', amber: 'border-amber-200 bg-amber-50 text-amber-900', green: 'border-emerald-200 bg-emerald-50 text-emerald-800' }[l] || 'border-sky-200 bg-sky-50 text-sky-900'),
        alertIcon: l => ({ red: 'fa-triangle-exclamation', amber: 'fa-clock', green: 'fa-circle-check' }[l] || 'fa-circle-info'),
        canSave() { return this.plan && !this.plan.blocking.length && !(this.plan.needs_replace && !this.f.replace); },

        body() {
            const f = this.f, start = f.takes_effect === 'at' ? { takes_effect: 'at', takes_effect_at: f.takes_effect_at } : { takes_effect: 'now' };
            switch (this.mode) {
                case 'create': case 'revise': {
                    const b = { cargo_type_id: f.cargo_type_id, route_id: f.route_id, transport_unit_id: f.transport_unit_id || null,
                        ends_on: f.ends_mode === 'at' ? f.ends_on : null, resume: !!f.resume, replace: !!f.replace, reason: f.reason || null, ...start };
                    PRICE_FIELDS.forEach(k => { b[k] = f[k] === '' || f[k] === null ? null : Number(f[k]); });
                    return b;
                }
                case 'end': return { end_mode: f.revoke ? 'now' : f.end_mode, ends_on: f.end_mode === 'at' && !f.revoke ? f.ends_on : null, revoke: !!f.revoke, reason: f.reason };
                case 'delete': return { reason: f.reason || null };
                case 'bulk': return { value_charge_pct: f.value_charge_pct, cargo_type_id: f.cargo_type_id || null, route_id: f.route_id || null, reason: f.reason, ...start };
                case 'copy': return { source_route_id: f.source_route_id, target_route_id: f.target_route_id, type_ids: f.type_ids.map(Number), adjust_pct: Number(f.adjust_pct || 0), existing: f.existing, reason: f.reason, ...start };
            }
        },

        showErrors(e) {
            this.errors = Object.fromEntries(Object.entries(e.errors || {}).map(([k, v]) => [k, v[0]]));
            this.message = Object.keys(this.errors).length ? '' : e.message;
            this.messageLevel = 'error';
        },

        async review() {
            this.busy = true; this.errors = {}; this.message = '';
            try {
                this.plan = await api(`${BASE}/preview`, { method: 'POST', body: { kind: this.mode, fare_id: this.fare?.id || null, ...this.body() } });
                this.step = 'review';
            } catch (e) {
                this.step = 'form';
                this.showErrors(e);
            } finally { this.busy = false; }
        },

        async save() {
            const id = this.fare?.id;
            const target = { create: [BASE, 'POST'], revise: [`${BASE}/${id}`, 'PUT'], end: [`${BASE}/${id}/end`, 'POST'], delete: [`${BASE}/${id}`, 'DELETE'],
                bulk: [`${BASE}/value-charge`, 'POST'], copy: [`${BASE}/copy`, 'POST'] }[this.mode];
            this.busy = true; this.message = '';
            try {
                const res = await api(target[0], { method: target[1], body: { ...this.body(), plan_hash: this.plan?.hash } });
                CargoUI.notifyChange(res);
                window.dispatchEvent(new CustomEvent('fares-changed', { detail: res }));
                this.close();
            } catch (e) {
                if (e.data?.plan) {
                    // 409: fares changed meanwhile / 422: blocked — show the fresh plan
                    this.plan = e.data.plan; this.step = 'review';
                    this.message = e.message; this.messageLevel = e.status === 409 ? 'warning' : 'error';
                } else {
                    this.step = 'form';
                    this.showErrors(e);
                }
            } finally { this.busy = false; }
        },
    };
}
</script>
@endpush
@endonce
