<template>
  <div class="bg-white rounded-2xl border-2 border-orange-200 shadow-sm overflow-hidden">
    <!-- Header -->
    <div class="px-5 py-4 bg-orange-50 border-b border-orange-100 flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <div class="h-10 w-10 rounded-xl bg-orange-500 text-white flex items-center justify-center shadow-sm"><i class="fas fa-hourglass-half"></i></div>
        <div>
          <h3 class="text-sm font-extrabold text-orange-900">Awaiting payment</h3>
          <p class="text-xs text-orange-700">Space is reserved — the booking completes as soon as payment is confirmed.</p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <span class="text-lg font-black text-slate-900 font-mono">{{ payment?.currency || currency }} {{ Number(payment?.amount || amount || 0).toLocaleString() }}</span>
        <span v-if="isOpen && countdown" :class="['px-2.5 py-1 rounded-full bg-white border border-orange-200 text-orange-700 text-xs font-mono font-bold', secondsLeft < 120 ? 'animate-pulse' : '']">
          <i class="fas fa-clock mr-1"></i>{{ countdown }}
        </span>
      </div>
    </div>

    <div class="p-5 space-y-4">
      <div v-if="error" class="px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm font-medium flex items-start gap-2">
        <i class="fas fa-exclamation-circle mt-0.5"></i><span>{{ error }}</span>
      </div>
      <div v-if="notice" class="px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-medium flex items-start gap-2">
        <i class="fas fa-check-circle mt-0.5"></i><span>{{ notice }}</span>
      </div>

      <!-- USSD push tracker -->
      <div v-if="isOpen && payment.flow === 'ussd_push' && !picking" class="space-y-4">
        <div class="flex items-center gap-2 text-sm">
          <i :class="['fas', payment.channel.icon, 'text-brand-600']"></i>
          <span class="font-bold text-slate-800">{{ payment.channel.name }}</span>
          <span v-if="payment.simulated" class="px-1.5 py-0.5 rounded bg-violet-100 text-violet-700 text-[10px] font-black uppercase" title="No live credentials — simulated push (dev only)">Simulated</span>
        </div>
        <ol class="grid grid-cols-1 sm:grid-cols-4 gap-2">
          <li v-for="(s, i) in steps" :key="i"
            :class="['rounded-xl border p-3 flex items-center gap-2.5',
              s.state === 'done' ? 'bg-emerald-50 border-emerald-200' : s.state === 'active' ? 'bg-brand-50 border-brand-300' : 'bg-slate-50 border-slate-200']">
            <span :class="['h-7 w-7 rounded-full flex items-center justify-center text-xs shrink-0',
              s.state === 'done' ? 'bg-emerald-500 text-white' : s.state === 'active' ? 'bg-brand-600 text-white' : 'bg-slate-200 text-slate-400']">
              <i :class="['fas', s.state === 'done' ? 'fa-check' : s.state === 'active' ? 'fa-circle-notch fa-spin' : s.icon]"></i>
            </span>
            <span :class="['text-xs font-bold', s.state === 'pending' ? 'text-slate-400' : 'text-slate-800']">{{ s.label }}</span>
          </li>
        </ol>
        <p v-if="payment.status === 'awaiting_customer'" class="text-xs text-slate-500">
          Ask the customer to check <strong>{{ payment.payer_masked }}</strong> and enter their PIN. This updates automatically.
        </p>
        <p v-if="payment.worker_delayed" class="text-xs text-amber-700"><i class="fas fa-exclamation-triangle mr-1"></i>The payment queue looks idle — sending the push directly.</p>
        <div class="flex flex-wrap items-end gap-2">
          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Resend to</label>
            <input v-model="payer" type="tel" placeholder="0754 123 456" class="w-48 px-3 py-2 text-sm font-mono rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-brand-400 outline-none" />
          </div>
          <button type="button" @click="resend" :disabled="busy" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold disabled:opacity-50">
            <i class="fas fa-redo mr-1"></i> Resend push
          </button>
        </div>
      </div>

      <!-- Lipa Namba -->
      <div v-else-if="isOpen && payment.flow === 'lipa_namba' && !picking" class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="rounded-2xl border-2 border-dashed border-slate-300 p-5 bg-slate-50 space-y-3">
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Give the customer these details</p>
          <div v-for="row in lipaRows" :key="row.label" class="flex items-center justify-between gap-3">
            <span class="text-xs text-slate-500">{{ row.label }}</span>
            <span class="flex items-center gap-2">
              <span :class="['font-mono font-black text-slate-900', row.big ? 'text-xl' : 'text-sm']">{{ row.value }}</span>
              <button type="button" @click="copy(row.value)" class="text-slate-400 hover:text-brand-600" title="Copy"><i class="fas fa-copy text-xs"></i></button>
            </span>
          </div>
          <p v-if="payment.lipa?.instructions" class="text-[11px] text-slate-500 pt-2 border-t border-slate-200">{{ payment.lipa.instructions }}</p>
          <button type="button" @click="smsInstructions" :disabled="busy" class="w-full px-4 py-2 rounded-xl bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 text-xs font-bold disabled:opacity-50">
            <i class="fas fa-sms mr-1"></i> SMS these instructions to the customer
          </button>
        </div>
        <form @submit.prevent="confirmLipa" class="space-y-3">
          <p class="text-sm text-slate-600">When the payment SMS arrives on the business phone, enter its transaction code and confirm.</p>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Transaction reference (from the SMS) *</label>
            <input v-model="reference" required placeholder="e.g. 9JK4XYZ1AB" class="w-full px-3 py-2.5 text-sm font-mono uppercase rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 outline-none" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Payer's number (optional)</label>
            <input v-model="lipaPayer" type="tel" placeholder="0754 123 456" class="w-full px-3 py-2.5 text-sm font-mono rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-emerald-400 outline-none" />
          </div>
          <button type="submit" :disabled="busy || !reference" class="w-full px-4 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold disabled:opacity-50">
            <i class="fas fa-check-circle mr-1"></i> Confirm payment
          </button>
        </form>
      </div>

      <!-- Last outcome when nothing is open -->
      <div v-if="!isOpen && payment && payment.status !== 'paid'" class="px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-sm">
        <p class="font-bold text-red-800">{{ payment.channel?.name }} — <span class="capitalize">{{ (payment.status || '').replace('_', ' ') }}</span></p>
        <p v-if="payment.failure_reason" class="text-red-700 text-xs mt-0.5">{{ payment.failure_reason }}</p>
      </div>

      <!-- Channel picker (no open attempt, or switching) -->
      <div v-if="!isOpen || picking" class="space-y-3">
        <p class="text-sm font-semibold text-slate-700">{{ isOpen ? 'Switch to another channel:' : 'Take the payment:' }}</p>
        <div class="flex flex-wrap gap-2">
          <button v-for="m in methods" :key="m.slug" type="button" @click="choice = m.slug" :disabled="!usable(m)"
            :title="usable(m) ? '' : `${m.name} isn't live yet — its credentials aren't set up`"
            :class="['px-3 py-2 rounded-xl border-2 text-xs font-bold flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed',
              choice === m.slug ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300']">
            <i :class="['fas', m.icon]"></i>{{ m.name }}
            <span v-if="m.simulated" class="px-1 rounded bg-violet-100 text-violet-700 text-[8px] uppercase">Sim</span>
            <span v-else-if="!m.live" class="px-1 rounded bg-amber-100 text-amber-700 text-[8px] uppercase">Not live</span>
          </button>
        </div>
        <div v-if="chosen?.flow === 'ussd_push'">
          <label class="block text-xs font-semibold text-slate-600 mb-1">Payer's phone (receives the push)</label>
          <input v-model="payer" type="tel" placeholder="0754 123 456" class="w-56 px-3 py-2 text-sm font-mono rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-brand-400 outline-none" />
          <p :class="['text-[11px] mt-1', hintClass(pickerHint.tone)]">{{ pickerHint.text }}</p>
          <p v-if="!usable(chosen)" class="text-[11px] mt-1 text-amber-700 font-semibold">{{ chosen.name }} isn't live yet — choose another channel.</p>
        </div>
        <div class="flex gap-2">
          <button type="button" @click="startPayment" :disabled="busy || !choice || !usable(chosen)" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold disabled:opacity-50">
            <i class="fas fa-paper-plane mr-1"></i>
            {{ chosen?.flow === 'cash' ? 'Record cash payment' : chosen?.flow === 'lipa_namba' ? 'Use Lipa Namba' : 'Send USSD push' }}
          </button>
          <button v-if="isOpen" type="button" @click="picking = false" class="px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-600 text-sm font-semibold">Back</button>
        </div>
      </div>

      <!-- Footer -->
      <div class="flex flex-wrap items-center gap-2 pt-3 border-t border-slate-100">
        <button v-if="isOpen && !picking" type="button" @click="picking = true" class="px-4 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold">
          <i class="fas fa-exchange-alt mr-1"></i> Switch payment channel
        </button>
        <template v-if="canCancel">
          <button v-if="!confirmingCancel" type="button" @click="confirmingCancel = true" class="px-4 py-2 rounded-xl bg-white border border-red-200 hover:bg-red-50 text-red-600 text-xs font-bold">
            <i class="fas fa-ban mr-1"></i> Cancel booking
          </button>
          <form v-else @submit.prevent="cancelBooking" class="flex flex-wrap items-center gap-2">
            <input v-model="cancelReason" required maxlength="500" placeholder="Reason (e.g. customer could not pay)" class="w-64 px-3 py-2 text-xs rounded-xl border border-red-200 bg-red-50/40 focus:bg-white outline-none" />
            <button type="submit" :disabled="busy" class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold disabled:opacity-50">Confirm cancel</button>
            <button type="button" @click="confirmingCancel = false" class="text-xs text-slate-500">Keep</button>
          </form>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import axios from 'axios';
