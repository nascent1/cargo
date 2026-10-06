<template>
  <div class="space-y-4">
    <!-- Step Progress Bar -->
    <BookingStepBar :steps="stepLabels" :currentStep="step" />

    <!-- Error Banner -->
    <div v-if="cargo.error" class="flex items-center gap-3 px-5 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700">
      <i class="fas fa-exclamation-circle text-red-500"></i>
      <span class="text-sm font-medium">{{ cargo.error }}</span>
      <button @click="cargo.error = null" class="ml-auto text-red-400 hover:text-red-600"><i class="fas fa-times"></i></button>
    </div>

    <!-- Pre-selected Service Summary (when coming from dashboard quick-book) -->
    <div v-if="preSelectedService && step >= 1" class="bg-brand-50 rounded-xl border border-brand-200 px-5 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="h-9 w-9 rounded-lg bg-brand-100 flex items-center justify-center text-brand-600"><i class="fas fa-ship"></i></div>
        <div>
          <p class="text-sm font-bold text-brand-800">{{ preSelectedService.route?.name || 'Selected Voyage' }}</p>
          <p class="text-xs text-brand-600">
            <i class="fas fa-clock mr-1"></i>{{ preSelectedService.departure_time }}
            <span class="mx-1">·</span>
            <i class="fas fa-anchor mr-1"></i>{{ preSelectedService.transport_unit?.name || preSelectedService.transportUnit?.name || 'Vessel' }}
          </p>
        </div>
      </div>
      <button @click="clearPreSelection" class="text-xs font-semibold text-brand-600 hover:text-brand-800 px-3 py-1.5 rounded-lg hover:bg-brand-100 transition-colors">
        <i class="fas fa-exchange-alt mr-1"></i> Change
      </button>
    </div>

    <!-- ─── STEP 1: Select Service ─── -->
    <transition name="fade" mode="out-in">
    <div v-if="step === 1 && !preSelectedService" key="step1" class="space-y-4">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden relative">
        <div class="absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 2px 2px, #e2e8f0 1px, transparent 0); background-size: 24px 24px;"></div>
        <div class="relative px-6 py-6 flex flex-col sm:flex-row sm:items-center justify-between gap-5">
          <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-brand-100 text-brand-700 text-[10px] font-extrabold tracking-widest uppercase mb-3 border border-brand-200">
              <i class="fas fa-ship"></i> Step 1
            </div>
            <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Select Transport Service</h2>
            <p class="text-sm text-slate-500 mt-1 font-medium max-w-sm">Choose your preferred vessel and departure date.</p>
          </div>
          <div class="relative shrink-0 bg-slate-50 p-4 rounded-xl border border-slate-200">
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Departure Date</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none"><i class="fas fa-calendar-alt text-brand-500 text-sm"></i></div>
              <input type="date" v-model="selectedDate" @change="onDateChange" :min="minDate"
                class="w-full sm:w-56 pl-10 pr-4 py-2.5 text-sm font-bold rounded-xl border border-slate-300 bg-white text-slate-900 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/20 outline-none transition-all" />
            </div>
          </div>
        </div>
      </div>

      <div v-if="cargo.loading" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-10 text-center text-slate-400">
        <i class="fas fa-circle-notch fa-spin text-2xl text-brand-400 mb-3 block"></i>
        <p class="text-sm">Fetching available services...</p>
      </div>

      <div v-else-if="!cargo.availableServices || cargo.availableServices.length === 0"
        class="bg-white rounded-2xl border border-slate-200 shadow-sm p-10 text-center text-slate-400">
        <i class="fas fa-ship text-3xl mb-3 block text-slate-300"></i>
        <p class="text-sm font-medium">No services available on this date.</p>
        <p class="text-xs mt-1">Try a different date.</p>
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <button v-for="svc in cargo.availableServices" :key="svc.id" @click="selectService(svc)"
          :class="['group text-left p-5 rounded-2xl border-2 transition-all duration-200',
            cargo.bookingState.serviceId === svc.id ? 'border-brand-500 bg-brand-50 shadow-lg shadow-brand-100/50' : 'border-slate-200 bg-white hover:border-brand-300 hover:shadow-md']">
          <div class="flex items-start justify-between gap-3">
            <div class="h-10 w-10 rounded-xl bg-brand-100 text-brand-600 flex items-center justify-center shrink-0"><i class="fas fa-ship"></i></div>
            <div v-if="cargo.bookingState.serviceId === svc.id" class="h-5 w-5 rounded-full bg-brand-600 flex items-center justify-center shrink-0 mt-0.5">
              <i class="fas fa-check text-white text-[10px]"></i>
            </div>
          </div>
          <h3 class="font-bold text-slate-800 mt-3">{{ svc.route?.name || 'N/A' }}</h3>
          <div class="flex flex-wrap gap-x-3 gap-y-1 mt-2">
            <span class="text-xs text-slate-500 flex items-center gap-1"><i class="fas fa-clock text-slate-400"></i> {{ svc.departure_time }}</span>
            <span class="text-xs text-slate-500 flex items-center gap-1"><i class="fas fa-anchor text-slate-400"></i> {{ svc.transport_unit?.name || svc.transportUnit?.name || 'N/A' }}</span>
          </div>
          <div v-if="svc.cargo_inventory" class="mt-4 grid grid-cols-3 gap-2">
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-2 text-center">
              <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Lane M</div>
              <div class="text-xs font-mono font-bold text-slate-700">{{ svc.cargo_inventory.used_lane_meters?.toLocaleString() || 0 }} / {{ svc.cargo_inventory.total_lane_meters?.toLocaleString() || 0 }}</div>
            </div>
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-2 text-center">
              <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Weight KG</div>
              <div class="text-xs font-mono font-bold text-slate-700">{{ svc.cargo_inventory.used_weight_kg?.toLocaleString() || 0 }} / {{ svc.cargo_inventory.total_weight_capacity_kg?.toLocaleString() || 0 }}</div>
            </div>
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-2 text-center">
              <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Volume CBM</div>
              <div class="text-xs font-mono font-bold text-slate-700">{{ svc.cargo_inventory.used_volume_cbm?.toLocaleString() || 0 }} / {{ svc.cargo_inventory.total_volume_capacity_cbm?.toLocaleString() || 0 }}</div>
            </div>
          </div>
        </button>
      </div>

      <div class="flex justify-end">
        <button v-if="cargo.bookingState.serviceId" @click="step = 2"
          class="inline-flex items-center gap-2 px-6 py-3 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl transition-colors shadow-sm shadow-brand-200">
          Next: Add Items <i class="fas fa-arrow-right text-xs"></i>
        </button>
      </div>
    </div>
    </transition>

    <!-- ─── STEP 2: Cargo Items ─── -->
    <transition name="fade" mode="out-in">
    <div v-if="step === 2" key="step2" class="space-y-4">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center justify-between">
        <div>
          <h2 class="text-lg font-extrabold text-slate-800 tracking-tight">Cargo Items</h2>
          <p class="text-sm text-slate-500 mt-0.5">Add one or more items to this shipment.</p>
        </div>
        <!-- Running Totals -->
        <div class="hidden sm:flex items-center gap-4">
          <div class="text-right">
            <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Total Weight</p>
            <p class="text-sm font-mono font-extrabold text-slate-800">{{ totalWeight.toLocaleString() }} <span class="text-slate-400 text-xs">KG</span></p>
          </div>
          <div class="h-8 w-px bg-slate-200"></div>
          <div class="text-right">
            <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Total Volume</p>
            <p class="text-sm font-mono font-extrabold text-slate-800">{{ totalVolume.toFixed(2) }} <span class="text-slate-400 text-xs">CBM</span></p>
          </div>
          <div class="h-8 w-px bg-slate-200"></div>
          <div class="text-right">
            <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Items</p>
            <p class="text-sm font-mono font-extrabold text-brand-600">{{ cargo.bookingState.items.length }}</p>
          </div>
        </div>
      </div>

      <div v-for="(item, idx) in cargo.bookingState.items" :key="idx"
        class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
          <span class="text-sm font-bold text-slate-700">Item {{ idx + 1 }}</span>
          <div class="flex items-center gap-1.5">
            <button @click="duplicateItem(idx)" class="h-7 w-7 rounded-lg flex items-center justify-center text-brand-500 bg-brand-50 hover:bg-brand-100 border border-brand-100 transition-colors text-xs" title="Duplicate item">
              <i class="fas fa-copy"></i>
            </button>
            <button @click="removeItem(idx)" class="h-7 w-7 rounded-lg flex items-center justify-center text-red-500 bg-red-50 hover:bg-red-100 border border-red-100 transition-colors text-xs" title="Remove item">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-slate-600">Cargo Type *</label>
            <select v-model="item.cargo_type_id"
              class="w-full px-3 py-2.5 text-sm rounded-xl border border-slate-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-slate-50 focus:bg-white">
              <option :value="null" disabled>Select type...</option>
              <option v-for="ct in cargo.cargoTypes" :key="ct.id" :value="ct.id">{{ ct.icon || '📦' }} {{ ct.name }}</option>
            </select>
          </div>
          <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-slate-600">Description</label>
            <input type="text" v-model="item.description" placeholder="Brief description..."
              class="w-full px-3 py-2.5 text-sm rounded-xl border border-slate-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-slate-50 focus:bg-white" />
          </div>
          <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-slate-600">Weight (kg)</label>
            <div class="relative">
              <input type="number" v-model.number="item.weight_kg" min="0" step="0.1"
                class="w-full pl-3 pr-10 py-2.5 text-sm rounded-xl border border-slate-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-slate-50 focus:bg-white" />
              <span class="absolute inset-y-0 right-3 flex items-center text-xs text-slate-400 font-semibold">kg</span>
            </div>
          </div>
          <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-slate-600">Volume (cbm)</label>
            <div class="relative">
              <input type="number" v-model.number="item.volume_cbm" min="0" step="0.01"
                class="w-full pl-3 pr-14 py-2.5 text-sm rounded-xl border border-slate-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-slate-50 focus:bg-white" />
              <span class="absolute inset-y-0 right-3 flex items-center text-xs text-slate-400 font-semibold">cbm</span>
            </div>
          </div>
        </div>

        <label class="flex items-center gap-3 cursor-pointer select-none">
          <div class="relative">
            <input type="checkbox" v-model="item.is_fragile" class="sr-only peer">
            <div class="w-9 h-5 rounded-full bg-slate-300 peer-checked:bg-amber-500 transition-colors"></div>
            <div class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></div>
          </div>
          <span class="text-sm font-medium text-slate-700">🔶 Mark as Fragile</span>
        </label>
      </div>

      <button @click="addItem"
        class="w-full py-3.5 rounded-2xl border-2 border-dashed border-brand-300 text-brand-600 hover:bg-brand-50 text-sm font-semibold transition-colors flex items-center justify-center gap-2">
        <i class="fas fa-plus text-xs"></i> Add Another Item
      </button>

      <!-- Mobile running totals -->
      <div class="sm:hidden bg-slate-50 rounded-xl border border-slate-200 p-4 flex items-center justify-around">
        <div class="text-center">
          <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Weight</p>
          <p class="text-sm font-mono font-bold text-slate-800">{{ totalWeight }} KG</p>
        </div>
        <div class="text-center">
          <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Volume</p>
          <p class="text-sm font-mono font-bold text-slate-800">{{ totalVolume.toFixed(2) }} CBM</p>
        </div>
        <div class="text-center">
          <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Items</p>
          <p class="text-sm font-mono font-bold text-brand-600">{{ cargo.bookingState.items.length }}</p>
        </div>
      </div>

      <div class="flex justify-between gap-3">
        <button @click="goBackFromStep2"
          class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
          <i class="fas fa-arrow-left text-xs"></i> Back
        </button>
        <button @click="calculateAndLock" :disabled="cargo.bookingState.items.length === 0"
          class="inline-flex items-center gap-2 px-6 py-2.5 bg-brand-600 hover:bg-brand-700 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold text-sm rounded-xl transition-colors shadow-sm shadow-brand-200">
          Next: Details <i class="fas fa-arrow-right text-xs"></i>
        </button>
      </div>
    </div>
    </transition>

    <!-- ─── STEP 3: Customer Details ─── -->
    <transition name="fade" mode="out-in">
    <div v-if="step === 3" key="step3" class="space-y-4">

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Sender -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
          <div class="flex items-center gap-2 mb-1">
            <div class="h-8 w-8 rounded-lg bg-brand-100 flex items-center justify-center text-brand-600 text-sm"><i class="fas fa-user"></i></div>
            <h3 class="font-bold text-slate-800">Sender</h3>
          </div>
          <input type="text" v-model="cargo.bookingState.sender.name" placeholder="Full Name *"
            class="w-full px-3 py-2.5 text-sm rounded-xl border border-slate-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-slate-50 focus:bg-white" />
          <input type="tel" v-model="cargo.bookingState.sender.phone" placeholder="Phone Number *"
            class="w-full px-3 py-2.5 text-sm rounded-xl border border-slate-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-slate-50 focus:bg-white" />
          <input type="email" v-model="cargo.bookingState.sender.email" placeholder="Email (Optional)"
            class="w-full px-3 py-2.5 text-sm rounded-xl border border-slate-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-slate-50 focus:bg-white" />
          <input type="text" v-model="cargo.bookingState.sender.id_number" placeholder="ID Number (Optional)"
            class="w-full px-3 py-2.5 text-sm rounded-xl border border-slate-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-slate-50 focus:bg-white" />
        </div>

        <!-- Receiver -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
          <div class="flex items-center gap-2 mb-1">
            <div class="h-8 w-8 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-600 text-sm"><i class="fas fa-user-check"></i></div>
            <h3 class="font-bold text-slate-800">Receiver</h3>
          </div>
          <input type="text" v-model="cargo.bookingState.receiver.name" placeholder="Full Name *"
            class="w-full px-3 py-2.5 text-sm rounded-xl border border-slate-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-slate-50 focus:bg-white" />
          <input type="tel" v-model="cargo.bookingState.receiver.phone" placeholder="Phone Number *"
            class="w-full px-3 py-2.5 text-sm rounded-xl border border-slate-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-slate-50 focus:bg-white" />
        </div>
      </div>

      <!-- Fare Summary -->
      <div v-if="fareBreakdown" class="bg-gradient-to-r from-brand-600 to-brand-700 rounded-2xl p-5 text-white shadow-lg shadow-brand-200/50">
        <div class="flex items-center justify-between mb-1">
          <p class="text-xs font-bold uppercase tracking-widest text-brand-200">Fare Summary</p>
          <span v-if="holdCountdown" class="text-[10px] font-mono font-bold bg-white/20 px-2 py-0.5 rounded-full border border-white/30">
            <i class="fas fa-clock mr-1"></i> {{ holdCountdown }}
          </span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-base font-semibold text-brand-100">Total Due</span>
          <div class="text-right">
            <span class="text-3xl font-extrabold font-mono tabular-nums">{{ fareBreakdown.net_amount.toLocaleString() }}</span>
            <span class="text-lg font-semibold text-brand-200 ml-2">{{ fareBreakdown.currency }}</span>
          </div>
        </div>
      </div>

      <div class="flex justify-between gap-3">
        <button @click="step = 2"
          class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
          <i class="fas fa-arrow-left text-xs"></i> Back
        </button>
        <button @click="goToPayment"
          class="inline-flex items-center gap-2 px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm rounded-xl transition-colors shadow-sm shadow-brand-200">
          Next: Payment <i class="fas fa-arrow-right text-xs"></i>
        </button>
      </div>
    </div>
    </transition>

    <!-- ─── STEP 4: Review & Payment ─── -->
    <transition name="fade" mode="out-in">
    <div v-if="step === 4" key="step4" class="space-y-4">
      <PaymentPanel v-if="pendingPayment"
        :consignment-id="confirmedBooking.consignment_id"
        :payment="pendingPayment"
        :methods="cargo.paymentMethods"
        :sender-phone="cargo.bookingState.sender.phone"
        :can-cancel="true"
        @paid="onPaid"
        @cancelled="onPaymentCancelled" />
      <template v-else>
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
          <h2 class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
            <i class="fas fa-file-invoice-dollar text-brand-600"></i> Order Summary & Payment
          </h2>
          <span v-if="holdCountdown" class="text-[10px] font-mono font-bold bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full border border-amber-200">
            <i class="fas fa-clock mr-1"></i> Hold {{ holdCountdown }}
          </span>
        </div>

        <div class="p-5 space-y-4">
          <!-- Route & departure -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200/80">
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Route</p>
              <p class="text-sm font-bold text-slate-800">{{ selectedService?.route?.name || 'N/A' }}</p>
            </div>
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200/80">
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Departure</p>
              <p class="text-sm font-bold text-slate-800">
                {{ selectedService?.departure_date ? moment(selectedService.departure_date).format('DD MMM YYYY') : '—' }}
                @ {{ selectedService?.departure_time || '—' }}
                <span class="text-slate-500 font-medium">· {{ selectedService?.transport_unit?.name || 'N/A' }}</span>
              </p>
            </div>
          </div>

          <!-- Sender / Receiver -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="bg-brand-50/50 rounded-xl p-4 border border-brand-200/60">
              <p class="text-[10px] font-bold text-brand-600 uppercase tracking-widest mb-1"><i class="fas fa-arrow-up mr-1"></i> Sender</p>
              <p class="text-sm font-bold text-slate-800">{{ cargo.bookingState.sender.name }}</p>
              <p class="text-xs text-slate-500">{{ cargo.bookingState.sender.phone }}</p>
            </div>
            <div class="bg-emerald-50/50 rounded-xl p-4 border border-emerald-200/60">
              <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest mb-1"><i class="fas fa-arrow-down mr-1"></i> Receiver</p>
              <p class="text-sm font-bold text-slate-800">{{ cargo.bookingState.receiver.name }}</p>
              <p class="text-xs text-slate-500">{{ cargo.bookingState.receiver.phone }}</p>
            </div>
          </div>

          <!-- Items -->
          <div class="bg-slate-50 rounded-xl border border-slate-200/80 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200/60">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Cargo Items</span>
            </div>
            <div class="p-4 space-y-2">
              <div v-for="(line, idx) in fareBreakdown?.items || []" :key="idx" class="flex justify-between items-center text-sm py-1 gap-3">
                <span class="text-slate-700 flex items-center gap-2 min-w-0">
                  <i class="fas fa-box text-slate-400 text-xs"></i>
                  <span class="truncate">{{ cargoTypeName(line.cargo_type_id) }}: {{ cargo.bookingState.items[idx]?.description || '—' }}</span>
                  <span class="text-slate-400 shrink-0">({{ Number(line.weight_kg || 0) }}kg)</span>
                </span>
                <span class="font-bold text-slate-800 font-mono shrink-0">{{ fareBreakdown.currency }} {{ Number(line.final_fare || 0).toLocaleString() }}</span>
              </div>
            </div>
            <div class="px-4 py-3 border-t border-slate-200 bg-white flex justify-between items-center">
              <span class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Total</span>
              <span class="text-xl font-black text-brand-600 font-mono">{{ fareBreakdown?.currency }} {{ Number(fareBreakdown?.net_amount || 0).toLocaleString() }}</span>
            </div>
          </div>

          <!-- Payment Method (channels from Settings › Payment Channels) -->
          <div class="space-y-3">
            <label class="text-[11px] text-slate-500 font-bold block uppercase tracking-wider">Payment Method <span class="text-rose-500">*</span></label>
            <div v-if="cargo.paymentMethods.length === 0" class="px-4 py-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-xs font-semibold flex items-center gap-2">
              <i class="fas fa-exclamation-triangle"></i> No payment channels are ready. Contact your administrator.
            </div>
            <div v-for="group in methodGroups" :key="group.flow">
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5"><i :class="['fas', group.icon, 'mr-1']"></i>{{ group.label }}</p>
              <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                <button v-for="m in group.methods" :key="m.slug" type="button" @click="cargo.bookingState.paymentMethod = m.slug"
                  :class="['relative px-3 py-3 rounded-xl border-2 text-center transition-all',
                    cargo.bookingState.paymentMethod === m.slug ? 'border-brand-500 bg-brand-50 shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300']">
                  <span v-if="m.simulated" class="absolute top-1 right-1 px-1 rounded bg-violet-100 text-violet-700 text-[8px] font-black uppercase" title="No live credentials yet — the push is simulated (testing only)">Sim</span>
                  <span v-else-if="!m.live" class="absolute top-1 right-1 px-1 rounded bg-amber-100 text-amber-700 text-[8px] font-black uppercase" title="Credentials not set up yet — no push will be sent">Not live</span>
                  <i :class="['fas', m.icon, 'text-lg block mb-1', cargo.bookingState.paymentMethod === m.slug ? 'text-brand-600' : 'text-slate-400']"></i>
                  <span :class="['text-[11px] font-bold', cargo.bookingState.paymentMethod === m.slug ? 'text-brand-700' : 'text-slate-600']">{{ m.name }}</span>
                </button>
              </div>
            </div>
            <!-- Payer phone for the USSD push -->
            <div v-if="chosenMethod?.flow === 'ussd_push'" class="bg-brand-50/60 border border-brand-200 rounded-xl p-4">
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">Payer's phone (receives the USSD push) *</label>
              <input type="tel" v-model="cargo.bookingState.payerMsisdn" placeholder="0754 123 456"
                class="w-full sm:w-72 px-3 py-2.5 text-sm font-mono rounded-xl border border-slate-200 bg-white focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none" />
              <p :class="['text-[11px] mt-1.5', hintClass(payerHintInfo.tone)]">{{ payerHintInfo.text }}</p>
              <p v-if="!chosenMethod.live && !chosenMethod.simulated" class="mt-3 px-3 py-2 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-[11px] font-semibold">
                <i class="fas fa-exclamation-triangle mr-1"></i>{{ chosenMethod.name }} isn't live yet, so no push will be sent. The booking is saved as <strong>awaiting payment</strong> with the space held — then take the payment through another channel from the booking.
              </p>
            </div>
            <p v-else-if="chosenMethod?.flow === 'lipa_namba'" class="text-xs text-slate-500">
              <i class="fas fa-info-circle mr-1"></i>After confirming, show the customer the Lipa Namba and the amount, then enter the transaction code from the payment SMS.
            </p>
          </div>
        </div>
      </div>

      <div class="flex justify-between gap-3">
        <button @click="step = 3"
          class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
          <i class="fas fa-arrow-left text-xs"></i> Back
        </button>
        <button @click="confirmBooking" :disabled="isConfirming || !cargo.bookingState.paymentMethod"
          class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-sm rounded-xl transition-colors shadow-sm shadow-emerald-200">
          <i v-if="isConfirming" class="fas fa-circle-notch fa-spin text-xs"></i>
          <i v-else class="fas fa-check-circle text-xs"></i>
          {{ isConfirming ? 'Processing...' : confirmLabel }}
        </button>
      </div>
      </template>
    </div>
    </transition>

    <!-- ─── STEP 5: Success ─── -->
    <transition name="fade" mode="out-in">
    <div v-if="step === 5" key="step5" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-10 text-center space-y-5">
      <div class="h-20 w-20 rounded-full bg-emerald-100 flex items-center justify-center mx-auto">
        <i class="fas fa-check text-emerald-600 text-3xl"></i>
      </div>
      <div>
        <h2 class="text-2xl font-extrabold text-slate-900">Booking Confirmed &amp; Paid!</h2>
        <p class="text-slate-500 mt-2 text-sm">
          Paid via {{ paidPayment?.channel?.name || 'cash' }}<span v-if="paidPayment?.manual_reference || paidPayment?.provider_reference"> · Ref <span class="font-mono font-semibold text-slate-700">{{ paidPayment.manual_reference || paidPayment.provider_reference }}</span></span>.
        </p>
      </div>
      <div class="bg-slate-50 rounded-xl border border-slate-100 px-6 py-4 inline-block">
        <p class="text-xs text-slate-400 uppercase tracking-widest font-bold mb-1">Consignment Number</p>
        <p class="text-2xl font-extrabold font-mono text-brand-600">{{ confirmedBooking?.consignment_number }}</p>
      </div>
      <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <button @click="viewProfile"
          class="inline-flex items-center gap-2 px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm rounded-xl transition-colors">
          <i class="fas fa-external-link-alt text-xs"></i> View Profile Now
        </button>
        <button v-if="auth.can('booking:print')" @click="printReceipt"
          class="inline-flex items-center gap-2 px-6 py-2.5 bg-white border border-sky-200 hover:bg-sky-50 text-sky-700 font-semibold text-sm rounded-xl transition-colors">
          <i class="fas fa-print text-xs"></i> Print Receipt
        </button>
        <button @click="startNew"
          class="inline-flex items-center gap-2 px-6 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-sm rounded-xl transition-colors">
          <i class="fas fa-plus text-xs"></i> New Booking
        </button>
      </div>
    </div>
    </transition>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useCargoStore } from '../../stores/cargo';
