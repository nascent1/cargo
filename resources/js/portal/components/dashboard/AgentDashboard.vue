<template>
  <div class="space-y-5">
    <!-- Wallet Health Bar -->
    <WalletHealthBar :wallet="data.wallet" @request-topup="showTopupModal = true" />

    <!-- Agency KPIs -->
    <AgencyKpiStrip v-if="data.agency_kpis" :kpis="data.agency_kpis" />

    <!-- Attention Required -->
    <AttentionRequired :items="data.attention_items || []" />

    <!-- Two-Column: Leaderboard + Voyage Board -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
      <!-- Associate Leaderboard (1/3 width) -->
      <div class="lg:col-span-1">
        <AssociateLeaderboard :associates="data.associate_leaderboard || []" />
      </div>

      <!-- Voyage Board (2/3 width) -->
      <div class="lg:col-span-2">
        <VoyageBoardTable :voyages="data.voyage_board || []" />
      </div>
    </div>

    <!-- Session Activity Feed -->
    <SessionActivityFeed :items="data.session_feed || []" @reprint="handleReprint" />

    <!-- Top-Up Modal (inline) -->
    <transition name="fade">
      <div v-if="showTopupModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4" @click.self="showTopupModal = false">
        <div class="bg-white border border-slate-200 rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4 animate-fade-slide-up">
          <div class="flex items-center justify-between pb-3 border-b border-slate-200/60">
            <h3 class="text-base font-bold text-slate-800">Quick Top-Up Request</h3>
            <button @click="showTopupModal = false" class="text-slate-500 hover:text-slate-800 transition-colors"><i class="fas fa-times"></i></button>
          </div>
          <p class="text-xs text-slate-500 leading-relaxed">
            Make a bank deposit or mobile money transfer, then submit your reference below for finance verification.
          </p>
          <div class="space-y-3">
            <div>
              <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1 block">Amount (TZS)</label>
              <input v-model="topupAmount" type="number" placeholder="e.g. 5000000"
                     class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200/60 rounded-xl text-sm focus:outline-none focus:border-brand-500/40">
            </div>
            <div>
              <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1 block">Payment Reference</label>
              <input v-model="topupReference" type="text" placeholder="e.g. TXN-2026-08-0012"
                     class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200/60 rounded-xl text-sm focus:outline-none focus:border-brand-500/40">
            </div>
          </div>
          <button @click="submitTopup"
                  :disabled="!topupAmount || !topupReference || submitting"
                  class="w-full py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-bold text-sm shadow-lg shadow-emerald-500/20 transition-all disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2">
            <i class="fas fa-paper-plane" v-if="!submitting"></i>
            <i class="fas fa-spinner fa-spin" v-else></i>
            {{ submitting ? 'Submitting...' : 'Submit Request' }}
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useUiStore } from '../../stores/ui';
import WalletHealthBar from '../dashboard/WalletHealthBar.vue';
import AgencyKpiStrip from '../dashboard/AgencyKpiStrip.vue';
import AttentionRequired from '../dashboard/AttentionRequired.vue';
import VoyageBoardTable from '../dashboard/VoyageBoardTable.vue';
import AssociateLeaderboard from '../dashboard/AssociateLeaderboard.vue';
import SessionActivityFeed from '../dashboard/SessionActivityFeed.vue';

defineProps({
  data: { type: Object, default: () => ({}) },
});

const uiStore = useUiStore();
const showTopupModal = ref(false);
const topupAmount = ref('');
const topupReference = ref('');
const submitting = ref(false);

async function submitTopup() {
  if (!topupAmount.value || !topupReference.value) return;
  submitting.value = true;
  try {
    await axios.post('/api/v1/portal/wallet/top-up', {
      amount: topupAmount.value,
      reference: topupReference.value,
    });
    uiStore.notify('Top-up request submitted for finance approval.', 'Request Submitted', 'success');
    showTopupModal.value = false;
    topupAmount.value = '';
    topupReference.value = '';
  } catch (err) {
    uiStore.notify(err.response?.data?.message || 'Failed to submit request', 'Error', 'error');
  } finally {
    submitting.value = false;
  }
}

function handleReprint(ticket) {
  const url = ticket.ticket_number ? `/portal/api/tickets/${ticket.ticket_number}/print` : null;
  if (url) {
    uiStore.notify(`Sending [${ticket.ticket_number}] to printer...`, 'Printing Ticket', 'info');
    window.open(url, '_blank');
  }
}
</script>
