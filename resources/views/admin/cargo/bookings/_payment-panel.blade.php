{{-- Live payment panel for a booking awaiting payment (USSD push tracker / Lipa Namba / channel picker) --}}
@php
    $openPayment = $consignment->payments->first(fn ($p) => $p->isOpen());
    $latestPayment = $consignment->payments->first();
    $panelConfig = [
        'apiBase'        => url('/admin/cargo/api'),
        'consignmentId'  => $consignment->id,
        'payment'        => ($openPayment ?? $latestPayment)?->toApi(),
        'methods'        => \App\Models\PaymentMethod::activeOptions(),
        'senderPhone'    => \App\Support\Msisdn::local($consignment->sender_phone) ?? $consignment->sender_phone,
        'networkLabels'  => \App\Support\Msisdn::NETWORK_LABELS,
        'networkPrefixes'=> \App\Support\Msisdn::NETWORK_PREFIXES,
    ];
@endphp

<div x-data="cargoPaymentPanel(@js($panelConfig))" class="bg-white rounded-3xl border-2 border-orange-200 shadow-sm overflow-hidden">
    {{-- Header --}}
    <div class="px-6 py-4 bg-orange-50 border-b border-orange-100 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-orange-500 text-white flex items-center justify-center shadow-sm"><i class="fas fa-hourglass-half"></i></div>
            <div>
                <h3 class="text-base font-black text-orange-900">Awaiting payment</h3>
                <p class="text-xs text-orange-700">Space is reserved. The booking completes as soon as payment is confirmed.</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xl font-black text-slate-900 font-mono">{{ $consignment->billing_currency }} {{ number_format($consignment->billing_amount) }}</span>
            <span x-show="open && countdown" class="px-2.5 py-1 rounded-full bg-white border border-orange-200 text-orange-700 text-xs font-mono font-bold" :class="secondsLeft < 120 ? 'animate-pulse' : ''">
                <i class="fas fa-clock mr-1"></i><span x-text="countdown"></span>
            </span>
        </div>
    </div>

    <div class="p-6 space-y-4">
        <div x-show="error" x-cloak class="px-4 py-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm font-semibold flex items-start gap-2">
            <i class="fas fa-exclamation-circle mt-0.5"></i><span x-text="error"></span>
        </div>
        <div x-show="notice" x-cloak class="px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-semibold flex items-start gap-2">
            <i class="fas fa-check-circle mt-0.5"></i><span x-text="notice"></span>
        </div>

        {{-- USSD push tracker --}}
        <template x-if="open && payment.flow === 'ussd_push'">
            <div class="space-y-4">
                <div class="flex items-center gap-2 text-sm text-slate-600">
                    <i :class="'fas ' + payment.channel.icon" class="text-cyan-600"></i>
                    <span class="font-bold text-slate-800" x-text="payment.channel.name"></span>
                    <span x-show="payment.simulated" class="px-1.5 py-0.5 rounded bg-violet-100 text-violet-700 text-[10px] font-black uppercase">Simulated</span>
                </div>
                <ol class="grid grid-cols-1 sm:grid-cols-4 gap-2">
                    <template x-for="(step, i) in steps" :key="i">
                        <li class="rounded-xl border p-3 flex items-center gap-2.5"
                            :class="step.state === 'done' ? 'bg-emerald-50 border-emerald-200' : step.state === 'active' ? 'bg-cyan-50 border-cyan-300' : 'bg-slate-50 border-slate-200'">
                            <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs shrink-0"
                                  :class="step.state === 'done' ? 'bg-emerald-500 text-white' : step.state === 'active' ? 'bg-cyan-500 text-white' : 'bg-slate-200 text-slate-400'">
                                <i class="fas" :class="step.state === 'done' ? 'fa-check' : step.state === 'active' ? 'fa-circle-notch fa-spin' : step.icon"></i>
                            </span>
                            <span class="text-xs font-bold" :class="step.state === 'pending' ? 'text-slate-400' : 'text-slate-800'" x-text="step.label"></span>
                        </li>
                    </template>
                </ol>
                <p x-show="payment.status === 'awaiting_customer'" class="text-xs text-slate-500">
                    Ask the customer to check <strong x-text="payment.payer_masked"></strong> and enter their PIN. This page updates automatically.
                </p>
                <p x-show="payment.worker_delayed" class="text-xs text-amber-700"><i class="fas fa-exclamation-triangle mr-1"></i>The payment queue worker looks idle — sending the push directly.</p>
                <div class="flex flex-wrap items-end gap-2 pt-1">
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Resend to</label>
                        <input type="tel" x-model="payer" placeholder="0754 123 456" class="w-48 bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-sm font-mono focus:outline-none focus:border-cyan-500">
                    </div>
                    <button type="button" @click="resend()" :disabled="busy" class="px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-bold disabled:opacity-50"><i class="fas fa-redo mr-1"></i> Resend push</button>
                </div>
            </div>
        </template>

        {{-- Lipa Namba --}}
        <template x-if="open && payment.flow === 'lipa_namba'">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="rounded-2xl border-2 border-dashed border-slate-300 p-5 bg-slate-50 space-y-3">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Give the customer these details</p>
                    <template x-for="row in lipaRows" :key="row.label">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-slate-500" x-text="row.label"></span>
                            <span class="flex items-center gap-2">
                                <span class="font-mono font-black text-slate-900" :class="row.big ? 'text-xl' : 'text-sm'" x-text="row.value"></span>
                                <button type="button" @click="copy(row.value)" class="text-slate-400 hover:text-cyan-600" title="Copy"><i class="fas fa-copy text-xs"></i></button>
                            </span>
                        </div>
                    </template>
                    <p class="text-[11px] text-slate-500 pt-2 border-t border-slate-200" x-text="payment.lipa?.instructions"></p>
                    <button type="button" @click="smsInstructions()" :disabled="busy" class="w-full px-4 py-2 rounded-xl bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 text-xs font-bold disabled:opacity-50">
                        <i class="fas fa-sms mr-1"></i> SMS these instructions to the customer
                    </button>
                </div>
                <form @submit.prevent="confirmLipa()" class="space-y-3">
                    <p class="text-sm text-slate-600">Once the payment SMS arrives on the business phone, enter its transaction code and confirm.</p>
                    <div>
                        <label class="text-[11px] font-bold text-slate-600 block mb-1">Transaction reference (from the SMS) <span class="text-rose-500">*</span></label>
                        <input type="text" x-model="reference" required placeholder="e.g. 9JK4XYZ1AB" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-mono uppercase focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                    </div>
                    <div>
                        <label class="text-[11px] font-bold text-slate-600 block mb-1">Payer's number (optional)</label>
                        <input type="tel" x-model="lipaPayer" placeholder="0754 123 456" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-mono focus:outline-none focus:border-emerald-500">
                    </div>
                    <button type="submit" :disabled="busy || !reference" class="w-full px-4 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-black disabled:opacity-50">
                        <i class="fas fa-check-circle mr-1"></i> Confirm payment
                    </button>
                </form>
            </div>
        </template>

        {{-- No open attempt: last outcome + channel picker --}}
        <template x-if="!open">
            <div class="space-y-3">
                <div x-show="payment && payment.status !== 'paid'" class="px-4 py-3 rounded-xl bg-rose-50 border border-rose-200 text-sm">
                    <p class="font-bold text-rose-800"><span x-text="payment?.channel?.name"></span> — <span class="capitalize" x-text="(payment?.status || '').replace('_', ' ')"></span></p>
                    <p class="text-rose-700 text-xs mt-0.5" x-text="payment?.failure_reason"></p>
                </div>
                <p class="text-sm font-semibold text-slate-700">Take the payment:</p>
            </div>
        </template>

        <div x-show="!open || picking" class="space-y-3">
            <div class="flex flex-wrap gap-2">
                <template x-for="m in methods" :key="m.slug">
                    <button type="button" @click="choice = m.slug" :disabled="!usable(m)" :title="usable(m) ? '' : m.name + ' isn\'t live yet — its credentials aren\'t set up'"
                            class="px-3 py-2 rounded-xl border-2 text-xs font-bold flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="choice === m.slug ? 'border-cyan-500 bg-cyan-50 text-cyan-700' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'">
                        <i :class="'fas ' + m.icon"></i><span x-text="m.name"></span>
                        <span x-show="m.simulated" class="px-1 rounded bg-violet-100 text-violet-700 text-[8px] uppercase">Sim</span>
                        <span x-show="!m.simulated && m.live === false" class="px-1 rounded bg-amber-100 text-amber-700 text-[8px] uppercase">Not live</span>
                    </button>
                </template>
            </div>
            <div x-show="chosen?.flow === 'ussd_push'">
                <label class="text-[11px] font-bold text-slate-600 block mb-1">Payer's phone (receives the push)</label>
                <input type="tel" x-model="payer" placeholder="0754 123 456" class="w-56 bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-sm font-mono focus:outline-none focus:border-cyan-500">
                <p class="text-[11px] mt-1" :class="payerHint.cls" x-text="payerHint.text"></p>
                <p x-show="!usable(chosen)" class="text-[11px] mt-1 text-amber-700 font-semibold" x-text="(chosen?.name || '') + ' isn\'t live yet — choose another channel.'"></p>
            </div>
            <div class="flex gap-2">
                <button type="button" @click="startPayment()" :disabled="busy || !choice || !usable(chosen)" class="px-5 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-bold disabled:opacity-50">
                    <i class="fas fa-paper-plane mr-1"></i> <span x-text="chosen?.flow === 'cash' ? 'Record cash payment' : chosen?.flow === 'lipa_namba' ? 'Use Lipa Namba' : 'Send USSD push'"></span>
                </button>
                <button type="button" x-show="open" @click="picking = false" class="px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-600 text-sm font-semibold">Back</button>
            </div>
        </div>

        {{-- Footer actions --}}
        <div class="flex flex-wrap gap-2 pt-3 border-t border-slate-100">
            <button type="button" x-show="open && !picking" @click="picking = true" class="px-4 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold">
                <i class="fas fa-exchange-alt mr-1"></i> Switch payment channel
            </button>
            <button type="button" @click="showCancelModal = true" class="px-4 py-2 rounded-xl bg-white border border-rose-200 hover:bg-rose-50 text-rose-600 text-xs font-bold">
                <i class="fas fa-ban mr-1"></i> Cancel booking
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    const OPEN = ['initiated', 'sending', 'awaiting_customer'];

    Alpine.data('cargoPaymentPanel', (cfg) => ({
        payment: cfg.payment,
        methods: cfg.methods,
        picking: false,
        choice: null,
        payer: cfg.payment?.payer_msisdn || cfg.senderPhone || '',
        reference: '',
        lipaPayer: '',
        busy: false,
        error: '',
        notice: '',
        now: Date.now(),
        timer: null,

        init() {
            this.choice = (this.methods.find(m => m.flow === 'ussd_push' && this.usable(m)) || this.methods.find(m => this.usable(m)))?.slug || null;
            setInterval(() => { this.now = Date.now(); }, 1000);
            this.poll();
        },

        get open() { return !!this.payment && OPEN.includes(this.payment.status); },
        get chosen() { return this.methods.find(m => m.slug === this.choice); },
        // A push channel without live credentials can't collect anything from here
        usable(m) { return !!m && (m.live !== false || m.simulated); },
        get secondsLeft() {
            if (!this.payment?.expires_at) return null;
            return Math.max(0, Math.floor((new Date(this.payment.expires_at) - this.now) / 1000));
        },
        get countdown() {
            const s = this.secondsLeft;
            return s === null ? '' : String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
        },
        get steps() {
            const st = this.payment?.status;
            const sent = st === 'awaiting_customer';
            return [
                { label: 'Space reserved', icon: 'fa-box', state: 'done' },
                { label: sent ? 'Push sent to ' + this.payment.payer_masked : 'Sending push…', icon: 'fa-paper-plane', state: sent ? 'done' : 'active' },
                { label: 'Waiting for PIN', icon: 'fa-key', state: sent ? 'active' : 'pending' },
                { label: 'Paid', icon: 'fa-check', state: st === 'paid' ? 'done' : 'pending' },
            ];
        },
        get lipaRows() {
            return [
                { label: 'Lipa Namba', value: this.payment.lipa?.number, big: true },
                { label: 'Business name', value: this.payment.lipa?.name || '—' },
                { label: 'Amount', value: this.payment.currency + ' ' + Number(this.payment.amount).toLocaleString(), big: true },
                { label: 'Reference', value: this.payment.consignment.consignment_number },
            ];
        },
        get payerHint() {
            const n = this.normalize(this.payer);
            if (!this.payer) return { text: '', cls: '' };
            if (!n) return { text: 'Enter a valid Tanzanian mobile number.', cls: 'text-rose-600' };
            const network = Object.keys(cfg.networkPrefixes).find(d => cfg.networkPrefixes[d].includes(n.slice(3, 5)));
            if (!network) return { text: 'Network not recognised — use Selcom Pesa or Lipa Namba.', cls: 'text-amber-600' };
            const c = this.chosen;
            if (c?.prefixes?.length && c.driver !== network) {
                const match = this.methods.find(m => m.driver === network);
                return { text: `${cfg.networkLabels[network]} number — ${match ? 'choose ' + match.name : c.name + " can't push to it"}.`, cls: 'text-rose-600' };
            }
            return { text: `${cfg.networkLabels[network]} number ✓`, cls: 'text-emerald-700 font-semibold' };
        },

        normalize(v) {
            const d = String(v || '').replace(/\D/g, '');
            const local = d.startsWith('255') && d.length === 12 ? d.slice(3) : d.startsWith('0') && d.length === 10 ? d.slice(1) : d.length === 9 ? d : null;
            return local && /^[67]\d{8}$/.test(local) ? '255' + local : null;
        },

        poll() {
            clearTimeout(this.timer);
            if (!this.open) return;
            this.timer = setTimeout(async () => { await this.refresh(); this.poll(); }, 3000);
        },
        async refresh() {
            const p = await this.api('GET', `payments/${this.payment.uuid}`, null, true);
            if (p) this.update(p);
        },
        update(p) {
            this.payment = p;
            if (p.status === 'paid') {
                this.notice = 'Payment received — completing the booking…';
                setTimeout(() => window.location.reload(), 1200);
            } else if (!OPEN.includes(p.status)) {
                this.picking = true;
            }
        },
        async api(method, path, body = null, quiet = false) {
            if (!quiet) this.error = '';
            try {
                const res = await fetch(`${cfg.apiBase}/${path}`, {
                    method,
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                    body: body ? JSON.stringify(body) : undefined,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    if (!quiet) this.error = data.error || (data.errors ? Object.values(data.errors)[0][0] : data.message) || 'Request failed.';
                    return null;
                }
                return data;
            } catch (e) {
                if (!quiet) this.error = 'Network error — check your connection.';
                return null;
            }
        },
        async startPayment() {
            this.busy = true; this.notice = '';
            const p = await this.api('POST', `consignments/${cfg.consignmentId}/payments`, { payment_method: this.choice, payer_msisdn: this.payer });
            this.busy = false;
            if (p) { this.picking = false; this.update(p); this.poll(); }
        },
        async resend() {
            this.busy = true; this.notice = '';
            const p = await this.api('POST', `payments/${this.payment.uuid}/resend`, { payer_msisdn: this.payer });
            this.busy = false;
            if (p) { this.update(p); this.poll(); this.notice = 'Push resent.'; }
        },
        async confirmLipa() {
            this.busy = true; this.notice = '';
            const p = await this.api('POST', `payments/${this.payment.uuid}/confirm`, { reference: this.reference, payer_msisdn: this.lipaPayer || null });
            this.busy = false;
            if (p) this.update(p);
        },
        async smsInstructions() {
            this.busy = true; this.notice = '';
            const r = await this.api('POST', `payments/${this.payment.uuid}/sms-instructions`, { phone: this.lipaPayer || null });
            this.busy = false;
            if (r) this.notice = r.sent ? `Instructions sent to ${r.to}.` : r.simulated ? `SMS gateway not configured — message logged only (to ${r.to}).` : `SMS to ${r.to} failed.`;
        },
        copy(text) {
            navigator.clipboard?.writeText(String(text ?? ''));
            this.notice = 'Copied.';
            setTimeout(() => { if (this.notice === 'Copied.') this.notice = ''; }, 1200);
        },
    }));
});
</script>
@endpush