import { useAuthStore } from '../../stores/auth';
import BookingStepBar from '../../components/booking/BookingStepBar.vue';
import PaymentPanel from '../../components/cargo/PaymentPanel.vue';
import { payerHint } from '../../utils/msisdn';
import moment from 'moment';
import axios from 'axios';

const cargo = useCargoStore();
const auth  = useAuthStore();
const route = useRoute();
const router = useRouter();

const step  = ref(1);
const stepLabels = ['Service', 'Items', 'Details', 'Payment', 'Done'];
const minDate        = ref(moment().format('YYYY-MM-DD'));
const selectedDate   = ref(moment().format('YYYY-MM-DD'));
const fareBreakdown  = ref(null);
const isConfirming   = ref(false);
const confirmedBooking = ref(null);
const holdCountdown  = ref('');
const preSelectedService = ref(null);
const pendingPayment = ref(null);   // push / Lipa Namba attempt being completed in step 4
const paidPayment    = ref(null);
let countdownTimer   = null;

// Computed running totals
const totalWeight = computed(() => {
  return cargo.bookingState.items.reduce((sum, item) => sum + (Number(item.weight_kg) || 0), 0);
});

const totalVolume = computed(() => {
  return cargo.bookingState.items.reduce((sum, item) => sum + (Number(item.volume_cbm) || 0), 0);
});

