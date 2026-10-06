<template>
  <div class="space-y-8">
    
    <!-- Reports Hub (Grid View) -->
    <div v-if="!activeReport">
      <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-slate-800 tracking-tight">Reports Hub 📊</h2>
        <p class="text-slate-500 mt-2">Access your operational data, sales ledgers, and manifests.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Summary Audit Card -->
        <button @click="openReport('audit')" class="text-left group bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden block w-full">
          <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
            <i class="fas fa-chart-pie text-8xl text-indigo-600"></i>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-6 border border-indigo-100/50">
            <i class="fas fa-chart-pie text-xl"></i>
          </div>
          <h3 class="text-lg font-bold text-slate-800 mb-2">Summary Audit</h3>
          <p class="text-sm text-slate-500 font-medium">Daily gross, commission, and net settlement breakdown.</p>
        </button>

        <!-- Bookings Card -->
        <button @click="openReport('bookings')" class="text-left group bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden block w-full">
          <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
            <i class="fas fa-ticket-alt text-8xl text-blue-600"></i>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mb-6 border border-blue-100/50">
            <i class="fas fa-ticket-alt text-xl"></i>
          </div>
          <h3 class="text-lg font-bold text-slate-800 mb-2">All Bookings</h3>
          <p class="text-sm text-slate-500 font-medium">Comprehensive ledger of all tickets issued.</p>
        </button>

        <!-- Cancellations Card -->
        <button @click="openReport('cancellations')" class="text-left group bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden block w-full">
          <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
            <i class="fas fa-ban text-8xl text-rose-600"></i>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mb-6 border border-rose-100/50">
            <i class="fas fa-ban text-xl"></i>
          </div>
          <h3 class="text-lg font-bold text-slate-800 mb-2">Cancellations</h3>
          <p class="text-sm text-slate-500 font-medium">Log of cancelled tickets, penalties, and refunds.</p>
        </button>

        <!-- Manifest Card -->
        <button @click="openReport('manifest')" class="text-left group bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden block w-full">
          <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
            <i class="fas fa-users text-8xl text-emerald-600"></i>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-6 border border-emerald-100/50">
            <i class="fas fa-users text-xl"></i>
          </div>
          <h3 class="text-lg font-bold text-slate-800 mb-2">Passenger Manifest</h3>
          <p class="text-sm text-slate-500 font-medium">Passenger lists with boarding status for voyages.</p>
        </button>

        <!-- Float Ledger Card (Agent Only) -->
        <button v-if="authStore.isAgent || authStore.can('report:float_ledger')" @click="openReport('ledger')" class="text-left group bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden block w-full">
          <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
            <i class="fas fa-wallet text-8xl text-amber-600"></i>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mb-6 border border-amber-100/50">
            <i class="fas fa-wallet text-xl"></i>
          </div>
          <h3 class="text-lg font-bold text-slate-800 mb-2">Float Ledger</h3>
          <p class="text-sm text-slate-500 font-medium">Dedicated transaction history for agent wallet.</p>
        </button>
      </div>
    </div>

    <!-- Specific Report View -->
    <div v-else>
      
      <!-- Breadcrumb & Title -->
      <div class="mb-8 flex justify-between items-start">
        <div>
          <div class="flex items-center gap-2 text-sm text-slate-500 font-medium mb-1">
            <button @click="closeReport" class="hover:text-brand-600 transition-colors cursor-pointer flex items-center">
              <i class="fas fa-chart-line mr-1"></i> Reports Hub
            </button>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <span class="text-slate-800">{{ currentReportTitle }}</span>
          </div>
          <h1 class="text-2xl font-bold text-slate-800">{{ currentReportTitle }}</h1>
          <p class="text-sm text-slate-500">{{ currentReportDescription }}</p>
        </div>
        
        <!-- View Toggle (Summary vs Detailed) -->
        <div v-if="['audit', 'bookings', 'cancellations'].includes(activeReport)" class="flex bg-slate-100 p-1 rounded-xl shadow-inner border border-slate-200/60">
          <button @click="viewMode = 'detailed'" :class="viewMode === 'detailed' ? 'bg-white text-slate-800 shadow-sm border-slate-200' : 'text-slate-500 hover:text-slate-700 border-transparent'" class="px-4 py-2 text-sm font-bold rounded-lg border transition-all">
            <i class="fas fa-list-ul mr-1"></i> Detailed
          </button>
          <button @click="viewMode = 'summary'" :class="viewMode === 'summary' ? 'bg-white text-slate-800 shadow-sm border-slate-200' : 'text-slate-500 hover:text-slate-700 border-transparent'" class="px-4 py-2 text-sm font-bold rounded-lg border transition-all">
            <i class="fas fa-chart-bar mr-1"></i> Summary
          </button>
        </div>
      </div>

      <!-- Filters Section -->
      <div class="bg-white rounded-2xl border border-slate-200 p-4 mb-6 shadow-sm">
        
        <!-- Top Row: Date & Route (Fixed) + Run Button -->
        <div class="flex flex-wrap gap-4 items-end">
          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Date Range</label>
            <div class="flex items-center space-x-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
              <input type="date" v-model="baseFilters.date_from" class="bg-transparent text-slate-800 text-sm outline-none w-32">
              <span class="text-slate-400 text-xs">to</span>
              <input type="date" v-model="baseFilters.date_to" class="bg-transparent text-slate-800 text-sm outline-none w-32">
            </div>
          </div>

          <div v-if="filterOptions.routes?.length">
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Route</label>
            <select v-model="baseFilters.route_id" class="bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-2 text-sm outline-none min-w-[150px]">
              <option value="">All Routes</option>
              <option v-for="r in filterOptions.routes" :key="r.id" :value="r.id">{{ r.name }}</option>
            </select>
          </div>

          <!-- Add Filter Dropdown -->
          <div class="relative flex-1" v-if="Object.keys(availableFiltersToAdd).length > 0">
            <div class="flex items-center">
              <select v-model="selectedFilterToAdd" @change="addFilterChip" class="bg-brand-50 border border-brand-200 text-brand-700 font-bold rounded-xl px-4 py-2 text-sm outline-none appearance-none pr-8 cursor-pointer hover:bg-brand-100 transition-colors">
                <option value="" disabled selected>+ Add Filter</option>
                <option v-for="(config, key) in availableFiltersToAdd" :key="key" :value="key">
                  {{ config.label }}
                </option>
              </select>
              <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-brand-600">
                <i class="fas fa-plus text-xs"></i>
              </div>
            </div>
          </div>
          
          <div class="ml-auto">
            <button @click="fetchData" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-sm font-bold transition-colors flex items-center justify-center gap-2 shadow-sm shadow-brand-200">
              <i class="fas fa-play text-xs"></i>
              Run Report
            </button>
          </div>
        </div>

        <!-- Filter Chips Row -->
        <div v-if="Object.keys(activeFilterChips).length > 0" class="flex flex-wrap gap-3 mt-4 pt-4 border-t border-slate-100">
          <div v-for="(val, key) in activeFilterChips" :key="key" class="flex items-center bg-slate-100 border border-slate-200 rounded-lg px-1 py-1 group hover:border-slate-300 transition-colors">
            <span class="text-xs font-bold text-slate-600 px-2">{{ allAvailableFilters[key].label }}:</span>
            
            <select v-model="activeFilterChips[key]" class="bg-white border border-slate-200 text-slate-800 rounded text-sm px-2 py-1 outline-none min-w-[120px] focus:ring-1 focus:ring-brand-500">
              <option value="">All</option>
              <option v-for="opt in allAvailableFilters[key].options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            
            <button @click="removeFilterChip(key)" class="ml-2 w-6 h-6 flex items-center justify-center rounded-md text-slate-400 hover:bg-rose-100 hover:text-rose-600 transition-colors">
              <i class="fas fa-times text-xs"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Action Bar -->
      <div class="flex justify-end mb-4 gap-2">
        <button v-if="viewMode === 'detailed'" @click="exportCSV" class="bg-slate-800 hover:bg-slate-900 text-white text-sm font-bold py-2 px-4 rounded-lg flex items-center gap-2 transition-colors">
          <i class="fas fa-file-csv"></i> Export CSV
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="py-12 flex justify-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-brand-600"></div>
      </div>

      <!-- Data View -->
      <div v-else>
        
        <div v-if="!hasData" class="bg-white border border-slate-200 shadow-sm rounded-2xl p-12 text-center text-slate-500">
          <i class="fas fa-folder-open text-4xl mb-4 text-slate-300"></i>
          <p>No records found for the selected criteria.</p>
        </div>

        <div v-else>
          <!-- Summary Chart View -->
          <div v-if="viewMode === 'summary'" class="bg-white border border-slate-200 shadow-sm rounded-2xl p-6 mb-6">
            <div class="h-[400px]">
              <Bar v-if="chartData" :data="chartData" :options="chartOptions" />
            </div>
          </div>

          <!-- Table View (Detailed or Summary Table) -->
          <div class="bg-white border border-slate-200 shadow-sm rounded-2xl overflow-hidden">
            
            <!-- Audit Tab -->
            <div v-if="activeReport === 'audit'">
              <div :class="authStore.isAgent ? 'grid grid-cols-1 md:grid-cols-3 gap-0 border-b border-slate-200' : 'p-6 border-b border-slate-200'">
                <div class="p-6" :class="authStore.isAgent ? 'border-r border-slate-200' : ''">
                  <div class="text-slate-500 text-[10px] font-semibold uppercase tracking-wider">Total Sales Volume</div>
                  <div class="text-2xl font-bold text-slate-800 font-mono mt-1">{{ formatCurrency(auditSummary.total_gross) }} TZS</div>
                </div>
                <div v-if="authStore.isAgent" class="p-6 border-r border-slate-200">
                  <div class="text-slate-500 text-[10px] font-semibold uppercase tracking-wider">Commission Earned</div>
                  <div class="text-2xl font-bold text-emerald-600 font-mono mt-1">{{ formatCurrency(auditSummary.total_commission) }} TZS</div>
                </div>
                <div v-if="authStore.isAgent" class="p-6">
                  <div class="text-slate-500 text-[10px] font-semibold uppercase tracking-wider">Net Settlement</div>
                  <div class="text-2xl font-bold text-brand-600 font-mono mt-1">{{ formatCurrency(auditSummary.net_settlement) }} TZS</div>
                </div>
              </div>

              <div v-if="viewMode === 'detailed'" class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                  <thead>
                    <tr class="bg-slate-50/50 text-slate-500 text-[11px] uppercase tracking-wider">
                      <th class="p-4 font-semibold border-b border-slate-200">Date/Time</th>
                      <th class="p-4 font-semibold border-b border-slate-200">Ticket #</th>
                      <th class="p-4 font-semibold border-b border-slate-200 text-right">Gross Fare</th>
                      <th v-if="authStore.isAgent" class="p-4 font-semibold border-b border-slate-200 text-right">Commission</th>
                      <th v-if="authStore.isAgent" class="p-4 font-semibold border-b border-slate-200 text-right">Net Fare</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 text-sm">
                    <tr v-for="row in reportData" :key="row.id" class="hover:bg-slate-50/50 transition-colors">
                      <td class="p-4 text-slate-600 whitespace-nowrap">{{ row.date }} <span class="text-slate-400 text-xs ml-1">{{ row.time }}</span></td>
                      <td class="p-4 font-mono font-medium text-slate-800">{{ row.ticket }}</td>
                      <td class="p-4 font-mono text-slate-800 text-right">{{ formatCurrency(row.gross) }}</td>
                      <td v-if="authStore.isAgent" class="p-4 font-mono text-emerald-600 text-right">{{ formatCurrency(row.comm) }}</td>
                      <td v-if="authStore.isAgent" class="p-4 font-mono font-bold text-brand-700 text-right">{{ formatCurrency(row.net) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Bookings Tab Detailed -->
            <div v-if="activeReport === 'bookings' && viewMode === 'detailed'" class="overflow-x-auto">
              <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                  <tr class="bg-slate-50/50 text-slate-500 text-[11px] uppercase tracking-wider">
                    <th class="p-3 font-semibold border-b border-slate-200">S/N</th>
                    <th class="p-3 font-semibold border-b border-slate-200">Booking ID</th>
                    <th class="p-3 font-semibold border-b border-slate-200">Passenger</th>
                    <th class="p-3 font-semibold border-b border-slate-200">Gender</th>
                    <th class="p-3 font-semibold border-b border-slate-200">Age</th>
                    <th class="p-3 font-semibold border-b border-slate-200">Nationality</th>
                    <th class="p-3 font-semibold border-b border-slate-200 text-right">(TSH)</th>
                    <th class="p-3 font-semibold border-b border-slate-200 text-right">(USD)</th>
                    <th class="p-3 font-semibold border-b border-slate-200 text-right">Rate</th>
                    <th class="p-3 font-semibold border-b border-slate-200">Ticket Status</th>
                    <th class="p-3 font-semibold border-b border-slate-200">Class</th>
                    <th class="p-3 font-semibold border-b border-slate-200">Journey Date</th>
                    <th class="p-3 font-semibold border-b border-slate-200">Booking Date</th>
                    <th class="p-3 font-semibold border-b border-slate-200">Route</th>
                    <th class="p-3 font-semibold border-b border-slate-200 text-right">Total (TSH)</th>
                    <th class="p-3 font-semibold border-b border-slate-200">Booking Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-[13px]">
                  <tr v-for="(row, i) in reportData" :key="row.id || i" class="hover:bg-slate-50/50 transition-colors">
                    <td class="p-3 text-slate-500">{{ i + 1 }}</td>
                    <td class="p-3 font-mono text-slate-800">{{ row.ticket }}</td>
                    <td class="p-3 text-slate-800">{{ row.passenger_name }}</td>
                    <td class="p-3 text-slate-600">{{ row.gender }}</td>
                    <td class="p-3 text-slate-600">{{ row.type }}</td>
                    <td class="p-3 text-slate-600">{{ row.nationality }}</td>
                    <td class="p-3 text-right font-mono text-slate-800">{{ formatCurrency(row.tsh_amount) }}</td>
                    <td class="p-3 text-right font-mono text-slate-800">{{ formatCurrency(row.usd_amount) }}</td>
                    <td class="p-3 text-right font-mono text-slate-800">{{ formatCurrency(row.rate) }}</td>
                    <td class="p-3 text-slate-800 capitalize">{{ row.status.replace('_', ' ') }}</td>
                    <td class="p-3 text-slate-600">{{ row.class }}</td>
                    <td class="p-3 text-slate-600">{{ row.journey_date }}</td>
                    <td class="p-3 text-slate-600">{{ row.booking_date }}</td>
                    <td class="p-3 text-slate-800">{{ row.route }}</td>
                    <td class="p-3 text-right font-mono font-bold text-slate-800">{{ formatCurrency(row.total_tsh) }}</td>
                    <td class="p-3 uppercase text-slate-800">{{ row.booking_status }}</td>
                  </tr>
                </tbody>
                <tfoot class="bg-slate-50 text-slate-900 border-t-2 border-slate-200">
                  <tr>
                    <td colspan="14" class="p-4 font-black uppercase tracking-wider text-xs text-right">Grand Total</td>
                    <td class="p-4 font-mono font-black text-right text-brand-600">{{ formatCurrency(totals.fare) }}</td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <!-- Bookings Tab Summary -->
            <div v-if="activeReport === 'bookings' && viewMode === 'summary'" class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="bg-slate-50/50 text-slate-500 text-[11px] uppercase tracking-wider">
                    <th class="p-4 font-semibold border-b border-slate-200">Grouping Metric</th>
                    <th class="p-4 font-semibold border-b border-slate-200 text-right">Total Tickets</th>
                    <th class="p-4 font-semibold border-b border-slate-200 text-right">Total Fare (TZS)</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                  <tr v-for="(row, i) in reportData" :key="i" class="hover:bg-slate-50/50 transition-colors">
                    <td class="p-4 text-slate-800 font-medium">{{ row.label }}</td>
                    <td class="p-4 font-mono text-right text-slate-800">{{ formatCurrency(row.count) }}</td>
                    <td class="p-4 font-mono text-right font-bold text-emerald-600">{{ formatCurrency(row.total_fare) }}</td>
                  </tr>
                </tbody>
                <tfoot class="bg-slate-50 text-slate-900 border-t-2 border-slate-200">
                  <tr>
                    <td class="p-4 font-black uppercase tracking-wider text-xs">Grand Total</td>
                    <td class="p-4 font-mono font-black text-right">{{ formatCurrency(totals.tix) }}</td>
                    <td class="p-4 font-mono font-black text-right text-brand-600">{{ formatCurrency(totals.fare) }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <!-- Cancellations Tab Detailed -->
            <div v-if="activeReport === 'cancellations' && viewMode === 'detailed'" class="overflow-x-auto">
              <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                  <tr class="bg-slate-50/50 text-slate-500 text-[11px] uppercase tracking-wider">
                    <th class="p-3 font-semibold border-b border-slate-200">S/N</th>
                    <th class="p-3 font-semibold border-b border-slate-200">Booking ID</th>
                    <th class="p-3 font-semibold border-b border-slate-200">Passenger</th>
                    <th class="p-3 font-semibold border-b border-slate-200">Gender</th>
                    <th class="p-3 font-semibold border-b border-slate-200">Age</th>
                    <th class="p-3 font-semibold border-b border-slate-200">Nationality</th>
                    <th class="p-3 font-semibold border-b border-slate-200 text-right">(TSH)</th>
                    <th class="p-3 font-semibold border-b border-slate-200 text-right">(USD)</th>
                    <th class="p-3 font-semibold border-b border-slate-200 text-right">Rate</th>
                    <th class="p-3 font-semibold border-b border-slate-200">Class</th>
                    <th class="p-3 font-semibold border-b border-slate-200">Journey Date</th>
                    <th class="p-3 font-semibold border-b border-slate-200">Booking Date</th>
                    <th class="p-3 font-semibold border-b border-slate-200">Route</th>
                    <th class="p-3 font-semibold border-b border-slate-200 text-right">Total Fare</th>
                    <th class="p-3 font-semibold border-b border-slate-200 text-right text-rose-600">Penalty</th>
                    <th class="p-3 font-semibold border-b border-slate-200 text-right text-emerald-600">Refund</th>
                    <th class="p-3 font-semibold border-b border-slate-200">Cancelled At</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-[13px]">
                  <tr v-for="(row, i) in reportData" :key="row.id || i" class="hover:bg-slate-50/50 transition-colors">
                    <td class="p-3 text-slate-500">{{ i + 1 }}</td>
                    <td class="p-3 font-mono text-slate-800">{{ row.ticket }}</td>
                    <td class="p-3 text-slate-800">{{ row.passenger_name }}</td>
                    <td class="p-3 text-slate-600">{{ row.gender }}</td>
                    <td class="p-3 text-slate-600">{{ row.type }}</td>
                    <td class="p-3 text-slate-600">{{ row.nationality }}</td>
                    <td class="p-3 text-right font-mono text-slate-800">{{ formatCurrency(row.tsh_amount) }}</td>
                    <td class="p-3 text-right font-mono text-slate-800">{{ formatCurrency(row.usd_amount) }}</td>
                    <td class="p-3 text-right font-mono text-slate-800">{{ formatCurrency(row.rate) }}</td>
                    <td class="p-3 text-slate-600">{{ row.class }}</td>
                    <td class="p-3 text-slate-600">{{ row.journey_date }}</td>
                    <td class="p-3 text-slate-600">{{ row.booking_date }}</td>
                    <td class="p-3 text-slate-800">{{ row.route }}</td>
                    <td class="p-3 text-right font-mono text-slate-800">{{ formatCurrency(row.total_tsh) }}</td>
                    <td class="p-3 text-right font-mono text-rose-600">{{ formatCurrency(row.penalty) }}</td>
                    <td class="p-3 text-right font-mono font-bold text-emerald-600">{{ formatCurrency(row.refund) }}</td>
                    <td class="p-3 text-slate-600">{{ row.cancelled_at }}</td>
                  </tr>
                </tbody>
                <tfoot class="bg-slate-50 text-slate-900 border-t-2 border-slate-200">
                  <tr>
                    <td colspan="13" class="p-4 font-black uppercase tracking-wider text-xs text-right">Grand Total</td>
                    <td class="p-4 font-mono font-black text-right">{{ formatCurrency(totals.fare) }}</td>
                    <td class="p-4 font-mono font-black text-right text-rose-600">{{ formatCurrency(totals.penalty) }}</td>
                    <td class="p-4 font-mono font-black text-right text-emerald-600">{{ formatCurrency(totals.refund) }}</td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <!-- Cancellations Tab Summary -->
            <div v-if="activeReport === 'cancellations' && viewMode === 'summary'" class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="bg-slate-50/50 text-slate-500 text-[11px] uppercase tracking-wider">
                    <th class="p-4 font-semibold border-b border-slate-200">Grouping Metric</th>
                    <th class="p-4 font-semibold border-b border-slate-200 text-right">Total Cancelled</th>
                    <th class="p-4 font-semibold border-b border-slate-200 text-right">Total Penalty Withheld</th>
                    <th class="p-4 font-semibold border-b border-slate-200 text-right">Total Refunded</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                  <tr v-for="(row, i) in reportData" :key="i" class="hover:bg-slate-50/50 transition-colors">
                    <td class="p-4 text-slate-800 font-medium">{{ row.label }}</td>
                    <td class="p-4 font-mono text-right text-slate-800">{{ formatCurrency(row.count) }}</td>
                    <td class="p-4 font-mono text-right text-rose-600">{{ formatCurrency(row.total_penalty) }}</td>
                    <td class="p-4 font-mono text-right font-bold text-emerald-600">{{ formatCurrency(row.total_refund) }}</td>
                  </tr>
                </tbody>
                <tfoot class="bg-slate-50 text-slate-900 border-t-2 border-slate-200">
                  <tr>
                    <td class="p-4 font-black uppercase tracking-wider text-xs text-right">Grand Total</td>
                    <td class="p-4 font-mono font-black text-right">{{ formatCurrency(totals.tix) }}</td>
                    <td class="p-4 font-mono font-black text-right text-rose-600">{{ formatCurrency(totals.penalty) }}</td>
                    <td class="p-4 font-mono font-black text-right text-emerald-600">{{ formatCurrency(totals.refund) }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <!-- Manifest Tab -->
            <div v-if="activeReport === 'manifest'" class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="bg-slate-50/50 text-slate-500 text-[11px] uppercase tracking-wider">
                    <th class="p-4 font-semibold border-b border-slate-200 w-12 text-center">#</th>
                    <th class="p-4 font-semibold border-b border-slate-200">Ticket #</th>
                    <th class="p-4 font-semibold border-b border-slate-200">Passenger</th>
                    <th class="p-4 font-semibold border-b border-slate-200">Category</th>
                    <th class="p-4 font-semibold border-b border-slate-200">Class</th>
                    <th class="p-4 font-semibold border-b border-slate-200">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                  <tr v-for="(row, idx) in reportData" :key="row.id" class="hover:bg-slate-50/50 transition-colors">
                    <td class="p-4 text-center text-slate-400 font-mono text-xs">{{ idx + 1 }}</td>
                    <td class="p-4 font-mono font-medium text-slate-800">{{ row.ticket }}</td>
                    <td class="p-4 font-medium text-slate-800">{{ row.passenger_name }}</td>
                    <td class="p-4 text-slate-600">{{ row.age_category }}</td>
                    <td class="p-4 text-slate-600">{{ row.class }}</td>
                    <td class="p-4">
                      <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                        :class="row.is_boarded ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                        {{ row.is_boarded ? 'Boarded' : 'Not Boarded' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Ledger Tab -->
            <div v-if="activeReport === 'ledger'" class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="bg-slate-50/50 text-slate-500 text-[11px] uppercase tracking-wider">
                    <th class="p-4 font-semibold border-b border-slate-200">Date</th>
                    <th class="p-4 font-semibold border-b border-slate-200">Reference</th>
                    <th class="p-4 font-semibold border-b border-slate-200">Category</th>
                    <th class="p-4 font-semibold border-b border-slate-200">Description</th>
                    <th class="p-4 font-semibold border-b border-slate-200 text-right">Amount</th>
                    <th class="p-4 font-semibold border-b border-slate-200 text-right">Balance</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                  <tr v-for="row in reportData" :key="row.id" class="hover:bg-slate-50/50 transition-colors">
                    <td class="p-4 text-slate-600 whitespace-nowrap">{{ new Date(row.created_at).toLocaleString('en-GB') }}</td>
                    <td class="p-4 font-mono font-medium text-slate-800">{{ row.transaction_number }}</td>
                    <td class="p-4">
                      <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-600">
                        {{ row.category?.name }}
                      </span>
                    </td>
                    <td class="p-4 text-slate-800">{{ row.description }}</td>
                    <td class="p-4 font-mono text-right" :class="isCredit(row) ? 'text-emerald-600' : 'text-rose-600'">
                      {{ isCredit(row) ? '+' : '-' }}{{ formatCurrency(row.amount) }}
                    </td>
                    <td class="p-4 font-mono font-bold text-slate-800 text-right">{{ formatCurrency(row.balance_after) }}</td>
                  </tr>
                </tbody>
                <tfoot class="bg-slate-50 text-slate-900 border-t-2 border-slate-200">
                  <tr>
                    <td colspan="4" class="p-4 font-black uppercase tracking-wider text-xs text-right">Net Flow</td>
                    <td class="p-4 font-mono font-black text-right" :class="totals.amount >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                      {{ totals.amount >= 0 ? '+' : '' }}{{ formatCurrency(totals.amount) }}
                    </td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useUiStore } from '../stores/ui';
import { useAuthStore } from '../stores/auth';
import { Bar } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const uiStore = useUiStore();
const authStore = useAuthStore();

const activeReport = ref(null); // null means Hub View
const viewMode = ref('detailed'); // 'detailed' or 'summary'

const filterOptions = ref({ agents: [], associates: [], routes: [], ferries: [], classes: [] });

// Base fixed filters
const baseFilters = ref({
  date_from: new Date().toISOString().split('T')[0],
  date_to: new Date().toISOString().split('T')[0],
  route_id: '',
});

// Dynamic Filter Chips
const activeFilterChips = ref({});
const selectedFilterToAdd = ref('');

const allAvailableFilters = computed(() => {
  return {
    agent_id: { label: 'Agent', options: filterOptions.value.agents?.map(a => ({ value: a.id, label: a.name })) || [] },
    associate_id: { label: 'Associate', options: filterOptions.value.associates?.map(a => ({ value: a.id, label: a.name })) || [] },
    ferry_class_id: { label: 'Class', options: filterOptions.value.classes?.map(c => ({ value: c.id, label: c.name })) || [] },
    booking_status: { label: 'Booking Status', options: [
      { value: 'confirmed', label: 'Confirmed' },
      { value: 'pending_payment', label: 'Pending Payment' },
      { value: 'cancelled', label: 'Cancelled' }
    ]},
    ticket_status: { label: 'Ticket Status', options: [
      { value: 'confirmed', label: 'Confirmed' },
      { value: 'rescheduled', label: 'Rescheduled' },
      { value: 'cancelled', label: 'Cancelled' }
    ]},
    boarding_status: { label: 'Boarding Status', options: [
      { value: 'boarded', label: 'Boarded' },
      { value: 'not_boarded', label: 'Not Boarded' }
    ]},
    nationality_type: { label: 'Nationality', options: [
      { value: 'resident', label: 'Resident' },
      { value: 'foreigner', label: 'Foreigner' }
    ]},
  };
});

const reportConfig = {
  audit: ['agent_id', 'associate_id', 'ticket_status'],
  bookings: ['agent_id', 'associate_id', 'ferry_class_id', 'booking_status', 'ticket_status'],
  cancellations: ['agent_id', 'associate_id', 'ferry_class_id'],
  manifest: ['boarding_status', 'nationality_type', 'ferry_class_id'],
  ledger: ['agent_id']
};

const availableFiltersToAdd = computed(() => {
  if (!activeReport.value) return {};
  const allowedKeys = reportConfig[activeReport.value] || [];
  const available = {};
  
  allowedKeys.forEach(key => {
    // Only show if not already added, and if options exist (e.g. for agent/associate depending on role)
    if (activeFilterChips.value[key] === undefined) {
      if (allAvailableFilters.value[key].options && allAvailableFilters.value[key].options.length > 0) {
        available[key] = allAvailableFilters.value[key];
      } else if (['booking_status', 'ticket_status', 'boarding_status', 'nationality_type'].includes(key)) {
         available[key] = allAvailableFilters.value[key];
      }
    }
  });
  return available;
});

const addFilterChip = () => {
  if (selectedFilterToAdd.value) {
    activeFilterChips.value[selectedFilterToAdd.value] = ''; // Add with empty 'All' value
    selectedFilterToAdd.value = ''; // Reset dropdown
  }
};

const removeFilterChip = (key) => {
  delete activeFilterChips.value[key];
};

const reportData = ref([]);
const chartData = ref(null);
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } }
};
const auditSummary = ref({ total_gross: 0, total_commission: 0, net_settlement: 0, total_tickets: 0 });
const isLoading = ref(false);

