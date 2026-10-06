<x-layouts.admin>
<x-slot:title>Cargo Booking Terminal</x-slot:title>
<x-slot:header>Cargo Booking</x-slot:header>

<div class="min-h-screen bg-slate-50 text-slate-800 p-4 font-sans">

    {{-- Sticky Header Bar --}}
    <header class="max-w-5xl mx-auto sticky top-20 z-40 bg-white/90 border border-slate-200 rounded-2xl px-5 py-3 mb-6 shadow-lg backdrop-blur-md">
        <div class="flex items-center justify-between gap-4">
            {{-- Left: Step indicator --}}
            <div class="flex items-center gap-6" id="header-steps">
                <template id="header-step-tpl"></template>
            </div>

            {{-- Center: Hold Timer --}}
            <div id="hold-timer-container" class="hidden">
                <span class="px-3.5 py-2 rounded-xl bg-amber-500/15 border border-amber-400/40 text-amber-700 text-xs font-mono font-bold animate-pulse flex items-center gap-2 shadow-sm">
                    <i class="fas fa-stopwatch text-amber-600"></i>
                    <span>Hold: <strong id="hold-countdown-text">--:--</strong></span>
                </span>
            </div>

            {{-- Right: Refresh --}}
            <button onclick="loadServices()" class="p-2.5 bg-slate-50 hover:bg-slate-100 text-slate-500 hover:text-cyan-600 rounded-xl border border-slate-200 transition" title="Refresh">
                <i class="fas fa-sync-alt text-sm"></i>
            </button>
        </div>
    </header>

    {{-- Error Banner --}}
    <div id="error-banner" class="hidden max-w-5xl mx-auto mb-4 bg-red-50 border border-red-200 rounded-xl px-5 py-3.5 flex items-center gap-3 shadow-sm">
        <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center shrink-0"><i class="fas fa-exclamation-triangle text-red-500"></i></div>
        <span id="error-text" class="text-sm text-red-700 font-medium"></span>
        <button onclick="hideError()" class="ml-auto text-red-400 hover:text-red-600"><i class="fas fa-times"></i></button>
    </div>

    {{-- Success Banner --}}
    <div id="success-banner" class="hidden max-w-5xl mx-auto mb-4 bg-emerald-50 border border-emerald-200 rounded-2xl px-6 py-5 shadow-lg">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0"><i class="fas fa-check-circle text-emerald-500 text-2xl"></i></div>
            <div>
                <h3 class="text-base font-extrabold text-emerald-800">Booking Confirmed!</h3>
                <p id="success-text" class="text-sm text-emerald-700 mt-0.5"></p>
            </div>
        </div>
    </div>

    <div class="max-w-5xl mx-auto">

        {{-- ═══════════════════════════════════════════════════ --}}
        {{-- STEP 1: Select Transport Service                   --}}
        {{-- ═══════════════════════════════════════════════════ --}}
        <div id="step-1" class="space-y-5">

            {{-- Hero Selection Panel --}}
            <div class="bg-white/80 border border-slate-200 rounded-2xl shadow-xl overflow-hidden relative">
                {{-- Subtle pattern --}}
                <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle at 2px 2px, #64748b 1px, transparent 0); background-size: 20px 20px;"></div>

                <div class="relative px-6 py-6 sm:px-8">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-5">
                        <div>
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-cyan-500/10 border border-cyan-500/25 text-cyan-600 text-[10px] font-extrabold tracking-widest uppercase mb-2">
                                <i class="fas fa-shipping-fast"></i> Step 1
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Select Transport Service</h2>
                            <p class="text-sm text-slate-500 mt-1 font-medium max-w-sm">Choose departure date and available transport service for your cargo.</p>
                        </div>
                    </div>

                    {{-- Date Strip --}}
                    <div class="mt-5 flex items-center gap-2 overflow-x-auto pb-2 scrollbar-thin">
                        <div class="flex-shrink-0 border border-slate-300 rounded-xl px-3 py-2 bg-white/70 flex flex-col justify-center shadow-sm">
                            <span class="text-[10px] text-slate-500 uppercase tracking-wider mb-0.5 font-bold">Select Date</span>
                            <input type="date" id="booking-date" value="{{ date('Y-m-d') }}" min="{{ date('Y-m-d') }}"
                                onchange="loadServices()"
                                class="bg-transparent text-cyan-600 text-sm font-bold focus:outline-none cursor-pointer" />
                        </div>
                        <div id="date-strip" class="flex items-center gap-2"></div>
                    </div>
                </div>
            </div>

            {{-- Services Grid --}}
            <div id="services-list" class="space-y-4"></div>

            {{-- Empty State --}}
            <div id="services-empty" class="hidden">
                <div class="bg-white/60 border border-slate-200 rounded-2xl p-14 text-center shadow-sm">
                    <i class="fas fa-truck text-4xl text-slate-300 mb-4 block"></i>
                    <p class="text-sm font-bold text-slate-500">No services available on this date.</p>
                    <p class="text-xs text-slate-400 mt-1">Try selecting a different departure date above.</p>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════ --}}
        {{-- STEP 2: Add Cargo Items                            --}}
        {{-- ═══════════════════════════════════════════════════ --}}
        <div id="step-2" class="hidden space-y-5">
            {{-- Selected service recap --}}
            <div class="bg-white/80 border border-slate-200 rounded-2xl p-5 shadow-xl flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500/20 to-blue-500/20 border border-cyan-500/30 flex items-center justify-center text-cyan-600 shadow-md">
                        <i class="fas fa-box-open text-lg"></i>
                    </div>
                    <div>
                        <span class="text-sm font-black text-slate-800 block">Cargo Item Manifest</span>
                        <span class="text-xs text-slate-500" id="step2-service-recap">—</span>
                    </div>
                </div>
                <span class="px-2.5 py-1 bg-emerald-500/10 border border-emerald-500/30 rounded-lg text-emerald-600 font-bold text-[10px] uppercase tracking-wider">
                    <i class="fas fa-check mr-1"></i> Service Selected
                </span>
            </div>

            {{-- Items Container --}}
            <div class="bg-white/80 border border-slate-200 rounded-2xl shadow-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                        <i class="fas fa-cubes text-cyan-500"></i> Cargo Items
                    </h3>
                    <button onclick="addItem()" class="px-3.5 py-1.5 bg-cyan-500 hover:bg-cyan-400 text-white text-xs font-bold rounded-lg transition-all shadow-md shadow-cyan-500/20 flex items-center gap-1.5">
                        <i class="fas fa-plus text-[10px]"></i> Add Item
                    </button>
                </div>
                <div id="items-container" class="p-5 space-y-4"></div>
            </div>

            {{-- Fare Summary --}}
            <div id="fare-summary" class="hidden bg-white/80 border border-slate-200 rounded-2xl shadow-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                        <i class="fas fa-receipt text-emerald-500"></i> Fare Breakdown
                    </h3>
                </div>
                <div class="p-5">
                    <div id="fare-breakdown" class="space-y-2"></div>
                    <div class="border-t border-slate-200 mt-4 pt-4 flex justify-between items-center">
                        <span class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Total Amount</span>
                        <span id="fare-total" class="text-xl font-black text-cyan-600"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════ --}}
        {{-- STEP 3: Sender & Receiver                          --}}
        {{-- ═══════════════════════════════════════════════════ --}}
        <div id="step-3" class="hidden space-y-5">
            <div class="bg-white/80 border border-slate-200 rounded-2xl shadow-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                        <i class="fas fa-users text-cyan-500"></i> Sender & Receiver Details
                    </h3>
                </div>

                <div class="p-5 grid grid-cols-1 lg:grid-cols-2 gap-0 lg:gap-0">
                    {{-- Sender --}}
                    <div class="p-5 lg:border-r border-slate-100 space-y-4">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-500 to-blue-600 text-white flex items-center justify-center shadow-md shadow-cyan-500/30">
                                <i class="fas fa-arrow-up text-xs"></i>
                            </div>
                            <div>
                                <span class="text-xs font-black text-slate-700 uppercase tracking-wider block">Sender (Origin)</span>
                                <span class="text-[11px] text-slate-400">Who is sending the cargo?</span>
                            </div>
                        </div>
                        <div>
                            <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Full Name <span class="text-rose-500">*</span></label>
                            <input type="text" id="sender-name" required placeholder="e.g. John Mwalimu"
                                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 font-medium shadow-inner transition-all">
                        </div>
                        <div>
                            <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Phone <span class="text-rose-500">*</span></label>
                            <input type="text" id="sender-phone" required placeholder="+255 7XX XXX XXX"
                                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 font-medium shadow-inner transition-all">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-[11px] text-slate-500 font-bold block mb-1.5">ID Type</label>
                                <select id="sender-id-type" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-cyan-500 font-medium shadow-inner">
                                    <option value="">None</option><option value="NIDA">NIDA</option><option value="Passport">Passport</option><option value="Voter">Voter ID</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[11px] text-slate-500 font-bold block mb-1.5">ID Number</label>
                                <input type="text" id="sender-id-number" placeholder="ID Number"
                                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-cyan-500 font-medium shadow-inner">
                            </div>
                        </div>
                    </div>

                    {{-- Receiver --}}
                    <div class="p-5 space-y-4 border-t lg:border-t-0 border-slate-100">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center shadow-md shadow-emerald-500/30">
                                <i class="fas fa-arrow-down text-xs"></i>
                            </div>
                            <div>
                                <span class="text-xs font-black text-slate-700 uppercase tracking-wider block">Receiver (Destination)</span>
                                <span class="text-[11px] text-slate-400">Who will collect the cargo?</span>
                            </div>
                        </div>
                        <div>
                            <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Full Name <span class="text-rose-500">*</span></label>
                            <input type="text" id="receiver-name" required placeholder="e.g. Amina Said"
                                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 font-medium shadow-inner transition-all">
                        </div>
                        <div>
                            <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Phone <span class="text-rose-500">*</span></label>
                            <input type="text" id="receiver-phone" required placeholder="+255 7XX XXX XXX"
                                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 font-medium shadow-inner transition-all">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════ --}}
        {{-- STEP 4: Payment & Confirm                          --}}
        {{-- ═══════════════════════════════════════════════════ --}}
        <div id="step-4" class="hidden space-y-5">
            <div class="bg-white/80 border border-slate-200 rounded-2xl shadow-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                        <i class="fas fa-file-invoice-dollar text-cyan-500"></i> Order Summary & Payment
                    </h3>
                </div>

                <div class="p-5 space-y-5">
                    {{-- Route & Service recap --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200/80">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Route</div>
                            <div id="confirm-route" class="text-sm font-bold text-slate-800"></div>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200/80">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Departure</div>
                            <div id="confirm-service" class="text-sm font-bold text-slate-800"></div>
                        </div>
                    </div>

                    {{-- Sender / Receiver recap --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-cyan-50/50 rounded-xl p-4 border border-cyan-200/50">
                            <div class="text-[10px] font-bold text-cyan-500 uppercase tracking-widest mb-1"><i class="fas fa-arrow-up mr-1"></i> Sender</div>
                            <div id="confirm-sender" class="text-sm font-medium text-slate-700"></div>
                        </div>
                        <div class="bg-emerald-50/50 rounded-xl p-4 border border-emerald-200/50">
                            <div class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest mb-1"><i class="fas fa-arrow-down mr-1"></i> Receiver</div>
                            <div id="confirm-receiver" class="text-sm font-medium text-slate-700"></div>
                        </div>
                    </div>

                    {{-- Items --}}
                    <div class="bg-slate-50 rounded-xl border border-slate-200/80 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-200/60">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Cargo Items</span>
                        </div>
                        <div id="confirm-items" class="p-4 space-y-2"></div>
                        <div class="px-4 py-3 border-t border-slate-200 bg-white flex justify-between items-center">
                            <span class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Total</span>
                            <span id="confirm-total" class="text-xl font-black text-cyan-600"></span>
                        </div>
                    </div>

                    {{-- Payment Method (channels from Settings › Payment Channels) --}}
                    @php
                        $channelGroups = [
                            'cash'       => ['Cash', 'fa-money-bill-wave'],
                            'ussd_push'  => ['Mobile Money — USSD push to customer\'s phone', 'fa-mobile-alt'],
                            'lipa_namba' => ['Lipa Namba — customer pays, you confirm the SMS reference', 'fa-store'],
                        ];
                    @endphp
                    <div class="space-y-4">
                        <label class="text-[11px] text-slate-500 font-bold block uppercase tracking-wider">Payment Method <span class="text-rose-500">*</span></label>
                        @if(empty($paymentMethods))
                            <div class="px-4 py-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-xs font-semibold flex items-center gap-2">
                                <i class="fas fa-exclamation-triangle"></i> No payment channels are ready. Configure them under Settings &rarr; Payment Channels.
                            </div>
                        @endif
                        @foreach($channelGroups as $flow => [$groupLabel, $groupIcon])
                            @php $group = array_values(array_filter($paymentMethods, fn ($m) => $m['flow'] === $flow)); @endphp
                            @if(count($group))
                                <div>
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 flex items-center gap-1.5"><i class="fas {{ $groupIcon }}"></i> {{ $groupLabel }}</div>
                                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                                        @foreach($group as $method)
                                            <button type="button" onclick="selectPayment('{{ $method['slug'] }}')" data-method="{{ $method['slug'] }}"
                                                class="payment-btn relative px-3 py-3 rounded-xl border-2 border-slate-200 bg-white text-center transition-all hover:border-slate-300">
                                                @if($method['simulated'])
                                                    <span class="absolute top-1 right-1 px-1 rounded bg-violet-100 text-violet-700 text-[8px] font-black uppercase" title="No live credentials yet — the push is simulated (testing only)">Sim</span>
                                                @elseif(!$method['live'])
                                                    <span class="absolute top-1 right-1 px-1 rounded bg-amber-100 text-amber-700 text-[8px] font-black uppercase" title="Credentials not set up yet — no push will be sent">Not live</span>
                                                @endif
                                                <i class="fas {{ $method['icon'] }} text-slate-400 text-lg block mb-1"></i>
                                                <span class="text-[11px] font-bold text-slate-600">{{ $method['name'] }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                        <input type="hidden" id="payment-method" value="">

                        {{-- Payer phone for USSD push --}}
                        <div id="payer-msisdn-box" class="hidden bg-cyan-50/60 border border-cyan-200 rounded-xl p-4">
                            <label class="text-[11px] text-slate-600 font-bold block mb-1.5">Payer's phone (receives the USSD push) <span class="text-rose-500">*</span></label>
                            <input type="tel" id="payer-msisdn" oninput="onPayerInput()" placeholder="0754 123 456"
                                class="w-full sm:w-72 bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 font-mono focus:outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20">
                            <p id="payer-network-hint" class="text-[11px] mt-1.5 text-slate-500"></p>
                            <p id="payer-not-live" class="hidden mt-3 px-3 py-2 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-[11px] font-semibold">
                                <i class="fas fa-exclamation-triangle mr-1"></i><span></span> isn't live yet, so no push will be sent. The booking is saved as <strong>awaiting payment</strong> with the space held — then take the payment through another channel from the booking profile.
                            </p>
                        </div>
                    </div>

                    {{-- Confirm Button --}}
                    <button onclick="confirmBooking()" id="confirm-btn"
                        class="w-full px-6 py-4 bg-gradient-to-r from-cyan-500 via-emerald-500 to-teal-500 hover:from-cyan-400 hover:via-emerald-400 hover:to-teal-400 text-white text-sm font-black rounded-xl transition-all shadow-lg shadow-cyan-500/25 flex items-center justify-center gap-2 uppercase tracking-wider">
                        <i class="fas fa-check-circle text-lg"></i> <span id="confirm-btn-label">Confirm & Book Cargo</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════ --}}
        {{-- Floating Navigation Bar                            --}}
        {{-- ═══════════════════════════════════════════════════ --}}
        <div id="nav-buttons" class="sticky bottom-4 z-30 mt-6">
            <div class="bg-white/90 backdrop-blur-xl border border-slate-200 rounded-2xl p-3 shadow-2xl flex justify-between items-center gap-4">
                <button onclick="prevStep()" id="btn-prev" class="hidden px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold rounded-xl transition-all flex items-center gap-2">
                    <i class="fas fa-arrow-left text-xs"></i> Back
                </button>
                <div class="flex-1"></div>
                <button onclick="nextStep()" id="btn-next" class="hidden px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-cyan-500/20 flex items-center gap-2">
                    Next Step <i class="fas fa-arrow-right text-xs"></i>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const API = '/admin/cargo/api';
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken };

const stepConfig = [
    { num: 1, label: 'Service', icon: 'fa-truck' },
    { num: 2, label: 'Cargo Items', icon: 'fa-cubes' },
    { num: 3, label: 'Sender/Receiver', icon: 'fa-users' },
    { num: 4, label: 'Payment', icon: 'fa-file-invoice-dollar' },
];
let currentStep = 1;
let selectedService = null;
let cargoTypes = [];
let items = [];
let itemFares = [];
let currentLockId = null;
let lockExpiresAt = null;
let countdownInterval = null;
const paymentMethods = @json($paymentMethods);
const NETWORK_LABELS = @json(\App\Support\Msisdn::NETWORK_LABELS);
const NETWORK_PREFIXES = @json(\App\Support\Msisdn::NETWORK_PREFIXES);
// Default to cash when that channel is active, otherwise the first active channel
let selectedPaymentMethod = (paymentMethods.find(m => m.slug === 'cash') || paymentMethods[0])?.slug || null;

// ── Date strip ────────────────────────────────────
function renderDateStrip() {
    const strip = document.getElementById('date-strip');
    const today = new Date();
    const bookingDate = document.getElementById('booking-date').value;
    let html = '';
    for (let i = 0; i < 7; i++) {
        const d = new Date(today);
        d.setDate(today.getDate() + i);
        const ds = d.toISOString().split('T')[0];
        const dayName = d.toLocaleDateString('en-US', { weekday: 'short' });
        const shortDate = d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
        const isSelected = bookingDate === ds;
        html += `<button onclick="selectDate('${ds}')"
            class="px-4 py-2 rounded-xl border text-center min-w-[90px] flex-shrink-0 transition-all duration-150
            ${isSelected
                ? 'bg-cyan-600/20 border-cyan-500 text-cyan-700 font-bold shadow-md'
                : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300 hover:text-slate-700 font-medium'}">
            <div class="text-[10px] uppercase tracking-wider ${isSelected ? 'text-cyan-600' : 'text-slate-400'}">${dayName}</div>
            <div class="text-sm font-bold">${shortDate}</div>
        </button>`;
    }
    strip.innerHTML = html;
}
function selectDate(ds) {
    document.getElementById('booking-date').value = ds;
    renderDateStrip();
    loadServices();
}

// ── Step management ───────────────────────────────
function renderSteps() {
    const bar = document.getElementById('header-steps');
    bar.innerHTML = '';
    stepConfig.forEach((s, i) => {
        const isActive = currentStep === s.num;
        const isDone = currentStep > s.num;
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2';
        div.innerHTML = `
            <button onclick="${isDone ? `showStep(${s.num})` : ''}" class="flex items-center gap-2.5 transition-all ${isDone ? 'cursor-pointer hover:opacity-80' : ''}" ${!isDone && !isActive ? 'disabled' : ''}>
                <span class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-black shadow-md transition-all
                    ${isDone ? 'bg-emerald-500 text-white' : isActive ? 'bg-cyan-500 text-white ring-2 ring-cyan-400/50 ring-offset-2 ring-offset-white' : 'bg-slate-100 text-slate-400 border border-slate-200'}">
                    ${isDone ? '<i class="fas fa-check text-xs"></i>' : `<i class="fas ${s.icon} text-xs"></i>`}
                </span>
                <div class="text-left hidden md:block">
                    <div class="text-[10px] font-bold uppercase tracking-widest ${isActive ? 'text-cyan-600' : isDone ? 'text-emerald-600' : 'text-slate-400'}">Step ${s.num}</div>
                    <div class="text-xs font-bold ${isActive ? 'text-slate-800' : isDone ? 'text-slate-600' : 'text-slate-400'}">${s.label}</div>
                </div>
            </button>
            ${i < stepConfig.length - 1 ? `<div class="w-8 h-0.5 rounded ${isDone ? 'bg-emerald-400' : 'bg-slate-200'} hidden sm:block"></div>` : ''}
        `;
        bar.appendChild(div);
    });
}

function showStep(step) {
    currentStep = step;
    for (let i = 1; i <= 4; i++) document.getElementById(`step-${i}`).classList.toggle('hidden', i !== step);
    document.getElementById('btn-prev').classList.toggle('hidden', step === 1);
    document.getElementById('btn-next').classList.toggle('hidden', step >= 4);
    document.getElementById('success-banner').classList.add('hidden');
    renderSteps();
    if (step === 2 && selectedService) {
        document.getElementById('step2-service-recap').textContent =
            `${selectedService.route?.name || 'N/A'} · ${selectedService.departure_date} · ${selectedService.departure_time} · ${selectedService.transport_unit?.name || 'N/A'}`;
    }
    if (step === 4) populateConfirmation();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function nextStep() {
    if (currentStep === 1 && !selectedService) { showError('Please select a transport service.'); return; }

    if (currentStep === 2) {
        const validItems = items.filter(i => i && i.fare_amount > 0);
        if (validItems.length === 0) { showError('Please add and calculate fare for at least one cargo item.'); return; }

        const btn = document.getElementById('btn-next');
        const oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Locking Inventory...';
        hideError();

        try {
            const res = await fetch(`${API}/booking/lock-inventory`, {
                method: 'POST', headers,
                body: JSON.stringify({ transport_service_id: selectedService.id, items: validItems })
            });
            const data = await res.json();
            if (!res.ok) { showError(data.message || 'Failed to lock inventory.'); btn.disabled = false; btn.innerHTML = oldHtml; return; }

            currentLockId = data.lock_id;
            lockExpiresAt = new Date(data.expires_at).getTime();
            startCountdown();
        } catch (err) {
            showError('Network error while locking inventory.');
            btn.disabled = false; btn.innerHTML = oldHtml; return;
        }
        btn.disabled = false; btn.innerHTML = oldHtml;
    }

    if (currentStep === 3) {
        if (!document.getElementById('sender-name').value || !document.getElementById('sender-phone').value ||
            !document.getElementById('receiver-name').value || !document.getElementById('receiver-phone').value) {
            showError('Please fill in all required sender and receiver fields.'); return;
        }
    }
    hideError();
    showStep(currentStep + 1);
}

function startCountdown() {
    document.getElementById('hold-timer-container').classList.remove('hidden');
    if (countdownInterval) clearInterval(countdownInterval);
    countdownInterval = setInterval(() => {
        const now = new Date().getTime();
        const distance = lockExpiresAt - now;
        if (distance <= 0) {
            clearInterval(countdownInterval);
            document.getElementById('hold-countdown-text').textContent = "00:00";
            showError("Hold expired. Please try again.");
            currentLockId = null;
            document.getElementById('hold-timer-container').classList.add('hidden');
            showStep(1);
            return;
        }
        const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const s = Math.floor((distance % (1000 * 60)) / 1000);
        document.getElementById('hold-countdown-text').textContent = (m < 10 ? "0" + m : m) + ":" + (s < 10 ? "0" + s : s);
    }, 1000);
}

function prevStep() { hideError(); showStep(currentStep - 1); }
function showError(msg) { document.getElementById('error-banner').classList.remove('hidden'); document.getElementById('error-text').textContent = msg; window.scrollTo({ top: 0, behavior: 'smooth' }); }
function hideError() { document.getElementById('error-banner').classList.add('hidden'); }

// ── Step 1: Load services ─────────────────────────
async function loadServices() {
    const date = document.getElementById('booking-date').value;
    if (!date) { showError('Please select a date.'); return; }
    hideError();
    renderDateStrip();

    const container = document.getElementById('services-list');
    const emptyState = document.getElementById('services-empty');
    container.innerHTML = '<div class="py-10 text-center text-slate-500 font-medium flex items-center justify-center gap-3"><i class="fas fa-spinner animate-spin text-cyan-600 text-xl"></i> Loading services...</div>';
    emptyState.classList.add('hidden');

    const res = await fetch(`${API}/booking/available-services?date=${date}`, { headers });
    const services = await res.json();

    if (!services.length) {
        container.innerHTML = '';
        emptyState.classList.remove('hidden');
        return;
    }
    emptyState.classList.add('hidden');

    container.innerHTML = services.map(s => {
        const inv = s.cargo_inventory;
        const selected = selectedService?.id === s.id;

        // Capacity bars
        function capBar(used, total, label, color) {
            const pct = total > 0 ? Math.min((used / total) * 100, 100) : 0;
            const avail = Math.max(total - used, 0);
            return `<div class="flex-1 min-w-[120px]">
                <div class="flex justify-between mb-1">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">${label}</span>
                    <span class="text-[10px] font-bold ${pct > 80 ? 'text-rose-500' : 'text-slate-500'}">${avail.toLocaleString()} avail</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden border border-slate-200/60">
                    <div class="h-full rounded-full transition-all duration-500 ${pct > 80 ? 'bg-gradient-to-r from-rose-400 to-rose-500' : `bg-gradient-to-r ${color}`}"
                         style="width: ${pct}%"></div>
                </div>
                <div class="text-[10px] text-slate-400 mt-0.5 font-mono">${Number(used||0).toFixed(1)} / ${Number(total||0).toFixed(1)}</div>
            </div>`;
        }

        return `<button onclick="selectService(${s.id})"
            class="group text-left w-full bg-white/80 border-2 rounded-2xl p-5 transition-all duration-200 relative overflow-hidden
                ${selected
                    ? 'border-cyan-500 bg-white shadow-xl shadow-cyan-500/10 ring-1 ring-cyan-400/30'
                    : 'border-slate-200 hover:border-cyan-300 hover:shadow-lg'}">
            ${selected ? '<div class="absolute top-3 right-3 w-7 h-7 rounded-lg bg-cyan-500 flex items-center justify-center shadow-md"><i class="fas fa-check text-white text-xs"></i></div>' : ''}

            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br ${selected ? 'from-cyan-500 to-blue-600' : 'from-slate-100 to-slate-200'} flex items-center justify-center shrink-0 shadow-md ${selected ? 'shadow-cyan-500/30' : ''}">
                    <i class="fas fa-ship text-lg ${selected ? 'text-white' : 'text-slate-400'}"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-extrabold text-slate-800 text-base truncate">${s.route?.name || 'N/A'}</h3>
                    <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1">
                        <span class="text-xs font-bold ${selected ? 'text-cyan-600' : 'text-slate-500'} flex items-center gap-1.5">
                            <i class="fas fa-clock text-xs"></i> ${s.departure_time || '—'}
                        </span>
                        <span class="text-xs font-medium text-slate-500 flex items-center gap-1.5">
                            <i class="fas fa-anchor text-xs"></i> ${s.transport_unit?.name || 'N/A'}
                        </span>
                        ${s.service_number ? `<span class="text-xs font-mono text-slate-400">#${s.service_number}</span>` : ''}
                    </div>
                </div>
            </div>

            ${inv ? `<div class="mt-4 flex flex-wrap gap-4">
                ${capBar(inv.used_lane_meters||0, inv.total_lane_meters||0, 'Lane Meters', 'from-cyan-400 to-cyan-500')}
                ${capBar(inv.used_weight_kg||0, inv.total_weight_capacity_kg||0, 'Weight (KG)', 'from-blue-400 to-blue-500')}
                ${capBar(inv.used_volume_cbm||0, inv.total_volume_capacity_cbm||0, 'Volume (CBM)', 'from-violet-400 to-violet-500')}
            </div>` : '<div class="mt-3 text-[11px] text-slate-400 italic"><i class="fas fa-info-circle mr-1"></i> No inventory data available</div>'}
        </button>`;
    }).join('');

    window._services = services;
}

function selectService(id) {
    selectedService = window._services.find(s => s.id === id);
    document.getElementById('btn-next').classList.remove('hidden');
    loadServices();
    loadCargoTypes();
}

// ── Step 2: Cargo items ───────────────────────────
async function loadCargoTypes() {
    if (cargoTypes.length) return;
    const res = await fetch(`${API}/types`, { headers });
    cargoTypes = (await res.json()).filter(t => t.status === 'active');
}

function addItem() {
    const idx = items.length;
    items.push({ cargo_type_id: '', weight_kg: '', length_m: '', width_m: '', height_m: '', description: '', quantity: 1, fare_amount: 0 });
    const typeOpts = cargoTypes.map(t => `<option value="${t.id}" data-unit="${t.measurement_unit}">${t.name} (${t.measurement_unit})</option>`).join('');

    const container = document.getElementById('items-container');
    const div = document.createElement('div');
    div.className = 'bg-slate-50/70 border border-slate-200 rounded-xl p-5 space-y-4 relative transition-all hover:border-slate-300';
    div.id = `item-${idx}`;
    div.style.borderLeft = '4px solid';
    div.style.borderLeftColor = '#06b6d4';
    div.innerHTML = `
        ${idx > 0 ? `<button onclick="removeItem(${idx})" class="absolute top-3 right-3 w-7 h-7 rounded-lg bg-slate-100 hover:bg-red-100 text-slate-400 hover:text-red-500 flex items-center justify-center transition-all"><i class="fas fa-times text-xs"></i></button>` : ''}
        <div class="flex items-center gap-2 mb-1">
            <span class="w-6 h-6 rounded-lg bg-cyan-500 text-white text-[10px] font-black flex items-center justify-center shadow-sm">${idx + 1}</span>
            <span class="text-xs font-extrabold text-slate-600 uppercase tracking-wider">Item ${idx + 1}</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Cargo Type <span class="text-rose-500">*</span></label>
                <select onchange="onTypeChange(${idx})" id="item-type-${idx}" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-cyan-500 font-medium shadow-inner">
                    <option value="">Select type...</option>${typeOpts}
                </select>
            </div>
            <div>
                <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Description</label>
                <input type="text" id="item-desc-${idx}" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-cyan-500 font-medium shadow-inner" placeholder="e.g. Samsung 55 TV">
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            <div>
                <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Weight (kg)</label>
                <input type="number" step="0.1" id="item-weight-${idx}" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-cyan-500 font-mono shadow-inner" placeholder="0.0">
            </div>
            <div>
                <label class="text-[11px] text-slate-500 font-bold block mb-1.5">L (m)</label>
                <input type="number" step="0.01" id="item-l-${idx}" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-cyan-500 font-mono shadow-inner" placeholder="0.0">
            </div>
            <div>
                <label class="text-[11px] text-slate-500 font-bold block mb-1.5">W (m)</label>
                <input type="number" step="0.01" id="item-w-${idx}" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-cyan-500 font-mono shadow-inner" placeholder="0.0">
            </div>
            <div>
                <label class="text-[11px] text-slate-500 font-bold block mb-1.5">H (m)</label>
                <input type="number" step="0.01" id="item-h-${idx}" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-cyan-500 font-mono shadow-inner" placeholder="0.0">
            </div>
            <div>
                <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Qty</label>
                <input type="number" id="item-qty-${idx}" value="1" min="1" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-cyan-500 font-mono shadow-inner">
            </div>
        </div>

        <div class="flex items-center justify-between gap-3 pt-1">
            <button onclick="calcItemFare(${idx})" class="px-4 py-2 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white text-xs font-bold rounded-lg transition-all shadow-md shadow-cyan-500/20 flex items-center gap-2">
                <i class="fas fa-calculator"></i> Calculate Fare
            </button>
            <div id="item-fare-${idx}" class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm text-slate-400 font-bold min-w-[120px] text-right">—</div>
        </div>
    `;
    container.appendChild(div);
}

function removeItem(idx) {
    document.getElementById(`item-${idx}`).remove();
    items[idx] = null;
    itemFares[idx] = null;
    updateFareSummary();
}

function onTypeChange(idx) {}

async function calcItemFare(idx) {
    const typeId = document.getElementById(`item-type-${idx}`).value;
    if (!typeId || !selectedService) { showError('Select a cargo type first.'); return; }
    hideError();
    const weight = parseFloat(document.getElementById(`item-weight-${idx}`).value) || 0;
    const l = parseFloat(document.getElementById(`item-l-${idx}`).value) || 0;
    const w = parseFloat(document.getElementById(`item-w-${idx}`).value) || 0;
    const h = parseFloat(document.getElementById(`item-h-${idx}`).value) || 0;
    const qty = parseInt(document.getElementById(`item-qty-${idx}`).value) || 1;
    const volume = (l && w && h) ? l * w * h : 0;
    const res = await fetch(`${API}/booking/calculate-fare`, { method: 'POST', headers, body: JSON.stringify({ cargo_type_id: typeId, route_id: selectedService.route_id, weight_kg: weight, volume_cbm: volume, quantity: qty }) });
    if (!res.ok) { const err = await res.json(); showError(err.message || 'Fare calculation failed.'); return; }
    const fare = await res.json();
    items[idx] = { cargo_type_id: typeId, weight_kg: weight, length_m: l, width_m: w, height_m: h, description: document.getElementById(`item-desc-${idx}`).value, quantity: qty, fare_amount: fare.total_fare };
    itemFares[idx] = fare;
    document.getElementById(`item-fare-${idx}`).innerHTML = `<span class="text-cyan-600 font-extrabold">TZS ${fare.total_fare.toLocaleString()}</span>`;
    updateFareSummary();
}

function updateFareSummary() {
    const validItems = items.filter(i => i && i.fare_amount > 0);
    if (!validItems.length) { document.getElementById('fare-summary').classList.add('hidden'); return; }
    document.getElementById('fare-summary').classList.remove('hidden');
    let html = '';
    validItems.forEach((item) => {
        const type = cargoTypes.find(t => t.id == item.cargo_type_id);
        html += `<div class="flex justify-between items-center text-sm">
            <span class="text-slate-600 flex items-center gap-2"><i class="fas fa-box text-slate-400 text-xs"></i> ${type?.name || 'Item'} ${item.description ? '<span class="text-slate-400">(' + item.description + ')</span>' : ''}</span>
            <span class="font-bold text-slate-800 font-mono">TZS ${item.fare_amount.toLocaleString()}</span>
        </div>`;
    });
    document.getElementById('fare-breakdown').innerHTML = html;
    const total = validItems.reduce((s, i) => s + i.fare_amount, 0);
    document.getElementById('fare-total').textContent = 'TZS ' + total.toLocaleString();
}

// ── Payment method ────────────────────────────────
// ── Payer number: network detection from the prefix ──
function normalizeMsisdn(v) {
    const d = String(v || '').replace(/\D/g, '');
    const local = d.startsWith('255') && d.length === 12 ? d.slice(3) : d.startsWith('0') && d.length === 10 ? d.slice(1) : d.length === 9 ? d : null;
    return local && /^[67]\d{8}$/.test(local) ? '255' + local : null;
}
function detectNetwork(msisdn) {
    const n = normalizeMsisdn(msisdn);
    if (!n) return null;
    const prefix = n.slice(3, 5);
    return Object.keys(NETWORK_PREFIXES).find(driver => NETWORK_PREFIXES[driver].includes(prefix)) || null;
}
function onPayerInput() {
    const value = document.getElementById('payer-msisdn').value;
    const hint = document.getElementById('payer-network-hint');
    const network = detectNetwork(value);
    const current = paymentMethods.find(m => m.slug === selectedPaymentMethod);
    if (!value) { hint.textContent = ''; return; }
    if (!normalizeMsisdn(value)) { hint.className = 'text-[11px] mt-1.5 text-rose-600'; hint.textContent = 'Enter a valid Tanzanian mobile number.'; return; }
    if (!network) { hint.className = 'text-[11px] mt-1.5 text-amber-600'; hint.textContent = 'Network not recognised — use Selcom Pesa or Lipa Namba.'; return; }
    // Auto-switch to the matching network when an MNO-specific channel is selected
    const match = paymentMethods.find(m => m.flow === 'ussd_push' && m.driver === network);
    if (current?.prefixes?.length && current.driver !== network && match) {
        selectPayment(match.slug, true);
        hint.className = 'text-[11px] mt-1.5 text-cyan-700 font-semibold';
        hint.innerHTML = `<i class="fas fa-magic mr-1"></i>${NETWORK_LABELS[network]} number detected — switched to ${match.name}.`;
        return;
    }
    const ok = !current?.prefixes?.length || current.driver === network;
    hint.className = 'text-[11px] mt-1.5 ' + (ok ? 'text-emerald-700 font-semibold' : 'text-rose-600');
    hint.innerHTML = ok ? `<i class="fas fa-check-circle mr-1"></i>${NETWORK_LABELS[network]} number` : `${NETWORK_LABELS[network]} number — ${current.name} can't push to it.`;
}

function selectPayment(method, fromDetection = false) {
    selectedPaymentMethod = method;
    const chosen = paymentMethods.find(m => m.slug === method);
    document.getElementById('payer-msisdn-box').classList.toggle('hidden', chosen?.flow !== 'ussd_push');
    const notLive = chosen?.flow === 'ussd_push' && !chosen.live && !chosen.simulated;
    const notLiveNote = document.getElementById('payer-not-live');
    notLiveNote.classList.toggle('hidden', !notLive);
    notLiveNote.querySelector('span').textContent = chosen?.name || '';
    document.getElementById('confirm-btn-label').textContent = notLive ? 'Save Booking — Awaiting Payment' : 'Confirm & Book Cargo';
    if (chosen?.flow === 'ussd_push' && !fromDetection) setTimeout(onPayerInput, 0);
    document.getElementById('payment-method').value = method;
    document.querySelectorAll('.payment-btn').forEach(btn => {
        const m = btn.dataset.method;
        if (m === method) {
            btn.className = 'payment-btn px-3 py-3 rounded-xl border-2 border-cyan-500 bg-cyan-50 text-center transition-all shadow-sm';
            btn.querySelector('i').className = btn.querySelector('i').className.replace(/text-slate-400/, 'text-cyan-600');
            btn.querySelector('span').className = 'text-[11px] font-bold text-cyan-700';
        } else {
            btn.className = 'payment-btn px-3 py-3 rounded-xl border-2 border-slate-200 bg-white text-center transition-all hover:border-slate-300';
            btn.querySelector('i').className = btn.querySelector('i').className.replace(/text-cyan-600/, 'text-slate-400');
            btn.querySelector('span').className = 'text-[11px] font-bold text-slate-600';
        }
    });
}

// ── Step 4: Confirm ───────────────────────────────
function populateConfirmation() {
    const payer = document.getElementById('payer-msisdn');
    if (!payer.value) payer.value = document.getElementById('sender-phone').value;
    onPayerInput();
    document.getElementById('confirm-route').textContent = selectedService.route?.name || 'N/A';
    document.getElementById('confirm-service').textContent = `${selectedService.departure_date} @ ${selectedService.departure_time} · ${selectedService.transport_unit?.name || ''}`;

    document.getElementById('confirm-sender').innerHTML = `<strong>${document.getElementById('sender-name').value}</strong><br><span class="text-xs text-slate-500">${document.getElementById('sender-phone').value}</span>`;
    document.getElementById('confirm-receiver').innerHTML = `<strong>${document.getElementById('receiver-name').value}</strong><br><span class="text-xs text-slate-500">${document.getElementById('receiver-phone').value}</span>`;

    const validItems = items.filter(i => i && i.fare_amount > 0);
    document.getElementById('confirm-items').innerHTML = validItems.map(item => {
        const type = cargoTypes.find(t => t.id == item.cargo_type_id);
        return `<div class="flex justify-between items-center text-sm py-1.5">
            <span class="text-slate-700 flex items-center gap-2"><i class="fas fa-box text-slate-400 text-xs"></i> ${type?.name || 'Item'}: ${item.description || '—'} <span class="text-slate-400">(${item.weight_kg || 0}kg)</span></span>
            <span class="font-bold text-slate-800 font-mono">TZS ${item.fare_amount.toLocaleString()}</span>
        </div>`;
    }).join('');
    const total = validItems.reduce((s, i) => s + i.fare_amount, 0);
    document.getElementById('confirm-total').textContent = 'TZS ' + total.toLocaleString();
}

async function confirmBooking() {
    if (!selectedPaymentMethod) { showError('Please select a payment method.'); return; }
    const btn = document.getElementById('confirm-btn');
    const btnHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing Booking...';
    hideError();
    const validItems = items.filter(i => i && i.fare_amount > 0);
    const payload = {
        lock_id: currentLockId,
        transport_service_id: selectedService.id,
        route_id: selectedService.route_id,
        sender_name: document.getElementById('sender-name').value,
        sender_phone: document.getElementById('sender-phone').value,
        sender_id_type: document.getElementById('sender-id-type').value || null,
        sender_id_number: document.getElementById('sender-id-number').value || null,
        receiver_name: document.getElementById('receiver-name').value,
        receiver_phone: document.getElementById('receiver-phone').value,
        payment_method: selectedPaymentMethod,
        payer_msisdn: document.getElementById('payer-msisdn').value || null,
        items: validItems,
    };
    try {
        const res = await fetch(`${API}/booking/confirm`, { method: 'POST', headers, body: JSON.stringify(payload) });
        const data = await res.json();
        if (!res.ok) { showError(data.message || 'Booking failed.'); btn.disabled = false; btn.innerHTML = btnHtml; return; }

        document.getElementById('success-banner').classList.remove('hidden');
        // Paid (cash) → profile; push / Lipa Namba → profile shows the live payment panel
        document.getElementById('success-text').textContent = data.payment?.status === 'paid'
            ? `Consignment ${data.consignment.consignment_number} booked and paid. Redirecting...`
            : `Consignment ${data.consignment.consignment_number} reserved — opening payment...`;
        document.getElementById('step-4').classList.add('hidden');
        document.getElementById('nav-buttons').classList.add('hidden');
        document.getElementById('hold-timer-container').classList.add('hidden');
        if (countdownInterval) clearInterval(countdownInterval);
        setTimeout(() => { window.location.href = `/admin/cargo/bookings/${data.consignment.id}`; }, 1500);
    } catch (err) {
        showError('Network error. Please try again.');
    }
    btn.disabled = false;
    btn.innerHTML = btnHtml;
}

// ── Init ──────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    document.getElementById('booking-date').value = new Date().toISOString().split('T')[0];
    renderSteps();
    renderDateStrip();
    await loadCargoTypes();
    addItem();
    loadServices();
    if (selectedPaymentMethod) selectPayment(selectedPaymentMethod);
});
</script>

<style>
@keyframes fade-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: fade-in 0.3s ease-out; }
.scrollbar-thin::-webkit-scrollbar { height: 4px; }
.scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
.scrollbar-thin::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
</style>
@endpush
</x-layouts.admin>
