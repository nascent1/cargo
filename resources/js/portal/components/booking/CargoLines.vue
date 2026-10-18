<template>
  <section class="space-y-3">
    <div v-for="(line, idx) in cargo.lines" :key="line.id" class="ui-card p-5 space-y-4" :class="cargo.lineHasError(line) && 'border-red-300'">
      <div class="flex items-center justify-between gap-3">
        <h3 class="text-sm font-semibold text-slate-900">Item {{ idx + 1 }}
          <span v-if="cargo.typeOf(line)" class="font-normal text-slate-500">· {{ unitLabel(line) }}</span></h3>
        <div class="flex items-center gap-3">
          <span v-if="cargo.lineQuote(line)" class="text-sm font-bold text-slate-900 tabular-nums whitespace-nowrap">TZS {{ num(cargo.lineQuote(line).final_fare) }}</span>
          <button v-if="cargo.lines.length > 1" type="button" class="h-8 w-8 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-700" :aria-label="`Remove item ${idx + 1}`" @click="cargo.removeLine(line)">
            <i class="fas fa-trash-can text-xs"></i>
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="ui-label" :for="`type-${line.id}`">Cargo type <span class="text-red-600">*</span></label>
          <select :id="`type-${line.id}`" v-model="line.cargo_type_id" class="ui-field" :class="cargo.err(line, 'cargo_type_id') && 'ui-field-invalid'" @change="cargo.onTypeChange(line)">
            <option value="">Select cargo type…</option>
            <optgroup v-for="group in cargo.typeGroups" :key="group.name" :label="group.name">
              <option v-for="t in group.types" :key="t.id" :value="String(t.id)">{{ t.name }}</option>
            </optgroup>
          </select>
          <p v-if="cargo.err(line, 'cargo_type_id')" class="ui-error">{{ cargo.err(line, 'cargo_type_id') }}</p>
        </div>
        <div>
          <label class="ui-label" :for="`desc-${line.id}`">What's inside <span class="font-normal text-slate-400">(optional)</span></label>
          <input :id="`desc-${line.id}`" v-model="line.description" type="text" maxlength="255" placeholder="e.g. 20 bags of cement" class="ui-field">
        </div>
      </div>

      <div v-if="cargo.typeOf(line)" class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <!-- Pieces (not for vehicles: one vehicle per line) -->
        <div v-if="cargo.unit(line) !== 'lane_meter'">
          <label class="ui-label" :for="`qty-${line.id}`">Pieces <span class="text-red-600">*</span></label>
          <input :id="`qty-${line.id}`" v-model.number="line.quantity" type="number" min="1" step="1" inputmode="numeric" class="ui-field" :class="cargo.err(line, 'quantity') && 'ui-field-invalid'">
          <p v-if="cargo.err(line, 'quantity')" class="ui-error">{{ cargo.err(line, 'quantity') }}</p>
        </div>
        <!-- Vehicle details -->
        <div v-if="cargo.unit(line) === 'lane_meter'" class="col-span-2 md:col-span-4 grid grid-cols-1 md:grid-cols-3 gap-3">
          <div>
            <label class="ui-label" :for="`reg-${line.id}`">Registration no. <span class="text-red-600">*</span></label>
            <input :id="`reg-${line.id}`" v-model="line.vehicle_reg_number" type="text" maxlength="30" placeholder="T 123 ABC" class="ui-field uppercase font-mono" :class="cargo.err(line, 'vehicle_reg_number') && 'ui-field-invalid'">
            <p v-if="cargo.err(line, 'vehicle_reg_number')" class="ui-error">{{ cargo.err(line, 'vehicle_reg_number') }}</p>
          </div>
          <div>
            <label class="ui-label" :for="`drv-${line.id}`">Driver name</label>
            <input :id="`drv-${line.id}`" v-model="line.driver_name" type="text" maxlength="100" class="ui-field">
          </div>
          <div>
            <label class="ui-label" :for="`drvp-${line.id}`">Driver phone</label>
            <input :id="`drvp-${line.id}`" v-model="line.driver_phone" type="tel" maxlength="20" placeholder="0754 123 456" class="ui-field font-mono" :class="cargo.err(line, 'driver_phone') && 'ui-field-invalid'">
            <p v-if="cargo.err(line, 'driver_phone')" class="ui-error">{{ cargo.err(line, 'driver_phone') }}</p>
          </div>
        </div>
        <!-- Weight: required for per-kg types, otherwise under "More" -->
        <div v-if="cargo.unit(line) === 'kg' || line.more || cargo.err(line, 'weight_kg')">
          <label class="ui-label" :for="`w-${line.id}`">{{ cargo.unit(line) === 'kg' ? 'Total weight (kg)' : 'Weight (kg)' }} <span v-if="cargo.unit(line) === 'kg'" class="text-red-600">*</span></label>
          <input :id="`w-${line.id}`" v-model.number="line.weight_kg" type="number" min="0" step="0.1" inputmode="decimal" class="ui-field" :class="cargo.err(line, 'weight_kg') && 'ui-field-invalid'">
          <p v-if="cargo.err(line, 'weight_kg')" class="ui-error">{{ cargo.err(line, 'weight_kg') }}</p>
          <p v-else-if="Number(cargo.typeOf(line)?.max_weight_kg) > 0" class="ui-hint">Up to {{ num(cargo.typeOf(line).max_weight_kg) }} kg per {{ cargo.unit(line) === 'lane_meter' ? 'vehicle' : 'piece' }}</p>
        </div>
        <!-- Size of one piece for volume-priced types -->
        <div v-if="cargo.unit(line) === 'cbm'" class="col-span-2 md:col-span-3">
          <label class="ui-label">Size of one piece (m) — length × width × height <span class="text-red-600">*</span></label>
          <div class="grid grid-cols-3 gap-2">
            <input v-model.number="line.length_m" type="number" min="0" step="0.01" inputmode="decimal" placeholder="Length" aria-label="Length in metres" class="ui-field" :class="cargo.err(line, 'volume_cbm') && 'ui-field-invalid'">
            <input v-model.number="line.width_m" type="number" min="0" step="0.01" inputmode="decimal" placeholder="Width" aria-label="Width in metres" class="ui-field" :class="cargo.err(line, 'volume_cbm') && 'ui-field-invalid'">
            <input v-model.number="line.height_m" type="number" min="0" step="0.01" inputmode="decimal" placeholder="Height" aria-label="Height in metres" class="ui-field" :class="cargo.err(line, 'volume_cbm') && 'ui-field-invalid'">
          </div>
          <p v-if="cargo.err(line, 'volume_cbm')" class="ui-error">{{ cargo.err(line, 'volume_cbm') }}</p>
          <p v-else class="ui-hint">Total: <span class="font-semibold text-slate-700 tabular-nums">{{ cbm(line).toFixed(3) }} m³</span></p>
        </div>
      </div>

      <!-- Declared value: required when the fare charges a % of it, otherwise under "More" -->
      <div v-if="cargo.typeOf(line) && (cargo.needsValue(line) || line.more)" class="grid grid-cols-1 md:grid-cols-2 gap-3 items-start">
        <div>
          <label class="ui-label" :for="`val-${line.id}`">Declared value (TZS) <span v-if="cargo.needsValue(line)" class="text-red-600">*</span></label>
          <input :id="`val-${line.id}`" v-model.number="line.declared_value" type="number" min="0" step="1000" inputmode="numeric" class="ui-field"
            :class="cargo.err(line, 'declared_value') && cargo.showValueError && 'ui-field-invalid'">
          <p v-if="cargo.err(line, 'declared_value')" class="text-xs mt-1" :class="cargo.showValueError ? 'text-red-700' : 'text-amber-800'">{{ cargo.err(line, 'declared_value') }}</p>
        </div>
        <p v-if="cargo.lineQuote(line)?.value_charge_pct > 0" class="text-xs text-slate-600 md:pt-7">
          <i class="fas fa-shield-halved text-brand-600 mr-1"></i>Value charge {{ pct(cargo.lineQuote(line).value_charge_pct) }}% =
          <b class="text-slate-900">TZS {{ num(cargo.lineQuote(line).value_charge) }}</b>
        </p>
      </div>

      <div v-if="cargo.typeOf(line)" class="flex flex-wrap items-center gap-x-5 gap-y-2">
        <label class="inline-flex items-center gap-2 text-sm text-slate-800 cursor-pointer">
          <input v-model="line.is_fragile" type="checkbox" :disabled="cargo.typeOf(line)?.is_fragile" class="h-4 w-4 rounded border-slate-400 accent-brand-600">
          Fragile <span class="text-xs text-slate-500">{{ cargo.typeOf(line)?.is_fragile ? '(always, for this type)' : '(handling surcharge)' }}</span>
        </label>
        <span v-if="cargo.typeOf(line)?.requires_hazmat_declaration" class="text-xs font-semibold text-red-700"><i class="fas fa-biohazard mr-1"></i>Hazardous — check the sender's declaration</span>
        <button v-if="moreLabel(line)" type="button" class="ui-link text-xs ml-auto" @click="line.more = !line.more">
          <i class="fas mr-1" :class="line.more ? 'fa-minus' : 'fa-plus'"></i>{{ line.more ? 'Fewer details' : moreLabel(line) }}
        </button>
      </div>

      <div v-if="cargo.lineQuote(line)" class="pt-3 border-t border-slate-100 text-xs text-slate-600 flex flex-wrap gap-x-4 gap-y-1">
        <span>Base <span class="font-semibold tabular-nums text-slate-800">{{ num(cargo.lineQuote(line).base_fare) }}</span> <span class="text-slate-500">{{ pricingNote(cargo.lineQuote(line)) }}</span></span>
        <span v-for="s in cargo.lineQuote(line).surcharges" :key="s.label">{{ s.label }}: {{ num(s.amount) }}</span>
        <span v-if="cargo.lineQuote(line).value_charge > 0">Value charge: {{ num(cargo.lineQuote(line).value_charge) }}</span>
        <span v-if="cargo.lineQuote(line).discount_amount > 0" class="text-emerald-700">Discount −{{ num(cargo.lineQuote(line).discount_amount) }}</span>
      </div>
      <p v-if="cargo.lineQuote(line)?.warning" class="text-xs font-semibold text-red-700"><i class="fas fa-circle-exclamation mr-1"></i>{{ cargo.lineQuote(line).warning }}</p>
    </div>

    <button type="button" class="w-full h-12 rounded-2xl border-2 border-dashed border-slate-300 hover:border-brand-400 hover:bg-brand-50/40 text-sm font-semibold text-slate-700" @click="cargo.addLine()">
      <i class="fas fa-plus mr-1"></i> Add another item
    </button>
  </section>
