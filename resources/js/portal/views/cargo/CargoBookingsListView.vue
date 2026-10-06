<template>
  <div class="min-h-screen bg-slate-50">

    <!-- Header -->
    <div class="bg-white border-b border-slate-200 px-4 py-4 sticky top-0 z-10 shadow-sm">
      <div class="max-w-5xl mx-auto flex items-center justify-between gap-3">
        <div>
          <h1 class="text-lg font-extrabold text-slate-900">My Consignments</h1>
          <p class="text-xs text-slate-400 mt-0.5">Track and manage your cargo shipments</p>
        </div>
        <router-link to="/cargo-booking"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm shadow-brand-200">
          <i class="fas fa-plus text-xs"></i> New Booking
        </router-link>
      </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 py-5 space-y-4">

      <!-- Search & Filter -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm px-5 py-4">
        <div class="flex flex-col sm:flex-row gap-3">
          <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <i class="fas fa-search text-slate-400 text-sm"></i>
            </div>
            <input type="text" v-model="search" @keyup.enter="fetchBookings"
              placeholder="Search by consignment number, sender or receiver..."
              class="w-full pl-9 pr-4 py-2.5 text-sm rounded-xl border border-slate-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-slate-50 focus:bg-white" />
          </div>
          <select v-model="statusFilter" @change="fetchBookings"
            class="text-sm rounded-xl border border-slate-200 px-3 py-2.5 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-slate-50 text-slate-700 min-w-[160px]">
            <option value="">All Statuses</option>
            <option value="pending_payment">Awaiting Payment</option>
            <option value="booked">Booked</option>
            <option value="loaded">Loaded</option>
            <option value="in_transit">In Transit</option>
            <option value="delivered">Delivered</option>
            <option value="collected">Collected</option>
            <option value="cancelled">Cancelled</option>
          </select>
          <button @click="fetchBookings"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold rounded-xl transition-colors">
            <i class="fas fa-filter text-xs"></i> Filter
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-12 text-center text-slate-400">
        <i class="fas fa-circle-notch fa-spin text-2xl text-brand-400 mb-3 block"></i>
        <p class="text-sm">Loading your consignments...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="bookings.length === 0"
        class="bg-white rounded-2xl border border-slate-200 shadow-sm p-12 text-center">
        <i class="fas fa-box-open text-4xl text-slate-300 mb-4 block"></i>
        <p class="text-slate-700 font-semibold">No consignments found</p>
        <p class="text-sm text-slate-400 mt-1">Try adjusting your search filters or create a new booking.</p>
        <router-link to="/cargo-booking"
          class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 bg-brand-600 text-white text-sm font-semibold rounded-xl hover:bg-brand-700 transition-colors">
          <i class="fas fa-plus text-xs"></i> New Booking
        </router-link>
      </div>

      <!-- Table -->
      <div v-else class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-100">
                <th class="text-left px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Consignment</th>
                <th class="text-left px-4 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Date</th>
                <th class="text-left px-4 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Sender → Receiver</th>
                <th class="text-left px-4 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Route</th>
                <th class="text-left px-4 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                <th class="text-right px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Amount</th>
                <th class="text-center px-4 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="booking in bookings" :key="booking.id"
                class="border-b border-slate-50 hover:bg-brand-50/30 transition-colors">
                <td class="px-5 py-3.5">
                  <router-link :to="`/cargo-bookings/${booking.id}`"
                    class="font-mono text-xs font-bold text-brand-600 bg-brand-50 hover:bg-brand-100 px-2 py-0.5 rounded transition-colors">
                    {{ booking.consignment_number }}
                  </router-link>
                </td>
                <td class="px-4 py-3.5 text-xs text-slate-500">{{ formatDate(booking.created_at) }}</td>
                <td class="px-4 py-3.5">
                  <div class="font-medium text-slate-800 text-sm">{{ booking.sender_name }}</div>
                  <div class="text-xs text-slate-400 flex items-center gap-1 mt-0.5">
                    <i class="fas fa-arrow-right text-[9px]"></i> {{ booking.receiver_name }}
                  </div>
                </td>
                <td class="px-4 py-3.5 text-xs text-slate-500">{{ booking.route?.name || '—' }}</td>
                <td class="px-4 py-3.5">
                  <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border', statusCls(booking.status)]">
                    {{ statusLabel(booking.status) }}
                  </span>
                </td>
                <td class="px-5 py-3.5 text-right font-semibold tabular-nums text-slate-800">
                  {{ booking.total_amount_usd > 0
                    ? booking.total_amount_usd.toLocaleString() + ' USD'
                    : Number(booking.total_amount_tzs).toLocaleString() + ' TZS' }}
                </td>
                <td class="px-4 py-3.5 text-center">
                  <router-link :to="`/cargo-bookings/${booking.id}`"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-brand-600 bg-brand-50 hover:bg-brand-100 rounded-lg transition-colors border border-brand-100">
                    <i class="fas fa-eye text-xs"></i> View
                  </router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import moment from 'moment';

const bookings     = ref([]);
const loading      = ref(false);
const search       = ref('');
const statusFilter = ref('');

const STATUS = {
    pending_payment: { label: 'Awaiting Payment', cls: 'bg-orange-100 text-orange-700 border-orange-200' },
    booked:     { label: 'Booked',     cls: 'bg-amber-100 text-amber-700 border-amber-200' },
    loaded:     { label: 'Loaded',     cls: 'bg-blue-100 text-blue-700 border-blue-200' },
    in_transit: { label: 'In Transit', cls: 'bg-violet-100 text-violet-700 border-violet-200' },
    delivered:  { label: 'Delivered',  cls: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
    collected:  { label: 'Collected',  cls: 'bg-teal-100 text-teal-700 border-teal-200' },
    cancelled:  { label: 'Cancelled',  cls: 'bg-red-100 text-red-700 border-red-200' },
};

const statusLabel = (s) => STATUS[s]?.label || s;
const statusCls   = (s) => STATUS[s]?.cls || 'bg-slate-100 text-slate-600 border-slate-200';

const fetchBookings = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (search.value)       params.append('search', search.value);
        if (statusFilter.value) params.append('status', statusFilter.value);
        const res = await axios.get(`/portal/api/cargo/consignments?${params.toString()}`);
        bookings.value = res.data.data;
    } catch (e) {
        console.error('Failed to fetch bookings', e);
    } finally {
        loading.value = false;
    }
};

const formatDate = (date) => moment(date).format('D MMM YYYY');

onMounted(fetchBookings);
</script>