import { payerHint } from '../../utils/msisdn';

const props = defineProps({
  consignmentId: { type: [Number, String], required: true },
  payment:       { type: Object, default: null },
  methods:       { type: Array, default: () => [] },
  senderPhone:   { type: String, default: '' },
  amount:        { type: [Number, String], default: 0 },
  currency:      { type: String, default: 'TZS' },
  canCancel:     { type: Boolean, default: false },
  apiBase:       { type: String, default: '/portal/api/cargo' },
});
const emit = defineEmits(['paid', 'cancelled', 'updated']);

const OPEN = ['initiated', 'sending', 'awaiting_customer'];

const payment          = ref(props.payment);
const picking          = ref(false);
const choice           = ref(null);
const payer            = ref(props.payment?.payer_msisdn || props.senderPhone || '');
const reference        = ref('');
const lipaPayer        = ref('');
const busy             = ref(false);
const error            = ref('');
const notice           = ref('');
const now              = ref(Date.now());
const confirmingCancel = ref(false);
const cancelReason     = ref('');
let pollTimer = null;
let clockTimer = null;

const isOpen = computed(() => !!payment.value && OPEN.includes(payment.value.status));
const chosen = computed(() => props.methods.find(m => m.slug === choice.value));
const pickerHint = computed(() => payerHint(props.methods, chosen.value, payer.value));
// A push channel without live credentials can't collect anything from here
const usable = (m) => !!m && (m.live !== false || m.simulated);

