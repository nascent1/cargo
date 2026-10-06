<template>
  <div class="space-y-6">
    <!-- Welcome Banner -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="relative px-6 py-5">
        <!-- Subtle pattern -->
        <div class="absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 2px 2px, #e2e8f0 1px, transparent 0); background-size: 24px 24px;"></div>
        <div class="relative flex items-center justify-between">
          <div>
            <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">
              {{ greeting }}, {{ authStore.user?.first_name || 'there' }}
            </h1>
            <p class="text-sm text-slate-500 mt-0.5 font-medium">
              {{ formattedDate }} · <span class="text-brand-600 font-semibold">{{ authStore.userRoleLabel }}</span>
            </p>
          </div>
          <div class="hidden sm:flex items-center gap-2 px-3 py-2 rounded-xl bg-emerald-50 border border-emerald-200">
            <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="text-xs font-bold text-emerald-700">System Online</span>
          </div>
        </div>
      </div>
    </div>

    <!-- KPI Strip -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
      <div v-for="(kpi, idx) in kpiCards" :key="idx"
        class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-3">
          <div class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0"
            :class="kpi.iconBg">
            <i :class="[kpi.icon, kpi.iconColor]" class="text-sm"></i>
          </div>
          <div class="min-w-0">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ kpi.label }}</p>
            <p class="text-lg font-extrabold tracking-tight" :class="kpi.valueColor || 'text-slate-800'">
              <span v-if="isLoading" class="inline-block w-12 h-5 bg-slate-100 rounded animate-pulse"></span>
              <span v-else>{{ kpi.value }}</span>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Book — Route Cards -->
    <div>
      <div class="flex items-center justify-between mb-3">
        <div>
          <h2 class="text-base font-extrabold text-slate-800 tracking-tight">Quick Book — Select a Route</h2>
          <p class="text-xs text-slate-400 font-medium mt-0.5">Pick a route to start a new cargo booking</p>
        </div>
        <button @click="fetchDashboard" class="text-xs text-brand-600 font-semibold hover:text-brand-700 flex items-center gap-1.5">
          <i class="fas fa-sync-alt" :class="{ 'animate-spin': isLoading }"></i>
          Refresh
        </button>
      </div>

      <!-- Loading skeleton -->
      <div v-if="isLoading && routes.length === 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <div v-for="i in 3" :key="i" class="bg-white rounded-2xl border border-slate-200 p-5 animate-pulse">
          <div class="flex items-center gap-3 mb-4">
            <div class="h-10 w-10 rounded-xl bg-slate-100"></div>
            <div class="flex-1">
              <div class="h-4 bg-slate-100 rounded w-3/4 mb-2"></div>
              <div class="h-3 bg-slate-100 rounded w-1/2"></div>
            </div>
          </div>
          <div class="h-2 bg-slate-100 rounded-full mb-3"></div>
          <div class="h-9 bg-slate-100 rounded-xl"></div>
        </div>
      </div>

      <!-- Route cards -->
      <div v-else-if="routes.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <button v-for="route in routes" :key="route.id"
          @click="openQuickBook(route)"
          class="group text-left bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-lg hover:border-brand-300 hover:-translate-y-0.5 transition-all duration-200 cursor-pointer">
          <!-- Route header -->
          <div class="flex items-start justify-between gap-2 mb-3">
            <div class="h-10 w-10 rounded-xl bg-brand-50 border border-brand-100 flex items-center justify-center shrink-0 group-hover:bg-brand-100 transition-colors">
              <i class="fas fa-ship text-brand-600"></i>
            </div>
            <span v-if="route.voyages_today > 0" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
              {{ route.voyages_today }} voyage{{ route.voyages_today > 1 ? 's' : '' }} today
            </span>
            <span v-else class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-50 text-slate-500 border border-slate-200">
              No voyages today
            </span>
          </div>

          <!-- Route name -->
          <h3 class="font-bold text-slate-800 text-sm mb-1">{{ route.name }}</h3>
          <p class="text-xs text-slate-400 mb-3">
            {{ route.origin }} → {{ route.destination }}
            <span v-if="route.next_departure"> · Next: <span class="font-mono font-semibold text-slate-600">{{ route.next_departure }}</span></span>
          </p>

          <!-- Capacity bar -->
          <div v-if="route.capacity_pct !== undefined" class="mb-4">
            <div class="flex items-center justify-between mb-1">
              <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Today's Capacity</span>
              <span class="text-[10px] font-mono font-bold" :class="route.capacity_pct > 80 ? 'text-amber-600' : 'text-slate-500'">{{ route.capacity_pct }}%</span>
            </div>
            <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
              <div class="h-full rounded-full transition-all duration-500"
                :class="route.capacity_pct > 80 ? 'bg-amber-500' : route.capacity_pct > 50 ? 'bg-brand-500' : 'bg-emerald-500'"
                :style="{ width: route.capacity_pct + '%' }">
              </div>
            </div>
          </div>

          <!-- CTA -->
          <div class="flex items-center justify-center gap-2 py-2 rounded-xl bg-brand-50 text-brand-700 text-xs font-bold group-hover:bg-brand-600 group-hover:text-white transition-all">
            <i class="fas fa-plus text-[10px]"></i>
            Book Now
          </div>
        </button>
      </div>

      <!-- No routes -->
      <div v-else class="bg-white rounded-2xl border border-slate-200 shadow-sm p-10 text-center">
        <i class="fas fa-route text-3xl text-slate-300 mb-3 block"></i>
        <p class="text-sm font-semibold text-slate-600">No active routes found</p>
        <p class="text-xs text-slate-400 mt-1">Contact admin to configure routes and schedules.</p>
      </div>
    </div>

    <!-- Recent Consignments -->
    <div>
      <h2 class="text-base font-extrabold text-slate-800 tracking-tight mb-3">Recent Consignments</h2>
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div v-if="recentBookings.length > 0" class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100">
                <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">Booking #</th>
                <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">Date</th>
                <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">Sender</th>
                <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">Route</th>
                <th class="text-right px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">Amount</th>
                <th class="text-center px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="b in recentBookings" :key="b.id"
                @click="$router.push('/cargo-bookings/' + b.id)"
                class="border-b border-slate-50 hover:bg-slate-50 cursor-pointer transition-colors">
                <td class="px-5 py-3 font-mono font-bold text-brand-600 text-xs">{{ b.booking_number }}</td>
                <td class="px-5 py-3 text-slate-500 text-xs">{{ b.date }}</td>
                <td class="px-5 py-3 font-medium text-slate-700 text-xs">{{ b.sender }}</td>
                <td class="px-5 py-3 text-slate-500 text-xs">{{ b.route }}</td>
                <td class="px-5 py-3 text-right font-mono font-bold text-slate-800 text-xs">{{ formatCurrency(b.amount) }}</td>
                <td class="px-5 py-3 text-center">
                  <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                    :class="b.status === 'booked' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-50 text-slate-600 border border-slate-200'">
                    {{ b.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="p-8 text-center">
          <i class="fas fa-inbox text-2xl text-slate-300 mb-2 block"></i>
          <p class="text-sm text-slate-500">No recent consignments</p>
        </div>
      </div>
    </div>

    <!-- Quick Book Modal -->
    <transition name="modal-pop">
      <div v-if="showQuickBookModal" class="fixed inset-0 z-[1000] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4" @click.self="showQuickBookModal = false">
        <div class="bg-white rounded-2xl shadow-2xl shadow-slate-200/50 border border-slate-200 w-full max-w-lg overflow-hidden">
          <!-- Modal Header -->
          <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="font-extrabold text-slate-800">{{ selectedRoute?.name }}</h3>
                <p class="text-xs text-slate-500 mt-0.5">Select a date and check vessel availability</p>
              </div>
              <button @click="showQuickBookModal = false" class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>

          <!-- Date Picker -->
          <div class="p-6 space-y-4">
            <div>
              <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Departure Date</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                  <i class="fas fa-calendar-alt text-brand-500 text-sm"></i>
                </div>
                <input type="date" v-model="quickBookDate" :min="todayStr" @change="checkAvailability"
                  class="w-full pl-10 pr-4 py-3 text-sm font-bold rounded-xl border border-slate-300 bg-white text-slate-900 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/20 outline-none transition-all" />
              </div>
            </div>

            <!-- Availability Results -->
            <div v-if="availLoading" class="py-6 text-center">
              <i class="fas fa-circle-notch fa-spin text-2xl text-brand-400 mb-2 block"></i>
              <p class="text-sm text-slate-500">Checking vessel availability...</p>
            </div>

            <div v-else-if="availServices.length > 0" class="space-y-2">
              <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ availServices.length }} vessel{{ availServices.length > 1 ? 's' : '' }} available</p>
              <button v-for="svc in availServices" :key="svc.id"
                @click="selectServiceAndBook(svc)"
                class="w-full text-left p-4 rounded-xl border-2 transition-all hover:border-brand-500 hover:shadow-md"
                :class="selectedServiceId === svc.id ? 'border-brand-500 bg-brand-50' : 'border-slate-200 bg-white'">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="font-bold text-slate-800 text-sm">{{ svc.transport_unit?.name || svc.transportUnit?.name || 'Vessel' }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">
                      <i class="fas fa-clock text-slate-400 mr-1"></i>{{ svc.departure_time }}
                      <span v-if="svc.cargo_inventory" class="ml-2">
                        <i class="fas fa-weight-hanging text-slate-400 mr-1"></i>{{ svc.cargo_inventory.available_weight_kg?.toLocaleString() || '–' }} KG avail
                      </span>
                    </p>
                  </div>
                  <div v-if="selectedServiceId === svc.id" class="h-6 w-6 rounded-full bg-brand-600 flex items-center justify-center">
                    <i class="fas fa-check text-white text-[10px]"></i>
                  </div>
                  <i v-else class="fas fa-chevron-right text-slate-300"></i>
                </div>
              </button>
            </div>

            <div v-else-if="availChecked" class="py-6 text-center">
              <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-ship text-amber-500"></i>
              </div>
              <p class="text-sm font-bold text-slate-700">No vessels available</p>
              <p class="text-xs text-slate-400 mt-1">Try a different date for this route.</p>
            </div>
          </div>

          <!-- Modal Footer -->
          <div v-if="selectedServiceId" class="px-6 py-4 border-t border-slate-100 bg-slate-50">
            <button @click="goToBooking"
              class="w-full py-3 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm shadow-lg shadow-brand-200/40 transition-all flex items-center justify-center gap-2">
              <i class="fas fa-arrow-right"></i>
              Continue to Booking
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const router = useRouter();

const isLoading = ref(true);
const routes = ref([]);
const recentBookings = ref([]);
const stats = ref({ bookings_today: 0, revenue_today: 0, active_voyages: 0, avg_weight_kg: 0 });

// Quick book modal
const showQuickBookModal = ref(false);
const selectedRoute = ref(null);
const quickBookDate = ref('');
const availLoading = ref(false);
const availChecked = ref(false);
const availServices = ref([]);
const selectedServiceId = ref(null);

let refreshInterval = null;

const todayStr = computed(() => new Date().toISOString().split('T')[0]);

const greeting = computed(() => {
  const h = new Date().getHours();
  if (h < 12) return 'Good morning';
  if (h < 17) return 'Good afternoon';
  return 'Good evening';
});

const formattedDate = computed(() => {
  return new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
});

const kpiCards = computed(() => [
  { label: "Today's Bookings", value: stats.value.bookings_today, icon: 'fas fa-boxes-stacked', iconBg: 'bg-brand-50 border border-brand-100', iconColor: 'text-brand-600' },
  { label: 'Revenue Today', value: formatCurrency(stats.value.revenue_today), icon: 'fas fa-coins', iconBg: 'bg-amber-50 border border-amber-100', iconColor: 'text-amber-600', valueColor: 'text-emerald-700' },
  { label: 'Active Voyages', value: stats.value.active_voyages, icon: 'fas fa-ship', iconBg: 'bg-emerald-50 border border-emerald-100', iconColor: 'text-emerald-600' },
  { label: 'Avg Weight/Shipment', value: stats.value.avg_weight_kg ? stats.value.avg_weight_kg.toLocaleString() + ' KG' : '–', icon: 'fas fa-weight-hanging', iconBg: 'bg-rose-50 border border-rose-100', iconColor: 'text-rose-500' },
]);

function formatCurrency(val) {
  if (!val && val !== 0) return '–';
  return 'TZS ' + Number(val).toLocaleString();
}

async function fetchDashboard() {
  isLoading.value = true;
  try {
    const response = await axios.get('/portal/api/cargo/dashboard-summary');
    if (response.data) {
      stats.value = response.data.kpi || stats.value;
      routes.value = response.data.routes || [];
      recentBookings.value = response.data.recent_consignments || [];
    }
  } catch (err) {
    console.warn('Dashboard API not available, loading routes from services API', err);
    // Fallback: try to load routes directly
    try {
      const routesRes = await axios.get('/portal/api/cargo/availability', { params: { active: 1 } });
      routes.value = (routesRes.data?.data || routesRes.data || []).map(r => ({
        id: r.id,
        name: r.name || `${r.origin_port?.name || 'Origin'} → ${r.destination_port?.name || 'Destination'}`,
        origin: r.origin_port?.name || 'Origin',
        destination: r.destination_port?.name || 'Destination',
        voyages_today: 0,
        next_departure: null,
        capacity_pct: undefined,
      }));
    } catch { /* silent */ }
  } finally {
    isLoading.value = false;
  }
}

function openQuickBook(route) {
  selectedRoute.value = route;
  quickBookDate.value = todayStr.value;
  availServices.value = [];
  availChecked.value = false;
  selectedServiceId.value = null;
  showQuickBookModal.value = true;
  checkAvailability();
}

async function checkAvailability() {
  if (!quickBookDate.value || !selectedRoute.value) return;
  availLoading.value = true;
  availChecked.value = false;
  availServices.value = [];
  selectedServiceId.value = null;

  try {
    const res = await axios.get('/portal/api/cargo/services', {
      params: { route_id: selectedRoute.value.id, date: quickBookDate.value }
    });
    availServices.value = res.data?.data || res.data || [];
  } catch (err) {
    console.error('Availability check failed', err);
    availServices.value = [];
  } finally {
    availLoading.value = false;
    availChecked.value = true;
  }
}

function selectServiceAndBook(svc) {
  selectedServiceId.value = svc.id;
}

function goToBooking() {
  showQuickBookModal.value = false;
  router.push({
    path: '/cargo-booking',
    query: { service_id: selectedServiceId.value, date: quickBookDate.value }
  });
}

onMounted(() => {
  fetchDashboard();
  refreshInterval = setInterval(fetchDashboard, 60000);
});

onUnmounted(() => {
  if (refreshInterval) clearInterval(refreshInterval);
});
</script>

<style scoped>
.modal-pop-enter-active { transition: opacity 0.2s ease; }
.modal-pop-leave-active { transition: opacity 0.15s ease; }
.modal-pop-enter-from, .modal-pop-leave-to { opacity: 0; }
</style>
