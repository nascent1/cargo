import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        roles: [],
        permissions: [],
        wallet: {
            balance: 0.00,
            currency: 'TZS',
        },
        loading: true,
        error: null,
        shiftEnded: false,
    }),

    getters: {
        isAuthenticated: (state) => !!state.user,
        isSalesOfficer: (state) => state.user?.domain === 'system_user',
        isAgent: (state) => state.user?.domain === 'agent',
        isAssociate: (state) => state.user?.domain === 'associate_agent',
        formattedWalletBalance: (state) => {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: state.wallet.currency || 'TZS',
                minimumFractionDigits: 0,
            }).format(state.wallet.balance || 0);
        },
        userRoleLabel: (state) => {
            if (state.user?.domain === 'system_user') return 'Booking Officer';
            if (state.user?.domain === 'agent') return 'Travel Agent';
            if (state.user?.domain === 'associate_agent') return 'Associate Agent';
            return 'Portal User';
        },
    },

    actions: {
        async fetchUser() {
            this.loading = true;
            this.error = null;
            try {
                const response = await axios.get('/portal/api/me');
                this.user = response.data.user;
                this.roles = response.data.roles || [];
                this.permissions = response.data.permissions || [];
                if (response.data.wallet) {
                    this.wallet = response.data.wallet;
                }
            } catch (err) {
                console.error('Failed to load portal user session:', err);
                this.error = err.response?.data?.message || 'Authentication error';
                if (err.response?.status === 401 || err.response?.status === 403) {
                    window.location.href = '/login';
                }
            } finally {
                this.loading = false;
            }
        },

        can(permissionSlug) {
            if (!this.user) return false;
            if (this.roles.includes('super_admin') || this.roles.includes('admin')) return true;
            return this.permissions.includes(permissionSlug);
        },

        hasRole(roleSlug) {
            if (!this.user) return false;
            if (this.roles.includes('super_admin')) return true;
            return this.roles.includes(roleSlug);
        },

        hasAnyRole(roleArray) {
            if (!this.user || !Array.isArray(roleArray)) return false;
            if (this.roles.includes('super_admin')) return true;
            return roleArray.some(r => this.roles.includes(r));
        },

        async logout() {
            try {
                await axios.post('/logout');
            } catch (err) {
                console.error('Logout error:', err);
            } finally {
                window.location.href = '/login';
            }
        },
    },
});
