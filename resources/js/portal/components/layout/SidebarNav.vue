<template>
  <!-- Phones and tablets: slide-out menu -->
  <transition name="fade">
    <div v-if="open" class="lg:hidden fixed inset-0 z-40 bg-slate-900/40" @click="emit('close')" aria-hidden="true"></div>
  </transition>

  <aside
    class="fixed lg:sticky top-0 left-0 z-50 h-screen w-64 lg:w-60 shrink-0 bg-white border-r border-slate-200 flex flex-col transition-transform duration-200 lg:translate-x-0"
    :class="open ? 'translate-x-0 shadow-xl' : '-translate-x-full'"
    aria-label="Main menu">
    <div class="h-16 px-4 flex items-center gap-3 border-b border-slate-100 shrink-0">
      <img :src="brand.logo" alt="" class="h-9 w-9 rounded-lg object-cover shrink-0">
      <div class="min-w-0 flex-1">
        <p class="text-sm font-bold text-slate-900 truncate" :title="brand.name">{{ brand.name }}</p>
        <p class="text-[11px] font-semibold text-brand-600">Cargo · {{ portalLabel }}</p>
      </div>
      <button type="button" class="lg:hidden h-9 w-9 rounded-lg text-slate-500 hover:bg-slate-100" @click="emit('close')" aria-label="Close menu">
        <i class="fas fa-xmark"></i>
      </button>
    </div>

    <nav class="p-3 space-y-1 flex-1 overflow-y-auto">
      <router-link to="/dashboard" class="nav-item" active-class="nav-item-on">
        <i class="fas fa-house w-5 text-center"></i>Dashboard
      </router-link>
      <router-link v-if="authStore.can('cargo-book')" to="/cargo-booking" class="nav-item" active-class="nav-item-on">
        <i class="fas fa-plus w-5 text-center"></i>New booking
      </router-link>
      <router-link v-if="authStore.can('booking:read')" to="/cargo-bookings" class="nav-item" :class="{ 'nav-item-on': onBookings }">
        <i class="fas fa-box w-5 text-center"></i>Bookings
      </router-link>
    </nav>

    <div v-if="authStore.isSalesOfficer && authStore.can('admin_portal:access')" class="p-3 border-t border-slate-100">
      <a href="/admin" class="nav-item"><i class="fas fa-arrow-right-arrow-left w-5 text-center"></i>Admin portal</a>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { brand } from '../../utils/cargoUi';

defineProps({ open: { type: Boolean, default: false } });
const emit = defineEmits(['close']);

const authStore = useAuthStore();
const route = useRoute();

// A booking's own page still belongs to "Bookings"
const onBookings = computed(() => route.path.startsWith('/cargo-bookings'));

const portalLabel = computed(() => {
  if (authStore.isAgent) return 'Agent';
  if (authStore.isAssociate) return 'Associate';
  return 'Sales counter';
});
</script>

<style scoped>
.nav-item {
  display: flex; align-items: center; gap: 0.75rem;
  height: 2.5rem; padding: 0 0.75rem; border-radius: 0.75rem;
  font-size: 0.875rem; font-weight: 500; color: #475569; text-decoration: none;
  transition: background-color 0.15s ease, color 0.15s ease;
}
.nav-item:hover { background: #f1f5f9; color: #0f172a; }
.nav-item-on, .nav-item-on:hover { background: #eff6ff; color: #1e40af; font-weight: 600; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
