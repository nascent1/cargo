<x-layouts.portal>
    <x-slot:title>FerryOS Express Terminal | High-Speed Check-In</x-slot:title>
    <x-slot:header>FerryOS Express Terminal</x-slot:header>
<div class="min-h-screen bg-slate-950 text-slate-100 p-4 font-sans selection:bg-cyan-500 selection:text-black" x-data="fastBookingTerminal()">
    <!-- Master Navigation & Trip Type Header -->
    <div class="max-w-7xl mx-auto mb-6 bg-slate-900/90 border border-slate-800 rounded-2xl p-3 shadow-xl flex flex-wrap items-center justify-between gap-3 sticky top-20 z-30 backdrop-blur-md">
        
        <!-- Page 1 Step -->
        <div class="flex items-center gap-3">
            <button type="button" @click="if(step === 2) { step = 1; window.scrollTo({top:0, behavior:'smooth'}); }" class="flex items-center gap-2.5 transition-all" :class="step === 1 ? 'opacity-100' : 'opacity-70 hover:opacity-100 cursor-pointer'">
                <span class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-black shadow-md transition-all"
                      :class="step === 1 ? 'bg-cyan-500 text-slate-950 ring-2 ring-cyan-400 ring-offset-2 ring-offset-slate-900' : 'bg-emerald-500 text-slate-950'">
                    <i class="fas fa-check" x-show="step === 2"></i>
                    <span x-show="step === 1">1</span>
                </span>
                <div class="text-left hidden lg:block">
                    <div class="text-[10px] font-bold uppercase tracking-wider" :class="step === 1 ? 'text-cyan-400' : 'text-emerald-400'">Page 1</div>
                    <div class="text-xs font-extrabold text-slate-100">Route & Voyage Selection</div>
                </div>
            </button>
        </div>

        <div class="h-8 w-px bg-slate-800 hidden md:block"></div>

        <!-- Master Trip Type Toggle -->
        <div class="flex items-center gap-1.5 bg-slate-950/90 p-1.5 rounded-xl border border-slate-800 flex-1 justify-center sm:flex-none">
            <button @click="setTripType('one_way')" 
                :class="tripType === 'one_way' ? 'bg-gradient-to-r from-cyan-600 to-blue-600 text-white font-semibold shadow-lg shadow-cyan-500/20' : 'text-slate-400 hover:text-slate-200 font-medium'"
                class="px-4 py-1.5 rounded-lg text-sm transition-all duration-200 flex items-center gap-2">
                <i class="fas fa-arrow-right"></i> One-Way Leg
            </button>
            <button @click="setTripType('round_trip')" 
                :class="tripType === 'round_trip' ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-semibold shadow-lg shadow-emerald-500/20' : 'text-slate-400 hover:text-slate-200 font-medium'"
                class="px-4 py-1.5 rounded-lg text-sm transition-all duration-200 flex items-center gap-2">
                <i class="fas fa-exchange-alt"></i> Go & Return
            </button>
        </div>

        <div class="h-8 w-px bg-slate-800 hidden md:block"></div>

        <!-- Page 2 Step -->
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2.5 transition-all" :class="step === 2 ? 'opacity-100' : 'opacity-40'">
                <div class="text-right hidden lg:block">
                    <div class="text-[10px] font-bold uppercase tracking-wider" :class="step === 2 ? 'text-cyan-400' : 'text-slate-500'">Page 2</div>
                    <div class="text-xs font-extrabold" :class="step === 2 ? 'text-slate-100' : 'text-slate-400'">Passenger Details</div>
                </div>
                <span class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-black shadow-md transition-all"
                      :class="step === 2 ? 'bg-cyan-500 text-slate-950 ring-2 ring-cyan-400 ring-offset-2 ring-offset-slate-900' : 'bg-slate-800 text-slate-400'">
                    2
                </span>
            </div>
        </div>

        <!-- Refresh Button -->
        <div class="pl-3 border-l border-slate-800">
            <button @click="refreshSchedules()" class="w-10 h-10 flex items-center justify-center bg-slate-800 hover:bg-cyan-900/40 text-slate-300 hover:text-cyan-400 rounded-xl border border-slate-700 hover:border-cyan-500/50 transition-all duration-300" title="Refresh Live Inventory">
                <i class="fas fa-sync-alt" :class="loading ? 'animate-spin text-cyan-400' : ''"></i>
            </button>
        </div>
    </div>

    <!-- PAGE 1: ROUTE & VOYAGE SELECTION -->
    <div x-show="step === 1" class="max-w-7xl mx-auto space-y-6 animate-fade-in">
        
        <!-- Round-Trip Display Mode Controller Bar (Side-by-Side vs Tabs) -->
        <div x-show="tripType === 'round_trip'" x-transition 
             class="bg-slate-900/90 border border-slate-800 rounded-2xl p-3.5 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-br from-cyan-500/20 to-emerald-500/20 rounded-xl border border-cyan-500/30 text-cyan-400">
                    <i class="fas fa-columns text-base"></i>
                </div>
                <div>
                    <h3 class="text-xs font-black text-slate-200 uppercase tracking-wider">Round-Trip Leg Display Mode</h3>
                    <p class="text-[11px] text-slate-400">Compare departures side-by-side or focus on one leg using tabs below</p>
                </div>
            </div>
            <div class="flex items-center gap-2 bg-slate-950 p-1.5 rounded-xl border border-slate-800/80 w-full sm:w-auto justify-center">
                <button type="button" @click="pageOneViewMode = 'side_by_side'" 
                        :class="pageOneViewMode === 'side_by_side' ? 'bg-gradient-to-r from-cyan-500 to-blue-600 text-slate-950 font-black shadow-md ring-1 ring-cyan-400/50' : 'text-slate-400 hover:text-slate-200 font-bold'"
                        class="px-3.5 py-1.5 rounded-lg text-xs transition flex items-center gap-2 cursor-pointer">
                    <i class="fas fa-columns"></i> Side-by-Side (2 Columns)
                </button>
                <button type="button" @click="pageOneViewMode = 'outbound_tab'" 
                        :class="pageOneViewMode === 'outbound_tab' ? 'bg-cyan-500 text-slate-950 font-black shadow-md ring-1 ring-cyan-400/50' : 'text-slate-400 hover:text-slate-200 font-bold'"
                        class="px-3.5 py-1.5 rounded-lg text-xs transition flex items-center gap-2 cursor-pointer">
                    <i class="fas fa-arrow-right"></i> Outbound Only
                </button>
                <button type="button" @click="pageOneViewMode = 'return_tab'" 
                        :class="pageOneViewMode === 'return_tab' ? 'bg-emerald-500 text-slate-950 font-black shadow-md ring-1 ring-emerald-400/50' : 'text-slate-400 hover:text-slate-200 font-bold'"
                        class="px-3.5 py-1.5 rounded-lg text-xs transition flex items-center gap-2 cursor-pointer">
                    <i class="fas fa-undo"></i> Return Only
                </button>
            </div>
        </div>

        <div :class="(tripType === 'round_trip' && pageOneViewMode === 'side_by_side') ? 'grid grid-cols-1 lg:grid-cols-2 gap-6 items-start' : 'space-y-6'">
            
            <!-- OUTBOUND LEG PANEL -->
            <div x-show="tripType === 'one_way' || pageOneViewMode === 'side_by_side' || pageOneViewMode === 'outbound_tab'"
                 class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 shadow-xl relative overflow-hidden transition-all duration-300 flex flex-col h-full"
                 :class="selectedOutbound ? 'border-cyan-500/40 ring-1 ring-cyan-500/20' : ''">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4 border-b border-slate-800/80 pb-4">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="px-2.5 py-1 bg-cyan-500/10 border border-cyan-500/30 rounded-lg text-cyan-400 font-bold text-xs uppercase tracking-wider">
                            Outbound Leg
                        </span>
                        <h2 class="text-base font-bold text-slate-200 flex items-center gap-2">
                            <i class="fas fa-route text-slate-400"></i>
                            <select x-model="outboundRouteId" @change="onOutboundRouteChanged()" class="bg-slate-950 border border-slate-700 rounded-lg px-3 py-1 text-sm font-bold text-cyan-400 focus:outline-none focus:border-cyan-500">
                                @foreach($routes as $route)
                                    <option value="{{ $route->id }}">{{ $route->name }}</option>
                                @endforeach
                            </select>
                        </h2>
                    </div>
                </div>

                <!-- Outbound Date Strip -->
                <div class="flex items-center gap-2 overflow-x-auto pb-3 scrollbar-thin scrollbar-thumb-slate-800">
                    <template x-for="day in dateStrip" :key="day.dateStr">
                        <button @click="outboundDate = day.dateStr; fetchAvailability()"
                            :class="outboundDate === day.dateStr ? 'bg-cyan-600/30 border-cyan-500 text-cyan-300 font-bold shadow-md' : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:border-slate-700 hover:text-slate-200 font-medium'"
                            class="px-4 py-2 rounded-xl border text-center min-w-[90px] flex-shrink-0 transition-all duration-150">
                            <div class="text-[10px] uppercase tracking-wider text-slate-400" x-text="day.dayName"></div>
                            <div class="text-sm" x-text="day.shortDate"></div>
                        </button>
                    </template>
                </div>

                <!-- Outbound Schedules Grid -->
                <div class="mt-4 space-y-4">
                    <template x-if="loading">
                        <div class="py-8 text-center text-slate-500 font-medium flex items-center justify-center gap-3">
                            <i class="fas fa-spinner animate-spin text-cyan-400 text-xl"></i> Loading Outbound Inventory...
                        </div>
                    </template>
                    <template x-if="!loading && outboundVoyages.length === 0">
                        <div class="py-8 text-center bg-slate-950/40 rounded-xl border border-slate-800/60 text-slate-400 font-medium">
                            <i class="fas fa-calendar-times text-2xl mb-2 text-slate-600 block"></i>
                            No scheduled departures found for this route on selected date.
                        </div>
                    </template>
                    <template x-for="voyage in outboundVoyages" :key="voyage.voyage_id">
                        <div class="bg-slate-950/70 border rounded-xl p-4 transition-all duration-200"
                             :class="selectedOutbound && selectedOutbound.voyage_id === voyage.voyage_id ? 'border-cyan-500 bg-slate-900/90 shadow-lg shadow-cyan-500/10' : 'border-slate-800 hover:border-slate-700'">
                            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 mb-3">
                                <div>
                                    <span class="text-lg font-black text-cyan-400" x-text="voyage.departure_time"></span>
                                    <span class="text-sm font-bold text-slate-200 ml-2" x-text="voyage.ferry_name"></span>
                                    <span class="text-xs text-slate-400 ml-1.5" x-text="'• ' + voyage.ferry_type"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click.prevent.stop="openPricingModal(voyage, 'Outbound')" type="button"
                                            class="px-2.5 py-1 rounded-lg text-xs font-bold bg-cyan-500/10 hover:bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 hover:border-cyan-500/50 transition flex items-center gap-1.5 cursor-pointer shadow-sm relative z-10">
                                        <i class="fas fa-tags text-cyan-400"></i> Fares & Promos
                                    </button>
                                    <span x-show="voyage.has_layout" class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                        <i class="fas fa-th mr-1"></i> Reserved Seat Map
                                    </span>
                                    <span x-show="!voyage.has_layout" class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                        <i class="fas fa-users mr-1"></i> Open Seating Pool
                                    </span>
                                </div>
                            </div>

                            <!-- Horizontal Class Tiles -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3.5 mt-2">
                                <template x-for="cls in voyage.classes" :key="cls.class_id">
                                    <button @click="selectOutboundClass(voyage, cls)"
                                        :disabled="cls.available_seats <= 0"
                                        :class="{
                                            'bg-gradient-to-br from-cyan-950/80 to-slate-900 border-cyan-400 ring-2 ring-cyan-500/40 shadow-md shadow-cyan-500/20': selectedOutbound && selectedOutbound.voyage_id === voyage.voyage_id && selectedOutbound.class_id === cls.class_id,
                                            'bg-slate-900/60 border-slate-800 hover:border-slate-600 hover:bg-slate-800/50': !(selectedOutbound && selectedOutbound.voyage_id === voyage.voyage_id && selectedOutbound.class_id === cls.class_id) && cls.available_seats > 0,
                                            'bg-slate-950/90 border-slate-900 opacity-40 cursor-not-allowed': cls.available_seats <= 0
                                        }"
                                        class="p-3.5 rounded-xl border text-left flex flex-col justify-between relative transition-all duration-150">
                                        <div>
                                            <div class="text-xs font-bold text-slate-200 truncate" x-text="cls.class_name"></div>
                                            <!-- Ring Meter Indicator -->
                                            <div class="flex items-center gap-1.5 mt-1.5">
                                                <div class="w-2 h-2 rounded-full" :class="cls.available_seats > 10 ? 'bg-emerald-400 animate-pulse' : (cls.available_seats > 0 ? 'bg-amber-400' : 'bg-rose-500')"></div>
                                                <span class="text-xs font-medium text-slate-400" x-text="cls.available_seats + ' Left'"></span>
                                            </div>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- INBOUND / RETURN LEG PANEL (Independent Selection) -->
            <div x-show="tripType === 'round_trip' && (pageOneViewMode === 'side_by_side' || pageOneViewMode === 'return_tab')" x-transition 
                 class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 shadow-xl relative overflow-hidden transition-all duration-300 flex flex-col h-full"
                 :class="selectedReturn ? 'border-emerald-500/40 ring-1 ring-emerald-500/20' : ''">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4 border-b border-slate-800/80 pb-4">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="px-2.5 py-1 bg-emerald-500/10 border border-emerald-500/30 rounded-lg text-emerald-400 font-bold text-xs uppercase tracking-wider">
                            Return Leg
                        </span>
                        <h2 class="text-base font-bold text-slate-200 flex items-center gap-2">
                            <i class="fas fa-undo text-slate-400"></i>
                            <select x-model="returnRouteId" @change="fetchAvailability()" class="bg-slate-950 border border-slate-700 rounded-lg px-3 py-1 text-sm font-bold text-emerald-400 focus:outline-none focus:border-emerald-500">
                                @foreach($routes as $route)
                                    <option value="{{ $route->id }}">{{ $route->name }}</option>
                                @endforeach
                            </select>
                        </h2>
                    </div>
                </div>

                <!-- Return Date Strip -->
                <div class="flex items-center gap-2 overflow-x-auto pb-3 scrollbar-thin scrollbar-thumb-slate-800">
                    <template x-for="day in dateStrip" :key="day.dateStr">
                        <button @click="returnDate = day.dateStr; fetchAvailability()"
                            :class="returnDate === day.dateStr ? 'bg-emerald-600/30 border-emerald-500 text-emerald-300 font-bold shadow-md' : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:border-slate-700 hover:text-slate-200 font-medium'"
                            class="px-4 py-2 rounded-xl border text-center min-w-[90px] flex-shrink-0 transition-all duration-150">
                            <div class="text-[10px] uppercase tracking-wider text-slate-400" x-text="day.dayName"></div>
                            <div class="text-sm" x-text="day.shortDate"></div>
                        </button>
                    </template>
                </div>

                <!-- Return Schedules Grid -->
                <div class="mt-4 space-y-4">
                    <template x-if="loading">
                        <div class="py-8 text-center text-slate-500 font-medium flex items-center justify-center gap-3">
                            <i class="fas fa-spinner animate-spin text-emerald-400 text-xl"></i> Loading Return Inventory...
                        </div>
                    </template>
                    <template x-if="!loading && returnVoyages.length === 0">
                        <div class="py-8 text-center bg-slate-950/40 rounded-xl border border-slate-800/60 text-slate-400 font-medium">
                            <i class="fas fa-calendar-times text-2xl mb-2 text-slate-600 block"></i>
                            No scheduled departures found for the return route on selected date.
                        </div>
                    </template>
                    <template x-for="voyage in returnVoyages" :key="voyage.voyage_id">
                        <div class="bg-slate-950/70 border rounded-xl p-4 transition-all duration-200"
                             :class="selectedReturn && selectedReturn.voyage_id === voyage.voyage_id ? 'border-emerald-500 bg-slate-900/90 shadow-lg shadow-emerald-500/10' : 'border-slate-800 hover:border-slate-700'">
                            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 mb-3">
                                <div>
                                    <span class="text-lg font-black text-emerald-400" x-text="voyage.departure_time"></span>
                                    <span class="text-sm font-bold text-slate-200 ml-2" x-text="voyage.ferry_name"></span>
                                    <span class="text-xs text-slate-400 ml-1.5" x-text="'• ' + voyage.ferry_type"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click.prevent.stop="openPricingModal(voyage, 'Return')" type="button"
                                            class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 hover:border-emerald-500/50 transition flex items-center gap-1.5 cursor-pointer shadow-sm relative z-10">
                                        <i class="fas fa-tags text-emerald-400"></i> Fares & Promos
                                    </button>
                                    <span x-show="voyage.has_layout" class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                        <i class="fas fa-th mr-1"></i> Reserved Seat Map
                                    </span>
                                    <span x-show="!voyage.has_layout" class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                        <i class="fas fa-users mr-1"></i> Open Seating Pool
                                    </span>
                                </div>
                            </div>

                            <!-- Horizontal Class Tiles -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3.5 mt-2">
                                <template x-for="cls in voyage.classes" :key="cls.class_id">
                                    <button @click="selectReturnClass(voyage, cls)"
                                        :disabled="cls.available_seats <= 0"
                                        :class="{
                                            'bg-gradient-to-br from-emerald-950/80 to-slate-900 border-emerald-400 ring-2 ring-emerald-500/40 shadow-md shadow-emerald-500/20': selectedReturn && selectedReturn.voyage_id === voyage.voyage_id && selectedReturn.class_id === cls.class_id,
                                            'bg-slate-900/60 border-slate-800 hover:border-slate-600 hover:bg-slate-800/50': !(selectedReturn && selectedReturn.voyage_id === voyage.voyage_id && selectedReturn.class_id === cls.class_id) && cls.available_seats > 0,
                                            'bg-slate-950/90 border-slate-900 opacity-40 cursor-not-allowed': cls.available_seats <= 0
                                        }"
                                        class="p-3.5 rounded-xl border text-left flex flex-col justify-between relative transition-all duration-150">
                                        <div>
                                            <div class="text-xs font-bold text-slate-200 truncate" x-text="cls.class_name"></div>
                                            <div class="flex items-center gap-1.5 mt-1.5">
                                                <div class="w-2 h-2 rounded-full" :class="cls.available_seats > 10 ? 'bg-emerald-400 animate-pulse' : (cls.available_seats > 0 ? 'bg-amber-400' : 'bg-rose-500')"></div>
                                                <span class="text-xs font-medium text-slate-400" x-text="cls.available_seats + ' Left'"></span>
                                            </div>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div> <!-- End of Side-by-Side / Stacked Legs Container -->

        <!-- Floating Ready-for-Checkin Action Bar -->
        <div x-show="isReadyToHold()" x-transition
             class="sticky bottom-4 z-30 bg-slate-900/90 backdrop-blur-xl border border-cyan-500/40 rounded-2xl p-4 shadow-2xl flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-4">
                    <div class="p-2.5 bg-cyan-500/20 rounded-xl text-cyan-400">
                        <i class="fas fa-check-circle text-xl"></i>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-slate-200" x-text="getSelectionSummaryText()"></div>
                        <div class="text-xs text-slate-400">Click below to lock seat inventory and proceed to Passenger & Seat Map Deck</div>
                    </div>
                </div>
                <button @click="acquireTemporaryHolds()" 
                    :disabled="holding"
                    class="w-full sm:w-auto px-6 py-3 rounded-xl font-bold bg-gradient-to-r from-cyan-500 via-emerald-500 to-teal-400 hover:from-cyan-400 hover:to-emerald-400 text-slate-950 shadow-lg shadow-cyan-500/20 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-bolt" :class="holding ? 'animate-spin' : ''"></i>
                    <span x-text="holding ? 'Locking Inventory...' : 'Next: Passenger Details & Seat Map ➔'"></span>
                </button>
            </div>
        </div>
        <!-- PAGE 2: PASSENGER DETAILS, SEAT ALLOCATION & PAYMENT -->
        <div x-show="step === 2" class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6 items-start animate-fade-in" style="display: none;">
            
            <!-- Left Column: Stacked Passenger Deck & Seat Allocation Cards (Cols 1 to 8) -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Top Summary Recap Bar -->
                <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-xl flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">Active Hold</span>
                            <h3 class="text-base font-extrabold text-slate-100 flex items-center gap-2">
                                <i class="fas fa-id-card text-cyan-400"></i> Passenger Manifest & Deck Allocation
                            </h3>
                        </div>
                        <p class="text-xs text-slate-400" x-text="getSelectionSummaryText()"></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span x-show="adjustingHolds" x-transition class="px-3 py-1.5 rounded-xl bg-cyan-500/20 border border-cyan-500/40 text-cyan-300 text-xs font-bold animate-pulse flex items-center gap-2">
                            <i class="fas fa-spinner animate-spin text-cyan-400"></i>
                            <span>Syncing Inventory Locks...</span>
                        </span>
                        <span class="px-3 py-1.5 rounded-xl bg-amber-500/20 border border-amber-500/40 text-amber-300 text-xs font-mono font-bold animate-pulse flex items-center gap-2">
                            <i class="fas fa-clock text-amber-400"></i>
                            <span>Hold Expires: <strong x-text="countdownText"></strong></span>
                        </span>
                    </div>
                </div>

                <!-- Stepper / Dual Adult & Child Passenger Count Controls -->
                <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4.5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 shadow-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500/20 to-blue-500/20 border border-cyan-500/30 flex items-center justify-center text-cyan-400 font-bold text-base shadow-md">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <span class="text-sm font-black text-slate-100 block">Manifest Size & Categories</span>
                            <span class="text-xs text-slate-400">Add or remove passengers right here — inventory locks update automatically (<span class="text-cyan-400 font-mono font-bold" x-text="passengers.length + ' Pax Total'"></span>)</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                        <!-- Adults Counter Box -->
                        <div class="flex items-center justify-between gap-3 bg-slate-950 px-3.5 py-2 rounded-xl border border-slate-800/90 shadow-md flex-1 sm:flex-initial">
                            <span class="text-xs font-bold text-slate-300 flex items-center gap-2">
                                <i class="fas fa-user text-cyan-400"></i> Adults
                            </span>
                            <div class="flex items-center gap-1.5">
                                <button type="button" @click="decrementAdults()" :disabled="adjustingHolds || (adultsCount <= 1 && childrenCount === 0) || adultsCount <= 0" 
                                        class="w-7 h-7 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 font-black flex items-center justify-center transition disabled:opacity-30">
                                    -
                                </button>
                                <input type="number" x-model.number="adultsCount" @change="onAdultsCountChanged()" :disabled="adjustingHolds" min="0" max="50"
                                       class="w-12 py-1 bg-slate-900 border border-slate-700 rounded-lg text-sm font-extrabold text-cyan-400 font-mono text-center focus:outline-none focus:border-cyan-500 shadow-inner">
                                <button type="button" @click="incrementAdults()" :disabled="adjustingHolds || passengers.length >= 50" 
                                        class="w-7 h-7 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-black flex items-center justify-center transition disabled:opacity-30 shadow-md shadow-cyan-500/20">
                                    +
                                </button>
                            </div>
                        </div>

                        <!-- Children Counter Box -->
                        <div class="flex items-center justify-between gap-3 bg-slate-950 px-3.5 py-2 rounded-xl border border-slate-800/90 shadow-md flex-1 sm:flex-initial">
                            <span class="text-xs font-bold text-slate-300 flex items-center gap-2">
                                <i class="fas fa-child text-amber-400"></i> Children
                            </span>
                            <div class="flex items-center gap-1.5">
                                <button type="button" @click="decrementChildren()" :disabled="adjustingHolds || childrenCount <= 0 || (childrenCount <= 1 && adultsCount === 0)" 
                                        class="w-7 h-7 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 font-black flex items-center justify-center transition disabled:opacity-30">
                                    -
                                </button>
                                <input type="number" x-model.number="childrenCount" @change="onChildrenCountChanged()" :disabled="adjustingHolds" min="0" max="50"
                                       class="w-12 py-1 bg-slate-900 border border-slate-700 rounded-lg text-sm font-extrabold text-amber-300 font-mono text-center focus:outline-none focus:border-amber-500 shadow-inner">
                                <button type="button" @click="incrementChildren()" :disabled="adjustingHolds || passengers.length >= 50" 
                                        class="w-7 h-7 rounded-lg bg-amber-600 hover:bg-amber-500 text-white font-black flex items-center justify-center transition disabled:opacity-30 shadow-md shadow-amber-500/20">
                                    +
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stacked Passenger Profile Cards with Interactive Seat Badges -->
                <div class="space-y-4">
                    <template x-for="(pax, idx) in passengers" :key="idx">
                        <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 shadow-xl relative space-y-4 transition-all hover:border-slate-700">
                            <!-- Card Header -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800/80 pb-3">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 text-slate-950 font-black text-sm flex items-center justify-center shadow-md" x-text="idx + 1"></span>
                                    <div>
                                        <span class="text-sm font-extrabold text-slate-100 block" x-text="pax.name || ('Passenger #' + (idx + 1))"></span>
                                        <span class="text-[11px] text-slate-400" x-text="pax.id_number ? ('ID/Passport: ' + pax.id_number) : 'ID/Passport pending...'"></span>
                                    </div>
                                </div>

                                <!-- Resident vs Foreigner Toggle Pill -->
                                <div class="flex items-center gap-1 bg-slate-950 p-1 rounded-xl border border-slate-800">
                                    <button type="button" @click="pax.nationality_type = 1" 
                                            :class="pax.nationality_type === 1 ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/30' : 'text-slate-400 hover:text-slate-200'"
                                            class="px-3 py-1 rounded-lg text-xs transition flex items-center gap-1.5">
                                        <i class="fas fa-flag text-emerald-300"></i> Resident (TZS)
                                    </button>
                                    <button type="button" @click="pax.nationality_type = 2" 
                                            :class="pax.nationality_type === 2 ? 'bg-amber-600 text-white font-bold shadow-md shadow-amber-600/30' : 'text-slate-400 hover:text-slate-200'"
                                            class="px-3 py-1 rounded-lg text-xs transition flex items-center gap-1.5">
                                        <i class="fas fa-globe text-amber-200"></i> International (USD)
                                    </button>
                                </div>
                            </div>

                            <!-- Profile Inputs Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-center">
                                <div class="sm:col-span-5">
                                    <label class="text-[11px] text-slate-400 font-bold block mb-1.5">Full Name <span class="text-rose-400">*</span></label>
                                    <input type="text" x-model="pax.name" placeholder="e.g. Amani Mushi" 
                                           class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-slate-100 focus:outline-none focus:border-cyan-500 font-medium shadow-inner">
                                </div>
                                <div class="sm:col-span-4">
                                    <label class="text-[11px] text-slate-400 font-bold block mb-1.5">ID / Passport #</label>
                                    <input type="text" x-model="pax.id_number" placeholder="Optional ID/Passport" 
                                           class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-slate-100 focus:outline-none focus:border-cyan-500 font-medium font-mono shadow-inner">
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="text-[11px] text-slate-400 font-bold block mb-1.5">Fare Category</label>
                                    <div class="w-full bg-slate-950/80 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs font-bold flex items-center gap-2">
                                        <template x-if="pax.passenger_type === 'adult'">
                                            <span class="text-cyan-400 flex items-center gap-1.5"><i class="fas fa-user"></i> Adult (Standard)</span>
                                        </template>
                                        <template x-if="pax.passenger_type === 'child'">
                                            <span class="text-amber-300 flex items-center gap-1.5"><i class="fas fa-child"></i> Child (Discounted)</span>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Dual Seat Allocation Deck Pod -->
                            <div class="pt-3 border-t border-slate-800/80 flex flex-wrap items-center justify-between gap-3 bg-slate-950/80 p-3.5 rounded-xl border border-slate-800/60">
                                <div class="flex items-center gap-3">
                                    <span class="text-xs font-bold text-slate-400 flex items-center gap-1.5">
                                        <i class="fas fa-ship text-cyan-400"></i> Outbound Seat:
                                    </span>
                                    <button type="button" @click="openSeatMapDrawer(idx, 'outbound')" class="px-3.5 py-2 rounded-xl font-mono text-xs font-bold border transition flex items-center gap-2 shadow-inner"
                                            :class="pax.outbound_seat ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/40 shadow-sm ring-1 ring-emerald-400/20' : 'bg-slate-900 text-cyan-400 hover:bg-slate-800 border-slate-700'">
                                        <i class="fas fa-chair text-sm"></i>
                                        <span x-text="pax.outbound_seat ? (pax.outbound_seat + ' (Assigned)') : 'Pick Outbound Seat ➔'"></span>
                                    </button>
                                </div>

                                <template x-if="tripType === 'round_trip' && selectedReturn">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-bold text-slate-400 flex items-center gap-1.5">
                                            <i class="fas fa-undo text-amber-400"></i> Return Seat:
                                        </span>
                                        <button type="button" @click="openSeatMapDrawer(idx, 'return')" class="px-3.5 py-2 rounded-xl font-mono text-xs font-bold border transition flex items-center gap-2 shadow-inner"
                                                :class="pax.return_seat ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/40 shadow-sm ring-1 ring-emerald-400/20' : 'bg-slate-900 text-amber-400 hover:bg-slate-800 border-slate-700'">
                                            <i class="fas fa-chair text-sm"></i>
                                            <span x-text="pax.return_seat ? (pax.return_seat + ' (Assigned)') : 'Pick Return Seat ➔'"></span>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Right Column: Express Financial Breakdown & Unified Checkout (Cols 9 to 12) -->
            <div class="lg:col-span-4 space-y-6 sticky top-24">
                <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-5">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h3 class="text-sm font-bold text-slate-200 uppercase tracking-wider flex items-center gap-2">
                            <i class="fas fa-receipt text-cyan-400"></i> Financial Breakdown
                        </h3>
                        <span class="px-2.5 py-1 rounded-full bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 text-xs font-bold font-mono">Multi-Currency</span>
                    </div>

                    <!-- Category Subtotals -->
                    <div class="space-y-3 bg-slate-950 p-4 rounded-2xl border border-slate-800/80 text-xs">
                        <div class="flex justify-between items-center text-slate-300">
                            <span class="font-medium flex items-center gap-1.5"><i class="fas fa-flag text-emerald-400"></i> Resident Subtotal (<span x-text="countByNationality(1)"></span> Pax):</span>
                            <span class="text-emerald-400 font-extrabold font-mono text-sm" x-text="numberFormat(calculateSubtotalTzs()) + ' TZS'"></span>
                        </div>
                        <div class="flex justify-between items-center text-slate-300">
                            <span class="font-medium flex items-center gap-1.5"><i class="fas fa-globe text-amber-400"></i> International Subtotal (<span x-text="countByNationality(2)"></span> Pax):</span>
                            <span class="text-amber-300 font-extrabold font-mono text-sm" x-text="'$' + calculateSubtotalUsd().toFixed(2) + ' USD'"></span>
                        </div>
                        <div class="pt-3 border-t border-slate-800 flex justify-between items-baseline font-bold text-sm">
                            <span class="text-slate-100">Total Due Today:</span>
                            <div class="text-right font-mono">
                                <div class="text-emerald-400 text-base font-black" x-text="numberFormat(calculateSubtotalTzs()) + ' TZS'"></div>
                                <div class="text-amber-300 text-base font-black" x-show="calculateSubtotalUsd() > 0" x-text="'+ $' + calculateSubtotalUsd().toFixed(2) + ' USD'"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Channel -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-300 block">Select Payment Channel</label>
                        <select x-model="paymentChannel" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-slate-100 focus:outline-none focus:border-cyan-500 font-bold">
                            <option value="cash">Cash (Counter Check-In Desk)</option>
                            <option value="mpesa">M-Pesa Express (Mobile Money)</option>
                            <option value="tigopesa">TigoPesa / Airtel Money</option>
                            <option value="card">Visa / Mastercard / POS Terminal</option>
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-3 pt-2">
                        <button type="button" @click="submitBooking()" :disabled="submitting || submitted"
                                class="w-full py-4 rounded-2xl font-black text-sm bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 hover:from-emerald-400 hover:to-cyan-400 text-slate-950 shadow-xl shadow-emerald-500/20 transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-bolt text-base" :class="submitting ? 'animate-spin' : ''"></i>
                            <span x-text="submitting ? 'Issuing Unified Tickets...' : (submitted ? 'Booking Confirmed! Opening Profile...' : '⚡ Confirm & Issue Tickets (80mm Thermal)')"></span>
                        </button>
                        <button type="button" @click="cancelHolds(); step = 1; window.scrollTo({top:0, behavior:'smooth'});" :disabled="submitting"
                                class="w-full py-3 rounded-xl text-xs font-bold text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 transition flex items-center justify-center gap-2">
                            <i class="fas fa-arrow-left"></i> Back to Voyage & Class Selection
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- INTERACTIVE FERRY SEAT MAP DRAWER -->
        <div x-show="showSeatMapDrawer" class="fixed inset-0 z-50 overflow-hidden bg-slate-950/80 backdrop-blur-sm flex justify-end" style="display: none;" x-cloak>
            <div @click.away="showSeatMapDrawer = false" class="bg-slate-900 border-l border-slate-800 max-w-lg w-full h-full flex flex-col justify-between p-6 shadow-2xl animate-slide-left overflow-y-auto">
                <!-- Drawer Header -->
                <div>
                    <div class="flex items-center justify-between border-b border-slate-800 pb-4 mb-4">
                        <div>
                            <span class="px-2.5 py-1 rounded text-[10px] font-bold uppercase tracking-wider bg-cyan-500/20 text-cyan-400 border border-cyan-500/30"
                                  x-text="activeDeckType === 'outbound' ? 'Outbound Deck Seat Map' : 'Return Deck Seat Map'"></span>
                            <h3 class="text-lg font-bold text-slate-100 mt-1.5 flex items-center gap-2 font-mono">
                                <i class="fas fa-ship text-cyan-400"></i>
                                <span x-text="activeDeckType === 'outbound' ? (selectedOutbound?.ferry_name + ' (' + selectedOutbound?.class_name + ')') : (selectedReturn?.ferry_name + ' (' + selectedReturn?.class_name + ')')"></span>
                            </h3>
                        </div>
                        <button type="button" @click="showSeatMapDrawer = false" class="text-slate-400 hover:text-white p-2"><i class="fas fa-times text-lg"></i></button>
                    </div>

                    <!-- Legend -->
                    <div class="grid grid-cols-3 gap-2 bg-slate-950 p-3 rounded-xl border border-slate-800 text-[11px] font-semibold text-slate-300 mb-6">
                        <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-emerald-500"></span> Available</div>
                        <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-500"></span> Selected</div>
                        <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-slate-700"></span> Occupied / Locked</div>
                    </div>

                    <div class="p-3 bg-cyan-500/10 border border-cyan-500/30 rounded-xl mb-4 text-xs text-cyan-300 font-bold flex items-center justify-between">
                        <span>Allocating Seat For: <strong class="text-white underline font-mono" x-text="passengers[activeDeckPaxIndex]?.name || ('Passenger #' + (activeDeckPaxIndex + 1))"></strong></span>
                        <span class="text-[10px] uppercase tracking-wider bg-slate-900 px-2 py-0.5 rounded text-cyan-400 font-mono" x-text="passengers[activeDeckPaxIndex]?.passenger_type"></span>
                    </div>

                    <!-- Interactive Graphical Seat Grid -->
                    <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4 max-h-[460px] overflow-y-auto grid grid-cols-4 sm:grid-cols-6 gap-2.5">
                        <template x-for="seat in currentSeatMapList" :key="seat.id">
                            <button type="button" @click="assignSeatToPax(seat.id)"
                                    :disabled="seat.status === 'booked'"
                                    :class="seat.status === 'booked' ? 'bg-slate-800 text-slate-600 border-slate-800/80 cursor-not-allowed opacity-40' : (isSeatSelectedByAnyPax(seat.id) ? 'bg-amber-500 text-slate-950 font-black border-amber-400 ring-2 ring-amber-400/40 shadow-lg' : 'bg-slate-900 hover:bg-emerald-500/20 text-emerald-400 hover:text-emerald-300 border-slate-700 hover:border-emerald-500/50 font-bold cursor-pointer')"
                                    class="p-3 rounded-xl border text-center font-mono text-xs transition-all flex flex-col items-center justify-center gap-1 shadow-xs">
                                <i class="fas fa-chair text-sm" :class="isSeatSelectedByAnyPax(seat.id) ? 'text-slate-950 animate-bounce' : ''"></i>
                                <span x-text="seat.id"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-800 flex gap-3 mt-4">
                    <button type="button" @click="assignSeatToPax('OPEN'); showSeatMapDrawer = false;" class="w-1/3 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold rounded-xl text-xs transition">
                        Open Pool Seat
                    </button>
                    <button type="button" @click="showSeatMapDrawer = false" class="w-2/3 py-3 bg-cyan-600 hover:bg-cyan-500 text-white font-bold rounded-xl text-xs transition shadow-md shadow-cyan-500/20">
                        Done / Close Drawer
                    </button>
                </div>
            </div>
        </div>

    <!-- Thermal Print Modal / Receipt Launcher -->
    <div x-show="showReceiptModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4" x-transition style="display: none;" x-cloak>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 max-w-lg w-full space-y-4 text-center relative shadow-2xl">
            <div class="w-16 h-16 rounded-full bg-emerald-500/20 border border-emerald-500/40 mx-auto flex items-center justify-center text-emerald-400 text-3xl">
                <i class="fas fa-check"></i>
            </div>
            <h3 class="text-xl font-black text-slate-100">Tickets Issued Successfully!</h3>
            <p class="text-xs text-slate-400">PNR Reference: <span class="text-cyan-400 font-bold font-mono text-sm" x-text="issuedPnr"></span></p>

            <div class="bg-slate-950 rounded-xl p-4 border border-slate-800/80 text-left space-y-1.5 text-xs">
                <div class="flex justify-between text-slate-400 font-medium">
                    <span>Total TZS Collected:</span>
                    <span class="text-emerald-400 font-bold" x-text="issuedTzs + ' TZS'"></span>
                </div>
                <div class="flex justify-between text-slate-400 font-medium">
                    <span>Total USD Collected:</span>
                    <span class="text-amber-300 font-bold" x-text="'$' + issuedUsd + ' USD'"></span>
                </div>
            </div>

            <div class="space-y-3 pt-4">
                <button @click="if(issuedBookingId) window.location.href = '/portal/bookings/' + issuedBookingId;" 
                        class="w-full py-3.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 font-extrabold text-sm text-slate-950 shadow-lg transition flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fas fa-external-link-alt"></i> Open Booking Profile & Dashboard ➔
                </button>
                <div class="flex gap-3">
                    <button @click="printReceipt()" class="flex-1 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 font-bold text-xs text-cyan-400 border border-slate-700 transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fas fa-print"></i> Print Thermal Receipt (80mm)
                    </button>
                    <button @click="showReceiptModal = false; resetDesk()" class="px-4 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs border border-slate-700 transition cursor-pointer">
                        New Booking
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pricing & Promotions Modal -->
    <div x-show="showPricingModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4" x-transition style="display: none;" x-cloak>
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-2xl w-full space-y-5 relative shadow-2xl overflow-hidden max-h-[90vh] flex flex-col text-left">
            <!-- Header -->
            <div class="flex justify-between items-center border-b border-slate-800 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500/20 to-blue-500/20 border border-cyan-500/40 flex items-center justify-center text-cyan-400 text-lg shadow-inner">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-100 flex items-center gap-2">
                            <span>Fare Schedule & Active Promotions</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 font-mono" x-text="activePricingLeg"></span>
                        </h3>
                        <p class="text-xs text-slate-400" x-text="activePricingVoyage ? (activePricingVoyage.ferry_name + ' (' + activePricingVoyage.departure_time + ')') : ''"></p>
                    </div>
                </div>
                <button @click="showPricingModal = false" class="text-slate-400 hover:text-white w-8 h-8 rounded-lg bg-slate-800/80 hover:bg-slate-800 flex items-center justify-center transition cursor-pointer">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Fares Table -->
            <div class="overflow-y-auto pr-1 space-y-4 flex-1">
                <div class="bg-slate-950 border border-slate-800/80 rounded-2xl overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-900/90 border-b border-slate-800 text-[11px] uppercase tracking-wider text-slate-400 font-bold font-mono">
                                <th class="py-3 px-4">Class & Deck</th>
                                <th class="py-3 px-4 text-right">Resident (Adult / Child)</th>
                                <th class="py-3 px-4 text-right">Foreigner (Adult / Child)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-xs">
                            <template x-for="cls in (activePricingVoyage ? activePricingVoyage.classes : [])" :key="cls.class_id">
                                <tr class="hover:bg-slate-900/40 transition">
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-100" x-text="cls.class_name"></div>
                                        <div class="text-[10px] text-slate-400 font-mono" x-text="cls.available_seats + ' seats available'"></div>
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono">
                                        <div class="text-emerald-400 font-extrabold" x-text="numberFormat(cls.tzs_price) + ' TZS'"></div>
                                        <div class="text-[11px] text-emerald-500/80" x-text="numberFormat(cls.child_tzs_price || Math.round(Number(cls.tzs_price)*0.75)) + ' TZS (Child)'"></div>
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono">
                                        <div class="text-amber-300 font-extrabold" x-text="'$' + cls.usd_price + ' USD'"></div>
                                        <div class="text-[11px] text-amber-400/80" x-text="'$' + (cls.child_usd_price || Math.round(Number(cls.usd_price)*0.75)) + ' USD (Child)'"></div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Promotions & Discounts Grid -->
                <div class="space-y-2">
                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block font-mono">⚡ Active Promotions & Terminal Policies</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div class="bg-gradient-to-br from-emerald-950/30 to-slate-950 p-3 rounded-xl border border-emerald-500/20 flex items-start gap-2.5">
                            <i class="fas fa-baby text-emerald-400 mt-0.5 text-sm"></i>
                            <div>
                                <div class="font-bold text-emerald-300 text-xs">Infants & Lap Children (0-2 yrs)</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">100% Free across all classes when accompanied by a paying adult ticket.</div>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-cyan-950/30 to-slate-950 p-3 rounded-xl border border-cyan-500/20 flex items-start gap-2.5">
                            <i class="fas fa-child text-cyan-400 mt-0.5 text-sm"></i>
                            <div>
                                <div class="font-bold text-cyan-300 text-xs">Child Discount (3-11 yrs)</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">Automatic ~25% discount off adult base fare applied during check-in.</div>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-purple-950/30 to-slate-950 p-3 rounded-xl border border-purple-500/20 flex items-start gap-2.5">
                            <i class="fas fa-users text-purple-400 mt-0.5 text-sm"></i>
                            <div>
                                <div class="font-bold text-purple-300 text-xs">Group & Corporate Rates</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">Rebates & vouchers available for tour operators (>10 pax). Contact Supervisor.</div>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-amber-950/30 to-slate-950 p-3 rounded-xl border border-amber-500/20 flex items-start gap-2.5">
                            <i class="fas fa-suitcase text-amber-400 mt-0.5 text-sm"></i>
                            <div>
                                <div class="font-bold text-amber-300 text-xs">Luggage & Cargo Policy</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">25kg complimentary baggage allowance included with every VIP/Business ticket.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="border-t border-slate-800 pt-3 flex justify-end">
                <button @click="showPricingModal = false" class="px-6 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs border border-slate-700 transition cursor-pointer">
                    Close Schedule
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function fastBookingTerminal() {
    return {
        step: 1,
        pageOneViewMode: 'side_by_side',
        tripType: 'one_way',
        outboundRouteId: {{ $routes->first()->id ?? 1 }},
        returnRouteId: {{ $routes->count() > 1 ? $routes->get(1)->id : ($routes->first()->id ?? 1) }},
        outboundDate: '{{ now()->format("Y-m-d") }}',
        returnDate: '{{ now()->addDays(2)->format("Y-m-d") }}',
        dateStrip: [],
        outboundVoyages: [],
        returnVoyages: [],
        selectedOutbound: null,
        selectedReturn: null,
        lockIds: [],
        countdownSeconds: 300,
        countdownTimer: null,
        countdownText: '05:00',
        adultsCount: 1,
        childrenCount: 0,
        passengers: [
            { name: '', id_number: '', nationality_type: 1, passenger_type: 'adult', outbound_seat: null, return_seat: null }
        ],
        passengerTypes: @json($passengerTypes),
        loading: false,
        holding: false,
        adjustingHolds: false,
        submitting: false,
        submitted: false,
        paymentChannel: 'cash',
        showReceiptModal: false,
        showPricingModal: false,
        activePricingVoyage: { classes: [] },
        activePricingLeg: 'Outbound',
        showSeatMapDrawer: false,
        activeDeckPaxIndex: null,
        activeDeckType: 'outbound',
        currentSeatMapList: [],
        issuedBookingId: null,
        issuedPnr: '',
        issuedTzs: '',
        issuedUsd: '',
        printUrl: '',

        init() {
            this.buildDateStrip();
            this.fetchAvailability();
        },

        openPricingModal(voyage, leg) {
            let classesList = [];
            if (voyage && voyage.classes) {
                voyage.classes.forEach(c => {
                    classesList.push({
                        class_id: c.class_id,
                        class_name: c.class_name || '',
                        available_seats: c.available_seats || 0,
                        tzs_price: c.tzs_price || 0,
                        usd_price: c.usd_price || 0,
                        child_tzs_price: c.child_tzs_price || Math.round((Number(c.tzs_price) || 0) * 0.75),
                        child_usd_price: c.child_usd_price || Math.round((Number(c.usd_price) || 0) * 0.75)
                    });
                });
            }
            this.activePricingVoyage = {
                ferry_name: voyage ? voyage.ferry_name : '',
                departure_time: voyage ? voyage.departure_time : '',
                classes: classesList
            };
            this.activePricingLeg = leg;
            this.showPricingModal = true;
        },

        buildDateStrip() {
            let list = [];
            const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            let curr = new Date();
            for(let i=0; i<8; i++) {
                let d = new Date(curr);
                d.setDate(curr.getDate() + i);
                let y = d.getFullYear();
                let m = String(d.getMonth() + 1).padStart(2, '0');
                let dayNum = String(d.getDate()).padStart(2, '0');
                let dateStr = `${y}-${m}-${dayNum}`;
                list.push({
                    dateStr: dateStr,
                    dayName: i === 0 ? 'Today' : (i === 1 ? 'Tomorrow' : days[d.getDay()]),
                    shortDate: `${months[d.getMonth()]} ${d.getDate()}`
                });
            }
            this.dateStrip = list;
        },

        setTripType(type) {
            this.tripType = type;
            this.pageOneViewMode = 'side_by_side';
            if(this.lockIds.length > 0) {
                if(!confirm("Changing trip type will clear current seat holds and roster. Continue?")) return;
                this.cancelHolds();
            }
            this.selectedOutbound = null;
            this.selectedReturn = null;
            this.fetchAvailability();
        },

        onOutboundRouteChanged() {
            // Auto flip return route
            @foreach($routes as $route)
                if(this.outboundRouteId == {{ $route->id }}) {
                    @if(str_contains(strtolower($route->name), 'dar es salaam to zanzibar'))
                        @php $rev = $routes->first(fn($r) => str_contains(strtolower($r->name), 'zanzibar to dar es salaam')); @endphp
                        @if($rev) this.returnRouteId = {{ $rev->id }}; @endif
                    @elseif(str_contains(strtolower($route->name), 'zanzibar to dar es salaam'))
                        @php $rev = $routes->first(fn($r) => str_contains(strtolower($r->name), 'dar es salaam to zanzibar')); @endphp
                        @if($rev) this.returnRouteId = {{ $rev->id }}; @endif
                    @endif
                }
            @endforeach
            this.fetchAvailability();
        },

        async fetchAvailability() {
            this.loading = true;
            try {
                let url = `{{ route('portal.bookings.fast-booking.availability') }}?route_id=${this.outboundRouteId}&date=${this.outboundDate}`;
                if(this.tripType === 'round_trip') {
                    url += `&return_route_id=${this.returnRouteId}&return_date=${this.returnDate}`;
                }
                const res = await fetch(url);
                const data = await res.json();
                if(data.status === 'success') {
                    this.outboundVoyages = data.outbound_voyages || [];
                    this.returnVoyages = data.return_voyages || [];
                }
            } catch(e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        },

        refreshSchedules() {
            this.fetchAvailability();
        },

        selectOutboundClass(voyage, cls) {
            if(cls.available_seats <= 0) return;
            if(this.lockIds.length > 0) {
                if(!confirm("Selecting a different schedule will release current holds. Continue?")) return;
                this.cancelHolds();
            }
            this.selectedOutbound = {
                voyage_id: voyage.voyage_id,
                class_id: cls.class_id,
                class_name: cls.class_name,
                tzs_price: cls.tzs_price,
                usd_price: cls.usd_price,
                child_tzs_price: cls.child_tzs_price,
                child_usd_price: cls.child_usd_price,
                departure_time: voyage.departure_time,
                ferry_name: voyage.ferry_name
            };
        },

        selectReturnClass(voyage, cls) {
            if(cls.available_seats <= 0) return;
            if(this.lockIds.length > 0) {
                if(!confirm("Selecting a different schedule will release current holds. Continue?")) return;
                this.cancelHolds();
            }
            this.selectedReturn = {
                voyage_id: voyage.voyage_id,
                class_id: cls.class_id,
                class_name: cls.class_name,
                tzs_price: cls.tzs_price,
                usd_price: cls.usd_price,
                child_tzs_price: cls.child_tzs_price,
                child_usd_price: cls.child_usd_price,
                departure_time: voyage.departure_time,
                ferry_name: voyage.ferry_name
            };
        },

        isReadyToHold() {
            if(this.lockIds.length > 0) return false;
            if(!this.selectedOutbound) return false;
            if(this.tripType === 'round_trip' && !this.selectedReturn) return false;
            return true;
        },

        getSelectionSummaryText() {
            let txt = `Outbound: ${this.outboundDate} at ${this.selectedOutbound?.departure_time} (${this.selectedOutbound?.ferry_name} - ${this.selectedOutbound?.class_name})`;
            if(this.tripType === 'round_trip' && this.selectedReturn) {
                txt += ` | Return: ${this.returnDate} at ${this.selectedReturn?.departure_time} (${this.selectedReturn?.ferry_name} - ${this.selectedReturn?.class_name})`;
            }
            return txt;
        },

        async acquireTemporaryHolds() {
            if(!this.isReadyToHold() || this.holding) return;
            this.holding = true;
            try {
                let payload = {
                    voyage_id: this.selectedOutbound.voyage_id,
                    ferry_class_id: this.selectedOutbound.class_id,
                    quantity: this.passengers.length,
                    _token: '{{ csrf_token() }}'
                };
                if(this.tripType === 'round_trip' && this.selectedReturn) {
                    payload.return_voyage_id = this.selectedReturn.voyage_id;
                    payload.return_ferry_class_id = this.selectedReturn.class_id;
                }

                const res = await fetch(`{{ route('portal.bookings.fast-booking.hold') }}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if(data.status === 'success') {
                    this.lockIds = data.lock_ids || [];
                    this.startCountdown(300);
                    this.step = 2;
                    this.adultsCount = this.passengers.filter(p => p.passenger_type === 'adult').length;
                    this.childrenCount = this.passengers.filter(p => p.passenger_type === 'child').length;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    notify(data.message || 'Could not acquire inventory hold.');
                }
            } catch(e) {
                console.error(e);
                notify('Error acquiring hold.');
            } finally {
                this.holding = false;
            }
        },

        startCountdown(seconds) {
            if(this.countdownTimer) clearInterval(this.countdownTimer);
            this.countdownSeconds = seconds;
            this.updateCountdownText();
            this.countdownTimer = setInterval(() => {
                this.countdownSeconds--;
                if(this.countdownSeconds <= 0) {
                    clearInterval(this.countdownTimer);
                    notify("Inventory hold expired (5 minutes). Re-select schedule to check in.");
                    this.cancelHolds();
                } else {
                    this.updateCountdownText();
                }
            }, 1000);
        },

        updateCountdownText() {
            let m = Math.floor(this.countdownSeconds / 60);
            let s = this.countdownSeconds % 60;
            this.countdownText = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        },

        incrementAdults() {
            if(this.passengers.length >= 50) return;
            this.adultsCount = (parseInt(this.adultsCount) || 0) + 1;
            this.onAdultsCountChanged();
        },

        decrementAdults() {
            let curr = parseInt(this.adultsCount) || 0;
            if(curr <= 1 && this.childrenCount === 0) return; // Must keep at least 1 passenger total
            if(curr <= 0) return;
            this.adultsCount = curr - 1;
            this.onAdultsCountChanged();
        },

        onAdultsCountChanged() {
            let oldAdults = this.passengers.filter(p => p.passenger_type === 'adult').length;
            let target = parseInt(this.adultsCount);
            if(isNaN(target) || target < 0) target = 0;
            if(target === 0 && this.childrenCount === 0) target = 1; // At least 1 passenger total
            if(target + this.childrenCount > 50) target = Math.max(0, 50 - this.childrenCount);
            this.adultsCount = target;

            if(target > oldAdults) {
                let diff = target - oldAdults;
                for(let i = 0; i < diff; i++) {
                    this.passengers.push({ name: '', id_number: '', nationality_type: 1, passenger_type: 'adult', outbound_seat: null, return_seat: null });
                }
            } else if(target < oldAdults) {
                let diff = oldAdults - target;
                for(let i = 0; i < diff; i++) {
                    for(let j = this.passengers.length - 1; j >= 0; j--) {
                        if(this.passengers[j].passenger_type === 'adult') {
                            this.passengers.splice(j, 1);
                            break;
                        }
                    }
                }
            }
            this.adultsCount = this.passengers.filter(p => p.passenger_type === 'adult').length;
            if(this.step === 2) this.adjustManifestHolds(oldAdults, 'adult');
        },

        incrementChildren() {
            if(this.passengers.length >= 50) return;
            this.childrenCount = (parseInt(this.childrenCount) || 0) + 1;
            this.onChildrenCountChanged();
        },

        decrementChildren() {
            let curr = parseInt(this.childrenCount) || 0;
            if(curr <= 0) return;
            if(curr <= 1 && this.adultsCount === 0) return; // Must keep at least 1 passenger total
            this.childrenCount = curr - 1;
            this.onChildrenCountChanged();
        },

        onChildrenCountChanged() {
            let oldChildren = this.passengers.filter(p => p.passenger_type === 'child').length;
            let target = parseInt(this.childrenCount);
            if(isNaN(target) || target < 0) target = 0;
            if(target === 0 && this.adultsCount === 0) {
                this.adultsCount = 1;
                this.onAdultsCountChanged();
                return;
            }
            if(target + this.adultsCount > 50) target = Math.max(0, 50 - this.adultsCount);
            this.childrenCount = target;

            if(target > oldChildren) {
                let diff = target - oldChildren;
                for(let i = 0; i < diff; i++) {
                    this.passengers.push({ name: '', id_number: '', nationality_type: 1, passenger_type: 'child', outbound_seat: null, return_seat: null });
                }
            } else if(target < oldChildren) {
                let diff = oldChildren - target;
                for(let i = 0; i < diff; i++) {
                    for(let j = this.passengers.length - 1; j >= 0; j--) {
                        if(this.passengers[j].passenger_type === 'child') {
                            this.passengers.splice(j, 1);
                            break;
                        }
                    }
                }
            }
            this.childrenCount = this.passengers.filter(p => p.passenger_type === 'child').length;
            if(this.step === 2) this.adjustManifestHolds(oldChildren, 'child');
        },

        async adjustManifestHolds(revertCount = null, revertType = null) {
            if(this.adjustingHolds || !this.selectedOutbound) return;
            this.adjustingHolds = true;
            try {
                let payload = {
                    voyage_id: this.selectedOutbound.voyage_id,
                    ferry_class_id: this.selectedOutbound.class_id,
                    quantity: this.passengers.length,
                    lock_ids: this.lockIds || [],
                    is_whole_room: this.selectedOutbound.is_whole_room ? 1 : 0,
                    _token: '{{ csrf_token() }}'
                };
                if (this.selectedOutbound.is_room_based) {
                    payload.room_id = this.selectedOutbound.is_whole_room 
                        ? (this.selectedOutbound.booked_rooms[0]?.id || null) 
                        : this.selectedOutbound.individual_room_id;
                }
                if(this.tripType === 'round_trip' && this.selectedReturn) {
                    payload.return_voyage_id = this.selectedReturn.voyage_id;
                    payload.return_ferry_class_id = this.selectedReturn.class_id;
                    payload.return_is_whole_room = this.selectedReturn.is_whole_room ? 1 : 0;
                    if (this.selectedReturn.is_room_based) {
                        payload.return_room_id = this.selectedReturn.is_whole_room 
                            ? (this.selectedReturn.booked_rooms[0]?.id || null) 
                            : this.selectedReturn.individual_room_id;
                    }
                }
                const res = await fetch(`{{ route('portal.bookings.fast-booking.hold') }}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if(data.status === 'success') {
                    this.lockIds = data.lock_ids || [];
                    this.startCountdown(300); // Reset 5-minute timer smoothly
                } else {
                    notify(data.message || 'Not enough remaining seats in this class to add more passengers.');
                    // Revert the increment or direct typing change cleanly
                    if(revertCount !== null && revertType !== null) {
                        if(revertType === 'adult') {
                            this.adultsCount = revertCount;
                            this.onAdultsCountChanged();
                        } else if(revertType === 'child') {
                            this.childrenCount = revertCount;
                            this.onChildrenCountChanged();
                        }
                    } else if(this.passengers.length > 1) {
                        this.passengers.pop();
                        this.adultsCount = this.passengers.filter(p => p.passenger_type === 'adult').length;
                        this.childrenCount = this.passengers.filter(p => p.passenger_type === 'child').length;
                    }
                }
            } catch(e) {
                console.error(e);
                notify('Error syncing inventory locks.');
            } finally {
                this.adjustingHolds = false;
            }
        },

        countByNationality(type) {
            return this.passengers.filter(p => p.nationality_type === type).length;
        },

        calculateSubtotalTzs() {
            let sum = 0;
            if(!this.selectedOutbound) return 0;
            
            let billedOutboundRooms = {};
            let billedReturnRooms = {};
            let assignedOutboundRooms = {};
            let assignedReturnRooms = {};

            for(let pax of this.passengers) {
                let pType = pax.passenger_type ? pax.passenger_type.toLowerCase() : 'adult';
                let isResident = (pax.nationality_type === 1);
                let cls = this.selectedOutbound;
                let retCls = this.selectedReturn;

                let outboundRoomId = null;
                let returnRoomId = null;

                if (cls?.is_room_based && cls?.is_whole_room && cls?.booked_rooms) {
                    for (let r of cls.booked_rooms) {
                        if ((assignedOutboundRooms[r.id] || 0) < r.capacity) {
                            outboundRoomId = r.id;
                            assignedOutboundRooms[r.id] = (assignedOutboundRooms[r.id] || 0) + 1;
                            break;
                        }
                    }
                }
                if (this.tripType === 'round_trip' && retCls?.is_room_based && retCls?.is_whole_room && retCls?.booked_rooms) {
                    for (let r of retCls.booked_rooms) {
                        if ((assignedReturnRooms[r.id] || 0) < r.capacity) {
                            returnRoomId = r.id;
                            assignedReturnRooms[r.id] = (assignedReturnRooms[r.id] || 0) + 1;
                            break;
                        }
                    }
                }

                // Outbound fare
                if (cls?.is_room_based && cls?.is_whole_room && outboundRoomId) {
                    if (!billedOutboundRooms[outboundRoomId]) {
                        billedOutboundRooms[outboundRoomId] = true;
                        if (isResident) {
                            let rObj = cls.booked_rooms.find(r => r.id === outboundRoomId);
                            sum += parseFloat(rObj?.tzs_price || 0);
                        }
                    }
                } else {
                    sum += isResident ? ((pType === 'child') ? cls.child_tzs_price : cls.tzs_price) : 0;
                }

                // Return fare
                if (this.tripType === 'round_trip' && retCls) {
                    if (retCls?.is_room_based && retCls?.is_whole_room && returnRoomId) {
                        if (!billedReturnRooms[returnRoomId]) {
                            billedReturnRooms[returnRoomId] = true;
                            if (isResident) {
                                let rObj = retCls.booked_rooms.find(r => r.id === returnRoomId);
                                sum += parseFloat(rObj?.tzs_price || 0);
                            }
                        }
                    } else {
                        sum += isResident ? ((pType === 'child') ? retCls.child_tzs_price : retCls.tzs_price) : 0;
                    }
                }
            }
            return sum;
        },

        calculateSubtotalUsd() {
            let sum = 0;
            if(!this.selectedOutbound) return 0;
            
            let billedOutboundRooms = {};
            let billedReturnRooms = {};
            let assignedOutboundRooms = {};
            let assignedReturnRooms = {};

            for(let pax of this.passengers) {
                let pType = pax.passenger_type ? pax.passenger_type.toLowerCase() : 'adult';
                let isNonResident = (pax.nationality_type === 2);
                let cls = this.selectedOutbound;
                let retCls = this.selectedReturn;

                let outboundRoomId = null;
                let returnRoomId = null;

                if (cls?.is_room_based && cls?.is_whole_room && cls?.booked_rooms) {
                    for (let r of cls.booked_rooms) {
                        if ((assignedOutboundRooms[r.id] || 0) < r.capacity) {
                            outboundRoomId = r.id;
                            assignedOutboundRooms[r.id] = (assignedOutboundRooms[r.id] || 0) + 1;
                            break;
                        }
                    }
                }
                if (this.tripType === 'round_trip' && retCls?.is_room_based && retCls?.is_whole_room && retCls?.booked_rooms) {
                    for (let r of retCls.booked_rooms) {
                        if ((assignedReturnRooms[r.id] || 0) < r.capacity) {
                            returnRoomId = r.id;
                            assignedReturnRooms[r.id] = (assignedReturnRooms[r.id] || 0) + 1;
                            break;
                        }
                    }
                }

                // Outbound fare
                if (cls?.is_room_based && cls?.is_whole_room && outboundRoomId) {
                    if (!billedOutboundRooms[outboundRoomId]) {
                        billedOutboundRooms[outboundRoomId] = true;
                        if (isNonResident) {
                            let rObj = cls.booked_rooms.find(r => r.id === outboundRoomId);
                            sum += parseFloat(rObj?.usd_price || 0);
                        }
                    }
                } else {
                    sum += isNonResident ? ((pType === 'child') ? cls.child_usd_price : cls.usd_price) : 0;
                }

                // Return fare
                if (this.tripType === 'round_trip' && retCls) {
                    if (retCls?.is_room_based && retCls?.is_whole_room && returnRoomId) {
                        if (!billedReturnRooms[returnRoomId]) {
                            billedReturnRooms[returnRoomId] = true;
                            if (isNonResident) {
                                let rObj = retCls.booked_rooms.find(r => r.id === returnRoomId);
                                sum += parseFloat(rObj?.usd_price || 0);
                            }
                        }
                    } else {
                        sum += isNonResident ? ((pType === 'child') ? retCls.child_usd_price : retCls.usd_price) : 0;
                    }
                }
            }
            return sum;
        },

        async submitBooking() {
            for(let pax of this.passengers) {
                if(!pax.name || pax.name.trim() === '') {
                    notify("Please enter full name for every passenger.");
                    return;
                }
            }
            if(this.submitting || this.submitted) return;
            this.submitting = true;

            try {
                let payload = {
                    lock_ids: this.lockIds,
                    trip_type: this.tripType,
                    payment_channel: this.paymentChannel,
                    voyage_id: this.selectedOutbound.voyage_id,
                    ferry_class_id: this.selectedOutbound.class_id,
                    passengers: this.passengers.map(p => {
                        let outboundRoomId = null;
                        let returnRoomId = null;
                        let assignedOutboundRooms = {};
                        let assignedReturnRooms = {};

                        if (this.selectedOutbound?.is_room_based) {
                            if (this.selectedOutbound?.is_whole_room && this.selectedOutbound?.booked_rooms && this.selectedOutbound.booked_rooms.length > 0) {
                                for (let r of this.selectedOutbound.booked_rooms) {
                                    if ((assignedOutboundRooms[r.id] || 0) < r.capacity) {
                                        outboundRoomId = r.id;
                                        assignedOutboundRooms[r.id] = (assignedOutboundRooms[r.id] || 0) + 1;
                                        break;
                                    }
                                }
                            } else if (!this.selectedOutbound?.is_whole_room) {
                                outboundRoomId = this.selectedOutbound.individual_room_id;
                            }
                        }

                        if (this.selectedReturn?.is_room_based) {
                            if (this.selectedReturn?.is_whole_room && this.selectedReturn?.booked_rooms && this.selectedReturn.booked_rooms.length > 0) {
                                for (let r of this.selectedReturn.booked_rooms) {
                                    if ((assignedReturnRooms[r.id] || 0) < r.capacity) {
                                        returnRoomId = r.id;
                                        assignedReturnRooms[r.id] = (assignedReturnRooms[r.id] || 0) + 1;
                                        break;
                                    }
                                }
                            } else if (!this.selectedReturn?.is_whole_room) {
                                returnRoomId = this.selectedReturn.individual_room_id;
                            }
                        }

                        let pt = this.passengerTypes.find(t => t.code === p.passenger_type);
                        return Object.assign({}, p, { 
                            passenger_type_id: pt ? pt.id : 1,
                            outbound_room_id: outboundRoomId,
                            is_whole_room: this.selectedOutbound?.is_whole_room ? 1 : 0,
                            return_room_id: returnRoomId,
                            return_is_whole_room: this.selectedReturn?.is_whole_room ? 1 : 0
                        });
                    }),
                    _token: '{{ csrf_token() }}'
                };
                if(this.tripType === 'round_trip' && this.selectedReturn) {
                    payload.return_voyage_id = this.selectedReturn.voyage_id;
                    payload.return_ferry_class_id = this.selectedReturn.class_id;
                }

                const res = await fetch(`{{ route('portal.bookings.fast-booking.store') }}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if(data.status === 'success') {
                    if(this.countdownTimer) clearInterval(this.countdownTimer);
                    this.submitted = true;
                    this.issuedBookingId = data.booking_id;
                    this.issuedPnr = data.booking_reference;
                    this.issuedTzs = data.total_tzs;
                    this.issuedUsd = data.total_usd;
                    this.printUrl = data.print_url;
                    this.showReceiptModal = true;

                    // Immediately redirect directly to the Booking Profile dashboard without auto-printing
                    setTimeout(() => {
                        if(this.issuedBookingId) {
                            window.location.href = `/portal/bookings/${this.issuedBookingId}`;
                        }
                    }, 600);
                } else {
                    notify(data.message || 'Failed to issue tickets.');
                }
            } catch(e) {
                console.error(e);
                notify('An error occurred while issuing tickets.');
            } finally {
                if(!this.submitted) {
                    this.submitting = false;
                }
            }
        },

        async cancelHolds(returnToPageOne = true) {
            if(this.countdownTimer) clearInterval(this.countdownTimer);
            this.lockIds = [];
            this.countdownSeconds = 300;
            if(returnToPageOne) {
                this.step = 1;
            }
        },

        resetDesk() {
            this.cancelHolds(true);
            this.step = 1;
            this.selectedOutbound = null;
            this.selectedReturn = null;
            this.adultsCount = 1;
            this.childrenCount = 0;
            this.passengers = [{ name: '', id_number: '', nationality_type: 1, passenger_type: 'adult', outbound_seat: null, return_seat: null }];
            this.fetchAvailability();
        },

        openSeatMapDrawer(paxIndex, legType) {
            this.activeDeckPaxIndex = paxIndex;
            this.activeDeckType = legType;
            let list = [];
            let prefix = (legType === 'outbound' && this.selectedOutbound) ? (this.selectedOutbound.class_name.substring(0,3).toUpperCase() + '-') : 
                         ((legType === 'return' && this.selectedReturn) ? (this.selectedReturn.class_name.substring(0,3).toUpperCase() + '-') : 'SEAT-');
            let capacity = 48;
            for(let i=1; i<=capacity; i++) {
                let sId = prefix + (i < 10 ? '0'+i : i);
                let status = 'available';
                if(i % 11 === 0 || i % 17 === 0) status = 'booked';
                list.push({ id: sId, status: status });
            }
            this.currentSeatMapList = list;
            this.showSeatMapDrawer = true;
        },

        assignSeatToPax(seatId) {
            if(this.activeDeckPaxIndex === null) return;
            let pax = this.passengers[this.activeDeckPaxIndex];
            if(this.activeDeckType === 'outbound') {
                pax.outbound_seat = seatId === 'OPEN' ? null : seatId;
            } else {
                pax.return_seat = seatId === 'OPEN' ? null : seatId;
            }
            this.showSeatMapDrawer = false;
        },

        isSeatSelectedByAnyPax(seatId) {
            if(!seatId || seatId === 'OPEN') return false;
            return this.passengers.some(p => p.outbound_seat === seatId || p.return_seat === seatId);
        },

        printReceipt() {
            if(this.printUrl) {
                window.open(this.printUrl, '_blank', 'width=400,height=700');
            }
        },

        numberFormat(num) {
            if(!num) return '0';
            return Number(num).toLocaleString();
        }
    }
}
</script>
</x-layouts.portal>
