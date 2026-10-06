<template>
<div class="min-h-screen bg-slate-50 text-slate-800 p-4 font-sans selection:bg-cyan-500 selection:text-black" x-data="fastBookingTerminal()">
    
    <!-- No Ferries Assigned Warning -->
    <template v-if="hasNoFerriesAssigned">
        <div class="max-w-3xl mx-auto mt-20 p-8 bg-white border border-red-500/50 rounded-2xl shadow-2xl text-center flex flex-col items-center">
            <div class="w-20 h-20 bg-red-500/10 rounded-full flex items-center justify-center mb-6">
                <i class="fas fa-ship text-4xl text-red-600"></i>
            </div>
            <h2 class="text-2xl font-black text-slate-800 mb-2">No Vessels Assigned</h2>
            <p class="text-slate-500 mb-6 text-sm">Your agency account currently does not have any ferries authorized for ticketing. Please contact your system administrator to assign vessels to your account.</p>
            <a href="/portal/dashboard" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-100 text-slate-800 font-bold rounded-xl transition">Return to Dashboard</a>
        </div>
    </template>

    <template v-if="!hasNoFerriesAssigned">
    <div>
        <!-- Unified Sticky Header Bar (Light & Even) -->
        <header class="max-w-7xl mx-auto sticky top-0 z-40 bg-white/90 border border-slate-200 rounded-2xl px-5 py-3 mb-6 shadow-lg backdrop-blur-md">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <!-- Left: Page 1 Stepper -->
                <div class="flex-1 flex justify-start">
                    <button type="button" @click="step === 2 ? (step = 1, window.scrollTo({top:0, behavior:'smooth'})) : null" class="flex items-center gap-2.5 transition-all" :class="step === 1 ? 'opacity-100' : 'opacity-60 hover:opacity-100 cursor-pointer'">
                        <span class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-black shadow-md transition-all"
                              :class="step === 1 ? 'bg-cyan-500 text-white ring-2 ring-cyan-400/50 ring-offset-2 ring-offset-white' : 'bg-emerald-500 text-white'">
                            <i class="fas fa-check" v-show="step === 2"></i>
                            <span v-show="step === 1">1</span>
                        </span>
                        <div class="text-left hidden sm:block">
                            <div class="text-[10px] font-bold uppercase tracking-widest" :class="step === 1 ? 'text-cyan-600' : 'text-emerald-600'">Page 1</div>
                            <div class="text-sm font-bold text-slate-800">Route & Voyage Selection</div>
                        </div>
                    </button>
                </div>

                <!-- Center: Trip Type Toggle -->
                <div class="flex-1 flex justify-center">
                    <div class="flex items-center gap-1.5 bg-slate-50 p-1 rounded-xl border border-slate-200">
                        <button @click="setTripType('one_way')" 
                            :class="tripType === 'one_way' ? 'bg-cyan-500 text-white font-semibold shadow-md shadow-cyan-500/20' : 'text-slate-500 hover:text-slate-700 font-medium'"
                            class="px-4 py-2 rounded-lg text-sm transition-all duration-200 flex items-center gap-2">
                            <i class="fas fa-arrow-right text-xs"></i> One-Way Leg
                        </button>
                        <button @click="setTripType('round_trip')" 
                            :class="tripType === 'round_trip' ? 'bg-emerald-500 text-white font-semibold shadow-md shadow-emerald-500/20' : 'text-slate-500 hover:text-slate-700 font-medium'"
                            class="px-4 py-2 rounded-lg text-sm transition-all duration-200 flex items-center gap-2">
                            <i class="fas fa-exchange-alt text-xs"></i> Go & Return
                        </button>
                    </div>
                </div>

                <!-- Right: Page 2 Stepper + Refresh -->
                <div class="flex-1 flex justify-end">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2.5 transition-all" :class="step === 2 ? 'opacity-100' : 'opacity-40'">
                            <div class="text-right hidden sm:block">
                                <div class="text-[10px] font-bold uppercase tracking-widest" :class="step === 2 ? 'text-cyan-600' : 'text-slate-500'">Page 2</div>
                                <div class="text-sm font-bold" :class="step === 2 ? 'text-slate-800' : 'text-slate-500'">Passenger Details</div>
                            </div>
                            <span class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-black shadow-md transition-all"
                                  :class="step === 2 ? 'bg-cyan-500 text-white ring-2 ring-cyan-400/50 ring-offset-2 ring-offset-white' : 'bg-slate-200 text-slate-500'">
                                2
                            </span>
                        </div>
                        <button @click="refreshSchedules()" class="p-2.5 bg-slate-50 hover:bg-slate-100 text-slate-500 hover:text-cyan-600 rounded-xl border border-slate-200 transition" title="Refresh Live Inventory">
                            <i class="fas fa-sync-alt" :class="loading ? 'animate-spin text-cyan-600' : ''"></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>

    <!-- PAGE 1: ROUTE & VOYAGE SELECTION -->
    <div v-show="step === 1" class="max-w-7xl mx-auto space-y-6 animate-fade-in">
        
        <!-- Round-Trip Display Mode Controller Bar (Side-by-Side vs Tabs) -->
        <div v-show="tripType === 'round_trip'" v-transition 
             class="bg-white/95 border border-slate-200 rounded-2xl p-3.5 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-br from-cyan-500/20 to-emerald-500/20 rounded-xl border border-cyan-500/30 text-cyan-600">
                    <i class="fas fa-columns text-base"></i>
                </div>
                <div>
                    <h3 class="text-xs font-black text-slate-700 uppercase tracking-wider">Round-Trip Leg Display Mode</h3>
                    <p class="text-[11px] text-slate-500">Compare departures side-by-side or focus on one leg using tabs below</p>
                </div>
            </div>
            <div class="flex items-center gap-2 bg-slate-50 p-1.5 rounded-xl border border-slate-200/80 w-full sm:w-auto justify-center">
                <button type="button" @click="pageOneViewMode = 'side_by_side'" 
                        :class="pageOneViewMode === 'side_by_side' ? 'bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-black shadow-md ring-1 ring-cyan-400/50' : 'text-slate-500 hover:text-slate-700 font-bold'"
                        class="px-3.5 py-1.5 rounded-lg text-xs transition flex items-center gap-2 cursor-pointer">
                    <i class="fas fa-columns"></i> Side-by-Side (2 Columns)
                </button>
                <button type="button" @click="pageOneViewMode = 'outbound_tab'" 
                        :class="pageOneViewMode === 'outbound_tab' ? 'bg-cyan-500 text-white font-black shadow-md ring-1 ring-cyan-400/50' : 'text-slate-500 hover:text-slate-700 font-bold'"
                        class="px-3.5 py-1.5 rounded-lg text-xs transition flex items-center gap-2 cursor-pointer">
                    <i class="fas fa-arrow-right"></i> Outbound Only
                </button>
                <button type="button" @click="pageOneViewMode = 'return_tab'" 
                        :class="pageOneViewMode === 'return_tab' ? 'bg-emerald-500 text-white font-black shadow-md ring-1 ring-emerald-400/50' : 'text-slate-500 hover:text-slate-700 font-bold'"
                        class="px-3.5 py-1.5 rounded-lg text-xs transition flex items-center gap-2 cursor-pointer">
                    <i class="fas fa-undo"></i> Return Only
                </button>
            </div>
        </div>

        <div :class="(tripType === 'round_trip' && pageOneViewMode === 'side_by_side') ? 'grid grid-cols-1 lg:grid-cols-2 gap-6 items-start' : 'space-y-6'">
            
            <!-- OUTBOUND LEG PANEL -->
            <div v-show="tripType === 'one_way' || pageOneViewMode === 'side_by_side' || pageOneViewMode === 'outbound_tab'"
                 class="bg-white/90 border border-slate-200 rounded-2xl p-5 shadow-xl relative overflow-hidden transition-all duration-300 flex flex-col h-full"
                 :class="selectedOutbound ? 'border-cyan-500/40 ring-1 ring-cyan-500/20' : ''">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4 border-b border-slate-200/80 pb-4">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="px-2.5 py-1 bg-cyan-500/10 border border-cyan-500/30 rounded-lg text-cyan-600 font-bold text-xs uppercase tracking-wider">
                            Outbound Leg
                        </span>
                        <h2 class="text-base font-bold text-slate-700 flex items-center gap-2">
                            <i class="fas fa-route text-slate-500"></i>
                            <select v-model="outboundRouteId" @change="onOutboundRouteChanged()" class="bg-slate-50 border border-slate-300 rounded-lg px-3 py-1 text-sm font-bold text-cyan-600 focus:outline-none focus:border-cyan-500">
                                <option v-for="route in routes" :key="route.id" :value="route.id">{{ route.name }}</option>
                            </select>
                        </h2>
                    </div>
                </div>

                <!-- Outbound Date Strip -->
                <div class="flex items-center gap-2 overflow-x-auto pb-3 scrollbar-thin scrollbar-thumb-slate-800">
                    <div class="flex-shrink-0 border border-slate-200 rounded-xl px-2 py-1 bg-slate-100/60 flex flex-col justify-center">
                        <span class="text-[10px] text-slate-500 uppercase tracking-wider mb-0.5">Select Date</span>
                        <input type="date" v-model="outboundDate" :min="todayDateStr" @change="fetchAvailability()" class="bg-transparent text-cyan-600 text-sm font-bold focus:outline-none cursor-pointer">
                    </div>
                    <template v-for="day in outboundDateStrip" :key="day.dateStr">
                        <button @click="outboundDate = day.dateStr; fetchAvailability()"
                            :class="outboundDate === day.dateStr ? 'bg-cyan-600/30 border-cyan-500 text-cyan-600 font-bold shadow-md' : 'bg-slate-50/80 border-slate-200 text-slate-500 hover:border-slate-300 hover:text-slate-700 font-medium'"
                            class="px-4 py-2 rounded-xl border text-center min-w-[90px] flex-shrink-0 transition-all duration-150">
                            <div class="text-[10px] uppercase tracking-wider text-slate-500" v-text="day.dayName"></div>
                            <div class="text-sm" v-text="day.shortDate"></div>
                        </button>
                    </template>
                </div>

                <!-- Outbound Schedules Grid -->
                <div class="mt-4 space-y-4">
                    <template x-if="loading">
                        <div class="py-8 text-center text-slate-500 font-medium flex items-center justify-center gap-3">
                            <i class="fas fa-spinner animate-spin text-cyan-600 text-xl"></i> Loading Outbound Inventory...
                        </div>
                    </template>
                    <template x-if="!loading && outboundVoyages.length === 0">
                        <div class="py-8 text-center bg-slate-50/80 rounded-xl border border-slate-200/60 text-slate-500 font-medium">
                            <i class="fas fa-calendar-times text-2xl mb-2 text-slate-500 block"></i>
                            No scheduled departures found for this route on selected date.
                        </div>
                    </template>
                    <template v-for="voyage in outboundVoyages" :key="voyage.voyage_id">
                        <div class="bg-slate-50/90 border rounded-xl p-4 transition-all duration-200"
                             :class="selectedOutbound && selectedOutbound.voyage_id === voyage.voyage_id ? 'border-cyan-500 bg-white/95 shadow-lg shadow-cyan-500/10' : 'border-slate-200 hover:border-slate-300'">
                            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 mb-3">
                                <div>
                                    <span class="text-lg font-black text-cyan-600" v-text="voyage.departure_time"></span>
                                    <span class="text-sm font-bold text-slate-700 ml-2" v-text="voyage.ferry_name"></span>
                                    <span class="text-xs text-slate-500 ml-1.5" v-text="'• ' + voyage.ferry_type"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click.prevent.stop="openPricingModal(voyage, 'Outbound')" type="button"
                                            class="px-2.5 py-1 rounded-lg text-xs font-bold bg-cyan-500/10 hover:bg-cyan-500/20 text-cyan-600 border border-cyan-500/30 hover:border-cyan-500/50 transition flex items-center gap-1.5 cursor-pointer shadow-sm relative z-10">
                                        <i class="fas fa-tags text-cyan-600"></i> Fares & Promos
                                    </button>
                                    <span v-show="voyage.has_layout" class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-500/10 text-indigo-600 border border-indigo-500/20">
                                        <i class="fas fa-th mr-1"></i> Reserved Seat Map
                                    </span>
                                    <span v-show="!voyage.has_layout" class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-600 border border-amber-500/20">
                                        <i class="fas fa-users mr-1"></i> Open Seating Pool
                                    </span>
                                </div>
                            </div>

                            <!-- Horizontal Class Tiles -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3.5 mt-2">
                                <template v-for="cls in voyage.classes" :key="cls.class_id">
                                    <button @click="selectOutboundClass(voyage, cls)"
                                        :disabled="cls.available_seats <= 0"
                                        :class="{
                                            'bg-cyan-50 border-cyan-500 ring-2 ring-cyan-500/40 shadow-md shadow-cyan-500/20': selectedOutbound && selectedOutbound.voyage_id === voyage.voyage_id && selectedOutbound.class_id === cls.class_id,
                                            'bg-white border-slate-200 hover:border-cyan-400 hover:bg-cyan-50/50 hover:shadow-md': !(selectedOutbound && selectedOutbound.voyage_id === voyage.voyage_id && selectedOutbound.class_id === cls.class_id) && cls.available_seats > 0,
                                            'bg-slate-50 border-slate-200 opacity-50 cursor-not-allowed': cls.available_seats <= 0
                                        }"
                                        class="p-3.5 rounded-xl border text-left flex flex-col justify-between relative transition-all duration-150">
                                        <div>
                                            <div class="text-xs font-bold text-slate-700 truncate" v-text="cls.class_name"></div>
                                            <!-- Ring Meter Indicator -->
                                            <div class="flex items-center gap-1.5 mt-1.5">
                                                <div class="w-2 h-2 rounded-full" :class="cls.available_seats > 10 ? 'bg-emerald-400 animate-pulse' : (cls.available_seats > 0 ? 'bg-amber-400' : 'bg-rose-500')"></div>
                                                <span class="text-xs font-medium text-slate-500" v-text="cls.available_seats + ' Left'"></span>
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
            <div v-show="tripType === 'round_trip' && (pageOneViewMode === 'side_by_side' || pageOneViewMode === 'return_tab')" v-transition 
                 class="bg-white/90 border border-slate-200 rounded-2xl p-5 shadow-xl relative overflow-hidden transition-all duration-300 flex flex-col h-full"
                 :class="selectedReturn ? 'border-emerald-500/40 ring-1 ring-emerald-500/20' : ''">
                    <div v-if="!selectedOutbound" class="absolute inset-0 z-20 bg-slate-50/90 backdrop-blur-[2px] flex items-center justify-center rounded-2xl">
                        <div class="text-slate-500 font-bold flex flex-col items-center bg-white border border-slate-300 px-6 py-4 rounded-2xl shadow-2xl text-center">
                            <i class="fas fa-lock text-3xl mb-3 text-slate-500"></i>
                            Select an Outbound Voyage first
                        </div>
                    </div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4 border-b border-slate-200/80 pb-4">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="px-2.5 py-1 bg-emerald-500/10 border border-emerald-500/30 rounded-lg text-emerald-600 font-bold text-xs uppercase tracking-wider">
                            Return Leg
                        </span>
                        <h2 class="text-base font-bold text-slate-700 flex items-center gap-2">
                            <i class="fas fa-undo text-slate-500"></i>
                            <select v-model="returnRouteId" disabled class="bg-slate-50 border border-slate-300 rounded-lg px-3 py-1 text-sm font-bold text-emerald-600 focus:outline-none opacity-80 cursor-not-allowed">
                                <option v-for="route in routes" :key="route.id" :value="route.id">{{ route.name }}</option>
                            </select>
                        </h2>
                    </div>
                </div>

                <!-- Return Date Strip -->
                <div class="flex items-center gap-2 overflow-x-auto pb-3 scrollbar-thin scrollbar-thumb-slate-800">
                    <div class="flex-shrink-0 border border-slate-200 rounded-xl px-2 py-1 bg-slate-100/60 flex flex-col justify-center">
                        <span class="text-[10px] text-slate-500 uppercase tracking-wider mb-0.5">Select Date</span>
                        <input type="date" v-model="returnDate" :min="outboundDate || todayDateStr" @change="fetchAvailability()" class="bg-transparent text-emerald-600 text-sm font-bold focus:outline-none cursor-pointer">
                    </div>
                    <template v-for="day in returnDateStrip" :key="day.dateStr">
                        <button @click="returnDate = day.dateStr; fetchAvailability()"
                            :class="returnDate === day.dateStr ? 'bg-emerald-600/30 border-emerald-500 text-emerald-600 font-bold shadow-md' : 'bg-slate-50/80 border-slate-200 text-slate-500 hover:border-slate-300 hover:text-slate-700 font-medium'"
                            class="px-4 py-2 rounded-xl border text-center min-w-[90px] flex-shrink-0 transition-all duration-150">
                            <div class="text-[10px] uppercase tracking-wider text-slate-500" v-text="day.dayName"></div>
                            <div class="text-sm" v-text="day.shortDate"></div>
                        </button>
                    </template>
                </div>

                <!-- Return Schedules Grid -->
                <div class="mt-4 space-y-4">
                    <template x-if="loading">
                        <div class="py-8 text-center text-slate-500 font-medium flex items-center justify-center gap-3">
                            <i class="fas fa-spinner animate-spin text-emerald-600 text-xl"></i> Loading Return Inventory...
                        </div>
                    </template>
                    <template x-if="!loading && filteredReturnVoyages.length === 0">
                        <div class="py-8 text-center bg-slate-50/80 rounded-xl border border-slate-200/60 text-slate-500 font-medium">
                            <i class="fas fa-calendar-times text-2xl mb-2 text-slate-500 block"></i>
                            No scheduled departures found for the return route on selected date.
                        </div>
                    </template>
                    <template v-for="voyage in filteredReturnVoyages" :key="voyage.voyage_id">
                        <div class="bg-slate-50/90 border rounded-xl p-4 transition-all duration-200"
                             :class="selectedReturn && selectedReturn.voyage_id === voyage.voyage_id ? 'border-emerald-500 bg-white/95 shadow-lg shadow-emerald-500/10' : 'border-slate-200 hover:border-slate-300'">
                            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 mb-3">
                                <div>
                                    <span class="text-lg font-black text-emerald-600" v-text="voyage.departure_time"></span>
                                    <span class="text-sm font-bold text-slate-700 ml-2" v-text="voyage.ferry_name"></span>
                                    <span class="text-xs text-slate-500 ml-1.5" v-text="'• ' + voyage.ferry_type"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click.prevent.stop="openPricingModal(voyage, 'Return')" type="button"
                                            class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 border border-emerald-500/30 hover:border-emerald-500/50 transition flex items-center gap-1.5 cursor-pointer shadow-sm relative z-10">
                                        <i class="fas fa-tags text-emerald-600"></i> Fares & Promos
                                    </button>
                                    <span v-show="voyage.has_layout" class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-500/10 text-indigo-600 border border-indigo-500/20">
                                        <i class="fas fa-th mr-1"></i> Reserved Seat Map
                                    </span>
                                    <span v-show="!voyage.has_layout" class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-600 border border-amber-500/20">
                                        <i class="fas fa-users mr-1"></i> Open Seating Pool
                                    </span>
                                </div>
                            </div>

                            <!-- Horizontal Class Tiles -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3.5 mt-2">
                                <template v-for="cls in voyage.classes" :key="cls.class_id">
                                    <button @click="selectReturnClass(voyage, cls)"
                                        :disabled="cls.available_seats <= 0"
                                        :class="{
                                            'bg-emerald-50 border-emerald-500 ring-2 ring-emerald-500/40 shadow-md shadow-emerald-500/20': selectedReturn && selectedReturn.voyage_id === voyage.voyage_id && selectedReturn.class_id === cls.class_id,
                                            'bg-white border-slate-200 hover:border-emerald-400 hover:bg-emerald-50/50 hover:shadow-md': !(selectedReturn && selectedReturn.voyage_id === voyage.voyage_id && selectedReturn.class_id === cls.class_id) && cls.available_seats > 0,
                                            'bg-slate-50 border-slate-200 opacity-50 cursor-not-allowed': cls.available_seats <= 0
                                        }"
                                        class="p-3.5 rounded-xl border text-left flex flex-col justify-between relative transition-all duration-150">
                                        <div>
                                            <div class="text-xs font-bold text-slate-700 truncate" v-text="cls.class_name"></div>
                                            <div class="flex items-center gap-1.5 mt-1.5">
                                                <div class="w-2 h-2 rounded-full" :class="cls.available_seats > 10 ? 'bg-emerald-400 animate-pulse' : (cls.available_seats > 0 ? 'bg-amber-400' : 'bg-rose-500')"></div>
                                                <span class="text-xs font-medium text-slate-500" v-text="cls.available_seats + ' Left'"></span>
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
        <div v-show="isReadyToHold()" v-transition
             class="sticky bottom-4 z-30 bg-white/95 backdrop-blur-xl border border-cyan-500/40 rounded-2xl p-4 shadow-2xl flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-4">
                    <div class="p-2.5 bg-cyan-500/20 rounded-xl text-cyan-600">
                        <i class="fas fa-check-circle text-xl"></i>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-slate-700" v-text="getSelectionSummaryText()"></div>
                        <div class="text-xs text-slate-500">Click below to lock seat inventory and proceed to Passenger & Seat Map Deck</div>
                    </div>
                </div>
                <button @click="acquireTemporaryHolds()" 
                    :disabled="holding"
                    class="w-full sm:w-auto px-6 py-3 rounded-xl font-bold bg-gradient-to-r from-cyan-500 via-emerald-500 to-teal-400 hover:from-cyan-400 hover:to-emerald-400 text-slate-950 shadow-lg shadow-cyan-500/20 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-bolt" :class="holding ? 'animate-spin' : ''"></i>
                    <span v-text="holding ? 'Locking Inventory...' : 'Next: Passenger Details & Seat Map ➔'"></span>
                </button>
            </div>
        </div>
        <!-- PAGE 2: PASSENGER DETAILS, SEAT ALLOCATION & PAYMENT -->
        <div v-show="step === 2" class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6 items-start animate-fade-in" style="display: none;">
            
            <!-- Left Column: Stacked Passenger Deck & Seat Allocation Cards (Cols 1 to 8) -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Top Summary Recap Bar -->
                <div class="bg-white/95 border border-slate-200 rounded-2xl p-5 shadow-xl flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-600 border border-emerald-500/30">Active Hold</span>
                            <h3 class="text-base font-extrabold text-slate-800 flex items-center gap-2">
                                <i class="fas fa-id-card text-cyan-600"></i> Passenger Manifest & Deck Allocation
                            </h3>
                        </div>
                        <p class="text-xs text-slate-500" v-text="getSelectionSummaryText()"></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span v-show="adjustingHolds" v-transition class="px-3 py-1.5 rounded-xl bg-cyan-500/20 border border-cyan-500/40 text-cyan-600 text-xs font-bold animate-pulse flex items-center gap-2">
                            <i class="fas fa-spinner animate-spin text-cyan-600"></i>
                            <span>Syncing Inventory Locks...</span>
                        </span>
                        <span class="px-3 py-1.5 rounded-xl bg-amber-500/20 border border-amber-500/40 text-amber-600 text-xs font-mono font-bold animate-pulse flex items-center gap-2">
                            <i class="fas fa-clock text-amber-600"></i>
                            <span>Hold Expires: <strong v-text="countdownText"></strong></span>
                        </span>
                    </div>
                </div>

                <!-- Stepper / Dual Adult & Child Passenger Count Controls -->
                <div class="bg-white/95 border border-slate-200 rounded-2xl p-4.5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 shadow-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500/20 to-blue-500/20 border border-cyan-500/30 flex items-center justify-center text-cyan-600 font-bold text-base shadow-md">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <span class="text-sm font-black text-slate-800 block">Manifest Size & Categories</span>
                            <span class="text-xs text-slate-500">Add or remove passengers right here — inventory locks update automatically (<span class="text-cyan-600 font-mono font-bold" v-text="passengers.length + ' Pax Total'"></span>)</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                        <!-- Adults Counter Box -->
                        <div class="flex items-center justify-between gap-3 bg-slate-50 px-3.5 py-2 rounded-xl border border-slate-800/90 shadow-md flex-1 sm:flex-initial">
                            <span class="text-xs font-bold text-slate-500 flex items-center gap-2">
                                <i class="fas fa-user text-blue-600"></i> Adults
                            </span>
                            <div class="flex items-center gap-1.5">
                                <button type="button" @click="decrementAdults()" :disabled="adjustingHolds || (adultsCount <= 1 && childrenCount === 0) || adultsCount <= 0" 
                                        class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-100 text-slate-500 font-black flex items-center justify-center transition disabled:opacity-30">
                                    -
                                </button>
                                <input type="number" v-model.number="adultsCount" @change="onAdultsCountChanged()" :disabled="adjustingHolds" min="0" max="50"
                                       class="w-12 py-1 bg-white border border-slate-300 rounded-lg text-sm font-extrabold text-blue-600 font-mono text-center focus:outline-none focus:border-blue-500 shadow-inner">
                                <button type="button" @click="incrementAdults()" :disabled="adjustingHolds || passengers.length >= 50" 
                                        class="w-7 h-7 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-black flex items-center justify-center transition disabled:opacity-30 shadow-md shadow-blue-500/20">
                                    +
                                </button>
                            </div>
                        </div>

                        <!-- Children Counter Box -->
                        <div class="flex items-center justify-between gap-3 bg-slate-50 px-3.5 py-2 rounded-xl border border-slate-800/90 shadow-md flex-1 sm:flex-initial">
                            <span class="text-xs font-bold text-slate-500 flex items-center gap-2">
                                <i class="fas fa-child text-purple-600"></i> Children
                            </span>
                            <div class="flex items-center gap-1.5">
                                <button type="button" @click="decrementChildren()" :disabled="adjustingHolds || childrenCount <= 0" 
                                        class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-100 text-slate-500 font-black flex items-center justify-center transition disabled:opacity-30">
                                    -
                                </button>
                                <input type="number" v-model.number="childrenCount" @change="onChildrenCountChanged()" :disabled="adjustingHolds" min="0" max="50"
                                       class="w-12 py-1 bg-white border border-slate-300 rounded-lg text-sm font-extrabold text-purple-600 font-mono text-center focus:outline-none focus:border-purple-500 shadow-inner">
                                <button type="button" @click="incrementChildren()" :disabled="adjustingHolds || passengers.length >= 50" 
                                        class="w-7 h-7 rounded-lg bg-purple-600 hover:bg-purple-500 text-white font-black flex items-center justify-center transition disabled:opacity-30 shadow-md shadow-purple-500/20">
                                    +
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto mt-3 sm:mt-0">
                        <!-- Infants Counter Box -->
                        <div class="flex items-center justify-between gap-3 bg-slate-50 px-3.5 py-2 rounded-xl border border-slate-800/90 shadow-md flex-1 sm:flex-initial">
                            <span class="text-xs font-bold text-slate-500 flex items-center gap-2">
                                <i class="fas fa-baby text-pink-500"></i> Infants
                            </span>
                            <div class="flex items-center gap-1.5">
                                <button type="button" @click="decrementInfants()" :disabled="adjustingHolds || infantsCount <= 0 || (infantsCount <= 1 && adultsCount === 0 && childrenCount === 0)" 
                                        class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-100 text-slate-500 font-black flex items-center justify-center transition disabled:opacity-30">
                                    -
                                </button>
                                <input type="number" v-model.number="infantsCount" @change="onInfantsCountChanged()" :disabled="adjustingHolds" min="0" max="50"
                                       class="w-12 py-1 bg-white border border-slate-300 rounded-lg text-sm font-extrabold text-pink-500 font-mono text-center focus:outline-none focus:border-pink-500 shadow-inner">
                                <button type="button" @click="incrementInfants()" :disabled="adjustingHolds || passengers.length >= 50" 
                                        class="w-7 h-7 rounded-lg bg-pink-500 hover:bg-pink-400 text-slate-800 font-black flex items-center justify-center transition disabled:opacity-30 shadow-md shadow-pink-500/20">
                                    +
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Stacked Passenger Profile Cards with Interactive Seat Badges -->
                <div class="space-y-4">
                    <template v-for="(pax, idx) in passengers" :key="idx">
                        <div class="bg-white border-y border-r border-slate-200 border-l-[6px] rounded-2xl p-5 shadow-xl relative space-y-4 transition-all"
                             :class="{
                                'border-l-blue-500 hover:border-blue-300': pax.passenger_type === 'adult',
                                'border-l-purple-500 hover:border-purple-300': pax.passenger_type === 'child',
                                'border-l-pink-500 hover:border-pink-300': pax.passenger_type === 'infant'
                             }">
                            <!-- Card Header -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200/80 pb-3">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-xl text-white font-black text-sm flex items-center justify-center shadow-md" 
                                          :class="{
                                            'bg-gradient-to-br from-blue-500 to-blue-600 shadow-blue-500/30': pax.passenger_type === 'adult',
                                            'bg-gradient-to-br from-purple-500 to-purple-600 shadow-purple-500/30': pax.passenger_type === 'child',
                                            'bg-gradient-to-br from-pink-400 to-pink-500 shadow-pink-500/30': pax.passenger_type === 'infant'
                                          }" 
                                          v-text="idx + 1"></span>
                                    <div>
                                        <span class="text-sm font-extrabold text-slate-800 block" v-text="pax.name || ('Passenger #' + (idx + 1))"></span>
                                        <span class="text-[11px] text-slate-500" v-text="pax.id_number ? ('ID/Passport: ' + pax.id_number) : 'ID/Passport pending...'"></span>
                                    </div>
                                </div>

                                <!-- Resident vs Foreigner Toggle Pill -->
                                <div class="flex items-center gap-1 bg-slate-50 p-1 rounded-xl border border-slate-200">
                                    <button type="button" @click="pax.nationality_type = 1" 
                                            :class="pax.nationality_type === 1 ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/30' : 'text-slate-500 hover:text-slate-700'"
                                            class="px-3 py-1 rounded-lg text-xs transition flex items-center gap-1.5">
                                        <i class="fas fa-flag text-emerald-600"></i> Resident (TZS)
                                    </button>
                                    <button type="button" @click="pax.nationality_type = 2" 
                                            :class="pax.nationality_type === 2 ? 'bg-amber-600 text-slate-800 font-bold shadow-md shadow-amber-600/30' : 'text-slate-500 hover:text-slate-700'"
                                            class="px-3 py-1 rounded-lg text-xs transition flex items-center gap-1.5">
                                        <i class="fas fa-globe text-amber-200"></i> International (USD)
                                    </button>
                                </div>
                            </div>

                            <!-- Profile Inputs Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-center">
                                <div class="sm:col-span-3">
                                    <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Full Name <span class="text-rose-600">*</span></label>
                                    <input type="text" v-model="pax.name" placeholder="e.g. Amani Mushi" 
                                           class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-cyan-500 font-medium shadow-inner">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Gender <span class="text-rose-600">*</span></label>
                                    <select v-model="pax.gender" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-cyan-500 font-bold shadow-inner">
                                        <option value="male">Male (M)</option>
                                        <option value="female">Female (F)</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="text-[11px] text-slate-500 font-bold block mb-1.5">ID Type</label>
                                    <select v-model="pax.id_type_id" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-cyan-500 font-medium shadow-inner">
                                        <option :value="null">N/A</option>
                                        <option v-for="type in idTypes" :key="type.id" :value="type.id">{{ type.label }}</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="text-[11px] text-slate-500 font-bold block mb-1.5">ID / Passport #</label>
                                    <input type="text" v-model="pax.id_number" placeholder="Optional ID/Passport" 
                                           class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-cyan-500 font-medium font-mono shadow-inner">
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="text-[11px] text-slate-500 font-bold block mb-1.5">Fare Category</label>
                                    <div class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-bold flex items-center">
                                        <template v-if="pax.passenger_type === 'adult'">
                                            <span class="text-blue-600 flex items-center gap-1.5"><i class="fas fa-user"></i> Adult (Standard)</span>
                                        </template>
                                        <template v-if="pax.passenger_type === 'child'">
                                            <span class="text-purple-600 flex items-center gap-1.5"><i class="fas fa-child"></i> Child (Discounted)</span>
                                        </template>
                                        <template v-if="pax.passenger_type === 'infant'">
                                            <span class="text-pink-500 flex items-center gap-1.5"><i class="fas fa-baby"></i> Infant</span>
                                        </template>
                                    </div>
                                </div>

                                <!-- Complimentary Toggle (only if user has permission) -->
                                <div v-if="authStore.can('booking:apply_exemption')" class="sm:col-span-12">
                                    <div class="flex items-center gap-3 p-3 rounded-xl border transition-all"
                                         :class="pax.is_complimentary ? 'bg-amber-500/10 border-amber-500/40' : 'bg-slate-50/80 border-slate-200/60'">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" v-model="pax.is_complimentary" class="sr-only peer">
                                            <div class="w-9 h-5 bg-slate-700 peer-checked:bg-amber-500 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all"></div>
                                        </label>
                                        <div>
                                            <span class="text-xs font-bold" :class="pax.is_complimentary ? 'text-amber-600' : 'text-slate-500'">🎫 Complimentary Ticket</span>
                                            <span v-if="pax.is_complimentary" class="block text-[10px] text-amber-600/70">This passenger will travel for free</span>
                                        </div>
                                    </div>
                                    <div v-if="pax.is_complimentary" class="mt-2">
                                        <input type="text" v-model="pax.complimentary_reason" placeholder="Reason for complimentary ticket (required)" 
                                               class="w-full bg-slate-50 border border-amber-500/30 rounded-xl px-3.5 py-2 text-xs text-slate-900 focus:outline-none focus:border-amber-400 font-medium">
                                    </div>
                                </div>

                                <!-- Corporate Work ID (when corporate booking) -->
                                <div v-if="isCorporateBooking" class="sm:col-span-12">
                                    <label class="text-[11px] text-slate-500 font-bold block mb-1.5"><i class="fas fa-id-badge text-blue-600 mr-1"></i>Corporate Work ID <span class="text-rose-600">*</span></label>
                                    <input type="text" v-model="pax.corporate_work_id" placeholder="Employee Work ID / Staff Number" 
                                           class="w-full bg-slate-50 border border-blue-500/30 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-blue-500 font-mono font-medium shadow-inner">
                                </div>
                            </div>

                            <!-- Dual Seat Allocation Deck Pod -->
                            <div v-if="(selectedOutbound && selectedOutbound.has_layout) || (tripType === 'round_trip' && selectedReturn && selectedReturn.has_layout)" class="pt-3 border-t border-slate-200/80 flex flex-wrap items-center justify-between gap-3 bg-slate-50 p-3.5 rounded-xl border border-slate-200/60">
                                <div v-if="selectedOutbound && selectedOutbound.has_layout" class="flex items-center gap-3">
                                    <span class="text-xs font-bold text-slate-500 flex items-center gap-1.5">
                                        <i class="fas fa-ship text-cyan-600"></i> Outbound Seat:
                                    </span>
                                    <button type="button" @click="openSeatMapDrawer(idx, 'outbound')" class="px-3.5 py-2 rounded-xl font-mono text-xs font-bold border transition flex items-center gap-2 shadow-inner"
                                            :class="pax.outbound_seat ? 'bg-emerald-500/20 text-emerald-600 border-emerald-500/40 shadow-sm ring-1 ring-emerald-400/20' : 'bg-white text-cyan-600 hover:bg-slate-100 border-slate-300'">
                                        <i class="fas fa-chair text-sm"></i>
                                        <span v-text="pax.outbound_seat ? ((pax.outbound_seat_label || pax.outbound_seat) + (pax.outbound_deck_number ? ' — Deck ' + pax.outbound_deck_number : '') + ' ✓') : 'Pick Outbound Seat ➔'"></span>
                                    </button>
                                </div>

                                <template v-if="tripType === 'round_trip' && selectedReturn && selectedReturn.has_layout">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-bold text-slate-500 flex items-center gap-1.5">
                                            <i class="fas fa-undo text-amber-600"></i> Return Seat:
                                        </span>
                                        <button type="button" @click="openSeatMapDrawer(idx, 'return')" class="px-3.5 py-2 rounded-xl font-mono text-xs font-bold border transition flex items-center gap-2 shadow-inner"
                                                :class="pax.return_seat ? 'bg-emerald-500/20 text-emerald-600 border-emerald-500/40 shadow-sm ring-1 ring-emerald-400/20' : 'bg-white text-amber-600 hover:bg-slate-100 border-slate-300'">
                                            <i class="fas fa-chair text-sm"></i>
                                            <span v-text="pax.return_seat ? ((pax.return_seat_label || pax.return_seat) + (pax.return_deck_number ? ' — Deck ' + pax.return_deck_number : '') + ' ✓') : 'Pick Return Seat ➔'"></span>
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
                <div class="bg-white/95 border border-slate-200 rounded-3xl p-6 shadow-2xl space-y-5">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                            <i class="fas fa-receipt text-cyan-600"></i> Financial Breakdown
                        </h3>
                        <span class="px-2.5 py-1 rounded-full bg-cyan-500/10 border border-cyan-500/30 text-cyan-600 text-xs font-bold font-mono">Multi-Currency</span>
                    </div>

                    <!-- Live Booking Preview & Discounts -->
                    <div v-if="calculatingPreview" class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80 text-center flex flex-col items-center justify-center gap-2">
                        <i class="fas fa-circle-notch fa-spin text-cyan-600 text-xl"></i>
                        <span class="text-xs text-slate-500 font-bold uppercase tracking-widest">Calculating Live Discounts...</span>
                    </div>
                    
                    <div v-else-if="bookingPreview && bookingPreview.items && bookingPreview.items.length > 0" class="space-y-3 bg-slate-50 p-4 rounded-2xl border border-slate-200/80">
                        <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-2 flex items-center gap-2">
                            <i class="fas fa-tags text-emerald-600"></i> Applied Automatic Discounts
                        </h4>
                        
                        <template v-if="passengers.length > 3">
                            <div v-for="(group, idx) in aggregatedDiscounts" :key="'agg_'+idx" class="border-b border-slate-200/60 last:border-0 pb-2 mb-2 last:mb-0 last:pb-0">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-slate-500 font-bold">{{ group.count }}x Passengers</span>
                                    <span class="text-slate-500 font-mono line-through">{{ numberFormat(group.base_fare) }} TZS</span>
                                </div>
                                <div class="flex justify-between items-center text-[11px] mt-1 bg-emerald-500/10 px-2 py-1 rounded text-emerald-600">
                                    <span class="font-bold flex items-center gap-1.5"><i class="fas fa-check-circle"></i> {{ group.name }}</span>
                                    <span class="font-extrabold font-mono">- {{ numberFormat(group.amount) }} TZS</span>
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <div v-for="(item, idx) in bookingPreview.items" :key="'prev_'+idx" class="border-b border-slate-200/60 last:border-0 pb-2 mb-2 last:mb-0 last:pb-0" v-show="item.discount_amount > 0 || passengers.length <= 3">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-slate-500 font-bold">Passenger {{ item.passenger_index + 1 }}</span>
                                    <span class="text-slate-500 font-mono line-through" v-if="item.discount_amount > 0">{{ item.currency === 'USD' ? '$' + item.base_fare.toFixed(2) : numberFormat(item.base_fare) }} {{ item.currency }}</span>
                                    <span class="text-slate-700 font-mono" v-else>{{ item.currency === 'USD' ? '$' + item.base_fare.toFixed(2) : numberFormat(item.base_fare) }} {{ item.currency }}</span>
                                </div>
                                <div v-if="item.discount_amount > 0" class="flex justify-between items-center text-[11px] mt-1 bg-emerald-500/10 px-2 py-1 rounded text-emerald-600">
                                    <span class="font-bold flex items-center gap-1.5"><i class="fas fa-check-circle"></i> {{ item.discount_name }}</span>
                                    <span class="font-extrabold font-mono">- {{ item.currency === 'USD' ? '$' + item.discount_amount.toFixed(2) : numberFormat(item.discount_amount) }} {{ item.currency }}</span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Category Subtotals -->
                    <div class="space-y-3 bg-slate-50 p-4 rounded-2xl border border-slate-200/80 text-xs">
                        <div class="flex justify-between items-center text-slate-500">
                            <span class="font-medium flex items-center gap-1.5"><i class="fas fa-flag text-emerald-600"></i> Resident Subtotal (<span v-text="countByNationality(1)"></span> Pax):</span>
                            <span class="text-emerald-600 font-extrabold font-mono text-sm" v-text="numberFormat(calculateSubtotalTzs()) + ' TZS'"></span>
                        </div>
                        <div class="flex justify-between items-center text-slate-500">
                            <span class="font-medium flex items-center gap-1.5"><i class="fas fa-globe text-amber-600"></i> International Subtotal (<span v-text="countByNationality(2)"></span> Pax):</span>
                            <span class="text-amber-600 font-extrabold font-mono text-sm" v-text="'$' + calculateSubtotalUsd().toFixed(2) + ' USD'"></span>
                        </div>
                        <div v-if="bookingPreview && bookingPreview.total_discount_tzs > 0" class="flex justify-between items-center text-emerald-600 bg-emerald-500/10 px-3 py-2 rounded-lg mt-2">
                            <span class="font-bold flex items-center gap-1.5 text-xs"><i class="fas fa-tags"></i> Auto Discounts (TZS):</span>
                            <span class="font-extrabold font-mono text-xs" v-text="'- ' + numberFormat(bookingPreview.total_discount_tzs) + ' TZS'"></span>
                        </div>
                        <div v-if="bookingPreview && bookingPreview.total_discount_usd > 0" class="flex justify-between items-center text-emerald-600 bg-emerald-500/10 px-3 py-2 rounded-lg mt-2">
                            <span class="font-bold flex items-center gap-1.5 text-xs"><i class="fas fa-tags"></i> Auto Discounts (USD):</span>
                            <span class="font-extrabold font-mono text-xs" v-text="'- $' + bookingPreview.total_discount_usd.toFixed(2) + ' USD'"></span>
                        </div>
                        <div v-show="couponDiscount > 0" class="flex justify-between items-center text-emerald-600 bg-emerald-500/10 px-3 py-2 rounded-lg mt-2">
                            <span class="font-bold flex items-center gap-1.5 text-xs"><i class="fas fa-tag"></i> Coupon Applied ({{ couponCurrency }}):</span>
                            <span class="font-extrabold font-mono text-xs" v-text="couponCurrency === 'USD' ? '- $' + couponDiscount.toFixed(2) + ' USD' : '- ' + numberFormat(couponDiscount) + ' TZS'"></span>
                        </div>

                        <!-- Exchange Rate Conversion Info (when non-resident passengers exist) -->
                        <div v-if="calculateSubtotalUsd() > 0" class="mt-3 p-3 rounded-xl border transition-all"
                             :class="exchangeRateEnabled ? 'bg-cyan-500/5 border-cyan-500/20' : 'bg-amber-500/5 border-amber-500/20'">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-exchange-alt text-xs" :class="exchangeRateEnabled ? 'text-cyan-600' : 'text-amber-600'"></i>
                                    <span class="text-[11px] font-bold" :class="exchangeRateEnabled ? 'text-cyan-700' : 'text-amber-700'">Exchange Rate Conversion</span>
                                </div>
                                <template v-if="exchangeRateEnabled">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="applyExchangeRate" class="sr-only peer">
                                        <div class="w-8 h-4 bg-surface-200 peer-focus:ring-2 peer-focus:ring-cyan-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-surface-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-cyan-500"></div>
                                    </label>
                                </template>
                                <template v-else>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-500/15 text-amber-600 border border-amber-500/30">DISABLED</span>
                                </template>
                            </div>
                            <div v-if="exchangeRateEnabled" class="mt-2 text-[11px] font-medium transition-colors" :class="applyExchangeRate ? 'text-slate-600' : 'text-slate-400'">
                                <span class="font-mono">1 USD = {{ numberFormat(exchangeRate) }} TZS</span>
                                <span class="mx-1.5 text-slate-300">•</span>
                                <span class="font-mono" :class="applyExchangeRate ? 'text-cyan-600' : 'text-slate-400'">${{ netTotalUsd().toFixed(2) }} × {{ numberFormat(exchangeRate) }} = {{ numberFormat(Math.round(netTotalUsd() * exchangeRate)) }} TZS</span>
                            </div>
                            <div v-else class="mt-2 text-[11px] text-amber-600/80 font-medium">
                                <i class="fas fa-info-circle mr-1"></i> USD fares will be collected in USD without conversion.
                            </div>
                        </div>

                        <!-- Total Due Today -->
                        <div class="pt-3 border-t border-slate-200 flex justify-between items-baseline font-bold text-sm mt-3">
                            <span class="text-slate-800">Total Due Today:</span>
                            <div class="text-right font-mono">
                                <!-- When exchange is APPLIED: show combined TZS total -->
                                <template v-if="exchangeRateEnabled && applyExchangeRate">
                                    <div class="text-emerald-600 text-base font-black" v-text="numberFormat(totalDueTzsConverted()) + ' TZS'"></div>
                                    <div class="text-[11px] text-slate-400 font-medium" v-show="calculateSubtotalUsd() > 0">
                                        (incl. USD converted at {{ numberFormat(exchangeRate) }} TZS)
                                    </div>
                                </template>
                                <!-- When exchange is NOT APPLIED: show TZS and USD separately -->
                                <template v-else>
                                    <div class="text-emerald-600 text-base font-black" v-text="numberFormat(netTotalTzs()) + ' TZS'"></div>
                                    <div class="text-amber-600 text-base font-black" v-show="netTotalUsd() > 0" v-text="'+ $' + netTotalUsd().toFixed(2) + ' USD'"></div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Coupon/Promotion -->
                    <div v-if="authStore.can('booking:apply_promo')" class="space-y-2 mb-4">
                        <label class="text-xs font-bold text-slate-500 block">Promotional Coupon / Promo Code</label>
                        <div class="flex items-center gap-2">
                            <input type="text" v-model="couponCode" @input="couponValid = false; couponDiscount = 0; couponMessage = ''" placeholder="Enter code (Optional)" class="flex-1 bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 uppercase tracking-widest focus:outline-none focus:border-cyan-500 font-mono shadow-inner">
                            <button type="button" @click="validateCoupon" :disabled="validatingCoupon || !couponCode" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-100 text-cyan-600 border border-slate-300 rounded-xl text-xs font-bold transition-colors disabled:opacity-50">
                                <i class="fas fa-spinner animate-spin" v-if="validatingCoupon"></i>
                                <span v-else>Apply</span>
                            </button>
                        </div>
                        <p v-if="couponMessage" :class="couponValid ? 'text-emerald-600' : 'text-rose-600'" class="text-[10px] font-medium mt-1"><i :class="couponValid ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i> {{ couponMessage }}</p>
                    </div>

                    <!-- Corporate Booking Toggle -->
                    <div v-if="authStore.can('booking:corporate_booking')" class="space-y-3 mb-4">
                        <div class="flex items-center gap-3 p-3 rounded-xl border transition-all"
                             :class="isCorporateBooking ? 'bg-blue-500/10 border-blue-500/40' : 'bg-slate-50/80 border-slate-200/60'">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="isCorporateBooking" @change="onCorporateToggle" class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-700 peer-checked:bg-blue-500 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all"></div>
                            </label>
                            <div>
                                <span class="text-xs font-bold" :class="isCorporateBooking ? 'text-blue-600' : 'text-slate-500'">🏢 Corporate Booking</span>
                                <span v-if="isCorporateBooking" class="block text-[10px] text-blue-300/70">Charged to corporate account</span>
                            </div>
                        </div>
                        <div v-if="isCorporateBooking" class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 block">Select Corporate Partner</label>
                            <select v-model="selectedCorporateId" class="w-full bg-slate-50 border border-blue-500/30 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-blue-500 font-bold">
                                <option value="">— Choose corporate —</option>
                                <option v-for="corp in corporatesList" :key="corp.id" :value="corp.id">
                                    {{ corp.name }} (Credit: TZS {{ numberFormat(corp.available_credit) }})
                                </option>
                            </select>
                            <div v-if="corporateCreditDeficit > 0" class="mt-2.5 bg-amber-500/15 border border-amber-500/40 rounded-xl p-3 text-amber-200 text-xs flex items-start gap-2.5 shadow-lg shadow-amber-500/5">
                                <i class="fas fa-info-circle text-amber-600 text-sm mt-0.5 shrink-0"></i>
                                <div class="w-full">
                                    <div class="font-black text-amber-600 flex justify-between">
                                        <span>Corporate Credit Deficit</span>
                                        <span class="font-mono">{{ numberFormat(corporateCreditDeficit) }} TZS</span>
                                    </div>
                                    <div class="mt-1 text-[11px] text-amber-100/90 leading-relaxed">
                                        Corporate Credit Covers: <span class="font-bold text-slate-800 font-mono">{{ numberFormat(selectedCorporateObj?.available_credit || 0) }} TZS</span><br/>
                                        Balance to Top-Up Now: <span class="font-bold text-amber-600 font-mono">{{ numberFormat(corporateCreditDeficit) }} TZS</span>
                                    </div>
                                    <div class="mt-1.5 text-[10px] text-amber-200/80 font-semibold"><i class="fas fa-wallet mr-1"></i>Please select a payment method below to collect the top-up balance.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Channel / Top-up -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 block">
                            <span v-if="isCorporateBooking && corporateCreditDeficit > 0">Select Payment Method for Top-Up (<span class="text-amber-600 font-mono">{{ numberFormat(corporateCreditDeficit) }} TZS</span>)</span>
                            <span v-else-if="isCorporateBooking">Select Payment Method / Channel</span>
                            <span v-else>Select Payment Channel</span>
                        </label>
                        <select v-model="paymentChannel" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-cyan-500 font-bold">
                            <option v-if="isCorporateBooking && corporateCreditDeficit === 0" value="corporate_account">Corporate Account (Invoiced)</option>
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
                            <span v-text="submitting ? 'Issuing Unified Tickets...' : (submitted ? 'Booking Confirmed! Opening Profile...' : '⚡ Confirm & Issue Tickets (80mm Thermal)')"></span>
                        </button>
                        <button type="button" @click="cancelHolds(); step = 1; window.scrollTo({top:0, behavior:'smooth'});" :disabled="submitting"
                                class="w-full py-3 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-800 bg-slate-100 hover:bg-slate-100 border border-slate-300 transition flex items-center justify-center gap-2">
                            <i class="fas fa-arrow-left"></i> Back to Voyage & Class Selection
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- INTERACTIVE FERRY SEAT MAP DRAWER -->
        <div v-show="showSeatMapDrawer" @click="showSeatMapDrawer = false" class="fixed inset-0 z-50 overflow-hidden bg-slate-900/40 backdrop-blur-sm flex justify-end" style="display: none;" v-cloak>
            <div @click.stop class="bg-white border-l border-slate-200 max-w-[1200px] w-full h-full flex flex-col justify-between p-6 shadow-2xl animate-slide-left overflow-y-auto">
                <!-- Drawer Header -->
                <div>
                    <div class="flex items-center justify-between border-b border-slate-200 pb-4 mb-4">
                        <div>
                            <span class="px-2.5 py-1 rounded text-[10px] font-bold uppercase tracking-wider bg-cyan-500/20 text-cyan-600 border border-cyan-500/30"
                                  v-text="activeDeckType === 'outbound' ? 'Outbound Deck Seat Map' : 'Return Deck Seat Map'"></span>
                            <h3 class="text-lg font-bold text-slate-800 mt-1.5 flex items-center gap-2 font-mono">
                                <i class="fas fa-ship text-cyan-600"></i>
                                <span v-text="activeDeckType === 'outbound' ? (selectedOutbound?.ferry_name + ' (' + selectedOutbound?.class_name + ')') : (selectedReturn?.ferry_name + ' (' + selectedReturn?.class_name + ')')"></span>
                            </h3>
                        </div>
                        <button type="button" @click="showSeatMapDrawer = false" class="text-slate-500 hover:text-slate-800 p-2"><i class="fas fa-times text-lg"></i></button>
                    </div>

                    <!-- Legend -->
                    <div class="grid grid-cols-3 gap-2 bg-slate-50 p-3 rounded-xl border border-slate-200 text-[11px] font-semibold text-slate-500 mb-6">
                        <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-emerald-500"></span> Available</div>
                        <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-500"></span> Selected</div>
                        <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-slate-700"></span> Occupied / Locked</div>
                    </div>

                    <div class="p-3 bg-cyan-500/10 border border-cyan-500/30 rounded-xl mb-4 text-xs text-cyan-600 font-bold flex items-center justify-between">
                        <span>Allocating Seat For: <strong class="text-slate-800 underline font-mono" v-text="passengers[activeDeckPaxIndex]?.name || ('Passenger #' + (activeDeckPaxIndex + 1))"></strong></span>
                        <span class="text-[10px] uppercase tracking-wider bg-white px-2 py-0.5 rounded text-cyan-600 font-mono" v-text="passengers[activeDeckPaxIndex]?.passenger_type"></span>
                    </div>

                    <!-- Interactive Graphical Seat Grid -->
                    <div v-if="!activeDeckHasLayout" class="bg-slate-50 border border-slate-200 rounded-2xl p-4 max-h-[460px] overflow-y-auto grid grid-cols-4 sm:grid-cols-6 gap-2.5">
                        <template v-for="seat in currentSeatMapList" :key="seat.id">
                            <button type="button" @click="assignSeatToPax(seat.id)"
                                    :disabled="seat.status === 'booked' || isSeatSelectedByOtherPax(seat.id)"
                                    :class="(seat.status === 'booked' || isSeatSelectedByOtherPax(seat.id)) ? 'bg-slate-100 text-slate-500 border-slate-200/80 cursor-not-allowed opacity-40' : (isSeatAssignedToActivePax(seat.id) ? 'bg-amber-500 text-slate-950 font-black border-amber-400 ring-2 ring-amber-400/40 shadow-lg' : 'bg-white hover:bg-emerald-500/20 text-emerald-600 hover:text-emerald-600 border-slate-300 hover:border-emerald-500/50 font-bold cursor-pointer')"
                                    class="p-3 rounded-xl border text-center font-mono text-xs transition-all flex flex-col items-center justify-center gap-1 shadow-xs">
                                <i class="fas fa-chair text-sm" :class="isSeatAssignedToActivePax(seat.id) ? 'text-slate-950 animate-bounce' : ''"></i>
                                <span v-text="seat.id"></span>
                            </button>
                        </template>
                    </div>
                    <div v-else class="bg-slate-50 border border-slate-200 rounded-2xl p-1 flex-1 min-h-[500px] w-full">
                        <SeatLayoutViewer 
                            :layoutJson="activeDeckLayoutJson"
                            :bookedSeats="activeDeckBookedSeats"
                            :selectedSeats="activeDeckSelectedSeats"
                            :targetClassId="activeDeckClassId"
                            :allowedRoomIds="activeDeckAllowedRoomIds"
                            :loading="activeDeckLoading"
                            @seatSelected="assignSeatToPax"
                        />
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-200 flex gap-3 mt-4">
                    <button type="button" @click="assignSeatToPax('OPEN'); showSeatMapDrawer = false;" class="w-1/3 py-3 bg-slate-100 hover:bg-slate-100 text-slate-500 font-bold rounded-xl text-xs transition">
                        Open Pool Seat
                    </button>
                    <button type="button" @click="showSeatMapDrawer = false" class="w-2/3 py-3 bg-cyan-600 hover:bg-cyan-500 text-white font-bold rounded-xl text-xs transition shadow-md shadow-cyan-500/20">
                        Done / Close Drawer
                    </button>
                </div>
            </div>
        </div>

    <!-- Thermal Print Modal / Receipt Launcher -->
    <div v-show="showReceiptModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4" v-transition style="display: none;" v-cloak>
        <div class="bg-white border border-slate-200 rounded-2xl p-6 max-w-lg w-full space-y-4 text-center relative shadow-2xl">
            <div class="w-16 h-16 rounded-full bg-emerald-500/20 border border-emerald-500/40 mx-auto flex items-center justify-center text-emerald-600 text-3xl">
                <i class="fas fa-check"></i>
            </div>
            <h3 class="text-xl font-black text-slate-800">Tickets Issued Successfully!</h3>
            <p class="text-xs text-slate-500">PNR Reference: <span class="text-cyan-600 font-bold font-mono text-sm" v-text="issuedPnr"></span></p>

            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200/80 text-left space-y-1.5 text-xs">
                <div class="flex justify-between text-slate-500 font-medium">
                    <span>Total TZS Collected:</span>
                    <span class="text-emerald-600 font-bold" v-text="issuedTzs + ' TZS'"></span>
                </div>
                <div class="flex justify-between text-slate-500 font-medium">
                    <span>Total USD Collected:</span>
                    <span class="text-amber-600 font-bold" v-text="'$' + issuedUsd + ' USD'"></span>
                </div>
            </div>

            <div class="space-y-3 pt-4">
                <button @click="issuedBookingId ? router.push('/bookings/' + issuedBookingId) : null" 
                        class="w-full py-3.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 font-extrabold text-sm text-slate-950 shadow-lg transition flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fas fa-external-link-alt"></i> Open Booking Profile & Dashboard ➔
                </button>
                <div class="flex gap-3">
                    <button @click="printReceipt()" class="flex-1 py-3 rounded-xl bg-slate-100 hover:bg-slate-100 font-bold text-xs text-cyan-600 border border-slate-300 transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fas fa-print"></i> Print Thermal Receipt (80mm)
                    </button>
                    <button @click="showReceiptModal = false; resetDesk()" class="px-4 py-3 rounded-xl bg-slate-100 hover:bg-slate-100 text-slate-500 font-bold text-xs border border-slate-300 transition cursor-pointer">
                        New Booking
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pricing & Promotions Modal -->
    <div v-show="showPricingModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4" v-transition style="display: none;" v-cloak>
        <div class="bg-white border border-slate-200 rounded-3xl p-6 max-w-2xl w-full space-y-5 relative shadow-2xl overflow-hidden max-h-[90vh] flex flex-col text-left">
            <!-- Header -->
            <div class="flex justify-between items-center border-b border-slate-200 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500/20 to-blue-500/20 border border-cyan-500/40 flex items-center justify-center text-cyan-600 text-lg shadow-inner">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-800 flex items-center gap-2">
                            <span>Fare Schedule & Active Promotions</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-cyan-500/10 text-cyan-600 border border-cyan-500/20 font-mono" v-text="activePricingLeg"></span>
                        </h3>
                        <p class="text-xs text-slate-500" v-text="activePricingVoyage ? (activePricingVoyage.ferry_name + ' (' + activePricingVoyage.departure_time + ')') : ''"></p>
                    </div>
                </div>
                <button @click="showPricingModal = false" class="text-slate-500 hover:text-slate-800 w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-100 flex items-center justify-center transition cursor-pointer">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Fares Table -->
            <div class="overflow-y-auto pr-1 space-y-4 flex-1">
                <div class="bg-slate-50 border border-slate-200/80 rounded-2xl overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/95 border-b border-slate-200 text-[11px] uppercase tracking-wider text-slate-500 font-bold font-mono">
                                <th class="py-3 px-4">Class & Deck</th>
                                <th class="py-3 px-4 text-right">Resident (Adult / Child)</th>
                                <th class="py-3 px-4 text-right">Foreigner (Adult / Child)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-xs">
                            <template v-for="cls in (activePricingVoyage ? activePricingVoyage.classes : [])" :key="cls.class_id">
                                <tr class="hover:bg-white/60 transition">
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-800" v-text="cls.class_name"></div>
                                        <div class="text-[10px] text-slate-500 font-mono" v-text="cls.available_seats + ' seats available'"></div>
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono">
                                        <div class="text-emerald-600 font-extrabold" v-text="numberFormat(cls.tzs_price) + ' TZS'"></div>
                                        <div class="text-[11px] text-emerald-500/80" v-text="numberFormat(cls.child_tzs_price || Math.round(Number(cls.tzs_price)*0.75)) + ' TZS (Child)'"></div>
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono">
                                        <div class="text-amber-600 font-extrabold" v-text="'$' + cls.usd_price + ' USD'"></div>
                                        <div class="text-[11px] text-amber-600/80" v-text="'$' + (cls.child_usd_price || Math.round(Number(cls.usd_price)*0.75)) + ' USD (Child)'"></div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Promotions & Discounts Grid -->
                <div class="space-y-2">
                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block font-mono">⚡ Active Promotions & Terminal Policies</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div class="bg-gradient-to-br from-emerald-950/30 to-slate-950 p-3 rounded-xl border border-emerald-500/20 flex items-start gap-2.5">
                            <i class="fas fa-baby text-emerald-600 mt-0.5 text-sm"></i>
                            <div>
                                <div class="font-bold text-emerald-600 text-xs">Infants & Lap Children (0-2 yrs)</div>
                                <div class="text-[11px] text-slate-500 mt-0.5">100% Free across all classes when accompanied by a paying adult ticket.</div>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-cyan-950/30 to-slate-950 p-3 rounded-xl border border-cyan-500/20 flex items-start gap-2.5">
                            <i class="fas fa-child text-cyan-600 mt-0.5 text-sm"></i>
                            <div>
                                <div class="font-bold text-cyan-600 text-xs">Child Discount (3-11 yrs)</div>
                                <div class="text-[11px] text-slate-500 mt-0.5">Automatic ~25% discount off adult base fare applied during check-in.</div>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-purple-950/30 to-slate-950 p-3 rounded-xl border border-purple-500/20 flex items-start gap-2.5">
                            <i class="fas fa-users text-purple-600 mt-0.5 text-sm"></i>
                            <div>
                                <div class="font-bold text-purple-300 text-xs">Group & Corporate Rates</div>
                                <div class="text-[11px] text-slate-500 mt-0.5">Rebates & vouchers available for tour operators (>10 pax). Contact Supervisor.</div>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-amber-950/30 to-slate-950 p-3 rounded-xl border border-amber-500/20 flex items-start gap-2.5">
                            <i class="fas fa-suitcase text-amber-600 mt-0.5 text-sm"></i>
                            <div>
                                <div class="font-bold text-amber-600 text-xs">Luggage & Cargo Policy</div>
                                <div class="text-[11px] text-slate-500 mt-0.5">25kg complimentary baggage allowance included with every VIP/Business ticket.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="border-t border-slate-200 pt-3 flex justify-end">
                <button @click="showPricingModal = false" class="px-6 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-100 text-slate-700 font-bold text-xs border border-slate-300 transition cursor-pointer">
                    Close Schedule
                </button>
            </div>
        </div>
    </div>
    </div>

    <!-- Room vs Seat Selection Modal -->
    <div v-if="showRoomModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-slate-900/60 backdrop-blur-sm animate-fade-in">
        <div class="bg-white border border-slate-200 rounded-3xl w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="p-5 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-600 border border-indigo-500/20">
                        <i class="fas fa-door-open text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-800">Booking Preference</h2>
                        <p class="text-xs text-slate-500 font-medium">How would you like to book <span class="text-indigo-600 font-bold">{{ selectedClassForModal?.class_name }}</span>?</p>
                    </div>
                </div>
                <button @click="showRoomModal = false" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition cursor-pointer">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="p-5 overflow-y-auto space-y-6">
                <!-- Option 1: Whole Room -->
                <div class="space-y-3">
                    <h3 class="text-sm font-bold text-slate-700 flex items-center gap-2">
                        <i class="fas fa-bed text-emerald-600"></i> Option A: Book Whole Room(s)
                    </h3>
                    <p class="text-xs text-slate-500">Book an entire private room. The price is fixed regardless of passenger count (up to room capacity).</p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <template v-for="room in selectedClassForModal?.rooms || []" :key="'room_'+room.id">
                            <label class="relative flex flex-col p-4 border rounded-xl transition-all duration-200"
                                   :class="[
                                        room.available_seats < room.capacity ? 'opacity-60 cursor-not-allowed border-slate-200 bg-slate-50' : 'cursor-pointer',
                                        selectedRooms.includes(room.id) ? 'border-emerald-500 bg-emerald-50/50 ring-1 ring-emerald-500/30 shadow-md shadow-emerald-500/10' : (room.available_seats === room.capacity ? 'border-slate-200 bg-white hover:border-emerald-300' : '')
                                   ]">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="font-black text-slate-700">{{ room.name }}</span>
                                    <input type="checkbox" :value="room.id" v-model="selectedRooms" :disabled="room.available_seats < room.capacity" class="w-4 h-4 text-emerald-600 bg-slate-100 border-slate-300 rounded focus:ring-emerald-500 disabled:opacity-50">
                                </div>
                                <div class="text-[11px] font-semibold flex items-center gap-1.5 mb-2"
                                     :class="room.available_seats === room.capacity ? 'text-emerald-500' : (room.available_seats > 0 ? 'text-amber-500' : 'text-red-500')">
                                    <i class="fas" :class="room.available_seats === room.capacity ? 'fa-check-circle' : (room.available_seats > 0 ? 'fa-exclamation-circle' : 'fa-times-circle')"></i>
                                    <span>{{ room.available_seats }} / {{ room.capacity }} Seats Available</span>
                                </div>
                                <div class="mt-auto pt-2 border-t border-slate-100 flex justify-between items-center">
                                    <span class="text-xs font-bold text-emerald-600">{{ numberFormat(room.tzs_price) }} TZS</span>
                                    <span class="text-[10px] text-amber-600 font-bold">${{ room.usd_price }} USD</span>
                                </div>
                            </label>
                        </template>
                    </div>
                    <div class="flex justify-end mt-3">
                        <button @click="confirmRoomSelection(true)" 
                                :disabled="selectedRooms.length === 0"
                                class="px-5 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 cursor-pointer shadow-md"
                                :class="selectedRooms.length > 0 ? 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-emerald-500/20' : 'bg-slate-100 text-slate-400 cursor-not-allowed'">
                            Book {{ selectedRooms.length }} Room(s) ➔
                        </button>
                    </div>
                </div>

                <div class="relative py-2">
                    <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="w-full border-t border-slate-200"></div>
                    </div>
                    <div class="relative flex justify-center">
                        <span class="bg-white px-3 text-xs font-bold text-slate-400 uppercase tracking-widest">OR</span>
                    </div>
                </div>

                <!-- Option 2: Individual Seats -->
                <div class="space-y-3 pb-2">
                    <h3 class="text-sm font-bold text-slate-700 flex items-center gap-2">
                        <i class="fas fa-chair text-cyan-600"></i> Option B: Book Individual Seats
                    </h3>
                    <p class="text-xs text-slate-500">Book per seat in the shared cabin layout. Pricing is calculated per passenger.</p>
                    
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl flex justify-between items-center">
                        <div>
                            <div class="text-xs font-bold text-slate-700 mb-1">Select Room for Seats</div>
                            <select v-model="selectedRoomForOptionB" class="text-sm border-slate-300 rounded-lg py-1.5 pl-3 pr-8 focus:ring-cyan-500 focus:border-cyan-500 mb-3 bg-white">
                                <option value="">-- Choose a Room --</option>
                                <template v-for="room in selectedClassForModal?.rooms || []" :key="'optb_'+room.id">
                                    <option :value="room.id" :disabled="room.available_seats === 0">{{ room.name }} ({{ room.available_seats }} seats left)</option>
                                </template>
                            </select>
                            
                            <div class="text-xs font-bold text-slate-700">Standard Seat Rate</div>
                            <div class="text-sm font-black text-cyan-600 mt-1">{{ numberFormat(selectedClassForModal?.tzs_price || 0) }} TZS <span class="text-xs text-slate-400 font-normal">per adult</span></div>
                        </div>
                        <button @click="confirmRoomSelection(false)" :disabled="!selectedRoomForOptionB" class="px-5 py-2 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-sm font-bold transition shadow-md shadow-cyan-500/20 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            Book Individual Seats ➔
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    </template>
</div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, computed, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';
import { useUiStore } from '../../stores/ui';
import SeatLayoutViewer from './SeatLayoutViewer.vue';

