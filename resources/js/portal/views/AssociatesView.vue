<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="bg-white/90 border border-slate-200/60 rounded-xl p-5 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
      <div>
        <span class="px-2 py-0.5 rounded-md bg-violet-500/10 text-violet-600 border border-violet-500/20 text-[9px] font-semibold uppercase tracking-widest">
          Associate Management
        </span>
        <h2 class="text-lg font-bold text-slate-800 mt-1.5">Manage Associates</h2>
        <p class="text-sm text-slate-500 mt-0.5">Add associates, set daily POS credit limits, and monitor their ticketing activity.</p>
      </div>

      <button
        @click="showCreateModal = true"
        class="px-4 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white font-bold text-xs uppercase tracking-wider shadow-md shadow-violet-600/20 transition-all flex items-center gap-1.5"
      >
        <i class="fas fa-user-plus"></i>
        Register Associate
      </button>
    </div>

    <!-- Create Modal -->
    <transition name="fade">
      <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4" @click.self="showCreateModal = false">
        <div class="bg-white border border-slate-200 rounded-2xl p-5 max-w-md w-full shadow-2xl space-y-4 animate-fade-slide-up">
          <div class="flex items-center justify-between pb-3 border-b border-slate-200/60">
            <h3 class="text-base font-bold text-slate-800">New Associate</h3>
            <button @click="showCreateModal = false" class="text-slate-500 hover:text-slate-800 transition-colors"><i class="fas fa-times"></i></button>
          </div>
          <form @submit.prevent="createAssociate" class="space-y-3 text-xs">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-slate-500 font-semibold mb-1">First Name</label>
                <input v-model="newAssoc.first_name" type="text" required class="w-full bg-slate-50 border border-slate-200/60 rounded-xl px-3.5 py-2 text-slate-800 uppercase focus:border-brand-500/50 outline-none transition-colors" />
              </div>
              <div>
                <label class="block text-slate-500 font-semibold mb-1">Last Name</label>
                <input v-model="newAssoc.last_name" type="text" required class="w-full bg-slate-50 border border-slate-200/60 rounded-xl px-3.5 py-2 text-slate-800 uppercase focus:border-brand-500/50 outline-none transition-colors" />
              </div>
            </div>
            <div>
              <label class="block text-slate-500 font-semibold mb-1">Email / Login</label>
              <input v-model="newAssoc.email" type="email" required class="w-full bg-slate-50 border border-slate-200/60 rounded-xl px-3.5 py-2 text-slate-800 focus:border-brand-500/50 outline-none transition-colors" />
            </div>
            <div>
              <label class="block text-slate-500 font-semibold mb-1">Phone Number</label>
              <input v-model="newAssoc.phone" type="tel" required class="w-full bg-slate-50 border border-slate-200/60 rounded-xl px-3.5 py-2 text-slate-800 focus:border-brand-500/50 outline-none transition-colors" />
            </div>
            <div>
              <label class="block text-slate-500 font-semibold mb-1">Daily POS Credit Ceiling (TZS)</label>
              <input v-model="newAssoc.limit" type="number" required class="w-full bg-slate-50 border border-slate-200/60 rounded-xl px-3.5 py-2 text-slate-800 focus:border-brand-500/50 outline-none transition-colors" />
            </div>
            <p class="text-[10px] text-slate-400 leading-relaxed">
              <i class="fas fa-info-circle mr-1 text-brand-600/60"></i>
              Associate bookings deduct from your agency float. No commission applies.
            </p>
            <div class="flex justify-end gap-2 pt-2">
              <button type="button" @click="showCreateModal = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-100 text-slate-400 font-semibold transition-colors">Cancel</button>
              <button type="submit" class="px-5 py-2 rounded-xl bg-violet-600 hover:bg-violet-500 text-white font-bold transition-colors">Register</button>
            </div>
          </form>
        </div>
      </div>
    </transition>

    <!-- Associates List -->
    <div class="bg-white/90 border border-slate-200/60 rounded-xl shadow-sm overflow-hidden">
      <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-200/60">
        <h3 class="text-sm font-semibold text-slate-800 flex items-center gap-2">
          <i class="fas fa-users text-violet-600"></i>
          Active Associates
        </h3>
        <span class="text-[10px] text-slate-400">Bookings deduct from your agency float</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead>
            <tr class="border-b border-slate-200/40 text-slate-500 text-[10px] font-semibold uppercase tracking-wider">
              <th class="py-2.5 px-5">Associate</th>
              <th class="py-2.5 px-5">Email</th>
              <th class="py-2.5 px-5">Phone</th>
              <th class="py-2.5 px-5">Daily Ceiling</th>
              <th class="py-2.5 px-5">Status</th>
              <th class="py-2.5 px-5 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200/60 text-xs">
            <tr v-for="(assoc, idx) in associates" :key="idx" class="hover:bg-slate-100/60 transition-colors">
              <td class="py-3 px-5">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-violet-500/10 border border-violet-500/20 flex items-center justify-center text-[10px] font-bold text-violet-600">
                    {{ assoc.name.split(' ').map(n => n[0]).slice(0, 2).join('') }}
                  </div>
                  <span class="font-semibold text-slate-800 uppercase">{{ assoc.name }}</span>
                </div>
              </td>
              <td class="py-3 px-5 text-slate-500">{{ assoc.email }}</td>
              <td class="py-3 px-5 text-slate-500">{{ assoc.phone }}</td>
              <td class="py-3 px-5 font-mono font-semibold text-brand-600">{{ assoc.limit }} TZS</td>
              <td class="py-3 px-5">
                <span class="px-2 py-0.5 rounded-md bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 font-bold uppercase text-[9px] tracking-wider">Active</span>
              </td>
              <td class="py-3 px-5 text-right space-x-1.5">
                <button @click="editCeiling(assoc)" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-100 text-slate-400 font-medium transition-colors border border-slate-200/40 text-[11px]">Set Limit</button>
                <button @click="toggleStatus(assoc)" class="px-2.5 py-1.5 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-600 font-medium transition-colors border border-red-500/20 text-[11px]">Suspend</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useUiStore } from '../stores/ui';

const uiStore = useUiStore();
const showCreateModal = ref(false);
const newAssoc = reactive({ first_name: '', last_name: '', email: '', phone: '', limit: 200000 });

const associates = ref([
  { name: 'ALI OMAR JUMA', email: 'ali.omar@honeyclub.co.tz', phone: '0777334455', limit: '200,000' },
  { name: 'ZAHRA HASSAN MUSA', email: 'zahra.hassan@honeyclub.co.tz', phone: '0777998811', limit: '350,000' },
]);

function createAssociate() {
  associates.value.push({
    name: `${newAssoc.first_name} ${newAssoc.last_name}`.toUpperCase(),
    email: newAssoc.email,
    phone: newAssoc.phone,
    limit: new Intl.NumberFormat('en-US').format(newAssoc.limit),
  });
  showCreateModal.value = false;
  newAssoc.first_name = '';
  newAssoc.last_name = '';
  newAssoc.email = '';
  newAssoc.phone = '';
}

function editCeiling(assoc) {
  const val = prompt(`Enter new daily credit ceiling (TZS) for ${assoc.name}:`, '300000');
  if (val) assoc.limit = new Intl.NumberFormat('en-US').format(val);
}

function toggleStatus(assoc) {
  uiStore.notify(`Associate [${assoc.name}] status toggled.`, 'Status Updated', 'success');
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
