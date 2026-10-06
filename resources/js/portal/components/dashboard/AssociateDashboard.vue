<template>
  <div class="space-y-5">
    <!-- Parent Wallet Status (Both exact balance AND status shown per user decision) -->
    <div class="border rounded-2xl shadow-sm overflow-hidden"
         :class="walletBorderClass">
      <div class="px-5 py-4" :class="walletBgClass">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0"
                 :class="walletIconClass">
              <i class="fas fa-wallet text-lg"></i>
            </div>
            <div>
              <div class="flex items-center gap-2 mb-1">
                <span class="text-[9px] font-bold uppercase tracking-widest text-slate-400">Parent Agency Wallet</span>
                <span class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider"
                      :class="statusBadgeClass">
                  {{ walletStatus }}
                </span>
              </div>
              <div class="text-xl font-extrabold font-mono text-slate-800">
                {{ formatCurrency(walletBalance) }}
                <span class="text-xs font-semibold text-slate-400 ml-1">TZS</span>
              </div>
              <div class="text-[10px] text-slate-500 mt-0.5">
                Available balance including credit limit
              </div>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <div class="text-right">
              <div class="text-[9px] font-bold uppercase tracking-widest text-slate-400">Daily Burn</div>
              <div class="text-sm font-bold font-mono text-slate-600">~{{ formatCompact(data.wallet?.daily_burn_rate) }}/day</div>
            </div>
            <div class="text-right">
              <div class="text-[9px] font-bold uppercase tracking-widest text-slate-400">Runway</div>
              <div class="text-sm font-bold font-mono" :class="runwayColor">
                {{ data.wallet?.runway_days >= 999 ? '∞' : '~' + data.wallet?.runway_days + ' days' }}
              </div>
            </div>

            <!-- Notify Agent Button -->
            <button v-if="walletHealth === 'low' || walletHealth === 'critical'"
                    class="px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-white font-bold text-xs uppercase transition-all flex items-center gap-1.5 shadow-sm">
              <i class="fas fa-bell"></i>
              Notify Agent
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Personal Performance Strip -->
    <div v-if="data.performance" class="bg-white/90 border border-slate-200/60 rounded-2xl shadow-sm p-4">
      <div class="text-[9px] font-bold uppercase tracking-widest text-slate-400 mb-3">My Performance</div>
      <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div>
          <div class="text-lg font-extrabold text-slate-800">{{ data.performance.today_tickets }}</div>
          <div class="text-[10px] text-slate-400">Tickets Today</div>
        </div>
        <div>
          <div class="text-lg font-extrabold text-emerald-600">{{ formatCurrency(data.performance.today_revenue) }}</div>
          <div class="text-[10px] text-slate-400">Revenue Today</div>
        </div>
        <div>
          <div class="text-lg font-extrabold text-slate-800">{{ data.performance.week_tickets }}</div>
          <div class="text-[10px] text-slate-400">Tickets This Week</div>
        </div>
        <div>
          <div class="text-lg font-extrabold text-emerald-600">{{ formatCurrency(data.performance.week_revenue) }}</div>
          <div class="text-[10px] text-slate-400">Revenue This Week</div>
        </div>
        <div>
          <div class="text-lg font-extrabold text-violet-600">{{ formatCurrency(data.performance.estimated_commission) }}</div>
          <div class="text-[10px] text-slate-400">Est. Commission ({{ data.performance.commission_rate }}%)</div>
        </div>
      </div>
    </div>

    <!-- Attention Required -->
    <AttentionRequired :items="data.attention_items || []" />

    <!-- Voyage Board (filtered to authorized ferries) -->
    <VoyageBoardTable :voyages="data.voyage_board || []" />

    <!-- Session Activity Feed -->
    <SessionActivityFeed :items="data.session_feed || []" @reprint="handleReprint" />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useUiStore } from '../../stores/ui';
import AttentionRequired from '../dashboard/AttentionRequired.vue';
import VoyageBoardTable from '../dashboard/VoyageBoardTable.vue';
import SessionActivityFeed from '../dashboard/SessionActivityFeed.vue';

const props = defineProps({
  data: { type: Object, default: () => ({}) },
});

const uiStore = useUiStore();

const walletBalance = computed(() => props.data.wallet?.available_balance || props.data.wallet?.balance || 0);
const walletHealth = computed(() => props.data.wallet?.health || 'healthy');

const walletStatus = computed(() => {
  switch (walletHealth.value) {
    case 'critical': return 'Critical';
    case 'low':      return 'Low';
    case 'moderate': return 'OK';
    default:         return 'Sufficient';
  }
});

const walletBorderClass = computed(() => {
  switch (walletHealth.value) {
    case 'critical': return 'border-rose-300/60';
    case 'low':      return 'border-amber-300/60';
    default:         return 'border-slate-200/60';
  }
});

const walletBgClass = computed(() => {
  switch (walletHealth.value) {
    case 'critical': return 'bg-gradient-to-r from-rose-50/50 via-white to-white';
    case 'low':      return 'bg-gradient-to-r from-amber-50/50 via-white to-white';
    default:         return 'bg-gradient-to-r from-emerald-50/30 via-white to-white';
  }
});

const walletIconClass = computed(() => {
  switch (walletHealth.value) {
    case 'critical': return 'bg-rose-500/10 text-rose-500 border border-rose-500/20';
    case 'low':      return 'bg-amber-500/10 text-amber-600 border border-amber-500/20';
    default:         return 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20';
  }
});

const statusBadgeClass = computed(() => {
  switch (walletHealth.value) {
    case 'critical': return 'bg-rose-500/10 text-rose-500 border border-rose-500/20';
    case 'low':      return 'bg-amber-500/10 text-amber-600 border border-amber-500/20';
    default:         return 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20';
  }
});

const runwayColor = computed(() => {
  const days = props.data.wallet?.runway_days || 999;
  if (days <= 1)  return 'text-rose-500';
  if (days <= 3)  return 'text-amber-500';
  return 'text-emerald-600';
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

function handleReprint(ticket) {
  const url = ticket.ticket_number ? `/portal/api/tickets/${ticket.ticket_number}/print` : null;
  if (url) {
    uiStore.notify(`Sending [${ticket.ticket_number}] to printer...`, 'Printing Ticket', 'info');
    window.open(url, '_blank');
  }
}
</script>