const hasData = computed(() => {
  return reportData.value && reportData.value.length > 0;
});

const totals = computed(() => {
  let t = { tix: 0, fare: 0, penalty: 0, refund: 0, amount: 0 };
  if (!reportData.value) return t;
  
  if (activeReport.value === 'bookings') {
    if (viewMode.value === 'summary') {
      t.tix = reportData.value.reduce((s, r) => s + Number(r.count || 0), 0);
      t.fare = reportData.value.reduce((s, r) => s + Number(r.total_fare || 0), 0);
    } else {
      t.fare = reportData.value.reduce((s, r) => s + Number(r.total_tsh || 0), 0);
    }
  } else if (activeReport.value === 'cancellations') {
    if (viewMode.value === 'summary') {
      t.tix = reportData.value.reduce((s, r) => s + Number(r.count || 0), 0);
      t.penalty = reportData.value.reduce((s, r) => s + Number(r.total_penalty || 0), 0);
      t.refund = reportData.value.reduce((s, r) => s + Number(r.total_refund || 0), 0);
    } else {
      t.fare = reportData.value.reduce((s, r) => s + Number(r.fare || 0), 0);
      t.penalty = reportData.value.reduce((s, r) => s + Number(r.penalty || 0), 0);
      t.refund = reportData.value.reduce((s, r) => s + Number(r.refund || 0), 0);
    }
  } else if (activeReport.value === 'ledger') {
    t.amount = reportData.value.reduce((s, r) => {
      const amt = Number(r.amount || 0);
      return isCredit(r) ? s + amt : s - amt;
    }, 0);
  }
  return t;
});

