<aside class="w-64 bg-gradient-to-b from-white via-[#fefcf8] to-[#faf7f2] border-r border-surface-200/80 flex flex-col shrink-0 min-h-screen relative">
    {{-- Decorative accent strip --}}
    <div class="absolute inset-y-0 left-0 w-[3px] bg-gradient-to-b from-indigo-400 via-blue-400 to-teal-400 opacity-50"></div>

    <!-- Header / Logo Area -->
    <div class="flex h-16 shrink-0 items-center gap-3 px-6 border-b border-surface-200/60 mb-2">
        <img src="{{ asset('images/logo.png') }}" alt="Ferry Flow" class="h-9 w-auto object-contain drop-shadow-sm">
        <div>
            <h2 class="text-sm font-bold text-surface-800 tracking-wide">Ferry Flow</h2>
            <p class="text-[10px] text-blue-500/80 font-semibold uppercase tracking-widest">POS & Agent Hub</p>
        </div>
    </div>

    <div class="py-4 px-3 flex-1 overflow-y-auto space-y-6">
        <!-- Section: Main POS Operations -->
        <div>
            <div class="px-3 mb-2 text-[10px] font-mono font-bold uppercase tracking-wider text-surface-400">Main Operations</div>
            <nav class="space-y-1">
                <a href="{{ route('portal.spa') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium text-sm transition-all duration-150 {{ request()->is('portal') || request()->is('portal/dashboard') ? 'bg-brand-50 text-brand-700 font-bold border-l-2 border-brand-500 shadow-sm shadow-brand-100/50' : 'text-surface-600 hover:text-surface-900 hover:bg-surface-100/80' }}">
                    <i class="fas fa-chart-pie w-5 text-center {{ request()->is('portal') || request()->is('portal/dashboard') ? 'text-brand-500' : 'text-surface-400' }}"></i>
                    <span>Dashboard Overview</span>
                </a>

                @if(auth()->user()->hasPermission('booking.create') || in_array(auth()->user()->domain, ['agent', 'associate_agent']))
                <a href="{{ route('portal.spa') }}/fast-booking"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl font-medium text-sm transition-all duration-150 text-surface-600 hover:text-surface-900 hover:bg-surface-100/80">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-bolt w-5 text-center text-amber-500"></i>
                        <span>Fast POS Booking</span>
                    </div>
                    <span class="text-[10px] font-mono uppercase bg-amber-50 text-amber-600 font-extrabold px-1.5 py-0.5 rounded border border-amber-200/60">Quick</span>
                </a>
                @endif


            </nav>
        </div>

        <!-- Section: Agency & Credit Management -->
        @if(in_array(auth()->user()->domain, ['agent', 'associate_agent']))
        <div>
            <div class="px-3 mb-2 text-[10px] font-mono font-bold uppercase tracking-wider text-surface-400">Agency & Wallet</div>
            <nav class="space-y-1">
                <a href="{{ route('portal.spa') }}/wallet"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl font-medium text-sm transition-all duration-150 text-surface-600 hover:text-surface-900 hover:bg-surface-100/80">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-wallet w-5 text-center text-emerald-500"></i>
                        <span>Credit Wallet</span>
                    </div>
                </a>

                @if(auth()->user()->domain === 'agent')
                <a href="{{ route('portal.spa') }}/associates"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium text-sm transition-all duration-150 text-surface-600 hover:text-surface-900 hover:bg-surface-100/80">
                    <i class="fas fa-users-gear w-5 text-center text-violet-500"></i>
                    <span>Manage Sub-Agents</span>
                </a>
                @endif
            </nav>
        </div>
        @endif

        <!-- Section: Audit & Reporting -->
        <div>
            <div class="px-3 mb-2 text-[10px] font-mono font-bold uppercase tracking-wider text-surface-400">Audit & Reports</div>
            <nav class="space-y-1">
                @if(auth()->user()->hasPermission('booking:read') || in_array(auth()->user()->domain, ['agent', 'associate_agent']))
                <a href="{{ route('portal.bookings.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium text-sm transition-all duration-150 {{ request()->routeIs('portal.bookings.*') ? 'bg-brand-50 text-brand-700 font-bold border-l-2 border-brand-500 shadow-sm shadow-brand-100/50' : 'text-surface-600 hover:text-surface-900 hover:bg-surface-100/80' }}">
                    <i class="fas fa-ticket-alt w-5 text-center {{ request()->routeIs('portal.bookings.*') ? 'text-brand-500' : 'text-indigo-500' }}"></i>
                    <span>Bookings & Tickets</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('report:view') || in_array(auth()->user()->domain, ['agent', 'associate_agent', 'sales_officer', 'system_user']))
                <a href="{{ route('portal.spa') }}/reports"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium text-sm transition-all duration-150 text-surface-600 hover:text-surface-900 hover:bg-surface-100/80">
                    <i class="fas fa-file-invoice-dollar w-5 text-center text-rose-500"></i>
                    <span>Commission & Sales Audit</span>
                </a>
                @endif
            </nav>
        </div>
    </div>
    
    <!-- User section at bottom -->
    <div class="p-4 border-t border-surface-200/60">
        <div class="flex items-center gap-x-3 px-3 py-3 rounded-lg bg-surface-50/80 border border-surface-200/40">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 shadow-sm shadow-brand-200/50">
                <span class="text-xs font-bold text-white">{{ substr(auth()->user()?->full_name ?? 'A', 0, 1) }}</span>
            </span>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-surface-800 truncate">{{ auth()->user()?->full_name ?? 'User' }}</p>
                <p class="text-[10px] text-surface-400 truncate">{{ auth()->user()?->email }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-surface-400 hover:text-rose-500 transition-colors"><i class="fas fa-sign-out-alt"></i></button>
            </form>
        </div>
    </div>
</aside>