const authStore = useAuthStore();
const uiStore = useUiStore();
const router = useRouter();
const route = useRoute();

const notify = (message, title, type) => {
    uiStore.notify(message, title, type);
};

const hasNoFerriesAssigned = computed(() => {
    if (authStore.user?.domain === 'system_user') return false; // Admin/staff can book everything
    const assigned = authStore.user?.domain_profile?.assigned_ferries;
    return Array.isArray(assigned) && assigned.length === 0;
});

const routes = ref([]);
const idTypes = ref([]);
const outboundRouteId = ref('');
const outboundDate = ref(new Date().toISOString().split('T')[0]);
const returnRouteId = ref('');
const returnDate = ref(new Date().toISOString().split('T')[0]);
const tripType = ref('one_way');
const step = ref(1);

const outboundVoyages = ref([]);
const returnVoyages = ref([]);
const selectedOutbound = ref(null);
const selectedReturn = ref(null);

const lockIds = ref([]);
const passengers = ref([{ name: '', id_type_id: null, id_number: '', gender: 'male', nationality_type: 1, passenger_type: 'adult', passenger_type_id: 1, outbound_seat: null, outbound_seat_label: null, outbound_deck_number: null, return_seat: null, return_seat_label: null, return_deck_number: null, is_complimentary: false, complimentary_reason: '', corporate_work_id: '' }]);
const adultsCount = ref(1);
const childrenCount = ref(0);
const infantsCount = ref(0);
const infantFreeMode = ref('per_adult');
const infantFreeLimit = ref(1);

