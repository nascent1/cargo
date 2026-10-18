<x-layouts.admin>
    <x-slot:title>Cargo Booking: {{ $consignment->consignment_number }}</x-slot:title>
    <x-slot:header>Cargo Booking Profile</x-slot:header>

    <div x-data="{
        activeTab: 'items',
        showCancelModal: false,
        showStatusModal: false,
        showHandover: {{ $errors->any() || old('collector_name') ? 'true' : 'false' }},
    }">
        <!-- Sleek Light-Theme Professional Dashboard Workspace -->
        <div class="bg-slate-50 text-slate-800 p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm space-y-8">
            {{-- Port operations notices --}}
            @if($consignment->isLeftBehind())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-3 text-sm text-rose-800">
                    <i class="fas fa-triangle-exclamation mr-1"></i><strong>Left behind:</strong> paid but not loaded when the vessel departed
                    ({{ $consignment->left_behind_at->format('d M Y H:i') }}). Contact the sender about the next departure, or cancel and refund.
                </div>
            @endif
            @if(session('handover_warnings'))
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-3 text-sm text-amber-900">
                    <p class="font-bold"><i class="fas fa-triangle-exclamation mr-1"></i>Handed over with warnings (recorded):</p>
                    <ul class="list-disc ml-5 mt-1">@foreach(session('handover_warnings') as $w)<li>{{ $w }}</li>@endforeach</ul>
                </div>
            @endif
            <!-- Top Dashboard Header Row -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3 sm:gap-4">
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight font-mono">
                        Booking {{ $consignment->consignment_number }}
                    </h1>
                    @php
                        $statusColors = [
                            'pending_payment' => 'bg-orange-100 text-orange-800 border border-orange-300',
                            'booked' => 'bg-amber-100 text-amber-800 border border-amber-300',
                            'loaded' => 'bg-blue-100 text-blue-800 border border-blue-300',
                            'in_transit' => 'bg-violet-100 text-violet-800 border border-violet-300',
                            'delivered' => 'bg-emerald-600 text-white font-black px-3.5 py-1 rounded-full text-xs uppercase tracking-wider shadow-sm border-0',
                            'collected' => 'bg-emerald-100 text-emerald-800 border border-emerald-300',
                            'cancelled' => 'bg-rose-100 text-rose-800 border border-rose-300',
                        ];
                        $badgeClass = $statusColors[$consignment->status] ?? 'bg-slate-100 text-slate-700 border border-slate-200';
                    @endphp
                    @if($consignment->status === 'delivered')
                        <span class="{{ $badgeClass }}">
                            DELIVERED
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $badgeClass }}">
                            {{ $consignment->status === 'pending_payment' ? 'Awaiting payment' : str_replace('_', ' ', $consignment->status) }}
                        </span>
                    @endif
                    @if($actions['correct'])
                        <button type="button" @click="showStatusModal = true" class="text-xs font-semibold text-slate-500 hover:text-brand-600 underline decoration-dotted" title="Fix a wrong status (with a reason)">Correct status</button>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.cargo.consignments') }}" class="px-3.5 py-2 bg-white hover:bg-slate-100 text-slate-700 font-bold rounded-xl border border-slate-200 transition-colors text-xs flex items-center gap-1.5 shadow-sm">
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
                            <i class="fas fa-boxes text-brand-600"></i> Booking Overview
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3 gap-x-6 text-sm text-slate-700">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <span class="text-slate-500">Sender:</span>
                                <span class="font-bold text-slate-900 truncate max-w-[160px]" title="{{ $consignment->sender_phone }}">
                                    {{ $consignment->sender_name }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <span class="text-slate-500">Receiver:</span>
                                <span class="font-bold text-slate-900 truncate max-w-[160px]" title="{{ $consignment->receiver_phone }}">
                                    {{ $consignment->receiver_name }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <span class="text-slate-500">Route:</span>
                                <span class="font-bold text-slate-900 truncate max-w-[160px]">
                                    {{ $consignment->route?->name ?? 'N/A' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <span class="text-slate-500">Vessel / Unit:</span>
                                <span class="font-bold text-slate-900 truncate max-w-[160px]">
                                    {{ $consignment->transportService?->transportUnit?->name ?? 'Unassigned' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between pt-1 border-b border-slate-100 pb-2">
                                <span class="text-slate-500">Date Booked:</span>
                                <span class="font-bold text-slate-900">
                                    {{ $consignment->created_at->format('d M Y, H:i') }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between pt-1 border-b border-slate-100 pb-2">
                                <span class="text-slate-500">Total Weight:</span>
                                <span class="font-bold text-slate-900">
                                    {{ number_format((float) $consignment->total_weight_kg, 2) }} kg
                                </span>
                            </div>
                            <div class="flex items-center justify-between pt-1 border-b border-slate-100 pb-2">
                                <span class="text-slate-500">Total ({{ $consignment->billing_currency }}):</span>
                                <span class="font-bold text-slate-900 font-mono">
                                    {{ number_format($consignment->billing_amount, 2) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between pt-1 border-b border-slate-100 pb-2">
                                <span class="text-slate-500">Payment:</span>
                                <span class="font-bold text-slate-900 truncate max-w-[160px]" title="{{ ucfirst($consignment->payment_status ?? '') }}">
                                    {{ $consignment->payment_method_label ?? 'Not recorded' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between pt-1">
                                <span class="text-slate-500">Flags:</span>
                                <div class="flex gap-1">
                                    @if($consignment->has_hazmat) <span class="px-1.5 py-0.5 bg-rose-100 text-rose-700 text-[10px] font-bold rounded" title="Hazmat"><i class="fas fa-biohazard"></i></span> @endif
                                    @if($consignment->has_fragile) <span class="px-1.5 py-0.5 bg-amber-100 text-amber-700 text-[10px] font-bold rounded" title="Fragile"><i class="fas fa-wine-glass"></i></span> @endif
                                    @if($consignment->has_livestock) <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-700 text-[10px] font-bold rounded" title="Livestock"><i class="fas fa-paw"></i></span> @endif
                                    @if(!$consignment->has_hazmat && !$consignment->has_fragile && !$consignment->has_livestock)
                                        <span class="text-slate-400 text-xs">None</span>
                                    @endif
                                </div>
                            </div>
                            @if((float) $consignment->discount_amount > 0)
                                <div class="flex items-center justify-between pt-1 border-b border-slate-100 pb-2">
                                    <span class="text-slate-500">Discount:</span>
                                    <span class="font-bold text-emerald-700 font-mono">−{{ number_format((float) $consignment->discount_amount, 2) }}</span>
                                </div>
                            @endif
                            @if($consignment->receiver_id_number)
                                <div class="flex items-center justify-between pt-1 border-b border-slate-100 pb-2">
                                    <span class="text-slate-500">Receiver ID:</span>
                                    <span class="font-bold text-slate-900">{{ $consignment->receiver_id_type }} {{ $consignment->receiver_id_number }}</span>
                                </div>
                            @endif
                            @if($consignment->special_instructions)
                                <div class="sm:col-span-2 pt-1">
                                    <span class="text-slate-500">Special instructions:</span>
                                    <p class="font-semibold text-amber-800 mt-0.5">{{ $consignment->special_instructions }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Right: 4 High-Impact Action Tiles Side by Side -->
                <div class="lg:col-span-5 grid grid-cols-2 sm:grid-cols-4 gap-3 items-stretch">
                    <!-- Tile 1: next port step (load before departure, hand over on arrival) -->
                    @if($actions['load'])
                        @php
                            $svc = $consignment->transportService;
                            $loadOn = collect([
                                $svc?->transportUnit?->name,
                                $svc?->departure_date ? \Carbon\Carbon::parse($svc->departure_date)->format('d M Y') . ($svc->departure_time ? ' ' . substr($svc->departure_time, 0, 5) : '') : null,
                            ])->filter()->implode(' · ');
                        @endphp
                        <form method="POST" action="{{ route('admin.cargo.bookings.load', $consignment) }}"
                            data-confirm="{{ $loadOn ? "On {$loadOn}." : 'On the vessel for this departure.' }}"
                            data-confirm-title="Mark {{ $consignment->consignment_number }} loaded?"
                            data-confirm-button="Mark loaded" data-confirm-cancel="Not now" data-confirm-icon="fa-dolly">
                            @csrf
                            <button class="flex flex-col items-center justify-center p-3.5 rounded-2xl bg-white hover:bg-brand-50/60 border border-slate-200 hover:border-brand-300 text-slate-700 hover:text-brand-600 font-bold text-xs transition-all shadow-sm hover:shadow group w-full h-full">
                                <div class="w-11 h-11 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center text-lg mb-2 shadow-2xs group-hover:bg-brand-600 group-hover:text-white transition-colors"><i class="fas fa-dolly"></i></div>
                                <span class="text-center leading-tight">Mark<br>Loaded</span>
                            </button>
                        </form>
                    @elseif($actions['handover'])
                        <button type="button" @click="showHandover = true" class="flex flex-col items-center justify-center p-3.5 rounded-2xl bg-white hover:bg-brand-50/60 border border-slate-200 hover:border-brand-300 text-slate-700 hover:text-brand-600 font-bold text-xs transition-all shadow-sm hover:shadow group w-full h-full">
                            <div class="w-11 h-11 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center text-lg mb-2 shadow-2xs group-hover:bg-brand-600 group-hover:text-white transition-colors"><i class="fas fa-hand-holding"></i></div>
                            <span class="text-center leading-tight">Hand<br>Over</span>
                        </button>
                    @else
                        @php
                            $next = match (true) {
                                $consignment->isLeftBehind() => 'Left<br>behind',
                                $consignment->status === 'booked' => 'Loading<br>closed',
                                $consignment->status === 'loaded' => 'Departs with<br>the vessel',
                                $consignment->status === 'in_transit' => 'Arrives with<br>the vessel',
                                $consignment->status === 'collected' => 'Collected',
                                default => 'No port<br>step',
                            };
                        @endphp
                        <div class="flex flex-col items-center justify-center p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-400 font-bold text-xs" title="In transit and arrived follow the journey (Operations › Journeys)">
                            <div class="w-11 h-11 rounded-2xl bg-slate-200 text-slate-400 flex items-center justify-center text-lg mb-2"><i class="fas fa-ship"></i></div>
                            <span class="text-center leading-tight">{!! $next !!}</span>
                        </div>
                    @endif

                    <!-- Tile 2: Print Receipt (available once paid) -->
                    @if(!$consignment->isAwaitingPayment())
                    <a href="{{ route('admin.cargo.bookings.print', $consignment) }}" target="_blank" class="flex flex-col items-center justify-center p-3.5 rounded-2xl bg-white hover:bg-sky-50/60 border border-slate-200 hover:border-sky-300 text-slate-700 hover:text-sky-600 font-bold text-xs transition-all shadow-sm hover:shadow group">
                        <div class="w-11 h-11 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-lg mb-2 shadow-2xs group-hover:bg-sky-600 group-hover:text-white transition-colors">
                            <i class="fas fa-print"></i>
                        </div>
                        <span class="text-center leading-tight">Print<br>Receipt</span>
                    </a>
                    @else
                    <div class="flex flex-col items-center justify-center p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-400 font-bold text-xs cursor-not-allowed" title="Available once the booking is paid">
                        <div class="w-11 h-11 rounded-2xl bg-slate-200 text-slate-400 flex items-center justify-center text-lg mb-2">
                            <i class="fas fa-print"></i>
                        </div>
                        <span class="text-center leading-tight">Print<br>Receipt</span>
                    </div>
                    @endif

                    <!-- Tile 3: Track Shipment -->
                    <button @click="activeTab = 'tracking'; $el.scrollIntoView({ behavior: 'smooth', block: 'center' })" class="flex flex-col items-center justify-center p-3.5 rounded-2xl bg-white hover:bg-indigo-50/60 border border-slate-200 hover:border-indigo-300 text-slate-700 hover:text-indigo-600 font-bold text-xs transition-all shadow-sm hover:shadow group">
                        <div class="w-11 h-11 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg mb-2 shadow-2xs group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                            <i class="fas fa-map-marked-alt"></i>
                        </div>
                        <span class="text-center leading-tight">Track<br>Shipment</span>
                    </button>

                    <!-- Tile 4: Cancel Booking -->
                    @if(!in_array($consignment->status, ['cancelled', 'delivered', 'collected']))
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
                    @endif
                </div>
            </div>

            @if (session()->has('success'))
                <div class="p-4 bg-emerald-950/60 border border-emerald-500/40 text-emerald-300 rounded-2xl flex items-start gap-3 font-semibold text-sm">
                    <i class="fas fa-check-circle mt-0.5 text-emerald-400"></i>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            @if (session()->has('error'))
                <div class="p-4 bg-rose-950/60 border border-rose-500/40 text-rose-300 rounded-2xl flex items-start gap-3 font-semibold text-sm">
                    <i class="fas fa-exclamation-triangle mt-0.5 text-rose-400"></i>
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            @if($consignment->isAwaitingPayment())
                @include('admin.cargo.bookings._payment-panel')
            @endif

            <!-- Profile Workspace Tabs -->
            <div class="border-b border-slate-200 flex gap-2 overflow-x-auto pb-1">
                <button @click="activeTab = 'items'"
                        :class="activeTab === 'items' ? 'border-brand-600 text-brand-600 font-bold bg-white shadow-xs' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold hover:border-slate-300'"
                        class="px-5 py-3 border-b-2 transition-all rounded-t-xl text-sm whitespace-nowrap flex items-center gap-2">
                    <i class="fas fa-box-open"></i> Cargo Items ({{ $consignment->items->count() }})
                </button>
                <button @click="activeTab = 'tracking'"
                        :class="activeTab === 'tracking' ? 'border-brand-600 text-brand-600 font-bold bg-white shadow-xs' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold hover:border-slate-300'"
                        class="px-5 py-3 border-b-2 transition-all rounded-t-xl text-sm whitespace-nowrap flex items-center gap-2">
                    <i class="fas fa-route"></i> Tracking History ({{ $consignment->trackingEvents->count() }})
                </button>
                <button @click="activeTab = 'receipts'"
                        :class="activeTab === 'receipts' ? 'border-brand-600 text-brand-600 font-bold bg-white shadow-xs' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold hover:border-slate-300'"
                        class="px-5 py-3 border-b-2 transition-all rounded-t-xl text-sm whitespace-nowrap flex items-center gap-2">
                    <i class="fas fa-receipt"></i> Receipts & Payments
                </button>
            </div>

            <!-- TAB 1: CARGO ITEMS -->
            <div x-show="activeTab === 'items'" class="space-y-4">
                <h3 class="text-xl font-bold text-slate-800 tracking-tight">
                    Cargo Manifest
                </h3>
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-slate-50 text-slate-600 text-[11px] uppercase font-semibold tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="py-3.5 px-4 w-12 text-center">#</th>
                                    <th class="py-3.5 px-4">QR Token</th>
                                    <th class="py-3.5 px-4">Cargo Type</th>
                                    <th class="py-3.5 px-4">Description</th>
                                    <th class="py-3.5 px-4">Metrics</th>
                                    <th class="py-3.5 px-4 text-center">Status</th>
                                    <th class="py-3.5 px-4 text-right">Fare (TZS)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                                @forelse($consignment->items as $index => $item)
                                    <tr class="hover:bg-slate-50 transition-colors {{ $item->status === 'cancelled' ? 'bg-rose-50/50 text-slate-400' : '' }}">
                                        <td class="py-4 px-4 text-center text-slate-500 font-mono">{{ $index + 1 }}.</td>
                                        <td class="py-4 px-4 font-mono font-bold">{{ $item->qr_code_token ?? 'N/A' }}</td>
                                        <td class="py-4 px-4">{{ $item->cargoType?->name ?? 'Unknown' }}</td>
                                        <td class="py-4 px-4">
                                            {{ (int) $item->quantity > 1 ? $item->quantity . ' × ' : '' }}{{ $item->description ?? 'No description' }}
                                            @if($item->vehicle_reg_number)
                                                <div class="text-slate-500 mt-0.5">Reg <span class="font-mono font-bold text-slate-700">{{ $item->vehicle_reg_number }}</span>{{ $item->driver_name ? ' · Driver ' . $item->driver_name : '' }}{{ $item->driver_phone ? ' (' . $item->driver_phone . ')' : '' }}</div>
                                            @endif
                                            @if((float) $item->declared_value > 0)
                                                <div class="text-slate-500 mt-0.5">Declared value {{ number_format((float) $item->declared_value) }}@if((float) $item->value_charge > 0) · value charge <span class="font-semibold text-slate-700">{{ number_format((float) $item->value_charge) }}</span>@endif</div>
                                            @endif
                                        </td>
                                        <td class="py-4 px-4">
                                            <div>{{ number_format((float) $item->weight_kg, 2) }} kg</div>
                                            <div class="text-slate-500">{{ number_format((float) $item->volume_cbm, 4) }} cbm</div>
                                        </td>
                                        <td class="py-4 px-4 text-center">
                                            @php
                                                $iBadge = $statusColors[$item->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider {{ $iBadge }}">
                                                {{ str_replace('_', ' ', $item->status) }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-right">
                                            <span class="font-mono font-bold">{{ number_format((float) $item->final_fare, 2) }}</span>
                                            {{-- How this line was priced, from its own record (never the live fare) --}}
                                            @if ($item->unit_rate !== null)
                                                <div class="text-[11px] text-slate-500 font-normal">
                                                    @ {{ number_format((float) $item->unit_rate) }} {{ ['per_kg' => '/ kg', 'per_cbm' => '/ cbm', 'minimum' => 'minimum'][$item->pricing_method] ?? 'each' }}
                                                    @if ((float) $item->value_charge_pct > 0) · +{{ rtrim(rtrim(number_format((float) $item->value_charge_pct, 2), '0'), '.') }}% value @endif
                                                </div>
                                            @endif
                                            @if ($item->fare_id)
                                                <a href="{{ route('admin.cargo.types.show', $item->cargo_type_id) }}#history" class="text-[11px] text-brand-700 hover:underline font-normal"
                                                    title="Priced {{ $item->priced_at?->format('d M Y H:i') }}">fare #{{ $item->fare_id }}{{ $item->fare_version ? ' v' . $item->fare_version : '' }}</a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-12 text-center text-slate-400 font-medium">No items found for this booking.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: TRACKING HISTORY -->
            <div x-show="activeTab === 'tracking'" class="space-y-4" style="display: none;">
                @if($consignment->collected_by_name)
                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
                        <h4 class="text-sm font-bold text-slate-800 mb-3"><i class="fas fa-hand-holding text-brand-600 mr-1"></i> Collection</h4>
                        <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div><dt class="text-xs text-slate-500">Collected by</dt><dd class="font-semibold">{{ $consignment->collected_by_name }}</dd></div>
                            <div><dt class="text-xs text-slate-500">ID</dt><dd class="font-semibold">{{ \App\Services\Cargo\CargoHandoverService::ID_TYPES[$consignment->collected_id_type] ?? $consignment->collected_id_type }} · <span class="font-mono">{{ $consignment->collected_id_number }}</span></dd></div>
                            <div><dt class="text-xs text-slate-500">Mobile</dt><dd class="font-semibold font-mono">{{ $consignment->collected_phone }}
                                @if($consignment->collected_phone_verified)<span class="ml-1 text-emerald-700 font-sans text-xs"><i class="fas fa-circle-check"></i> confirmed by code</span>
                                @else<span class="ml-1 text-amber-700 font-sans text-xs"><i class="fas fa-triangle-exclamation"></i> not confirmed</span>@endif</dd></div>
                            <div><dt class="text-xs text-slate-500">Handed over</dt><dd class="font-semibold">{{ $consignment->collected_at?->format('d M Y H:i') }} by {{ $consignment->handedOverBy?->full_name ?? '—' }}</dd></div>
                        </dl>
                        @if($consignment->collection_warnings)
                            <ul class="mt-3 text-xs text-amber-800 list-disc ml-5">@foreach($consignment->collection_warnings as $w)<li>{{ $w }}</li>@endforeach</ul>
                        @endif
                    </div>
                @endif
                <h3 class="text-xl font-bold text-slate-800 tracking-tight">
                    Tracking History
                </h3>
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6">
                    @if($consignment->trackingEvents->isEmpty())
                        <p class="text-center py-8 text-slate-500">No tracking events recorded.</p>
                    @else
                        <div class="space-y-6">
                            @foreach($consignment->trackingEvents as $event)
                                <div class="flex gap-4">
                                    <div class="flex flex-col items-center">
                                        <div class="w-3 h-3 rounded-full bg-brand-500 shadow-[0_0_0_4px_rgba(var(--color-brand-100),1)]"></div>
                                        @if(!$loop->last)
                                            <div class="w-0.5 h-full bg-slate-200 mt-2"></div>
                                        @endif
                                    </div>
                                    <div class="pb-6">
                                        <p class="text-sm font-bold text-slate-900 uppercase tracking-wider">{{ str_replace('_', ' ', $event->to_status ?? $event->event_type) }}</p>
                                        <p class="text-xs font-semibold text-slate-500">{{ $event->created_at?->format('M d, Y H:i:s') }}</p>
                                        @if($event->notes)
                                            <p class="text-sm text-slate-600 mt-1">{{ $event->notes }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- TAB 3: RECEIPTS -->
            <div x-show="activeTab === 'receipts'" class="space-y-4" style="display: none;">
                <h3 class="text-xl font-bold text-slate-800 tracking-tight">
                    Receipts & Payments
                </h3>
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Payment Method</p>
                            <p class="font-bold text-slate-800">{{ $consignment->payment_method_label ?? 'Not recorded' }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Payment Status</p>
                            <p class="font-bold text-slate-800 capitalize">{{ $consignment->payment_status ?? '—' }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Amount</p>
                            <p class="font-bold text-slate-800 font-mono">{{ $consignment->billing_currency }} {{ number_format($consignment->billing_amount, 2) }}</p>
                        </div>
                    </div>
                    @if($consignment->payments->isNotEmpty())
                        @php
                            $attemptColors = [
                                'paid' => 'bg-emerald-100 text-emerald-700', 'failed' => 'bg-rose-100 text-rose-700',
                                'expired' => 'bg-slate-200 text-slate-600', 'cancelled' => 'bg-slate-100 text-slate-500',
                                'refund_due' => 'bg-amber-100 text-amber-800',
                            ];
                        @endphp
                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[10px] font-bold">
                                    <tr>
                                        <th class="py-2.5 px-3">When</th><th class="py-2.5 px-3">Channel</th><th class="py-2.5 px-3">Payer</th>
                                        <th class="py-2.5 px-3">Reference</th><th class="py-2.5 px-3">Status</th><th class="py-2.5 px-3">By</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($consignment->payments as $attempt)
                                        <tr>
                                            <td class="py-2.5 px-3 whitespace-nowrap">{{ $attempt->created_at?->format('d M, H:i') }}</td>
                                            <td class="py-2.5 px-3 font-semibold">{{ $attempt->paymentMethod?->name }} @if($attempt->isSimulated())<span class="text-[9px] text-violet-600 font-black">SIM</span>@endif</td>
                                            <td class="py-2.5 px-3 font-mono">{{ \App\Support\Msisdn::mask($attempt->payer_msisdn) ?? '—' }}</td>
                                            <td class="py-2.5 px-3 font-mono">{{ $attempt->manual_reference ?? $attempt->provider_reference ?? '—' }}</td>
                                            <td class="py-2.5 px-3">
                                                <span class="px-2 py-0.5 rounded-full font-bold {{ $attemptColors[$attempt->status] ?? 'bg-cyan-100 text-cyan-700' }}">{{ str_replace('_', ' ', $attempt->status) }}</span>
                                                @if($attempt->failure_reason)<div class="text-[10px] text-slate-500 mt-0.5">{{ $attempt->failure_reason }}</div>@endif
                                            </td>
                                            <td class="py-2.5 px-3">{{ $attempt->confirmedBy?->full_name ?? $attempt->initiatedBy?->full_name ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    @if($consignment->receipts->isEmpty())
                        <p class="text-center py-8 text-slate-500">No generated receipts found for this booking.</p>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($consignment->receipts as $receipt)
                                <div class="border border-slate-200 rounded-xl p-4 hover:border-brand-300 transition-colors">
                                    <div class="flex justify-between items-start mb-3">
                                        <span class="px-2 py-1 bg-slate-100 text-slate-700 text-xs font-bold rounded">{{ $receipt->receipt_number }}</span>
                                        <span class="text-xs text-slate-500">{{ $receipt->issued_at?->format('M d, Y H:i') }}</span>
                                    </div>
                                    <p class="text-xs text-slate-500">Issued by <span class="font-bold text-slate-700">{{ $receipt->issuedBy?->full_name ?? 'System' }}</span></p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Cancel Modal -->
        <div x-show="showCancelModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="display: none;">
            <div x-show="showCancelModal" x-transition.opacity class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            <div x-show="showCancelModal" x-transition.scale.95 class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-100">
                <form action="{{ route('admin.cargo.bookings.cancel', $consignment) }}" method="POST">
                    @csrf
                    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-rose-50/50">
                        <h3 class="text-lg font-bold text-rose-700 flex items-center gap-2">
                            <i class="fas fa-exclamation-triangle"></i> Cancel Cargo Booking
                        </h3>
                        <button type="button" @click="showCancelModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>
                    <div class="p-6">
                        <p class="text-slate-600 text-sm mb-4">Are you sure you want to cancel booking <strong class="text-slate-900">{{ $consignment->consignment_number }}</strong>? This action cannot be undone and will mark all associated cargo items as cancelled.</p>
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Cancellation Reason</label>
                            <textarea name="reason" rows="3" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-rose-500 focus:ring-1 focus:ring-rose-500 focus:bg-white transition-colors" placeholder="Enter reason for cancellation..." required></textarea>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                        <button type="button" @click="showCancelModal = false" class="px-5 py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl font-semibold text-sm hover:bg-slate-50 transition-colors">Abort</button>
                        <button type="submit" class="px-5 py-2.5 bg-rose-600 text-white rounded-xl font-bold text-sm shadow-sm hover:bg-rose-700 transition-colors flex items-center gap-2">
                            Confirm Cancellation
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Correct status (supervisors, with a reason) -->
        @if($actions['correct'])
            <div x-show="showStatusModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="display: none;">
                <div class="absolute inset-0 bg-slate-900/60" @click="showStatusModal = false"></div>
                <form method="POST" action="{{ route('admin.cargo.bookings.update-status', $consignment) }}" class="relative bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden">
                    @csrf
                    @method('PATCH')
                    <div class="px-6 py-5 border-b border-slate-100">
                        <h3 class="text-lg font-bold text-slate-800"><i class="fas fa-wrench text-brand-600 mr-1"></i> Correct status</h3>
                        <p class="text-xs text-slate-500 mt-1">For fixing mistakes only. Normal steps: load here, depart / arrive on Journeys, hand over on arrival.</p>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2" for="correct-status">Set status to</label>
                            <select id="correct-status" name="status" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm">
                                @foreach(\App\Services\Cargo\CargoOperationsService::CORRECTABLE as $st)
                                    @continue($st === $consignment->status)
                                    <option value="{{ $st }}">{{ \App\Support\CargoUi::STATUS[$st]['label'] ?? $st }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2" for="correct-reason">Reason</label>
                            <textarea id="correct-reason" name="reason" required minlength="5" maxlength="500" rows="3" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm" placeholder="e.g. Marked loaded by mistake"></textarea>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                        <button type="button" @click="showStatusModal = false" class="px-5 py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl font-semibold text-sm">Cancel</button>
                        <button type="submit" class="px-5 py-2.5 bg-brand-600 text-white rounded-xl font-bold text-sm hover:bg-brand-700">Correct status</button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Hand over (collector's ID + mobile confirmed by a code) -->
        @if($actions['handover'])
            @include('admin.cargo.bookings.partials.handover', ['consignment' => $consignment])
        @endif
    </div>
</x-layouts.admin>
