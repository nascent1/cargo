<template>
  <section class="space-y-4">
    <div class="ui-card p-5 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
      <div>
        <p class="ui-overline">Sender</p>
        <p class="font-semibold text-slate-900">{{ cargo.people.sender.name }}</p>
        <p class="text-slate-700 font-mono text-xs">{{ cargo.people.sender.phone }}</p>
      </div>
      <div>
        <p class="ui-overline">Receiver</p>
        <p class="font-semibold text-slate-900">{{ cargo.people.receiver.name }}</p>
        <p class="text-slate-700 font-mono text-xs">{{ cargo.people.receiver.phone }}</p>
      </div>
      <div v-if="cargo.specialInstructions" class="sm:col-span-2">
        <p class="ui-overline">Special instructions</p>
        <p class="text-slate-800">{{ cargo.specialInstructions }}</p>
      </div>
    </div>

    <!-- Fare override (only for users allowed to override fares) -->
    <div v-if="cargo.canOverride" class="ui-card p-5">
      <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-900 cursor-pointer">
        <input v-model="cargo.discount.on" type="checkbox" class="h-4 w-4 rounded border-slate-400 accent-brand-600"> Apply a discount (fare override)
      </label>
      <div v-if="cargo.discount.on" class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3">
        <div>
          <label class="ui-label" for="disc-amt">Discount (TZS)</label>
          <input id="disc-amt" v-model.number="cargo.discount.amount" type="number" min="0" step="500" class="ui-field" :class="(cargo.errors.discount_amount || cargo.quoteErrors.discount_amount) && 'ui-field-invalid'">
          <p v-if="cargo.errors.discount_amount || cargo.quoteErrors.discount_amount" class="ui-error">{{ cargo.errors.discount_amount || cargo.quoteErrors.discount_amount }}</p>
        </div>
        <div class="sm:col-span-2">
          <label class="ui-label" for="disc-reason">Reason <span class="text-red-600">*</span> <span class="font-normal text-slate-500">(kept on the booking's history)</span></label>
          <input id="disc-reason" v-model="cargo.discount.reason" type="text" maxlength="255" placeholder="e.g. Regular customer, approved by station manager" class="ui-field" :class="(cargo.errors.discount_reason || cargo.quoteErrors.discount_reason) && 'ui-field-invalid'">
          <p v-if="cargo.errors.discount_reason || cargo.quoteErrors.discount_reason" class="ui-error">{{ cargo.errors.discount_reason || cargo.quoteErrors.discount_reason }}</p>
        </div>
      </div>
      <p v-if="cargo.discountPending && !cargo.errors.discount_reason" class="text-xs text-amber-800 mt-2"><i class="fas fa-circle-info mr-1"></i>Enter a reason to apply the discount to the total.</p>
    </div>

    <!-- Payment channel -->
    <div class="ui-card p-5 space-y-4">
      <h2 class="text-base font-semibold text-slate-900">Payment</h2>
      <div v-if="!cargo.paymentMethods.length" class="px-4 py-3 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 text-sm">
        <i class="fas fa-triangle-exclamation mr-1"></i> No payment channels are ready. Contact your administrator.
      </div>
      <div v-for="group in methodGroups" :key="group.flow">
        <p class="ui-overline mb-1.5"><i :class="['fas', group.icon, 'mr-1']"></i>{{ group.label }}</p>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
          <button v-for="m in group.methods" :key="m.slug" type="button" class="relative px-3 py-3 rounded-xl border-2 text-center transition-colors"
            :class="cargo.paymentMethod === m.slug ? 'border-brand-500 bg-brand-50' : 'border-slate-200 hover:border-slate-400'" @click="cargo.paymentMethod = m.slug">
            <span v-if="m.simulated" class="absolute top-1 right-1 px-1 rounded bg-violet-100 text-violet-800 text-[9px] font-bold uppercase" title="No live credentials yet — the push is simulated (testing only)">Sim</span>
            <span v-else-if="!m.live" class="absolute top-1 right-1 px-1 rounded bg-amber-100 text-amber-800 text-[9px] font-bold uppercase" title="Credentials not set up yet — no push will be sent">Not live</span>
            <i :class="['fas', m.icon, 'text-lg block mb-1', cargo.paymentMethod === m.slug ? 'text-brand-600' : 'text-slate-500']"></i>
            <span class="text-xs font-semibold text-slate-800">{{ m.name }}</span>
          </button>
        </div>
      </div>

      <div v-if="cargo.currentMethod?.flow === 'ussd_push'" class="rounded-xl bg-brand-50/60 border border-brand-200 p-4">
        <label class="ui-label" for="payer">Payer's phone (receives the USSD push) <span class="text-red-600">*</span></label>
        <input id="payer" v-model="cargo.payerMsisdn" type="tel" placeholder="0754 123 456" class="ui-field sm:w-72 font-mono" :class="cargo.errors.payer_msisdn && 'ui-field-invalid'">
        <p v-if="cargo.payerHintInfo.text" class="text-xs mt-1.5" :class="hintClass">{{ cargo.payerHintInfo.text }}</p>
        <p v-if="cargo.errors.payer_msisdn" class="ui-error">{{ cargo.errors.payer_msisdn }}</p>
        <p v-if="cargo.methodNotLive" class="mt-3 px-3 py-2 rounded-lg bg-amber-50 border border-amber-300 text-amber-900 text-xs font-semibold">
          <i class="fas fa-triangle-exclamation mr-1"></i>{{ cargo.currentMethod.name }} isn't live yet, so no push will be sent. The booking is saved as <strong>awaiting payment</strong> with the space held — take the payment through another channel from the booking.
        </p>
      </div>
      <p v-else-if="cargo.currentMethod?.flow === 'lipa_namba'" class="text-xs text-slate-600">
        <i class="fas fa-circle-info mr-1"></i>After booking, show the customer the Lipa Namba and the amount, then enter the transaction code from the payment SMS.
      </p>
    </div>
  </section>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useCargoStore } from '../../stores/cargo';

// Step 4: check, (optionally) discount, choose how the customer pays
const cargo = useCargoStore();

const FLOW_GROUPS = [
  { flow: 'cash', label: 'Cash', icon: 'fa-money-bill-wave' },
  { flow: 'ussd_push', label: "Mobile money — USSD push to the customer's phone", icon: 'fa-mobile-alt' },
  { flow: 'lipa_namba', label: 'Lipa Namba — customer pays, you confirm the SMS reference', icon: 'fa-store' },
];
const methodGroups = computed(() => FLOW_GROUPS
  .map(g => ({ ...g, methods: cargo.paymentMethods.filter(m => m.flow === g.flow) }))
  .filter(g => g.methods.length));

const hintClass = computed(() => ({ ok: 'text-emerald-700 font-semibold', warn: 'text-amber-800', error: 'text-red-700' }[cargo.payerHintInfo.tone] || 'text-slate-500'));

// Typing another network's number while a network channel is chosen: follow the number
watch(() => cargo.payerMsisdn, () => {
  const h = cargo.payerHintInfo;
  if (h.tone === 'error' && h.match && (cargo.currentMethod?.prefixes || []).length) cargo.paymentMethod = h.match.slug;
});
</script>
