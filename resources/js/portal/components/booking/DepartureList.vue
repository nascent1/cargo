<template>
  <section class="ui-card overflow-hidden">
    <div class="px-4 py-3 flex flex-wrap items-center gap-2 border-b border-slate-100">
      <button type="button" class="ui-chip" :class="!cargo.routeFilter && 'ui-chip-on'" @click="cargo.setRoute(null)">All routes</button>
      <button v-for="r in cargo.routeChoices" :key="r.id" type="button" class="ui-chip" :class="cargo.routeFilter?.id === r.id && 'ui-chip-on'" @click="cargo.setRoute(r)">
        <i v-if="cargo.routeFilter?.id === r.id" class="fas fa-check text-[10px]"></i>{{ r.name }}
      </button>
      <div class="ml-auto flex items-center gap-2">
        <label for="bk-other-date" class="text-xs text-slate-500">Other date</label>
        <input id="bk-other-date" type="date" v-model="cargo.otherDate" :min="today" @change="cargo.loadDepartures()" class="ui-field w-auto h-9">
        <button v-if="cargo.otherDate" type="button" class="ui-link text-xs" @click="cargo.otherDate = ''; cargo.loadDepartures()">Next days</button>
        <button type="button" class="ui-btn h-9 w-9 px-0" title="Refresh" aria-label="Refresh departures" @click="cargo.loadDepartures()"><i class="fas fa-rotate-right text-xs"></i></button>
      </div>
    </div>

    <div v-if="cargo.loadingDepartures" class="py-12 text-center text-sm text-slate-500"><i class="fas fa-circle-notch fa-spin text-brand-500 mr-2"></i>Loading departures…</div>
    <div v-else-if="!hasDepartures" class="py-12 text-center text-sm text-slate-500">
      <i class="fas fa-ship text-3xl text-slate-300 block mb-2"></i>
      {{ cargo.otherDate ? 'No departures open for booking on this date.' : `No departures open for booking in the next ${cargo.days} days${cargo.routeFilter ? ' on this route' : ''}.` }}
    </div>
    <div v-else class="pb-2">
      <div v-for="g in cargo.departureGroups" :key="g.date">
        <p class="ui-day">{{ dayLabel(g.date) }}<span v-if="!g.services.length" class="normal-case font-normal tracking-normal text-slate-400"> — no more departures today</span></p>
        <div class="px-3 space-y-0.5">
          <button v-for="s in g.services" :key="s.id" type="button" class="ui-departure" :disabled="s.space?.full || !s.priced_type_ids?.length"
            :class="cargo.service?.id === s.id && 'bg-brand-50 ring-1 ring-brand-300'" @click="cargo.selectService(s)">
            <span class="font-mono font-semibold text-slate-900 w-12 shrink-0">{{ (s.departure_time || '').slice(0, 5) }}</span>
            <span class="flex-1 min-w-0">
              <span class="block text-sm font-semibold text-slate-900 truncate">{{ s.route?.name }}</span>
              <span class="block text-xs text-slate-500 truncate">{{ s.transport_unit?.name }}<span class="sm:hidden" :class="spaceInfo(s.space).cls"> · {{ spaceInfo(s.space).text }}</span></span>
            </span>
            <SpaceBar :space="s.space" class="w-48 shrink-0 hidden sm:block" />
            <span class="w-16 shrink-0 text-right text-sm font-semibold" :class="s.space?.full || !s.priced_type_ids?.length ? 'text-slate-400' : 'text-brand-700'"
              :title="!s.priced_type_ids?.length ? 'No cargo fares are set for this route' : ''">{{ s.space?.full ? 'Full' : (!s.priced_type_ids?.length ? 'No fares' : 'Select ›') }}</span>
          </button>
        </div>
      </div>
    </div>
    <div v-if="!cargo.loadingDepartures && !cargo.otherDate && cargo.days < 14" class="px-4 py-3 border-t border-slate-100">
      <button type="button" class="ui-link" @click="cargo.days = 14; cargo.loadDepartures()"><i class="fas fa-chevron-down mr-1 text-xs"></i>Later dates (up to 2 weeks ahead)</button>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue';
import { useCargoStore } from '../../stores/cargo';
import { dayLabel, isoDate, spaceInfo } from '../../utils/cargoUi';
import SpaceBar from '../cargo/SpaceBar.vue';

// Step 1: the next few days of departures in one list, narrowed by route; one click picks one
const cargo = useCargoStore();
const today = isoDate();
const hasDepartures = computed(() => cargo.departureGroups.some(g => g.services.length));
</script>
