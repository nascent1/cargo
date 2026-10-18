<template>
  <div class="fixed inset-0 z-[1100] flex items-center justify-center bg-slate-900/40 p-4" @click.self="$emit('close')">
    <div class="ui-card w-full max-w-lg shadow-xl max-h-[90vh] overflow-y-auto shift-sheet" role="dialog" aria-labelledby="shift-sheet-title">
      <div class="ui-card-head">
        <h2 id="shift-sheet-title" class="font-semibold text-slate-900"><i class="fas fa-receipt mr-1 text-brand-600"></i>Shift summary</h2>
        <span class="text-xs text-slate-500">{{ brand.name }}</span>
      </div>

      <div class="p-5 space-y-4 text-sm">
        <div class="grid grid-cols-2 gap-3">
          <div><p class="ui-label">Clerk</p><p class="font-semibold">{{ clerk }}</p></div>
          <div><p class="ui-label">Shift</p><p class="font-semibold">{{ dateTime(summary.from) }} – {{ endText }}</p></div>
        </div>

        <div class="grid grid-cols-3 gap-3">
          <div class="rounded-xl bg-slate-50 p-3"><p class="ui-label">Bookings</p><p class="text-lg font-bold tabular-nums">{{ summary.bookings }}</p></div>
          <div class="rounded-xl bg-slate-50 p-3"><p class="ui-label">Items</p><p class="text-lg font-bold tabular-nums">{{ summary.items }}</p></div>
          <div class="rounded-xl bg-slate-50 p-3"><p class="ui-label">Weight</p><p class="text-lg font-bold tabular-nums">{{ num(summary.weight_kg) }} kg</p></div>
        </div>

        <div>
          <p class="ui-label mb-1">Payments taken</p>
          <p v-if="!summary.collections?.length" class="text-slate-500">None.</p>
          <table v-else class="w-full">
            <tbody>
              <tr v-for="c in summary.collections" :key="c.method + c.currency" class="border-t border-slate-100">
                <td class="py-1.5">{{ c.method }} <span v-if="c.flow === 'cash'" class="text-xs text-emerald-700">(cash)</span></td>
                <td class="py-1.5 text-right text-slate-500 tabular-nums">{{ c.count }}×</td>
                <td class="py-1.5 text-right font-semibold tabular-nums">{{ money(c.total, c.currency) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="border-t border-slate-300"><td class="py-1.5 font-semibold">Total</td><td></td><td class="py-1.5 text-right font-bold tabular-nums">{{ totals(summary.collected) }}</td></tr>
            </tfoot>
          </table>
        </div>

        <div class="rounded-xl border-2 border-emerald-200 bg-emerald-50 p-4 flex items-center justify-between">
          <span class="font-semibold text-emerald-900">Cash to hand over</span>
          <span class="text-xl font-bold text-emerald-800 tabular-nums">{{ totals(summary.cash_to_hand_over) }}</span>
        </div>

        <p class="text-xs text-slate-600">
          Still unpaid: <strong>{{ summary.unpaid?.count || 0 }}</strong> ({{ totals(summary.unpaid?.value) }}) · Cancelled: <strong>{{ summary.cancelled || 0 }}</strong>
        </p>

        <div class="hidden print-only gap-6 pt-10 text-xs">
          <p class="border-t border-slate-400 pt-1">Handed over by</p>
          <p class="border-t border-slate-400 pt-1">Received by</p>
        </div>
      </div>

      <div class="px-5 pb-5 flex gap-2 justify-end no-print">
        <button type="button" class="ui-btn" @click="$emit('close')">Close</button>
        <button type="button" class="ui-btn-primary" @click="print"><i class="fas fa-print"></i> Print</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { num, money, dateTime, brand } from '../../utils/cargoUi';

const props = defineProps({
  summary: { type: Object, required: true },
  clerk: { type: String, default: '' },
});
defineEmits(['close']);

const endText = computed(() => new Date(props.summary.to).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' }));
const totals = byCurrency => {
  const parts = Object.entries(byCurrency || {}).map(([c, v]) => money(v, c));
  return parts.length ? parts.join(' + ') : money(0, 'TZS');
};

function print() {
  document.body.classList.add('printing-shift');
  window.print();
  document.body.classList.remove('printing-shift');
}
</script>

<style>
@media print {
  body.printing-shift * { visibility: hidden; }
  body.printing-shift .shift-sheet, body.printing-shift .shift-sheet * { visibility: visible; }
  body.printing-shift .shift-sheet { position: absolute; inset: 0; max-height: none; box-shadow: none; border: 0; }
  body.printing-shift .shift-sheet .no-print { display: none; }
  body.printing-shift .shift-sheet .print-only { display: grid !important; grid-template-columns: 1fr 1fr; }
}
</style>
