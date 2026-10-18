<template>
  <div class="space-y-6">

    <!-- Loading -->
    <div v-if="loading" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-12 text-center text-slate-400">
      <i class="fas fa-circle-notch fa-spin text-2xl text-brand-400 mb-3 block"></i>
      <p class="text-sm">Loading the booking…</p>
    </div>

    <!-- Not Found -->
    <div v-else-if="!booking" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-12 text-center">
      <i class="fas fa-search text-3xl text-slate-300 mb-4 block"></i>
      <p class="text-slate-600 font-semibold">Booking not found</p>
      <router-link to="/cargo-bookings" class="text-sm text-brand-600 mt-2 inline-block hover:underline">
        ← Back to bookings
      </router-link>
    </div>

    <div v-else class="text-slate-800 space-y-6 max-w-7xl">

      <!-- Header Row -->
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight font-mono">
            Booking {{ booking.consignment_number }}
          </h1>
          <span :class="['inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border', statusCls(booking.status)]">
            {{ statusLabel(booking.status) }}
          </span>
        </div>
        <router-link to="/cargo-bookings"
          class="px-3.5 py-2 bg-white hover:bg-slate-100 text-slate-700 font-bold rounded-xl border border-slate-200 transition-colors text-xs flex items-center gap-1.5 shadow-sm">
          <i class="fas fa-arrow-left"></i> Back to bookings
        </router-link>
      </div>

      <!-- Overview & Action Tiles -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
        <!-- Overview -->
        <div class="lg:col-span-7 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm relative overflow-hidden">
          <div class="absolute top-0 left-0 right-0 h-1.5 bg-brand-600"></div>
          <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i class="fas fa-boxes text-brand-600"></i> Booking Overview
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3 gap-x-6 text-sm text-slate-700">
            <div v-for="row in overviewRows" :key="row.label" class="flex items-center justify-between gap-3 border-b border-slate-100 pb-2">
              <span class="text-slate-500 shrink-0">{{ row.label }}:</span>
              <span :class="['font-bold text-slate-900 truncate max-w-[180px]', row.mono ? 'font-mono' : '']" :title="row.title || row.value">
                {{ row.value }}
              </span>
            </div>
            <div class="flex items-center justify-between pt-1">
              <span class="text-slate-500">Flags:</span>
              <div class="flex gap-1">
                <span v-if="booking.has_hazmat" class="px-1.5 py-0.5 bg-rose-100 text-rose-700 text-[10px] font-bold rounded" title="Hazmat"><i class="fas fa-biohazard"></i></span>
                <span v-if="booking.has_fragile" class="px-1.5 py-0.5 bg-amber-100 text-amber-700 text-[10px] font-bold rounded" title="Fragile"><i class="fas fa-wine-glass"></i></span>
                <span v-if="booking.has_livestock" class="px-1.5 py-0.5 bg-emerald-100 text-emerald-700 text-[10px] font-bold rounded" title="Livestock"><i class="fas fa-paw"></i></span>
                <span v-if="!booking.has_hazmat && !booking.has_fragile && !booking.has_livestock" class="text-slate-400 text-xs">None</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Tiles -->
        <div class="lg:col-span-5 grid grid-cols-2 sm:grid-cols-4 gap-3 items-stretch">
          <button v-for="tile in actionTiles" :key="tile.key" type="button"
            :disabled="!tile.enabled" @click="tile.enabled && tile.run()"
            :class="['flex flex-col items-center justify-center p-3.5 rounded-2xl border font-bold text-xs transition-all group',
              tile.enabled
                ? `bg-white border-slate-200 text-slate-700 shadow-sm hover:shadow ${tile.hover}`
                : 'bg-slate-50 border-slate-200 text-slate-400 cursor-not-allowed']">
            <div :class="['w-11 h-11 rounded-2xl flex items-center justify-center text-lg mb-2 transition-colors',
              tile.enabled ? tile.iconCls : 'bg-slate-200 text-slate-400']">
              <i :class="['fas', tile.icon]"></i>
            </div>
            <span class="text-center leading-tight" v-html="tile.label"></span>
          </button>
        </div>
      </div>

      <!-- Awaiting payment: resume the push / Lipa Namba / switch channel from here -->
      <PaymentPanel v-if="booking.status === 'pending_payment' && booking.actions?.can_take_payment"
        :key="booking.open_payment?.uuid || 'none'"
        :consignment-id="booking.id"
        :payment="booking.open_payment || booking.payments?.[0] || null"
        :methods="paymentMethods"
        :sender-phone="booking.sender_phone || ''"
        :amount="booking.billing_amount"
        :currency="booking.billing_currency"
        :can-cancel="!!booking.actions?.can_cancel"
        @paid="onPaid"
        @cancelled="onCancelled" />

      <!-- Left behind (paid, not loaded when the vessel departed) -->
      <div v-if="booking.left_behind_at && booking.status === 'booked'" class="p-4 rounded-2xl border border-rose-200 bg-rose-50 text-rose-800 text-sm">
        <i class="fas fa-triangle-exclamation mr-1"></i><strong>Left behind:</strong> paid but not loaded when the vessel departed ({{ formatDateTime(booking.left_behind_at) }}).
        Contact the sender about the next departure, or cancel and refund.
      </div>

      <!-- Flash -->
      <div v-if="flash" :class="['p-4 rounded-2xl flex items-start gap-3 font-semibold text-sm border',
        flash.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : flash.type === 'info' ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-rose-50 border-rose-200 text-rose-700']">
        <i :class="['fas mt-0.5', flash.type === 'success' ? 'fa-check-circle' : flash.type === 'info' ? 'fa-hourglass-half' : 'fa-exclamation-triangle']"></i>
        <div class="flex-1">{{ flash.message }}</div>
        <button @click="flash = null" class="opacity-60 hover:opacity-100"><i class="fas fa-times"></i></button>
      </div>

      <!-- Tabs -->
      <div ref="tabsEl" class="border-b border-slate-200 flex gap-2 overflow-x-auto pb-1">
        <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key"
          :class="['px-5 py-3 border-b-2 transition-all rounded-t-xl text-sm whitespace-nowrap flex items-center gap-2',
            activeTab === tab.key ? 'border-brand-600 text-brand-600 font-bold bg-white shadow-xs' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold hover:border-slate-300']">
          <i :class="['fas', tab.icon]"></i> {{ tab.label }}
        </button>
      </div>

      <!-- TAB: Cargo Items -->
      <div v-show="activeTab === 'items'" class="space-y-4">
        <h3 class="text-xl font-bold text-slate-800 tracking-tight">Cargo Manifest</h3>
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead class="bg-slate-50 text-slate-600 text-[11px] uppercase font-semibold tracking-wider border-b border-slate-200">
                <tr>
                  <th class="py-3.5 px-4 w-12 text-center">#</th>
                  <th class="py-3.5 px-4">QR Token</th>
                  <th class="py-3.5 px-4">Cargo Type</th>
                  <th class="py-3.5 px-4">Description</th>
                  <th class="py-3.5 px-4">Metrics</th>
                  <th class="py-3.5 px-4 text-center">Status</th>
                  <th class="py-3.5 px-4 text-right">Fare ({{ booking.billing_currency }})</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                <tr v-if="!booking.items?.length">
                  <td colspan="7" class="py-12 text-center text-slate-400 font-medium">No items found for this booking.</td>
                </tr>
                <tr v-for="(item, idx) in booking.items" :key="item.id"
                  :class="['hover:bg-slate-50 transition-colors', item.status === 'cancelled' ? 'bg-rose-50/50 text-slate-400' : '']">
                  <td class="py-4 px-4 text-center text-slate-500 font-mono">{{ idx + 1 }}.</td>
                  <td class="py-4 px-4 font-mono font-bold">{{ item.qr_code_token || 'N/A' }}</td>
                  <td class="py-4 px-4">
                    {{ item.cargo_type?.name || 'Unknown' }}
                    <span v-if="item.is_fragile" class="ml-1 text-[10px] font-bold bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded">Fragile</span>
                  </td>
                  <td class="py-4 px-4">
                    {{ Number(item.quantity) > 1 ? item.quantity + ' × ' : '' }}{{ item.description || 'No description' }}
                    <div v-if="item.vehicle_reg_number" class="text-slate-500 mt-0.5">Reg <span class="font-mono font-bold text-slate-700">{{ item.vehicle_reg_number }}</span><span v-if="item.driver_name"> · Driver {{ item.driver_name }}</span></div>
                    <div v-if="Number(item.declared_value) > 0" class="text-slate-500 mt-0.5">Declared value {{ num(item.declared_value) }}<span v-if="Number(item.value_charge) > 0"> · value charge <span class="font-semibold text-slate-700">{{ num(item.value_charge) }}</span></span></div>
                  </td>
                  <td class="py-4 px-4">
                    <div>{{ num(item.weight_kg, 2) }} kg</div>
                    <div class="text-slate-500">{{ num(item.volume_cbm, 4) }} cbm</div>
                  </td>
                  <td class="py-4 px-4 text-center">
                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider', statusCls(item.status)]">
                      {{ statusLabel(item.status) }}
                    </span>
                  </td>
                  <td class="py-4 px-4 text-right font-mono font-bold">{{ num(item.final_fare, 2) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB: Tracking History -->
      <div v-show="activeTab === 'tracking'" class="space-y-4">
        <h3 class="text-xl font-bold text-slate-800 tracking-tight">Tracking History</h3>
        <div v-if="booking.collected_by_name" class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 text-sm">
          <h4 class="font-bold text-slate-800 mb-3"><i class="fas fa-hand-holding text-brand-600 mr-1"></i> Collection</h4>
          <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div><dt class="text-xs text-slate-500">Collected by</dt><dd class="font-semibold">{{ booking.collected_by_name }}</dd></div>
            <div><dt class="text-xs text-slate-500">ID</dt><dd class="font-semibold font-mono">{{ booking.collected_id_number }}</dd></div>
            <div><dt class="text-xs text-slate-500">Mobile</dt><dd class="font-semibold font-mono">{{ booking.collected_phone }}
              <span v-if="booking.collected_phone_verified" class="ml-1 text-xs font-sans text-emerald-700"><i class="fas fa-circle-check"></i> confirmed by code</span>
              <span v-else class="ml-1 text-xs font-sans text-amber-700"><i class="fas fa-triangle-exclamation"></i> not confirmed</span></dd></div>
            <div><dt class="text-xs text-slate-500">When</dt><dd class="font-semibold">{{ formatDateTime(booking.collected_at) }}</dd></div>
          </dl>
          <ul v-if="booking.collection_warnings?.length" class="mt-3 text-xs text-amber-800 list-disc ml-5"><li v-for="w in booking.collection_warnings" :key="w">{{ w }}</li></ul>
        </div>
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6">
          <p v-if="!booking.tracking_events?.length" class="text-center py-8 text-slate-500">No tracking events recorded.</p>
          <div v-else>
            <div v-for="(event, idx) in booking.tracking_events" :key="event.id" class="flex gap-4">
              <div class="flex flex-col items-center">
                <div class="w-3 h-3 rounded-full bg-brand-500 ring-4 ring-brand-100"></div>
                <div v-if="idx < booking.tracking_events.length - 1" class="w-0.5 h-full bg-slate-200 mt-2"></div>
              </div>
              <div class="pb-6">
                <p class="text-sm font-bold text-slate-900 uppercase tracking-wider">{{ statusLabel(event.status) }}</p>
                <p class="text-xs font-semibold text-slate-500">{{ formatDateTime(event.created_at) }}</p>
                <p v-if="event.description" class="text-sm text-slate-600 mt-1">{{ event.description }}</p>
                <p v-if="event.location" class="text-xs text-slate-400 mt-0.5"><i class="fas fa-map-marker-alt mr-1"></i>{{ event.location }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB: Receipts & Payments -->
      <div v-show="activeTab === 'receipts'" class="space-y-4">
        <h3 class="text-xl font-bold text-slate-800 tracking-tight">Receipts & Payments</h3>
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 space-y-5">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Payment Method</p>
              <p class="font-bold text-slate-800">{{ booking.payment_method_label || 'Not recorded' }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Payment Status</p>
              <p class="font-bold text-slate-800 capitalize">{{ booking.payment_status || '—' }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Amount</p>
              <p class="font-bold text-slate-800 font-mono">{{ booking.billing_currency }} {{ num(booking.billing_amount, 2) }}</p>
            </div>
          </div>
          <div v-if="booking.payments?.length" class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[10px] font-bold">
                <tr>
                  <th class="py-2.5 px-3">When</th><th class="py-2.5 px-3">Channel</th><th class="py-2.5 px-3">Payer</th>
                  <th class="py-2.5 px-3">Reference</th><th class="py-2.5 px-3">Status</th><th class="py-2.5 px-3">By</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="p in booking.payments" :key="p.uuid">
                  <td class="py-2.5 px-3 whitespace-nowrap">{{ formatDateTime(p.created_at) }}</td>
                  <td class="py-2.5 px-3 font-semibold">{{ p.channel?.name }} <span v-if="p.simulated" class="text-[9px] text-violet-600 font-black">SIM</span></td>
                  <td class="py-2.5 px-3 font-mono">{{ p.payer_masked || '—' }}</td>
                  <td class="py-2.5 px-3 font-mono">{{ p.manual_reference || p.provider_reference || '—' }}</td>
                  <td class="py-2.5 px-3">
                    <span :class="['px-2 py-0.5 rounded-full font-bold', attemptCls(p.status)]">{{ p.status.replace('_', ' ') }}</span>
                    <div v-if="p.failure_reason" class="text-[10px] text-slate-500 mt-0.5">{{ p.failure_reason }}</div>
                  </td>
                  <td class="py-2.5 px-3">{{ p.confirmed_by || p.initiated_by || '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-if="!booking.receipts?.length" class="text-center py-6 text-slate-500">No generated receipts found for this booking.</p>
          <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="receipt in booking.receipts" :key="receipt.id" class="border border-slate-200 rounded-xl p-4 hover:border-brand-300 transition-colors">
              <div class="flex justify-between items-start gap-2 mb-3">
                <span class="px-2 py-1 bg-slate-100 text-slate-700 text-xs font-bold rounded font-mono break-all">{{ receipt.receipt_number }}</span>
                <span class="text-xs text-slate-500 shrink-0">{{ formatDateTime(receipt.issued_at) }}</span>
              </div>
              <p class="text-xs text-slate-500">Issued by <span class="font-bold text-slate-700">{{ fullName(receipt.issued_by) }}</span></p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Cancel Modal -->
    <div v-if="showCancelModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showCancelModal = false"></div>
      <form @submit.prevent="submitCancel" class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-100">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-rose-50/50">
          <h3 class="text-lg font-bold text-rose-700 flex items-center gap-2">
            <i class="fas fa-exclamation-triangle"></i> Cancel Cargo Booking
          </h3>
          <button type="button" @click="showCancelModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
            <i class="fas fa-times text-lg"></i>
          </button>
        </div>
        <div class="p-6">
          <p class="text-slate-600 text-sm mb-4">
            Are you sure you want to cancel booking <strong class="text-slate-900">{{ booking?.consignment_number }}</strong>?
            This action cannot be undone and will mark all associated cargo items as cancelled.
          </p>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Cancellation Reason</label>
          <textarea v-model="cancelReason" rows="3" required maxlength="500"
            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-rose-500 focus:ring-1 focus:ring-rose-500 focus:bg-white outline-none transition-colors"
            placeholder="Enter reason for cancellation..."></textarea>
        </div>
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
          <button type="button" @click="showCancelModal = false" class="px-5 py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl font-semibold text-sm hover:bg-slate-50 transition-colors">Abort</button>
          <button type="submit" :disabled="submitting"
            class="px-5 py-2.5 bg-rose-600 text-white rounded-xl font-bold text-sm shadow-sm hover:bg-rose-700 disabled:opacity-50 transition-colors flex items-center gap-2">
            <i v-if="submitting" class="fas fa-circle-notch fa-spin text-xs"></i> Confirm Cancellation
          </button>
        </div>
      </form>
    </div>

    <!-- Hand over (collector's ID + mobile confirmed by a code) -->
    <HandoverModal v-if="showHandover" :booking="booking" @close="showHandover = false" @done="handedOver" />

  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import moment from 'moment';
import PaymentPanel from '../../components/cargo/PaymentPanel.vue';
import HandoverModal from '../../components/cargo/HandoverModal.vue';
import { badge } from '../../utils/cargoUi';
import { useUiStore } from '../../stores/ui';

const route   = useRoute();
const uiStore = useUiStore();
const booking = ref(null);
const loading = ref(true);

const activeTab       = ref('items');
const tabsEl          = ref(null);
const flash           = ref(null);
const submitting      = ref(false);
const showCancelModal = ref(false);
const showHandover    = ref(false);
const cancelReason    = ref('');

const paymentMethods = ref([]);

const ATTEMPT_CLS = {
    paid: 'bg-emerald-100 text-emerald-700', failed: 'bg-red-100 text-red-700', expired: 'bg-slate-200 text-slate-600',
    cancelled: 'bg-slate-100 text-slate-500', refund_due: 'bg-amber-100 text-amber-800',
};
const attemptCls = (s) => ATTEMPT_CLS[s] || 'bg-sky-100 text-sky-700';

const statusLabel    = (s) => badge('status', s).label;
const statusCls      = (s) => badge('status', s).cls;
const formatDateTime = (d) => (d ? moment(d).format('DD MMM YYYY, HH:mm') : '—');
const num            = (v, dp = 0) => Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: dp, maximumFractionDigits: dp });
const fullName       = (u) => (u ? `${u.first_name || ''} ${u.last_name || ''}`.trim() || u.email : 'System');

const departure = computed(() => {
    const svc = booking.value?.transport_service;
    if (!svc?.departure_date) return 'TBD';
    return `${moment(svc.departure_date).format('DD MMM YYYY')} @ ${(svc.departure_time || '').substring(0, 5)}`;
});

const overviewRows = computed(() => {
    const b = booking.value;
    if (!b) return [];
    const routeName = b.route?.name
        || (b.route ? `${b.route.origin?.name || '?'} → ${b.route.destination?.name || '?'}` : 'N/A');
    return [
        { label: 'Sender',       value: b.sender_name, title: b.sender_phone },
        { label: 'Receiver',     value: b.receiver_name, title: b.receiver_phone },
        { label: 'Route',        value: routeName },
        { label: 'Vessel / Unit', value: b.transport_service?.transport_unit?.name || 'Unassigned' },
        { label: 'Departure',    value: departure.value },
        { label: 'Date Booked',  value: formatDateTime(b.created_at) },
        { label: 'Total Weight', value: `${num(b.total_weight_kg, 2)} kg` },
        { label: `Total (${b.billing_currency})`, value: num(b.billing_amount, 2), mono: true },
        { label: 'Payment',      value: b.payment_method_label || 'Not recorded', title: b.payment_status },
        { label: 'Booked By',    value: fullName(b.booked_by_user) },
    ];
});

const tabs = computed(() => [
    { key: 'items',    icon: 'fa-box-open', label: `Cargo Items (${booking.value?.items?.length || 0})` },
    { key: 'tracking', icon: 'fa-route',    label: `Tracking History (${booking.value?.tracking_events?.length || 0})` },
    { key: 'receipts', icon: 'fa-receipt',  label: 'Receipts & Payments' },
]);

const actionTiles = computed(() => {
    const actions = booking.value?.actions || {};
    return [
        // Next port step: load before departure, hand over on arrival (in transit / arrived follow the journey)
        actions.can_handover
            ? {
                key: 'handover', icon: 'fa-hand-holding', label: 'Hand<br>Over', enabled: true,
                hover: 'hover:bg-emerald-50/60 hover:border-emerald-300 hover:text-emerald-600',
                iconCls: 'bg-emerald-50 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white',
                run: () => { showHandover.value = true; },
            }
            : {
                key: 'load', icon: 'fa-dolly', label: actions.can_load ? 'Mark<br>Loaded' : nextStepLabel.value, enabled: !!actions.can_load,
                hover: 'hover:bg-brand-50/60 hover:border-brand-300 hover:text-brand-600',
                iconCls: 'bg-brand-50 text-brand-600 group-hover:bg-brand-600 group-hover:text-white',
                run: markLoaded,
            },
        {
            key: 'print', icon: 'fa-print', label: 'Print<br>Receipt',
            enabled: !!actions.can_print,
            hover: 'hover:bg-sky-50/60 hover:border-sky-300 hover:text-sky-600',
            iconCls: 'bg-sky-50 text-sky-600 group-hover:bg-sky-600 group-hover:text-white',
            run: printReceipt,
        },
        {
            key: 'track', icon: 'fa-map-marked-alt', label: 'Track<br>Shipment',
            enabled: true,
            hover: 'hover:bg-indigo-50/60 hover:border-indigo-300 hover:text-indigo-600',
            iconCls: 'bg-indigo-50 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white',
            run: showTracking,
        },
        {
            key: 'cancel', icon: 'fa-ban', label: 'Cancel<br>Booking',
            enabled: !!actions.can_cancel,
            hover: 'hover:bg-rose-50/60 hover:border-rose-300 hover:text-rose-600',
            iconCls: 'bg-rose-50 text-rose-600 group-hover:bg-rose-600 group-hover:text-white',
            run: () => { cancelReason.value = ''; showCancelModal.value = true; },
        },
    ];
});

const errorMessage = (e, fallback) => {
    const data = e.response?.data;
    if (data?.errors) return Object.values(data.errors)[0][0];
    return data?.error || data?.message || fallback;
};

const fetchDetails = async () => {
    try {
        const res = await axios.get(`/portal/api/cargo/consignments/${route.params.id}`);
        booking.value = res.data;
        if (res.data.status === 'pending_payment' && !paymentMethods.value.length) {
            paymentMethods.value = (await axios.get('/portal/api/cargo/payment-methods')).data;
        }
    } catch (e) {
        console.error('Failed to fetch booking details', e);
        booking.value = null;
    } finally {
        loading.value = false;
    }
};

const onPaid = async () => {
    flash.value = { type: 'success', message: 'Payment received — booking confirmed.' };
    await fetchDetails();
};

const onCancelled = async () => {
    flash.value = { type: 'success', message: 'Booking has been cancelled and its space released.' };
    await fetchDetails();
};

const printReceipt = () => {
    window.open(`/portal/api/cargo/consignments/${booking.value.id}/print`, '_blank');
};

const showTracking = async () => {
    activeTab.value = 'tracking';
    await nextTick();
    tabsEl.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

// What happens next when this user has no port action here
const nextStepLabel = computed(() => {
    const b = booking.value || {};
    if (b.left_behind_at && b.status === 'booked') return 'Left<br>behind';
    return { loaded: 'Departs with<br>the vessel', in_transit: 'Arrives with<br>the vessel', collected: 'Collected' }[b.status] || 'Next<br>step';
});

const handedOver = async (data) => {
    showHandover.value = false;
    flash.value = data.warnings?.length
        ? { type: 'info', message: `${data.message} Recorded warnings: ${data.warnings.join(' ')}` }
        : { type: 'success', message: data.message };
    await fetchDetails();
};

const submitCancel = async () => {
    submitting.value = true;
    try {
        const res = await axios.post(`/portal/api/cargo/cancel/${booking.value.id}`, { reason: cancelReason.value });
        showCancelModal.value = false;
        flash.value = res.data?.pending
            ? { type: 'info', message: res.data.message } // cancellation awaits a checker (dual control)
            : { type: 'success', message: 'Booking has been cancelled successfully.' };
        await fetchDetails();
    } catch (e) {
        showCancelModal.value = false;
        flash.value = { type: 'error', message: errorMessage(e, 'Cancellation failed. Please try again or contact support.') };
    } finally {
        submitting.value = false;
    }
};

const markLoaded = async () => {
    const b = booking.value;
    const loadOn = [b.transport_service?.transport_unit?.name, departure.value !== 'TBD' && departure.value.replace(' @ ', ' ')].filter(Boolean).join(' · ');
    const ok = await uiStore.confirm({
        title: `Mark ${b.consignment_number} loaded?`,
        message: loadOn ? `On ${loadOn}.` : 'On the vessel for this departure.',
        icon: 'fa-dolly',
        confirmText: 'Mark loaded',
        cancelText: 'Not now',
    });
    if (!ok) return;
    submitting.value = true;
    try {
        const res = await axios.patch(`/portal/api/cargo/consignments/${booking.value.id}/status`, { status: 'loaded' });
        flash.value = { type: 'success', message: res.data.message || 'Marked loaded.' };
        await fetchDetails();
    } catch (e) {
        flash.value = { type: 'error', message: errorMessage(e, 'Could not mark it loaded.') };
    } finally {
        submitting.value = false;
    }
};

onMounted(fetchDetails);
</script>
