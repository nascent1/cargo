<x-layouts.iframe>
    <x-slot:title>Booking Profile: {{ $booking->booking_reference }}</x-slot:title>
    <x-slot:header>Booking Profile & Operations</x-slot:header>

    <div x-data="{
        activeTab: 'manifest',
        manifestSearch: '',
        manifestFilter: 'all',
        expandedTicketId: null,
        showPrintAllModal: false,
        selectedPrintTicketIds: [],
        selectedPrintFormat: 'thermal',
        openPrinterModal(ids = null) {
            if (ids && Array.isArray(ids) && ids.length > 0) {
                this.selectedPrintTicketIds = [...ids];
            } else if (this.selectedPrintTicketIds.length === 0) {
                this.selectedPrintTicketIds = [
                    @foreach($booking->items()->where('status', '!=', 'cancelled')->get() as $item)
                    {{ $item->id }},
                    @endforeach
                ];
            }
            this.showPrintAllModal = true;
        },
        togglePrintTicket(id) {
            if (this.selectedPrintTicketIds.includes(id)) {
                this.selectedPrintTicketIds = this.selectedPrintTicketIds.filter(i => i !== id);
            } else {
                this.selectedPrintTicketIds.push(id);
            }
        },
        toggleAllPrintTickets(checked) {
            if (checked) {
                this.selectedPrintTicketIds = [
                    @foreach($booking->items()->where('status', '!=', 'cancelled')->get() as $item)
                    {{ $item->id }},
                    @endforeach
                ];
            } else {
                this.selectedPrintTicketIds = [];
            }
        },
        selectAllPrintTickets() {
            this.selectedPrintTicketIds = [
                @foreach($booking->items()->where('status', '!=', 'cancelled')->get() as $item)
                {{ $item->id }},
                @endforeach
            ];
        },
        deselectAllPrintTickets() {
            this.selectedPrintTicketIds = [];
        },
        showCompleteModal: false,
        showEditModal: false,
        showCancelModal: false,
        showEmailModal: false,
        showRescheduleModal: false,
        showTicketCancelModal: false,
        selectedTicketId: null,
        selectedTicketNumber: '',
        selectedTicketClassId: null,
        cancelReason: '',
        // 3-Step Availability Workflow State
        rescheduleStep: 1,
        searchDate: '{{ date("Y-m-d") }}',
        availableVoyagesList: [],
        isLoadingAvailability: false,
        selectedNewVoyageId: null,
        selectedNewVoyage: null,
        selectedNewClassId: null,
        selectedClassDetails: null,
        activeLockId: null,
        activeLockExpires: null,
        lockError: null,
        editingPassengerId: null,
        passengerNames: {
            @foreach($booking->items as $item)
            {{ $item->id }}: @js($item->passenger?->full_name ?? 'Guest Passenger'),
            @endforeach
        },
        ticketSelections: {},

        async checkRescheduleAvailability() {
            this.isLoadingAvailability = true;
            this.lockError = null;
            try {
                const res = await fetch(`{{ route('portal.bookings.check-availability') }}?date=${this.searchDate}&current_class_id=${this.selectedTicketClassId || ''}`);
                const data = await res.json();
                if (data.status === 'success') {
                    this.availableVoyagesList = data.voyages;
                    this.rescheduleStep = 2;
                } else {
                    this.lockError = data.message || 'Failed to check availability.';
                }
            } catch (err) {
                this.lockError = 'Network error checking availability.';
            } finally {
                this.isLoadingAvailability = false;
            }
        },

        async releaseRescheduleLock() {
            for (const itemId in this.ticketSelections) {
                const sel = this.ticketSelections[itemId];
                if (sel && sel.lockId) {
                    try {
                        await fetch(`{{ route('portal.bookings.unlock-seat') }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ lock_id: sel.lockId })
                        });
                    } catch (e) {}
                }
            }
            if (this.activeLockId) {
                try {
                    await fetch(`{{ route('portal.bookings.unlock-seat') }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ lock_id: this.activeLockId })
                    });
                } catch (e) {}
            }
            this.ticketSelections = {};
            this.activeLockId = null;
            this.activeLockExpires = null;
        },

        selectVoyageForReschedule(v) {
            this.selectedNewVoyageId = v.voyage_id || v.id;
            this.selectedNewVoyage = v;
            this.lockError = null;
            if (!this.selectedTicketId) {
                @if($booking->items->where('status', '!=', 'cancelled')->first())
                this.selectedTicketId = {{ $booking->items->where('status', '!=', 'cancelled')->first()->id }};
                @endif
            }
        },

        async acquireRescheduleLock(voyageId, classId, classObj, targetTicketId = null) {
            if (classObj.available_seats <= 0) {
                this.lockError = 'Selected class has zero available seats!';
                return;
            }
            this.lockError = null;
            this.selectedNewVoyageId = voyageId;
            if (!this.selectedNewVoyage) {
                this.selectedNewVoyage = this.availableVoyagesList.find(x => (x.voyage_id || x.id) == voyageId) || null;
            }
            
            const activeId = targetTicketId || this.selectedTicketId;
            if (!activeId) {
                this.lockError = 'Please select a passenger ticket on the right before picking a class.';
                return;
            }

            this.selectedNewClassId = classId;
            this.selectedClassDetails = classObj;

            const oldLockId = this.ticketSelections[activeId]?.lockId || null;

            try {
                const res = await fetch(`{{ route('portal.bookings.lock-seat') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        voyage_id: voyageId,
                        ferry_class_id: classId,
                        quantity: 1,
                        old_lock_id: oldLockId
                    })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    this.ticketSelections[activeId] = {
                        voyageId: voyageId,
                        classId: classId,
                        classObj: classObj,
                        lockId: data.lock_id,
                        expiresAt: data.expires_at
                    };
                    this.ticketSelections = { ...this.ticketSelections };
                    this.activeLockId = data.lock_id;
                    this.activeLockExpires = data.expires_at;
                    this.rescheduleStep = 3;
                } else {
                    this.lockError = data.message || 'Failed to lock seat.';
                }
            } catch (err) {
                this.lockError = 'Network error acquiring inventory lock.';
            }
        },

        computeLivePriceDifference() {
            let totalNew = 0;
            let totalOld = 0;
            let count = 0;
            @foreach($booking->items as $item)
            @if($item->status !== 'cancelled')
            if (this.ticketSelections[{{ $item->id }}]) {
                totalNew += Number(this.ticketSelections[{{ $item->id }}].classObj.fare_amount || 0);
                totalOld += {{ (float) ($item->final_fare ?? 0) }};
                count++;
            }
            @endif
            @endforeach
            if (count === 0) return '+ 0.00 TZS';
            const diff = totalNew - totalOld;
            return (diff >= 0 ? '+ ' : '- ') + Math.abs(diff).toLocaleString() + ' TZS';
        },

        resetRescheduleFlow() {
            this.releaseRescheduleLock();
            this.rescheduleStep = 1;
            this.selectedNewVoyageId = null;
            this.selectedNewVoyage = null;
            this.selectedNewClassId = null;
            this.selectedClassDetails = null;
            this.editingPassengerId = null;
            this.ticketSelections = {};
            this.lockError = null;
        }
    }">
        <!-- Sleek Light-Theme Professional Dashboard Workspace -->
        <div class="bg-slate-50 text-slate-800 p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm space-y-8">
            <!-- Top Dashboard Header Row -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3 sm:gap-4">
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight font-mono">
                        Booking {{ $booking->booking_reference }}
                    </h1>
                    @php
                        $statusColors = [
                            'held' => 'bg-amber-100 text-amber-800 border border-amber-300 font-bold',
                            'pending_payment' => 'bg-blue-100 text-blue-800 border border-blue-300 font-bold',
                            'confirmed' => 'bg-emerald-600 text-white font-black px-3.5 py-1 rounded-full text-xs uppercase tracking-wider shadow-sm',
                            'cancelled' => 'bg-rose-100 text-rose-800 border border-rose-300 font-bold',
                            'expired' => 'bg-slate-200 text-slate-600 border border-slate-300 font-bold',
                        ];
                        $badgeClass = $statusColors[$booking->status] ?? 'bg-slate-100 text-slate-700 border border-slate-200';
                    @endphp
                    @if($booking->status === 'confirmed')
                        <span class="{{ $badgeClass }}">
                            CONFIRMED
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $badgeClass }}">
                            {{ $booking->status }}
                        </span>
                    @endif
                    @if($booking->isExpired())
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700 border border-rose-300">
                            EXPIRED
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('portal.bookings.index') }}" class="px-3.5 py-2 bg-white hover:bg-slate-100 text-slate-700 font-bold rounded-xl border border-slate-200 transition-colors text-xs flex items-center gap-1.5 shadow-sm">
                        <i class="fas fa-arrow-left"></i> Back to Bookings
                    </a>
                </div>
            </div>

            <!-- Two-Column Overview & Action Tiles Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
                <!-- Left: Booking Overview Box -->
                <div class="lg:col-span-7 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm relative overflow-hidden flex flex-col justify-between">
                    <div class="absolute top-0 left-0 right-0 h-1.5 bg-brand-600"></div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-file-invoice text-brand-600"></i> Booking Overview
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3 gap-x-6 text-sm text-slate-700">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <span class="text-slate-500">Customer:</span>
                                <span class="font-bold text-slate-900 truncate max-w-[160px]">
                                    {{ $booking->user?->name ?? 'Direct Passenger' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <span class="text-slate-500">Vessel:</span>
                                <span class="font-bold text-slate-900 truncate max-w-[160px]">
                                    {{ $booking->items->first()?->voyage->ferry->name ?? 'Fast Ferry Vessel' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <span class="text-slate-500">Route:</span>
                                <span class="font-bold text-slate-900 truncate max-w-[160px]">
                                    {{ $booking->items->first()?->voyage->route->name ?? 'Ferry Route' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <span class="text-slate-500">Total:</span>
                                <span class="font-bold text-slate-900 font-mono">
                                    {{ $booking->currency }} {{ number_format((float) ($booking->net_amount ?? 0), 2) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between pt-1">
                                <span class="text-slate-500">Date:</span>
                                <span class="font-bold text-slate-900">
                                    {{ $booking->created_at->format('d M Y, H:i') }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between pt-1">
                                <span class="text-slate-500">Paid:</span>
                                <span class="font-bold text-emerald-600 flex items-center gap-1.5 font-mono">
                                    {{ $booking->currency }} {{ number_format((float) ($booking->paid_amount ?? 0), 2) }}
                                    @if((float) ($booking->paid_amount ?? 0) >= (float) ($booking->net_amount ?? 0) && (float) ($booking->net_amount ?? 0) > 0)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 uppercase font-sans">Paid</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: 4 High-Impact Action Tiles Side by Side -->
                <div class="lg:col-span-5 grid grid-cols-2 sm:grid-cols-4 gap-3 items-stretch">
                    <!-- Tile 1: Edit Booking (Disabled / Alert when past due) -->
                    @if(!$booking->is_past_due && in_array($booking->status, ['held', 'pending_payment', 'confirmed']))
                        @can('booking:edit')
                            <button @click="activeTab = 'reschedule'; if(!selectedTicketId && {{ $booking->items->count() }} > 0) { selectedTicketId = {{ $booking->items->first()?->id ?? 'null' }}; selectedTicketNumber = '{{ $booking->items->first()?->ticket_number ?? '' }}'; selectedTicketClassId = {{ $booking->items->first()?->ferry_class_id ?? 'null' }}; } resetRescheduleFlow(); checkRescheduleAvailability(); document.getElementById('reschedule-section')?.scrollIntoView({ behavior: 'smooth' });" class="flex flex-col items-center justify-center p-3.5 rounded-2xl bg-white hover:bg-indigo-50/60 border border-slate-200 hover:border-indigo-300 text-slate-700 hover:text-indigo-600 font-bold text-xs transition-all shadow-sm hover:shadow group">
                                <div class="w-11 h-11 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg mb-2 shadow-2xs group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <span class="text-center leading-tight">Edit<br>Booking</span>
                            </button>
                        @else
                            <div class="flex flex-col items-center justify-center p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-400 font-bold text-xs cursor-not-allowed">
                                <div class="w-11 h-11 rounded-2xl bg-slate-200 text-slate-400 flex items-center justify-center text-lg mb-2">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <span class="text-center leading-tight">Edit<br>Booking</span>
                            </div>
                        @endcan
                    @else
                        <button type="button" @click="alert('Cannot edit or reschedule: All tickets on this booking have already passed their scheduled departure date/time.')" class="flex flex-col items-center justify-center p-3.5 rounded-2xl bg-slate-100 border border-slate-200 text-slate-400 font-bold text-xs cursor-not-allowed opacity-60" title="Past Due Booking: Travel date has passed">
                            <div class="w-11 h-11 rounded-2xl bg-slate-200 text-slate-400 flex items-center justify-center text-lg mb-2">
                                <i class="fas fa-history"></i>
                            </div>
                            <span class="text-center leading-tight">Past Due<br>(Locked)</span>
                        </button>
                    @endif

                    <!-- Tile 2: Complete Payment -->
                    @if(in_array($booking->status, ['held', 'pending_payment']))
                        @can('booking:complete')
                            <button @click="showCompleteModal = true" class="flex flex-col items-center justify-center p-3.5 rounded-2xl bg-white hover:bg-emerald-50/60 border border-slate-200 hover:border-emerald-300 text-slate-700 hover:text-emerald-600 font-bold text-xs transition-all shadow-sm hover:shadow group">
                                <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg mb-2 shadow-2xs group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <span class="text-center leading-tight">Complete<br>Payment</span>
                            </button>
                        @else
                            <div class="flex flex-col items-center justify-center p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-400 font-bold text-xs cursor-not-allowed">
                                <div class="w-11 h-11 rounded-2xl bg-slate-200 text-slate-400 flex items-center justify-center text-lg mb-2">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <span class="text-center leading-tight">Complete<br>Payment</span>
                            </div>
                        @endcan
                    @else
                        <div class="flex flex-col items-center justify-center p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-400 font-bold text-xs cursor-not-allowed">
                            <div class="w-11 h-11 rounded-2xl bg-slate-200 text-slate-400 flex items-center justify-center text-lg mb-2">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <span class="text-center leading-tight">Complete<br>Payment</span>
                        </div>
                    @endif

                    <!-- Tile 3: Cancel Booking -->
                    @if(!$booking->is_past_due && !in_array($booking->status, ['cancelled', 'expired']))
                        @can('booking:cancel')
                            <button @click="showCancelModal = true" class="flex flex-col items-center justify-center p-3.5 rounded-2xl bg-white hover:bg-rose-50/60 border border-slate-200 hover:border-rose-300 text-slate-700 hover:text-rose-600 font-bold text-xs transition-all shadow-sm hover:shadow group">
                                <div class="w-11 h-11 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg mb-2 shadow-2xs group-hover:bg-rose-600 group-hover:text-white transition-colors">
                                    <i class="fas fa-ban"></i>
                                </div>
                                <span class="text-center leading-tight">Cancel<br>Booking</span>
                            </button>
                        @else
                            <div class="flex flex-col items-center justify-center p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-400 font-bold text-xs cursor-not-allowed">
                                <div class="w-11 h-11 rounded-2xl bg-slate-200 text-slate-400 flex items-center justify-center text-lg mb-2">
                                    <i class="fas fa-ban"></i>
                                </div>
                                <span class="text-center leading-tight">Cancel<br>Booking</span>
                            </div>
                        @endcan
                    @else
                        <button type="button" @click="alert('Cannot cancel: All tickets on this booking have already passed their scheduled departure date/time.')" class="flex flex-col items-center justify-center p-3.5 rounded-2xl bg-slate-100 border border-slate-200 text-slate-400 font-bold text-xs cursor-not-allowed opacity-60" title="Past Due Booking: Travel date has passed">
                            <div class="w-11 h-11 rounded-2xl bg-slate-200 text-slate-400 flex items-center justify-center text-lg mb-2">
                                <i class="fas fa-history"></i>
                            </div>
                            <span class="text-center leading-tight">Past Due<br>(Locked)</span>
                        </button>
                    @endif

                    <!-- Tile 4: Print All Tickets -->
                    @if(in_array($booking->status, ['confirmed', 'paid']))
                        @can('booking:print')
                            @php
                                $hasPrintedTickets = $booking->items()->where('status', '!=', 'cancelled')->where('print_count', '>=', 1)->exists();
                                $requiresAllReprintDC = ($hasPrintedTickets && auth()->user() && !auth()->user()->hasAnyPermission(['booking:reprint_unlimited', 'ticket:reprint_unlimited']));
                            @endphp
                            <button @click="openPrinterModal()" class="flex flex-col items-center justify-center p-3.5 rounded-2xl bg-white hover:bg-sky-50/60 border border-slate-200 hover:border-sky-300 text-slate-700 hover:text-sky-600 font-bold text-xs transition-all shadow-sm hover:shadow group" title="{{ $requiresAllReprintDC ? 'Reprint requires Dual Control approval' : '' }}">
                                <div class="w-11 h-11 rounded-2xl {{ $requiresAllReprintDC ? 'bg-amber-100 text-amber-700' : 'bg-sky-50 text-sky-600' }} flex items-center justify-center text-lg mb-2 shadow-2xs group-hover:bg-sky-600 group-hover:text-white transition-colors">
                                    <i class="fas {{ $requiresAllReprintDC ? 'fa-shield-alt' : 'fa-print' }}"></i>
                                </div>
                                <span class="text-center leading-tight">Print All<br>Tickets</span>
                            </button>
                        @else
                            <div class="flex flex-col items-center justify-center p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-400 font-bold text-xs cursor-not-allowed">
                                <div class="w-11 h-11 rounded-2xl bg-slate-200 text-slate-400 flex items-center justify-center text-lg mb-2">
                                    <i class="fas fa-print"></i>
                                </div>
                                <span class="text-center leading-tight">Print All<br>Tickets</span>
                            </div>
                        @endcan
                    @else
                        <div class="flex flex-col items-center justify-center p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-400 font-bold text-xs cursor-not-allowed">
                            <div class="w-11 h-11 rounded-2xl bg-slate-200 text-slate-400 flex items-center justify-center text-lg mb-2">
                                <i class="fas fa-print"></i>
                            </div>
                            <span class="text-center leading-tight">Print All<br>Tickets</span>
                        </div>
                    @endif
                </div>
            </div>

            @if (session()->has('success'))
                <div class="p-4 bg-emerald-950/60 border border-emerald-500/40 text-emerald-300 rounded-2xl flex items-start gap-3 animate-fade-in-up font-semibold text-sm">
                    <i class="fas fa-check-circle mt-0.5 text-emerald-400"></i>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            @if (session()->has('error'))
                <div class="p-4 bg-rose-950/60 border border-rose-500/40 text-rose-300 rounded-2xl flex items-start gap-3 animate-fade-in-up font-semibold text-sm">
                    <i class="fas fa-exclamation-triangle mt-0.5 text-rose-400"></i>
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            <!-- Profile Workspace Tabs -->
            <div class="border-b border-slate-800 flex gap-2 overflow-x-auto pb-1">
                <button @click="activeTab = 'manifest'"
                        :class="activeTab === 'manifest' ? 'border-cyan-400 text-cyan-400 font-bold bg-cyan-950/40' : 'border-transparent text-slate-400 hover:text-white font-semibold hover:border-slate-700'"
                        class="px-5 py-3 border-b-2 transition-all rounded-t-xl text-sm whitespace-nowrap flex items-center gap-2">
                    <i class="fas fa-ticket-alt"></i> Manifest & Tickets ({{ $booking->items->count() }})
                </button>
                <button @click="activeTab = 'payment'"
                        :class="activeTab === 'payment' ? 'border-brand-600 text-brand-600 font-bold bg-white shadow-xs' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold hover:border-slate-300'"
                        class="px-5 py-3 border-b-2 transition-all rounded-t-xl text-sm whitespace-nowrap flex items-center gap-2">
                    <i class="fas fa-receipt"></i> Payment & Transactions ({{ $booking->transactions->count() }})
                </button>
                <button @click="activeTab = 'audit'"
                        :class="activeTab === 'audit' ? 'border-brand-600 text-brand-600 font-bold bg-white shadow-xs' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold hover:border-slate-300'"
                        class="px-5 py-3 border-b-2 transition-all rounded-t-xl text-sm whitespace-nowrap flex items-center gap-2">
                    <i class="fas fa-history"></i> Audit & Activity Log ({{ $booking->logs->count() }})
                </button>
                <button @click="activeTab = 'print_logs'"
                        :class="activeTab === 'print_logs' ? 'border-brand-600 text-brand-600 font-bold bg-white shadow-xs' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold hover:border-slate-300'"
                        class="px-5 py-3 border-b-2 transition-all rounded-t-xl text-sm whitespace-nowrap flex items-center gap-2">
                    <i class="fas fa-print"></i> Print & Email Log
                </button>
                @if(!$booking->is_past_due && in_array($booking->status, ['confirmed', 'paid']))
                    @can('ticket:reschedule')
                        <button @click="activeTab = 'reschedule'; if(!selectedTicketId && {{ $booking->items->count() }} > 0) { selectedTicketId = {{ $booking->items->first()?->id ?? 'null' }}; selectedTicketNumber = '{{ $booking->items->first()?->ticket_number ?? '' }}'; selectedTicketClassId = {{ $booking->items->first()?->ferry_class_id ?? 'null' }}; } resetRescheduleFlow(); checkRescheduleAvailability();"
                                :class="activeTab === 'reschedule' ? 'border-amber-500 text-amber-700 font-bold bg-amber-50 shadow-xs' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold hover:border-slate-300'"
                                class="px-5 py-3 border-b-2 transition-all rounded-t-xl text-sm whitespace-nowrap flex items-center gap-2">
                            <i class="fas fa-calendar-alt text-amber-500"></i> Reschedule / Edit Tickets
                        </button>
                    @endcan
                @elseif($booking->is_past_due)
                    <button type="button" @click="alert('Cannot open reschedule tab: All tickets on this booking have passed their scheduled departure date/time.')"
                            class="px-5 py-3 border-b-2 border-transparent text-slate-400 font-semibold rounded-t-xl text-sm whitespace-nowrap flex items-center gap-2 cursor-not-allowed opacity-60" title="Past Due Booking">
                        <i class="fas fa-history text-slate-400"></i> Reschedule (Locked)
                    </button>
                @endif
            </div>

            <!-- TAB 1: MANIFEST & TICKETS (Sleek Professional Light Mode Table) -->
            <div x-show="activeTab === 'manifest'" class="space-y-4 animate-fade-in-up">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h3 class="text-xl font-bold text-slate-800 tracking-tight">
                        Passenger Manifest ({{ $booking->items()->where('status', '!=', 'cancelled')->count() }} Tickets)
                    </h3>

                    <div class="flex flex-wrap items-center gap-2">
                        @php
                            $adultsCount = $booking->items->filter(fn($i) => strtolower($i->passengerType?->code ?? 'adult') === 'adult')->count();
                            $childrenCount = $booking->items->filter(fn($i) => in_array(strtolower($i->passengerType?->code ?? ''), ['child', 'infant']))->count();
                            $printedCount = $booking->items->where('print_count', '>', 0)->count();
                            $openSeatsCount = $booking->items->whereNull('seat_number')->count();
                        @endphp
                        <button @click="manifestFilter = 'all'"
                                :class="manifestFilter === 'all' ? 'bg-brand-600 text-white font-bold shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200 font-semibold'"
                                class="px-3.5 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5 shadow-2xs">
                            All ({{ $booking->items->count() }})
                        </button>
                        <button @click="manifestFilter = 'adult'"
                                :class="manifestFilter === 'adult' ? 'bg-brand-600 text-white font-bold shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200 font-semibold'"
                                class="px-3.5 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5 shadow-2xs">
                            <i class="fas fa-user text-indigo-500"></i> Adults ({{ $adultsCount }})
                        </button>
                        <button @click="manifestFilter = 'child'"
                                :class="manifestFilter === 'child' ? 'bg-amber-500 text-white font-bold shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200 font-semibold'"
                                class="px-3.5 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5 shadow-2xs">
                            <i class="fas fa-child text-amber-500"></i> Children ({{ $childrenCount }})
                        </button>
                        <button @click="manifestFilter = 'printed'"
                                :class="manifestFilter === 'printed' ? 'bg-indigo-600 text-white font-bold shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200 font-semibold'"
                                class="px-3.5 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5 shadow-2xs">
                            <i class="fas fa-print text-indigo-500"></i> Printed ({{ $printedCount }})
                        </button>
                        <button @click="manifestFilter = 'open'"
                                :class="manifestFilter === 'open' ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200 font-semibold'"
                                class="px-3.5 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5 shadow-2xs">
                            <i class="fas fa-chair text-emerald-500"></i> Open Seats ({{ $openSeatsCount }})
                        </button>
                        <button type="button" x-show="selectedPrintTicketIds.length > 0" @click="openPrinterModal()"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm bg-sky-600 text-white hover:bg-sky-700">
                            <i class="fas fa-print"></i> Print Selected (<span x-text="selectedPrintTicketIds.length"></span>)
                        </button>

                        <div class="relative w-full sm:w-64 ml-2">
                            <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" x-model="manifestSearch" placeholder="Search ticket, name, ID, seat..." 
                                   class="w-full pl-9 pr-4 py-1.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500/40 focus:border-brand-500 transition-all font-medium shadow-2xs">
                        </div>
                    </div>
                </div>

                <!-- Professional Manifest Table with Vertical Action Icons -->
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="max-h-[calc(100vh-360px)] overflow-y-auto relative">
                        <table class="w-full text-left border-collapse">
                            <thead class="sticky top-0 z-10 bg-slate-50 text-slate-600 text-[11px] uppercase font-semibold tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="py-3.5 px-3 w-10 text-center">
                                        <input type="checkbox" @click="toggleAllPrintTickets($event.target.checked)"
                                               :checked="selectedPrintTicketIds.length > 0 && selectedPrintTicketIds.length === {{ $booking->items()->where('status', '!=', 'cancelled')->count() }}"
                                               class="w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 bg-white cursor-pointer" title="Select/Deselect All">
                                    </th>
                                    <th class="py-3.5 px-3 w-12 text-center">#</th>
                                    <th class="py-3.5 px-4">Ticket Number</th>
                                    <th class="py-3.5 px-4">Passenger Name</th>
                                    <th class="py-3.5 px-4">Journey Date & Vessel</th>
                                    <th class="py-3.5 px-4 text-center">Status</th>
                                    <th class="py-3.5 px-4">Age Category</th>
                                    <th class="py-3.5 px-4">Fare Class</th>
                                    <th class="py-3.5 px-4">Seat</th>
                                    <th class="py-3.5 px-4 text-right">Net Fare</th>
                                    <th class="py-3.5 px-4 text-center">Print Count</th>
                                    <th class="py-3.5 px-6 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                                @foreach($booking->items as $index => $item)
                                    @php
                                        $pType = strtolower($item->passengerType?->code ?? 'adult');
                                        $isChild = in_array($pType, ['child', 'infant']);
                                        $isPrinted = $item->print_count > 0;
                                        $isOpenSeat = empty($item->seat_number);
                                        $requiresReprintDC = ($item->print_count >= 1 && auth()->user() && !auth()->user()->hasAnyPermission(['booking:reprint_unlimited', 'ticket:reprint_unlimited']));
                                    @endphp
                                    <tr x-show="(manifestFilter === 'all' || (manifestFilter === 'adult' && '{{ $pType }}' === 'adult') || (manifestFilter === 'child' && {{ $isChild ? 'true' : 'false' }}) || (manifestFilter === 'printed' && {{ $isPrinted ? 'true' : 'false' }}) || (manifestFilter === 'open' && {{ $isOpenSeat ? 'true' : 'false' }})) && (!manifestSearch || '{{ strtolower($item->ticket_number . ' ' . ($item->passenger?->full_name ?? '') . ' ' . ($item->passenger?->id_number ?? '') . ' ' . ($item->seat_number ?? 'open')) }}'.includes(manifestSearch.toLowerCase()))"
                                        class="hover:bg-slate-50 transition-colors {{ $item->status === 'cancelled' ? 'bg-rose-50/50 text-slate-400' : ($index % 2 === 0 ? 'bg-slate-50/40' : 'bg-transparent') }}">
                                        <td class="py-4 px-3 text-center" @click.stop>
                                            @if($item->status !== 'cancelled')
                                                <input type="checkbox" :checked="selectedPrintTicketIds.includes({{ $item->id }})" @click.stop="togglePrintTicket({{ $item->id }})"
                                                       class="w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 bg-white cursor-pointer">
                                            @endif
                                        </td>
                                        <td class="py-4 px-3 text-center text-slate-500 font-mono">{{ $index + 1 }}.</td>
                                        <td class="py-4 px-4">
                                            <div class="flex items-center gap-2">
                                                <span class="font-mono font-bold text-sm {{ $item->status === 'cancelled' ? 'text-rose-600 line-through' : 'text-slate-900 font-black tracking-wide' }}">
                                                    {{ $item->ticket_number }}
                                                </span>
                                                @if($item->edit_count > 0)
                                                    <span class="px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700 text-[10px] font-bold" title="Edited {{ $item->edit_count }} times">
                                                        <i class="fas fa-edit"></i>
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="font-bold {{ $item->status === 'cancelled' ? 'text-slate-400 line-through' : 'text-slate-900' }}">
                                                {{ $item->passenger?->full_name ?? 'Guest Passenger' }}
                                            </div>
                                            @if($item->passenger?->id_number)
                                                <div class="text-[11px] text-slate-400 mt-0.5 font-mono">
                                                    ID: {{ $item->passenger->id_number }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="flex items-center gap-1.5 font-mono font-bold text-slate-800 text-xs">
                                                <i class="fas fa-calendar-day text-brand-600"></i>
                                                <span>{{ $item->voyage?->departure_date ?? $booking->travel_date }}</span>
                                            </div>
                                            <div class="text-[11px] text-indigo-600 font-bold mt-0.5">
                                                <i class="fas fa-ship mr-1"></i>{{ $item->voyage?->ferry?->name ?? 'Ferry' }} ({{ $item->voyage?->departure_time ?? '--:--' }})
                                            </div>
                                        </td>
                                        <td class="py-4 px-4 text-center">
                                            @php
                                                $itemStatusColors = [
                                                    'confirmed' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                                    'boarded' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
                                                    'cancelled' => 'bg-rose-100 text-rose-800 border-rose-300',
                                                    'pending' => 'bg-amber-100 text-amber-800 border-amber-300',
                                                ];
                                                $iClass = $itemStatusColors[$item->status] ?? 'bg-slate-100 text-slate-700 border-slate-300';
                                            @endphp
                                            <span class="px-2.5 py-1 rounded-lg border text-[10px] uppercase font-extrabold {{ $iClass }}">
                                                {{ $item->status }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-4">
                                            <span class="font-semibold {{ $isChild ? 'text-amber-600' : 'text-slate-700' }}">
                                                <i class="fas fa-id-badge text-slate-500 w-4 text-center"></i>
                                                {{ $item->passengerType?->name ?? 'Adult' }}
                                                @if($item->passenger?->dob)
                                                    <span class="text-slate-400 text-[11px]">({{ \Carbon\Carbon::parse($item->passenger->dob)->age }})</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 font-bold text-slate-800">
                                            {{ $item->ferryClass?->name ?? 'Standard' }}
                                        </td>
                                        <td class="py-4 px-4 font-mono font-bold">
                                            @if($item->seat_number)
                                                <span class="text-brand-600">{{ $item->seat_number }}</span>
                                            @else
                                                <span class="text-slate-400 italic">Open</span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-4 text-right font-mono font-bold {{ $item->status === 'cancelled' ? 'text-slate-400 line-through' : 'text-slate-800' }}">
                                            @if($booking->currency === 'GBP') £ @elseif($booking->currency === 'USD') $ @else {{ $booking->currency }} @endif{{ number_format((float) ($item->final_fare ?? 0), 2) }}
                                        </td>
                                        <td class="py-4 px-4 text-center font-mono text-slate-700 font-bold">
                                            {{ $item->print_count }}
                                        </td>
                                        <td class="py-4 px-6 text-right">
                                            <div class="flex items-center justify-end gap-4 text-[11px] font-medium">
                                                <!-- Single Print Button (Opens Printer Selection Hub Modal) -->
                                                @if($item->status !== 'cancelled')
                                                    @can('ticket:print')
                                                        <button type="button" @click="openPrinterModal([{{ $item->id }}])"
                                                                class="flex flex-col items-center text-slate-500 hover:text-brand-600 transition-colors group {{ $requiresReprintDC ? 'text-amber-600 hover:text-amber-700' : '' }}"
                                                                title="{{ $requiresReprintDC ? 'Select Printer & Print (Dual Control Required)' : 'Select Printer & Print Ticket' }}">
                                                            <i class="fas {{ $requiresReprintDC ? 'fa-shield-alt' : 'fa-print' }} text-sm sm:text-base mb-0.5 group-hover:scale-110 transition-transform"></i>
                                                            <span>Print</span>
                                                        </button>
                                                    @endcan

                                                    <!-- Reschedule -->
                                                    @if(!$item->is_past_due && in_array($booking->status, ['confirmed', 'paid']))
                                                        @can('ticket:reschedule')
                                                            @if($item->is_last_adult)
                                                                <button disabled class="flex flex-col items-center text-slate-300 cursor-not-allowed" title="Last Adult Safeguard: Cannot reschedule sole adult when minors are on booking">
                                                                    <i class="fas fa-calendar-alt text-sm sm:text-base mb-0.5"></i>
                                                                    <span>Reschedule</span>
                                                                </button>
                                                            @else
                                                                <button type="button" @click="selectedTicketId = {{ $item->id }}; selectedTicketNumber = '{{ $item->ticket_number }}'; selectedTicketClassId = {{ $item->ferry_class_id ?? 'null' }}; resetRescheduleFlow(); activeTab = 'reschedule'; checkRescheduleAvailability(); document.getElementById('reschedule-section')?.scrollIntoView({ behavior: 'smooth' });"
                                                                        class="flex flex-col items-center text-slate-500 hover:text-amber-600 transition-colors group cursor-pointer" title="Reschedule Ticket">
                                                                    <i class="fas fa-calendar-alt text-sm sm:text-base mb-0.5 group-hover:scale-110 transition-transform"></i>
                                                                    <span>Reschedule</span>
                                                                </button>
                                                            @endif
                                                        @endcan
                                                    @elseif($item->is_past_due)
                                                        <button type="button" @click="alert('Cannot reschedule Ticket ' + @js($item->ticket_number) + ': scheduled departure date/time has already passed.')"
                                                                class="flex flex-col items-center text-slate-300 cursor-not-allowed opacity-60" title="Past Due Ticket: Cannot Reschedule">
                                                            <i class="fas fa-calendar-alt text-sm sm:text-base mb-0.5"></i>
                                                            <span>Reschedule</span>
                                                        </button>
                                                    @endif

                                                    <!-- Cancel -->
                                                    @if(!$item->is_past_due)
                                                        @can('ticket:cancel')
                                                            @if($item->is_last_adult)
                                                                <button disabled class="flex flex-col items-center text-slate-300 cursor-not-allowed" title="Last Adult Safeguard: Cannot cancel sole adult when minors are on booking">
                                                                    <i class="fas fa-ban text-sm sm:text-base mb-0.5"></i>
                                                                    <span>Cancel</span>
                                                                </button>
                                                            @else
                                                                <button type="button" @click="selectedTicketId = {{ $item->id }}; selectedTicketNumber = '{{ $item->ticket_number }}'; showTicketCancelModal = true;"
                                                                        class="flex flex-col items-center text-rose-600 hover:text-rose-700 transition-colors group cursor-pointer" title="Cancel Ticket">
                                                                    <i class="fas fa-ban text-sm sm:text-base mb-0.5 group-hover:scale-110 transition-transform"></i>
                                                                    <span>Cancel</span>
                                                                </button>
                                                            @endif
                                                        @endcan
                                                    @else
                                                        <button type="button" @click="alert('Cannot cancel Ticket ' + @js($item->ticket_number) + ': scheduled departure date/time has already passed.')"
                                                                class="flex flex-col items-center text-slate-300 cursor-not-allowed opacity-60" title="Past Due Ticket: Cannot Cancel">
                                                            <i class="fas fa-ban text-sm sm:text-base mb-0.5"></i>
                                                            <span>Cancel</span>
                                                        </button>
                                                    @endif
                                                @else
                                                    <!-- Cancelled Status Row Actions -->
                                                    <div class="flex items-center gap-2 text-rose-600 text-xs font-bold">
                                                        <i class="fas fa-ban"></i> Cancelled
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="bg-slate-50 border-t border-slate-200 px-6 py-3.5 text-xs text-slate-500 flex justify-between items-center">
                        <div>Showing <span class="font-bold text-slate-800">{{ $booking->items->count() }}</span> total tickets on this booking profile.</div>
                        <div class="flex items-center gap-4">
                            <span class="flex items-center gap-1.5 font-semibold text-slate-700"><span class="inline-block w-2.5 h-2.5 rounded-full bg-brand-600"></span> Confirmed / Active</span>
                            <span class="flex items-center gap-1.5 font-semibold text-slate-700"><span class="inline-block w-2.5 h-2.5 rounded-full bg-rose-500"></span> Cancelled</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: PAYMENT & TRANSACTIONS -->
        <div x-show="activeTab === 'payment'" class="space-y-6 animate-fade-in-up" style="display: none;">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Financial Breakdown Card -->
                <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6 space-y-4">
                    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-3">
                        <i class="fas fa-coins text-brand-600"></i> Financial Summary
                    </h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between items-center text-slate-600">
                            <span>Total Gross Fare</span>
                            <span class="font-semibold">{{ $booking->currency }} {{ number_format($booking->total_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-emerald-600">
                            <span>Discounts Applied</span>
                            <span class="font-semibold">-{{ $booking->currency }} {{ number_format($booking->discount_amount, 2) }}</span>
                        </div>
                        <div class="pt-3 border-t border-slate-200 flex justify-between items-center text-lg font-bold text-slate-800">
                            <span>Net Payable Fare</span>
                            <span class="text-brand-600">{{ $booking->currency }} {{ number_format($booking->net_amount, 2) }}</span>
                        </div>
                        <div class="pt-2 border-t border-slate-100 text-xs text-slate-500 space-y-1">
                            <div class="flex justify-between">
                                <span>Payment Channel:</span>
                                <span class="font-bold text-slate-700">{{ $booking->payment_channel ?? 'Not Paid / Cash' }}</span>
                            </div>
                            @if($booking->transaction_reference)
                                <div class="flex justify-between">
                                    <span>Tx Reference:</span>
                                    <span class="font-mono font-bold text-slate-700">{{ $booking->transaction_reference }}</span>
                                </div>
                            @endif
                            @if($booking->completedBy)
                                <div class="flex justify-between">
                                    <span>Processed By:</span>
                                    <span class="font-bold text-slate-700">{{ $booking->completedBy->full_name }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Transaction History Table -->
                <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2 mb-4">
                        <i class="fas fa-list-alt text-brand-600"></i> Transaction Ledger ({{ $booking->transactions->count() }})
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-200 text-xs font-bold uppercase text-slate-400">
                                    <th class="py-3 px-3">Tx Number</th>
                                    <th class="py-3 px-3">Type</th>
                                    <th class="py-3 px-3">Channel</th>
                                    <th class="py-3 px-3">Amount (TZS)</th>
                                    <th class="py-3 px-3">Reference / Desc</th>
                                    <th class="py-3 px-3">Date & Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs">
                                @forelse($booking->transactions as $tx)
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="py-3 px-3 font-mono font-bold text-slate-700">{{ $tx->transaction_number }}</td>
                                        <td class="py-3 px-3">
                                            @if($tx->type === 'credit')
                                                <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold uppercase">Credit</span>
                                            @elseif($tx->type === 'reverse')
                                                <span class="px-2 py-0.5 rounded bg-rose-100 text-rose-800 font-bold uppercase">Refund / Reverse</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-800 font-bold uppercase">{{ $tx->type }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-3 font-semibold text-slate-600">{{ $tx->payment_channel ?? 'Cash' }}</td>
                                        <td class="py-3 px-3 font-bold {{ $tx->type === 'reverse' ? 'text-rose-600' : 'text-emerald-600' }}">
                                            {{ $tx->type === 'reverse' ? '-' : '+' }}{{ number_format($tx->amount, 2) }}
                                        </td>
                                        <td class="py-3 px-3 text-slate-600 max-w-xs truncate" title="{{ $tx->description }}">
                                            <span class="font-semibold block">{{ $tx->reference ?? '-' }}</span>
                                            <span class="text-[11px] text-slate-400">{{ $tx->description }}</span>
                                        </td>
                                        <td class="py-3 px-3 text-slate-500 whitespace-nowrap">{{ $tx->created_at->format('M d, Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-slate-400 font-semibold italic">
                                            No payment or reverse transactions recorded for this booking yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: AUDIT & ACTIVITY LOG -->
        <div x-show="activeTab === 'audit'" class="space-y-6 animate-fade-in-up" style="display: none;">
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6">
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2 mb-4">
                    <i class="fas fa-clipboard-list text-brand-600"></i> Full Booking Audit Log (mx_booking_log equivalent)
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 text-xs font-bold uppercase text-slate-400">
                                <th class="py-3 px-3">Timestamp</th>
                                <th class="py-3 px-3">Action Type</th>
                                <th class="py-3 px-3">Ticket / Item</th>
                                <th class="py-3 px-3">Description</th>
                                <th class="py-3 px-3">Performed By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            @forelse($booking->logs as $log)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-3 px-3 font-mono text-slate-500 whitespace-nowrap">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td class="py-3 px-3 font-bold text-slate-800">
                                        <span class="px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200">{{ $log->action }}</span>
                                    </td>
                                    <td class="py-3 px-3 font-mono font-bold text-brand-600">
                                        {{ $log->bookingItem?->ticket_number ?? 'All Booking' }}
                                    </td>
                                    <td class="py-3 px-3 text-slate-600 max-w-md break-words">{{ $log->description ?? '-' }}</td>
                                    <td class="py-3 px-3 font-semibold text-slate-700">
                                        {{ $log->performedBy?->full_name ?? 'System / Guest' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-400 font-semibold italic">
                                        No audit log entries found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 4: PRINT & EMAIL LOG -->
        <div x-show="activeTab === 'print_logs'" class="space-y-6 animate-fade-in-up" style="display: none;">
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6">
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2 mb-4">
                    <i class="fas fa-print text-brand-600"></i> Ticket Print Counter & Email History
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 text-xs font-bold uppercase text-slate-400">
                                <th class="py-3 px-3">Ticket Number</th>
                                <th class="py-3 px-3">Passenger</th>
                                <th class="py-3 px-3">Total Prints</th>
                                <th class="py-3 px-3">Last Printed At</th>
                                <th class="py-3 px-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            @foreach($booking->items as $item)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-3 px-3 font-mono font-bold text-brand-600">{{ $item->ticket_number }}</td>
                                    <td class="py-3 px-3 font-bold text-slate-800">{{ $item->passenger?->full_name }}</td>
                                    <td class="py-3 px-3">
                                        <span class="px-3 py-1 rounded-full {{ $item->print_count > 0 ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-100 text-slate-600' }} font-bold">
                                            {{ $item->print_count }} times
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-slate-500 font-mono">{{ $item->last_printed_at ? $item->last_printed_at->format('Y-m-d H:i:s') : 'Never Printed' }}</td>
                                    <td class="py-3 px-3 uppercase font-bold text-[10px]">{{ $item->status }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 5: ROUND-TRIP RESCHEDULE WITH MONTHLY CALENDAR PICKER -->
        <div id="reschedule-section" x-show="activeTab === 'reschedule'" class="space-y-6 animate-fade-in-up" style="display: none;">
            <!-- Top Banner / Leg Switcher -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600 text-2xl shadow-2xs">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold font-sans tracking-wide text-slate-800">Ferry Ticket Reschedule & Modification</h3>
                        <p class="text-xs text-slate-500 font-medium">Interactive availability picker & live seat inventory lock workflow</p>
                    </div>
                </div>
                <!-- Leg Switcher Pills (Outbound vs Return) -->
                <div class="flex items-center gap-2 bg-slate-50 p-1.5 rounded-2xl border border-slate-200">
                    <button type="button" @click="resetRescheduleFlow()" class="px-4 py-2 rounded-xl text-xs font-bold transition-all bg-emerald-600 text-white shadow-sm flex items-center gap-2">
                        <i class="fas fa-ship"></i> Outbound Leg / Active Tickets
                    </button>
                    @if($booking->items->count() > 1)
                        <button type="button" @click="resetRescheduleFlow()" class="px-4 py-2 rounded-xl text-xs font-bold transition-all text-slate-600 hover:text-slate-900 hover:bg-slate-200/60 flex items-center gap-2">
                            <i class="fas fa-undo"></i> Return Leg / Secondary Tickets
                        </button>
                    @endif
                </div>
            </div>

            <!-- Main 2-Column Split Workspace -->
            <div class="grid grid-cols-12 gap-6 items-start">
                <!-- Left Column (col-span-12 lg:col-span-7): Calendar Picker & Glassmorphism/Table Voyages -->
                <div class="col-span-12 lg:col-span-7 flex flex-col gap-6">
                    <!-- Interactive Monthly Calendar Picker Box -->
                    <div class="bg-white rounded-3xl shadow-xl border border-slate-200/80 p-6">
                        <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                            <div>
                                <h4 class="text-base font-bold text-slate-800 flex items-center gap-2">
                                    <i class="fas fa-calendar-day text-brand-600"></i> Select Departure Date
                                </h4>
                                <span class="text-xs text-slate-400 font-medium">Pick a travel date to view scheduled voyages and capacity</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="date" x-model="searchDate" class="px-3.5 py-2 rounded-xl border border-slate-300 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-brand-500 shadow-sm font-mono">
                                <button type="button" @click="checkRescheduleAvailability()" :disabled="isLoadingAvailability"
                                        class="px-4 py-2 bg-brand-600 hover:bg-brand-700 disabled:opacity-50 text-white font-bold rounded-xl text-xs shadow-md shadow-brand-500/20 transition-all flex items-center gap-1.5">
                                    <i class="fas fa-search" :class="isLoadingAvailability ? 'animate-spin' : ''"></i> Search Availability
                                </button>
                            </div>
                        </div>

                        <!-- Quick Date Pills (Next 7 days) -->
                        <div class="flex items-center gap-2 overflow-x-auto pb-2 pt-1">
                            @foreach(range(0, 6) as $offset)
                                @php $dt = now()->addDays($offset); @endphp
                                <button type="button" @click="searchDate = '{{ $dt->format('Y-m-d') }}'; checkRescheduleAvailability();"
                                        :class="searchDate === '{{ $dt->format('Y-m-d') }}' ? 'bg-brand-600 text-white shadow-md shadow-brand-500/30 border-brand-600' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 border-slate-200/80'"
                                        class="px-3.5 py-2 rounded-xl border text-center transition-all flex-shrink-0 min-w-[70px]">
                                    <span class="block text-[10px] font-bold uppercase tracking-wider opacity-80">{{ $dt->format('D') }}</span>
                                    <span class="block text-sm font-extrabold font-mono">{{ $dt->format('M d') }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Available Voyages List / Structured Display -->
                    <div class="bg-white rounded-3xl shadow-xl border border-slate-200/80 p-6 min-h-[350px]">
                        <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                            <h4 class="text-base font-bold text-slate-800 flex items-center gap-2">
                                <i class="fas fa-ship text-indigo-600"></i> Available Voyages on <span x-text="searchDate" class="font-mono text-brand-600"></span>
                            </h4>
                            <span x-show="availableVoyagesList.length > 0" class="text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800" x-text="availableVoyagesList.length + ' Voyages Found'"></span>
                        </div>

                        <div x-show="isLoadingAvailability" class="py-16 text-center space-y-3">
                            <i class="fas fa-circle-notch fa-spin text-3xl text-brand-600"></i>
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Checking fleet schedules and class capacity...</p>
                        </div>

                        <div x-show="!isLoadingAvailability && availableVoyagesList.length === 0 && lockError" class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-xs text-rose-700 font-bold flex items-center gap-2">
                            <i class="fas fa-exclamation-circle text-lg text-rose-600"></i>
                            <span x-text="lockError"></span>
                        </div>

                        <div x-show="!isLoadingAvailability && availableVoyagesList.length === 0 && !lockError" class="py-16 text-center text-slate-400 space-y-2">
                            <i class="fas fa-calendar-times text-4xl opacity-40"></i>
                            <p class="text-sm font-semibold text-slate-600">No scheduled voyages found for <span x-text="searchDate" class="font-mono font-bold"></span>.</p>
                            <p class="text-xs text-slate-400">Please select another date using the monthly calendar picker above.</p>
                        </div>

                        <!-- Structured Table / Card View of Available Voyages -->
                        <div x-show="!isLoadingAvailability && availableVoyagesList.length > 0" class="space-y-4">
                            <template x-for="v in availableVoyagesList" :key="v.voyage_id">
                                <div @click="selectVoyageForReschedule(v)" class="p-5 rounded-2xl border transition-all cursor-pointer"
                                     :class="selectedNewVoyageId === (v.voyage_id || v.id) ? 'bg-indigo-50/80 border-indigo-400 shadow-lg ring-2 ring-indigo-400/30' : 'bg-slate-50/80 hover:bg-slate-100/60 border-slate-200/80'">
                                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-slate-200/60 pb-3 mb-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-brand-600 text-white flex items-center justify-center font-bold text-base shadow-md">
                                                <i class="fas fa-ship"></i>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <h5 class="font-extrabold text-slate-800 text-base" x-text="v.ferry.name"></h5>
                                                    <span x-show="selectedNewVoyageId === (v.voyage_id || v.id)" class="px-2 py-0.5 rounded-full bg-indigo-600 text-white text-[10px] font-bold uppercase tracking-wider flex items-center gap-1">
                                                        <i class="fas fa-check"></i> Selected Ferry
                                                    </span>
                                                </div>
                                                <span class="text-xs font-bold text-slate-500" x-text="v.route.name"></span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 text-sm font-mono font-bold text-slate-700 bg-white px-3.5 py-1.5 rounded-xl border border-slate-200 shadow-2xs">
                                            <i class="fas fa-clock text-amber-500"></i>
                                            <span x-text="v.departure_time"></span>
                                            <i class="fas fa-arrow-right text-slate-400 text-xs"></i>
                                            <span x-text="v.arrival_time"></span>
                                        </div>
                                    </div>

                                    <!-- Classes & Capacity (Informative / Quick Pick for Active Passenger) -->
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                        <template x-for="cls in v.ferry.classes" :key="cls.class_id || cls.id">
                                            <button type="button" @click.stop="selectVoyageForReschedule(v); acquireRescheduleLock(v.voyage_id || v.id, cls.class_id || cls.id, cls, selectedTicketId)"
                                                    :disabled="cls.available_seats <= 0"
                                                    :class="selectedNewVoyageId === (v.voyage_id || v.id) && selectedTicketId && ticketSelections[selectedTicketId] && ticketSelections[selectedTicketId].classId === (cls.class_id || cls.id) ? 'bg-emerald-600 text-white border-emerald-600 shadow-md ring-2 ring-emerald-400 ring-offset-2' : (cls.available_seats > 0 ? 'bg-white hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 border-slate-200 hover:border-emerald-300' : 'bg-slate-100 text-slate-400 border-slate-200 opacity-60 cursor-not-allowed')"
                                                    class="p-3 rounded-xl border text-left transition-all flex flex-col justify-between">
                                                <div class="flex justify-between items-start">
                                                    <span class="font-bold text-xs truncate" x-text="cls.name || cls.class_name"></span>
                                                    <i class="fas fa-check-circle text-xs" x-show="selectedNewVoyageId === (v.voyage_id || v.id) && selectedTicketId && ticketSelections[selectedTicketId] && ticketSelections[selectedTicketId].classId === (cls.class_id || cls.id)"></i>
                                                </div>
                                                <div class="mt-2 flex items-baseline justify-between">
                                                    <span class="text-xs font-mono font-extrabold" :class="selectedNewVoyageId === (v.voyage_id || v.id) && selectedTicketId && ticketSelections[selectedTicketId] && ticketSelections[selectedTicketId].classId === (cls.class_id || cls.id) ? 'text-white' : 'text-brand-600'" x-text="cls.fare_formatted"></span>
                                                    <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-md"
                                                          :class="selectedNewVoyageId === (v.voyage_id || v.id) && selectedTicketId && ticketSelections[selectedTicketId] && ticketSelections[selectedTicketId].classId === (cls.class_id || cls.id) ? 'bg-emerald-700 text-emerald-100' : (cls.available_seats > 10 ? 'bg-emerald-100 text-emerald-800' : (cls.available_seats > 0 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800'))"
                                                          x-text="cls.available_seats + ' left'"></span>
                                                </div>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Right Column (col-span-12 lg:col-span-5): Passenger Details & Seat Selection Pod -->
                <div class="col-span-12 lg:col-span-5 flex flex-col gap-6">
                    <div class="bg-white rounded-3xl shadow-xl border border-slate-200/80 p-6">
                        <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                            <div>
                                <h4 class="text-base font-bold text-slate-800 flex items-center gap-2">
                                    <i class="fas fa-users text-amber-500"></i> Target Ticket & Passenger Pod
                                </h4>
                                <span class="text-xs text-slate-400 font-medium">Select target tickets and class allocation below</span>
                            </div>
                            <span class="text-xs font-bold font-mono px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200" x-text="selectedTicketNumber || 'All Passengers Shown'"></span>
                        </div>

                        <!-- Single Ticket Focus Banner -->
                        <div x-show="selectedTicketId" class="mb-3.5 p-3 rounded-2xl bg-indigo-50/90 border border-indigo-200 flex items-center justify-between text-xs animate-fadeIn">
                            <div class="flex items-center gap-2 text-indigo-900 font-bold">
                                <i class="fas fa-filter text-indigo-600"></i>
                                <span>Rescheduling Single Ticket (<span x-text="selectedTicketNumber" class="font-mono font-extrabold"></span>)</span>
                            </div>
                            <button type="button" @click="selectedTicketId = null; selectedTicketNumber = '';" class="font-extrabold text-indigo-700 hover:text-indigo-900 transition-colors underline flex items-center gap-1">
                                <i class="fas fa-users text-[10px]"></i> Show All {{ $booking->items->whereNotIn('status', ['cancelled', 'rescheduled', 'boarded'])->where('is_past_due', false)->count() }} Active Passengers
                            </button>
                        </div>

                        <!-- Stacked Passenger Cards -->
                        <div class="space-y-3.5 max-h-[480px] overflow-y-auto pr-1">
                            @if($booking->items->whereNotIn('status', ['cancelled', 'rescheduled', 'boarded'])->where('is_past_due', false)->count() === 0)
                                <div class="p-8 rounded-2xl bg-slate-50 border border-slate-200 text-center space-y-3.5">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-200 text-slate-500 flex items-center justify-center text-xl mx-auto shadow-2xs">
                                        <i class="fas fa-ship"></i>
                                    </div>
                                    <div>
                                        <h5 class="font-extrabold text-slate-800 text-sm">No Active Tickets Available for Reschedule / Edit</h5>
                                        <p class="text-xs text-slate-500 max-w-xs mx-auto mt-1">All tickets on this booking have either already departed (due), been boarded, or were previously rescheduled/cancelled.</p>
                                    </div>
                                </div>
                            @endif
                            @foreach($booking->items as $item)
                                @if(!in_array($item->status, ['cancelled', 'rescheduled', 'boarded']) && !$item->is_past_due)
                                    <div x-show="!selectedTicketId || selectedTicketId === {{ $item->id }}"
                                         @click="selectedTicketId = {{ $item->id }}; selectedTicketNumber = '{{ $item->ticket_number }}'; selectedTicketClassId = {{ $item->ferry_class_id ?? 'null' }};"
                                         :class="selectedTicketId === {{ $item->id }} ? 'bg-amber-50/90 border-amber-400 shadow-md ring-2 ring-amber-400/30' : 'bg-slate-50/70 hover:bg-slate-100/80 border-slate-200/80'"
                                         class="p-4 rounded-2xl border transition-all cursor-pointer space-y-3">
                                        @if($item->print_count > 0)
                                            <div class="p-2 bg-amber-100/80 rounded-xl border border-amber-300 text-amber-900 text-[11px] font-bold flex items-center gap-1.5">
                                                <i class="fas fa-exclamation-triangle text-amber-600"></i>
                                                <span>Already Printed ({{ $item->print_count }}x): Please make sure to reprint the new ticket for this passenger!</span>
                                            </div>
                                        @endif
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex items-start gap-2.5 flex-1">
                                                <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs shadow-2xs mt-0.5"
                                                     :class="selectedTicketId === {{ $item->id }} ? 'bg-amber-500 text-white' : 'bg-slate-200 text-slate-700'">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div class="flex-1">
                                                    <!-- Passenger Name (Editable unless past due) -->
                                                    <div class="flex items-center gap-2">
                                                        <template x-if="editingPassengerId === {{ $item->id }}">
                                                            <div class="flex items-center gap-1.5 w-full" @click.stop>
                                                                <input type="text" x-model="passengerNames[{{ $item->id }}]"
                                                                       class="px-2.5 py-1 text-xs font-bold text-slate-900 border border-amber-400 rounded-lg focus:ring-2 focus:ring-amber-500 w-full shadow-2xs">
                                                                <button type="button" @click="editingPassengerId = null" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-bold shadow-2xs">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            </div>
                                                        </template>
                                                        <template x-if="editingPassengerId !== {{ $item->id }}">
                                                            <div class="flex items-center gap-2">
                                                                <h5 class="font-extrabold text-slate-800 text-sm" x-text="passengerNames[{{ $item->id }}]"></h5>
                                                                @if(!$item->is_past_due)
                                                                    <button type="button" @click.stop="editingPassengerId = {{ $item->id }}" class="text-slate-400 hover:text-brand-600 transition-colors" title="Edit Passenger Name">
                                                                        <i class="fas fa-edit text-xs"></i>
                                                                    </button>
                                                                @else
                                                                    <span class="text-slate-400 text-xs" title="Past due ticket: Cannot edit passenger name">
                                                                        <i class="fas fa-lock"></i>
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </template>
                                                    </div>
                                                    <div class="flex items-center gap-3 mt-1">
                                                        <span class="font-mono text-xs text-slate-500 font-bold">{{ $item->ticket_number }}</span>
                                                        <span class="text-xs font-mono font-extrabold text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200 shadow-2xs">
                                                            Fare: {{ number_format((float) ($item->final_fare ?? 0), 2) }} TZS
                                                        </span>
                                                    </div>

                                                    <!-- Current Booked Vessel, Journey Date & Status -->
                                                    <div class="mt-2.5 pt-2 border-t border-slate-200/60 flex flex-wrap items-center justify-between gap-2 text-xs">
                                                        <div class="flex items-center gap-2 font-mono text-slate-700 font-bold">
                                                            <span class="px-2 py-0.5 rounded-lg bg-indigo-50 text-indigo-800 border border-indigo-200 shadow-2xs">
                                                                <i class="fas fa-ship text-indigo-600 mr-1"></i>{{ $item->voyage?->ferry?->name ?? 'Vessel N/A' }} ({{ $item->voyage?->departure_time ?? '--:--' }})
                                                            </span>
                                                            <span class="px-2 py-0.5 rounded-lg bg-amber-50 text-amber-900 border border-amber-200 shadow-2xs">
                                                                <i class="fas fa-calendar-day text-amber-600 mr-1"></i>{{ $item->voyage?->departure_date ?? $booking->travel_date }}
                                                            </span>
                                                        </div>
                                                        <div>
                                                            @php
                                                                $itemStatusColors = [
                                                                    'confirmed' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                                                    'boarded' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
                                                                    'cancelled' => 'bg-rose-100 text-rose-800 border-rose-300',
                                                                    'pending' => 'bg-amber-100 text-amber-800 border-amber-300',
                                                                ];
                                                                $iClass = $itemStatusColors[$item->status] ?? 'bg-slate-100 text-slate-700 border-slate-300';
                                                            @endphp
                                                            <span class="px-2 py-0.5 rounded-md border text-[10px] uppercase font-extrabold {{ $iClass }}">
                                                                {{ $item->status }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md bg-slate-200 text-slate-700 flex-shrink-0">{{ $item->ferryClass?->name ?? 'Class' }}</span>
                                        </div>

                                        <!-- Target Class & Seat Status -->
                                        <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between text-xs">
                                            <span class="text-slate-500 font-medium">Status & Allocation:</span>
                                            <div class="flex items-center gap-1.5 font-bold" :class="ticketSelections[{{ $item->id }}] ? 'text-emerald-700' : 'text-slate-400'">
                                                <span x-text="ticketSelections[{{ $item->id }}] ? ticketSelections[{{ $item->id }}].classObj.name + ' (' + ticketSelections[{{ $item->id }}].classObj.fare_formatted + ')' : 'Pick ferry & class below'"></span>
                                                <i class="fas fa-check-circle text-emerald-600" x-show="ticketSelections[{{ $item->id }}]"></i>
                                            </div>
                                        </div>

                                        @if($item->is_past_due)
                                            <!-- Past Due Ticket Lock Notice -->
                                            <div class="pt-2 border-t border-slate-200/60">
                                                <div class="py-2 px-3 rounded-xl bg-slate-100 border border-slate-200 text-slate-500 text-xs font-bold flex items-center justify-center gap-1.5">
                                                    <i class="fas fa-history text-slate-400"></i>
                                                    <span>Past Due Ticket (Cannot be rescheduled or modified)</span>
                                                </div>
                                            </div>
                                        @else
                                            <!-- Interactive Class Selection for THIS Passenger -->
                                            <div x-show="selectedNewVoyage && selectedNewVoyage.ferry && selectedNewVoyage.ferry.classes" class="pt-2 border-t border-amber-200/60 space-y-2">
                                                <div class="text-[11px] font-bold text-amber-800 flex items-center justify-between">
                                                    <span>Select Target Class for <span x-text="passengerNames[{{ $item->id }}]"></span>:</span>
                                                    <span class="text-[10px] text-slate-500 font-mono" x-text="selectedNewVoyage.ferry.name"></span>
                                                </div>
                                                <div class="grid grid-cols-3 gap-1.5">
                                                    <template x-for="cls in (selectedNewVoyage.ferry ? selectedNewVoyage.ferry.classes : [])" :key="cls.class_id || cls.id">
                                                        <button type="button" @click.stop="selectedTicketId = {{ $item->id }}; acquireRescheduleLock(selectedNewVoyageId, cls.class_id || cls.id, cls, {{ $item->id }})"
                                                                :disabled="cls.available_seats <= 0"
                                                                :class="ticketSelections[{{ $item->id }}] && ticketSelections[{{ $item->id }}].classId === (cls.class_id || cls.id) ? 'bg-emerald-600 text-white font-extrabold shadow-sm ring-1 ring-emerald-400' : (cls.available_seats > 0 ? 'bg-white hover:bg-emerald-50 text-slate-700 border border-slate-200' : 'bg-slate-100 text-slate-400 opacity-50 cursor-not-allowed')"
                                                                class="py-1.5 px-2 rounded-xl text-center transition-all flex flex-col items-center justify-center">
                                                            <span class="text-[11px] font-bold truncate block w-full" x-text="cls.name || cls.class_name"></span>
                                                            <span class="text-[10px] font-mono opacity-90 block mt-0.5" x-text="cls.fare_formatted"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        <!-- Active Lock Badge (Clean, clear timer explanation without incrementing numbers) -->
                        <div x-show="activeLockId || Object.keys(ticketSelections).length > 0" class="mt-4 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-between animate-pulse">
                            <div class="flex items-center gap-2.5 text-xs text-emerald-800 font-bold">
                                <i class="fas fa-lock text-base text-emerald-600"></i>
                                <div>
                                    <span class="block">Seat Inventory Locked for 5 Minutes</span>
                                    <span class="text-[10px] text-emerald-600 font-normal">Saves seat while you finalize changes</span>
                                </div>
                            </div>
                            <button type="button" @click="releaseRescheduleLock()" class="text-xs font-bold text-rose-600 hover:underline flex items-center gap-1">
                                <i class="fas fa-times-circle"></i> Release Lock
                            </button>
                        </div>

                        <!-- Price Difference & Dual Control Summary -->
                        <div class="mt-6 pt-4 border-t border-slate-100 space-y-4">
                            <div class="flex items-center justify-between text-sm font-bold">
                                <span class="text-slate-600">Live Price Difference:</span>
                                <span class="text-lg font-extrabold font-mono text-emerald-600" x-text="computeLivePriceDifference()"></span>
                            </div>

                            <div class="p-3.5 bg-amber-50/80 rounded-2xl border border-amber-200/80 flex items-start gap-2.5 text-xs text-amber-900 font-medium">
                                <i class="fas fa-shield-alt text-amber-600 text-base flex-shrink-0 mt-0.5"></i>
                                <div>
                                    <strong class="font-bold block">Dual Control Notice:</strong>
                                    Downgrading ticket classes triggers supervisor review before final ticket reissue.
                                </div>
                            </div>

                            <!-- Finalize Form -->
                            <form :action="Object.keys(ticketSelections).length > 1 ? `{{ route('portal.bookings.reschedule-batch', $booking) }}` : `/portal/bookings/{{ $booking->id }}/tickets/${selectedTicketId}/reschedule`" method="POST" class="space-y-3">
                                @csrf
                                <input type="hidden" name="new_voyage_id" :value="selectedNewVoyageId">
                                <input type="hidden" name="new_ferry_class_id" :value="selectedNewClassId">
                                <input type="hidden" name="lock_id" :value="activeLockId">
                                <input type="hidden" name="passenger_name" :value="selectedTicketId ? passengerNames[selectedTicketId] : ''">

                                <!-- Batch Inputs for independent per-ticket configuration -->
                                <template x-for="(sel, itemId) in ticketSelections" :key="itemId">
                                    <div>
                                        <input type="hidden" :name="`tickets[${itemId}][ticket_id]`" :value="itemId">
                                        <input type="hidden" :name="`tickets[${itemId}][voyage_id]`" :value="sel.voyageId">
                                        <input type="hidden" :name="`tickets[${itemId}][ferry_class_id]`" :value="sel.classId">
                                        <input type="hidden" :name="`tickets[${itemId}][passenger_name]`" :value="passengerNames[itemId]">
                                        <input type="hidden" :name="`tickets[${itemId}][lock_id]`" :value="sel.lockId || ''">
                                    </div>
                                </template>

                                <div class="flex gap-3">
                                    <button type="button" @click="activeTab = 'manifest'; resetRescheduleFlow();"
                                            class="w-1/3 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-2xl text-sm transition-all">
                                        Cancel
                                    </button>
                                    <button type="submit" :disabled="Object.keys(ticketSelections).length === 0"
                                            :class="Object.keys(ticketSelections).length > 0 ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-600/30 cursor-pointer' : 'bg-slate-200 text-slate-400 cursor-not-allowed'"
                                            class="w-2/3 py-3.5 font-bold rounded-2xl text-sm transition-all flex items-center justify-center gap-2">
                                        <i class="fas fa-check-circle"></i>
                                        <span x-text="Object.keys(ticketSelections).length > 1 ? `Confirm & Finalize All (${Object.keys(ticketSelections).length} Tickets)` : 'Confirm & Finalize'"></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: PRINTER SELECTION HUB -->
        <div x-show="showPrintAllModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" style="display: none;" x-cloak>
            <div @click.away="showPrintAllModal = false" class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl border border-slate-200/80 animate-scale-up text-slate-800">
                <div class="flex justify-between items-center mb-5 pb-3 border-b border-slate-100">
                    <h3 class="text-lg font-extrabold text-slate-800 tracking-tight flex items-center gap-2">
                        <i class="fas fa-print text-brand-600"></i> Printer Selection Hub
                    </h3>
                    <button @click="showPrintAllModal = false" class="text-slate-400 hover:text-slate-600 transition-colors text-lg"><i class="fas fa-times"></i></button>
                </div>

                @php
                    $hasPrintedTickets = $booking->items()->where('status', '!=', 'cancelled')->where('print_count', '>=', 1)->exists();
                    $requiresAllReprintDC = ($hasPrintedTickets && auth()->user() && !auth()->user()->hasAnyPermission(['booking:reprint_unlimited', 'ticket:reprint_unlimited']));
                @endphp

                @if($requiresAllReprintDC)
                    <div class="mb-5 p-3.5 bg-amber-50 rounded-2xl border border-amber-200/80 flex items-start gap-3 text-amber-800 text-xs">
                        <i class="fas fa-shield-alt text-amber-600 text-base mt-0.5"></i>
                        <div>
                            <span class="font-bold block">Reprint Approval Required</span>
                            One or more selected tickets have already been printed. Clicking either option will submit a Dual Control authorization request.
                        </div>
                    </div>
                @endif

                <div class="space-y-4">
                    <p class="text-xs text-slate-500">
                        Select destination printer format for <span class="font-bold text-slate-800" x-text="selectedPrintTicketIds.length > 0 ? selectedPrintTicketIds.length + ' selected ticket(s)' : 'all active tickets'"></span> on this booking. Click any card to select option, or double-click to print directly.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-1">
                        <!-- Thermal Receipt Card -->
                        <div @click="selectedPrintFormat = 'thermal'"
                             @dblclick="$refs.thermalPrintForm.submit(); showPrintAllModal = false;"
                             :class="selectedPrintFormat === 'thermal'
                                 ? 'bg-amber-50/90 border-brand-500 shadow-md ring-4 ring-brand-500/15 scale-[1.01]'
                                 : 'bg-slate-50/80 hover:bg-slate-100/90 border-slate-200/80 hover:border-slate-300 opacity-80 hover:opacity-100'"
                             class="border-2 rounded-2xl p-5 flex flex-col justify-between transition-all duration-200 cursor-pointer select-none group">
                            <div>
                                <div class="flex items-center justify-between">
                                    <div :class="selectedPrintFormat === 'thermal' ? 'bg-brand-500 text-white shadow-md' : 'bg-slate-200 text-slate-600 group-hover:bg-brand-100 group-hover:text-brand-600'"
                                         class="w-11 h-11 rounded-2xl flex items-center justify-center text-xl transition-colors shadow-2xs">
                                        <i class="fas fa-receipt"></i>
                                    </div>
                                    <div :class="selectedPrintFormat === 'thermal' ? 'border-brand-600 bg-brand-50' : 'border-slate-300 bg-white'"
                                         class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all">
                                        <div x-show="selectedPrintFormat === 'thermal'" class="w-3 h-3 rounded-full bg-brand-600 animate-scale-up"></div>
                                    </div>
                                </div>
                                <h4 class="font-extrabold text-slate-900 text-base mt-4 leading-snug">80mm Thermal Receipt</h4>
                                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Compact thermal format optimized for POS receipt printers (SRP-350 / EPSON).</p>
                            </div>
                            <form x-ref="thermalPrintForm" method="POST" action="{{ route('portal.bookings.print-all', $booking) }}" target="_blank" class="mt-5" @click.stop>
                                @csrf
                                <input type="hidden" name="template" value="thermal">
                                <template x-for="tid in selectedPrintTicketIds" :key="tid">
                                    <input type="hidden" name="ticket_ids[]" :value="tid">
                                </template>
                                <button type="submit" :disabled="selectedPrintTicketIds.length === 0" @click="showPrintAllModal = false"
                                        :class="selectedPrintFormat === 'thermal' ? 'bg-brand-600 hover:bg-brand-700 text-white shadow-md' : 'bg-slate-200 hover:bg-slate-300 text-slate-700'"
                                        class="w-full py-2.5 font-bold rounded-xl text-xs transition-all flex items-center justify-center gap-2">
                                    <i class="fas fa-print"></i>
                                    <span>Select & Print Thermal</span>
                                </button>
                            </form>
                        </div>

                        <!-- A4 Standard Office Card -->
                        <div @click="selectedPrintFormat = 'standard'"
                             @dblclick="$refs.standardPrintForm.submit(); showPrintAllModal = false;"
                             :class="selectedPrintFormat === 'standard'
                                 ? 'bg-slate-100/90 border-slate-800 shadow-md ring-4 ring-slate-800/15 scale-[1.01]'
                                 : 'bg-slate-50/80 hover:bg-slate-100/90 border-slate-200/80 hover:border-slate-300 opacity-80 hover:opacity-100'"
                             class="border-2 rounded-2xl p-5 flex flex-col justify-between transition-all duration-200 cursor-pointer select-none group">
                            <div>
                                <div class="flex items-center justify-between">
                                    <div :class="selectedPrintFormat === 'standard' ? 'bg-slate-800 text-white shadow-md' : 'bg-slate-200 text-slate-600 group-hover:bg-slate-300 group-hover:text-slate-800'"
                                         class="w-11 h-11 rounded-2xl flex items-center justify-center text-xl transition-colors shadow-2xs">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <div :class="selectedPrintFormat === 'standard' ? 'border-slate-800 bg-slate-200' : 'border-slate-300 bg-white'"
                                         class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all">
                                        <div x-show="selectedPrintFormat === 'standard'" class="w-3 h-3 rounded-full bg-slate-800 animate-scale-up"></div>
                                    </div>
                                </div>
                                <h4 class="font-extrabold text-slate-900 text-base mt-4 leading-snug">A4 Office Document</h4>
                                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Full-page PDF boarding pass & itinerary suitable for standard HP/Canon office printers.</p>
                            </div>
                            <form x-ref="standardPrintForm" method="POST" action="{{ route('portal.bookings.print-all', $booking) }}" target="_blank" class="mt-5" @click.stop>
                                @csrf
                                <input type="hidden" name="template" value="standard">
                                <template x-for="tid in selectedPrintTicketIds" :key="tid">
                                    <input type="hidden" name="ticket_ids[]" :value="tid">
                                </template>
                                <button type="submit" :disabled="selectedPrintTicketIds.length === 0" @click="showPrintAllModal = false"
                                        :class="selectedPrintFormat === 'standard' ? 'bg-slate-800 hover:bg-slate-900 text-white shadow-md' : 'bg-slate-200 hover:bg-slate-300 text-slate-700'"
                                        class="w-full py-2.5 font-bold rounded-xl text-xs transition-all flex items-center justify-center gap-2">
                                    <i class="fas fa-print"></i>
                                    <span>Select & Print A4 PDF</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-5 border-t border-slate-100 mt-4">
                        <div class="text-xs text-slate-500 flex items-center gap-1.5">
                            <i class="fas fa-mouse-pointer text-brand-500"></i>
                            <span>Click card to switch format, or double-click card to print directly.</span>
                        </div>
                        <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                            <button type="button" @click="showPrintAllModal = false" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition-colors">
                                Cancel
                            </button>
                            <button type="button" :disabled="selectedPrintTicketIds.length === 0"
                                    @click="if(selectedPrintFormat === 'thermal') { $refs.thermalPrintForm.submit(); } else { $refs.standardPrintForm.submit(); } showPrintAllModal = false;"
                                    class="px-6 py-2.5 bg-brand-600 hover:bg-brand-700 disabled:opacity-40 text-white font-extrabold rounded-xl text-xs shadow-md transition-all flex items-center gap-2">
                                <i class="fas fa-print"></i>
                                <span>Proceed & Print (<span x-text="selectedPrintFormat === 'thermal' ? 'Thermal' : 'A4 PDF'"></span>)</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: COMPLETE PAYMENT -->
        <div x-show="showCompleteModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" style="display: none;" x-cloak>
            <div @click.away="showCompleteModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200/80 animate-scale-up">
                <div class="flex justify-between items-center mb-4 border-b border-slate-100 pb-3">
                    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <i class="fas fa-check-circle text-emerald-600"></i> Complete Booking Payment
                    </h3>
                    <button @click="showCompleteModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                </div>
                <form method="POST" action="{{ route('portal.bookings.complete', $booking) }}" class="space-y-4">
                    @csrf
                    <div class="p-3 bg-emerald-50 rounded-2xl border border-emerald-200/60 text-xs text-emerald-800">
                        <span class="font-bold block">Net Payable Amount:</span>
                        <span class="text-lg font-bold">{{ $booking->currency }} {{ number_format($booking->net_amount, 2) }}</span>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Payment Channel <span class="text-rose-500">*</span></label>
                        <select name="payment_channel" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold focus:ring-2 focus:ring-brand-500">
                            <option value="Cash">Cash (Office / Counter)</option>
                            <option value="CRDB Bank">CRDB Bank / SimBanking</option>
                            <option value="M-Pesa">Vodacom M-Pesa</option>
                            <option value="TigoPesa">TigoPesa / Mixx by Yas</option>
                            <option value="Airtel Money">Airtel Money</option>
                            <option value="Halopesa">Halopesa</option>
                            <option value="Card / POS">Bank Card / POS Terminal</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Transaction / Receipt Reference (Optional)</label>
                        <input type="text" name="transaction_reference" placeholder="e.g. CRDB-9843234 or POS-8872" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm font-mono focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                        <button type="button" @click="showCompleteModal = false" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl text-sm">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-sm text-sm">Confirm Payment</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: EDIT BOOKING -->
        <div x-show="showEditModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" style="display: none;" x-cloak>
            <div @click.away="showEditModal = false" class="bg-white rounded-3xl max-w-3xl w-full p-6 shadow-2xl border border-slate-200/80 max-h-[90vh] overflow-y-auto animate-scale-up">
                <div class="flex justify-between items-center mb-4 border-b border-slate-100 pb-3">
                    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <i class="fas fa-edit text-blue-600"></i> Edit Booking & Passenger Details
                    </h3>
                    <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                </div>
                <form method="POST" action="{{ route('portal.bookings.update', $booking) }}" class="space-y-4">
                    @csrf
                    @if($booking->edit_count >= 1)
                        <div class="p-3.5 bg-amber-50 rounded-2xl border border-amber-200 text-amber-900 text-xs font-medium flex items-start gap-2.5">
                            <i class="fas fa-shield-alt text-amber-600 text-base mt-0.5"></i>
                            <div>
                                <strong class="font-bold block">Booking Already Edited (Count: {{ $booking->edit_count }})</strong>
                                Subsequent modifications require the <strong>Multi-Edit Booking (booking:multi_edit)</strong> permission. Downgrading ticket class requires Dual Control supervisor approval.
                            </div>
                        </div>
                    @endif
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1 uppercase tracking-wider">Admin Notes / Special Request</label>
                        <textarea name="notes" rows="2" class="w-full px-4 py-2 rounded-xl border border-slate-300 text-sm font-mono">{{ $booking->notes }}</textarea>
                    </div>

                    <div class="space-y-4 pt-2">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <div>
                                <h4 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Update Manifest Passengers & Schedule</h4>
                                <span class="text-xs font-normal text-slate-500">Upgrades allowed instantly • Downgrades require Dual Control</span>
                            </div>
                            <button type="button" @click="showEditModal = false; resetRescheduleFlow(); activeTab = 'reschedule'; checkRescheduleAvailability();"
                                    class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl text-xs shadow-sm flex items-center gap-1.5 transition-all">
                                <i class="fas fa-search-dollar"></i> Check Live Availability & Lock Seats
                            </button>
                        </div>
                        @foreach($booking->items as $item)
                            @if(!in_array($item->status, ['cancelled', 'rescheduled', 'boarded']) && !$item->is_past_due)
                                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/60 space-y-3">
                                    <div class="flex items-center justify-between text-xs font-bold font-mono text-brand-600 border-b border-slate-200/40 pb-2">
                                        <span><i class="fas fa-ticket-alt mr-1"></i> {{ $item->ticket_number }}</span>
                                        <span class="text-slate-500 font-sans font-semibold">Current Class: {{ $item->ferryClass?->name ?? 'N/A' }}</span>
                                    </div>
                                    @if($item->print_count > 0)
                                        <div class="p-2.5 bg-amber-50 rounded-xl border border-amber-300 text-amber-900 text-[11px] font-bold flex items-center gap-2">
                                            <i class="fas fa-exclamation-triangle text-amber-600"></i>
                                            <span>Already Printed ({{ $item->print_count }}x): Editing this ticket requires reprinting so passenger has a valid physical ticket!</span>
                                        </div>
                                    @endif
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <div class="md:col-span-1">
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Journey Date / Voyage</label>
                                            <select name="items[{{ $item->id }}][voyage_id]" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold bg-white">
                                                @foreach($availableVoyages as $voyage)
                                                    <option value="{{ $voyage->id }}" {{ $item->voyage_id == $voyage->id ? 'selected' : '' }}>
                                                        {{ \Carbon\Carbon::parse($voyage->departure_time)->format('M d, H:i') }} (#{{ $voyage->id }})
                                                    </option>
                                                @endforeach
                                                @if(!$availableVoyages->contains('id', $item->voyage_id) && $item->voyage)
                                                    <option value="{{ $item->voyage_id }}" selected>
                                                        Current: {{ \Carbon\Carbon::parse($item->voyage->departure_time)->format('M d, H:i') }}
                                                    </option>
                                                @endif
                                            </select>
                                        </div>
                                        <div class="md:col-span-1">
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Seating Class</label>
                                            <select name="items[{{ $item->id }}][ferry_class_id]" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold bg-white">
                                                @foreach($ferryClasses as $fc)
                                                    @php
                                                        $isDowngrade = ($item->ferryClass && $fc->sort_order > $item->ferryClass->sort_order);
                                                        $isUpgrade = ($item->ferryClass && $fc->sort_order < $item->ferryClass->sort_order);
                                                    @endphp
                                                    <option value="{{ $fc->id }}" {{ $item->ferry_class_id == $fc->id ? 'selected' : '' }}>
                                                        {{ $fc->name }} {{ $isUpgrade ? '⬆ Upgrade' : ($isDowngrade ? '⬇ Downgrade (Dual Control)' : '') }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="md:col-span-1">
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Seat Number</label>
                                            <input type="text" name="items[{{ $item->id }}][seat_number]" value="{{ $item->seat_number }}" placeholder="e.g. 14A" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-mono">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 pt-2 border-t border-slate-200/60">
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1">First Name</label>
                                            <input type="text" name="items[{{ $item->id }}][passenger][first_name]" value="{{ $item->passenger?->first_name }}" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Last Name</label>
                                            <input type="text" name="items[{{ $item->id }}][passenger][last_name]" value="{{ $item->passenger?->last_name }}" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1">ID Number</label>
                                            <input type="text" name="items[{{ $item->id }}][passenger][id_number]" value="{{ $item->passenger?->id_number }}" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Phone</label>
                                            <input type="text" name="items[{{ $item->id }}][passenger][phone]" value="{{ $item->passenger?->phone }}" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" @click="showEditModal = false" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl text-sm">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-sm text-sm">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: CANCEL ENTIRE BOOKING -->
        <div x-show="showCancelModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" style="display: none;" x-cloak>
            <div @click.away="showCancelModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200/80 animate-scale-up">
                <div class="flex justify-between items-center mb-4 border-b border-slate-100 pb-3">
                    <h3 class="text-lg font-bold text-rose-600 flex items-center gap-2">
                        <i class="fas fa-exclamation-circle"></i> Cancel Entire Booking
                    </h3>
                    <button @click="showCancelModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                </div>
                <form method="POST" action="{{ route('portal.bookings.cancel', $booking) }}" class="space-y-4">
                    @csrf
                    <p class="text-xs text-slate-600 leading-relaxed">
                        This action will cancel all <span class="font-bold">{{ $booking->items()->where('status', '!=', 'cancelled')->count() }} active ticket(s)</span>, release reserved seat(s), and apply the configured cancellation penalty (<span class="font-bold">CPP: 10%</span>) if paid.
                    </p>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Cancellation Reason <span class="text-rose-500">*</span></label>
                        <textarea name="reason" rows="3" required placeholder="e.g. Customer requested cancellation due to weather schedule change..." class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                        <button type="button" @click="showCancelModal = false" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl text-sm">Dismiss</button>
                        <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl shadow-sm text-sm">Confirm Cancellation</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: CANCEL SINGLE TICKET -->
        <div x-show="showTicketCancelModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" style="display: none;" x-cloak>
            <div @click.away="showTicketCancelModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200/80 animate-scale-up">
                <div class="flex justify-between items-center mb-4 border-b border-slate-100 pb-3">
                    <h3 class="text-lg font-bold text-rose-600 flex items-center gap-2">
                        <i class="fas fa-user-times"></i> Cancel Single Ticket
                    </h3>
                    <button @click="showTicketCancelModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                </div>
                <form :action="`/portal/bookings/{{ $booking->id }}/tickets/${selectedTicketId}/cancel`" method="POST" class="space-y-4">
                    @csrf
                    <div class="p-3 bg-rose-50 rounded-xl text-xs text-rose-800 font-semibold">
                        Cancelling Ticket: <span x-text="selectedTicketNumber" class="font-mono font-bold"></span>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Reason for Cancellation <span class="text-rose-500">*</span></label>
                        <textarea name="reason" rows="3" required placeholder="e.g. Passenger unable to travel on this date..." class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                        <button type="button" @click="showTicketCancelModal = false" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl text-sm">Back</button>
                        <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl shadow-sm text-sm">Cancel This Ticket</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: 3-STEP AVAILABILITY-FIRST RESCHEDULE TICKET -->
        <div x-show="showRescheduleModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" style="display: none;" x-cloak>
            <div @click.away="showRescheduleModal = false" class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200/80 animate-scale-up">
                <div class="flex justify-between items-center mb-4 border-b border-slate-100 pb-3">
                    <h3 class="text-lg font-bold text-amber-600 flex items-center gap-2">
                        <i class="fas fa-calendar-alt"></i> Reschedule Ticket: 3-Step Availability Workflow
                    </h3>
                    <button @click="showRescheduleModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                </div>

                <div class="p-3.5 bg-amber-50 rounded-2xl border border-amber-200/80 mb-4 flex items-center justify-between text-xs text-amber-900 font-semibold">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-ticket-alt text-amber-600 text-sm"></i>
                        <span>Ticket: <strong x-text="selectedTicketNumber" class="font-mono"></strong></span>
                    </div>
                    <!-- Step Indicator Badge -->
                    <div class="flex items-center gap-1.5 font-bold uppercase tracking-wider text-[10px]">
                        <span :class="rescheduleStep === 1 ? 'bg-amber-500 text-white px-2 py-0.5 rounded-md shadow-sm' : 'bg-amber-200/60 text-amber-800 px-2 py-0.5 rounded-md'">Step 1: Search</span>
                        <i class="fas fa-chevron-right text-amber-400"></i>
                        <span :class="rescheduleStep === 2 ? 'bg-amber-500 text-white px-2 py-0.5 rounded-md shadow-sm' : 'bg-amber-200/60 text-amber-800 px-2 py-0.5 rounded-md'">Step 2: Select & Lock</span>
                        <i class="fas fa-chevron-right text-amber-400"></i>
                        <span :class="rescheduleStep === 3 ? 'bg-emerald-600 text-white px-2 py-0.5 rounded-md shadow-sm' : 'bg-amber-200/60 text-amber-800 px-2 py-0.5 rounded-md'">Step 3: Confirm</span>
                    </div>
                </div>

                <!-- Error Display -->
                <template x-if="lockError">
                    <div class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-bold flex items-center justify-between">
                        <span class="flex items-center gap-2"><i class="fas fa-exclamation-triangle"></i> <span x-text="lockError"></span></span>
                        <button type="button" @click="lockError = null" class="text-rose-400 hover:text-rose-600"><i class="fas fa-times"></i></button>
                    </div>
                </template>

                <!-- STEP 1: SEARCH AVAILABILITY -->
                <div x-show="rescheduleStep === 1" class="space-y-4">
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/60 space-y-3">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Select New Target Journey Date</label>
                        <div class="flex items-center gap-3">
                            <input type="date" x-model="searchDate" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold bg-white shadow-sm">
                            <button type="button" @click="checkRescheduleAvailability()" :disabled="isLoadingAvailability"
                                    class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 disabled:opacity-50 text-white font-bold rounded-xl shadow-sm text-sm flex items-center gap-2 transition-all">
                                <i class="fas fa-search" :class="{'animate-spin fa-spinner': isLoadingAvailability, 'fa-search': !isLoadingAvailability}"></i>
                                <span x-text="isLoadingAvailability ? 'Checking Live Inventory...' : 'Search Available Voyages'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: SELECT TARGET & LOCK -->
                <div x-show="rescheduleStep === 2" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold text-slate-600 uppercase tracking-wider">Available Voyages on <span x-text="searchDate" class="text-brand-600 font-mono font-bold"></span></h4>
                        <button type="button" @click="releaseRescheduleLock(); rescheduleStep = 1" class="text-xs font-bold text-brand-600 hover:underline flex items-center gap-1">
                            <i class="fas fa-arrow-left"></i> Change Date
                        </button>
                    </div>

                    <div class="max-h-80 overflow-y-auto space-y-3 pr-1">
                        <template x-if="availableVoyagesList.length === 0">
                            <div class="p-8 text-center bg-slate-50 rounded-2xl border border-slate-200 text-slate-500 text-sm font-medium">
                                <i class="fas fa-ship text-2xl text-slate-400 mb-2 block"></i>
                                No scheduled voyages with available capacity found for this date.
                            </div>
                        </template>

                        <template x-for="v in availableVoyagesList" :key="v.voyage_id">
                            <div class="p-4 rounded-2xl border border-slate-200 bg-white hover:border-brand-400 transition-all shadow-sm">
                                <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-3">
                                    <div>
                                        <span class="text-sm font-black text-slate-800 font-mono" x-text="v.departure_time"></span>
                                        <span class="text-xs font-bold text-slate-500 ml-2" x-text="`Voyage #${v.trip_number} (${v.ferry_name})`"></span>
                                    </div>
                                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md bg-slate-100 text-slate-600" x-text="v.route_name"></span>
                                </div>

                                <!-- Classes Deck -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                    <template x-for="cls in v.classes" :key="cls.class_id">
                                        <button type="button" @click="acquireRescheduleLock(v.voyage_id, cls.class_id, cls)"
                                                :disabled="cls.available_seats <= 0"
                                                class="text-left p-2.5 rounded-xl border transition-all relative flex flex-col justify-between"
                                                :class="cls.available_seats > 0 ? 'bg-slate-50 hover:bg-brand-50/60 border-slate-200 hover:border-brand-400 cursor-pointer' : 'bg-slate-100/60 border-slate-200 opacity-60 cursor-not-allowed'">
                                            <div class="flex items-center justify-between text-xs font-bold text-slate-800 mb-1">
                                                <span x-text="cls.class_name"></span>
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-black"
                                                      :class="cls.available_seats > 10 ? 'bg-emerald-100 text-emerald-800' : (cls.available_seats > 0 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800')"
                                                      x-text="`${cls.available_seats} SEATS`"></span>
                                            </div>

                                            <div class="flex items-center justify-between text-[10px] font-semibold mt-1 pt-1 border-t border-slate-200/50">
                                                <template x-if="cls.is_upgrade">
                                                    <span class="text-emerald-600 font-bold"><i class="fas fa-arrow-up"></i> Upgrade Instantly</span>
                                                </template>
                                                <template x-if="cls.is_downgrade">
                                                    <span class="text-amber-600 font-bold"><i class="fas fa-arrow-down"></i> Downgrade (Dual Control)</span>
                                                </template>
                                                <template x-if="!cls.is_upgrade && !cls.is_downgrade">
                                                    <span class="text-slate-500">Same Class</span>
                                                </template>
                                                <span class="text-brand-600 font-bold group-hover:underline">Select & Lock &rarr;</span>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- STEP 3: CONFIRM & SUBMIT WITH LOCK -->
                <div x-show="rescheduleStep === 3" class="space-y-4 animate-fade-in">
                    <form :action="`/portal/bookings/{{ $booking->id }}/tickets/${selectedTicketId}/reschedule`" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="voyage_id" :value="selectedNewVoyageId">
                        <input type="hidden" name="ferry_class_id" :value="selectedNewClassId">
                        <input type="hidden" name="lock_id" :value="activeLockId">

                        <div class="p-4 bg-emerald-50 rounded-2xl border border-emerald-200 text-emerald-900 space-y-2">
                            <div class="flex items-center justify-between font-bold text-sm">
                                <span class="flex items-center gap-2"><i class="fas fa-lock text-emerald-600"></i> Inventory Locked Successfully!</span>
                                <span class="text-xs px-2 py-0.5 rounded bg-emerald-200/70 font-mono" x-text="`Lock #${activeLockId}`"></span>
                            </div>
                            <p class="text-xs">
                                1 seat reserved on <strong x-text="selectedClassDetails?.class_name"></strong>. Lock automatically expires at <strong class="font-mono" x-text="activeLockExpires"></strong>.
                            </p>
                        </div>

                        <template x-if="selectedClassDetails?.is_downgrade">
                            <div class="p-3.5 bg-amber-50 rounded-2xl border border-amber-300 text-amber-900 text-xs font-semibold flex items-start gap-2.5">
                                <i class="fas fa-user-shield text-amber-600 text-base mt-0.5"></i>
                                <div>
                                    <strong class="font-bold block">Dual Control Supervisor Approval Required</strong>
                                    You have selected a downgrade class (<span x-text="selectedClassDetails?.class_name"></span>). Upon submission, a Dual Control approval request will be dispatched to supervisors before the ticket is finalized.
                                </div>
                            </div>
                        </template>

                        <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                            <button type="button" @click="releaseRescheduleLock(); rescheduleStep = 2" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl text-sm">Back to Selection</button>
                            <button type="submit" class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/20 text-sm flex items-center gap-2">
                                <i class="fas fa-check-circle"></i> Confirm & Finalize Reschedule
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL: EMAIL RECEIPT -->
        <div x-show="showEmailModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" style="display: none;" x-cloak>
            <div @click.away="showEmailModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200/80 animate-scale-up">
                <div class="flex justify-between items-center mb-4 border-b border-slate-100 pb-3">
                    <h3 class="text-lg font-bold text-indigo-600 flex items-center gap-2">
                        <i class="fas fa-paper-plane"></i> Dispatch e-Ticket & Receipt
                    </h3>
                    <button @click="showEmailModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                </div>
                <form method="POST" action="{{ route('portal.bookings.email', $booking) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Recipient Email Address <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" value="{{ $booking->user?->email }}" required placeholder="passenger@example.com" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm">
                    </div>
                    <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                        <button type="button" @click="showEmailModal = false" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl text-sm">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-sm text-sm">Send Now</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.iframe>
