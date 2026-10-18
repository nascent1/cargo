import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import axios from 'axios';
import { useShiftStore } from './stores/shift';

// Configure Axios
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
const csrfToken = document.querySelector('meta[name="csrf-token"]');
if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
}
axios.defaults.withCredentials = true;

const pinia = createPinia();
const app = createApp(App);

// Booking, payment and cancel calls answer SHIFT_ENDED once the clerk's shift is over
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 403 && error.response?.data?.code === 'SHIFT_ENDED') {
            useShiftStore(pinia).markEnded(error.response.data.shift);
        }
        return Promise.reject(error);
    }
);

app.use(pinia);
app.use(router);

app.mount('#portal-app');
