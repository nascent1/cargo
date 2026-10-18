<x-layouts.admin>
<x-slot:title>Cargo Booking</x-slot:title>
<x-slot:header>Cargo Booking</x-slot:header>

@include('admin.cargo.partials.ui')
@push('styles')
    @include('partials.cargo-ui-theme')
@endpush

@php
    $config = [
        'types' => $cargoTypes,
        'paymentMethods' => $paymentMethods,
        'canOverride' => $canOverrideFare,
        'networkLabels' => \App\Support\Msisdn::NETWORK_LABELS,
        'networkPrefixes' => \App\Support\Msisdn::NETWORK_PREFIXES,
        'csrf' => csrf_token(),
    ];
    $flowGroups = [
        'cash' => ['Cash', 'fa-money-bill-wave'],
        'ussd_push' => ["Mobile money — USSD push to the customer's phone", 'fa-mobile-alt'],
        'lipa_namba' => ['Lipa Namba — customer pays, you confirm the SMS reference', 'fa-store'],
    ];
@endphp

{{-- Same screen as the sales portal's booking form (resources/js/portal/views/cargo/CargoBooking.vue) --}}
<div x-data="bookingTerminal(@js($config))" x-cloak class="ui-scope py-2 pb-28 lg:pb-2 text-slate-800">

    {{-- Header + steps --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">New cargo booking</h1>
            <p class="text-sm text-slate-600 mt-0.5">The system works out the price as you type.</p>
        </div>
        <ol class="flex items-center gap-1" aria-label="Booking steps">
            <template x-for="(s, i) in steps" :key="i">
                <li class="flex items-center gap-1">
                    <button type="button" @click="goStep(i + 1)" :disabled="!canVisit(i + 1)" class="ui-step"
                        :class="step === i + 1 ? 'bg-brand-600 text-white' : (step > i + 1 ? 'text-emerald-700 hover:bg-emerald-50' : 'text-slate-500')"
                        :aria-current="step === i + 1 ? 'step' : null">
                        <span class="ui-step-dot" :class="step === i + 1 ? 'bg-white text-brand-700' : (step > i + 1 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600')">
                            <i x-show="step > i + 1" class="fas fa-check text-[9px]"></i><span x-show="step <= i + 1" x-text="i + 1"></span>
                        </span>
                        <span class="hidden sm:inline" x-text="s"></span>
                    </button>
                    <span x-show="i < steps.length - 1" class="w-4 h-px bg-slate-300 hidden sm:block"></span>
                </li>
            </template>
        </ol>
    </div>

    {{-- Banner --}}
    <template x-if="banner">
        <div class="mb-4 rounded-xl px-4 py-3 text-sm flex items-start gap-3 border"
            :class="{ 'bg-red-50 border-red-200 text-red-800': banner.type === 'error', 'bg-amber-50 border-amber-300 text-amber-900': banner.type === 'warning', 'bg-emerald-50 border-emerald-200 text-emerald-800': banner.type === 'success' }"
            role="alert">
            <i class="fas mt-0.5" :class="{ 'fa-circle-exclamation': banner.type === 'error', 'fa-triangle-exclamation': banner.type === 'warning', 'fa-circle-check': banner.type === 'success' }"></i>
            <span class="flex-1" x-text="banner.text"></span>
            <button type="button" @click="banner = null" class="opacity-60 hover:opacity-100" aria-label="Dismiss"><i class="fas fa-times"></i></button>
        </div>
    </template>

    {{-- Booked (paid) --}}
    <template x-if="done">
        <div class="ui-card border-emerald-200 p-8 text-center max-w-xl mx-auto">
            <div class="h-14 w-14 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto text-2xl"><i class="fas fa-check"></i></div>
            <h2 class="text-xl font-bold text-slate-900 mt-4">Booked and paid</h2>
            <p class="text-sm text-slate-600 mt-1">Booking <span class="font-mono font-bold text-slate-900" x-text="done.consignment.consignment_number"></span> · TZS <span x-text="CargoUI.num(done.payment.amount)"></span></p>
            <div class="flex flex-wrap justify-center gap-2 mt-6">
                <a :href="`/admin/cargo/bookings/${done.consignment.id}/print`" target="_blank" class="ui-btn"><i class="fas fa-print"></i> Print receipt</a>
                <a :href="`/admin/cargo/bookings/${done.consignment.id}`" class="ui-btn"><i class="fas fa-eye"></i> Open booking</a>
                <button type="button" @click="startNew(true)" class="ui-btn"><i class="fas fa-user-check"></i> Same sender again</button>
                <button type="button" @click="startNew(false)" class="ui-btn-primary"><i class="fas fa-plus"></i> New booking</button>
            </div>
        </div>
    </template>

    <div x-show="!done" class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
        <div class="lg:col-span-2 space-y-4">

            {{-- Chosen departure (steps 2–4) --}}
            <div x-show="step > 1 && service" class="ui-card px-4 py-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                <i class="fas fa-ship text-brand-600"></i>
                <span class="font-semibold text-slate-900" x-text="service?.route?.name"></span>
                <span class="text-slate-500"><span x-text="dayLabel(String(service?.departure_date || '').slice(0, 10), true)"></span> · <span class="font-mono" x-text="(service?.departure_time || '').slice(0, 5)"></span> · <span x-text="service?.transport_unit?.name"></span></span>
                <button type="button" @click="goStep(1)" class="ui-link ml-auto">Change</button>
            </div>

            {{-- ═══ STEP 1: Departure ═══ --}}
            <section x-show="step === 1" class="ui-card overflow-hidden">
                <div class="px-4 py-3 flex flex-wrap items-center gap-2 border-b border-slate-100">
                    <button type="button" @click="setRoute(null)" class="ui-chip" :class="!routeFilter && 'ui-chip-on'">All routes</button>
                    <template x-for="r in routeChoices" :key="r.id">
                        <button type="button" @click="setRoute(r)" class="ui-chip" :class="routeFilter?.id === r.id && 'ui-chip-on'">
                            <i x-show="routeFilter?.id === r.id" class="fas fa-check text-[10px]"></i><span x-text="r.name"></span>
                        </button>
                    </template>
                    <div class="ml-auto flex items-center gap-2">
                        <label for="bk-other-date" class="text-xs text-slate-500">Other date</label>
                        <input id="bk-other-date" type="date" x-model="otherDate" :min="today" @change="loadServices()" class="ui-field w-auto h-9">
                        <button type="button" x-show="otherDate" @click="otherDate = ''; loadServices()" class="ui-link text-xs">Next days</button>
                        <button type="button" @click="loadServices()" class="ui-btn h-9 w-9 px-0" title="Refresh" aria-label="Refresh departures"><i class="fas fa-rotate-right text-xs"></i></button>
                    </div>
                </div>

                <div x-show="loadingServices" class="py-12 text-center text-sm text-slate-500"><i class="fas fa-circle-notch fa-spin text-brand-500 mr-2"></i>Loading departures…</div>
                <div x-show="!loadingServices && !hasDepartures" class="py-12 text-center text-sm text-slate-500">
                    <i class="fas fa-ship text-3xl text-slate-300 block mb-2"></i>
                    <span x-text="otherDate ? 'No departures open for booking on this date.' : `No departures open for booking in the next ${days} days${routeFilter ? ' on this route' : ''}.`"></span>
                </div>

                <div x-show="!loadingServices && hasDepartures" class="pb-2">
                    <template x-for="g in departureGroups" :key="g.date">
                        <div>
                            <p class="ui-day"><span x-text="g.label"></span><span x-show="!g.services.length" class="normal-case font-normal tracking-normal text-slate-400"> — no more departures today</span></p>
                            <div class="px-3 space-y-0.5">
                                <template x-for="s in g.services" :key="s.id">
                                    <button type="button" @click="selectService(s)" :disabled="s.space?.full || !s.priced_type_ids?.length" class="ui-departure"
                                        :class="service?.id === s.id && 'bg-brand-50 ring-1 ring-brand-300'">
                                        <span class="font-mono font-semibold text-slate-900 w-12 shrink-0" x-text="(s.departure_time || '').slice(0, 5)"></span>
                                        <span class="flex-1 min-w-0">
                                            <span class="block text-sm font-semibold text-slate-900 truncate" x-text="s.route?.name"></span>
                                            <span class="block text-xs text-slate-500 truncate"><span x-text="s.transport_unit?.name"></span><span class="sm:hidden" :class="spaceInfo(s).cls" x-text="' · ' + spaceInfo(s).text"></span></span>
                                        </span>
                                        <span class="w-48 shrink-0 hidden sm:block">
                                            <span class="block h-1.5 rounded-full bg-slate-200 overflow-hidden"><span class="block h-full rounded-full" :class="spaceInfo(s).bar" :style="`width:${Math.max(spaceInfo(s).pct, 2)}%`"></span></span>
                                            <span class="block text-[11px] mt-1" :class="spaceInfo(s).cls" x-text="spaceInfo(s).text"></span>
                                        </span>
                                        <span class="w-16 shrink-0 text-right text-sm font-semibold" :class="s.space?.full || !s.priced_type_ids?.length ? 'text-slate-400' : 'text-brand-700'"
                                            :title="!s.priced_type_ids?.length ? 'No cargo fares are set for this route' : ''" x-text="s.space?.full ? 'Full' : (!s.priced_type_ids?.length ? 'No fares' : 'Select ›')"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
                <div x-show="!loadingServices && !otherDate && days < 14" class="px-4 py-3 border-t border-slate-100">
                    <button type="button" @click="days = 14; loadServices()" class="ui-link"><i class="fas fa-chevron-down mr-1 text-xs"></i>Later dates (up to 2 weeks ahead)</button>
                </div>
            </section>

            {{-- ═══ STEP 2: Cargo ═══ --}}
            <section x-show="step === 2" class="space-y-3">
                <template x-for="(line, idx) in lines" :key="line.id">
                    <div class="ui-card p-5 space-y-4" :class="lineHasError(line) && 'border-red-300'">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-slate-900">Item <span x-text="idx + 1"></span>
                                <span x-show="typeOf(line)" class="font-normal text-slate-500">· <span x-text="unitLabel(line)"></span></span></h3>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold text-slate-900 tabular-nums whitespace-nowrap" x-show="lineQuote(line)" x-text="'TZS ' + CargoUI.num(lineQuote(line)?.final_fare)"></span>
                                <button type="button" x-show="lines.length > 1" @click="removeLine(line)" class="h-8 w-8 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-700" :aria-label="`Remove item ${idx + 1}`"><i class="fas fa-trash-can text-xs"></i></button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="ui-label" :for="`type-${line.id}`">Cargo type <span class="text-red-600">*</span></label>
                                <select :id="`type-${line.id}`" x-model="line.cargo_type_id" @change="onTypeChange(line)" class="ui-field" :class="err(line, 'cargo_type_id') && 'ui-field-invalid'">
                                    <option value="">Select cargo type…</option>
                                    <template x-for="group in typeGroups" :key="group.name">
                                        <optgroup :label="group.name">
                                            <template x-for="t in group.types" :key="t.id">
                                                <option :value="String(t.id)" x-text="t.name" :selected="String(t.id) === String(line.cargo_type_id)"></option>
                                            </template>
                                        </optgroup>
                                    </template>
                                </select>
                                <p class="ui-error" x-show="err(line, 'cargo_type_id')" x-text="err(line, 'cargo_type_id')"></p>
                            </div>
                            <div>
                                <label class="ui-label" :for="`desc-${line.id}`">What's inside <span class="font-normal text-slate-400">(optional)</span></label>
                                <input :id="`desc-${line.id}`" type="text" x-model="line.description" maxlength="255" placeholder="e.g. 20 bags of cement" class="ui-field">
                            </div>
                        </div>

                        <div x-show="typeOf(line)" class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            {{-- Pieces (not for vehicles: one vehicle per line) --}}
                            <div x-show="unit(line) !== 'lane_meter'">
                                <label class="ui-label" :for="`qty-${line.id}`">Pieces <span class="text-red-600">*</span></label>
                                <input :id="`qty-${line.id}`" type="number" min="1" step="1" inputmode="numeric" x-model.number="line.quantity" class="ui-field" :class="err(line, 'quantity') && 'ui-field-invalid'">
                                <p class="ui-error" x-show="err(line, 'quantity')" x-text="err(line, 'quantity')"></p>
                            </div>
                            {{-- Vehicle details --}}
                            <template x-if="unit(line) === 'lane_meter'">
                                <div class="col-span-2 md:col-span-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div>
                                        <label class="ui-label" :for="`reg-${line.id}`">Registration no. <span class="text-red-600">*</span></label>
                                        <input :id="`reg-${line.id}`" type="text" x-model="line.vehicle_reg_number" maxlength="30" placeholder="T 123 ABC" class="ui-field uppercase font-mono" :class="err(line, 'vehicle_reg_number') && 'ui-field-invalid'">
                                        <p class="ui-error" x-show="err(line, 'vehicle_reg_number')" x-text="err(line, 'vehicle_reg_number')"></p>
                                    </div>
                                    <div>
                                        <label class="ui-label" :for="`drv-${line.id}`">Driver name</label>
                                        <input :id="`drv-${line.id}`" type="text" x-model="line.driver_name" maxlength="100" class="ui-field">
                                    </div>
                                    <div>
                                        <label class="ui-label" :for="`drvp-${line.id}`">Driver phone</label>
                                        <input :id="`drvp-${line.id}`" type="tel" x-model="line.driver_phone" maxlength="20" placeholder="0754 123 456" class="ui-field font-mono" :class="err(line, 'driver_phone') && 'ui-field-invalid'">
                                        <p class="ui-error" x-show="err(line, 'driver_phone')" x-text="err(line, 'driver_phone')"></p>
                                    </div>
                                </div>
                            </template>
                            {{-- Weight: required for per-kg types, otherwise under "More" --}}
                            <div x-show="unit(line) === 'kg' || line.more || err(line, 'weight_kg')">
                                <label class="ui-label" :for="`w-${line.id}`">
                                    <span x-text="unit(line) === 'kg' ? 'Total weight (kg)' : 'Weight (kg)'"></span>
                                    <span x-show="unit(line) === 'kg'" class="text-red-600">*</span>
                                </label>
                                <input :id="`w-${line.id}`" type="number" min="0" step="0.1" inputmode="decimal" x-model.number="line.weight_kg" class="ui-field" :class="err(line, 'weight_kg') && 'ui-field-invalid'">
                                <p class="ui-hint" x-show="!err(line, 'weight_kg') && Number(typeOf(line)?.max_weight_kg) > 0"
                                   x-text="`Up to ${CargoUI.num(typeOf(line)?.max_weight_kg)} kg per ${unit(line) === 'lane_meter' ? 'vehicle' : 'piece'}`"></p>
                                <p class="ui-error" x-show="err(line, 'weight_kg')" x-text="err(line, 'weight_kg')"></p>
                            </div>
                            {{-- Size of one piece for volume-priced types --}}
                            <template x-if="unit(line) === 'cbm'">
                                <div class="col-span-2 md:col-span-3">
                                    <label class="ui-label">Size of one piece (m) — length × width × height <span class="text-red-600">*</span></label>
                                    <div class="grid grid-cols-3 gap-2">
                                        <input type="number" min="0" step="0.01" inputmode="decimal" x-model.number="line.length_m" placeholder="Length" aria-label="Length in metres" class="ui-field" :class="err(line, 'volume_cbm') && 'ui-field-invalid'">
                                        <input type="number" min="0" step="0.01" inputmode="decimal" x-model.number="line.width_m" placeholder="Width" aria-label="Width in metres" class="ui-field" :class="err(line, 'volume_cbm') && 'ui-field-invalid'">
                                        <input type="number" min="0" step="0.01" inputmode="decimal" x-model.number="line.height_m" placeholder="Height" aria-label="Height in metres" class="ui-field" :class="err(line, 'volume_cbm') && 'ui-field-invalid'">
                                    </div>
                                    <p class="ui-hint" x-show="!err(line, 'volume_cbm')">Total: <span class="font-semibold text-slate-700 tabular-nums" x-text="cbm(line).toFixed(3) + ' m³'"></span></p>
                                    <p class="ui-error" x-show="err(line, 'volume_cbm')" x-text="err(line, 'volume_cbm')"></p>
                                </div>
                            </template>
                        </div>

                        {{-- Declared value: required when the fare charges a % of it, otherwise under "More" --}}
                        <div x-show="typeOf(line) && (needsValue(line) || line.more)" class="grid grid-cols-1 md:grid-cols-2 gap-3 items-start">
                            <div>
                                <label class="ui-label" :for="`val-${line.id}`">Declared value (TZS) <span x-show="needsValue(line)" class="text-red-600">*</span></label>
                                <input :id="`val-${line.id}`" type="number" min="0" step="1000" inputmode="numeric" x-model.number="line.declared_value" class="ui-field"
                                    :class="err(line, 'declared_value') && showValueError && 'ui-field-invalid'">
                                <p class="text-xs mt-1" x-show="err(line, 'declared_value')" :class="showValueError ? 'text-red-700' : 'text-amber-800'" x-text="err(line, 'declared_value')"></p>
                            </div>
                            <p x-show="lineQuote(line)?.value_charge_pct > 0" class="text-xs text-slate-600 md:pt-7">
                                <i class="fas fa-shield-halved text-brand-600 mr-1"></i>Value charge <span x-text="CargoUI.num(lineQuote(line)?.value_charge_pct, 2).replace(/\.?0+$/, '')"></span>% =
                                <b class="text-slate-900" x-text="'TZS ' + CargoUI.num(lineQuote(line)?.value_charge)"></b>
                            </p>
                        </div>

                        <div x-show="typeOf(line)" class="flex flex-wrap items-center gap-x-5 gap-y-2">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-800 cursor-pointer">
                                <input type="checkbox" x-model="line.is_fragile" :disabled="typeOf(line)?.is_fragile" class="h-4 w-4 rounded border-slate-400 accent-brand-600">
                                Fragile <span class="text-xs text-slate-500" x-text="typeOf(line)?.is_fragile ? '(always, for this type)' : '(handling surcharge)'"></span>
                            </label>
                            <span x-show="typeOf(line)?.requires_hazmat_declaration" class="text-xs font-semibold text-red-700"><i class="fas fa-biohazard mr-1"></i>Hazardous — check the sender's declaration</span>
                            <button type="button" x-show="moreLabel(line)" @click="line.more = !line.more" class="ui-link text-xs ml-auto">
                                <i class="fas mr-1" :class="line.more ? 'fa-minus' : 'fa-plus'"></i><span x-text="line.more ? 'Fewer details' : moreLabel(line)"></span>
                            </button>
                        </div>

                        <template x-if="lineQuote(line)">
                            <div class="pt-3 border-t border-slate-100 text-xs text-slate-600 flex flex-wrap gap-x-4 gap-y-1">
                                <span>Base <span class="font-semibold tabular-nums text-slate-800" x-text="CargoUI.num(lineQuote(line).base_fare)"></span>
                                    <span class="text-slate-500" x-text="pricingNote(lineQuote(line))"></span></span>
                                <template x-for="s in lineQuote(line).surcharges" :key="s.label">
                                    <span x-text="`${s.label}: ${CargoUI.num(s.amount)}`"></span>
                                </template>
                                <span x-show="lineQuote(line).value_charge > 0" x-text="`Value charge: ${CargoUI.num(lineQuote(line).value_charge)}`"></span>
                                <span x-show="lineQuote(line).discount_amount > 0" class="text-emerald-700" x-text="`Discount −${CargoUI.num(lineQuote(line).discount_amount)}`"></span>
                            </div>
                        </template>
                        <p x-show="lineQuote(line)?.warning" class="text-xs font-semibold text-red-700"><i class="fas fa-circle-exclamation mr-1"></i><span x-text="lineQuote(line)?.warning"></span></p>
                    </div>
                </template>

                <button type="button" @click="addLine()" class="w-full h-12 rounded-2xl border-2 border-dashed border-slate-300 hover:border-brand-400 hover:bg-brand-50/40 text-sm font-semibold text-slate-700">
                    <i class="fas fa-plus mr-1"></i> Add another item
                </button>
            </section>

            {{-- ═══ STEP 3: Sender & receiver ═══ --}}
            <section x-show="step === 3" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <template x-for="role in ['sender', 'receiver']" :key="role">
                    <div class="ui-card p-5 space-y-3">
                        <h2 class="text-base font-semibold text-slate-900 flex items-center gap-2">
                            <i class="fas" :class="role === 'sender' ? 'fa-arrow-up-from-bracket text-brand-600' : 'fa-hand-holding text-emerald-600'"></i>
                            <span x-text="role === 'sender' ? 'Sender' : 'Receiver'"></span>
                            <button type="button" x-show="role === 'receiver' && people.sender.name" @click="sameAsSender()" class="ui-link text-xs ml-auto font-semibold">Same person as sender</button>
                        </h2>
                        <div>
                            <label class="ui-label" :for="`${role}-phone`">Mobile number <span class="text-red-600">*</span></label>
                            <input :id="`${role}-phone`" type="tel" x-model="people[role].phone" @input="lookupCustomer(role)" maxlength="20" placeholder="0754 123 456" autocomplete="off"
                                class="ui-field font-mono" :class="errors[`${role}_phone`] && 'ui-field-invalid'">
                            <p class="ui-error" x-show="errors[`${role}_phone`]" x-text="errors[`${role}_phone`]"></p>
                            <div x-show="suggestions[role].length" class="mt-2 space-y-1">
                                <p class="text-[11px] text-slate-600">Used before:</p>
                                <template x-for="p in suggestions[role]" :key="p.name + p.role">
                                    <button type="button" @click="useCustomer(role, p)" class="w-full text-left rounded-lg border border-slate-300 hover:border-brand-400 hover:bg-brand-50/50 px-3 py-2 text-sm">
                                        <span class="font-semibold text-slate-900" x-text="p.name"></span>
                                        <span class="text-xs text-slate-600" x-text="` · ${p.role} · ${p.last_used}`"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div>
                            <label class="ui-label" :for="`${role}-name`">Full name <span class="text-red-600">*</span></label>
                            <input :id="`${role}-name`" type="text" x-model="people[role].name" maxlength="100" class="ui-field" :class="errors[`${role}_name`] && 'ui-field-invalid'">
                            <p class="ui-error" x-show="errors[`${role}_name`]" x-text="errors[`${role}_name`]"></p>
                        </div>
                        <div class="grid grid-cols-2 gap-3" x-show="people[role].showId">
                            <div>
                                <label class="ui-label" :for="`${role}-idt`">ID type</label>
                                <select :id="`${role}-idt`" x-model="people[role].id_type" class="ui-field">
                                    <option value="">None</option><option value="NIDA">NIDA</option><option value="Passport">Passport</option><option value="Voter">Voter ID</option><option value="Driving">Driving licence</option>
                                </select>
                            </div>
                            <div>
                                <label class="ui-label" :for="`${role}-idn`">ID number</label>
                                <input :id="`${role}-idn`" type="text" x-model="people[role].id_number" maxlength="50" class="ui-field">
                            </div>
                        </div>
                        <button type="button" x-show="!people[role].showId" @click="people[role].showId = true" class="ui-link text-xs"><i class="fas fa-plus mr-1"></i>Add ID</button>
                    </div>
                </template>
                <div class="md:col-span-2 ui-card p-5">
                    <label class="ui-label" for="special">Special instructions <span class="font-normal text-slate-500">(printed on the manifest)</span></label>
                    <textarea id="special" x-model="specialInstructions" rows="2" maxlength="1000" placeholder="e.g. Keep upright, call receiver on arrival" class="ui-field"></textarea>
                </div>
            </section>

            {{-- ═══ STEP 4: Review & pay ═══ --}}
            <section x-show="step === 4" class="space-y-4">
                <div class="ui-card p-5 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="ui-overline">Sender</p>
                        <p class="font-semibold text-slate-900" x-text="people.sender.name"></p>
                        <p class="text-slate-700 font-mono text-xs" x-text="people.sender.phone"></p>
                    </div>
                    <div>
                        <p class="ui-overline">Receiver</p>
                        <p class="font-semibold text-slate-900" x-text="people.receiver.name"></p>
                        <p class="text-slate-700 font-mono text-xs" x-text="people.receiver.phone"></p>
                    </div>
                    <div class="sm:col-span-2" x-show="specialInstructions">
                        <p class="ui-overline">Special instructions</p>
                        <p class="text-slate-800" x-text="specialInstructions"></p>
                    </div>
                </div>

                {{-- Fare override --}}
                <div x-show="cfg.canOverride" class="ui-card p-5">
                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-900 cursor-pointer">
                        <input type="checkbox" x-model="discount.on" class="h-4 w-4 rounded border-slate-400 accent-brand-600"> Apply a discount (fare override)
                    </label>
                    <div x-show="discount.on" class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3">
                        <div>
                            <label class="ui-label" for="disc-amt">Discount (TZS)</label>
                            <input id="disc-amt" type="number" min="0" step="500" x-model.number="discount.amount" class="ui-field" :class="(errors.discount_amount || quoteErrors.discount_amount) && 'ui-field-invalid'">
                            <p class="ui-error" x-show="errors.discount_amount || quoteErrors.discount_amount" x-text="errors.discount_amount || quoteErrors.discount_amount?.[0]"></p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="ui-label" for="disc-reason">Reason <span class="text-red-600">*</span> <span class="font-normal text-slate-500">(kept on the booking's history)</span></label>
                            <input id="disc-reason" type="text" x-model="discount.reason" maxlength="255" placeholder="e.g. Regular customer, approved by station manager" class="ui-field" :class="(errors.discount_reason || quoteErrors.discount_reason) && 'ui-field-invalid'">
                            <p class="ui-error" x-show="errors.discount_reason || quoteErrors.discount_reason" x-text="errors.discount_reason || quoteErrors.discount_reason?.[0]"></p>
                        </div>
                    </div>
                    <p x-show="discountPending && !errors.discount_reason" class="text-xs text-amber-800 mt-2"><i class="fas fa-circle-info mr-1"></i>Enter a reason to apply the discount to the total.</p>
                </div>

                {{-- Payment channel --}}
                <div class="ui-card p-5 space-y-4">
                    <h2 class="text-base font-semibold text-slate-900">Payment</h2>
                    @if(empty($paymentMethods))
                        <div class="px-4 py-3 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 text-sm"><i class="fas fa-triangle-exclamation mr-1"></i> No payment channels are ready. Set them up under Settings → Payment Channels.</div>
                    @endif
                    @foreach($flowGroups as $flow => [$groupLabel, $groupIcon])
                        @php $group = array_values(array_filter($paymentMethods, fn ($m) => $m['flow'] === $flow)); @endphp
                        @if(count($group))
                            <div>
                                <p class="ui-overline mb-1.5"><i class="fas {{ $groupIcon }} mr-1"></i>{{ $groupLabel }}</p>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    @foreach($group as $method)
                                        <button type="button" @click="selectPayment(@js($method['slug']))"
                                            class="relative px-3 py-3 rounded-xl border-2 text-center transition-colors"
                                            :class="payment.method === @js($method['slug']) ? 'border-brand-500 bg-brand-50' : 'border-slate-200 hover:border-slate-400'">
                                            @if($method['simulated'])
                                                <span class="absolute top-1 right-1 px-1 rounded bg-violet-100 text-violet-800 text-[9px] font-bold uppercase" title="No live credentials yet — the push is simulated (testing only)">Sim</span>
                                            @elseif(!$method['live'])
                                                <span class="absolute top-1 right-1 px-1 rounded bg-amber-100 text-amber-800 text-[9px] font-bold uppercase" title="Credentials not set up yet — no push will be sent">Not live</span>
                                            @endif
                                            <i class="fas {{ $method['icon'] }} text-lg block mb-1" :class="payment.method === @js($method['slug']) ? 'text-brand-600' : 'text-slate-500'"></i>
                                            <span class="text-xs font-semibold text-slate-800">{{ $method['name'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach

                    <div x-show="currentMethod?.flow === 'ussd_push'" class="rounded-xl bg-brand-50/60 border border-brand-200 p-4">
                        <label class="ui-label" for="payer">Payer's phone (receives the USSD push) <span class="text-red-600">*</span></label>
                        <input id="payer" type="tel" x-model="payment.msisdn" @input="onPayerInput()" placeholder="0754 123 456" class="ui-field sm:w-72 font-mono" :class="errors.payer_msisdn && 'ui-field-invalid'">
                        <p class="text-xs mt-1.5" :class="payerHint.cls" x-show="payerHint.text" x-html="payerHint.text"></p>
                        <p class="ui-error" x-show="errors.payer_msisdn" x-text="errors.payer_msisdn"></p>
                        <p x-show="methodNotLive" class="mt-3 px-3 py-2 rounded-lg bg-amber-50 border border-amber-300 text-amber-900 text-xs font-semibold">
                            <i class="fas fa-triangle-exclamation mr-1"></i><span x-text="currentMethod?.name"></span> isn't live yet, so no push will be sent. The booking is saved as <strong>awaiting payment</strong> with the space held — take the payment through another channel from the booking page.
                        </p>
                    </div>
                </div>
            </section>

            {{-- Navigation (desktop; phones use the bar pinned to the bottom) --}}
            <div x-show="step > 1" class="hidden lg:flex items-center justify-between gap-3">
                <button type="button" @click="goStep(step - 1)" class="ui-btn"><i class="fas fa-arrow-left text-xs"></i> Back</button>
                <button type="button" x-show="step < 4" @click="next()" :disabled="busy" class="ui-btn-primary">
                    <span x-show="!holding">Continue</span><span x-show="holding"><i class="fas fa-circle-notch fa-spin mr-1"></i> Holding space…</span>
                    <i x-show="!holding" class="fas fa-arrow-right text-xs"></i>
                </button>
                <button type="button" x-show="step === 4" @click="confirm()" :disabled="busy || !payment.method" class="ui-btn-success h-11">
                    <i class="fas" :class="submitting ? 'fa-circle-notch fa-spin' : 'fa-check-circle'"></i><span x-text="submitting ? 'Booking…' : confirmLabel"></span>
                </button>
            </div>
        </div>

        {{-- ═══ Order summary ═══ --}}
        <aside class="lg:sticky lg:top-20 ui-card overflow-hidden">
            <div class="ui-card-head">
                <h2 class="text-sm font-semibold text-slate-900">Order summary</h2>
                <span x-show="lockId" class="ui-badge" :class="secondsLeft <= 120 ? 'bg-amber-50 text-amber-900 border-amber-300' : 'bg-slate-50 text-slate-600 border-slate-200'"
                    :title="'Space is held for this booking until the timer runs out'"><i class="fas fa-clock"></i>Held <span class="font-mono" x-text="countdown"></span></span>
            </div>
            <div class="px-5 py-4 space-y-4 text-sm">
                <div>
                    <p class="ui-overline">Departure</p>
                    <template x-if="service">
                        <div>
                            <p class="font-semibold text-slate-900" x-text="service.route?.name"></p>
                            <p class="text-xs text-slate-600"><span x-text="dayLabel(String(service.departure_date).slice(0, 10), true)"></span> · <span class="font-mono" x-text="(service.departure_time || '').slice(0, 5)"></span> · <span x-text="service.transport_unit?.name"></span></p>
                        </div>
                    </template>
                    <p x-show="!service" class="text-slate-500">Pick a departure — you'll go straight to adding the cargo.</p>
                </div>

                <div x-show="service">
                    <p class="ui-overline">Cargo</p>
                    <ul class="mt-1 space-y-1">
                        <template x-for="line in lines.filter(l => typeOf(l))" :key="line.id">
                            <li class="flex justify-between gap-3">
                                <span class="text-slate-800 truncate" x-text="(unit(line) !== 'lane_meter' && line.quantity > 1 ? line.quantity + ' × ' : '') + typeOf(line).name + (line.vehicle_reg_number ? ' (' + line.vehicle_reg_number.toUpperCase() + ')' : '')"></span>
                                <span class="tabular-nums text-slate-900 whitespace-nowrap" x-text="lineQuote(line) ? CargoUI.num(lineQuote(line).base_fare + lineQuote(line).surcharge_amount) : (quoting ? '…' : '—')"></span>
                            </li>
                        </template>
                        <li x-show="quote?.totals?.value_charge > 0" class="flex justify-between gap-3 text-slate-600">
                            <span>Value charge</span><span class="tabular-nums" x-text="CargoUI.num(quote?.totals?.value_charge)"></span>
                        </li>
                    </ul>
                    <p x-show="!lines.some(l => typeOf(l))" class="text-slate-500">No items yet</p>
                </div>

                <div x-show="quote" class="border-t border-slate-200 pt-3 space-y-1">
                    <div class="flex justify-between text-emerald-700" x-show="quote?.total_discount > 0"><span>Discount</span><span class="tabular-nums" x-text="'−' + CargoUI.num(quote?.total_discount)"></span></div>
                    <div class="flex justify-between items-baseline">
                        <span class="font-semibold text-slate-900">Total</span>
                        <span class="text-2xl font-bold text-slate-900 tabular-nums" x-text="'TZS ' + CargoUI.num(quote?.net_amount)"></span>
                    </div>
                    <p class="text-xs text-slate-500" x-text="totalsText"></p>
                </div>
                <p x-show="quoting" class="text-xs text-slate-500"><i class="fas fa-circle-notch fa-spin mr-1"></i>Updating price…</p>
                <p x-show="!quoting && quoteMessage" class="text-xs text-red-700" x-text="quoteMessage"></p>
            </div>
        </aside>
    </div>

    {{-- Phones: total and the next action stay pinned to the bottom --}}
    <div x-show="!done && step > 1" class="lg:hidden fixed bottom-0 inset-x-0 z-30 bg-white border-t border-slate-200 px-4 py-3 flex items-center gap-3 shadow-[0_-4px_12px_rgba(15,23,42,0.06)]">
        <button type="button" @click="goStep(step - 1)" class="ui-btn h-11 w-11 px-0 shrink-0" aria-label="Back"><i class="fas fa-arrow-left"></i></button>
        <div class="min-w-0">
            <p class="text-[11px] text-slate-500 truncate" x-text="lockId ? `Total · held ${countdown}` : 'Total'"></p>
            <p class="text-lg font-bold text-slate-900 tabular-nums" x-text="quote ? 'TZS ' + CargoUI.num(quote.net_amount) : (quoting ? '…' : '—')"></p>
        </div>
        <button type="button" x-show="step < 4" @click="next()" :disabled="busy" class="ui-btn-primary h-11 ml-auto shrink-0">
            <span x-text="holding ? 'Holding…' : 'Continue'"></span><i class="fas fa-arrow-right text-xs"></i>
        </button>
        <button type="button" x-show="step === 4" @click="confirm()" :disabled="busy || !payment.method" class="ui-btn-success h-11 ml-auto shrink-0">
            <span x-text="submitting ? 'Booking…' : 'Book'"></span>
        </button>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    const { api, debounce, isoDate } = window.CargoUI;
    const API = '/admin/cargo/api/booking';
    const ROUTE_KEY = 'cargo.booking.route';

    Alpine.data('bookingTerminal', (cfg) => ({
        cfg,
        steps: ['Departure', 'Cargo', 'Sender & receiver', 'Review & pay'],
        step: 1,
        today: isoDate(),
        days: 3,
        otherDate: '',
        routeFilter: null,
        services: [],
        loadingServices: false,
        service: null,
        lines: [],
        nextId: 1,
        quote: null,
        quoting: false,
        quoteErrors: {},
        quoteMessage: '',
        quoteSeq: 0,
        sentIds: [],
        showValueError: false,
        lockId: null,
        lockExpires: null,
        lockHash: null,
        holding: false,
        secondsLeft: 0,
        people: {
            sender: { name: '', phone: '', id_type: '', id_number: '', showId: false },
            receiver: { name: '', phone: '', id_type: '', id_number: '', showId: false },
        },
        specialInstructions: '',
        suggestions: { sender: [], receiver: [] },
        discount: { on: false, amount: '', reason: '' },
        payment: { method: null, msisdn: '' },
        payerHint: { text: '', cls: '' },
        errors: {},
        banner: null,
        submitting: false,
        done: null,

        init() {
            this.payment.method = (cfg.paymentMethods.find(m => m.slug === 'cash') || cfg.paymentMethods[0])?.slug || null;
            try { this.routeFilter = JSON.parse(localStorage.getItem(ROUTE_KEY) || 'null'); } catch (e) { this.routeFilter = null; }
            this.addLine();
            this.loadServices();
            this.queueQuote = debounce(() => this.fetchQuote(), 400);
            this.lookupDebounced = debounce(role => this.fetchCustomers(role), 400);
            this.$watch('lines', () => this.queueQuote(), { deep: true });
            this.$watch('discount', () => this.queueQuote(), { deep: true });
            setInterval(() => this.tick(), 1000);
            // Free the held space if the clerk leaves mid-booking
            window.addEventListener('pagehide', () => {
                if (!this.lockId || this.done) return;
                const body = new FormData();
                body.append('_token', cfg.csrf);
                body.append('lock_id', this.lockId);
                navigator.sendBeacon(`${API}/release-lock`, body);
            });
        },

        get busy() { return this.holding || this.submitting || this.quoting; },
        // Only cargo types with a fare in force on the chosen departure can be booked
        get bookableTypes() {
            const priced = this.service?.priced_type_ids;
            return priced ? cfg.types.filter(t => priced.includes(t.id)) : cfg.types;
        },
        get typeGroups() {
            const groups = {};
            this.bookableTypes.forEach(t => { (groups[t.category] ??= []).push(t); });
            return Object.entries(groups).map(([name, types]) => ({ name: name.charAt(0).toUpperCase() + name.slice(1), types }));
        },
        get countdown() {
            const s = Math.max(0, this.secondsLeft);
            return `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`;
        },
        get currentMethod() { return cfg.paymentMethods.find(m => m.slug === this.payment.method); },
        get methodNotLive() { const m = this.currentMethod; return m?.flow === 'ussd_push' && !m.live && !m.simulated; },
        get confirmLabel() {
            if (this.methodNotLive) return 'Save booking — awaiting payment';
            const total = this.quote ? `TZS ${CargoUI.num(this.quote.net_amount)}` : '';
            return this.currentMethod?.flow === 'cash' ? `Book & take ${total} cash` : 'Book & request payment';
        },
        get totalsText() {
            const t = this.quote?.totals;
            if (!t) return '';
            return `${CargoUI.num(t.pieces)} piece${t.pieces === 1 ? '' : 's'} · ${CargoUI.num(t.weight_kg, 1)} kg`
                + (t.volume_cbm > 0 ? ` · ${CargoUI.num(t.volume_cbm, 2)} m³` : '') + (t.lane_meters > 0 ? ` · ${CargoUI.num(t.lane_meters, 1)} lane m` : '');
        },

        // ── Steps ───────────────────────────────────
        canVisit(n) { return n < this.step || (n === this.step); },
        goStep(n) {
            if (n > this.step) return;
            this.step = n;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        async next() {
            this.banner = null;
            if (this.step === 2) {
                this.showValueError = true;
                if (!(await this.holdSpace())) return;
            } else if (this.step === 3) {
                if (!this.validatePeople()) return;
                if (!this.payment.msisdn) this.payment.msisdn = this.people.sender.phone;
                this.onPayerInput();
            }
            this.step++;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        // ── Step 1: departures (next few days in one list) ──
        async loadServices() {
            this.loadingServices = true;
            const query = this.otherDate ? `date=${encodeURIComponent(this.otherDate)}` : `days=${this.days}`;
            try {
                this.services = await api(`${API}/available-services?${query}`);
            } catch (e) {
                this.services = [];
                this.banner = { type: 'error', text: `Couldn't load departures: ${e.message}` };
            } finally {
                this.loadingServices = false;
            }
        },
        get routeChoices() {
            const routes = new Map();
            this.services.forEach(s => s.route && routes.set(s.route_id, s.route.name));
            if (this.routeFilter && !routes.has(this.routeFilter.id)) routes.set(this.routeFilter.id, this.routeFilter.name);
            return [...routes].map(([id, name]) => ({ id, name })).sort((a, b) => a.name.localeCompare(b.name));
        },
        setRoute(r) {
            this.routeFilter = r ? { id: r.id, name: r.name } : null;
            try { r ? localStorage.setItem(ROUTE_KEY, JSON.stringify(this.routeFilter)) : localStorage.removeItem(ROUTE_KEY); } catch (e) { /* storage blocked */ }
        },
        get departureGroups() {
            const groups = new Map();
            if (!this.otherDate) groups.set(this.today, []);
            this.services
                .filter(s => !this.routeFilter || s.route_id === this.routeFilter.id)
                .forEach(s => {
                    const d = String(s.departure_date).slice(0, 10);
                    if (!groups.has(d)) groups.set(d, []);
                    groups.get(d).push(s);
                });
            return [...groups].sort(([a], [b]) => a.localeCompare(b)).map(([date, services]) => ({ date, label: this.dayLabel(date), services }));
        },
        get hasDepartures() { return this.departureGroups.some(g => g.services.length); },
        dayLabel(iso, short = false) {
            if (!iso) return '';
            const [y, m, d] = iso.split('-').map(Number);
            const day = new Date(y, m - 1, d);
            const [ty, tm, td] = this.today.split('-').map(Number);
            const diff = Math.round((day - new Date(ty, tm - 1, td)) / 864e5);
            const pretty = day.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' });
            if (diff === 0) return short ? 'Today' : `Today · ${pretty}`;
            if (diff === 1) return short ? 'Tomorrow' : `Tomorrow · ${pretty}`;
            return pretty;
        },
        spaceInfo(s) {
            const sp = s.space;
            if (!sp) return { pct: 0, text: 'No cargo space set', cls: 'text-amber-800', bar: 'bg-slate-300' };
            if (sp.full) return { pct: 100, text: 'Full', cls: 'text-slate-500', bar: 'bg-slate-400' };
            const unit = { weight_kg: 'kg', volume_cbm: 'm³', lane_meters: 'm of deck' }[sp.dimension];
            const free = `${CargoUI.num(sp.free, sp.free % 1 ? 1 : 0)} ${unit}`;
            if (sp.pct >= 85) return { pct: sp.pct, text: `${sp.pct}% full · only ${free} left`, cls: 'text-red-700 font-semibold', bar: 'bg-red-600' };
            return { pct: sp.pct, text: `${sp.pct}% full · ${free} free`, cls: 'text-slate-600', bar: 'bg-brand-500' };
        },
        async selectService(s) {
            if (s.space?.full || !s.priced_type_ids?.length) return;
            if (this.service?.id !== s.id && this.lockId) await this.releaseHold();
            this.service = s;
            // Lines with a type that has no fare on this departure are cleared
            const dropped = this.lines.filter(l => l.cargo_type_id && !s.priced_type_ids?.includes(Number(l.cargo_type_id)));
            if (dropped.length) {
                const names = [...new Set(dropped.map(l => this.typeOf(l)?.name).filter(Boolean))].join(', ');
                dropped.forEach(l => { l.cargo_type_id = ''; });
                window.notify?.(`${names} can't be booked on this departure (no fare on its route) — choose another type.`, 'error');
            }
            this.step = 2;
            this.queueQuote();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        // ── Step 2: cargo lines ─────────────────────
        addLine() {
            this.lines.push({ id: this.nextId++, cargo_type_id: '', quantity: 1, weight_kg: '', length_m: '', width_m: '', height_m: '',
                description: '', declared_value: '', is_fragile: false, vehicle_reg_number: '', driver_name: '', driver_phone: '', more: false });
        },
        removeLine(line) { this.lines = this.lines.filter(l => l.id !== line.id); },
        typeOf(line) { return cfg.types.find(t => String(t.id) === String(line.cargo_type_id)) || null; },
        unit(line) { return this.typeOf(line)?.measurement_unit; },
        unitLabel(line) { return { kg: 'priced by weight (kg)', cbm: 'priced by size (m³)', unit: 'priced per piece', lane_meter: 'priced per vehicle' }[this.unit(line)] || ''; },
        onTypeChange(line) {
            if (this.unit(line) === 'lane_meter') line.quantity = 1;
            if (this.typeOf(line)?.is_fragile) line.is_fragile = true;
        },
        // The fare charges a % of the declared value (known once the line is priced, or from its error)
        needsValue(line) { return !!(this.lineQuote(line)?.value_charge_pct > 0 || this.err(line, 'declared_value')); },
        moreLabel(line) {
            const extra = [];
            if (this.unit(line) !== 'kg') extra.push('weight');
            if (!this.needsValue(line)) extra.push('declared value');
            return extra.length ? `More: ${extra.join(', ')}` : '';
        },
        cbm(line) {
            const v = Number(line.length_m) * Number(line.width_m) * Number(line.height_m) * Math.max(1, Number(line.quantity) || 1);
            return Number.isFinite(v) ? v : 0;
        },
        pricingNote(q) {
            return { per_kg: `(${CargoUI.num(q.weight_kg, 1)} kg × ${CargoUI.num(q.unit_rate)})`, per_cbm: `(${CargoUI.num(q.volume_cbm, 3)} m³ × ${CargoUI.num(q.unit_rate)})`,
                     flat: q.quantity > 1 ? `(${q.quantity} × ${CargoUI.num(q.unit_rate)})` : '', minimum: '(minimum charge)' }[q.pricing_method] || '';
        },
        itemsPayload() {
            const typed = this.lines.filter(l => this.typeOf(l));
            const num = v => (v === '' || v === null || v === undefined || Number.isNaN(Number(v))) ? null : Number(v);
            return {
                ids: typed.map(l => l.id),
                items: typed.map(l => {
                    const u = this.unit(l);
                    const item = { cargo_type_id: Number(l.cargo_type_id), quantity: u === 'lane_meter' ? 1 : Math.max(1, parseInt(l.quantity, 10) || 1),
                        weight_kg: num(l.weight_kg), is_fragile: !!l.is_fragile, description: l.description || null, declared_value: num(l.declared_value) };
                    if (u === 'cbm') Object.assign(item, { length_m: num(l.length_m), width_m: num(l.width_m), height_m: num(l.height_m) });
                    if (u === 'lane_meter') Object.assign(item, { vehicle_reg_number: l.vehicle_reg_number || null, driver_name: l.driver_name || null, driver_phone: l.driver_phone || null });
                    return item;
                }),
            };
        },
        capacityHash(items) {
            return JSON.stringify(items.map(i => [i.cargo_type_id, i.quantity, i.weight_kg, i.length_m, i.width_m, i.height_m]));
        },
        // The live quote only applies the discount once a reason is given (the server requires one)
        discountPayload(forQuote = false) {
            if (!this.discount.on || !(Number(this.discount.amount) > 0)) return {};
            if (forQuote && this.discount.reason.trim().length < 5) return {};
            return { discount_amount: Number(this.discount.amount), discount_reason: this.discount.reason };
        },
        get discountPending() {
            return this.discount.on && Number(this.discount.amount) > 0 && this.discount.reason.trim().length < 5;
        },
        lineQuote(line) {
            const i = this.sentIds.indexOf(line.id);
            return i >= 0 ? (this.quote?.items?.[i] || null) : null;
        },
        err(line, field) {
            const i = this.sentIds.indexOf(line.id);
            return i >= 0 ? (this.quoteErrors[`items.${i}.${field}`]?.[0] || null) : null;
        },
        lineHasError(line) {
            const i = this.sentIds.indexOf(line.id);
            // A missing declared value is a prompt, not a mistake, until the clerk tries to continue
            return i >= 0 && Object.keys(this.quoteErrors).some(k => k.startsWith(`items.${i}.`) && (this.showValueError || !k.endsWith('.declared_value')));
        },
        async fetchQuote() {
            const { ids, items } = this.itemsPayload();
            if (!this.service || !items.length) { this.quote = null; this.sentIds = []; this.quoteErrors = {}; this.quoteMessage = ''; return; }
            const seq = ++this.quoteSeq;
            this.quoting = true;
            try {
                const q = await api(`${API}/quote`, { method: 'POST', body: { transport_service_id: this.service.id, items, ...this.discountPayload(true) } });
                if (seq !== this.quoteSeq) return;
                this.quote = q; this.sentIds = ids; this.quoteErrors = {}; this.quoteMessage = '';
            } catch (e) {
                if (seq !== this.quoteSeq) return;
                this.quote = null; this.sentIds = ids; this.quoteErrors = e.errors || {};
                const keys = Object.keys(this.quoteErrors);
                this.quoteMessage = !keys.length ? e.message
                    : keys.every(k => k.endsWith('.declared_value')) ? 'Enter the declared value to see the price.' : 'Fix the highlighted fields to see the price.';
            } finally {
                if (seq === this.quoteSeq) this.quoting = false;
            }
        },

        // ── Space hold ──────────────────────────────
        async holdSpace() {
            await this.fetchQuote();
            if (!this.quote) {
                this.banner = { type: 'error', text: this.lines.some(l => this.typeOf(l)) ? 'Fix the highlighted fields before continuing.' : 'Add at least one item.' };
                return false;
            }
            const { items } = this.itemsPayload();
            const hash = this.capacityHash(items);
            if (this.lockId && this.lockHash === hash && this.secondsLeft > 15) return true;

            this.holding = true;
            try {
                const res = await api(`${API}/lock-inventory`, { method: 'POST', body: { transport_service_id: this.service.id, items, replace_lock_id: this.lockId } });
                this.lockId = res.lock_id;
                this.lockExpires = new Date(res.expires_at).getTime();
                this.lockHash = hash;
                this.tick();
                return true;
            } catch (e) {
                this.lockId = null;
                this.quoteErrors = e.errors || {};
                this.banner = { type: 'error', text: e.message };
                return false;
            } finally {
                this.holding = false;
            }
        },
        async releaseHold() {
            const id = this.lockId;
            this.lockId = null; this.lockHash = null;
            if (id) { try { await api(`${API}/release-lock`, { method: 'POST', body: { lock_id: id } }); } catch (e) { /* expired already */ } }
        },
        tick() {
            if (!this.lockId) return;
            this.secondsLeft = Math.round((this.lockExpires - Date.now()) / 1000);
            if (this.secondsLeft <= 0) {
                this.lockId = null; this.lockHash = null;
                if (this.step > 2) this.step = 2;
                this.banner = { type: 'warning', text: 'The space hold expired. Your items are kept — press Continue to hold space again.' };
            }
        },

        // ── Step 3: people ──────────────────────────
        normalisePhone(v) {
            const raw = String(v || '').trim(), d = raw.replace(/\D/g, '');
            const local = d.startsWith('255') && d.length === 12 ? d.slice(3) : d.startsWith('0') && d.length === 10 ? d.slice(1) : d.length === 9 ? d : null;
            if (local && /^[67]\d{8}$/.test(local)) return '0' + local;
            return raw.startsWith('+') && d.length >= 8 && d.length <= 15 ? '+' + d : null;
        },
        validatePeople() {
            const e = {};
            ['sender', 'receiver'].forEach(role => {
                const p = this.people[role];
                if (!p.name.trim()) e[`${role}_name`] = 'Enter the full name.';
                const phone = this.normalisePhone(p.phone);
                if (!phone) e[`${role}_phone`] = 'Enter a valid mobile number, e.g. 0754 123 456 or +254 712 345 678.';
                else p.phone = phone;
            });
            this.errors = e;
            if (Object.keys(e).length) this.banner = { type: 'error', text: 'Check the sender and receiver details.' };
            return !Object.keys(e).length;
        },
        sameAsSender() {
            const s = this.people.sender;
            Object.assign(this.people.receiver, { name: s.name, phone: s.phone, id_type: s.id_type, id_number: s.id_number, showId: s.showId });
            this.suggestions.receiver = [];
        },
        lookupCustomer(role) {
            this.suggestions[role] = [];
            if (String(this.people[role].phone).replace(/\D/g, '').length >= 9) this.lookupDebounced(role);
        },
        async fetchCustomers(role) {
            try {
                const list = await api(`${API}/customers?phone=${encodeURIComponent(this.people[role].phone)}`);
                const current = this.people[role].name.trim().toLowerCase();
                this.suggestions[role] = list.filter(p => p.name.trim().toLowerCase() !== current);
            } catch (e) { this.suggestions[role] = []; }
        },
        useCustomer(role, p) {
            Object.assign(this.people[role], { name: p.name, phone: p.phone || this.people[role].phone });
            if (p.id_number) Object.assign(this.people[role], { id_type: p.id_type || '', id_number: p.id_number, showId: true });
            this.suggestions[role] = [];
        },

        // ── Step 4: payment ─────────────────────────
        selectPayment(slug, fromDetection = false) {
            this.payment.method = slug;
            if (!fromDetection) this.onPayerInput();
        },
        onPayerInput() {
            const value = this.payment.msisdn;
            const m = this.currentMethod;
            if (!value || m?.flow !== 'ussd_push') { this.payerHint = { text: '', cls: '' }; return; }
            const n = this.normalisePhone(value);
            if (!n || n.startsWith('+')) { this.payerHint = { text: 'Enter a valid Tanzanian mobile number.', cls: 'text-red-700' }; return; }
            const prefix = n.slice(1, 3);
            const network = Object.keys(cfg.networkPrefixes).find(d => cfg.networkPrefixes[d].includes(prefix));
            if (!network) { this.payerHint = { text: 'Network not recognised — use Selcom Pesa or Lipa Namba.', cls: 'text-amber-800' }; return; }
            const match = cfg.paymentMethods.find(x => x.flow === 'ussd_push' && x.driver === network);
            if (m.prefixes?.length && m.driver !== network && match) {
                this.selectPayment(match.slug, true);
                this.payerHint = { text: `<i class="fas fa-wand-magic-sparkles mr-1"></i>${cfg.networkLabels[network]} number — switched to ${CargoUI.esc(match.name)}.`, cls: 'text-brand-800 font-semibold' };
                return;
            }
            const ok = !m.prefixes?.length || m.driver === network;
            this.payerHint = ok
                ? { text: `<i class="fas fa-circle-check mr-1"></i>${cfg.networkLabels[network]} number`, cls: 'text-emerald-700 font-semibold' }
                : { text: `${cfg.networkLabels[network]} number — ${CargoUI.esc(m.name)} can't push to it.`, cls: 'text-red-700' };
        },

        async confirm() {
            this.banner = null; this.errors = {};
            if (!this.lockId) { this.step = 2; this.banner = { type: 'warning', text: 'The space hold expired. Press Continue to hold space again.' }; return; }
            if (this.discount.on && Number(this.discount.amount) > 0 && this.discount.reason.trim().length < 5) {
                this.errors.discount_reason = 'Give a reason for the discount (at least 5 characters).';
                return;
            }
            const { ids, items } = this.itemsPayload();
            const s = this.people.sender, r = this.people.receiver;
            this.submitting = true;
            try {
                const res = await api(`${API}/confirm`, { method: 'POST', body: {
                    lock_id: this.lockId, transport_service_id: this.service.id, items, ...this.discountPayload(),
                    sender_name: s.name.trim(), sender_phone: s.phone, sender_id_type: s.id_type || null, sender_id_number: s.id_number || null,
                    receiver_name: r.name.trim(), receiver_phone: r.phone, receiver_id_type: r.id_type || null, receiver_id_number: r.id_number || null,
                    special_instructions: this.specialInstructions || null,
                    payment_method: this.payment.method, payer_msisdn: this.currentMethod?.flow === 'ussd_push' ? this.payment.msisdn : null,
                } });
                this.lockId = null;
                if (res.payment?.status === 'paid') {
                    this.done = res;
                } else {
                    this.banner = { type: 'success', text: `${res.consignment.consignment_number} is booked — opening it to take the payment…` };
                    setTimeout(() => { location.href = `/admin/cargo/bookings/${res.consignment.id}`; }, 1200);
                }
            } catch (e) {
                const keys = Object.keys(e.errors || {});
                if (keys.some(k => k.startsWith('items.'))) { this.sentIds = ids; this.quoteErrors = e.errors; this.step = 2; }
                else if (keys.some(k => /^(sender|receiver)_/.test(k))) { this.errors = Object.fromEntries(keys.map(k => [k, e.errors[k][0]])); this.step = 3; }
                else { this.errors = Object.fromEntries(keys.map(k => [k, e.errors[k][0]])); }
                if (/hold|expired/i.test(e.message)) { this.lockId = null; this.step = 2; }
                this.banner = { type: 'error', text: e.message };
            } finally {
                this.submitting = false;
            }
        },

        startNew(sameSender) {
            const sender = sameSender ? { ...this.people.sender } : { name: '', phone: '', id_type: '', id_number: '', showId: false };
            Object.assign(this, {
                done: null, step: 1, service: null, quote: null, sentIds: [], quoteErrors: {}, lines: [], banner: null, errors: {}, showValueError: false,
                specialInstructions: '', discount: { on: false, amount: '', reason: '' }, suggestions: { sender: [], receiver: [] },
                people: { sender, receiver: { name: '', phone: '', id_type: '', id_number: '', showId: false } },
                otherDate: '', days: 3,
            });
            this.payment.msisdn = '';
            this.addLine();
            this.loadServices();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    }));
});
</script>
@endpush
</x-layouts.admin>
