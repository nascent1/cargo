<template>
  <div class="ui-scope min-h-screen flex flex-col bg-slate-50 text-slate-800">
    <!-- Loading -->
    <div v-if="authStore.loading" class="flex-1 flex items-center justify-center min-h-screen">
      <div class="flex flex-col items-center gap-4 text-center">
        <img :src="brand.logo" alt="" class="h-14 w-14 rounded-xl object-cover animate-pulse">
        <p class="text-sm font-medium text-slate-500">Opening the sales counter…</p>
      </div>
    </div>

    <!-- Signed in -->
    <div v-else-if="authStore.user" class="flex-1 flex min-h-screen">
      <SidebarNav :open="menuOpen" @close="menuOpen = false" />
      <div class="flex-1 min-w-0 flex flex-col">
        <TopHeader @menu="menuOpen = true" />
        <main class="flex-1 p-4 sm:p-6">
          <router-view v-slot="{ Component }">
            <transition name="page-fade" mode="out-in">
              <component :is="Component" />
            </transition>
          </router-view>
        </main>
      </div>
    </div>

    <!-- Session expired -->
    <div v-else class="flex-1 flex items-center justify-center min-h-screen p-6">
      <div class="ui-card max-w-sm w-full p-8 text-center">
        <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center mx-auto mb-4 text-lg"><i class="fas fa-lock"></i></div>
        <h3 class="text-base font-semibold text-slate-900 mb-2">Session expired</h3>
        <p class="text-sm text-slate-600 mb-6">{{ authStore.error || 'Please sign in to use the sales counter.' }}</p>
        <a href="/login" class="ui-btn-primary w-full"><i class="fas fa-arrow-right-to-bracket"></i> Sign in</a>
      </div>
    </div>

    <!-- Shift ended / handover summary -->
    <ShiftEndedModal v-if="authStore.user && shiftStore.showDialog" />
    <ShiftSummary v-if="shiftStore.summaryOpen && shiftStore.summary" :summary="shiftStore.summary"
      :clerk="`${authStore.user?.first_name || ''} ${authStore.user?.last_name || ''}`.trim()" @close="shiftStore.summaryOpen = false" />

    <!-- Confirm dialog: await uiStore.confirm({...}) -->
    <ConfirmDialog />

    <!-- Global notification dialog -->
    <transition name="page-fade">
      <div v-if="uiStore.showNotificationModal" class="fixed inset-0 z-[1000] flex items-center justify-center bg-slate-900/40 p-4" @click.self="uiStore.showNotificationModal = false">
        <div class="ui-card p-6 max-w-sm w-full space-y-4 text-center shadow-xl" role="alertdialog">
          <div class="w-12 h-12 rounded-xl mx-auto flex items-center justify-center text-xl" :class="tone.icon">
            <i class="fas" :class="tone.glyph"></i>
          </div>
          <h3 class="text-base font-semibold text-slate-900" v-text="uiStore.notificationTitle"></h3>
          <p class="text-sm text-slate-600" v-text="uiStore.notificationMessage"></p>
          <button @click="uiStore.showNotificationModal = false" class="ui-btn-primary w-full">OK</button>
        </div>
      </div>
    </transition>

    <!-- Toasts -->
    <div class="fixed top-20 right-4 z-[9999] flex flex-col gap-2 pointer-events-none" aria-live="polite">
      <transition-group name="toast-slide">
        <div v-for="toast in toasts" :key="toast.id"
          class="ui-card px-4 py-3 shadow-lg text-sm font-medium flex items-center gap-3 w-80 max-w-[calc(100vw-2rem)] pointer-events-auto"
          :class="{ 'border-red-200 text-red-800': toast.type === 'error', 'border-emerald-200 text-emerald-800': toast.type === 'success', 'text-slate-800': !['error', 'success'].includes(toast.type) }">
          <i class="fas" :class="{ 'fa-circle-exclamation text-red-600': toast.type === 'error', 'fa-circle-check text-emerald-600': toast.type === 'success', 'fa-circle-info text-brand-600': !['error', 'success'].includes(toast.type) }"></i>
          <span v-text="toast.text"></span>
        </div>
      </transition-group>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from './stores/auth';
import { useUiStore } from './stores/ui';
import { brand } from './utils/cargoUi';
import { useShiftStore } from './stores/shift';
import TopHeader from './components/layout/TopHeader.vue';
import SidebarNav from './components/layout/SidebarNav.vue';
import ShiftEndedModal from './components/shift/ShiftEndedModal.vue';
import ShiftSummary from './components/shift/ShiftSummary.vue';
import ConfirmDialog from './components/ui/ConfirmDialog.vue';

const authStore = useAuthStore();
const uiStore = useUiStore();
const shiftStore = useShiftStore();
let shiftTimers = [];
const route = useRoute();
const toasts = ref([]);
const menuOpen = ref(false);

// The phone/tablet menu closes after navigating
watch(() => route.fullPath, () => { menuOpen.value = false; });

const tone = computed(() => ({
  error: { icon: 'bg-red-50 text-red-600', glyph: 'fa-triangle-exclamation' },
  success: { icon: 'bg-emerald-50 text-emerald-600', glyph: 'fa-circle-check' },
}[uiStore.notificationType] || { icon: 'bg-brand-50 text-brand-600', glyph: 'fa-circle-info' }));

function handleNotify(e) {
  const id = Date.now() + Math.random();
  toasts.value.push({ id, text: e.detail.text, type: e.detail.type || 'info' });
  setTimeout(() => { toasts.value = toasts.value.filter(t => t.id !== id); }, 4000);
}

onMounted(async () => {
  window.addEventListener('notify', handleNotify);
  window.notify = (text, type = 'info') => window.dispatchEvent(new CustomEvent('notify', { detail: { text, type } }));
  if (!authStore.user) await authStore.fetchUser();

  // Shift: state now, a refresh every minute, and a local countdown in between
  if (authStore.user) {
    await shiftStore.refresh();
    if (shiftStore.applies) {
      shiftTimers = [setInterval(() => shiftStore.refresh(), 60000), setInterval(() => shiftStore.heartbeat(), 15000)];
    }
  }
});

onUnmounted(() => {
  window.removeEventListener('notify', handleNotify);
  shiftTimers.forEach(clearInterval);
});
</script>

<style scoped>
.page-fade-enter-active, .page-fade-leave-active { transition: opacity 0.15s ease; }
.page-fade-enter-from, .page-fade-leave-to { opacity: 0; }
.toast-slide-enter-active, .toast-slide-leave-active { transition: all 0.2s ease; }
.toast-slide-enter-from, .toast-slide-leave-to { opacity: 0; transform: translateX(16px); }
</style>