const secondsLeft = computed(() => {
  if (!payment.value?.expires_at) return null;
  return Math.max(0, Math.floor((new Date(payment.value.expires_at) - now.value) / 1000));
});
const countdown = computed(() => {
  const s = secondsLeft.value;
  return s === null ? '' : `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`;
});

const steps = computed(() => {
  const sent = payment.value?.status === 'awaiting_customer';
  return [
    { label: 'Space reserved', icon: 'fa-box', state: 'done' },
    { label: sent ? `Push sent to ${payment.value.payer_masked}` : 'Sending push…', icon: 'fa-paper-plane', state: sent ? 'done' : 'active' },
    { label: 'Waiting for PIN', icon: 'fa-key', state: sent ? 'active' : 'pending' },
    { label: 'Paid', icon: 'fa-check', state: payment.value?.status === 'paid' ? 'done' : 'pending' },
  ];
});

const lipaRows = computed(() => [
  { label: 'Lipa Namba', value: payment.value.lipa?.number, big: true },
  { label: 'Business name', value: payment.value.lipa?.name || '—' },
  { label: 'Amount', value: `${payment.value.currency} ${Number(payment.value.amount).toLocaleString()}`, big: true },
  { label: 'Reference', value: payment.value.consignment?.consignment_number },
]);