const selectedService = computed(() => preSelectedService.value || cargo.bookingState.service || null);

const FLOW_GROUPS = [
  { flow: 'cash', label: 'Cash', icon: 'fa-money-bill-wave' },
  { flow: 'ussd_push', label: "Mobile Money — USSD push to customer's phone", icon: 'fa-mobile-alt' },
  { flow: 'lipa_namba', label: 'Lipa Namba — customer pays, you confirm the SMS reference', icon: 'fa-store' },
];
const methodGroups = computed(() => FLOW_GROUPS
  .map(g => ({ ...g, methods: cargo.paymentMethods.filter(m => m.flow === g.flow) }))
  .filter(g => g.methods.length));
const chosenMethod = computed(() => cargo.paymentMethods.find(m => m.slug === cargo.bookingState.paymentMethod));
const payerHintInfo = computed(() => payerHint(cargo.paymentMethods, chosenMethod.value, cargo.bookingState.payerMsisdn));
const hintClass = (tone) => ({ ok: 'text-emerald-700 font-semibold', warn: 'text-amber-600', error: 'text-red-600' }[tone] || 'text-slate-500');
const confirmLabel = computed(() => chosenMethod.value && !chosenMethod.value.live && !chosenMethod.value.simulated
  ? 'Save Booking — Awaiting Payment'
  : ({
  cash: 'Confirm & Record Cash',
  ussd_push: 'Confirm & Send USSD Push',
  lipa_namba: 'Confirm & Show Lipa Namba',
}[chosenMethod.value?.flow] || 'Confirm & Book Cargo'));

