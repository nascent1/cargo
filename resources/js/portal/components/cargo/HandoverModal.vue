<template>
  <div class="fixed inset-0 z-[100] flex items-center justify-center p-4" @keydown.esc="$emit('close')">
    <div class="absolute inset-0 bg-slate-900/60" @click="$emit('close')"></div>
    <form @submit.prevent="submit" class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg max-h-[92vh] overflow-y-auto" aria-labelledby="ho-title">
      <div class="px-6 py-5 border-b border-slate-100">
        <h3 id="ho-title" class="text-lg font-bold text-slate-800"><i class="fas fa-hand-holding text-brand-600 mr-1"></i> Hand over {{ booking.consignment_number }}</h3>
        <p class="text-xs text-slate-500 mt-1">Receiver on the booking: <strong>{{ booking.receiver_name }}</strong>
          <template v-if="booking.receiver_id_number"> · ID <span class="font-mono">{{ booking.receiver_id_number }}</span></template>
          · {{ booking.receiver_phone }}</p>
      </div>

      <div class="p-6 space-y-4 text-sm">
        <div>
          <label class="ui-label" for="ho-name">Collector's full name</label>
          <input id="ho-name" v-model="form.collector_name" required maxlength="150" class="ui-field">
        </div>
        <div class="grid grid-cols-5 gap-3">
          <div class="col-span-2">
            <label class="ui-label" for="ho-idtype">ID type</label>
            <select id="ho-idtype" v-model="form.id_type" required class="ui-field">
              <option v-for="(label, key) in ID_TYPES" :key="key" :value="key">{{ label }}</option>
            </select>
          </div>
          <div class="col-span-3">
            <label class="ui-label" for="ho-idno">ID number</label>
            <input id="ho-idno" v-model="form.id_number" required maxlength="60" class="ui-field font-mono uppercase">
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 p-4 space-y-3">
          <label class="ui-label" for="ho-phone">Collector's mobile</label>
          <div class="flex gap-2">
            <input id="ho-phone" v-model="form.phone" required maxlength="20" inputmode="tel" class="ui-field flex-1 min-w-0 font-mono">
            <button type="button" class="ui-btn-primary shrink-0" :disabled="sending" @click="sendCode">
              <i class="fas" :class="sending ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i> {{ sent === null ? 'Send code' : 'Send again' }}
            </button>
          </div>
          <p v-if="message" class="text-xs" :class="sent === false ? 'text-red-700' : 'text-slate-600'">{{ message }}</p>
          <div v-if="sent === true">
            <label class="ui-label" for="ho-code">Code read out by the collector</label>
            <input id="ho-code" v-model="form.code" required inputmode="numeric" maxlength="6" autocomplete="one-time-code" class="ui-field w-40 font-mono text-lg tracking-widest">
          </div>
          <div v-if="sent === false">
            <label class="ui-label" for="ho-reason">Why the mobile can't be confirmed</label>
            <input id="ho-reason" v-model="form.unconfirmed_reason" required maxlength="255" class="ui-field" placeholder="e.g. SMS not arriving — no network at the port">
          </div>
        </div>

        <div v-if="warnings.length" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-900">
          <p class="font-bold mb-1"><i class="fas fa-triangle-exclamation mr-1"></i>Check before handing over — these will be recorded:</p>
          <ul class="list-disc ml-4"><li v-for="w in warnings" :key="w">{{ w }}</li></ul>
        </div>
        <p v-if="error" class="ui-error">{{ error }}</p>
      </div>

      <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
        <button type="button" class="ui-btn" @click="$emit('close')">Cancel</button>
        <button type="submit" class="ui-btn-success" :disabled="sent === null || busy" :title="sent === null ? 'Send a code to the mobile first' : ''">
          <i class="fas" :class="busy ? 'fa-spinner fa-spin' : 'fa-check'"></i> Hand over
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import axios from 'axios';
import { apiError } from '../../utils/cargoUi';

const props = defineProps({ booking: { type: Object, required: true } });
const emit = defineEmits(['close', 'done']);

// Same list as App\Services\Cargo\CargoHandoverService::ID_TYPES
const ID_TYPES = {
  national_id: 'National ID (NIDA)', passport: 'Passport', driving_licence: 'Driving licence', voter_id: 'Voter ID', other: 'Other',
};

const form = reactive({
  collector_name: '', id_type: ID_TYPES[props.booking.receiver_id_type] ? props.booking.receiver_id_type : 'national_id',
  id_number: '', phone: props.booking.receiver_phone || '', code: '', unconfirmed_reason: '',
});
const sent = ref(null); // null: not asked yet · true: delivered · false: could not be delivered
const sending = ref(false);
const busy = ref(false);
const message = ref('');
const error = ref('');
const url = `/portal/api/cargo/consignments/${props.booking.id}/handover`;

const norm = s => String(s || '').toLowerCase().replace(/\s+/g, ' ').trim();
const digits = p => {
  let d = String(p || '').replace(/\D/g, '');
  if (d.startsWith('0') && d.length === 10) d = '255' + d.slice(1);
  if (d.length === 9) d = '255' + d;
  return d;
};

// The same checks the server records with the hand-over (warnings, never a block)
const warnings = computed(() => {
  const list = [];
  const r = norm(props.booking.receiver_name), c = norm(form.collector_name);
  if (c && r && !r.includes(c) && !c.includes(r)) list.push(`The collector is not the receiver on the booking (${props.booking.receiver_name}).`);
  if (form.id_number && props.booking.receiver_id_number && norm(form.id_number) !== norm(props.booking.receiver_id_number)) list.push("The ID number differs from the receiver's ID on the booking.");
  if (form.phone && props.booking.receiver_phone && digits(form.phone) !== digits(props.booking.receiver_phone)) list.push("The mobile differs from the receiver's number on the booking.");
  return list;
});

async function sendCode() {
  sending.value = true;
  error.value = '';
  try {
    const { data } = await axios.post(`${url}/code`, { phone: form.phone });
    sent.value = !!data.sent;
    message.value = data.message;
  } catch (e) {
    message.value = apiError(e, "Couldn't send the code.").message;
  } finally {
    sending.value = false;
  }
}

async function submit() {
  busy.value = true;
  error.value = '';
  try {
    const { data } = await axios.post(url, {
      ...form,
      code: sent.value ? form.code : null,
      unconfirmed_reason: sent.value === false ? form.unconfirmed_reason : null,
    });
    emit('done', data);
  } catch (e) {
    error.value = apiError(e, 'Hand-over failed.').message;
  } finally {
    busy.value = false;
  }
}
</script>