const hintClass = (tone) => ({ ok: 'text-emerald-700 font-semibold', warn: 'text-amber-600', error: 'text-red-600' }[tone] || 'text-slate-500');

// Picking an MNO channel and typing another network's number: follow the number
watch(payer, () => {
  const h = pickerHint.value;
  if (h.tone === 'error' && h.match && (chosen.value?.prefixes || []).length) choice.value = h.match.slug;
});

watch(() => props.methods, (ms) => {
  if (!choice.value) choice.value = (ms.find(m => m.flow === 'ussd_push' && usable(m)) || ms.find(usable))?.slug || null;
}, { immediate: true });

watch(() => props.payment, (p) => { if (p && p.uuid !== payment.value?.uuid) { payment.value = p; schedulePoll(); } });

const errorMessage = (e, fallback) => {
  const d = e.response?.data;
  if (d?.errors) return Object.values(d.errors)[0][0];
  return d?.error || d?.message || fallback;
};

function update(p) {
  payment.value = p;
  emit('updated', p);
  if (p.status === 'paid') {
    notice.value = 'Payment received — booking confirmed.';
    emit('paid', p);
  } else if (!OPEN.includes(p.status)) {
    picking.value = true;
  }
}

function schedulePoll() {
  clearTimeout(pollTimer);
  if (!isOpen.value) return;
  pollTimer = setTimeout(async () => {
    try {
      const { data } = await axios.get(`${props.apiBase}/payments/${payment.value.uuid}`);
      update(data);
    } catch { /* transient — keep polling */ }
    schedulePoll();
  }, 3000);
}

async function run(fn, fallback) {
  busy.value = true; error.value = ''; notice.value = '';
  try { return await fn(); }
  catch (e) { error.value = errorMessage(e, fallback); return null; }
  finally { busy.value = false; }
}

async function startPayment() {
  const data = await run(async () => (await axios.post(`${props.apiBase}/consignments/${props.consignmentId}/payments`, {
    payment_method: choice.value, payer_msisdn: payer.value,
  })).data, 'Could not start the payment.');
  if (data) { picking.value = false; update(data); schedulePoll(); }
}

async function resend() {
  const data = await run(async () => (await axios.post(`${props.apiBase}/payments/${payment.value.uuid}/resend`, { payer_msisdn: payer.value })).data, 'Could not resend the push.');
  if (data) { update(data); notice.value = 'Push resent.'; schedulePoll(); }
}

async function confirmLipa() {
  const data = await run(async () => (await axios.post(`${props.apiBase}/payments/${payment.value.uuid}/confirm`, {
    reference: reference.value, payer_msisdn: lipaPayer.value || null,
  })).data, 'Could not confirm the payment.');
  if (data) update(data);
}

async function smsInstructions() {
  const r = await run(async () => (await axios.post(`${props.apiBase}/payments/${payment.value.uuid}/sms-instructions`, { phone: lipaPayer.value || null })).data, 'Could not send the SMS.');
  if (r) notice.value = r.sent ? `Instructions sent to ${r.to}.` : r.simulated ? `SMS gateway not configured — message logged only (to ${r.to}).` : `SMS to ${r.to} failed.`;
}

async function cancelBooking() {
  const ok = await run(async () => (await axios.post(`${props.apiBase}/cancel/${props.consignmentId}`, { reason: cancelReason.value })).data, 'Cancellation failed.');
  if (ok) { clearTimeout(pollTimer); emit('cancelled'); }
}

function copy(text) {
  navigator.clipboard?.writeText(String(text ?? ''));
  notice.value = 'Copied.';
  setTimeout(() => { if (notice.value === 'Copied.') notice.value = ''; }, 1200);
}

onMounted(() => {
  clockTimer = setInterval(() => { now.value = Date.now(); }, 1000);
  if (!isOpen.value && payment.value?.status !== 'paid') picking.value = true;
  schedulePoll();
});
onBeforeUnmount(() => { clearTimeout(pollTimer); clearInterval(clockTimer); });
</script>
