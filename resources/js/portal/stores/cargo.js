import { defineStore } from 'pinia';
import axios from 'axios';
import { isoDate, num, normalisePhone, apiError } from '../utils/cargoUi';
import { payerHint } from '../utils/msisdn';
import { useAuthStore } from './auth';

const API = '/portal/api/cargo';
const ROUTE_KEY = 'cargo.booking.route';

const blankPerson = () => ({ name: '', phone: '', id_type: '', id_number: '', showId: false });
const blankLine = id => ({ id, cargo_type_id: '', quantity: 1, weight_kg: '', length_m: '', width_m: '', height_m: '',
    description: '', declared_value: '', is_fragile: false, vehicle_reg_number: '', driver_name: '', driver_phone: '', more: false });

function rememberedRoute() {
    try { return JSON.parse(localStorage.getItem(ROUTE_KEY) || 'null'); } catch (e) { return null; }
}

/**
 * The sales portal's booking form. Same flow and rules as the admin booking page
 * (bookingTerminal in admin/cargo/booking.blade.php): prices come from the server as you type,
 * space is held when leaving the Cargo step, and the hold is replaced rather than stacked.
 */
export const useCargoStore = defineStore('cargo', {
    state: () => ({
        types: [],
        paymentMethods: [],
        step: 1,
        // Departures
        departures: [],
        loadingDepartures: false,
        days: 3,
        otherDate: '',
        routeFilter: rememberedRoute(),
        service: null,
        // Cargo lines + live quote
        lines: [blankLine(1)],
        nextId: 2,
        quote: null,
        quoting: false,
        quoteErrors: {},
        quoteMessage: '',
        quoteSeq: 0,
        sentIds: [],
        showValueError: false,
        // Space hold
        lockId: null,
        lockExpires: null,
        lockHash: null,
        holding: false,
        secondsLeft: 0,
        // People
        people: { sender: blankPerson(), receiver: blankPerson() },
        specialInstructions: '',
        suggestions: { sender: [], receiver: [] },
        // Review & pay
        discount: { on: false, amount: '', reason: '' },
        paymentMethod: null,
        payerMsisdn: '',
        errors: {},
        banner: null,
        submitting: false,
        // After booking
        booked: null,          // { consignment_id, consignment_number, payment }
        pendingPayment: null,  // open push / Lipa Namba attempt shown in the payment panel
        paidPayment: null,
    }),

    getters: {
        busy: s => s.holding || s.submitting || s.quoting,
        canOverride: () => useAuthStore().can('cargo-override-fare'),
        // Only cargo types with a fare in force on the chosen departure can be booked
        bookableTypes: s => (s.service?.priced_type_ids ? s.types.filter(t => s.service.priced_type_ids.includes(t.id)) : s.types),
        typeGroups() {
            const groups = {};
            this.bookableTypes.forEach(t => { (groups[t.category] ??= []).push(t); });
            return Object.entries(groups).map(([name, types]) => ({ name: name.charAt(0).toUpperCase() + name.slice(1), types }));
        },
        routeChoices: s => {
            const routes = new Map();
            s.departures.forEach(d => d.route && routes.set(d.route_id, d.route.name));
            if (s.routeFilter && !routes.has(s.routeFilter.id)) routes.set(s.routeFilter.id, s.routeFilter.name);
            return [...routes].map(([id, name]) => ({ id, name })).sort((a, b) => a.name.localeCompare(b.name));
        },
        departureGroups: s => {
            const groups = new Map();
            if (!s.otherDate) groups.set(isoDate(), []);
            s.departures
                .filter(d => !s.routeFilter || d.route_id === s.routeFilter.id)
                .forEach(d => {
                    const day = String(d.departure_date).slice(0, 10);
                    if (!groups.has(day)) groups.set(day, []);
                    groups.get(day).push(d);
                });
            return [...groups].sort(([a], [b]) => a.localeCompare(b)).map(([date, services]) => ({ date, services }));
        },
        countdown: s => {
            const t = Math.max(0, s.secondsLeft);
            return `${String(Math.floor(t / 60)).padStart(2, '0')}:${String(t % 60).padStart(2, '0')}`;
        },
        currentMethod: s => s.paymentMethods.find(m => m.slug === s.paymentMethod),
        methodNotLive() { const m = this.currentMethod; return m?.flow === 'ussd_push' && !m.live && !m.simulated; },
        payerHintInfo() { return payerHint(this.paymentMethods, this.currentMethod, this.payerMsisdn); },
        confirmLabel() {
            if (this.methodNotLive) return 'Save booking — awaiting payment';
            const total = this.quote ? `TZS ${num(this.quote.net_amount)}` : '';
            return {
                cash: `Book & take ${total} cash`,
                ussd_push: 'Book & send USSD push',
                lipa_namba: 'Book & show Lipa Namba',
            }[this.currentMethod?.flow] || 'Book';
        },
        totalsText: s => {
            const t = s.quote?.totals;
            if (!t) return '';
            return `${num(t.pieces)} piece${t.pieces === 1 ? '' : 's'} · ${num(t.weight_kg, 1)} kg`
                + (t.volume_cbm > 0 ? ` · ${num(t.volume_cbm, 2)} m³` : '') + (t.lane_meters > 0 ? ` · ${num(t.lane_meters, 1)} lane m` : '');
        },
        discountPending: s => s.discount.on && Number(s.discount.amount) > 0 && s.discount.reason.trim().length < 5,
    },

    actions: {
        async loadReferenceData() {
            const [types, methods] = await Promise.allSettled([axios.get(`${API}/types`), axios.get(`${API}/payment-methods`)]);
            if (types.status === 'fulfilled') this.types = types.value.data;
            if (methods.status === 'fulfilled') {
                this.paymentMethods = methods.value.data;
                if (!this.paymentMethod) this.paymentMethod = (this.paymentMethods.find(m => m.slug === 'cash') || this.paymentMethods[0])?.slug || null;
            }
        },

        // ── Step 1: departures (the next few days in one list) ──
        async loadDepartures(date = null) {
            this.loadingDepartures = true;
            const params = date || this.otherDate ? { date: date || this.otherDate } : { days: this.days };
            try {
                this.departures = (await axios.get(`${API}/availability`, { params })).data;
            } catch (e) {
                this.departures = [];
                this.banner = { type: 'error', text: `Couldn't load departures: ${apiError(e).message}` };
            } finally {
                this.loadingDepartures = false;
            }
        },
        setRoute(r) {
            this.routeFilter = r ? { id: r.id, name: r.name } : null;
            try { r ? localStorage.setItem(ROUTE_KEY, JSON.stringify(this.routeFilter)) : localStorage.removeItem(ROUTE_KEY); } catch (e) { /* storage blocked */ }
        },
        async selectService(svc) {
            if (svc.space?.full || !svc.priced_type_ids?.length) return;
            if (this.service?.id !== svc.id && this.lockId) await this.releaseHold();
            this.service = svc;
            // Lines with a type that has no fare on this departure are cleared
            const dropped = this.lines.filter(l => l.cargo_type_id && !svc.priced_type_ids.includes(Number(l.cargo_type_id)));
            if (dropped.length) {
                const names = [...new Set(dropped.map(l => this.typeOf(l)?.name).filter(Boolean))].join(', ');
                dropped.forEach(l => { l.cargo_type_id = ''; });
                this.banner = { type: 'error', text: `${names} can't be booked on this departure (no fare on its route) — choose another type.` };
            }
            this.step = 2;
            this.fetchQuote();
        },

        // ── Step 2: cargo lines ─────────────────────
        addLine() { this.lines.push(blankLine(this.nextId++)); },
        removeLine(line) { this.lines = this.lines.filter(l => l.id !== line.id); },
        typeOf(line) { return this.types.find(t => String(t.id) === String(line.cargo_type_id)) || null; },
        unit(line) { return this.typeOf(line)?.measurement_unit; },
        onTypeChange(line) {
            if (this.unit(line) === 'lane_meter') line.quantity = 1;
            if (this.typeOf(line)?.is_fragile) line.is_fragile = true;
        },
        lineQuote(line) {
            const i = this.sentIds.indexOf(line.id);
            return i >= 0 ? (this.quote?.items?.[i] || null) : null;
        },
        err(line, field) {
            const i = this.sentIds.indexOf(line.id);
            return i >= 0 ? (this.quoteErrors[`items.${i}.${field}`] || null) : null;
        },
        lineHasError(line) {
            const i = this.sentIds.indexOf(line.id);
            // A missing declared value is a prompt, not a mistake, until the clerk tries to continue
            return i >= 0 && Object.keys(this.quoteErrors).some(k => k.startsWith(`items.${i}.`) && (this.showValueError || !k.endsWith('.declared_value')));
        },
        // The fare charges a % of the declared value (known once the line is priced, or from its error)
        needsValue(line) { return !!(this.lineQuote(line)?.value_charge_pct > 0 || this.err(line, 'declared_value')); },
        itemsPayload() {
            const typed = this.lines.filter(l => this.typeOf(l));
            const n = v => (v === '' || v === null || v === undefined || Number.isNaN(Number(v))) ? null : Number(v);
            return {
                ids: typed.map(l => l.id),
                items: typed.map(l => {
                    const u = this.unit(l);
                    const item = { cargo_type_id: Number(l.cargo_type_id), quantity: u === 'lane_meter' ? 1 : Math.max(1, parseInt(l.quantity, 10) || 1),
                        weight_kg: n(l.weight_kg), is_fragile: !!l.is_fragile, description: l.description || null, declared_value: n(l.declared_value) };
                    if (u === 'cbm') Object.assign(item, { length_m: n(l.length_m), width_m: n(l.width_m), height_m: n(l.height_m) });
                    if (u === 'lane_meter') Object.assign(item, { vehicle_reg_number: l.vehicle_reg_number || null, driver_name: l.driver_name || null, driver_phone: l.driver_phone || null });
                    return item;
                }),
            };
        },
        // The live quote only applies the discount once a reason is given (the server requires one)
        discountPayload(forQuote = false) {
            if (!this.discount.on || !(Number(this.discount.amount) > 0)) return {};
            if (forQuote && this.discount.reason.trim().length < 5) return {};
            return { discount_amount: Number(this.discount.amount), discount_reason: this.discount.reason };
        },
        async fetchQuote() {
            const { ids, items } = this.itemsPayload();
            if (!this.service || !items.length) { this.quote = null; this.sentIds = []; this.quoteErrors = {}; this.quoteMessage = ''; return; }
            const seq = ++this.quoteSeq;
            this.quoting = true;
            try {
                const { data } = await axios.post(`${API}/calculate-fare`, { transport_service_id: this.service.id, items, ...this.discountPayload(true) });
                if (seq !== this.quoteSeq) return;
                this.quote = data; this.sentIds = ids; this.quoteErrors = {}; this.quoteMessage = '';
            } catch (e) {
                if (seq !== this.quoteSeq) return;
                const { message, errors } = apiError(e);
                this.quote = null; this.sentIds = ids; this.quoteErrors = errors;
                const keys = Object.keys(errors);
                this.quoteMessage = !keys.length ? message
                    : keys.every(k => k.endsWith('.declared_value')) ? 'Enter the declared value to see the price.' : 'Fix the highlighted fields to see the price.';
            } finally {
                if (seq === this.quoteSeq) this.quoting = false;
            }
        },

        // ── Space hold ──────────────────────────────
        async holdSpace() {
            await this.fetchQuote();
            if (!this.quote) {
                this.banner = { type: 'error', text: this.lines.some(l => this.typeOf(l)) ? 'Fix the highlighted fields before continuing.' : 'Add at least one item.' };
                return false;
            }
            const { items } = this.itemsPayload();
            const hash = JSON.stringify(items.map(i => [i.cargo_type_id, i.quantity, i.weight_kg, i.length_m, i.width_m, i.height_m]));
            if (this.lockId && this.lockHash === hash && this.secondsLeft > 15) return true;

            this.holding = true;
            try {
                const { data } = await axios.post(`${API}/lock-inventory`, { transport_service_id: this.service.id, items, replace_lock_id: this.lockId });
                this.lockId = data.lock_id;
                this.lockExpires = new Date(data.expires_at).getTime();
                this.lockHash = hash;
                this.tick();
                return true;
            } catch (e) {
                const { message, errors } = apiError(e);
                this.lockId = null;
                this.quoteErrors = errors;
                this.banner = { type: 'error', text: message };
                return false;
            } finally {
                this.holding = false;
            }
        },
        async releaseHold() {
            const id = this.lockId;
            this.lockId = null; this.lockHash = null;
            if (id) { try { await axios.post(`${API}/release-lock`, { lock_id: id }); } catch (e) { /* expired already */ } }
        },
        // Leaving the page mid-booking frees the held space
        releaseOnLeave() {
            if (!this.lockId || this.booked) return;
            const body = new FormData();
            body.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
            body.append('lock_id', this.lockId);
            navigator.sendBeacon(`${API}/release-lock`, body);
            this.lockId = null;
        },
        tick() {
            if (!this.lockId) return;
            this.secondsLeft = Math.round((this.lockExpires - Date.now()) / 1000);
            if (this.secondsLeft <= 0) {
                this.lockId = null; this.lockHash = null;
                if (this.step > 2) this.step = 2;
                this.banner = { type: 'warning', text: 'The space hold expired. Your items are kept — press Continue to hold space again.' };
            }
        },

        // ── Steps ───────────────────────────────────
        goStep(n) {
            if (n > this.step || this.pendingPayment) return;
            this.step = n;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        async next() {
            this.banner = null;
            if (this.step === 2) {
                this.showValueError = true;
                if (!(await this.holdSpace())) return;
            } else if (this.step === 3) {
                if (!this.validatePeople()) return;
                if (!this.payerMsisdn) this.payerMsisdn = this.people.sender.phone;
            }
            this.step++;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        // ── Step 3: people ──────────────────────────
        validatePeople() {
            const e = {};
            ['sender', 'receiver'].forEach(role => {
                const p = this.people[role];
                if (!p.name.trim()) e[`${role}_name`] = 'Enter the full name.';
                const phone = normalisePhone(p.phone);
                if (!phone) e[`${role}_phone`] = 'Enter a valid mobile number, e.g. 0754 123 456 or +254 712 345 678.';
                else p.phone = phone;
            });
            this.errors = e;
            if (Object.keys(e).length) this.banner = { type: 'error', text: 'Check the sender and receiver details.' };
            return !Object.keys(e).length;
        },
        sameAsSender() {
            const s = this.people.sender;
            Object.assign(this.people.receiver, { name: s.name, phone: s.phone, id_type: s.id_type, id_number: s.id_number, showId: s.showId });
            this.suggestions.receiver = [];
        },
        async fetchCustomers(role) {
            try {
                const { data } = await axios.get(`${API}/customers`, { params: { phone: this.people[role].phone } });
                const current = this.people[role].name.trim().toLowerCase();
                this.suggestions[role] = data.filter(p => p.name.trim().toLowerCase() !== current);
            } catch (e) { this.suggestions[role] = []; }
        },
        useCustomer(role, p) {
            Object.assign(this.people[role], { name: p.name, phone: p.phone || this.people[role].phone });
            if (p.id_number) Object.assign(this.people[role], { id_type: p.id_type || '', id_number: p.id_number, showId: true });
            this.suggestions[role] = [];
        },

        // ── Step 4: book ────────────────────────────
        async confirm() {
            this.banner = null; this.errors = {};
            if (!this.lockId) { this.step = 2; this.banner = { type: 'warning', text: 'The space hold expired. Press Continue to hold space again.' }; return; }
            if (this.discountPending) { this.errors.discount_reason = 'Give a reason for the discount (at least 5 characters).'; return; }
            const { ids, items } = this.itemsPayload();
            const s = this.people.sender, r = this.people.receiver;
            this.submitting = true;
            try {
                const { data } = await axios.post(`${API}/confirm-booking`, {
                    lock_id: this.lockId, transport_service_id: this.service.id, items, ...this.discountPayload(),
                    sender_name: s.name.trim(), sender_phone: s.phone, sender_id_type: s.id_type || null, sender_id_number: s.id_number || null,
                    receiver_name: r.name.trim(), receiver_phone: r.phone, receiver_id_type: r.id_type || null, receiver_id_number: r.id_number || null,
                    special_instructions: this.specialInstructions || null,
                    payment_method: this.paymentMethod, payer_msisdn: this.currentMethod?.flow === 'ussd_push' ? this.payerMsisdn : null,
                });
                this.lockId = null;
                this.booked = data;
                if (data.payment?.status === 'paid') this.paidPayment = data.payment;
                else this.pendingPayment = data.payment; // push tracker / Lipa Namba in the payment panel
            } catch (e) {
                const { message, errors } = apiError(e);
                const keys = Object.keys(errors);
                if (keys.some(k => k.startsWith('items.'))) { this.sentIds = ids; this.quoteErrors = errors; this.step = 2; }
                else if (keys.some(k => /^(sender|receiver)_/.test(k))) { this.errors = errors; this.step = 3; }
                else { this.errors = errors; }
                if (/hold|expired/i.test(message)) { this.lockId = null; this.step = 2; }
                this.banner = { type: 'error', text: message };
            } finally {
                this.submitting = false;
            }
        },

        startNew(sameSender = false) {
            const sender = sameSender ? { ...this.people.sender } : blankPerson();
            Object.assign(this, {
                step: 1, service: null, lines: [blankLine(this.nextId++)], quote: null, sentIds: [], quoteErrors: {}, quoteMessage: '',
                showValueError: false, lockId: null, lockHash: null, people: { sender, receiver: blankPerson() }, specialInstructions: '',
                suggestions: { sender: [], receiver: [] }, discount: { on: false, amount: '', reason: '' }, payerMsisdn: '',
                errors: {}, banner: null, booked: null, pendingPayment: null, paidPayment: null, otherDate: '', days: 3,
                paymentMethod: (this.paymentMethods.find(m => m.slug === 'cash') || this.paymentMethods[0])?.slug || null,
            });
        },
    },
});
