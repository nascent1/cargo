<template>
  <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div v-for="role in ['sender', 'receiver']" :key="role" class="ui-card p-5 space-y-3">
      <h2 class="text-base font-semibold text-slate-900 flex items-center gap-2">
        <i class="fas" :class="role === 'sender' ? 'fa-arrow-up-from-bracket text-brand-600' : 'fa-hand-holding text-emerald-600'"></i>
        {{ role === 'sender' ? 'Sender' : 'Receiver' }}
        <button v-if="role === 'receiver' && cargo.people.sender.name" type="button" class="ui-link text-xs ml-auto" @click="cargo.sameAsSender()">Same person as sender</button>
      </h2>
      <div>
        <label class="ui-label" :for="`${role}-phone`">Mobile number <span class="text-red-600">*</span></label>
        <input :id="`${role}-phone`" v-model="cargo.people[role].phone" type="tel" maxlength="20" placeholder="0754 123 456" autocomplete="off"
          class="ui-field font-mono" :class="cargo.errors[`${role}_phone`] && 'ui-field-invalid'" @input="lookup(role)">
        <p v-if="cargo.errors[`${role}_phone`]" class="ui-error">{{ cargo.errors[`${role}_phone`] }}</p>
        <div v-if="cargo.suggestions[role].length" class="mt-2 space-y-1">
          <p class="text-[11px] text-slate-600">Used before:</p>
          <button v-for="p in cargo.suggestions[role]" :key="p.name + p.role" type="button" class="w-full text-left rounded-lg border border-slate-300 hover:border-brand-400 hover:bg-brand-50/50 px-3 py-2 text-sm" @click="cargo.useCustomer(role, p)">
            <span class="font-semibold text-slate-900">{{ p.name }}</span>
            <span class="text-xs text-slate-600"> · {{ p.role }} · {{ p.last_used }}</span>
          </button>
        </div>
      </div>
      <div>
        <label class="ui-label" :for="`${role}-name`">Full name <span class="text-red-600">*</span></label>
        <input :id="`${role}-name`" v-model="cargo.people[role].name" type="text" maxlength="100" class="ui-field" :class="cargo.errors[`${role}_name`] && 'ui-field-invalid'">
        <p v-if="cargo.errors[`${role}_name`]" class="ui-error">{{ cargo.errors[`${role}_name`] }}</p>
      </div>
      <div v-if="cargo.people[role].showId" class="grid grid-cols-2 gap-3">
        <div>
          <label class="ui-label" :for="`${role}-idt`">ID type</label>
          <select :id="`${role}-idt`" v-model="cargo.people[role].id_type" class="ui-field">
            <option value="">None</option><option value="NIDA">NIDA</option><option value="Passport">Passport</option><option value="Voter">Voter ID</option><option value="Driving">Driving licence</option>
          </select>
        </div>
        <div>
          <label class="ui-label" :for="`${role}-idn`">ID number</label>
          <input :id="`${role}-idn`" v-model="cargo.people[role].id_number" type="text" maxlength="50" class="ui-field">
        </div>
      </div>
      <button v-else type="button" class="ui-link text-xs" @click="cargo.people[role].showId = true"><i class="fas fa-plus mr-1"></i>Add ID</button>
    </div>

    <div class="md:col-span-2 ui-card p-5">
      <label class="ui-label" for="special">Special instructions <span class="font-normal text-slate-500">(printed on the manifest)</span></label>
      <textarea id="special" v-model="cargo.specialInstructions" rows="2" maxlength="1000" placeholder="e.g. Keep upright, call receiver on arrival" class="ui-field"></textarea>
    </div>
  </section>
</template>

<script setup>
import { useCargoStore } from '../../stores/cargo';

// Step 3: who sends and who collects. Returning customers are offered from earlier bookings.
const cargo = useCargoStore();
const timers = {};

function lookup(role) {
  cargo.suggestions[role] = [];
  clearTimeout(timers[role]);
  if (String(cargo.people[role].phone).replace(/\D/g, '').length >= 9) {
    timers[role] = setTimeout(() => cargo.fetchCustomers(role), 400);
  }
}
</script>