// Typing another network's number while an MNO channel is selected: follow the number
watch(() => cargo.bookingState.payerMsisdn, () => {
  const h = payerHintInfo.value;
  if (h.tone === 'error' && h.match && (chosenMethod.value?.prefixes || []).length) cargo.bookingState.paymentMethod = h.match.slug;
});

const cargoTypeName = (id) => cargo.cargoTypes.find(t => t.id == id)?.name || 'Item';

const startCountdown = () => {
    if (countdownTimer) clearInterval(countdownTimer);
    countdownTimer = setInterval(() => {
        if (!cargo.bookingState.expiresAt) return;
        const diff = moment(cargo.bookingState.expiresAt).diff(moment());
        if (diff <= 0) {
            clearInterval(countdownTimer);
            holdCountdown.value = '00:00';
            cargo.error = 'Booking failed. The lock has expired. Please try again.';
            step.value = 1;
        } else {
            holdCountdown.value = moment.utc(diff).format('mm:ss');
        }
    }, 1000);
};

onMounted(async () => {
    // Always start from a clean booking — stale state from an earlier booking caused "N/A" voyages
    cargo.resetBooking();
    cargo.fetchCargoTypes();
    cargo.fetchPaymentMethods();

    // Check if we came from the dashboard quick-book with a pre-selected service
    const serviceId = route.query.service_id;
    const date = route.query.date;

    if (serviceId) {
        // Fetch the specific service to show summary
        try {
            selectedDate.value = date || moment().format('YYYY-MM-DD');
            await cargo.fetchAvailability(selectedDate.value);
            const svc = cargo.availableServices.find(s => s.id == serviceId);
            if (svc) {
                preSelectedService.value = svc;
                cargo.bookingState.serviceId = svc.id;
                cargo.bookingState.service = svc;
                cargo.bookingState.routeId = svc.route_id;
                // Skip to Step 2 directly
                step.value = 2;
                // Add a default empty item
                if (cargo.bookingState.items.length === 0) {
                    addItem();
                }
            } else {
                fetchServices();
            }
        } catch {
            fetchServices();
        }
    } else {
        fetchServices();
    }
});

