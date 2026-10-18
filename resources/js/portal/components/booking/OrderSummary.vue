<template>
  <aside class="lg:sticky lg:top-20 ui-card overflow-hidden">
    <div class="ui-card-head">
      <h2 class="text-sm font-semibold text-slate-900">Order summary</h2>
      <span v-if="cargo.lockId" class="ui-badge" :class="cargo.secondsLeft <= 120 ? 'bg-amber-50 text-amber-900 border-amber-300' : 'bg-slate-50 text-slate-600 border-slate-200'"
        title="Space is held for this booking until the timer runs out"><i class="fas fa-clock"></i>Held <span class="font-mono">{{ cargo.countdown }}</span></span>
    </div>
    <div class="px-5 py-4 space-y-4 text-sm">
      <div>
        <p class="ui-overline">Departure</p>
        <div v-if="cargo.service">
          <p class="font-semibold text-slate-900">{{ cargo.service.route?.name }}</p>
          <p class="text-xs text-slate-600">{{ dayLabel(cargo.service.departure_date, true) }} · <span class="font-mono">{{ (cargo.service.departure_time || '').slice(0, 5) }}</span> · {{ cargo.service.transport_unit?.name }}</p>
        </div>
        <p v-else class="text-slate-500">Pick a departure — you'll go straight to adding the cargo.</p>
      </div>

      <div v-if="cargo.service">
        <p class="ui-overline">Cargo</p>
        <ul class="mt-1 space-y-1">
          <li v-for="line in typedLines" :key="line.id" class="flex justify-between gap-3">
            <span class="text-slate-800 truncate">{{ lineName(line) }}</span>
            <span class="tabular-nums text-slate-900 whitespace-nowrap">{{ linePrice(line) }}</span>
          </li>
          <li v-if="cargo.quote?.totals?.value_charge > 0" class="flex justify-between gap-3 text-slate-600">
            <span>Value charge</span><span class="tabular-nums">{{ num(cargo.quote.totals.value_charge) }}</span>
          </li>
        </ul>
        <p v-if="!typedLines.length" class="text-slate-500">No items yet</p>
      </div>

      <div v-if="cargo.quote" class="border-t border-slate-200 pt-3 space-y-1">
        <div v-if="cargo.quote.total_discount > 0" class="flex justify-between text-emerald-700"><span>Discount</span><span class="tabular-nums">−{{ num(cargo.quote.total_discount) }}</span></div>
        <div class="flex justify-between items-baseline">
          <span class="font-semibold text-slate-900">Total</span>
          <span class="text-2xl font-bold text-slate-900 tabular-nums">TZS {{ num(cargo.quote.net_amount) }}</span>
        </div>
        <p class="text-xs text-slate-500">{{ cargo.totalsText }}</p>
      </div>
      <p v-if="cargo.quoting" class="text-xs text-slate-500"><i class="fas fa-circle-notch fa-spin mr-1"></i>Updating price…</p>
      <p v-else-if="cargo.quoteMessage" class="text-xs text-red-700">{{ cargo.quoteMessage }}</p>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue';
import { useCargoStore } from '../../stores/cargo';
import { num, dayLabel } from '../../utils/cargoUi';

const cargo = useCargoStore();
const typedLines = computed(() => cargo.lines.filter(l => cargo.typeOf(l)));

const lineName = line => (cargo.unit(line) !== 'lane_meter' && line.quantity > 1 ? `${line.quantity} × ` : '')
  + cargo.typeOf(line).name + (line.vehicle_reg_number ? ` (${line.vehicle_reg_number.toUpperCase()})` : '');

// Base + handling; the value charge is listed on its own line
const linePrice = line => {
  const q = cargo.lineQuote(line);
  return q ? num(q.base_fare + q.surcharge_amount) : (cargo.quoting ? '…' : '—');
};
</script>
