<template>
  <div v-if="items.length" class="bg-white/90 border border-slate-200/60 rounded-2xl shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200/40">
      <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
        <i class="fas fa-history text-brand-600 text-xs"></i>
        Today's Activity
      </h3>
      <span class="text-[10px] font-mono text-slate-400">{{ items.length }} ticket{{ items.length !== 1 ? 's' : '' }}</span>
    </div>

    <div class="max-h-[300px] overflow-y-auto">
      <table class="w-full text-left">
        <thead class="sticky top-0 bg-white z-10">
          <tr class="text-[9px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200/40">
            <th class="px-5 py-2">Time</th>
            <th class="px-5 py-2">Ticket</th>
            <th class="px-5 py-2">Passenger</th>
            <th class="px-5 py-2">Class</th>
            <th class="px-5 py-2">Fare</th>
            <th class="px-5 py-2 text-right">Reprint</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="(item, index) in items" :key="index"
              class="hover:bg-slate-50/60 transition-colors text-xs"
              :class="item.status === 'cancelled' ? 'opacity-50' : ''">
            <td class="px-5 py-2.5 font-mono text-slate-500">{{ item.time }}</td>
            <td class="px-5 py-2.5">
              <span class="font-mono font-semibold text-slate-700">{{ shortToken(item.ticket_number) }}</span>
              <span v-if="item.status === 'cancelled'"
                    class="ml-1.5 px-1.5 py-0.5 rounded bg-rose-500/10 text-rose-500 text-[8px] font-bold uppercase">
                Cancelled
              </span>
            </td>
            <td class="px-5 py-2.5 text-slate-600 uppercase text-[11px]">{{ item.passenger }}</td>
            <td class="px-5 py-2.5">
              <span class="px-2 py-0.5 rounded-md bg-slate-100/80 text-brand-600 font-medium border border-slate-200/40 text-[10px]">
                {{ item.class }}
              </span>
            </td>
            <td class="px-5 py-2.5 font-bold font-mono text-emerald-600">{{ formatCurrency(item.fare) }}</td>
            <td class="px-5 py-2.5 text-right">
              <button v-if="item.can_reprint"
                      @click="$emit('reprint', item)"
                      class="px-2.5 py-1 rounded-lg bg-slate-100/80 hover:bg-slate-200/80 text-slate-500 hover:text-slate-800 font-medium transition-all border border-slate-200/40 text-[10px]">
                <i class="fas fa-print mr-1"></i> Print
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
defineProps({
  items: { type: Array, default: () => [] },
});

defineEmits(['reprint']);

function shortToken(ticketNumber) {
  if (!ticketNumber) return 'N/A';
  // Show last segment for readability: ZFF2026...-XXXX-X → XXXX-X
  const parts = ticketNumber.split('-');
  if (parts.length >= 2) return parts.slice(-2).join('-');
  return ticketNumber.slice(-10);
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('en-TZ', { maximumFractionDigits: 0 }).format(amount || 0);
}
</script>
