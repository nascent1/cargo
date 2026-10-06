<template>
  <aside
    :class="[collapsed ? 'w-[64px]' : 'w-[240px]']"
    class="relative flex flex-col shrink-0 transition-all duration-300 ease-in-out overflow-hidden bg-white border-r border-slate-200"
  >
    <!-- Logo area -->
    <div :class="[collapsed ? 'px-3 py-4' : 'px-4 py-4']"
      class="flex items-center gap-3 border-b border-slate-100 shrink-0">
      <div class="h-9 w-9 rounded-xl flex items-center justify-center shrink-0 shadow-sm bg-gradient-to-br from-brand-500 to-brand-700">
        <i class="fas fa-ship text-sm text-white"></i>
      </div>
      <div v-if="!collapsed" class="min-w-0">
        <p class="text-[12px] font-extrabold text-slate-800 tracking-tight truncate leading-tight">Cargo Portal</p>
        <p class="text-[9px] font-bold uppercase tracking-widest leading-tight text-brand-500">{{ portalLabel }}</p>
      </div>
    </div>

    <!-- Quick Book CTA -->
    <div v-if="!collapsed && authStore.can('cargo-book')" class="px-3 pt-4 pb-2">
      <router-link to="/cargo-booking"
        class="flex items-center justify-center gap-2 w-full px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-500 hover:to-brand-600 shadow-lg shadow-brand-200/40 hover:shadow-brand-300/50 transition-all duration-200">
        <i class="fas fa-plus text-xs"></i>
        <span>New Booking</span>
      </router-link>
    </div>
    <div v-else-if="collapsed && authStore.can('cargo-book')" class="px-2 pt-4 pb-2">
      <router-link to="/cargo-booking"
        class="flex items-center justify-center h-9 w-9 mx-auto rounded-xl text-white bg-gradient-to-br from-brand-600 to-brand-700 shadow-sm hover:shadow-md transition-all"
        title="New Booking">
        <i class="fas fa-plus text-xs"></i>
      </router-link>
    </div>

    <!-- Nav items -->
    <div class="flex-1 overflow-y-auto py-3 space-y-5 sidebar-scroll" :class="collapsed ? 'px-2' : 'px-3'">

      <!-- Booking -->
      <div>
        <p v-if="!collapsed" class="text-[8px] font-bold uppercase tracking-[0.15em] mb-1.5 px-2 text-slate-400">Booking</p>
        <nav class="space-y-0.5">
          <router-link to="/dashboard" class="nav-item group" active-class="nav-item-active">
            <div class="nav-icon-wrap"><i class="fas fa-chart-pie text-sm"></i></div>
            <span v-if="!collapsed" class="nav-label">Dashboard</span>
          </router-link>

          <router-link v-if="authStore.can('cargo-book')" to="/cargo-booking" class="nav-item group" active-class="nav-item-active">
            <div class="nav-icon-wrap"><i class="fas fa-truck-loading text-sm"></i></div>
            <span v-if="!collapsed" class="nav-label flex-1">Cargo Booking</span>
            <span v-if="!collapsed" class="text-[8px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded-md bg-brand-50 text-brand-600 border border-brand-200">POS</span>
          </router-link>
        </nav>
      </div>

      <!-- Agency -->
      <div v-if="authStore.isAgent || authStore.isAssociate">
        <p v-if="!collapsed" class="text-[8px] font-bold uppercase tracking-[0.15em] mb-1.5 px-2 text-slate-400">Agency</p>
        <nav class="space-y-0.5">
          <router-link v-if="authStore.isAgent || authStore.isAssociate" to="/wallet" class="nav-item group" active-class="nav-item-active">
            <div class="nav-icon-wrap"><i class="fas fa-wallet text-sm"></i></div>
            <span v-if="!collapsed" class="nav-label flex-1">Credit Wallet</span>
          </router-link>

          <router-link v-if="authStore.isAgent" to="/associates" class="nav-item group" active-class="nav-item-active">
            <div class="nav-icon-wrap"><i class="fas fa-users text-sm"></i></div>
            <span v-if="!collapsed" class="nav-label">Associates</span>
          </router-link>
        </nav>
      </div>

      <!-- History -->
      <div>
        <p v-if="!collapsed" class="text-[8px] font-bold uppercase tracking-[0.15em] mb-1.5 px-2 text-slate-400">History</p>
        <nav class="space-y-0.5">
          <router-link to="/cargo-bookings" class="nav-item group" active-class="nav-item-active">
            <div class="nav-icon-wrap"><i class="fas fa-boxes text-sm"></i></div>
            <span v-if="!collapsed" class="nav-label">Consignments</span>
          </router-link>

          <router-link v-if="authStore.isAgent" to="/reports" class="nav-item group" active-class="nav-item-active">
            <div class="nav-icon-wrap"><i class="fas fa-file-invoice-dollar text-sm"></i></div>
            <span v-if="!collapsed" class="nav-label">Commission & Sales</span>
          </router-link>
        </nav>
      </div>

      <!-- Account -->
      <div>
        <p v-if="!collapsed" class="text-[8px] font-bold uppercase tracking-[0.15em] mb-1.5 px-2 text-slate-400">Account</p>
        <nav class="space-y-0.5">
          <router-link to="/change-password" class="nav-item group" active-class="nav-item-active">
            <div class="nav-icon-wrap"><i class="fas fa-key text-sm"></i></div>
            <span v-if="!collapsed" class="nav-label">Change Password</span>
          </router-link>

          <router-link to="/2fa-setup" class="nav-item group" active-class="nav-item-active">
            <div class="nav-icon-wrap"><i class="fas fa-shield-halved text-sm"></i></div>
            <span v-if="!collapsed" class="nav-label">2FA Security</span>
          </router-link>
        </nav>
      </div>
    </div>

    <!-- Collapse toggle -->
    <div class="shrink-0 p-3 border-t border-slate-100">
      <button @click="collapsed = !collapsed"
        class="w-full flex items-center justify-center gap-2 py-2 px-2 rounded-xl transition-all text-xs font-semibold text-slate-400 hover:text-slate-600 hover:bg-slate-50"
        :title="collapsed ? 'Expand' : 'Collapse'">
        <i class="fas text-[10px]" :class="collapsed ? 'fa-angles-right' : 'fa-angles-left'"></i>
        <span v-if="!collapsed" class="text-[10px] uppercase tracking-wider">Collapse</span>
      </button>
    </div>
  </aside>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';

