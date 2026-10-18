import { defineStore } from 'pinia';
import axios from 'axios';

/**
 * The clerk's shift (portal/api/shift): state is none | not_started | active | ended.
 * `ended` opens the shift-ended dialog; any booking call answering SHIFT_ENDED sets it too.
 */
export const useShiftStore = defineStore('shift', {
    state: () => ({
        context: null,
        ended: false,
        dismissed: false,
        summaryOpen: false,
        warned: false,
        tick: Date.now(),
    }),

    getters: {
        applies: (s) => !!s.context?.applies,
        status: (s) => s.context?.state || 'none',
        shift: (s) => s.context?.shift || null,
        summary: (s) => s.context?.summary || null,
        pending: (s) => s.context?.pending_extension || null,
        warningMinutes: (s) => s.context?.warning_minutes ?? 15,
        // Counted down locally between refreshes
        minutesLeft(s) {
            if (s.context?.state !== 'active' || !s.context?.shift?.ends_at) return null;
            return Math.max(0, Math.floor((new Date(s.context.shift.ends_at) - s.tick) / 60000));
        },
        showDialog: (s) => s.ended && !s.dismissed,
    },

    actions: {
        async refresh() {
            try {
                const { data } = await axios.get('/portal/api/shift');
                this.apply(data);
            } catch {
                // Keep the last known state; the next refresh retries
            }
        },

        apply(context) {
            const wasEnded = this.ended;
            this.context = context;
            this.ended = context?.state === 'ended';
            if (!this.ended) this.dismissed = false;
            if (wasEnded && context?.state === 'active') {
                window.notify?.('Your shift extension was approved. You can carry on selling.', 'success');
            }
            if (context?.state !== 'active') this.warned = false;
        },

        // A guarded endpoint answered SHIFT_ENDED
        markEnded(context) {
            if (context) this.apply(context);
            this.ended = true;
            this.dismissed = false;
        },

        async end() {
            const { data } = await axios.post('/portal/api/shift/end');
            this.apply(data.shift);
            // They ended it themselves: show the handover summary, not the "shift ended" alert
            this.dismissed = true;
            this.summaryOpen = true;
            return data.message;
        },

        async requestExtension(hours, reason) {
            const { data } = await axios.post('/portal/api/shift/extension', { hours, reason });
            this.apply(data.shift);
            return data.message;
        },

        // Called every few seconds: countdown + one warning toast near the end
        heartbeat() {
            this.tick = Date.now();
            const left = this.minutesLeft;
            if (left !== null && left <= this.warningMinutes && !this.warned) {
                this.warned = true;
                window.notify?.(`Your shift ends in ${left} min. Ask for more time now if you need it.`, 'info');
            }
            if (left === 0 && this.context?.state === 'active') this.refresh();
        },
    },
});
