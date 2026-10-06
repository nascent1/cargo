import { defineStore } from 'pinia';
import axios from 'axios';

export const useWalletStore = defineStore('wallet', {
    state: () => ({
        summary: {
            agent_code: '',
            agency_name: '',
            balance: 0,
            credit_limit: 0,
            available_credit: 0,
            currency: 'TZS',
            security_deposit: 0,
            commission_rate: 0,
            commission_method: 'post_sale',
        },
        transactions: [],
        transactionsMeta: { current_page: 1, last_page: 1, total: 0 },
        loading: false,
        topUpLoading: false,
        error: null,
    }),

    getters: {
        formattedBalance: (state) => {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: state.summary.currency || 'TZS',
                minimumFractionDigits: 0,
            }).format(state.summary.balance || 0);
        },
        formattedAvailableCredit: (state) => {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: state.summary.currency || 'TZS',
                minimumFractionDigits: 0,
            }).format(state.summary.available_credit || 0);
        },
        isLowBalance: (state) => state.summary.balance < 50000,
    },

    actions: {
        async fetchSummary() {
            this.loading = true;
            this.error = null;
            try {
                const { data } = await axios.get('/api/v1/portal/wallet');
                this.summary = data.data;
            } catch (e) {
                this.error = e.response?.data?.error || 'Failed to load wallet.';
            } finally {
                this.loading = false;
            }
        },

        async fetchTransactions(page = 1, type = null) {
            this.loading = true;
            try {
                const params = { page, per_page: 20 };
                if (type) params.type = type;
                const { data } = await axios.get('/api/v1/portal/wallet/transactions', { params });
                this.transactions = data.data;
                this.transactionsMeta = data.meta || { current_page: data.current_page, last_page: data.last_page, total: data.total };
            } catch (e) {
                this.error = e.response?.data?.error || 'Failed to load transactions.';
            } finally {
                this.loading = false;
            }
        },

        async requestTopUp(amount, paymentMethod, reference = null, notes = null) {
            this.topUpLoading = true;
            this.error = null;
            try {
                const { data } = await axios.post('/api/v1/portal/wallet/top-up', {
                    amount,
                    payment_method: paymentMethod,
                    reference,
                    notes,
                });
                // Refresh summary after request
                await this.fetchSummary();
                return data;
            } catch (e) {
                this.error = e.response?.data?.error || 'Top-up request failed.';
                throw e;
            } finally {
                this.topUpLoading = false;
            }
        },
    },
});
