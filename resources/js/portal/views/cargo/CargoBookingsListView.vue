<template>
  <div class="space-y-4 max-w-7xl">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-bold text-slate-900">Bookings</h1>
        <p class="text-sm text-slate-600">{{ authStore.isAgent || authStore.isAssociate ? 'The bookings you made.' : 'Search by booking number, phone or name.' }}</p>
      </div>
    </div>

    <!-- Filters (kept in the address bar, so links from the dashboard land filtered) -->
    <form class="ui-card px-4 py-3 flex flex-col sm:flex-row sm:items-end gap-3" role="search" @submit.prevent="apply">
      <div class="flex-1 relative">
        <label class="ui-label" for="bk-search">Search</label>
        <i class="fas fa-magnifying-glass absolute left-3 bottom-3 text-slate-400 text-sm"></i>
        <input id="bk-search" v-model="filters.search" type="search" class="ui-field pl-9" placeholder="Booking number, phone (any format) or name">
      </div>
      <div class="sm:w-52">
        <label class="ui-label" for="bk-status">Status</label>
        <select id="bk-status" v-model="filters.status" class="ui-field" @change="apply">
          <option value="">All statuses</option>
          <option v-for="(s, key) in STATUS" :key="key" :value="key">{{ s.label }}</option>
        </select>
      </div>
      <label v-if="!(authStore.isAgent || authStore.isAssociate)" class="inline-flex items-center gap-2 h-10 text-sm text-slate-700 cursor-pointer select-none">
        <input v-model="filters.mine" type="checkbox" class="h-4 w-4 rounded border-slate-400 accent-brand-600" @change="apply"> Only my bookings
      </label>
      <button type="submit" class="ui-btn"><i class="fas fa-magnifying-glass text-xs"></i> Search</button>
    </form>

    <div class="ui-card overflow-hidden">
      <div v-if="loading" class="py-12 text-center text-sm text-slate-500"><i class="fas fa-circle-notch fa-spin text-brand-500 mr-2"></i>Loading bookings…</div>
      <div v-else-if="error" class="py-12 text-center text-sm text-red-700"><i class="fas fa-circle-exclamation mr-1"></i>{{ error }}</div>
      <div v-else-if="!bookings.length" class="py-12 text-center">
        <i class="fas fa-box-open text-3xl text-slate-300 block mb-2"></i>
        <p class="text-sm font-semibold text-slate-700">No bookings found</p>
        <p class="text-xs text-slate-500 mt-1">Try another search or clear the filters.</p>
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="th">Booking</th><th class="th">Booked</th><th class="th">Sender → Receiver</th><th class="th">Route</th>
              <th class="th">Status</th><th class="th">Payment</th><th class="th th-r">Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="b in bookings" :key="b.id" class="hover:bg-slate-50 cursor-pointer" @click="$router.push(`/cargo-bookings/${b.id}`)">
              <td class="td whitespace-nowrap">
                <router-link :to="`/cargo-bookings/${b.id}`" class="font-mono font-semibold text-brand-700 hover:underline" @click.stop>{{ b.consignment_number }}</router-link>
              </td>
              <td class="td text-slate-600 whitespace-nowrap">{{ dateTime(b.created_at) }}</td>
              <td class="td">
                <div class="font-medium text-slate-800">{{ b.sender_name }}</div>
                <div class="text-xs text-slate-500"><i class="fas fa-arrow-right text-[9px] mr-1"></i>{{ b.receiver_name }}</div>
              </td>
              <td class="td text-slate-600">{{ b.route?.name || b.transport_service?.route?.name || '—' }}</td>
              <td class="td"><StatusBadge kind="status" :value="b.status" /></td>
              <td class="td"><StatusBadge kind="payment" :value="b.payment_status" /></td>
              <td class="td text-right font-semibold tabular-nums whitespace-nowrap">{{ amount(b) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="meta.last_page > 1" class="px-4 py-3 border-t border-slate-100 flex items-center justify-between gap-3 text-sm">
        <span class="text-slate-600">Page {{ meta.current_page }} of {{ meta.last_page }} · {{ num(meta.total) }} bookings</span>
        <div class="flex gap-2">
          <button type="button" class="ui-btn ui-btn-sm" :disabled="meta.current_page <= 1" @click="go(meta.current_page - 1)"><i class="fas fa-chevron-left text-[10px]"></i> Previous</button>
          <button type="button" class="ui-btn ui-btn-sm" :disabled="meta.current_page >= meta.last_page" @click="go(meta.current_page + 1)">Next <i class="fas fa-chevron-right text-[10px]"></i></button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';
import { num, money, dateTime, apiError } from '../../utils/cargoUi';
import StatusBadge from '../../components/ui/StatusBadge.vue';

const STATUS = window.CargoUiConfig?.status || {};
const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();

const bookings = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(false);
const error = ref('');
const filters = reactive({ search: '', status: '', mine: false });

const amount = b => Number(b.total_amount_usd) > 0 ? money(b.total_amount_usd, 'USD') : money(b.total_amount_tzs);

// The address bar is the source of truth: ?search=&status=&mine=1&page=
function apply() {
  router.replace({ query: {
    ...(filters.search.trim() ? { search: filters.search.trim() } : {}),
    ...(filters.status ? { status: filters.status } : {}),
    ...(filters.mine ? { mine: 1 } : {}),
  } });
}
const go = page => router.replace({ query: { ...route.query, page } });

async function load() {
  if (route.name !== 'CargoBookings') return; // leaving for a booking page
  filters.search = route.query.search || '';
  filters.status = route.query.status || '';
  filters.mine = !!route.query.mine;
  loading.value = true;
  try {
    const { data } = await axios.get('/portal/api/cargo/consignments', { params: route.query });
    bookings.value = data.data;
    meta.value = { current_page: data.current_page, last_page: data.last_page, total: data.total };
    error.value = '';
  } catch (e) {
    error.value = apiError(e, "Couldn't load bookings.").message;
  } finally {
    loading.value = false;
  }
}

watch(() => route.query, load, { immediate: true });
</script>

<style scoped>
.th { text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.025em; padding: 0.625rem 1rem; white-space: nowrap; }
.th-r { text-align: right !important; }
.td { padding: 0.75rem 1rem; color: #334155; border-top: 1px solid #f1f5f9; }
</style>
