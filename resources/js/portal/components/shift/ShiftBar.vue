<template>
  <section v-if="shiftStore.applies && shiftStore.status !== 'none'" class="ui-card p-4">
    <!-- Not started -->
    <div v-if="shiftStore.status === 'not_started'" class="flex items-center gap-3 text-sm text-slate-600">
      <span class="h-9 w-9 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0"><i class="fas fa-clock"></i></span>
      You're not on shift. A new shift starts when you sign in, or with your next booking, and lasts {{ hoursText(shiftStore.context.shift_hours) }} h.
    </div>

    <!-- On shift -->
    <div v-else-if="shiftStore.status === 'active'" class="flex flex-wrap items-center gap-x-6 gap-y-3">
      <div class="flex items-center gap-3">
        <span class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0" :class="low ? 'bg-red-50 text-red-600' : 'bg-brand-50 text-brand-700'"><i class="fas fa-clock"></i></span>
        <div>
          <p class="text-xs font-semibold text-slate-500">Shift time left</p>
          <p class="text-xl font-bold font-mono tabular-nums" :class="low ? 'text-red-600' : 'text-slate-900'">{{ clock(shiftStore.minutesLeft) }}</p>
        </div>
      </div>
      <div class="text-xs text-slate-500">
        {{ time(shiftStore.shift.started_at) }} → {{ time(shiftStore.shift.ends_at) }}
        <span v-if="shiftStore.shift.extended_minutes" class="block text-brand-700">+{{ hoursText(shiftStore.shift.extended_minutes / 60) }} h extended</span>
      </div>
      <div v-if="shiftStore.summary" class="flex gap-6 text-sm">
        <div><p class="text-xs text-slate-500">Bookings</p><p class="font-bold tabular-nums">{{ shiftStore.summary.bookings }}</p></div>
        <div><p class="text-xs text-slate-500">Collected</p><p class="font-bold tabular-nums whitespace-nowrap">{{ totals(shiftStore.summary.collected) }}</p></div>
        <div><p class="text-xs text-slate-500">Cash in hand</p><p class="font-bold tabular-nums whitespace-nowrap text-emerald-700">{{ totals(shiftStore.summary.cash_to_hand_over) }}</p></div>
      </div>
      <div class="ml-auto flex flex-wrap gap-2">
        <span v-if="shiftStore.pending" class="ui-badge bg-amber-50 text-amber-800 border border-amber-200">
          <i class="fas fa-hourglass-half mr-1"></i>+{{ shiftStore.pending.hours }} h requested (#DC-{{ shiftStore.pending.id }})
        </span>
        <button v-else type="button" class="ui-btn ui-btn-sm" @click="asking = !asking"><i class="fas fa-plus"></i> More time</button>
        <button type="button" class="ui-btn ui-btn-sm" @click="shiftStore.summaryOpen = true"><i class="fas fa-receipt"></i> Summary</button>
        <button type="button" class="ui-btn ui-btn-sm text-red-700" :disabled="busy" @click="endShift"><i class="fas fa-stop"></i> End shift</button>
      </div>
      <form v-if="asking && !shiftStore.pending" @submit.prevent="ask" class="w-full grid grid-cols-1 sm:grid-cols-6 gap-2 items-end">
        <div>
          <label class="ui-label" for="more-hours">Hours</label>
          <input id="more-hours" v-model.number="hours" type="number" min="0.5" max="12" step="0.5" required class="ui-field">
        </div>
        <div class="sm:col-span-4">
          <label class="ui-label" for="more-reason">Reason</label>
          <input id="more-reason" v-model="reason" type="text" maxlength="255" required class="ui-field" placeholder="Why you need more time">
        </div>
        <button type="submit" class="ui-btn-primary" :disabled="busy">Request</button>
        <p v-if="error" class="ui-error sm:col-span-6">{{ error }}</p>
      </form>
    </div>

    <!-- Ended -->
    <div v-else-if="shiftStore.status === 'ended'" class="flex flex-wrap items-center gap-3">
      <span class="h-10 w-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center shrink-0"><i class="fas fa-clock"></i></span>
      <div class="text-sm">
        <p class="font-semibold text-slate-900">Your shift has ended</p>
        <p class="text-xs text-slate-500">
          Booking and payments are paused{{ shiftStore.shift?.next_shift_at ? ` until ${dateTime(shiftStore.shift.next_shift_at)}` : '' }}{{ shiftStore.pending ? ' — extension waiting for approval' : '' }}.
        </p>
      </div>
      <div class="ml-auto flex gap-2">
        <button type="button" class="ui-btn ui-btn-sm" @click="shiftStore.summaryOpen = true"><i class="fas fa-receipt"></i> Summary</button>
        <button type="button" class="ui-btn-primary ui-btn-sm" @click="shiftStore.dismissed = false"><i class="fas fa-clock"></i> {{ shiftStore.pending ? 'Status' : 'Request extension' }}</button>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useShiftStore } from '../../stores/shift';
import { useUiStore } from '../../stores/ui';
import { money, apiError, dateTime } from '../../utils/cargoUi';

const shiftStore = useShiftStore();
const uiStore = useUiStore();
const asking = ref(false);
const hours = ref(1);
const reason = ref('');
const busy = ref(false);
const error = ref('');

const low = computed(() => shiftStore.minutesLeft !== null && shiftStore.minutesLeft <= shiftStore.warningMinutes);
const clock = m => m === null ? '–' : `${Math.floor(m / 60)}:${String(m % 60).padStart(2, '0')}`;
const time = iso => iso ? new Date(iso).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' }) : '–';
const hoursText = h => Number(h || 0).toFixed(1).replace(/\.0$/, '');
const totals = byCurrency => Object.entries(byCurrency || {}).map(([c, v]) => money(v, c)).join(' + ') || money(0, 'TZS');

async function ask() {
  busy.value = true;
  error.value = '';
  try {
    window.notify?.(await shiftStore.requestExtension(hours.value, reason.value), 'success');
    asking.value = false;
    reason.value = '';
  } catch (e) {
    error.value = apiError(e, "Couldn't send the request. Try again.").message;
  } finally {
    busy.value = false;
  }
}

async function endShift() {
  const ok = await uiStore.confirm({
    title: 'End your shift now?',
    message: 'Booking and payments stop until your next shift.',
    tone: 'warning',
    icon: 'fa-stop',
    confirmText: 'End shift',
    cancelText: 'Keep working',
  });
  if (!ok) return;
  busy.value = true;
  try {
    window.notify?.(await shiftStore.end(), 'success');
  } catch (e) {
    window.notify?.(apiError(e, "Couldn't end the shift. Try again.").message, 'error');
  } finally {
    busy.value = false;
  }
}
</script>
