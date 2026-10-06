<template>
  <div class="max-w-lg mx-auto">
    <!-- Success Message -->
    <transition name="page-slide">
      <div v-if="successMsg" class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex items-start gap-3 text-sm">
        <i class="fas fa-check-circle mt-0.5"></i>
        <span>{{ successMsg }}</span>
      </div>
    </transition>

    <!-- Error Message -->
    <transition name="page-slide">
      <div v-if="errorMsg" class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-xl flex items-start gap-3 text-sm">
        <i class="fas fa-exclamation-circle mt-0.5"></i>
        <span>{{ errorMsg }}</span>
      </div>
    </transition>

    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
        <h3 class="font-semibold text-slate-900 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-600 text-white flex items-center justify-center shadow-md shadow-indigo-500/25">
            <i class="fas fa-key"></i>
          </div>
          Change Your Password
        </h3>
        <p class="text-xs text-slate-400 mt-2 ml-[52px]">
          Password must be at least 8 characters with uppercase, lowercase, numbers, and symbols.
        </p>
      </div>

      <form @submit.prevent="submitChangePassword" class="p-6 space-y-5">
        <!-- Current Password -->
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1.5">
            Current Password <span class="text-rose-500">*</span>
          </label>
          <div class="relative">
            <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input :type="showCurrent ? 'text' : 'password'" v-model="form.current_password" required
                   class="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-10 py-3 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors placeholder:text-slate-400 text-slate-900 shadow-sm"
                   placeholder="Enter your current password">
            <button type="button" @click="showCurrent = !showCurrent" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
              <i :class="showCurrent ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-sm"></i>
            </button>
          </div>
        </div>

        <!-- New Password -->
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1.5">
            New Password <span class="text-rose-500">*</span>
          </label>
          <div class="relative">
            <i class="fas fa-shield-alt absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input :type="showNew ? 'text' : 'password'" v-model="form.new_password" required
                   class="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-10 py-3 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors placeholder:text-slate-400 text-slate-900 shadow-sm"
                   placeholder="Enter a strong new password">
            <button type="button" @click="showNew = !showNew" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
              <i :class="showNew ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-sm"></i>
            </button>
          </div>
          <div class="mt-2 grid grid-cols-2 gap-2 text-[11px]">
            <span :class="form.new_password.length >= 8 ? 'text-emerald-500' : 'text-slate-400'">
              <i class="fas fa-check-circle mr-1"></i> Min 8 characters
            </span>
            <span :class="/[a-z]/.test(form.new_password) && /[A-Z]/.test(form.new_password) ? 'text-emerald-500' : 'text-slate-400'">
              <i class="fas fa-check-circle mr-1"></i> Upper & lowercase
            </span>
            <span :class="/[0-9]/.test(form.new_password) ? 'text-emerald-500' : 'text-slate-400'">
              <i class="fas fa-check-circle mr-1"></i> At least one number
            </span>
            <span :class="/[^a-zA-Z0-9]/.test(form.new_password) ? 'text-emerald-500' : 'text-slate-400'">
              <i class="fas fa-check-circle mr-1"></i> At least one symbol
            </span>
          </div>
        </div>

        <!-- Confirm New Password -->
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1.5">
            Confirm New Password <span class="text-rose-500">*</span>
          </label>
          <div class="relative">
            <i class="fas fa-shield-alt absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input :type="showConfirm ? 'text' : 'password'" v-model="form.new_password_confirmation" required
                   class="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-10 py-3 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors placeholder:text-slate-400 text-slate-900 shadow-sm"
                   placeholder="Re-enter new password">
            <button type="button" @click="showConfirm = !showConfirm" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
              <i :class="showConfirm ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-sm"></i>
            </button>
          </div>
          <p v-if="form.new_password_confirmation && form.new_password !== form.new_password_confirmation" class="text-xs text-rose-500 mt-1">
            <i class="fas fa-times-circle mr-1"></i> Passwords do not match
          </p>
        </div>

        <!-- Submit -->
        <div class="pt-4 border-t border-slate-100">
          <button type="submit" :disabled="saving || !isFormValid"
                  class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-500 to-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0">
            <i :class="saving ? 'fas fa-spinner fa-spin' : 'fas fa-save'" class="text-xs"></i>
            {{ saving ? 'Updating...' : 'Update Password' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

const form = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
});

const saving = ref(false);
const successMsg = ref('');
const errorMsg = ref('');
const showCurrent = ref(false);
const showNew = ref(false);
const showConfirm = ref(false);

const isFormValid = computed(() => {
  return form.value.current_password
    && form.value.new_password.length >= 8
    && /[a-z]/.test(form.value.new_password)
    && /[A-Z]/.test(form.value.new_password)
    && /[0-9]/.test(form.value.new_password)
    && /[^a-zA-Z0-9]/.test(form.value.new_password)
    && form.value.new_password === form.value.new_password_confirmation;
});

const submitChangePassword = async () => {
  saving.value = true;
  successMsg.value = '';
  errorMsg.value = '';

  try {
    const res = await axios.post('/portal/api/change-password', form.value);
    if (res.data.status === 'success') {
      successMsg.value = res.data.message;
      form.value = { current_password: '', new_password: '', new_password_confirmation: '' };
    } else {
      errorMsg.value = res.data.message || 'Failed to change password.';
    }
  } catch (e) {
    if (e.response && e.response.data) {
      errorMsg.value = e.response.data.message || 'Failed to change password.';
    } else {
      errorMsg.value = 'Network error. Please try again.';
    }
  } finally {
    saving.value = false;
  }
};
</script>
