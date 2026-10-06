<template>
  <div class="min-h-screen text-slate-700 flex flex-col font-sans bg-surface-100">
    <div v-if="authStore.loading" class="flex-1 flex items-center justify-center min-h-screen">
      <div class="flex flex-col items-center gap-5 text-center animate-fade-slide-up">
        <div class="relative">
          <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center text-white text-2xl shadow-lg shadow-brand-200/50 animate-pulse">
            <i class="fas fa-ship"></i>
          </div>
          <div class="absolute -inset-3 rounded-3xl border border-brand-300/30 animate-ping opacity-30"></div>
        </div>
        <div>
          <p class="text-base font-bold text-slate-800 tracking-tight">Initializing Session</p>
          <p class="text-xs font-medium text-slate-400 mt-1">Connecting to cargo system...</p>
        </div>
      </div>
    </div>

    <!-- Main Layout once loaded -->
    <div v-else-if="authStore.user" class="flex-1 flex flex-col min-h-screen">
      <TopHeader />

      <div class="flex-1 flex overflow-hidden">
        <SidebarNav />

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto">
          <!-- Mesh gradient background -->
          <div class="min-h-full p-5 lg:p-7" style="background-color: #f1f5f9; background-image: radial-gradient(at 40% 20%, rgba(37,99,235,0.04) 0px, transparent 50%), radial-gradient(at 80% 0%, rgba(59,130,246,0.03) 0px, transparent 50%), radial-gradient(at 0% 50%, rgba(37,99,235,0.03) 0px, transparent 50%);">
            <!-- Router View -->
            <router-view v-slot="{ Component }">
              <transition name="page-slide" mode="out-in">
                <component :is="Component" />
              </transition>
            </router-view>
          </div>
        </main>
      </div>
    </div>

    <!-- Error state -->
    <div v-else class="flex-1 flex items-center justify-center bg-surface-50 min-h-screen p-6">
      <div class="max-w-sm w-full bg-white border border-red-200/40 rounded-2xl p-8 text-center shadow-xl shadow-slate-200/50">
        <div class="w-14 h-14 rounded-2xl bg-red-50 border border-red-200/40 flex items-center justify-center mx-auto mb-4 text-red-500 text-xl">
          <i class="fas fa-lock"></i>
        </div>
        <h3 class="text-base font-bold text-slate-800 mb-2">Session Expired</h3>
        <p class="text-sm text-slate-500 mb-6 leading-relaxed">{{ authStore.error || 'Please sign in to access the cargo portal.' }}</p>
        <a href="/login" class="inline-flex items-center justify-center px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm tracking-wide transition-all shadow-lg shadow-brand-200/40">
          <i class="fas fa-arrow-right-to-bracket mr-2"></i>
          Sign In
        </a>
      </div>
    </div>

    <!-- Global Notification Modal -->
    <transition name="modal-pop">
      <div v-if="uiStore.showNotificationModal" class="fixed inset-0 z-[1000] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4" @click.self="uiStore.showNotificationModal = false">
        <div class="bg-white border border-slate-200/60 rounded-2xl p-6 max-w-sm w-full space-y-4 text-center shadow-2xl shadow-slate-200/50">
          <div class="w-14 h-14 rounded-2xl mx-auto flex items-center justify-center text-2xl"
               :class="uiStore.notificationType === 'error' ? 'bg-red-50 border border-red-200/40 text-red-500' : (uiStore.notificationType === 'success' ? 'bg-emerald-50 border border-emerald-200/40 text-emerald-500' : 'bg-brand-50 border border-brand-200/40 text-brand-500')">
            <i class="fas" :class="uiStore.notificationType === 'error' ? 'fa-exclamation-triangle' : (uiStore.notificationType === 'success' ? 'fa-check-circle' : 'fa-info-circle')"></i>
          </div>
          <h3 class="text-base font-bold text-slate-800" v-text="uiStore.notificationTitle"></h3>
          <p class="text-sm text-slate-500 leading-relaxed" v-text="uiStore.notificationMessage"></p>
          <div class="pt-2">
            <button @click="uiStore.showNotificationModal = false"
                    class="w-full py-2.5 rounded-xl font-bold text-sm text-white shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer hover:brightness-110"
                    :class="uiStore.notificationType === 'error' ? 'bg-red-600 hover:bg-red-500' : (uiStore.notificationType === 'success' ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-brand-600 hover:bg-brand-500')">
              Got It
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Global Toast Notifications -->
    <div class="fixed top-16 right-4 z-[9999] flex flex-col gap-2 pointer-events-none">
      <transition-group name="toast-slide">
        <div v-for="toast in toasts" :key="toast.id"
             class="px-4 py-3 rounded-xl shadow-xl border text-sm font-semibold flex items-center gap-3 w-80 pointer-events-auto"
             :class="{
               'bg-white text-red-700 border-red-200': toast.type === 'error',
               'bg-white text-emerald-700 border-emerald-200': toast.type === 'success',
               'bg-white text-brand-700 border-brand-200': toast.type === 'info' || !toast.type
             }">
          <i class="fas" :class="{
            'fa-exclamation-circle text-red-500': toast.type === 'error',
            'fa-check-circle text-emerald-500': toast.type === 'success',
            'fa-info-circle text-brand-500': toast.type === 'info' || !toast.type
          }"></i>
          <span v-text="toast.text"></span>
        </div>
      </transition-group>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from './stores/auth';
import { useUiStore } from './stores/ui';
import TopHeader from './components/layout/TopHeader.vue';
import SidebarNav from './components/layout/SidebarNav.vue';

const authStore = useAuthStore();
const uiStore = useUiStore();
const toasts = ref([]);

function handleNotify(e) {
  const id = Date.now();
  toasts.value.push({ id, text: e.detail.text, type: e.detail.type || 'info' });
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== id);
  }, 4000);
}

onMounted(() => {
  if (!authStore.user) {
    authStore.fetchUser();
  }
  window.addEventListener('notify', handleNotify);
  window.notify = function(text, type = 'info') {
    window.dispatchEvent(new CustomEvent('notify', { detail: { text, type } }));
  };
});

onUnmounted(() => {
  window.removeEventListener('notify', handleNotify);
});
</script>

<style scoped>
.page-slide-enter-active,
.page-slide-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.page-slide-enter-from {
  opacity: 0;
  transform: translateY(6px);
}
.page-slide-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

.modal-pop-enter-active {
  transition: opacity 0.2s ease;
}
.modal-pop-leave-active {
  transition: opacity 0.15s ease;
}
.modal-pop-enter-from,
.modal-pop-leave-to {
  opacity: 0;
}

.toast-slide-enter-active {
  transition: all 0.3s ease;
}
.toast-slide-leave-active {
  transition: all 0.2s ease;
}
.toast-slide-enter-from {
  opacity: 0;
  transform: translateX(20px);
}
.toast-slide-leave-to {
  opacity: 0;
  transform: translateX(20px);
}
</style>