const fetchServices = () => cargo.fetchAvailability(selectedDate.value);

// A voyage chosen for another date must not survive a date change
const onDateChange = () => {
    cargo.bookingState.serviceId = null;
    cargo.bookingState.service = null;
    cargo.bookingState.routeId = null;
    fetchServices();
};

const selectService = (svc) => {
    cargo.bookingState.serviceId = svc.id;
    cargo.bookingState.service   = svc;
    cargo.bookingState.routeId   = svc.route_id;
};

const clearPreSelection = () => {
    preSelectedService.value = null;
    cargo.bookingState.serviceId = null;
    cargo.bookingState.service = null;
    cargo.bookingState.routeId = null;
    step.value = 1;
    // Clear the query params
    router.replace({ query: {} });
};

const goBackFromStep2 = () => {
    if (preSelectedService.value) {
        clearPreSelection();
    } else {
        step.value = 1;
    }
};

const addItem = () => {
    cargo.bookingState.items.push({ cargo_type_id: null, description: '', weight_kg: 0, volume_cbm: 0, is_fragile: false });
};

const duplicateItem = (idx) => {
    const source = cargo.bookingState.items[idx];
    cargo.bookingState.items.push({ ...source });
};

const removeItem = (idx) => cargo.bookingState.items.splice(idx, 1);

