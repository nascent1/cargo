<template>
  <header class="h-14 shrink-0 z-20 flex items-center justify-between px-5 gap-3 bg-white border-b border-slate-200">
    <!-- Left: Breadcrumb-style page context -->
    <div class="flex items-center gap-2 min-w-0">
      <span class="text-sm font-bold text-slate-800 truncate">{{ $route.meta.title || 'Dashboard' }}</span>
      <span class="hidden sm:inline-flex items-center gap-1.5 text-[10px] font-medium text-slate-400">
        <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
        Online
      </span>
    </div>

    <!-- Right controls -->
    <div class="flex items-center gap-2">

      <!-- Admin panel switcher -->
      <a v-if="authStore.isSalesOfficer && authStore.can('admin_portal:access')"
        href="/admin"
        class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold transition-all bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100"
        title="Switch to Admin Panel">
        <i class="fas fa-arrow-right-arrow-left text-xs text-brand-500"></i>
        Admin Panel
      </a>

      <!-- Wallet float (agents) -->
      <div v-if="authStore.isAgent"
        class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200">
        <i class="fas fa-wallet text-xs text-emerald-500"></i>
        <div class="leading-none">
          <span class="text-[8px] uppercase tracking-wider block font-bold text-emerald-600">Float</span>
          <span class="text-[11px] font-mono font-bold text-emerald-700">{{ authStore.formattedWalletBalance }}</span>
        </div>
      </div>

      <!-- Divider -->
      <div class="h-6 w-px bg-slate-200"></div>

      <!-- Avatar + name -->
      <div class="flex items-center gap-2.5">
        <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-extrabold shadow-sm shrink-0 bg-gradient-to-br from-brand-500 to-brand-700 text-white">
          {{ authStore.user?.first_name?.[0] || 'U' }}{{ authStore.user?.last_name?.[0] || '' }}
        </div>
        <div class="hidden lg:block leading-tight">
          <span class="text-xs font-bold text-slate-800 block">{{ authStore.user?.name }}</span>
          <span class="text-[10px] capitalize text-slate-400">
            {{ authStore.roles[0]?.replace(/_/g, ' ') || authStore.user?.domain }}
          </span>
        </div>
      </div>

      <!-- Logout -->
      <button @click="authStore.logout()"
        class="h-8 w-8 rounded-lg flex items-center justify-center transition-all text-slate-400 hover:text-red-600 hover:bg-red-50"
        title="Sign Out">
        <i class="fas fa-power-off text-sm"></i>
      </button>
    </div>
  </header>
</template>

<script setup>
import { useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const authStore = useAuthStore();
const $route = useRoute();
</script>
