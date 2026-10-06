<x-layouts.admin>
    <x-slot:title>Reports Hub</x-slot:title>
    <x-slot:header>Reports Hub</x-slot:header>

    {{-- ═══ WELCOME BANNER ═══ --}}
    <div class="rounded-3xl bg-gradient-to-br from-indigo-900 via-blue-900 to-slate-900 p-8 mb-8 relative overflow-hidden shadow-2xl border border-white/10">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute right-0 top-0 w-64 h-64 rounded-full bg-blue-500 blur-[80px]"></div>
            <div class="absolute -left-20 -bottom-20 w-60 h-60 rounded-full bg-indigo-600 blur-[60px]"></div>
        </div>
        <div class="relative z-10">
            <h2 class="text-3xl font-extrabold text-white tracking-tight">Data & Reports 📊</h2>
            <p class="text-blue-200/80 text-sm mt-2 font-medium max-w-lg">Access financial statements, voyage occupancies, and operational analytics. Choose a report module below.</p>
        </div>
    </div>

    {{-- ═══ REPORT CARDS ═══ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

        {{-- Predictive Analytics (Highlight Card) --}}
        <a href="{{ route('admin.reports.projections') }}" class="lg:col-span-4 group bg-gradient-to-r from-indigo-600 to-blue-700 rounded-3xl p-8 shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden flex items-center justify-between">
            <div class="absolute -right-10 -top-10 w-48 h-48 bg-white/10 rounded-full blur-2xl group-hover:scale-125 transition-transform duration-700"></div>
            <div class="absolute right-10 bottom-0 opacity-10">
                <i class="fas fa-robot text-9xl text-white"></i>
            </div>
            
            <div class="relative z-10">
                <div class="inline-flex items-center gap-2 bg-white/20 text-indigo-50 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider mb-4 border border-white/20">
                    <i class="fas fa-sparkles"></i> AI Powered
                </div>
                <h3 class="text-3xl font-black text-white mb-2 tracking-tight">Projections & Insights</h3>
                <p class="text-indigo-100 max-w-xl text-sm leading-relaxed">
                    Unlock forward-looking analytics. View 30-day revenue forecasts, AI-driven route optimization recommendations, and customer loyalty retention radar.
                </p>
            </div>
            
            <div class="relative z-10 hidden md:flex items-center justify-center h-16 w-16 bg-white text-indigo-600 rounded-full shadow-lg group-hover:scale-110 transition-transform">
                <i class="fas fa-arrow-right text-xl"></i>
            </div>
        </a>

        {{-- Revenue & Sales --}}
        <a href="{{ route('admin.reports.revenue') }}" class="group bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden block">
            <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-50 rounded-bl-[100px] -z-10 group-hover:scale-110 transition-transform"></div>
            <div class="h-14 w-14 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl mb-5 shadow-inner">
                <i class="fas fa-chart-line"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 group-hover:text-emerald-600 transition-colors">Revenue & Sales</h3>
            <p class="text-sm text-slate-500 mt-2">Breakdown of ticket sales by route, ferry, class, and agent with grouping options.</p>
            <div class="mt-6 flex items-center text-sm font-bold text-emerald-600">
                Run Report <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

        {{-- Ferry Utilization Report --}}
        <a href="{{ route('admin.reports.occupancy') }}" class="group bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden block">
            <div class="absolute top-0 right-0 w-32 h-32 bg-blue-50 rounded-bl-[100px] -z-10 group-hover:scale-110 transition-transform"></div>
            <div class="h-14 w-14 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center text-2xl mb-5 shadow-inner">
                <i class="fas fa-users"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 group-hover:text-blue-600 transition-colors">Ferry Utilization Report</h3>
            <p class="text-sm text-slate-500 mt-2">Vessel occupancy rates, total passengers boarded vs capacity per scheduled voyage.</p>
            <div class="mt-6 flex items-center text-sm font-bold text-blue-600">
                Run Report <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

        {{-- Booking Report --}}
        <a href="{{ route('admin.reports.bookings') }}" class="group bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden block">
            <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-50 rounded-bl-[100px] -z-10 group-hover:scale-110 transition-transform"></div>
            <div class="h-14 w-14 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-2xl mb-5 shadow-inner">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">Booking Report</h3>
            <p class="text-sm text-slate-500 mt-2">Detailed ledger of individual tickets, passenger names, and booking statuses.</p>
            <div class="mt-6 flex items-center text-sm font-bold text-indigo-600">
                Run Report <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

        {{-- Passenger Manifest --}}
        <a href="{{ route('admin.reports.manifest') }}" class="group bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden block">
            <div class="absolute top-0 right-0 w-32 h-32 bg-teal-50 rounded-bl-[100px] -z-10 group-hover:scale-110 transition-transform"></div>
            <div class="h-14 w-14 rounded-2xl bg-teal-100 text-teal-600 flex items-center justify-center text-2xl mb-5 shadow-inner">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 group-hover:text-teal-600 transition-colors">Passenger Manifest</h3>
            <p class="text-sm text-slate-500 mt-2">Official passenger manifest per voyage for maritime authorities and port clearance.</p>
            <div class="mt-6 flex items-center text-sm font-bold text-teal-600">
                Run Report <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

        {{-- Float Ledger Report --}}
        @can('report:view_all')
        <a href="{{ route('admin.reports.float_ledger') }}" class="group bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden block">
            <div class="absolute top-0 right-0 w-32 h-32 bg-violet-50 rounded-bl-[100px] -z-10 group-hover:scale-110 transition-transform"></div>
            <div class="h-14 w-14 rounded-2xl bg-violet-100 text-violet-600 flex items-center justify-center text-2xl mb-5 shadow-inner">
                <i class="fas fa-wallet"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 group-hover:text-violet-600 transition-colors">Agent Float Ledger</h3>
            <p class="text-sm text-slate-500 mt-2">Detailed ledger of top-ups, deductions, commissions, and penalties for partner agencies.</p>
            <div class="mt-6 flex items-center text-sm font-bold text-violet-600">
                Run Report <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
            </div>
        @endcan

        {{-- Boarding & No-Show Report --}}
        <a href="{{ route('admin.reports.boarding') }}" class="group bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden block">
            <div class="absolute top-0 right-0 w-32 h-32 bg-amber-50 rounded-bl-[100px] -z-10 group-hover:scale-110 transition-transform"></div>
            <div class="h-14 w-14 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center text-2xl mb-5 shadow-inner">
                <i class="fas fa-users-slash"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 group-hover:text-amber-600 transition-colors">Boarding & No-Show</h3>
            <p class="text-sm text-slate-500 mt-2">Analyze passenger attendance, boarded counts, and no-show rates per voyage.</p>
            <div class="mt-6 flex items-center text-sm font-bold text-amber-600">
                Run Report <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
        {{-- Agent Performance Report --}}
        @can('report:view_all')
        <a href="{{ route('admin.reports.agent_performance') }}" class="group bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden block">
            <div class="absolute top-0 right-0 w-32 h-32 bg-sky-50 rounded-bl-[100px] -z-10 group-hover:scale-110 transition-transform"></div>
            <div class="h-14 w-14 rounded-2xl bg-sky-100 text-sky-600 flex items-center justify-center text-2xl mb-5 shadow-inner">
                <i class="fas fa-handshake"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 group-hover:text-sky-600 transition-colors">Agent Performance</h3>
            <p class="text-sm text-slate-500 mt-2">Track total ticket sales and estimated commissions per travel agency.</p>
            <div class="mt-6 flex items-center text-sm font-bold text-sky-600">
                Run Report <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>
        @endcan

        {{-- System Financial Reconciliation --}}
        @can('report:view_all')
        <a href="{{ route('admin.reports.system_reconciliation') }}" class="group bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden block">
            <div class="absolute top-0 right-0 w-32 h-32 bg-purple-50 rounded-bl-[100px] -z-10 group-hover:scale-110 transition-transform"></div>
            <div class="h-14 w-14 rounded-2xl bg-purple-100 text-purple-600 flex items-center justify-center text-2xl mb-5 shadow-inner">
                <i class="fas fa-balance-scale"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 group-hover:text-purple-600 transition-colors">Financial Reconciliation</h3>
            <p class="text-sm text-slate-500 mt-2">Identify discrepancies between bookings, payments, and agent wallet balances.</p>
            <div class="mt-6 flex items-center text-sm font-bold text-purple-600">
                Run Check <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>
        @endcan
    </div>

    {{-- ═══ NEW ANALYTICS REPORTS ═══ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">

        {{-- Payment Channel Analysis --}}
        <a href="{{ route('admin.reports.payment_analysis') }}" class="group bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden block">
            <div class="absolute top-0 right-0 w-32 h-32 bg-pink-50 rounded-bl-[100px] -z-10 group-hover:scale-110 transition-transform"></div>
            <div class="inline-flex items-center gap-1 bg-pink-100 text-pink-600 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider mb-3">
                <i class="fas fa-sparkles"></i> New
            </div>
            <div class="h-14 w-14 rounded-2xl bg-pink-100 text-pink-600 flex items-center justify-center text-2xl mb-5 shadow-inner">
                <i class="fas fa-credit-card"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 group-hover:text-pink-600 transition-colors">Payment Analysis</h3>
            <p class="text-sm text-slate-500 mt-2">Revenue by payment channel with period-over-period growth trends.</p>
            <div class="mt-6 flex items-center text-sm font-bold text-pink-600">
                Run Report <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

        {{-- Peak Hours --}}
        <a href="{{ route('admin.reports.peak_hours') }}" class="group bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden block">
            <div class="absolute top-0 right-0 w-32 h-32 bg-orange-50 rounded-bl-[100px] -z-10 group-hover:scale-110 transition-transform"></div>
            <div class="inline-flex items-center gap-1 bg-orange-100 text-orange-600 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider mb-3">
                <i class="fas fa-sparkles"></i> New
            </div>
            <div class="h-14 w-14 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center text-2xl mb-5 shadow-inner">
                <i class="fas fa-clock"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 group-hover:text-orange-600 transition-colors">Peak Hours</h3>
            <p class="text-sm text-slate-500 mt-2">Hour-by-hour booking demand for dynamic pricing decisions.</p>
            <div class="mt-6 flex items-center text-sm font-bold text-orange-600">
                Run Report <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

        {{-- Route Profitability --}}
        <a href="{{ route('admin.reports.route_profitability') }}" class="group bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden block">
            <div class="absolute top-0 right-0 w-32 h-32 bg-cyan-50 rounded-bl-[100px] -z-10 group-hover:scale-110 transition-transform"></div>
            <div class="inline-flex items-center gap-1 bg-cyan-100 text-cyan-600 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider mb-3">
                <i class="fas fa-sparkles"></i> New
            </div>
            <div class="h-14 w-14 rounded-2xl bg-cyan-100 text-cyan-600 flex items-center justify-center text-2xl mb-5 shadow-inner">
                <i class="fas fa-route"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 group-hover:text-cyan-600 transition-colors">Route Profitability</h3>
            <p class="text-sm text-slate-500 mt-2">Revenue per seat, load factors, and profitability ranking.</p>
            <div class="mt-6 flex items-center text-sm font-bold text-cyan-600">
                Run Report <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

        {{-- Cancellation Trends --}}
        <a href="{{ route('admin.reports.cancellation_trends') }}" class="group bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden block">
            <div class="absolute top-0 right-0 w-32 h-32 bg-red-50 rounded-bl-[100px] -z-10 group-hover:scale-110 transition-transform"></div>
            <div class="inline-flex items-center gap-1 bg-red-100 text-red-600 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider mb-3">
                <i class="fas fa-sparkles"></i> New
            </div>
            <div class="h-14 w-14 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center text-2xl mb-5 shadow-inner">
                <i class="fas fa-chart-area"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 group-hover:text-red-600 transition-colors">Cancellation Trends</h3>
            <p class="text-sm text-slate-500 mt-2">Daily cancellation patterns with penalty recovery rates.</p>
            <div class="mt-6 flex items-center text-sm font-bold text-red-600">
                Run Report <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

        {{-- Commission Reconciliation --}}
        @can('report:view_all')
        <a href="{{ route('admin.reports.commission_reconciliation') }}" class="group bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden block">
            <div class="absolute top-0 right-0 w-32 h-32 bg-lime-50 rounded-bl-[100px] -z-10 group-hover:scale-110 transition-transform"></div>
            <div class="inline-flex items-center gap-1 bg-lime-100 text-lime-600 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider mb-3">
                <i class="fas fa-sparkles"></i> New
            </div>
            <div class="h-14 w-14 rounded-2xl bg-lime-100 text-lime-600 flex items-center justify-center text-2xl mb-5 shadow-inner">
                <i class="fas fa-balance-scale"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 group-hover:text-lime-600 transition-colors">Commission Reconciliation</h3>
            <p class="text-sm text-slate-500 mt-2">Earned vs paid commissions with discrepancy detection.</p>
            <div class="mt-6 flex items-center text-sm font-bold text-lime-600">
                Run Report <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>
        @endcan
    </div>
</x-layouts.admin>
