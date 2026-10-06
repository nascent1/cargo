<template>
  <div v-if="items.length > 0" class="bg-white/90 border border-slate-200/60 rounded-2xl shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200/40">
      <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
        <span class="w-2 h-2 rounded-full animate-pulse" :class="hasCritical ? 'bg-rose-500' : 'bg-amber-400'"></span>
        Attention Required
      </h3>
      <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">{{ items.length }} item{{ items.length !== 1 ? 's' : '' }}</span>
    </div>
    <div class="divide-y divide-slate-100">
      <div v-for="(item, index) in items" :key="index"
           class="px-5 py-3 flex items-start gap-3 hover:bg-slate-50/60 transition-colors group">
        <!-- Severity Icon -->
        <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 mt-0.5"
             :class="severityClasses(item.severity)">
          <i class="text-xs" :class="severityIcon(item.type)"></i>
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
          <p class="text-sm text-slate-700 font-medium leading-snug">{{ item.message }}</p>

          <!-- Cross-cancellation details -->
          <div v-if="item.type === 'cross_cancellation' && item.details?.length" class="mt-1.5 space-y-1">
            <div v-for="(detail, di) in item.details.slice(0, 3)" :key="di" class="flex items-center gap-2 text-[11px] text-slate-500">
              <span class="font-mono font-semibold text-slate-600">{{ detail.booking_ref }}</span>
              <span class="text-slate-300">·</span>
              <span>{{ detail.passenger }}</span>
              <span class="text-slate-300">·</span>
              <span class="text-rose-500">by {{ detail.cancelled_by }}</span>
              <span class="text-slate-300">·</span>
              <span class="font-mono">{{ detail.at }}</span>
            </div>
          </div>

          <!-- Resolved DC details -->
          <div v-if="item.type === 'dc_resolved'" class="mt-1 text-[11px] text-slate-400 font-mono">
            {{ item.resolved_at }}
          </div>
        </div>

        <!-- Action Button -->
        <router-link v-if="item.action_url"
          :to="item.action_url"
          class="px-3 py-1.5 rounded-lg text-[11px] font-bold uppercase tracking-wide shrink-0 transition-all opacity-80 group-hover:opacity-100"
          :class="item.type === 'capacity_alert'
            ? 'bg-brand-500 text-white hover:bg-brand-400 shadow-sm'
            : 'bg-slate-100 text-slate-600 hover:bg-slate-200 border border-slate-200/60'">
          <i class="fas fa-arrow-right mr-1 text-[9px]"></i>
          {{ item.type === 'capacity_alert' ? 'Book' : 'View' }}
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  items: { type: Array, default: () => [] },
});

const hasCritical = computed(() => props.items.some(i => i.severity === 'critical'));

function severityClasses(severity) {
  switch (severity) {
    case 'critical': return 'bg-rose-500/10 text-rose-500 border border-rose-500/20';
    case 'warning':  return 'bg-amber-500/10 text-amber-600 border border-amber-500/20';
    case 'success':  return 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20';
    default:         return 'bg-sky-500/10 text-sky-600 border border-sky-500/20';
  }
}

function severityIcon(type) {
  switch (type) {
    case 'capacity_alert':     return 'fas fa-ship';
    case 'cross_cancellation': return 'fas fa-user-xmark';
    case 'dc_pending':         return 'fas fa-clock';
    case 'dc_resolved':        return 'fas fa-check-double';
    case 'expiring_hold':      return 'fas fa-hourglass-half';
    default:                   return 'fas fa-bell';
  }
}
</script>