const authStore = useAuthStore();
const collapsed = ref(false);

const portalLabel = computed(() => {
  if (authStore.isSalesOfficer) return 'Sales Counter';
  if (authStore.isAgent)        return 'Agent Hub';
  if (authStore.isAssociate)    return 'Associate Portal';
  return 'Portal';
});
</script>

<style scoped>
.sidebar-scroll::-webkit-scrollbar { width: 3px; }
.sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
.sidebar-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.45rem 0.6rem;
  border-radius: 0.65rem;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #64748b;
  transition: all 0.15s ease;
  cursor: pointer;
  text-decoration: none;
}
.nav-item:hover {
  color: #0f172a;
  background: #f1f5f9;
}
/* Blue active states — matching admin portal */
.nav-item-active {
  color: #2563eb !important;
  background: #eff6ff !important;
  box-shadow: inset 0 0 0 1px #dbeafe;
  font-weight: 600;
}
.nav-icon-wrap {
  width: 1.5rem;
  height: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.4rem;
  flex-shrink: 0;
  font-size: 0.85rem;
  transition: all 0.15s ease;
}
.nav-item:hover .nav-icon-wrap {
  background: #e2e8f0;
}
.nav-item-active .nav-icon-wrap {
  background: #dbeafe !important;
  color: #2563eb;
}
.nav-label {
  font-size: 0.8125rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