const loading = ref(false);
const holding = ref(false);
const adjustingHolds = ref(false);
const submitting = ref(false);
const submitted = ref(false);

const countdownSeconds = ref(300);
const countdownText = ref('05:00');
let countdownTimer = null;

const paymentChannel = ref('cash');
const couponCode = ref('');
const couponDiscount = ref(0);
const couponCurrency = ref('TZS');
const validatingCoupon = ref(false);
const couponValid = ref(false);
const couponMessage = ref('');

// Exchange rate state
const exchangeRateEnabled = ref(true);  // system-level config from admin
const exchangeRate = ref(2600);          // current USD→TZS rate from system config
const applyExchangeRate = ref(false);    // user-toggled state

// Corporate booking state
const isCorporateBooking = ref(false);
const selectedCorporateId = ref('');
const corporatesList = ref([]);

const onCorporateToggle = () => {
    if (isCorporateBooking.value) {
        paymentChannel.value = 'corporate_account';
        fetchCorporates();
    } else {
        paymentChannel.value = 'cash';
        selectedCorporateId.value = '';
    }
};

const fetchCorporates = async () => {
    try {
        const res = await axios.get('/portal/api/fast-booking/corporates');
        corporatesList.value = res.data || [];
    } catch (e) {
        console.error('Failed to load corporates:', e);
        corporatesList.value = [];
    }
};

