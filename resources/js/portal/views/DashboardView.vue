<template>
  <div class="space-y-5 max-w-7xl">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-bold text-slate-900">{{ greeting }}, {{ authStore.user?.first_name || 'there' }}</h1>
        <p class="text-sm text-slate-600">{{ todayText }} · your bookings and payments today</p>
      </div>
      <button type="button" @click="load" class="ui-btn ui-btn-sm" :disabled="loading">
        <i class="fas fa-rotate-right" :class="{ 'fa-spin': loading }"></i> Refresh
      </button>
    </div>

    <div v-if="error" class="rounded-xl px-4 py-3 text-sm bg-red-50 border border-red-200 text-red-800" role="alert">
      <i class="fas fa-circle-exclamation mr-1"></i>{{ error }}
    </div>

    <ShiftBar />

    <!-- My day -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="ui-card p-4">
        <p class="text-xs font-semibold text-slate-500">Bookings made</p>
        <p class="text-2xl font-bold text-slate-900 mt-1 tabular-nums">{{ tiles ? tiles.bookings.today : '–' }}</p>
        <p class="text-xs text-slate-500 mt-1">{{ vsYesterday }}</p>
      </div>
      <div class="ui-card p-4">
        <p class="text-xs font-semibold text-slate-500">Money collected</p>
        <p class="text-2xl font-bold text-slate-900 mt-1 tabular-nums whitespace-nowrap"><span class="text-sm font-semibold text-slate-500">TZS</span> {{ tiles ? num(tiles.collected.total) : '–' }}</p>
        <p class="text-xs text-slate-500 mt-1">Cash {{ num(tiles?.collected.cash) }} · Mobile {{ num(tiles?.collected.mobile) }}</p>
      </div>
      <router-link :to="{ path: '/cargo-bookings', query: { status: 'pending_payment', mine: 1 } }"
        class="ui-card p-4 block transition-colors" :class="tiles?.awaiting.count ? 'border-amber-300 bg-amber-50/60 hover:bg-amber-50' : 'hover:bg-slate-50'">
        <p class="text-xs font-semibold" :class="tiles?.awaiting.count ? 'text-amber-800' : 'text-slate-500'"><i class="fas fa-hourglass-half mr-1"></i>Awaiting payment</p>
        <p class="text-2xl font-bold text-slate-900 mt-1 tabular-nums">{{ tiles ? tiles.awaiting.count : '–' }}
          <span v-if="tiles?.awaiting.count" class="text-sm font-semibold text-slate-500">· TZS {{ num(tiles.awaiting.value) }}</span></p>
        <p class="text-xs mt-1 font-semibold" :class="tiles?.awaiting.count ? 'text-amber-800' : 'text-slate-500'">{{ tiles?.awaiting.count ? 'Take payment →' : 'Nothing unpaid' }}</p>
      </router-link>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
      <!-- Next departures -->
      <section class="ui-card lg:col-span-2 overflow-hidden">
        <div class="ui-card-head">
          <h2 class="font-semibold text-slate-900">Next departures</h2>
          <span class="text-xs text-slate-500 hidden sm:inline">Space left includes held space</span>
        </div>
        <div v-if="!departureGroups.length" class="px-4 py-10 text-center text-sm text-slate-500">
          <i class="fas fa-ship text-2xl text-slate-300 block mb-2"></i>No departures open for booking today or tomorrow.
        </div>
        <div v-for="g in departureGroups" :key="g.date">
          <p class="ui-day">{{ g.label }}</p>
          <div v-for="d in g.items" :key="d.id" class="px-4 py-2.5 flex items-center gap-4 border-t border-slate-100">
            <span class="font-mono font-semibold text-slate-900 w-12 shrink-0">{{ d.time }}</span>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-semibold text-slate-900 truncate">{{ d.route }}</p>
              <p class="text-xs text-slate-500 truncate">{{ d.vessel }}</p>
            </div>
            <SpaceBar :space="d.space" class="w-40 hidden sm:block" />
            <button v-if="authStore.can('cargo-book')" type="button" class="ui-btn ui-btn-sm shrink-0" :disabled="d.space?.full" @click="book(d)">
              {{ d.space?.full ? 'Full' : 'Book' }}
            </button>
          </div>
        </div>
      </section>

      <!-- Needs your action -->
      <section class="ui-card overflow-hidden">
        <div class="ui-card-head"><h2 class="font-semibold text-slate-900">Needs your action</h2></div>
        <p v-if="!action.length" class="px-4 py-8 text-center text-sm text-slate-500"><i class="fas fa-circle-check text-emerald-600 mr-1"></i>Nothing waiting on you.</p>
        <ul class="divide-y divide-slate-100 text-sm">
          <li v-for="a in action" :key="a.id" class="px-4 py-3">
            <div class="flex justify-between gap-3">
              <span class="font-mono font-semibold text-brand-700">{{ a.consignment_number }}</span>
              <span class="font-semibold tabular-nums whitespace-nowrap">{{ money(a.amount, a.currency) }}</span>
            </div>
            <p class="text-xs text-slate-600 mt-0.5">{{ a.note }} · {{ timeAgo(a.since) }}</p>
            <router-link :to="`/cargo-bookings/${a.id}`" class="ui-btn ui-btn-sm mt-2">Take payment</router-link>
          </li>
        </ul>
      </section>
    </div>

    <!-- My recent bookings -->
    <section class="ui-card overflow-hidden">
      <div class="ui-card-head">
        <h2 class="font-semibold text-slate-900">My recent bookings</h2>
        <router-link v-if="authStore.can('booking:read')" :to="{ path: '/cargo-bookings', query: { mine: 1 } }" class="ui-link">All my bookings →</router-link>
      </div>
      <p v-if="!recent.length" class="px-4 py-8 text-center text-sm text-slate-500">You haven't made any bookings yet.</p>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="th">Booking</th><th class="th">Sender → Receiver</th><th class="th">Departure</th>
              <th class="th">Status</th><th class="th">Payment</th><th class="th th-r">Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="b in recent" :key="b.id" class="hover:bg-slate-50 cursor-pointer" @click="$router.push(`/cargo-bookings/${b.id}`)">
              <td class="td font-mono font-semibold text-brand-700 whitespace-nowrap">{{ b.consignment_number }}</td>
              <td class="td">{{ b.sender_name }} <span class="text-slate-400">→</span> {{ b.receiver_name }}</td>
              <td class="td text-slate-600 whitespace-nowrap">{{ departureText(b.departure) }}</td>
              <td class="td"><StatusBadge kind="status" :value="b.status" /></td>
              <td class="td"><StatusBadge kind="payment" :value="b.payment_status" /></td>
              <td class="td text-right font-semibold tabular-nums whitespace-nowrap">{{ money(b.amount, b.currency) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';
import { num, money, dayLabel, timeAgo, apiError } from '../utils/cargoUi';
import StatusBadge from '../components/ui/StatusBadge.vue';
import SpaceBar from '../components/cargo/SpaceBar.vue';
import ShiftBar from '../components/shift/ShiftBar.vue';
import { useShiftStore } from '../stores/shift';

const authStore = useAuthStore();
const shiftStore = useShiftStore();
const router = useRouter();

const loading = ref(false);
const error = ref('');
const tiles = ref(null);
const departures = ref([]);
const action = ref([]);
const recent = ref([]);
let timer = null;

const greeting = computed(() => {
  const h = new Date().getHours();
  return h < 12 ? 'Good morning' : h < 17 ? 'Good afternoon' : 'Good evening';
});
const todayText = new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' });

const vsYesterday = computed(() => {
  if (!tiles.value) return '';
  const diff = tiles.value.bookings.today - tiles.value.bookings.yesterday;
  if (!tiles.value.bookings.yesterday && !tiles.value.bookings.today) return 'None yesterday either';
  return diff === 0 ? 'Same as yesterday' : `${Math.abs(diff)} ${diff > 0 ? 'more' : 'fewer'} than yesterday`;
});

const departureGroups = computed(() => {
  const groups = [];
  departures.value.forEach(d => {
    let g = groups.find(x => x.date === d.date);
    if (!g) groups.push(g = { date: d.date, label: dayLabel(d.date), items: [] });
    g.items.push(d);
  });
  return groups;
});

const departureText = v => {
  if (!v) return '—';
  const [date, time] = v.split(' ');
  return `${dayLabel(date, true)} ${time || ''}`;
};

function book(d) {
  router.push({ path: '/cargo-booking', query: { service_id: d.id, date: d.date } });
}

async function load() {
  loading.value = true;
  try {
    if (shiftStore.applies) shiftStore.refresh();
    const { data } = await axios.get('/portal/api/cargo/dashboard-summary');
    tiles.value = data.tiles;
    departures.value = data.departures || [];
    action.value = data.action || [];
    recent.value = data.recent || [];
    error.value = '';
  } catch (e) {
    error.value = apiError(e, "Couldn't load your day. Check the connection and press Refresh.").message;
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  load();
  timer = setInterval(load, 60000);
});
onUnmounted(() => clearInterval(timer));
</script>

<style scoped>
.th-r { text-align: right !important; }
.th { text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.025em; padding: 0.625rem 1rem; white-space: nowrap; }
.td { padding: 0.75rem 1rem; color: #334155; border-top: 1px solid #f1f5f9; }
</style>
