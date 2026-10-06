<template>
  <div class="bg-gradient-to-r from-white via-white/95 to-slate-50 border border-slate-200/60 rounded-2xl px-5 py-3.5 shadow-sm">
    <div class="flex items-center gap-4 flex-wrap">
      <!-- Shift Timer -->
      <div class="flex items-center gap-3 pr-4 border-r border-slate-200/60">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
             :class="shift.remaining_minutes <= 60
               ? 'bg-rose-500/10 text-rose-500 border border-rose-500/20'
               : 'bg-brand-500/10 text-brand-600 border border-brand-500/20'">
          <i class="fas fa-clock text-sm"></i>
        </div>
        <div>
          <div class="text-[9px] font-bold uppercase tracking-widest text-slate-400">Shift</div>
          <div class="text-base font-extrabold font-mono" :class="shift.remaining_minutes <= 60 ? 'text-rose-500' : 'text-slate-800'">
            {{ formatTime(shift.remaining_minutes) }}
          </div>
          <div class="text-[10px] text-slate-400">remaining</div>
        </div>
      </div>

      <!-- Tickets Today + Pace -->
      <div class="flex items-center gap-3 pr-4 border-r border-slate-200/60">
        <div class="w-9 h-9 rounded-xl bg-sky-500/10 text-sky-600 border border-sky-500/20 flex items-center justify-center shrink-0">
          <i class="fas fa-ticket-alt text-sm"></i>
        </div>
        <div>
          <div class="text-[9px] font-bold uppercase tracking-widest text-slate-400">Tickets</div>
          <div class="text-base font-extrabold text-slate-800">{{ shift.tickets_this_shift }}</div>
          <div class="text-[10px] flex items-center gap-1">
            <template v-if="shift.yesterday_at_this_point > 0">
              <span :class="paceDirection >= 0 ? 'text-emerald-500' : 'text-rose-500'">
                <i class="fas" :class="paceDirection >= 0 ? 'fa-arrow-up' : 'fa-arrow-down'"></i>
                {{ Math.abs(paceDirection) }} vs yesterday
              </span>
            </template>
            <span v-else class="text-slate-400">—</span>
          </div>
        </div>
      </div>

      <!-- Revenue -->
      <div class="flex items-center gap-3 pr-4 border-r border-slate-200/60">
        <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 flex items-center justify-center shrink-0">
          <i class="fas fa-coins text-sm"></i>
        </div>
        <div>
          <div class="text-[9px] font-bold uppercase tracking-widest text-slate-400">Revenue</div>
          <div class="text-base font-extrabold text-emerald-600">{{ formatCurrency(shift.revenue_this_shift) }}</div>
          <div class="text-[10px] text-slate-400">TZS</div>
        </div>
      </div>

      <!-- Payment Breakdown (compact pills) -->
      <div class="flex-1 flex items-center gap-2 flex-wrap">
        <div v-for="(amount, method) in shift.payment_breakdown" :key="method"
             class="px-2.5 py-1 rounded-lg bg-slate-100/80 border border-slate-200/40 text-[10px] font-semibold text-slate-600 flex items-center gap-1.5">
          <i class="text-[9px]" :class="methodIcon(method)"></i>
          <span class="capitalize">{{ method }}</span>
          <span class="text-slate-800 font-bold font-mono">{{ formatCompact(amount) }}</span>
        </div>
      </div>

      <!-- Quick Book CTA -->
      <router-link to="/fast-booking"
                   class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-400 hover:to-brand-500 text-white font-bold text-xs uppercase tracking-wide shadow-lg shadow-brand-500/20 transition-all flex items-center gap-2 shrink-0">
        <i class="fas fa-bolt"></i>
        Quick Book
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  shift: { type: Object, default: () => ({}) },
});

const paceDirection = computed(() => {
  return (props.shift.tickets_this_shift || 0) - (props.shift.yesterday_at_this_point || 0);
});

function formatTime(minutes) {
  if (!minutes || minutes <= 0) return '0:00';
  const h = Math.floor(minutes / 60);
  const m = minutes % 60;
  return `${h}:${String(m).padStart(2, '0')}`;
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('en-TZ', { maximumFractionDigits: 0 }).format(amount || 0);
}

function formatCompact(amount) {
  if (!amount) return '0';
  if (amount >= 1000000) return (amount / 1000000).toFixed(1) + 'M';
  if (amount >= 1000) return Math.round(amount / 1000) + 'K';
  return amount.toString();
}

function methodIcon(method) {
  const m = (method || '').toLowerCase();
  if (m.includes('cash')) return 'fas fa-money-bill-wave text-emerald-500';
  if (m.includes('pos') || m.includes('card')) return 'fas fa-credit-card text-sky-500';
  if (m.includes('wallet') || m.includes('agent')) return 'fas fa-wallet text-violet-500';
  if (m.includes('mobile') || m.includes('mpesa')) return 'fas fa-mobile-alt text-amber-500';
  return 'fas fa-receipt text-slate-400';
}
</script>