const currentReportTitle = computed(() => {
  if (activeReport.value === 'audit') return 'Summary Audit';
  if (activeReport.value === 'bookings') return 'Booking Report';
  if (activeReport.value === 'cancellations') return 'Cancellation Report';
  if (activeReport.value === 'manifest') return 'Passenger Manifest';
  if (activeReport.value === 'ledger') return 'Float Ledger';
  return '';
});

const currentReportDescription = computed(() => {
  if (activeReport.value === 'audit') return 'Daily gross, commission, and net settlement breakdown.';
  if (activeReport.value === 'bookings') return 'Detailed ledger of individual tickets and passenger names.';
  if (activeReport.value === 'cancellations') return 'Log of cancelled tickets, penalties, and refunds.';
  if (activeReport.value === 'manifest') return 'Passenger lists with boarding status for voyages.';
  if (activeReport.value === 'ledger') return 'Dedicated transaction history for agent wallet.';
  return '';
});

const openReport = (reportType) => {
  activeReport.value = reportType;
  activeFilterChips.value = {}; // Reset chips
  viewMode.value = 'detailed';
  fetchData();
};

const closeReport = () => {
  activeReport.value = null;
  reportData.value = [];
  chartData.value = null;
};

const isCredit = (tx) => {
  return [1, 5, 7].includes(tx.transaction_category_id);
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-TZ', { maximumFractionDigits: 0 }).format(amount);
};