const selectedCorporateObj = computed(() => {
    if (!selectedCorporateId.value) return null;
    return corporatesList.value.find(c => String(c.id) === String(selectedCorporateId.value)) || null;
});

const netTotalDueAllTzs = computed(() => {
    if (exchangeRateEnabled.value && applyExchangeRate.value) {
        return totalDueTzsConverted();
    }
    // When exchange is disabled or not applied, only count TZS portion (USD collected separately)
    return netTotalTzs();
});

const corporateCreditDeficit = computed(() => {
    if (!isCorporateBooking.value || !selectedCorporateObj.value) return 0;
    const avail = Number(selectedCorporateObj.value.available_credit) || 0;
    const req = netTotalDueAllTzs.value;
    if (avail < req && req > 0) {
        return req - avail;
    }
    return 0;
});

watch(corporateCreditDeficit, (val) => {
    if (val > 0 && paymentChannel.value === 'corporate_account') {
        paymentChannel.value = 'cash';
    } else if (val === 0 && isCorporateBooking.value) {
        paymentChannel.value = 'corporate_account';
    }
});

const bookingPreview = ref(null);
const calculatingPreview = ref(false);
let previewDebounceTimer = null;

const showReceiptModal = ref(false);
const issuedBookingId = ref(null);
const issuedPnr = ref('');
const issuedTzs = ref(0);
const issuedUsd = ref(0);
const printUrl = ref('');