</template>

<script setup>
import { useCargoStore } from '../../stores/cargo';
import { num, pct } from '../../utils/cargoUi';

// Step 2: what is being shipped. Fields follow the cargo type; each line is priced as you type.
const cargo = useCargoStore();

const unitLabel = line => ({ kg: 'priced by weight (kg)', cbm: 'priced by size (m³)', unit: 'priced per piece', lane_meter: 'priced per vehicle' }[cargo.unit(line)] || '');

const cbm = line => {
  const v = Number(line.length_m) * Number(line.width_m) * Number(line.height_m) * Math.max(1, Number(line.quantity) || 1);
  return Number.isFinite(v) ? v : 0;
};

const pricingNote = q => ({
  per_kg: `(${num(q.weight_kg, 1)} kg × ${num(q.unit_rate)})`,
  per_cbm: `(${num(q.volume_cbm, 3)} m³ × ${num(q.unit_rate)})`,
  flat: q.quantity > 1 ? `(${q.quantity} × ${num(q.unit_rate)})` : '',
  minimum: '(minimum charge)',
}[q.pricing_method] || '');

const moreLabel = line => {
  const extra = [];
  if (cargo.unit(line) !== 'kg') extra.push('weight');
  if (!cargo.needsValue(line)) extra.push('declared value');
  return extra.length ? `More: ${extra.join(', ')}` : '';
};
</script>
