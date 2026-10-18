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
            meta: { title: 'New booking', permission: 'cargo-book' },
        },
        {
            path: '/cargo-bookings',
            name: 'CargoBookings',
            component: () => import('../views/cargo/CargoBookingsListView.vue'),
            meta: { title: 'Bookings', permission: 'booking:read' },
        },
        {
            path: '/cargo-bookings/:id',
            name: 'CargoBookingDetails',
            component: () => import('../views/cargo/CargoBookingDetailView.vue'),
            meta: { title: 'Booking' },
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
            meta: { title: 'Change password' },
        },
        {
            path: '/2fa-setup',
            name: 'TwoFactorSetup',
            component: () => import('../views/auth/TwoFactorSetupView.vue'),
            meta: { title: '2FA security' },
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
