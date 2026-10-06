<template>
  <div class="bg-white/90 border border-slate-200/60 rounded-2xl shadow-sm p-4">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <!-- Today's Sales -->
      <div class="space-y-1">
        <div class="text-[9px] font-bold uppercase tracking-widest text-slate-400">Today's Sales</div>
        <div class="text-xl font-extrabold text-slate-800">{{ formatCurrency(kpis.today_revenue) }}</div>
        <div class="text-[10px] flex items-center gap-1">
          <span v-if="kpis.change_percent != null" :class="kpis.change_percent >= 0 ? 'text-emerald-500' : 'text-rose-500'">
            <i class="fas" :class="kpis.change_percent >= 0 ? 'fa-arrow-up' : 'fa-arrow-down'" style="font-size: 8px;"></i>
            {{ Math.abs(kpis.change_percent) }}% vs yesterday
          </span>
          <span v-else class="text-slate-400">—</span>
        </div>
      </div>

      <!-- This Month -->
      <div class="space-y-1">
        <div class="text-[9px] font-bold uppercase tracking-widest text-slate-400">This Month</div>
        <div class="text-xl font-extrabold text-slate-800">{{ formatCompact(kpis.month_revenue) }}</div>
        <div class="text-[10px] text-slate-400">{{ kpis.today_tickets || 0 }} tickets today</div>
      </div>

      <!-- Commission Due -->
      <div class="space-y-1">
        <div class="text-[9px] font-bold uppercase tracking-widest text-slate-400">Commission Due</div>
        <div class="text-xl font-extrabold text-violet-600">{{ formatCurrency(kpis.commission_due) }}</div>
        <div class="text-[10px] text-slate-400">Estimated this month</div>
      </div>

      <!-- Cancellation Rate + Pending Refunds -->
      <div class="space-y-1">
        <div class="text-[9px] font-bold uppercase tracking-widest text-slate-400">Health</div>
        <div class="flex items-baseline gap-2">
          <span class="text-xl font-extrabold" :class="kpis.cancellation_rate > 10 ? 'text-rose-500' : kpis.cancellation_rate > 5 ? 'text-amber-500' : 'text-emerald-600'">
            {{ kpis.cancellation_rate }}%
          </span>
          <span class="text-[10px] text-slate-400">cancel rate</span>
        </div>
        <div v-if="kpis.pending_refunds > 0" class="text-[10px] text-rose-500 font-medium">
          <i class="fas fa-exclamation-circle mr-0.5"></i>
          {{ formatCurrency(kpis.pending_refunds) }} TZS pending refunds
        </div>
        <div v-else class="text-[10px] text-slate-400">No pending refunds</div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  kpis: { type: Object, default: () => ({}) },
});

function formatCurrency(amount) {
  return new Intl.NumberFormat('en-TZ', { maximumFractionDigits: 0 }).format(amount || 0);
}

function formatCompact(amount) {
  if (!amount) return '0';
  if (amount >= 1000000) return (amount / 1000000).toFixed(1) + 'M';
  if (amount >= 1000) return Math.round(amount / 1000) + 'K';
  return amount.toString();
}
</script>
