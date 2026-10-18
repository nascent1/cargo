<template>
  <!-- Same screen as the admin booking page (admin/cargo/booking.blade.php) -->
  <div class="pb-28 lg:pb-0 max-w-7xl">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-5">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">New cargo booking</h1>
        <p class="text-sm text-slate-600 mt-0.5">The system works out the price as you type.</p>
      </div>
      <StepPills v-if="!cargo.paidPayment" :steps="STEPS" :step="cargo.step" :locked="!!cargo.pendingPayment" @go="cargo.goStep" />
    </div>

    <div v-if="cargo.banner" role="alert" class="mb-4 rounded-xl px-4 py-3 text-sm flex items-start gap-3 border"
      :class="{ 'bg-red-50 border-red-200 text-red-800': cargo.banner.type === 'error', 'bg-amber-50 border-amber-300 text-amber-900': cargo.banner.type === 'warning', 'bg-emerald-50 border-emerald-200 text-emerald-800': cargo.banner.type === 'success' }">
      <i class="fas mt-0.5" :class="{ 'fa-circle-exclamation': cargo.banner.type === 'error', 'fa-triangle-exclamation': cargo.banner.type === 'warning', 'fa-circle-check': cargo.banner.type === 'success' }"></i>
      <span class="flex-1">{{ cargo.banner.text }}</span>
      <button type="button" class="opacity-60 hover:opacity-100" aria-label="Dismiss" @click="cargo.banner = null"><i class="fas fa-times"></i></button>
    </div>

    <!-- Booked and paid -->
    <div v-if="cargo.paidPayment" class="ui-card border-emerald-200 p-8 text-center max-w-xl mx-auto">
      <div class="h-14 w-14 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto text-2xl"><i class="fas fa-check"></i></div>
      <h2 class="text-xl font-bold text-slate-900 mt-4">Booked and paid</h2>
      <p class="text-sm text-slate-600 mt-1">
        Booking <span class="font-mono font-bold text-slate-900">{{ cargo.booked.consignment_number }}</span> · {{ money(cargo.paidPayment.amount, cargo.paidPayment.currency || 'TZS') }}
        <span v-if="cargo.paidPayment.channel?.name"> · {{ cargo.paidPayment.channel.name }}</span>
        <span v-if="cargo.paidPayment.manual_reference || cargo.paidPayment.provider_reference"> · Ref <span class="font-mono">{{ cargo.paidPayment.manual_reference || cargo.paidPayment.provider_reference }}</span></span>
      </p>
      <div class="flex flex-wrap justify-center gap-2 mt-6">
        <button v-if="authStore.can('booking:print')" type="button" class="ui-btn" @click="printReceipt"><i class="fas fa-print"></i> Print receipt</button>
        <router-link :to="`/cargo-bookings/${cargo.booked.consignment_id}`" class="ui-btn"><i class="fas fa-eye"></i> Open booking</router-link>
        <button type="button" class="ui-btn" @click="startNew(true)"><i class="fas fa-user-check"></i> Same sender again</button>
        <button type="button" class="ui-btn-primary" @click="startNew(false)"><i class="fas fa-plus"></i> New booking</button>
      </div>
    </div>

    <!-- Booked, waiting for the customer's payment -->
    <div v-else-if="cargo.pendingPayment" class="max-w-4xl">
      <PaymentPanel
        :consignment-id="cargo.booked.consignment_id"
        :payment="cargo.pendingPayment"
        :methods="cargo.paymentMethods"
        :sender-phone="cargo.people.sender.phone"
        :can-cancel="true"
        @paid="onPaid"
        @cancelled="onPaymentCancelled" />
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
      <div class="lg:col-span-2 space-y-4">
        <!-- Chosen departure (steps 2–4) -->
        <div v-if="cargo.step > 1 && cargo.service" class="ui-card px-4 py-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
          <i class="fas fa-ship text-brand-600"></i>
          <span class="font-semibold text-slate-900">{{ cargo.service.route?.name }}</span>
          <span class="text-slate-500">{{ dayLabel(cargo.service.departure_date, true) }} · <span class="font-mono">{{ (cargo.service.departure_time || '').slice(0, 5) }}</span> · {{ cargo.service.transport_unit?.name }}</span>
          <button type="button" class="ui-link ml-auto" @click="cargo.goStep(1)">Change</button>
        </div>

        <DepartureList v-if="cargo.step === 1" />
        <CargoLines v-else-if="cargo.step === 2" />
        <PeopleForm v-else-if="cargo.step === 3" />
        <ReviewPay v-else />

        <!-- Navigation (desktop; phones use the bar pinned to the bottom) -->
        <div v-if="cargo.step > 1" class="hidden lg:flex items-center justify-between gap-3">
          <button type="button" class="ui-btn" @click="cargo.goStep(cargo.step - 1)"><i class="fas fa-arrow-left text-xs"></i> Back</button>
          <button v-if="cargo.step < 4" type="button" class="ui-btn-primary" :disabled="cargo.busy" @click="cargo.next()">
            <template v-if="cargo.holding"><i class="fas fa-circle-notch fa-spin"></i> Holding space…</template>
            <template v-else>Continue <i class="fas fa-arrow-right text-xs"></i></template>
          </button>
          <button v-else type="button" class="ui-btn-success h-11" :disabled="cargo.busy || !cargo.paymentMethod" @click="cargo.confirm()">
            <i class="fas" :class="cargo.submitting ? 'fa-circle-notch fa-spin' : 'fa-check-circle'"></i>{{ cargo.submitting ? 'Booking…' : cargo.confirmLabel }}
          </button>
        </div>
      </div>

      <OrderSummary />
    </div>

    <!-- Phones: total and the next action stay pinned to the bottom -->
    <div v-if="!cargo.booked && cargo.step > 1" class="lg:hidden fixed bottom-0 inset-x-0 z-30 bg-white border-t border-slate-200 px-4 py-3 flex items-center gap-3 shadow-[0_-4px_12px_rgba(15,23,42,0.06)]">
      <button type="button" class="ui-btn h-11 w-11 px-0 shrink-0" aria-label="Back" @click="cargo.goStep(cargo.step - 1)"><i class="fas fa-arrow-left"></i></button>
      <div class="min-w-0">
        <p class="text-[11px] text-slate-500 truncate">{{ cargo.lockId ? `Total · held ${cargo.countdown}` : 'Total' }}</p>
        <p class="text-lg font-bold text-slate-900 tabular-nums">{{ cargo.quote ? 'TZS ' + num(cargo.quote.net_amount) : (cargo.quoting ? '…' : '—') }}</p>
      </div>
      <button v-if="cargo.step < 4" type="button" class="ui-btn-primary h-11 ml-auto shrink-0" :disabled="cargo.busy" @click="cargo.next()">
        {{ cargo.holding ? 'Holding…' : 'Continue' }} <i class="fas fa-arrow-right text-xs"></i>
      </button>
      <button v-else type="button" class="ui-btn-success h-11 ml-auto shrink-0" :disabled="cargo.busy || !cargo.paymentMethod" @click="cargo.confirm()">
        {{ cargo.submitting ? 'Booking…' : 'Book' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { watch, onMounted, onBeforeUnmount } from 'vue';
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router';
import { useCargoStore } from '../../stores/cargo';
import { useAuthStore } from '../../stores/auth';
import { num, money, dayLabel } from '../../utils/cargoUi';
import StepPills from '../../components/booking/StepPills.vue';
import DepartureList from '../../components/booking/DepartureList.vue';
import CargoLines from '../../components/booking/CargoLines.vue';
import PeopleForm from '../../components/booking/PeopleForm.vue';
import ReviewPay from '../../components/booking/ReviewPay.vue';
import OrderSummary from '../../components/booking/OrderSummary.vue';
import PaymentPanel from '../../components/cargo/PaymentPanel.vue';

const STEPS = ['Departure', 'Cargo', 'Sender & receiver', 'Review & pay'];
const cargo = useCargoStore();
const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();

// Live price: re-quote shortly after the cart or the discount changes
let quoteTimer = null;
const queueQuote = () => { clearTimeout(quoteTimer); quoteTimer = setTimeout(() => cargo.fetchQuote(), 400); };
watch(() => cargo.lines, queueQuote, { deep: true });
watch(() => cargo.discount, queueQuote, { deep: true });

const clock = setInterval(() => cargo.tick(), 1000);
const onPageHide = () => cargo.releaseOnLeave();

onMounted(async () => {
  cargo.startNew();
  window.addEventListener('pagehide', onPageHide);
  await Promise.all([cargo.loadReferenceData(), cargo.loadDepartures()]);

  // From the dashboard's "Book": go straight to the Cargo step for that departure
  if (route.query.service_id) {
    const svc = cargo.departures.find(s => String(s.id) === String(route.query.service_id));
    if (svc) cargo.selectService(svc);
    router.replace({ query: {} });
  }
});

// Leaving for another page frees the held space
onBeforeRouteLeave(() => { if (cargo.lockId && !cargo.booked) cargo.releaseHold(); });
onBeforeUnmount(() => {
  clearInterval(clock);
  clearTimeout(quoteTimer);
  window.removeEventListener('pagehide', onPageHide);
});

async function startNew(sameSender) {
  cargo.startNew(sameSender);
  window.scrollTo({ top: 0, behavior: 'smooth' });
  await cargo.loadDepartures();
}

function printReceipt() {
  window.open(`/portal/api/cargo/consignments/${cargo.booked.consignment_id}/print`, '_blank');
}

function onPaid(payment) {
  cargo.paidPayment = payment;
  cargo.pendingPayment = null;
}

async function onPaymentCancelled() {
  await startNew(false);
  cargo.banner = { type: 'warning', text: 'The booking was cancelled and its space released.' };
}
</script>
