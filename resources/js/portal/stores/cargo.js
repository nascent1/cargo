import { defineStore } from 'pinia';
import axios from 'axios';

export const useCargoStore = defineStore('cargo', {
    state: () => ({
        cargoTypes: [],
        paymentMethods: [],
        availableServices: [],
        loading: false,
        error: null,
        bookingState: {
            serviceId: null,
            // Selected voyage kept as an object — the availability list changes with the date
            service: null,
            routeId: null,
            sender: {
                name: '',
                phone: '',
                email: '',
                id_type: '',
                id_number: ''
            },
            receiver: {
                name: '',
                phone: '',
                email: ''
            },
            items: [], // Array of items with weight, type, dimensions
            currency: 'TZS',
            paymentMethod: null,
            payerMsisdn: '',
            lockId: null,
            expiresAt: null
        }
    }),
    
    actions: {
        async fetchCargoTypes() {
            try {
                const res = await axios.get('/portal/api/cargo/types');
                this.cargoTypes = res.data;
            } catch (err) {
                console.error('Failed to load cargo types', err);
            }
        },

        async fetchPaymentMethods() {
            try {
                const res = await axios.get('/portal/api/cargo/payment-methods');
                this.paymentMethods = res.data;
                // Default to cash when active, otherwise the first channel
                if (!this.bookingState.paymentMethod) {
                    this.bookingState.paymentMethod = (res.data.find(m => m.slug === 'cash') || res.data[0])?.slug || null;
                }
            } catch (err) {
                console.error('Failed to load payment methods', err);
            }
        },

        async fetchAvailability(date) {
            this.loading = true;
            try {
                const res = await axios.get(`/portal/api/cargo/availability?date=${date}`);
                this.availableServices = res.data;
            } catch (err) {
                this.error = 'Failed to load available services';
            } finally {
                this.loading = false;
            }
        },

        async calculateFare() {
            try {
                const res = await axios.post('/portal/api/cargo/calculate-fare', {
                    transport_service_id: this.bookingState.serviceId,
                    currency: this.bookingState.currency,
                    items: this.bookingState.items
                });
                return res.data; // breakdown object
            } catch (err) {
                this.error = err.response?.data?.error || 'Calculation failed';
                throw err;
            }
        },

        async lockInventory() {
            try {
                const res = await axios.post('/portal/api/cargo/lock-inventory', {
                    transport_service_id: this.bookingState.serviceId,
                    items: this.bookingState.items
                });
                this.bookingState.lockId = res.data.lock_id;
                this.bookingState.expiresAt = res.data.expires_at;
                return res.data;
            } catch (err) {
                if (err.response?.data?.errors) {
                    this.error = Object.values(err.response.data.errors)[0][0];
                } else {
                    this.error = err.response?.data?.message || err.response?.data?.error || 'Failed to lock inventory';
                }
                throw err;
            }
        },

        async confirmBooking() {
            try {
                const payload = {
                    lock_id: this.bookingState.lockId,
                    sender_name: this.bookingState.sender.name,
                    sender_phone: this.bookingState.sender.phone,
                    sender_email: this.bookingState.sender.email,
                    sender_id_type: this.bookingState.sender.id_type,
                    sender_id_number: this.bookingState.sender.id_number,
                    receiver_name: this.bookingState.receiver.name,
                    receiver_phone: this.bookingState.receiver.phone,
                    receiver_email: this.bookingState.receiver.email,
                    currency: this.bookingState.currency,
                    payment_method: this.bookingState.paymentMethod,
                    payer_msisdn: this.bookingState.payerMsisdn || null,
                    items: this.bookingState.items
                };

                const res = await axios.post('/portal/api/cargo/confirm-booking', payload);
                return res.data;
            } catch (err) {
                if (err.response?.data?.errors) {
                    this.error = Object.values(err.response.data.errors)[0][0];
                } else {
                    this.error = err.response?.data?.message || err.response?.data?.error || 'Booking failed';
                }
                throw err;
            }
        },

        resetBooking() {
            this.bookingState = {
                serviceId: null,
                service: null,
                routeId: null,
                sender: { name: '', phone: '', email: '', id_type: '', id_number: '' },
                receiver: { name: '', phone: '', email: '' },
                items: [],
                currency: 'TZS',
                paymentMethod: (this.paymentMethods.find(m => m.slug === 'cash') || this.paymentMethods[0])?.slug || null,
                payerMsisdn: '',
                lockId: null,
                expiresAt: null
            };
            this.error = null;
        }
    }
});