const fetchFilterOptions = async () => {
  try {
    const res = await axios.get('/api/v1/portal/reports/filters');
    if (res.data.status === 'success') {
      filterOptions.value = res.data.filters;
    }
  } catch (err) {
    console.error('Failed to load filters', err);
  }
};

const buildChartData = (data, valueField, labelField) => {
  if (!data || data.length === 0) return null;
  return {
    labels: data.map(d => d[labelField]),
    datasets: [
      {
        label: 'Volume',
        backgroundColor: '#4f46e5',
        data: data.map(d => d[valueField])
      }
    ]
  };
};

const fetchData = async () => {
  if (!activeReport.value) return;
  isLoading.value = true;
  reportData.value = [];
  chartData.value = null;
  
  let endpoint = '/api/v1/portal/reports';
  if (activeReport.value === 'bookings') endpoint += '/bookings';
  if (activeReport.value === 'cancellations') endpoint += '/cancellations';
  if (activeReport.value === 'manifest') endpoint += '/manifest';
  if (activeReport.value === 'ledger') endpoint += '/float-ledger';

  const payload = {
    ...baseFilters.value,
    ...activeFilterChips.value,
    view_mode: viewMode.value
  };

  try {
    const res = await axios.get(endpoint, { params: payload });
    if (res.data.status === 'success') {
      reportData.value = res.data.data;
      
      if (activeReport.value === 'audit' && res.data.summary) {
        auditSummary.value = res.data.summary;
      }

      if (viewMode.value === 'summary') {
        if (activeReport.value === 'bookings') {
          chartData.value = buildChartData(reportData.value, 'count', 'label');
        } else if (activeReport.value === 'cancellations') {
          chartData.value = buildChartData(reportData.value, 'count', 'label');
        }
      }
    }
  } catch (err) {
    uiStore.notify(err.response?.data?.message || 'Failed to fetch report data', 'Error', 'error');
  } finally {
    isLoading.value = false;
  }
};

