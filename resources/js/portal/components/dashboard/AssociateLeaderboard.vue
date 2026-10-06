<template>
  <div class="bg-white/90 border border-slate-200/60 rounded-2xl shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200/40">
      <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
        <i class="fas fa-users text-brand-600 text-xs"></i>
        Associate Performance — Today
      </h3>
      <router-link to="/associates" class="text-[10px] font-semibold text-brand-600 hover:text-brand-500 transition-colors uppercase tracking-wider">
        View All <i class="fas fa-arrow-right ml-0.5 text-[8px]"></i>
      </router-link>
    </div>

    <div v-if="!associates?.length" class="px-5 py-8 text-center">
      <p class="text-sm text-slate-400">No associates registered</p>
    </div>

    <div v-else class="divide-y divide-slate-100">
      <div v-for="assoc in associates" :key="assoc.id"
           class="px-5 py-3 flex items-center gap-3 hover:bg-slate-50/60 transition-colors">
        <!-- Rank Badge -->
        <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 text-xs font-black"
             :class="assoc.rank === 1
               ? 'bg-amber-500/10 text-amber-600 border border-amber-500/20'
               : assoc.rank === 2
                 ? 'bg-slate-200/60 text-slate-600 border border-slate-300/40'
                 : 'bg-slate-100/60 text-slate-500 border border-slate-200/40'">
          {{ assoc.rank }}
        </div>

        <!-- Name + Alert -->
        <div class="flex-1 min-w-0">
          <div class="text-sm font-semibold text-slate-800 truncate">{{ assoc.name }}</div>
          <div v-if="assoc.alert" class="text-[10px] text-rose-500 font-medium mt-0.5">
            <i class="fas fa-exclamation-triangle mr-0.5 text-[8px]"></i>
            No sales for {{ assoc.zero_sale_days }} day{{ assoc.zero_sale_days !== 1 ? 's' : '' }}
          </div>
        </div>

        <!-- Tickets -->
        <div class="text-center shrink-0">
          <div class="text-sm font-bold text-slate-800">{{ assoc.tickets }}</div>
          <div class="text-[9px] text-slate-400 uppercase tracking-wider">Tkts</div>
        </div>

        <!-- Revenue -->
        <div class="text-right shrink-0 min-w-[80px]">
          <div class="text-sm font-bold font-mono" :class="assoc.revenue > 0 ? 'text-emerald-600' : 'text-slate-400'">
            {{ formatCompact(assoc.revenue) }}
          </div>
          <div class="text-[9px] text-slate-400 uppercase tracking-wider">TZS</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  associates: { type: Array, default: () => [] },
});

function formatCompact(amount) {
  if (!amount) return '0';
  if (amount >= 1000000) return (amount / 1000000).toFixed(1) + 'M';
  if (amount >= 1000) return Math.round(amount / 1000) + 'K';
  return new Intl.NumberFormat('en-TZ', { maximumFractionDigits: 0 }).format(amount);
}
</script>
