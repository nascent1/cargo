<template>
  <div class="space-y-5">

    <!-- Hero Wallet Banner -->
    <div class="relative rounded-2xl overflow-hidden shadow-xl"
      style="background: linear-gradient(135deg, #0f172a 0%, #0e2240 50%, #0f1f36 100%);">

      <!-- Ambient glow -->
      <div class="absolute inset-0 pointer-events-none"
        style="background: radial-gradient(ellipse at 70% 50%, rgba(52,211,153,0.12) 0%, transparent 60%);"></div>
      <div class="absolute -right-12 -bottom-12 opacity-5 pointer-events-none">
        <i class="fas fa-wallet text-[160px] text-white -rotate-12"></i>
      </div>

      <div class="relative px-6 py-6 flex flex-col md:flex-row md:items-center justify-between gap-5">
        <div>
          <span class="inline-flex items-center gap-1.5 text-[9px] font-bold uppercase tracking-[0.15em] px-2 py-1 rounded-full mb-3"
            style="background: rgba(52,211,153,0.12); color: #34d399; border: 1px solid rgba(52,211,153,0.2)">
            <i class="fas fa-circle text-[6px]"></i> Agency Credit Ledger
          </span>
          <p class="text-4xl font-extrabold font-mono text-white tracking-tight">{{ authStore.formattedWalletBalance }}</p>
          <p class="text-sm mt-1.5" style="color: rgba(255,255,255,0.4)">
            Available prepaid balance{{ authStore.isAgent ? ' for agency & associates' : '' }}.
          </p>
        </div>
        <button @click="showTopupModal = true"
          class="inline-flex items-center gap-2.5 px-5 py-3 rounded-xl font-bold text-sm transition-all shadow-lg shrink-0"
          style="background: linear-gradient(135deg, #FABD00, #f59e0b); color: #78350f; box-shadow: 0 8px 24px rgba(250,189,0,0.25);">
          <i class="fas fa-plus-circle"></i> Request Top-Up
        </button>
      </div>
    </div>

    <!-- KPI Cards -->
    <div :class="authStore.isAgent ? 'grid grid-cols-1 sm:grid-cols-3 gap-4' : 'grid grid-cols-1 sm:grid-cols-2 gap-4'">

      <!-- Purchasing Power -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 hover-lift">
        <div class="flex items-center justify-between mb-3">
          <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Purchasing Power</p>
          <div class="h-8 w-8 rounded-xl bg-emerald-50 flex items-center justify-center">
            <i class="fas fa-layer-group text-sm text-emerald-600"></i>
          </div>
        </div>
        <p class="text-2xl font-extrabold font-mono text-slate-900">{{ walletStore.formattedBalance }}</p>
        <p class="text-[11px] text-slate-400 mt-1">
          + <span class="font-semibold text-emerald-600">{{ new Intl.NumberFormat('en-US').format(walletStore.summary.credit_limit || 0) }} TZS</span> overdraft
        </p>
        <div class="mt-3 pt-3 border-t border-slate-100 flex justify-between text-[10px] text-slate-400">
          <span>Status: <strong class="text-emerald-600">Active</strong></span>
          <span>Dual Control</span>
        </div>
      </div>

      <!-- Security Deposit -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 hover-lift">
        <div class="flex items-center justify-between mb-3">
          <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Security Deposit</p>
          <div class="h-8 w-8 rounded-xl bg-amber-50 flex items-center justify-center">
            <i class="fas fa-shield-alt text-sm text-amber-600"></i>
          </div>
        </div>
        <p class="text-2xl font-extrabold font-mono text-slate-900">
          {{ walletStore.summary.security_deposit || 40 }}%
          <span class="text-sm font-normal text-slate-400 ml-1">Hold</span>
        </p>
        <p class="text-[11px] text-slate-400 mt-1">System-governed security hold on new credit.</p>
        <div class="mt-3 pt-3 border-t border-slate-100 flex justify-between text-[10px] text-slate-400">
          <span>Non-Negotiable</span>
          <span class="text-amber-600 font-semibold flex items-center gap-1"><i class="fas fa-lock text-[9px]"></i> System Policy</span>
        </div>
      </div>

      <!-- Commission (agents only) -->
      <div v-if="authStore.isAgent" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 hover-lift">
        <div class="flex items-center justify-between mb-3">
          <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Commission Rate</p>
          <div class="h-8 w-8 rounded-xl bg-violet-50 flex items-center justify-center">
            <i class="fas fa-percent text-sm text-violet-600"></i>
          </div>
        </div>
        <p class="text-2xl font-extrabold font-mono text-slate-900">
          {{ walletStore.summary.commission_rate || 5 }}%
          <span class="text-sm font-normal text-slate-400 ml-1">Rate</span>
        </p>
        <p class="text-[11px] text-slate-400 mt-1">
          Method: <strong class="text-slate-700">{{ walletStore.summary.commission_method === 'pre_deposit' ? 'Pre-Deposit' : 'Post-Sale' }}</strong>
        </p>
        <div class="mt-3 pt-3 border-t border-slate-100 flex justify-between text-[10px] text-slate-400">
          <span>Earned: <strong class="text-violet-600">{{ walletStore.summary.total_earned_commission || '0' }} TZS</strong></span>
          <span>Settlement</span>
        </div>
      </div>
    </div>

    <!-- Ledger Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
      <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
        <div>
          <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <i class="fas fa-list-check text-emerald-500 text-xs"></i>
            Recent Ledger Entries
          </h2>
          <p class="text-[10px] text-slate-400 mt-0.5">Auto-balanced per ticket issue</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-100">
              <th class="text-left px-6 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Date / Time</th>
              <th class="text-left px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Description</th>
              <th class="text-left px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Operator</th>
              <th class="text-left px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Type</th>
              <th class="text-right px-6 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Amount (TZS)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="walletStore.loading">
              <td colspan="5" class="px-6 py-12 text-center text-slate-400 text-sm">
                <i class="fas fa-circle-notch fa-spin text-xl text-emerald-400 mb-2 block"></i>
                Loading ledger...
              </td>
            </tr>
            <tr v-else-if="walletStore.transactions.length === 0">
              <td colspan="5" class="px-6 py-12 text-center text-sm">
                <i class="fas fa-receipt text-2xl text-slate-300 mb-2 block"></i>
                <span class="text-slate-400">No transactions yet.</span>
              </td>
            </tr>
            <tr v-else v-for="(item, idx) in walletStore.transactions" :key="idx"
              class="border-b border-slate-50 hover:bg-slate-50/80 transition-colors">
              <td class="px-6 py-3.5 text-xs text-slate-500 font-mono tabular-nums">{{ item.created_at }}</td>
              <td class="px-4 py-3.5 text-sm font-semibold text-slate-800">{{ item.description }}</td>
              <td class="px-4 py-3.5 text-xs text-slate-500">{{ item.performed_by_name || 'System' }}</td>
              <td class="px-4 py-3.5">
                <span :class="item.type === 'credit'
                  ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
                  : 'bg-red-100 text-red-700 border-red-200'"
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border uppercase">
                  {{ item.type }}
                </span>
              </td>
              <td :class="item.type === 'credit' ? 'text-emerald-600' : 'text-red-600'"
                class="px-6 py-3.5 text-right font-bold font-mono tabular-nums text-sm">
                {{ item.type === 'credit' ? '+' : '−' }}{{ new Intl.NumberFormat('en-US').format(item.amount) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Top-Up Modal -->
    <transition name="fade">
      <div v-if="showTopupModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
        @click.self="showTopupModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">

          <!-- Modal header -->
          <div class="px-6 py-4 flex items-center justify-between"
            style="background: linear-gradient(135deg, #0f172a, #0e2240); border-bottom: 1px solid rgba(255,255,255,0.07)">
            <div class="flex items-center gap-3">
              <div class="h-9 w-9 rounded-xl flex items-center justify-center"
                style="background: rgba(250,189,0,0.15); border: 1px solid rgba(250,189,0,0.25)">
                <i class="fas fa-plus-circle text-sm" style="color: #FABD00"></i>
              </div>
              <div>
                <h3 class="text-sm font-bold text-white">Credit Top-Up Request</h3>
                <p class="text-[10px]" style="color: rgba(255,255,255,0.35)">Submit payment reference for verification</p>
              </div>
            </div>
            <button @click="showTopupModal = false"
              class="h-7 w-7 rounded-lg flex items-center justify-center transition-all"
              style="color: rgba(255,255,255,0.35)">
              <i class="fas fa-times text-xs"></i>
            </button>
          </div>

          <div class="p-6 space-y-4">
            <!-- Bank details -->
            <div class="rounded-xl p-4 space-y-2" style="background: #f8fafc; border: 1px solid #e2e8f0">
              <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Payment Destination</p>
              <div class="flex justify-between text-sm">
                <span class="text-slate-500">Bank</span>
                <span class="font-bold text-slate-800">CRDB BANK PLC</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-slate-500">Account Name</span>
                <span class="font-bold text-slate-800">FERRY FLOW MARITIME LTD</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-slate-500">Account No.</span>
                <span class="font-bold font-mono text-brand-600">0150248991200</span>
              </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitTopup" class="space-y-4">
              <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Amount (TZS) *</label>
                <div class="relative">
                  <span class="absolute inset-y-0 left-3 flex items-center text-xs font-bold text-slate-400">TZS</span>
                  <input v-model="topupAmount" type="number" required placeholder="500,000"
                    class="w-full pl-10 pr-4 py-3 text-sm rounded-xl border border-slate-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-slate-50 focus:bg-white font-mono" />
                </div>
              </div>

              <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Bank Receipt / Reference # *</label>
                <div class="relative">
                  <span class="absolute inset-y-0 left-3 flex items-center text-xs text-slate-400">
                    <i class="fas fa-hashtag"></i>
                  </span>
                  <input v-model="topupRef" type="text" required placeholder="CRDB-TRX-988210"
                    class="w-full pl-9 pr-4 py-3 text-sm rounded-xl border border-slate-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-slate-50 focus:bg-white font-mono uppercase" />
                </div>
              </div>

              <div class="flex gap-3 pt-2">
                <button type="button" @click="showTopupModal = false"
                  class="flex-1 py-2.5 text-sm font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                  Cancel
                </button>
                <button type="submit"
                  class="flex-1 py-2.5 text-sm font-bold rounded-xl text-amber-900 transition-all shadow-lg flex items-center justify-center gap-2"
                  style="background: linear-gradient(135deg, #FABD00, #f59e0b); box-shadow: 0 4px 14px rgba(250,189,0,0.3)">
                  <i class="fas fa-paper-plane text-xs"></i>
                  Submit for Audit
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </transition>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useUiStore }   from '../stores/ui';
import { useWalletStore } from '../stores/wallet';

const authStore   = useAuthStore();
const uiStore     = useUiStore();
const walletStore = useWalletStore();

const showTopupModal = ref(false);
const topupAmount    = ref('');
const topupRef       = ref('');

onMounted(() => {
  walletStore.fetchSummary();
  walletStore.fetchTransactions();
});

async function submitTopup() {
  try {
    await walletStore.requestTopUp({ amount: topupAmount.value, reference: topupRef.value });
    uiStore.notify(
      `Top-up request for ${topupAmount.value} TZS [Ref: ${topupRef.value}] submitted to finance queue.`,
      'Top-Up Submitted', 'success'
    );
    showTopupModal.value = false;
    topupAmount.value = '';
    topupRef.value = '';
  } catch {
    uiStore.notify(walletStore.error || 'Failed to submit top-up request.', 'Top-Up Error', 'error');
  }
}
</script>

<style scoped>
.hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.hover-lift:hover { transform: translateY(-2px); box-shadow: 0 8px 24px -4px rgba(0,0,0,0.08); }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
