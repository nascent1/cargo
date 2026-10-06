<template>
  <div class="bg-white/90 border border-slate-200/60 rounded-2xl shadow-sm overflow-hidden">
    <!-- Header -->
    <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200/40">
      <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
        <i class="fas fa-ship text-brand-600 text-xs"></i>
        Live Departures
      </h3>
      <span class="text-[10px] font-mono text-slate-400">Auto-refresh 60s</span>
    </div>

    <!-- Empty State -->
    <div v-if="!voyages.length" class="px-5 py-10 text-center">
      <div class="w-12 h-12 rounded-2xl bg-slate-100/60 border border-slate-200/40 flex items-center justify-center mx-auto mb-3 text-slate-400">
        <i class="fas fa-anchor text-lg"></i>
      </div>
      <p class="text-sm text-slate-500 font-medium">No upcoming departures</p>
    </div>

    <!-- Voyage Table -->
    <div v-else class="overflow-x-auto">
      <table class="w-full text-left">
        <thead>
          <tr class="bg-slate-50/50 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200/40">
            <th class="px-5 py-2.5">Vessel</th>
            <th class="px-5 py-2.5">Route</th>
            <th class="px-5 py-2.5">Departs</th>
            <th class="px-5 py-2.5">Capacity</th>
            <th class="px-5 py-2.5">Avail</th>
            <th class="px-5 py-2.5 text-right">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <template v-for="voyage in voyages" :key="voyage.voyage_id">
            <!-- Main Row -->
            <tr class="hover:bg-slate-50/60 transition-colors cursor-pointer group"
                @click="toggleExpand(voyage.voyage_id)">
              <!-- Vessel -->
              <td class="px-5 py-3">
                <div class="flex items-center gap-2">
                  <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 text-[10px]"
                       :class="voyage.status === 'boarding'
                         ? 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20'
                         : 'bg-brand-500/10 text-brand-600 border border-brand-500/20'">
                    <i class="fas fa-ship"></i>
                  </div>
                  <div>
                    <div class="text-xs font-bold text-slate-800">{{ voyage.vessel }}</div>
                    <div v-if="voyage.status === 'boarding'" class="text-[9px] font-bold text-emerald-600 uppercase tracking-wider">Boarding</div>
                  </div>
                </div>
              </td>

              <!-- Route -->
              <td class="px-5 py-3 text-xs text-slate-600 font-medium">{{ voyage.route }}</td>

              <!-- Departure Time + Countdown -->
              <td class="px-5 py-3">
                <div class="text-sm font-bold font-mono text-slate-800">{{ voyage.departure_time }}</div>
                <div class="text-[10px] font-medium mt-0.5"
                     :class="voyage.countdown_minutes <= 60 ? 'text-rose-500' : voyage.countdown_minutes <= 120 ? 'text-amber-500' : 'text-slate-400'">
                  {{ formatCountdown(voyage.countdown_minutes) }}
                </div>
              </td>

              <!-- Capacity Bar -->
              <td class="px-5 py-3">
                <div class="w-24">
                  <div class="flex items-center justify-between text-[10px] mb-1">
                    <span class="font-bold" :class="loadColor(voyage.load_percent)">{{ voyage.load_percent }}%</span>
                    <span class="text-slate-400">{{ voyage.sold }}/{{ voyage.total_capacity }}</span>
                  </div>
                  <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-700 ease-out"
                         :class="loadBarColor(voyage.load_percent)"
                         :style="{ width: voyage.load_percent + '%' }">
                    </div>
                  </div>
                </div>
              </td>

              <!-- Available Seats -->
              <td class="px-5 py-3">
                <span class="text-sm font-bold font-mono" :class="voyage.available <= 5 ? 'text-rose-500' : voyage.available <= 20 ? 'text-amber-500' : 'text-emerald-600'">
                  {{ voyage.available }}
                </span>
                <span class="text-[10px] text-slate-400 ml-0.5">seats</span>
              </td>

              <!-- Action -->
              <td class="px-5 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <router-link
                    :to="`/fast-booking?voyage_id=${voyage.voyage_id}&route_id=${voyage.route_id}&date=${voyage.departure_date}`"
                    @click.stop
                    class="px-3.5 py-1.5 rounded-lg bg-brand-500 text-white hover:bg-brand-400 text-[11px] font-bold uppercase shadow-sm transition-all flex items-center gap-1.5">
                    <i class="fas fa-ticket-alt text-[9px]"></i>
                    Book
                  </router-link>
                  <button @click.stop="toggleExpand(voyage.voyage_id)"
                          class="w-7 h-7 rounded-lg bg-slate-100 text-slate-400 hover:text-slate-600 hover:bg-slate-200 flex items-center justify-center transition-all text-[10px]">
                    <i class="fas" :class="expandedId === voyage.voyage_id ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                  </button>
                </div>
              </td>
            </tr>

            <!-- Expanded Class Breakdown -->
            <tr v-if="expandedId === voyage.voyage_id && voyage.class_breakdown?.length">
              <td colspan="6" class="px-5 py-0">
                <div class="py-3 pl-9">
                  <div class="bg-slate-50/80 border border-slate-200/40 rounded-xl overflow-hidden">
                    <table class="w-full text-left">
                      <thead>
                        <tr class="text-[9px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200/30">
                          <th class="px-4 py-2">Class</th>
                          <th class="px-4 py-2 text-center">Total</th>
                          <th class="px-4 py-2 text-center">Sold</th>
                          <th class="px-4 py-2 text-center">Held</th>
                          <th class="px-4 py-2 text-center">Avail</th>
                          <th class="px-4 py-2"></th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-slate-200/30">
                        <tr v-for="cls in voyage.class_breakdown" :key="cls.class_id" class="text-xs">
                          <td class="px-4 py-2 font-semibold text-slate-700">{{ cls.class }}</td>
                          <td class="px-4 py-2 text-center text-slate-500">{{ cls.total }}</td>
                          <td class="px-4 py-2 text-center text-slate-600 font-semibold">{{ cls.sold }}</td>
                          <td class="px-4 py-2 text-center text-amber-500 font-semibold">{{ cls.held || 0 }}</td>
                          <td class="px-4 py-2 text-center font-bold font-mono" :class="cls.available <= 2 ? 'text-rose-500' : cls.available <= 10 ? 'text-amber-500' : 'text-emerald-600'">
                            {{ cls.available }}
                            <span v-if="cls.available <= 2" class="ml-1 text-[8px] align-middle">🔴</span>
                            <span v-else-if="cls.available <= 10" class="ml-1 text-[8px] align-middle">🟡</span>
                            <span v-else class="ml-1 text-[8px] align-middle">🟢</span>
                          </td>
                          <td class="px-4 py-2 text-right">
                            <router-link v-if="cls.available > 0"
                              :to="`/fast-booking?voyage_id=${voyage.voyage_id}&route_id=${voyage.route_id}&date=${voyage.departure_date}`"
                              class="px-2.5 py-1 rounded-md bg-brand-500/10 text-brand-600 hover:bg-brand-500/20 text-[10px] font-bold transition-colors">
                              Book
                            </router-link>
                            <span v-else class="text-[10px] text-rose-400 font-semibold">FULL</span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
  voyages: { type: Array, default: () => [] },
});

const expandedId = ref(null);

function toggleExpand(id) {
  expandedId.value = expandedId.value === id ? null : id;
}

function formatCountdown(minutes) {
  if (minutes <= 0) return 'Departed';
  if (minutes < 60) return `${minutes}m`;
  const h = Math.floor(minutes / 60);
  const m = minutes % 60;
  return m > 0 ? `${h}h ${m}m` : `${h}h`;
}

function loadColor(percent) {
  if (percent >= 95) return 'text-rose-500';
  if (percent >= 85) return 'text-amber-500';
  if (percent >= 60) return 'text-brand-600';
  return 'text-emerald-600';
}

function loadBarColor(percent) {
  if (percent >= 95) return 'bg-rose-500';
  if (percent >= 85) return 'bg-amber-400';
  if (percent >= 60) return 'bg-brand-500';
  return 'bg-emerald-500';
}
</script>