const exportCSV = () => {
  if (!hasData.value) return uiStore.notify('No data to export', 'Error', 'error');
  
  let headers = [];
  let rows = [];

  if (activeReport.value === 'audit') {
    headers = ['Date', 'Time', 'Ticket #', 'Gross Fare', 'Commission', 'Net Fare'];
    rows = reportData.value.map(r => [r.date, r.time, r.ticket, r.gross, r.comm || 0, r.net || 0]);
  } else if (activeReport.value === 'bookings') {
    if (viewMode.value === 'summary') {
      headers = ['Grouping Metric', 'Total Tickets', 'Total Fare (TZS)'];
      rows = reportData.value.map(r => [`"${r.label}"`, r.count, r.total_fare]);
    } else {
      headers = ['S/N', 'Booking ID', 'Passenger', 'Gender', 'Age', 'Nationality', '(TSH)', '(USD)', 'Rate', 'Ticket Status', 'Class', 'Journey Date', 'Booking Date', 'Route', 'Total (TSH)', 'Booking Status'];
      rows = reportData.value.map((r, i) => [i + 1, r.ticket, `"${r.passenger_name}"`, r.gender, r.type, r.nationality, r.tsh_amount, r.usd_amount, r.rate, r.status, r.class, r.journey_date, r.booking_date, `"${r.route}"`, r.total_tsh, r.booking_status]);
    }
  } else if (activeReport.value === 'cancellations') {
    if (viewMode.value === 'summary') {
      headers = ['Grouping Metric', 'Total Cancelled', 'Total Penalty Withheld', 'Total Refunded'];
      rows = reportData.value.map(r => [`"${r.label}"`, r.count, r.total_penalty, r.total_refund]);
    } else {
      headers = ['S/N', 'Booking ID', 'Passenger', 'Gender', 'Age', 'Nationality', '(TSH)', '(USD)', 'Rate', 'Class', 'Journey Date', 'Booking Date', 'Route', 'Total Fare', 'Penalty', 'Refund', 'Cancelled At'];
      rows = reportData.value.map((r, i) => [i + 1, r.ticket, `"${r.passenger_name}"`, r.gender, r.type, r.nationality, r.tsh_amount, r.usd_amount, r.rate, r.class, r.journey_date, r.booking_date, `"${r.route}"`, r.total_tsh, r.penalty, r.refund, r.cancelled_at]);
    }
  } else if (activeReport.value === 'manifest') {
    headers = ['SN', 'Ticket', 'Passenger', 'Category', 'Class', 'Boarded'];
    rows = reportData.value.map(r => [r.sn, r.ticket, `"${r.passenger_name}"`, r.age_category, r.class, r.boarded ? 'Yes' : 'No']);
  } else if (activeReport.value === 'ledger') {
    headers = ['Date', 'Reference', 'Category', 'Description', 'Amount', 'Balance'];
    rows = reportData.value.map(r => [r.created_at, r.transaction_number, r.category?.name, `"${r.description}"`, r.amount, r.balance_after]);
  }

  const csvContent = "data:text/csv;charset=utf-8," + headers.join(',') + "\n" + rows.map(e => e.join(',')).join("\n");
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement("a");
  link.setAttribute("href", encodedUri);
  link.setAttribute("download", `${activeReport.value}_report_${baseFilters.value.date_from}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

onMounted(() => {
  fetchFilterOptions();
});
</script>
