import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import axios from 'axios';

const router = createRouter({
    history: createWebHistory('/portal'),
    routes: [
        {
            path: '/',
            redirect: '/dashboard',
        },
        {
            path: '/dashboard',
            name: 'Dashboard',
            component: () => import('../views/DashboardView.vue'),
            meta: { title: 'Dashboard' },
        },
        {
            path: '/cargo-booking',
            name: 'CargoBooking',
            component: () => import('../views/cargo/CargoBooking.vue'),
            meta: { title: 'Cargo Booking', permission: 'cargo-book' },
        },

        {
            path: '/wallet',
            name: 'Wallet',
            component: () => import('../views/WalletView.vue'),
            meta: { title: 'Credit Wallet', permission: 'wallet:view' },
        },
        {
            path: '/associates',
            name: 'Associates',
            component: () => import('../views/AssociatesView.vue'),
            meta: { title: 'Manage Associates', permission: 'associates:manage' },
        },
        {
            path: '/reports',
            name: 'Reports',
            component: () => import('../views/ReportsView.vue'),
            meta: { title: 'Sales Reports' },
        },
        {
            path: '/cargo-bookings',
            name: 'CargoBookings',
            component: () => import('../views/cargo/CargoBookingsListView.vue'),
            meta: { title: 'Cargo Consignments', permission: 'booking:read' },
        },
        {
            path: '/cargo-bookings/:id',
            name: 'CargoBookingDetails',
            component: () => import('../views/cargo/CargoBookingDetailView.vue'),
            meta: { title: 'Consignment Details' },
        },
        {
            // Receipts are printed from the profile via the shared thermal view
            path: '/cargo-bookings/:id/receipt',
            redirect: to => `/cargo-bookings/${to.params.id}`,
        },
        {
            path: '/change-password',
            name: 'ChangePassword',
            component: () => import('../views/ChangePasswordView.vue'),
            meta: { title: 'Change Password' },
        },
        {
            path: '/2fa-setup',
            name: 'TwoFactorSetup',
            component: () => import('../views/auth/TwoFactorSetupView.vue'),
            meta: { title: '2FA Security Configuration' },
        },
        {
            path: '/:pathMatch(.*)*',
            redirect: '/dashboard',
        },
    ],
});

// Global navigation guard
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    // If user not loaded yet, fetch from server
    if (!authStore.user && !authStore.error) {
        await authStore.fetchUser();
    }

    // If still no user, block access
    if (!authStore.user) {
        return; // fetchUser will redirect to /login if 401
    }

    // Check strict permission guard
    if (to.meta.permission && !authStore.can(to.meta.permission)) {
        return next('/dashboard');
    }

    next();
});

// Run background refresh on every navigation to catch permission/role updates without full login
router.afterEach(() => {
    const authStore = useAuthStore();
    if (authStore.user) {
        axios.get('/portal/api/me').then(response => {
            authStore.permissions = response.data.permissions || [];
            authStore.roles = response.data.roles || [];
        }).catch(() => {});
    }
});

export default router;
