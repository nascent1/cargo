<template>
  <div class="fixed inset-0 z-[1050] flex items-center justify-center bg-slate-900/50 p-4" role="alertdialog" aria-labelledby="shift-ended-title">
    <div class="ui-card w-full max-w-md shadow-2xl overflow-hidden">
      <div class="bg-red-600 px-5 py-4 text-white flex items-center gap-3">
        <i class="fas fa-clock text-2xl"></i>
        <div>
          <h2 id="shift-ended-title" class="text-lg font-bold">Your shift has ended</h2>
          <p class="text-xs text-red-100">{{ endedText }}</p>
        </div>
      </div>

      <div class="p-5 space-y-4 text-sm">
        <p class="text-slate-600">
          Booking, taking payments and cancelling are paused. You can still look up and print bookings.
          <template v-if="nextShift"> From <strong>{{ nextShift }}</strong> a new shift starts when you sign in (or make a booking).</template>
        </p>

        <div v-if="shiftStore.summary" class="rounded-xl bg-slate-50 p-3 flex items-center justify-between gap-3">
          <div class="text-xs text-slate-600">
            {{ shiftStore.summary.bookings }} bookings ·
            cash to hand over <strong class="text-slate-900">{{ totals(shiftStore.summary.cash_to_hand_over) }}</strong>
          </div>
          <button type="button" class="ui-btn ui-btn-sm" @click="shiftStore.summaryOpen = true"><i class="fas fa-receipt"></i> Summary</button>
        </div>

        <div v-if="shiftStore.pending" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900">
          <p class="font-semibold"><i class="fas fa-hourglass-half mr-1"></i>Extension of {{ shiftStore.pending.hours }} h requested</p>
          <p class="text-xs mt-1">Waiting for approval (#DC-{{ shiftStore.pending.id }}). This screen updates on its own once it's approved.</p>
        </div>

        <form v-else @submit.prevent="submit" class="space-y-3">
          <p class="font-semibold text-slate-900">Need more time?</p>
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="ui-label" for="ext-hours">Hours</label>
              <input id="ext-hours" v-model.number="hours" type="number" min="0.5" max="12" step="0.5" required class="ui-field">
            </div>
            <div class="col-span-2">
              <label class="ui-label" for="ext-reason">Reason</label>
              <input id="ext-reason" v-model="reason" type="text" maxlength="255" required class="ui-field" placeholder="e.g. late vessel still loading">
            </div>
          </div>
          <p v-if="error" class="ui-error">{{ error }}</p>
          <button type="submit" class="ui-btn-primary w-full" :disabled="busy">
            <i class="fas" :class="busy ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i> Request extension
          </button>
          <p class="ui-hint">A supervisor approves it in the dual-control inbox.</p>
        </form>
      </div>

      <div class="px-5 pb-5 flex gap-2">
        <button type="button" class="ui-btn flex-1" @click="shiftStore.dismissed = true">Keep viewing</button>
        <button type="button" class="ui-btn flex-1 text-red-700" @click="authStore.logout()"><i class="fas fa-power-off"></i> Sign out</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useShiftStore } from '../../stores/shift';
import { useAuthStore } from '../../stores/auth';
import { money, dateTime, apiError } from '../../utils/cargoUi';

const shiftStore = useShiftStore();
const authStore = useAuthStore();
const hours = ref(1);
const reason = ref('');
const busy = ref(false);
const error = ref('');
let timer = null;

const endedText = computed(() => {
  const at = shiftStore.shift?.ended_at;
  return at ? `Ended ${dateTime(at)}` : 'Your shift time is up';
});
const nextShift = computed(() => shiftStore.shift?.next_shift_at ? dateTime(shiftStore.shift.next_shift_at) : '');
const totals = byCurrency => Object.entries(byCurrency || {}).map(([c, v]) => money(v, c)).join(' + ') || money(0, 'TZS');

async function submit() {
  busy.value = true;
  error.value = '';
  try {
    const message = await shiftStore.requestExtension(hours.value, reason.value);
    // Applied at once (rule off): the store already announced it
    if (shiftStore.status !== 'active') window.notify?.(message, 'success');
  } catch (e) {
    error.value = apiError(e, "Couldn't send the request. Try again.").message;
  } finally {
    busy.value = false;
  }
}

onMounted(() => { timer = setInterval(() => shiftStore.refresh(), 30000); });
onUnmounted(() => clearInterval(timer));
</script>