const calculateAndLock = async () => {
    try {
        fareBreakdown.value = await cargo.calculateFare();
        await cargo.lockInventory();
        startCountdown();
        step.value = 3;
    } catch {
        if (!cargo.error) cargo.error = 'Could not lock inventory. Please check capacity.';
    }
};

const goToPayment = () => {
    const { sender, receiver } = cargo.bookingState;
    if (!sender.name || !sender.phone || !receiver.name || !receiver.phone) {
        cargo.error = 'Please fill in all required sender and receiver fields.';
        return;
    }
    cargo.error = null;
    if (!cargo.bookingState.payerMsisdn) cargo.bookingState.payerMsisdn = sender.phone;
    step.value = 4;
};

const confirmBooking = async () => {
    if (!cargo.bookingState.paymentMethod) {
        cargo.error = 'Please select a payment method.';
        return;
    }
    isConfirming.value = true;
    try {
        const res = await cargo.confirmBooking();
        // The inventory lock is now committed to the booking — the hold timer no longer applies
        if (countdownTimer) clearInterval(countdownTimer);
        holdCountdown.value = '';
        confirmedBooking.value = res;
        if (res.payment?.status === 'paid') {
            paidPayment.value = res.payment;
            step.value = 5;
        } else {
            pendingPayment.value = res.payment; // live panel: push tracker / Lipa Namba
        }
    } catch {
        // Lock may still be valid (e.g. a validation error), so keep the hold timer running
        if (!cargo.error) cargo.error = 'Booking failed. The lock may have expired. Please try again.';
    } finally {
        isConfirming.value = false;
    }
};

const onPaid = (payment) => {
    paidPayment.value = payment;
    pendingPayment.value = null;
    step.value = 5;
};

const onPaymentCancelled = () => {
    startNew();
    cargo.error = 'The booking was cancelled and its space released.';
};

const printReceipt = () => {
    if (confirmedBooking.value?.consignment_id) {
        window.open(`/portal/api/cargo/consignments/${confirmedBooking.value.consignment_id}/print`, '_blank');
    }
};

const viewProfile = () => {
    if (confirmedBooking.value?.consignment_id) {
        router.push('/cargo-bookings/' + confirmedBooking.value.consignment_id);
    }
};

const startNew = () => {
    cargo.resetBooking();
    preSelectedService.value = null;
    step.value = 1;
    fareBreakdown.value    = null;
    confirmedBooking.value = null;
    pendingPayment.value   = null;
    paidPayment.value      = null;
    router.replace({ query: {} });
};
</script>

<style scoped>
@reference "../../../../css/app.css";

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.fade-enter-from { opacity: 0; transform: translateY(8px); }
.fade-leave-to   { opacity: 0; transform: translateY(-4px); }
</style>