const showPricingModal = ref(false);
const activePricingVoyage = ref({});
const activePricingLeg = ref('');

const showSeatMapDrawer = ref(false);
const activeDeckPaxIndex = ref(null);
const activeDeckType = ref('');
const currentSeatMapList = ref([]);
const activeDeckHasLayout = ref(false);
const activeDeckLayoutJson = ref(null);
const activeDeckBookedSeats = ref([]);
const activeDeckSelectedSeats = ref([]);
const activeDeckClassId = ref(null);
const activeDeckAllowedRoomIds = ref([]);
const activeDeckLoading = ref(false);


const pageOneViewMode = ref('side_by_side');

// Room Booking Modal State
const showRoomModal = ref(false);
const roomModalLeg = ref('outbound');
const selectedClassForModal = ref(null);
const selectedVoyageForModal = ref(null);
const selectedRooms = ref([]);
const selectedRoomForOptionB = ref('');

const csrfToken = computed(() => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
});

const aggregatedDiscounts = computed(() => {
    if (!bookingPreview.value || !bookingPreview.value.items) return [];
    
    let grouped = {};
    bookingPreview.value.items.forEach(item => {
        if (item.discount_amount > 0) {
            if (!grouped[item.discount_name]) {
                grouped[item.discount_name] = { amount: 0, count: 0, base_fare: 0 };
            }
            grouped[item.discount_name].amount += item.discount_amount;
            grouped[item.discount_name].count += 1;
            grouped[item.discount_name].base_fare += item.base_fare;
        }
    });
    
    return Object.keys(grouped).map(name => ({
        name: name,
        amount: grouped[name].amount,
        count: grouped[name].count,
        base_fare: grouped[name].base_fare
    }));
});

const releaseLocksOnUnload = () => {
    if (lockIds.value && lockIds.value.length > 0) {
        const formData = new FormData();
        formData.append('_token', csrfToken.value);
        lockIds.value.forEach(id => formData.append('lock_ids[]', id));
        navigator.sendBeacon('/portal/api/fast-booking/release-hold', formData);
    }
};

onMounted(async () => {
    // Release any orphaned locks from a previous hard refresh
    axios.post('/portal/api/fast-booking/release-hold').catch(() => {});

    await fetchRoutes();
    
    if (route.query.route_id && route.query.date) {
        outboundRouteId.value = route.query.route_id;
        outboundDate.value = route.query.date;
        await fetchAvailability();
        if (route.query.voyage_id) {
            const voyage = outboundVoyages.value.find(v => v.voyage_id == route.query.voyage_id);
            if (voyage && voyage.classes && voyage.classes.length > 0) {
                // Auto-select the lowest priced class
                const sortedClasses = [...voyage.classes].sort((a,b) => a.tzs_price - b.tzs_price);
                selectOutboundClass(voyage, sortedClasses[0]);
            }
        }
    }
    
    window.addEventListener('beforeunload', releaseLocksOnUnload);
});

onUnmounted(() => {
    window.removeEventListener('beforeunload', releaseLocksOnUnload);
    if (lockIds.value && lockIds.value.length > 0) {
        cancelHolds(false);
    }
});

watch(passengers, () => {
    triggerCalculatePreview();
}, { deep: true });

watch(step, (newVal) => {
    if (newVal === 2) {
        triggerCalculatePreview();
    }
});

const triggerCalculatePreview = () => {
    if (step.value !== 2 || !selectedOutbound.value) return;
    if (previewDebounceTimer) clearTimeout(previewDebounceTimer);
    previewDebounceTimer = setTimeout(() => {
        calculatePreview();
    }, 500);
};

const calculatePreview = async () => {
    calculatingPreview.value = true;
    try {
        let reqPassengers = [];
        let adultCount = passengers.value.filter(px => !px.is_complimentary && px.passenger_type !== 'child' && px.passenger_type !== 'infant').length;
        let infantCount = passengers.value.filter(px => !px.is_complimentary && px.passenger_type === 'infant').length;
        
        let maxFree = infantFreeMode.value === 'per_booking' 
            ? parseInt(infantFreeLimit.value) || 1 
            : adultCount * (parseInt(infantFreeLimit.value) || 1);
            
        let excessInfants = Math.max(0, infantCount - maxFree);
        let paidInfantsCount = Math.ceil(excessInfants / 2);
        let paidInfantsCharged = 0;
        let freeInfantsUsed = 0;

        // Local variable to track how many pax we've assigned to each room
        let assignedOutboundRooms = {}; // room_id -> count
        let assignedReturnRooms = {};

        for (let idx = 0; idx < passengers.value.length; idx++) {
            const p = passengers.value[idx];
            let outFare = 0;
            let retFare = null;
            let pType = p.passenger_type ? p.passenger_type.toLowerCase() : 'adult';
            let outboundRoomId = p.outbound_room_id || null;
            let returnRoomId = p.return_room_id || null;

            // Handle Outbound Room Assignment
            if (selectedOutbound.value?.is_room_based && selectedOutbound.value?.booked_rooms && selectedOutbound.value.booked_rooms.length > 0) {
                // Find a room that isn't full yet
                for (let r of selectedOutbound.value.booked_rooms) {
                    if ((assignedOutboundRooms[r.id] || 0) < r.capacity) {
                        outboundRoomId = r.id;
                        break;
                    }
                }
                if (outboundRoomId) {
                    let count = assignedOutboundRooms[outboundRoomId] || 0;
                    if (count === 0) {
                        // First passenger in this room pays the full room price
                        let roomInfo = (selectedOutbound.value.rooms || []).find(x => x.id === outboundRoomId);
                        outFare = roomInfo ? ((p.nationality_type === 1) ? roomInfo.tzs_price : roomInfo.usd_price) : 0;
                    } else {
                        // Subsequent passengers in the room pay 0
                        outFare = 0;
                    }
                    assignedOutboundRooms[outboundRoomId] = count + 1;
                } else {
                    // All rooms are full (should be prevented by UI validation)
                    outFare = 0;
                }
            }

            // Handle Return Room Assignment
            if (selectedReturn.value?.is_room_based && selectedReturn.value?.booked_rooms && selectedReturn.value.booked_rooms.length > 0) {
                for (let r of selectedReturn.value.booked_rooms) {
                    if ((assignedReturnRooms[r.id] || 0) < r.capacity) {
                        returnRoomId = r.id;
                        break;
                    }
                }
                if (returnRoomId) {
                    let count = assignedReturnRooms[returnRoomId] || 0;
                    if (count === 0) {
                        retFare = (p.nationality_type === 1) ? 
                            selectedReturn.value.booked_rooms.find(x => x.id === returnRoomId).tzs_price :
                            selectedReturn.value.booked_rooms.find(x => x.id === returnRoomId).usd_price;
                    } else {
                        retFare = 0;
                    }
                    assignedReturnRooms[returnRoomId] = count + 1;
                } else {
                    retFare = 0;
                }
            }

            // Standard Fare calculation if not using whole rooms
            if (!outboundRoomId || (selectedOutbound.value?.is_room_based && !selectedOutbound.value?.is_whole_room)) {
                if (p.is_complimentary) {
                    outFare = 0;
                } else if (pType === 'infant') {
                    let isInfantFree = false;
                    if (freeInfantsUsed < maxFree) {
                        isInfantFree = true;
                        freeInfantsUsed++;
                    } else if (paidInfantsCharged < paidInfantsCount) {
                        isInfantFree = false;
                        paidInfantsCharged++;
                    } else {
                        isInfantFree = true; // shares seat
                    }
                    outFare = isInfantFree ? 0 : (p.nationality_type === 1 ? (selectedOutbound.value.child_tzs_price || 0) : (selectedOutbound.value.child_usd_price || 0));
                } else {
                    outFare = p.nationality_type === 1 ?
                        (pType === 'child' ? (selectedOutbound.value.child_tzs_price || Math.round(selectedOutbound.value.tzs_price*0.75)) : selectedOutbound.value.tzs_price) :
                        (pType === 'child' ? (selectedOutbound.value.child_usd_price || Math.round(selectedOutbound.value.usd_price*0.75)) : selectedOutbound.value.usd_price);
                }
            }

            if (selectedReturn.value && (!returnRoomId || (selectedReturn.value?.is_room_based && !selectedReturn.value?.is_whole_room))) {
                if (p.is_complimentary) {
                    retFare = 0;
                } else if (pType === 'infant') {
                    let isInfantFree = false;
                    if (freeInfantsUsed < maxFree) { // Approximation
                        isInfantFree = true;
                    }
                    retFare = isInfantFree ? 0 : (p.nationality_type === 1 ? (selectedReturn.value.child_tzs_price || 0) : (selectedReturn.value.child_usd_price || 0));
                } else {
                    retFare = p.nationality_type === 1 ?
                        (pType === 'child' ? (selectedReturn.value.child_tzs_price || Math.round(selectedReturn.value.tzs_price*0.75)) : selectedReturn.value.tzs_price) :
                        (pType === 'child' ? (selectedReturn.value.child_usd_price || Math.round(selectedReturn.value.usd_price*0.75)) : selectedReturn.value.usd_price);
                }
            }

            reqPassengers.push({
                passenger_index: idx,
                outbound_route_id: outboundRouteId.value,
                outbound_class_id: selectedOutbound.value.class_id,
                outbound_room_id: outboundRoomId || p.outbound_room_id || null,
                outbound_fare: outFare,
                passenger_type_id: p.passenger_type === 'child' ? 2 : (p.passenger_type === 'infant' ? 3 : 1),
                gender: p.gender || 'male',
                return_route_id: selectedReturn.value ? returnRouteId.value : null,
                return_class_id: selectedReturn.value ? selectedReturn.value.class_id : null,
                return_room_id: returnRoomId,
                return_fare: retFare,
                currency: p.nationality_type === 2 ? 'USD' : 'TZS'
            });
        }
        
        const res = await axios.post('/portal/api/fast-booking/preview', {
            passengers: reqPassengers
        });
        
        if (res.data.status === 'success') {
            bookingPreview.value = res.data.preview;
        }
    } catch (e) {
        console.error(e);
    } finally {
        calculatingPreview.value = false;
    }
};

