<template>
  <div v-if="wallet" class="border rounded-2xl shadow-sm overflow-hidden"
       :class="healthBorderClass">
    <div class="px-5 py-4"
         :class="healthBgClass">
      <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <!-- Left: Balance & Runway -->
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0"
               :class="healthIconClass">
            <i class="fas text-lg" :class="healthIcon"></i>
          </div>
          <div>
            <div class="text-[9px] font-bold uppercase tracking-widest mb-1"
                 :class="healthLabelColor">
              Agency Wallet {{ healthLabel }}
            </div>
            <div class="text-2xl font-extrabold font-mono text-slate-800">
              {{ formatCurrency(wallet.balance) }}
              <span class="text-xs font-semibold text-slate-400 ml-1">TZS</span>
            </div>
            <div class="flex items-center gap-3 mt-1 text-[11px]">
              <span class="text-slate-500">
                Available: <strong class="text-slate-700 font-mono">{{ formatCurrency(wallet.available_balance) }}</strong>
              </span>
              <span class="text-slate-300">·</span>
              <span class="text-slate-500">
                Credit: <strong class="text-emerald-600 font-mono">+{{ formatCurrency(wallet.credit_limit) }}</strong>
              </span>
            </div>
          </div>
        </div>

        <!-- Center: Burn Rate + Runway -->
        <div class="flex items-center gap-6">
          <div class="text-center">
            <div class="text-[9px] font-bold uppercase tracking-widest text-slate-400 mb-0.5">Daily Burn</div>
            <div class="text-sm font-bold font-mono text-slate-700">~{{ formatCompact(wallet.daily_burn_rate) }}/day</div>
          </div>
          <div class="text-center">
            <div class="text-[9px] font-bold uppercase tracking-widest text-slate-400 mb-0.5">Runway</div>
            <div class="text-sm font-bold font-mono" :class="runwayColor">
              {{ wallet.runway_days >= 999 ? '∞' : '~' + wallet.runway_days + ' days' }}
            </div>
          </div>
          <div v-if="wallet.commission_rate" class="text-center">
            <div class="text-[9px] font-bold uppercase tracking-widest text-slate-400 mb-0.5">Commission</div>
            <div class="text-sm font-bold font-mono text-violet-600">{{ wallet.commission_rate }}%</div>
          </div>
        </div>

        <!-- Right: Top-Up Status + Action -->
        <div class="flex items-center gap-3 shrink-0">
          <!-- Pending/Recent Top-Up Status -->
          <div v-if="wallet.pending_topup" class="text-right mr-2">
            <div class="text-[10px] font-semibold"
                 :class="wallet.pending_topup.status === 'approved' ? 'text-emerald-600' : 'text-amber-500'">
              <i class="fas" :class="wallet.pending_topup.status === 'approved' ? 'fa-check-circle' : 'fa-clock'"></i>
              Top-up {{ wallet.pending_topup.status === 'approved' ? 'Approved' : 'Pending' }}
            </div>
            <div class="text-[10px] font-mono text-slate-400">
              {{ formatCurrency(wallet.pending_topup.amount) }} TZS
            </div>
          </div>

          <button @click="$emit('request-topup')"
                  class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-bold text-xs uppercase tracking-wide shadow-lg shadow-emerald-500/20 transition-all flex items-center gap-2">
            <i class="fas fa-plus-circle"></i>
            Request Top-Up
          </button>
        </div>
      </div>

      <!-- Capacity gauge bar -->
      <div class="mt-3 pt-3 border-t" :class="healthBorderClass.replace('border-', 'border-t-')">
        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
          <div class="h-full rounded-full transition-all duration-1000 ease-out"
               :class="healthBarColor"
               :style="{ width: gaugePercent + '%' }">
          </div>
        </div>
        <div class="flex justify-between mt-1 text-[9px] text-slate-400">
          <span>Empty</span>
          <span>{{ gaugePercent }}% capacity</span>
          <span>Full ({{ formatCurrency(wallet.balance + wallet.credit_limit) }})</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  wallet: { type: Object, default: null },
});

defineEmits(['request-topup']);

const healthLabel = computed(() => {
  if (!props.wallet) return '';
  switch (props.wallet.health) {
    case 'critical': return '· CRITICAL';
    case 'low':      return '· LOW BALANCE';
    case 'moderate': return '· MODERATE';
    default:         return '· HEALTHY';
  }
});

const healthBorderClass = computed(() => {
  switch (props.wallet?.health) {
    case 'critical': return 'border-rose-300/60';
    case 'low':      return 'border-amber-300/60';
    default:         return 'border-slate-200/60';
  }
});

const healthBgClass = computed(() => {
  switch (props.wallet?.health) {
    case 'critical': return 'bg-gradient-to-r from-rose-50/50 via-white to-white';
    case 'low':      return 'bg-gradient-to-r from-amber-50/50 via-white to-white';
    default:         return 'bg-gradient-to-r from-emerald-50/30 via-white to-white';
  }
});

const healthIconClass = computed(() => {
  switch (props.wallet?.health) {
    case 'critical': return 'bg-rose-500/10 text-rose-500 border border-rose-500/20';
    case 'low':      return 'bg-amber-500/10 text-amber-600 border border-amber-500/20';
    default:         return 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20';
  }
});

const healthIcon = computed(() => {
  switch (props.wallet?.health) {
    case 'critical': return 'fa-exclamation-triangle';
    case 'low':      return 'fa-exclamation-circle';
    default:         return 'fa-wallet';
  }
});

const healthLabelColor = computed(() => {
  switch (props.wallet?.health) {
    case 'critical': return 'text-rose-500';
    case 'low':      return 'text-amber-500';
    default:         return 'text-emerald-600';
  }
});

const healthBarColor = computed(() => {
  switch (props.wallet?.health) {
    case 'critical': return 'bg-rose-500';
    case 'low':      return 'bg-amber-400';
    default:         return 'bg-emerald-500';
  }
});

const runwayColor = computed(() => {
  if (!props.wallet) return 'text-slate-500';
  if (props.wallet.runway_days <= 1)  return 'text-rose-500';
  if (props.wallet.runway_days <= 3)  return 'text-amber-500';
  return 'text-emerald-600';
});

const gaugePercent = computed(() => {
  if (!props.wallet) return 0;
  const max = props.wallet.balance + props.wallet.credit_limit;
  if (max <= 0) return 0;
  return Math.min(100, Math.round((props.wallet.available_balance / max) * 100));
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
