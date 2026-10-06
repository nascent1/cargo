<template>
  <div class="fixed inset-0 z-[2000] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 animate-fade-in">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden animate-fade-slide-up">
      <!-- Header -->
      <div class="bg-gradient-to-r from-red-500 to-rose-600 px-6 py-4 flex items-center gap-3 text-white">
        <i class="fas fa-clock text-2xl"></i>
        <div>
          <h3 class="text-lg font-bold">Shift Ended</h3>
          <p class="text-red-100 text-xs">Your current shift session has expired</p>
        </div>
      </div>

      <!-- Body -->
      <div class="p-6">
        <p class="text-sm text-slate-600 mb-5 leading-relaxed">
          You are currently locked out of the booking portal. To continue working, please request a shift extension. This request requires dual control approval from an administrator.
        </p>

        <form @submit.prevent="submitRequest" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Extension Hours Needed</label>
            <input v-model="hours" type="number" min="0.5" max="12" step="0.5" required
              class="w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:border-brand-500 focus:ring-brand-500 transition-colors"
              placeholder="e.g., 2">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Reason for Extension</label>
            <textarea v-model="reason" rows="3" required
              class="w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:border-brand-500 focus:ring-brand-500 transition-colors"
              placeholder="Briefly explain why you need to extend your shift..."></textarea>
          </div>

          <!-- Error / Success Alert -->
          <div v-if="error" class="p-3 bg-red-50 border border-red-100 rounded-lg text-sm text-red-600">
            {{ error }}
          </div>
          <div v-if="success" class="p-3 bg-emerald-50 border border-emerald-100 rounded-lg text-sm text-emerald-600">
            {{ success }}
          </div>

          <!-- Actions -->
          <div class="pt-2 flex gap-3">
            <button type="button" @click="logout"
              class="flex-1 py-2.5 px-4 rounded-xl font-bold text-sm text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
              Sign Out
            </button>
            <button type="submit" :disabled="loading || success"
              class="flex-1 py-2.5 px-4 rounded-xl font-bold text-sm text-white bg-brand-600 hover:bg-brand-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
              <i class="fas fa-spinner fa-spin mr-2" v-if="loading"></i>
              {{ loading ? 'Submitting...' : 'Request Extension' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';

const authStore = useAuthStore();
const hours = ref(1);
const reason = ref('');
const loading = ref(false);
const error = ref('');
const success = ref('');

const submitRequest = async () => {
  if (loading.value) return;
  loading.value = true;
  error.value = '';
  
  try {
    const res = await axios.post('/portal/api/shift/overtime-request', {
      hours: hours.value,
      reason: reason.value
    });
    
    success.value = res.data.message || 'Request submitted successfully. Waiting for admin approval.';
    // Could optionally poll for status, but for now we just show success.
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to submit request. Please try again.';
  } finally {
    loading.value = false;
  }
};

const logout = () => {
  authStore.logout();
};
</script>