const fetchRoutes = async () => {
    try {
        const res = await axios.get('/portal/api/fast-booking/routes');
        if (res.data.status === 'success') {
            routes.value = res.data.routes;
            idTypes.value = res.data.id_types || [];
            if (res.data.infant_free_mode !== undefined) {
                infantFreeMode.value = res.data.infant_free_mode;
            }
            if (res.data.infant_free_limit !== undefined) {
                infantFreeLimit.value = parseInt(res.data.infant_free_limit) || 1;
            }
            // Exchange rate config from system settings
            if (res.data.exchange_rate_enabled !== undefined) {
                exchangeRateEnabled.value = !!res.data.exchange_rate_enabled;
            }
            if (res.data.exchange_rate !== undefined) {
                exchangeRate.value = parseFloat(res.data.exchange_rate) || 2600;
            }
            if (routes.value.length > 0 && !outboundRouteId.value) {
                outboundRouteId.value = routes.value[0].id;
                await fetchAvailability();
            }
        }
    } catch (e) {
        console.error(e);
    }
};

const todayDateStr = computed(() => {
    let d = new Date();
    let y = d.getFullYear();
    let m = String(d.getMonth() + 1).padStart(2, '0');
    let dayNum = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${dayNum}`;
});

const outboundDateStrip = computed(() => {
    let list = [];
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    let curr = new Date(todayDateStr.value + 'T00:00:00');
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
    return list;
});

const returnDateStrip = computed(() => {
    let list = [];
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    let baseDate = outboundDate.value ? outboundDate.value : todayDateStr.value;
    let curr = new Date(baseDate + 'T00:00:00');
    let today = new Date(todayDateStr.value + 'T00:00:00');
    for(let i=0; i<8; i++) {
        let d = new Date(curr);
        d.setDate(curr.getDate() + i);
        let y = d.getFullYear();
        let m = String(d.getMonth() + 1).padStart(2, '0');
        let dayNum = String(d.getDate()).padStart(2, '0');
        let dateStr = `${y}-${m}-${dayNum}`;
        
        let diffDays = Math.round((d - today) / (1000 * 60 * 60 * 24));
        let dayName = days[d.getDay()];
        if(diffDays === 0) dayName = 'Today';
        else if(diffDays === 1) dayName = 'Tomorrow';

        list.push({
            dateStr: dateStr,
            dayName: dayName,
            shortDate: `${months[d.getMonth()]} ${d.getDate()}`
        });
    }
    return list;
});

const filteredReturnVoyages = computed(() => {
    if(!selectedOutbound.value) return returnVoyages.value;
    if(outboundDate.value !== returnDate.value) return returnVoyages.value;
    const outTime = selectedOutbound.value.departure_time;
    return returnVoyages.value.filter(v => v.departure_time > outTime);
});

watch(outboundDate, (newVal) => {
    if(newVal && returnDate.value && new Date(returnDate.value) < new Date(newVal)) {
        returnDate.value = newVal;
    }
});

const setTripType = (type) => {
    tripType.value = type;
    pageOneViewMode.value = 'side_by_side';
    if(lockIds.value.length > 0) {
        if(!confirm("Changing trip type will clear current seat holds and roster. Continue?")) return;
        cancelHolds();
    }
    selectedOutbound.value = null;
    selectedReturn.value = null;
    if(type === 'round_trip') {
        onOutboundRouteChanged();
    } else {
        fetchAvailability();
    }
};

const onOutboundRouteChanged = () => {
    autoSetReturnRoute();
    fetchAvailability();
};

const autoSetReturnRoute = () => {
    // Generic reverse route detection using origin/destination port swap
    const currentRoute = routes.value.find(r => r.id == outboundRouteId.value);
    if(currentRoute) {
        const reverseRoute = routes.value.find(r => 
            r.origin_port_id == currentRoute.destination_port_id && 
            r.destination_port_id == currentRoute.origin_port_id
        );
        if(reverseRoute) {
            returnRouteId.value = reverseRoute.id;
        } else {
            // Fallback: if no reverse route found, use different route or same route
            const otherRoute = routes.value.find(r => r.id != outboundRouteId.value);
            returnRouteId.value = otherRoute ? otherRoute.id : outboundRouteId.value;
        }
    }
};

const fetchAvailability = async () => {
    loading.value = true;
    try {
        let url = `/portal/api/fast-booking/availability?route_id=${outboundRouteId.value}&date=${outboundDate.value}`;
        if(tripType.value === 'round_trip') {
            url += `&return_route_id=${returnRouteId.value}&return_date=${returnDate.value}`;
        }
        const res = await axios.get(url);
        if(res.data.status === 'success') {
            outboundVoyages.value = (res.data.outbound_voyages || []).map(v => ({...v, showClassSelection: false}));
            returnVoyages.value = (res.data.return_voyages || []).map(v => ({...v, showClassSelection: false}));
        }
    } catch(e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const refreshSchedules = () => fetchAvailability();

const selectOutboundClass = (voyage, cls) => {
    if(cls.available_seats <= 0) return;
    
    if (cls.is_room_based && cls.rooms && cls.rooms.length > 0) {
        selectedClassForModal.value = cls;
        selectedVoyageForModal.value = voyage;
        roomModalLeg.value = 'outbound';
        selectedRooms.value = []; // Reset
        showRoomModal.value = true;
        return;
    }

    commitOutboundClass(voyage, cls);
};

const commitOutboundClass = (voyage, cls, rooms = [], isWholeRoom = false, individualRoomId = null) => {
    if(lockIds.value.length > 0) {
        if(!confirm("Selecting a different schedule will release current holds. Continue?")) return;
        cancelHolds();
    }
    selectedOutbound.value = {
        voyage_id: voyage.voyage_id,
        class_id: cls.class_id,
        class_name: cls.class_name,
        tzs_price: cls.tzs_price,
        usd_price: cls.usd_price,
        child_tzs_price: cls.child_tzs_price,
        child_usd_price: cls.child_usd_price,
        departure_time: voyage.departure_time,
        ferry_name: voyage.ferry_name,
        has_layout: voyage.has_layout,
        is_room_based: cls.is_room_based,
        booked_rooms: rooms,
        is_whole_room: isWholeRoom,
        individual_room_id: individualRoomId
    };
    showRoomModal.value = false;
};

const selectReturnClass = (voyage, cls) => {
    if(cls.available_seats <= 0) return;
    
    if (cls.is_room_based && cls.rooms && cls.rooms.length > 0) {
        selectedClassForModal.value = cls;
        selectedVoyageForModal.value = voyage;
        roomModalLeg.value = 'return';
        selectedRooms.value = []; // Reset
        showRoomModal.value = true;
        return;
    }

    commitReturnClass(voyage, cls);
};

const confirmRoomSelection = (bookWholeRooms) => {
    let voyage = selectedVoyageForModal.value;
    let cls = selectedClassForModal.value;
    let rooms = [];
    let individualRoomId = null;
    
    if (bookWholeRooms && selectedRooms.value.length > 0) {
        rooms = cls.rooms.filter(r => selectedRooms.value.includes(r.id));
    } else if (!bookWholeRooms) {
        individualRoomId = selectedRoomForOptionB.value;
    }
    
    if (roomModalLeg.value === 'outbound') {
        commitOutboundClass(voyage, cls, rooms, bookWholeRooms, individualRoomId);
    } else {
        commitReturnClass(voyage, cls, rooms, bookWholeRooms, individualRoomId);
    }
};

const commitReturnClass = (voyage, cls, rooms = [], isWholeRoom = false, individualRoomId = null) => {
    if(lockIds.value.length > 0) {
        if(!confirm("Selecting a different schedule will release current holds. Continue?")) return;
        cancelHolds();
    }
    selectedReturn.value = {
        voyage_id: voyage.voyage_id,
        class_id: cls.class_id,
        class_name: cls.class_name,
        tzs_price: cls.tzs_price,
        usd_price: cls.usd_price,
        child_tzs_price: cls.child_tzs_price,
        child_usd_price: cls.child_usd_price,
        departure_time: voyage.departure_time,
        ferry_name: voyage.ferry_name,
        has_layout: voyage.has_layout,
        is_room_based: cls.is_room_based,
        booked_rooms: rooms,
        is_whole_room: isWholeRoom,
        individual_room_id: individualRoomId
    };
    showRoomModal.value = false;
};

const isReadyToHold = () => {
    if(lockIds.value.length > 0) return false;
    if(!selectedOutbound.value) return false;
    if(tripType.value === 'round_trip' && !selectedReturn.value) return false;
    return true;
};

const getSelectionSummaryText = () => {
    let txt = `Outbound: ${outboundDate.value} at ${selectedOutbound.value?.departure_time} (${selectedOutbound.value?.ferry_name} - ${selectedOutbound.value?.class_name})`;
    if(tripType.value === 'round_trip' && selectedReturn.value) {
        txt += ` | Return: ${returnDate.value} at ${selectedReturn.value?.departure_time} (${selectedReturn.value?.ferry_name} - ${selectedReturn.value?.class_name})`;
    }
    return txt;
};

const getMaxPassengers = () => {
    let maxOut = 50;
    if (selectedOutbound.value?.is_room_based) {
        if (selectedOutbound.value.is_whole_room && selectedOutbound.value.booked_rooms && selectedOutbound.value.booked_rooms.length > 0) {
            maxOut = selectedOutbound.value.booked_rooms.reduce((sum, r) => sum + r.capacity, 0);
        } else if (!selectedOutbound.value.is_whole_room && selectedOutbound.value.individual_room_id) {
            let cls = outboundVoyages.value.flatMap(v => v.classes).find(c => c.class_id === selectedOutbound.value.class_id);
            if (cls && cls.rooms) {
                let r = cls.rooms.find(x => x.id == selectedOutbound.value.individual_room_id);
                if (r) maxOut = r.available_seats;
            }
        }
    }
    let maxRet = 50;
    if (selectedReturn.value?.is_room_based) {
        if (selectedReturn.value.is_whole_room && selectedReturn.value.booked_rooms && selectedReturn.value.booked_rooms.length > 0) {
            maxRet = selectedReturn.value.booked_rooms.reduce((sum, r) => sum + r.capacity, 0);
        } else if (!selectedReturn.value.is_whole_room && selectedReturn.value.individual_room_id) {
            let cls = returnVoyages.value.flatMap(v => v.classes).find(c => c.class_id === selectedReturn.value.class_id);
            if (cls && cls.rooms) {
                let r = cls.rooms.find(x => x.id == selectedReturn.value.individual_room_id);
                if (r) maxRet = r.available_seats;
            }
        }
    }
    return Math.min(maxOut, maxRet) || 50;
};

const acquireTemporaryHolds = async () => {
    if(!isReadyToHold() || holding.value) return;
    holding.value = true;
    try {
        let payload = {
            voyage_id: selectedOutbound.value.voyage_id,
            ferry_class_id: selectedOutbound.value.class_id,
            quantity: passengers.value.length,
            is_whole_room: selectedOutbound.value.is_whole_room ? 1 : 0,
            _token: csrfToken.value
        };
        
        if (selectedOutbound.value.is_room_based) {
            payload.room_id = selectedOutbound.value.is_whole_room 
                ? (selectedOutbound.value.booked_rooms[0]?.id || null) 
                : selectedOutbound.value.individual_room_id;
        }

        if(tripType.value === 'round_trip' && selectedReturn.value) {
            payload.return_voyage_id = selectedReturn.value.voyage_id;
            payload.return_ferry_class_id = selectedReturn.value.class_id;
            
            if (selectedReturn.value.is_room_based) {
                payload.is_whole_room = selectedReturn.value.is_whole_room ? 1 : 0;
                payload.return_room_id = selectedReturn.value.is_whole_room 
                    ? (selectedReturn.value.booked_rooms[0]?.id || null) 
                    : selectedReturn.value.individual_room_id;
            }
        }

        const res = await axios.post(`/portal/api/fast-booking/hold`, payload);
        if(res.data.status === 'success') {
            lockIds.value = res.data.lock_ids || [];
            startCountdown(300);
            step.value = 2;
            adultsCount.value = passengers.value.filter(p => p.passenger_type === 'adult').length;
            childrenCount.value = passengers.value.filter(p => p.passenger_type === 'child').length;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            notify(res.data.message || 'Could not acquire inventory hold.', 'Hold Failed', 'error');
        }
    } catch(e) {
        console.error(e);
        const errorMessage = e.response?.data?.message || e.message || 'Could not acquire inventory hold.';
        notify(errorMessage, 'Hold Failed', 'error');
    } finally {
        holding.value = false;
    }
};

const startCountdown = (seconds) => {
    if(countdownTimer) clearInterval(countdownTimer);
    countdownSeconds.value = seconds;
    updateCountdownText();
    countdownTimer = setInterval(() => {
        countdownSeconds.value--;
        if(countdownSeconds.value <= 0) {
            clearInterval(countdownTimer);
            notify("Inventory hold expired (5 minutes). Re-select schedule to check in.", 'Hold Expired', 'error');
            cancelHolds();
        } else {
            updateCountdownText();
        }
    }, 1000);
};

const updateCountdownText = () => {
    let m = Math.floor(countdownSeconds.value / 60);
    let s = countdownSeconds.value % 60;
    countdownText.value = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
};

const onAdultsCountChanged = () => {
    let oldAdults = passengers.value.filter(p => p.passenger_type === 'adult').length;
    let target = parseInt(adultsCount.value);
    if(isNaN(target) || target < 1) target = 1;
    if(target + childrenCount.value + infantsCount.value > 50) target = Math.max(1, 50 - childrenCount.value - infantsCount.value);
    adultsCount.value = target;

    if(target > oldAdults) {
        let diff = target - oldAdults;
        for(let i = 0; i < diff; i++) {
            passengers.value.push({ name: '', id_type_id: null, id_number: '', gender: 'male', nationality_type: 1, passenger_type: 'adult', passenger_type_id: 1, outbound_seat: null, outbound_seat_label: null, outbound_deck_number: null, return_seat: null, return_seat_label: null, return_deck_number: null, is_complimentary: false, complimentary_reason: '', corporate_work_id: '' });
        }
    } else if(target < oldAdults) {
        let diff = oldAdults - target;
        for(let i = 0; i < diff; i++) {
            for(let j = passengers.value.length - 1; j >= 0; j--) {
                if(passengers.value[j].passenger_type === 'adult') {
                    passengers.value.splice(j, 1);
                    break;
                }
            }
        }
    }
    if(step.value === 2) adjustManifestHolds(oldAdults, 'adult');
};

const incrementAdults = () => {
    if(passengers.value.length >= getMaxPassengers()) {
        notify("Maximum passenger capacity reached for this selection.", 'Limit Reached', 'error');
        return;
    }
    adultsCount.value = (parseInt(adultsCount.value) || 0) + 1;
    onAdultsCountChanged();
};

const decrementAdults = () => {
    let curr = parseInt(adultsCount.value) || 0;
    if(curr <= 1) return;
    adultsCount.value = curr - 1;
    onAdultsCountChanged();
};

const onChildrenCountChanged = () => {
    let oldChildren = passengers.value.filter(p => p.passenger_type === 'child').length;
    let target = parseInt(childrenCount.value);
    if(isNaN(target) || target < 0) target = 0;
    if(target + adultsCount.value + infantsCount.value > 50) target = Math.max(0, 50 - adultsCount.value - infantsCount.value);
    childrenCount.value = target;

    if(target > oldChildren) {
        let diff = target - oldChildren;
        for(let i = 0; i < diff; i++) {
            passengers.value.push({ name: '', id_type_id: null, id_number: '', gender: 'male', nationality_type: 1, passenger_type: 'child', passenger_type_id: 2, outbound_seat: null, outbound_seat_label: null, outbound_deck_number: null, return_seat: null, return_seat_label: null, return_deck_number: null, is_complimentary: false, complimentary_reason: '', corporate_work_id: '' });
        }
    } else if(target < oldChildren) {
        let diff = oldChildren - target;
        for(let i = 0; i < diff; i++) {
            for(let j = passengers.value.length - 1; j >= 0; j--) {
                if(passengers.value[j].passenger_type === 'child') {
                    passengers.value.splice(j, 1);
                    break;
                }
            }
        }
    }
    if(step.value === 2) adjustManifestHolds(oldChildren, 'child');
};

const incrementChildren = () => {
    if(passengers.value.length >= getMaxPassengers()) {
        notify("Maximum passenger capacity reached for this selection.", 'Limit Reached', 'error');
        return;
    }
    childrenCount.value = (parseInt(childrenCount.value) || 0) + 1;
    onChildrenCountChanged();
};

const decrementChildren = () => {
    let curr = parseInt(childrenCount.value) || 0;
    if(curr <= 0) return;
    childrenCount.value = curr - 1;
    onChildrenCountChanged();
};

const onInfantsCountChanged = () => {
    let oldInfants = passengers.value.filter(p => p.passenger_type === 'infant').length;
    let target = parseInt(infantsCount.value);
    if(isNaN(target) || target < 0) target = 0;
    if(target === 0 && adultsCount.value === 0 && childrenCount.value === 0) {
        adultsCount.value = 1;
        onAdultsCountChanged();
        return;
    }
    let maxPax = getMaxPassengers();
    if(target + adultsCount.value + childrenCount.value > maxPax) target = Math.max(0, maxPax - adultsCount.value - childrenCount.value);
    infantsCount.value = target;

    if(target > oldInfants) {
        let diff = target - oldInfants;
        for(let i = 0; i < diff; i++) {
            passengers.value.push({ name: '', id_type_id: null, id_number: '', gender: 'male', nationality_type: 1, passenger_type: 'infant', passenger_type_id: 3, outbound_seat: null, outbound_seat_label: null, outbound_deck_number: null, return_seat: null, return_seat_label: null, return_deck_number: null, is_complimentary: false, complimentary_reason: '', corporate_work_id: '' });
        }
    } else if(target < oldInfants) {
        let diff = oldInfants - target;
        for(let i = 0; i < diff; i++) {
            for(let j = passengers.value.length - 1; j >= 0; j--) {
                if(passengers.value[j].passenger_type === 'infant') {
                    passengers.value.splice(j, 1);
                    break;
                }
            }
        }
    }
    if(step.value === 2) adjustManifestHolds(oldInfants, 'infant');
};

const incrementInfants = () => {
    if(passengers.value.length >= getMaxPassengers()) {
        notify("Maximum passenger capacity reached for this selection.", 'Limit Reached', 'error');
        return;
    }
    infantsCount.value = (parseInt(infantsCount.value) || 0) + 1;
    onInfantsCountChanged();
};

const decrementInfants = () => {
    let curr = parseInt(infantsCount.value) || 0;
    if(curr <= 0) return;
    if(curr <= 1 && adultsCount.value === 0 && childrenCount.value === 0) return;
    infantsCount.value = curr - 1;
    onInfantsCountChanged();
};

const adjustManifestHolds = async (revertCount = null, revertType = null) => {
    if(adjustingHolds.value || !selectedOutbound.value) return;
    adjustingHolds.value = true;
    try {
        let adultCountForLimit = adultsCount.value;
        let maxFreeInfants = infantFreeMode.value === 'per_booking' 
            ? parseInt(infantFreeLimit.value) || 1
            : adultCountForLimit * (parseInt(infantFreeLimit.value) || 1);
        let excessInfants = Math.max(0, infantsCount.value - maxFreeInfants);
        let paidInfantsCount = Math.ceil(excessInfants / 2);
        let billableQuantity = adultsCount.value + childrenCount.value + paidInfantsCount;
        
        let payload = {
            voyage_id: selectedOutbound.value.voyage_id,
            ferry_class_id: selectedOutbound.value.class_id,
            quantity: billableQuantity || 1, // At least hold 1 if calculating
            lock_ids: lockIds.value || [],
            is_whole_room: selectedOutbound.value.is_whole_room ? 1 : 0,
            _token: csrfToken.value
        };
        
        if (selectedOutbound.value.is_room_based) {
            payload.room_id = selectedOutbound.value.is_whole_room 
                ? (selectedOutbound.value.booked_rooms[0]?.id || null) 
                : selectedOutbound.value.individual_room_id;
        }

        if(tripType.value === 'round_trip' && selectedReturn.value) {
            payload.return_voyage_id = selectedReturn.value.voyage_id;
            payload.return_ferry_class_id = selectedReturn.value.class_id;
            
            if (selectedReturn.value.is_room_based) {
                payload.is_whole_room = selectedReturn.value.is_whole_room ? 1 : 0;
                payload.return_room_id = selectedReturn.value.is_whole_room 
                    ? (selectedReturn.value.booked_rooms[0]?.id || null) 
                    : selectedReturn.value.individual_room_id;
            }
        }
        const res = await axios.post(`/portal/api/fast-booking/hold`, payload);
        if(res.data.status === 'success') {
            lockIds.value = res.data.lock_ids || [];
            startCountdown(300);
        } else {
            notify(res.data.message || 'Not enough remaining seats in this class to add more passengers.', 'Hold Error', 'error');
            if(revertCount !== null && revertType !== null) {
                if(revertType === 'adult') {
                    adultsCount.value = revertCount;
                    onAdultsCountChanged();
                } else if(revertType === 'child') {
                    childrenCount.value = revertCount;
                    onChildrenCountChanged();
                } else if(revertType === 'infant') {
                    infantsCount.value = revertCount;
                    onInfantsCountChanged();
                }
            } else if(passengers.value.length > 1) {
                passengers.value.pop();
                adultsCount.value = passengers.value.filter(p => p.passenger_type === 'adult').length;
                childrenCount.value = passengers.value.filter(p => p.passenger_type === 'child').length;
                infantsCount.value = passengers.value.filter(p => p.passenger_type === 'infant').length;
            }
        }
    } catch(e) {
        console.error(e);
        const errorMessage = e.response?.data?.message || 'Error syncing inventory locks.';
        notify(errorMessage, 'Hold Sync Error', 'error');
    } finally {
        adjustingHolds.value = false;
    }
};

const countByNationality = (type) => {
    return passengers.value.filter(p => p.nationality_type === type).length;
};

const calculateSubtotalTzs = () => {
    let sum = 0;
    if(!selectedOutbound.value) return 0;
    let adultCount = passengers.value.filter(p => !p.is_complimentary && p.passenger_type !== 'child' && p.passenger_type !== 'infant').length;
    let infantCount = passengers.value.filter(p => !p.is_complimentary && p.passenger_type === 'infant').length;
    
    let maxFreeInfants = infantFreeMode.value === 'per_booking' 
        ? parseInt(infantFreeLimit.value) || 1 
        : adultCount * (parseInt(infantFreeLimit.value) || 1);
        
    let excessInfants = Math.max(0, infantCount - maxFreeInfants);
    let paidInfantsCount = Math.ceil(excessInfants / 2);
    let paidInfantsCharged = 0;
    let freeInfantsUsed = 0;

    let billedOutboundRooms = {};
    let billedReturnRooms = {};
    let assignedOutboundRooms = {};
    let assignedReturnRooms = {};

    for(let pax of passengers.value) {
        if(pax.is_complimentary) continue;
        let pType = pax.passenger_type ? pax.passenger_type.toLowerCase() : 'adult';
        let cls = selectedOutbound.value;
        let retCls = selectedReturn.value;

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
        if (tripType.value === 'round_trip' && retCls?.is_room_based && retCls?.is_whole_room && retCls?.booked_rooms) {
            for (let r of retCls.booked_rooms) {
                if ((assignedReturnRooms[r.id] || 0) < r.capacity) {
                    returnRoomId = r.id;
                    assignedReturnRooms[r.id] = (assignedReturnRooms[r.id] || 0) + 1;
                    break;
                }
            }
        }

        if(pax.nationality_type === 1) {
            // Outbound
            if (cls?.is_room_based && cls?.is_whole_room && outboundRoomId) {
                if (!billedOutboundRooms[outboundRoomId]) {
                    billedOutboundRooms[outboundRoomId] = true;
                    let rObj = (cls.rooms || cls.booked_rooms || []).find(r => r.id === outboundRoomId);
                    sum += parseFloat(rObj?.tzs_price || 0);
                }
            } else {
                if(pType === 'infant') {
                    if(freeInfantsUsed < maxFreeInfants) {
                        freeInfantsUsed++;
                    } else if(paidInfantsCharged < paidInfantsCount) {
                        sum += parseFloat(cls.child_tzs_price || 0);
                        paidInfantsCharged++;
                    }
                } else {
                    sum += (pType === 'child') ? parseFloat(cls.child_tzs_price || 0) : parseFloat(cls.tzs_price || 0);
                }
            }

            // Return
            if(tripType.value === 'round_trip' && retCls) {
                if (retCls?.is_room_based && retCls?.is_whole_room && returnRoomId) {
                    if (!billedReturnRooms[returnRoomId]) {
                        billedReturnRooms[returnRoomId] = true;
                        let rObj = (retCls.rooms || retCls.booked_rooms || []).find(r => r.id === returnRoomId);
                        sum += parseFloat(rObj?.tzs_price || 0);
                    }
                } else {
                    if(pType === 'infant') {
                        // For return, infants are already counted in outbound max limit logic
                        // If they were charged outbound, they must be charged return
                        if (paidInfantsCharged > 0 && freeInfantsUsed >= maxFreeInfants) {
                            sum += parseFloat(retCls.child_tzs_price || 0);
                        }
                    } else {
                        sum += (pType === 'child') ? parseFloat(retCls.child_tzs_price || 0) : parseFloat(retCls.tzs_price || 0);
                    }
                }
            }
        }
    }
    return sum;
};

const calculateSubtotalUsd = () => {
    let sum = 0;
    if(!selectedOutbound.value) return 0;
    let adultCount = passengers.value.filter(p => !p.is_complimentary && p.passenger_type !== 'child' && p.passenger_type !== 'infant').length;
    let infantCount = passengers.value.filter(p => !p.is_complimentary && p.passenger_type === 'infant').length;
    
    let maxFreeInfants = infantFreeMode.value === 'per_booking' 
        ? parseInt(infantFreeLimit.value) || 1 
        : adultCount * (parseInt(infantFreeLimit.value) || 1);
        
    let excessInfants = Math.max(0, infantCount - maxFreeInfants);
    let paidInfantsCount = Math.ceil(excessInfants / 2);
    let paidInfantsCharged = 0;
    let freeInfantsUsed = 0;

    let billedOutboundRooms = {};
    let billedReturnRooms = {};
    let assignedOutboundRooms = {};
    let assignedReturnRooms = {};

    for(let pax of passengers.value) {
        if(pax.is_complimentary) continue;
        let pType = pax.passenger_type ? pax.passenger_type.toLowerCase() : 'adult';
        let cls = selectedOutbound.value;
        let retCls = selectedReturn.value;

        let outboundRoomId = pax.outbound_room_id || null;
        let returnRoomId = pax.return_room_id || null;

        if (cls?.is_room_based && cls?.is_whole_room && cls?.booked_rooms) {
            for (let r of cls.booked_rooms) {
                if ((assignedOutboundRooms[r.id] || 0) < r.capacity) {
                    outboundRoomId = r.id;
                    assignedOutboundRooms[r.id] = (assignedOutboundRooms[r.id] || 0) + 1;
                    break;
                }
            }
        } else if (outboundRoomId) {
            assignedOutboundRooms[outboundRoomId] = (assignedOutboundRooms[outboundRoomId] || 0) + 1;
        }
        if (tripType.value === 'round_trip' && retCls?.is_room_based && retCls?.is_whole_room && retCls?.booked_rooms) {
            for (let r of retCls.booked_rooms) {
                if ((assignedReturnRooms[r.id] || 0) < r.capacity) {
                    returnRoomId = r.id;
                    assignedReturnRooms[r.id] = (assignedReturnRooms[r.id] || 0) + 1;
                    break;
                }
            }
        }

        if(pax.nationality_type === 2) {
            // Outbound
            if (cls?.is_room_based && cls?.is_whole_room && outboundRoomId) {
                if (!billedOutboundRooms[outboundRoomId]) {
                    billedOutboundRooms[outboundRoomId] = true;
                    let rObj = (cls.rooms || cls.booked_rooms || []).find(r => r.id === outboundRoomId);
                    sum += parseFloat(rObj?.usd_price || 0);
                }
            } else {
                if(pType === 'infant') {
                    if(freeInfantsUsed < maxFreeInfants) {
                        freeInfantsUsed++;
                    } else if(paidInfantsCharged < paidInfantsCount) {
                        sum += parseFloat(cls.child_usd_price || 0);
                        paidInfantsCharged++;
                    }
                } else {
                    sum += (pType === 'child') ? parseFloat(cls.child_usd_price || 0) : parseFloat(cls.usd_price || 0);
                }
            }

            // Return
            if(tripType.value === 'round_trip' && retCls) {
                if (retCls?.is_room_based && retCls?.is_whole_room && returnRoomId) {
                    if (!billedReturnRooms[returnRoomId]) {
                        billedReturnRooms[returnRoomId] = true;
                        let rObj = (retCls.rooms || retCls.booked_rooms || []).find(r => r.id === returnRoomId);
                        sum += parseFloat(rObj?.usd_price || 0);
                    }
                } else {
                    if(pType === 'infant') {
                        if (paidInfantsCharged > 0 && freeInfantsUsed >= maxFreeInfants) {
                            sum += parseFloat(retCls.child_usd_price || 0);
                        }
                    } else {
                        sum += (pType === 'child') ? parseFloat(retCls.child_usd_price || 0) : parseFloat(retCls.usd_price || 0);
                    }
                }
            }
        }
    }
    return sum;
};

const validateCoupon = async () => {
    if(!couponCode.value || couponCode.value.trim() === '') {
        couponDiscount.value = 0;
        couponValid.value = false;
        couponMessage.value = '';
        return;
    }
    
    validatingCoupon.value = true;
    couponMessage.value = '';
    
    let baseAmount = calculateSubtotalTzs();
    let currency = 'TZS';
    
    if (calculateSubtotalUsd() > 0 && calculateSubtotalTzs() === 0) {
        baseAmount = calculateSubtotalUsd();
        currency = 'USD';
    }
    
    try {
        const res = await axios.post('/api/v1/portal/coupons/validate', {
            code: couponCode.value,
            amount: baseAmount,
            currency: currency
        });
        
        if (res.data.valid) {
            couponDiscount.value = res.data.data.discount_amount;
            couponCurrency.value = res.data.data.currency || 'TZS';
            couponValid.value = true;
            couponMessage.value = 'Coupon applied successfully!';
        } else {
            couponDiscount.value = 0;
            couponValid.value = false;
            couponMessage.value = res.data.message || 'Invalid coupon.';
        }
    } catch (e) {
        couponDiscount.value = 0;
        couponValid.value = false;
        couponMessage.value = e.response?.data?.message || 'Invalid coupon.';
    } finally {
        validatingCoupon.value = false;
    }
};
const netTotalTzs = () => {
    let base = calculateSubtotalTzs();
    let discount = bookingPreview.value?.total_discount_tzs || 0;
    let finalAmt = Math.max(0, base - discount);
    if (couponCurrency.value === 'TZS') {
        finalAmt = Math.max(0, finalAmt - couponDiscount.value);
    }
    return finalAmt;
};

const netTotalUsd = () => {
    let base = calculateSubtotalUsd();
    let discount = bookingPreview.value?.total_discount_usd || 0;
    let finalAmt = Math.max(0, base - discount);
    if (couponCurrency.value === 'USD') {
        finalAmt = Math.max(0, finalAmt - couponDiscount.value);
    }
    return finalAmt;
};

const totalDueTzsConverted = () => {
    return Math.max(0, netTotalTzs() + Math.round(netTotalUsd() * exchangeRate.value));
};
const submitBooking = async () => {
    for(let pax of passengers.value) {
        if(!pax.name || pax.name.trim() === '') {
            notify("Please enter full name for every passenger.", 'Validation Error', 'error');
            return;
        }
    }

    if (authStore.isAgent || authStore.isAssociate) {
        const totalTzs = (exchangeRateEnabled.value && applyExchangeRate.value) ? totalDueTzsConverted() : netTotalTzs();
        const availableBalance = Number(authStore.wallet?.balance || 0) + Number(authStore.wallet?.credit_limit || 0);
        if (totalTzs > availableBalance) {
            notify(`Total due is ${numberFormat(totalTzs)} TZS but your available balance (including credit) is ${numberFormat(availableBalance)} TZS.`, 'Insufficient Wallet Balance', 'error');
            return;
        }
    }

    if(submitting.value || submitted.value) return;
    submitting.value = true;

    // Validate corporate booking requirements
    if (isCorporateBooking.value) {
        if (!selectedCorporateId.value) {
            notify('Please select a corporate partner.', 'Validation Error', 'error');
            submitting.value = false;
            return;
        }

        for (let pax of passengers.value) {
            if (!pax.corporate_work_id || pax.corporate_work_id.trim() === '') {
                notify('Please enter the Work ID for every passenger in a corporate booking.', 'Validation Error', 'error');
                submitting.value = false;
                return;
            }
        }
    }

    // Validate complimentary reasons
    for (let pax of passengers.value) {
        if (pax.is_complimentary && (!pax.complimentary_reason || pax.complimentary_reason.trim() === '')) {
            notify('Please enter a reason for every complimentary ticket.', 'Validation Error', 'error');
            submitting.value = false;
            return;
        }
    }

    try {
        let assignedOutboundRooms = {};
        let assignedReturnRooms = {};

        let mappedPassengers = passengers.value.map((p, idx) => {
            let outboundRoomId = null;
            let returnRoomId = null;

            if (selectedOutbound.value?.is_room_based && selectedOutbound.value?.booked_rooms && selectedOutbound.value.booked_rooms.length > 0) {
                for (let r of selectedOutbound.value.booked_rooms) {
                    if ((assignedOutboundRooms[r.id] || 0) < r.capacity) {
                        outboundRoomId = r.id;
                        assignedOutboundRooms[r.id] = (assignedOutboundRooms[r.id] || 0) + 1;
                        break;
                    }
                }
            }

            if (selectedReturn.value?.is_room_based && selectedReturn.value?.booked_rooms && selectedReturn.value.booked_rooms.length > 0) {
                for (let r of selectedReturn.value.booked_rooms) {
                    if ((assignedReturnRooms[r.id] || 0) < r.capacity) {
                        returnRoomId = r.id;
                        assignedReturnRooms[r.id] = (assignedReturnRooms[r.id] || 0) + 1;
                        break;
                    }
                }
            }

            return {
                ...p,
                passenger_type_id: p.passenger_type === 'child' ? 2 : (p.passenger_type === 'infant' ? 3 : 1),
                gender: p.gender || 'male',
                outbound_room_id: outboundRoomId || p.outbound_room_id || null,
                outbound_deck_number: p.outbound_deck_number || null,
                is_whole_room: selectedOutbound.value?.is_whole_room ? 1 : 0,
                return_room_id: returnRoomId || p.return_room_id || null,
                return_deck_number: p.return_deck_number || null,
                return_is_whole_room: selectedReturn.value?.is_whole_room ? 1 : 0
            };
        });

        let payload = {
            lock_ids: lockIds.value,
            trip_type: tripType.value,
            payment_channel: (isCorporateBooking.value && corporateCreditDeficit.value === 0) ? 'corporate_account' : paymentChannel.value,
            coupon_code: couponCode.value,
            voyage_id: selectedOutbound.value.voyage_id,
            ferry_class_id: selectedOutbound.value.class_id,
            passengers: mappedPassengers,
            apply_exchange_rate: applyExchangeRate.value ? 1 : 0,
            _token: csrfToken.value
        };
        if (isCorporateBooking.value) {
            payload.corporate_id = selectedCorporateId.value;
        }
        if(tripType.value === 'round_trip' && selectedReturn.value) {
            payload.return_voyage_id = selectedReturn.value.voyage_id;
            payload.return_ferry_class_id = selectedReturn.value.class_id;
        }

        const res = await axios.post(`/portal/api/fast-booking/store`, payload);
        if(res.data.status === 'success') {
            if(countdownTimer) clearInterval(countdownTimer);
            submitted.value = true;
            issuedBookingId.value = res.data.booking_id;
            issuedPnr.value = res.data.booking_reference;
            issuedTzs.value = res.data.total_tzs;
            issuedUsd.value = res.data.total_usd;
            printUrl.value = res.data.print_url;
            showReceiptModal.value = true;

            setTimeout(() => {
                if(issuedBookingId.value) {
                    router.push(`/bookings/${issuedBookingId.value}`);
                }
            }, 600);
        } else if (res.data.status === 'pending_approval') {
            if(countdownTimer) clearInterval(countdownTimer);
            submitted.value = true;
            notify(res.data.message || 'Booking routed for Supervisor Approval.', 'Approval Required', 'info');
            setTimeout(() => {
                window.location.href = `/portal/dashboard`;
            }, 1000);
        } else {
            notify(res.data.message || 'Failed to issue tickets.', 'Booking Failed', 'error');
        }
    } catch(e) {
        console.error(e);
        const errorMessage = e.response?.data?.message || 'Failed to submit booking due to network/server error.';
        notify(errorMessage, 'Booking Error', 'error');
    } finally {
        if(!submitted.value) {
            submitting.value = false;
        }
    }
};

const cancelHolds = async (returnToPageOne = true) => {
    if(countdownTimer) clearInterval(countdownTimer);

    // Release locks on the server
    if (lockIds.value && lockIds.value.length > 0) {
        try {
            await axios.post('/portal/api/fast-booking/release-hold', {
                lock_ids: lockIds.value
            });
        } catch(e) {
            console.error('Failed to release holds:', e);
        }
    }

    lockIds.value = [];
    countdownSeconds.value = 300;
    if(returnToPageOne) {
        step.value = 1;
    }
};

const resetDesk = () => {
    cancelHolds(true);
    selectedOutbound.value = null;
    selectedReturn.value = null;
    adultsCount.value = 1;
    childrenCount.value = 0;
    infantsCount.value = 0;
    passengers.value = [{ name: '', id_type_id: null, id_number: '', gender: 'male', nationality_type: 1, passenger_type: 'adult', passenger_type_id: 1, outbound_seat: null, outbound_seat_label: null, outbound_deck_number: null, return_seat: null, return_seat_label: null, return_deck_number: null, is_complimentary: false, complimentary_reason: '', corporate_work_id: '' }];
    fetchAvailability();
};

const openSeatMapDrawer = async (paxIndex, legType) => {
    activeDeckPaxIndex.value = paxIndex;
    activeDeckType.value = legType;
    showSeatMapDrawer.value = true;
    
    let voyage = legType === 'outbound' ? selectedOutbound.value : selectedReturn.value;
    if(!voyage) return;
    
    activeDeckClassId.value = voyage.class_id;
    activeDeckHasLayout.value = voyage.has_layout;
    
    if (voyage.is_room_based) {
        if (voyage.is_whole_room) {
            activeDeckAllowedRoomIds.value = voyage.booked_rooms ? voyage.booked_rooms.map(r => r.id) : [];
        } else {
            activeDeckAllowedRoomIds.value = voyage.individual_room_id ? [voyage.individual_room_id] : [];
        }
    } else {
        activeDeckAllowedRoomIds.value = [];
    }
    
    // Calculate currently selected seats in this session
    let selected = [];
    passengers.value.forEach((p, idx) => {
        let sId = legType === 'outbound' ? p.outbound_seat : p.return_seat;
        if(sId) selected.push(sId);
    });
    activeDeckSelectedSeats.value = selected;

    if (voyage.has_layout) {
        // Fetch layout & booked seats
        activeDeckLoading.value = true;
        try {
            const res = await axios.get('/portal/api/fast-booking/layout-status', {
                params: {
                    voyage_id: voyage.voyage_id,
                    class_id: voyage.class_id
                }
            });
            activeDeckLayoutJson.value = res.data.layout_json;
            activeDeckBookedSeats.value = res.data.booked_seats;
        } catch(e) {
            console.error('Error fetching layout status', e);
            notify('Error loading interactive layout', 'Error', 'error');
            activeDeckHasLayout.value = false; // Fallback to list
        } finally {
            activeDeckLoading.value = false;
        }
    } else {
        // Legacy mock grid fallback
        let list = [];
        let prefix = voyage.class_name ? (voyage.class_name.substring(0,3).toUpperCase() + '-') : 'SEAT-';
        let capacity = 48;
        for(let i=1; i<=capacity; i++) {
            let sId = prefix + (i < 10 ? '0'+i : i);
            list.push({ id: sId, status: 'available' });
        }
        currentSeatMapList.value = list;
    }
};

const syncSeatLocks = async () => {
    let voyage = activeDeckType.value === 'outbound' ? selectedOutbound.value : selectedReturn.value;
    if (!voyage) return;
    
    let seats = [];
    passengers.value.forEach(p => {
        let sId = activeDeckType.value === 'outbound' ? p.outbound_seat : p.return_seat;
        let rId = activeDeckType.value === 'outbound' ? p.outbound_room_id : p.return_room_id;
        if(sId) seats.push({ seat: sId, room_id: rId || null });
    });
    
    try {
        const res = await axios.post('/portal/api/fast-booking/sync-seat-locks', {
            voyage_id: voyage.voyage_id,
            class_id: voyage.class_id,
            seats: seats
        });
        
        if (res.data.status === 'partial' || res.data.status === 'error') {
            const acquired = res.data.acquired || [];
            passengers.value.forEach((p, idx) => {
                let sId = activeDeckType.value === 'outbound' ? p.outbound_seat : p.return_seat;
                if (sId && sId !== 'OPEN' && !acquired.includes(sId)) {
                    if (activeDeckType.value === 'outbound') {
                        p.outbound_seat = null;
                        p.outbound_seat_label = null;
                    } else {
                        p.return_seat = null;
                        p.return_seat_label = null;
                    }
                    notify(`Seat ${sId} was just taken by another agent! Please select a different seat.`, 'Seat Unavailable', 'warning');
                }
            });
        }
    } catch (e) {
        console.error('Error syncing seat locks', e);
        notify('Failed to verify seat availability.', 'Connection Error', 'error');
    }
};

const assignSeatToPax = async (seatData) => {
    if(activeDeckPaxIndex.value === null) return;
    let pax = passengers.value[activeDeckPaxIndex.value];
    
    let seatId    = typeof seatData === 'object' ? seatData.id   : seatData;
    let seatLabel = typeof seatData === 'object' ? seatData.label : seatData;
    let deckNum   = typeof seatData === 'object' ? (seatData.deck_number || null) : null;
    let roomId    = typeof seatData === 'object' ? (seatData.room_id || null) : null;

    if(activeDeckType.value === 'outbound') {
        pax.outbound_seat         = seatId === 'OPEN' ? null : seatId;
        pax.outbound_seat_label   = seatId === 'OPEN' ? null : seatLabel;
        pax.outbound_deck_number  = seatId === 'OPEN' ? null : deckNum;
        pax.outbound_room_id      = seatId === 'OPEN' ? null : roomId;
    } else {
        pax.return_seat           = seatId === 'OPEN' ? null : seatId;
        pax.return_seat_label     = seatId === 'OPEN' ? null : seatLabel;
        pax.return_deck_number    = seatId === 'OPEN' ? null : deckNum;
        pax.return_room_id        = seatId === 'OPEN' ? null : roomId;
    }
    showSeatMapDrawer.value = false;
    await syncSeatLocks();
};

const isSeatAssignedToActivePax = (seatId) => {
    if (!seatId || seatId === 'OPEN' || activeDeckPaxIndex.value === null) return false;
    let pax = passengers.value[activeDeckPaxIndex.value];
    return activeDeckType.value === 'outbound' ? pax.outbound_seat === seatId : pax.return_seat === seatId;
};

const isSeatSelectedByOtherPax = (seatId) => {
    if (!seatId || seatId === 'OPEN' || activeDeckPaxIndex.value === null) return false;
    return passengers.value.some((p, idx) => {
        if (idx === activeDeckPaxIndex.value) return false;
        return (activeDeckType.value === 'outbound' ? p.outbound_seat === seatId : p.return_seat === seatId);
    });
};

const isSeatSelectedByAnyPax = (seatId) => {
    if(!seatId || seatId === 'OPEN') return false;
    return passengers.value.some(p => p.outbound_seat === seatId || p.return_seat === seatId);
};

const printReceipt = () => {
    if(printUrl.value) {
        window.open(printUrl.value, '_blank', 'width=400,height=700');
    }
};

const numberFormat = (num) => {
    if(!num) return '0';
    return Number(num).toLocaleString();
};

const viewClassPricing = (voyage, leg) => {
    let classesList = [];
    if(voyage && voyage.classes) {
        classesList = voyage.classes.map(c => ({
            class_name: c.class_name,
            total_capacity: c.total_capacity,
            available_seats: c.available_seats,
            tzs_price: c.tzs_price || 0,
            usd_price: c.usd_price || 0,
            child_tzs_price: c.child_tzs_price || Math.round((Number(c.tzs_price) || 0) * 0.75),
            child_usd_price: c.child_usd_price || Math.round((Number(c.usd_price) || 0) * 0.75)
        }));
    }
    activePricingVoyage.value = {
        ferry_name: voyage ? voyage.ferry_name : '',
        departure_time: voyage ? voyage.departure_time : '',
        classes: classesList
    };
    activePricingLeg.value = leg;
    showPricingModal.value = true;
};

</script>
<style scoped>
[v-cloak] { display: none !important; }
</style>
