<template>
  <header class="h-16 shrink-0 sticky top-0 z-30 bg-white border-b border-slate-200 px-4 sm:px-6 flex items-center gap-3">
    <button type="button" class="lg:hidden h-10 w-10 -ml-2 rounded-xl text-slate-600 hover:bg-slate-100" @click="emit('menu')" aria-label="Open menu">
      <i class="fas fa-bars"></i>
    </button>
    <h1 class="text-base font-bold text-slate-900 truncate">{{ route.meta.title || 'Dashboard' }}</h1>

    <!-- Find a booking -->
    <form v-if="authStore.can('booking:read')" @submit.prevent="find" class="hidden md:block flex-1 max-w-md ml-4 relative" role="search">
      <i class="fas fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
      <input v-model="query" type="search" class="ui-field pl-9 bg-slate-50" placeholder="Find a booking — number, phone or name" aria-label="Find a booking">
    </form>

    <div class="ml-auto flex items-center gap-2">
      <!-- Shift time left (or ended) -->
      <router-link v-if="shiftStore.status === 'active' || shiftStore.status === 'ended'" to="/dashboard"
        class="ui-chip font-mono tabular-nums" :class="shiftChipClass" :title="shiftStore.status === 'ended' ? 'Your shift has ended' : 'Shift time left'">
        <i class="fas fa-clock"></i>
        <span>{{ shiftStore.status === 'ended' ? 'Shift ended' : shiftClock }}</span>
      </router-link>
      <router-link v-if="authStore.can('booking:read')" to="/cargo-bookings" class="md:hidden ui-btn h-10 w-10 px-0" aria-label="Find a booking">
        <i class="fas fa-magnifying-glass"></i>
      </router-link>
      <router-link v-if="authStore.can('cargo-book') && route.name !== 'CargoBooking'" to="/cargo-booking" class="ui-btn-primary">
        <i class="fas fa-plus text-xs"></i><span class="hidden sm:inline">New booking</span>
      </router-link>

      <!-- Name menu -->
      <div class="relative" ref="menuEl">
        <button type="button" @click="menuOpen = !menuOpen" class="flex items-center gap-2 h-10 pl-1 pr-2 rounded-xl hover:bg-slate-100" :aria-expanded="menuOpen" aria-haspopup="menu">
          <span class="h-8 w-8 rounded-full bg-brand-100 text-brand-800 text-xs font-bold flex items-center justify-center">{{ initials }}</span>
          <span class="hidden lg:block text-left leading-tight">
            <span class="block text-sm font-semibold text-slate-900">{{ authStore.user?.first_name }} {{ authStore.user?.last_name }}</span>
            <span class="block text-[11px] text-slate-500">{{ authStore.userRoleLabel }}</span>
          </span>
          <i class="fas fa-chevron-down text-[10px] text-slate-400"></i>
        </button>
        <div v-if="menuOpen" class="absolute right-0 mt-2 w-56 ui-card shadow-lg py-1.5 z-50" role="menu">
          <p class="px-4 py-2 text-xs text-slate-500 border-b border-slate-100 truncate">{{ authStore.user?.email }}</p>
          <router-link to="/change-password" class="menu-item" role="menuitem" @click="menuOpen = false"><i class="fas fa-key w-4"></i>Change password</router-link>
          <router-link to="/2fa-setup" class="menu-item" role="menuitem" @click="menuOpen = false"><i class="fas fa-shield-halved w-4"></i>2FA security</router-link>
          <a v-if="authStore.isSalesOfficer && authStore.can('admin_portal:access')" href="/admin" class="menu-item" role="menuitem"><i class="fas fa-arrow-right-arrow-left w-4"></i>Admin portal</a>
          <button type="button" class="menu-item w-full text-red-700" role="menuitem" @click="authStore.logout()"><i class="fas fa-power-off w-4"></i>Sign out</button>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useShiftStore } from '../../stores/shift';

const emit = defineEmits(['menu']);
const authStore = useAuthStore();
const shiftStore = useShiftStore();

const shiftClock = computed(() => {
  const m = shiftStore.minutesLeft;
  return m === null ? '' : `${Math.floor(m / 60)}:${String(m % 60).padStart(2, '0')}`;
});
const shiftChipClass = computed(() => {
  if (shiftStore.status === 'ended') return 'border-red-200 bg-red-50 text-red-700';
  return shiftStore.minutesLeft !== null && shiftStore.minutesLeft <= shiftStore.warningMinutes ? 'border-red-200 bg-red-50 text-red-700' : '';
});
const route = useRoute();
const router = useRouter();
const query = ref('');
const menuOpen = ref(false);
const menuEl = ref(null);

const initials = computed(() => `${authStore.user?.first_name?.[0] || ''}${authStore.user?.last_name?.[0] || ''}`.toUpperCase() || 'U');

function find() {
  const search = query.value.trim();
  if (!search) return;
  router.push({ path: '/cargo-bookings', query: { search } });
  query.value = '';
}

// Close the name menu on an outside click
const onDocClick = e => { if (menuOpen.value && menuEl.value && !menuEl.value.contains(e.target)) menuOpen.value = false; };
onMounted(() => document.addEventListener('click', onDocClick));
onUnmounted(() => document.removeEventListener('click', onDocClick));
</script>

<style scoped>
.menu-item {
  display: flex; align-items: center; gap: 0.6rem; padding: 0.5rem 1rem;
  font-size: 0.875rem; color: #334155; text-decoration: none; text-align: left;
}
.menu-item:hover { background: #f8fafc; }
</style>
