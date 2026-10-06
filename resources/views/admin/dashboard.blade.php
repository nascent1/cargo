<x-layouts.admin>
    <x-slot:title>Command Center</x-slot:title>
    <x-slot:header>Operations Command Center</x-slot:header>

    {{-- ═══ WELCOME BANNER ═══ --}}
    <div class="rounded-3xl bg-gradient-to-r from-slate-900 via-slate-800 to-blue-900/60 p-8 mb-8 relative overflow-hidden shadow-2xl border border-white/10 backdrop-blur-md">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute -right-20 -top-20 w-80 h-80 rounded-full bg-blue-500 blur-[80px]"></div>
            <div class="absolute -left-20 -bottom-20 w-60 h-60 rounded-full bg-indigo-600 blur-[60px]"></div>
        </div>
        <div class="relative z-10 flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-extrabold text-white tracking-tight">Operations Command Center ⛴️</h2>
                <p class="text-blue-200/80 text-sm mt-2 font-medium max-w-lg">Welcome, {{ auth()->user()->full_name ?? 'Admin' }}. Real-time overview for {{ now()->format('l, F j, Y') }}.</p>
            </div>
            <div class="hidden md:flex items-center gap-4">
                <div class="px-4 py-2 rounded-xl bg-white/10 backdrop-blur-lg border border-white/10 text-white text-sm font-mono" id="live-clock"></div>
                <div class="px-5 py-3 rounded-xl bg-white/10 backdrop-blur-lg border border-white/10 flex items-center gap-3">
                    <div class="h-3 w-3 rounded-full bg-emerald-400 animate-pulse"></div>
                    <span class="text-white text-sm font-semibold tracking-wide">System Online</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ TAB NAVIGATION ═══ --}}
    @if(count($tabs) > 1)
    <div class="flex items-center gap-2 mb-8 border-b border-slate-200 pb-0">
        @foreach($tabs as $tab)
            @php
                $tabConfig = match($tab) {
                    'operations'       => ['label' => 'Operations', 'icon' => 'fa-ship',        'color' => 'blue'],
                    'finance'          => ['label' => 'Finance',    'icon' => 'fa-wallet',       'color' => 'emerald'],
                    'agent_performance'=> ['label' => 'Agent Performance', 'icon' => 'fa-trophy', 'color' => 'violet'],
                };
            @endphp
            <a href="?tab={{ $tab }}"
               class="flex items-center gap-2 px-5 py-3 text-sm font-bold border-b-2 transition-all -mb-px
                   {{ $activeTab === $tab
                       ? 'border-'.$tabConfig['color'].'-500 text-'.$tabConfig['color'].'-600'
                       : 'border-transparent text-slate-400 hover:text-slate-600 hover:border-slate-300' }}">
                <i class="fas {{ $tabConfig['icon'] }}"></i>
                {{ $tabConfig['label'] }}
            </a>
        @endforeach
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- TAB 1: OPERATIONS --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    @if($activeTab === 'operations' && !empty($opsData))
        @php $m = $opsData['metrics']; @endphp

        {{-- KPI Card Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
            @php
                $cards = [
                    ['label' => "Today's Bookings", 'value' => number_format($m['today_bookings']), 'icon' => 'fa-ticket', 'color' => 'blue', 'sub' => 'Confirmed tickets'],
                    ['label' => "Today's Revenue", 'value' => 'TZS ' . number_format($m['today_revenue_tzs']), 'icon' => 'fa-money-bill-wave', 'color' => 'emerald', 'sub' => 'Gross revenue'],
                    ['label' => "Cancellations", 'value' => number_format($m['today_cancelled']), 'icon' => 'fa-times-circle', 'color' => 'rose', 'sub' => 'Cancelled today'],
                    ['label' => 'Load Factor', 'value' => $m['load_factor'] . '%', 'icon' => 'fa-chart-pie', 'color' => 'amber', 'sub' => 'Avg vessel occupancy'],
                    ['label' => 'Active Ferries', 'value' => number_format($m['active_ferries']), 'icon' => 'fa-ship', 'color' => 'sky', 'sub' => 'Operational fleet'],
                    ['label' => 'Active Routes', 'value' => number_format($m['active_routes']), 'icon' => 'fa-route', 'color' => 'indigo', 'sub' => 'Published routes'],
                    ['label' => 'Active Agents', 'value' => number_format($m['active_agents']), 'icon' => 'fa-users-gear', 'color' => 'violet', 'sub' => 'Licensed partners'],
                    ['label' => 'Pending Approvals', 'value' => number_format($m['pending_approvals']), 'icon' => 'fa-shield-halved', 'color' => 'orange', 'sub' => 'DC queue'],
                    ['label' => 'Monthly Tickets', 'value' => number_format($m['monthly_tickets']), 'icon' => 'fa-calendar-check', 'color' => 'teal', 'sub' => now()->format('F Y')],
                    ['label' => 'Monthly Revenue', 'value' => 'TZS ' . number_format($m['monthly_revenue']), 'icon' => 'fa-chart-line', 'color' => 'green', 'sub' => now()->format('F Y')],
                    ['label' => 'Total Users', 'value' => number_format($m['total_users']), 'icon' => 'fa-users', 'color' => 'slate', 'sub' => 'All platform users'],
                    ['label' => 'Scheduled Trips', 'value' => number_format($m['scheduled_trips']), 'icon' => 'fa-calendar-days', 'color' => 'cyan', 'sub' => 'Active schedules'],
                ];
            @endphp

            @foreach ($cards as $card)
                <div class="bg-white/80 backdrop-blur-xl border border-slate-200/60 rounded-2xl p-5 hover:-translate-y-1 hover:shadow-xl transition-all duration-300 group">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $card['label'] }}</p>
                            <p class="text-2xl font-black text-slate-800 mt-1 tracking-tight">{{ $card['value'] }}</p>
                            <p class="text-[10px] text-slate-400 mt-0.5">{{ $card['sub'] }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-2xl bg-{{ $card['color'] }}-50 border border-{{ $card['color'] }}-100 flex items-center justify-center transform group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                            <i class="fas {{ $card['icon'] }} text-xl text-{{ $card['color'] }}-500"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Charts Row: Revenue by Route + Boarding Summary --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white/90 backdrop-blur-xl border border-slate-200/60 rounded-3xl p-7 shadow-sm">
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2 mb-5">
                    <div class="p-2 bg-emerald-100 rounded-lg text-emerald-600"><i class="fas fa-chart-bar"></i></div>
                    Revenue by Route (Today)
                </h3>
                <canvas id="revenueByRouteChart" height="280"></canvas>
            </div>

            <div class="bg-white/90 backdrop-blur-xl border border-slate-200/60 rounded-3xl p-7 shadow-sm">
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2 mb-5">
                    <div class="p-2 bg-indigo-100 rounded-lg text-indigo-600"><i class="fas fa-clipboard-list"></i></div>
                    Boarding Summary (Route × Class)
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                <th class="py-3 px-3 text-left">Route</th>
                                <th class="py-3 px-3 text-left">Ferry</th>
                                <th class="py-3 px-3 text-left">Class</th>
                                <th class="py-3 px-3 text-center">Adults</th>
                                <th class="py-3 px-3 text-center">Children</th>
                                <th class="py-3 px-3 text-center">Infants</th>
                                <th class="py-3 px-3 text-center font-black">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($opsData['boardingSummary'] as $row)
                                <tr class="hover:bg-blue-50/50 transition-colors">
                                    <td class="py-3 px-3 font-semibold text-slate-800">{{ $row->route }}</td>
                                    <td class="py-3 px-3 text-slate-600">{{ $row->ferry }}</td>
                                    <td class="py-3 px-3"><span class="px-2 py-0.5 rounded-lg bg-indigo-100 text-indigo-700 text-xs font-bold">{{ $row->class }}</span></td>
                                    <td class="py-3 px-3 text-center font-mono">{{ $row->adults }}</td>
                                    <td class="py-3 px-3 text-center font-mono">{{ $row->children }}</td>
                                    <td class="py-3 px-3 text-center font-mono">{{ $row->infants }}</td>
                                    <td class="py-3 px-3 text-center font-black text-slate-900">{{ $row->adults + $row->children + $row->infants }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-8 text-center text-slate-400">No boarding data today.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Fleet + Dual Control Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white/90 backdrop-blur-xl border border-slate-200/60 rounded-3xl p-7 shadow-sm">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <div class="p-2 bg-blue-100 rounded-lg text-blue-600"><i class="fas fa-ship"></i></div>
                        Fleet Overview
                    </h3>
                    <a href="{{ route('admin.ferries.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">View All &rarr;</a>
                </div>
                <div class="space-y-3">
                    @forelse ($opsData['recentFerries'] as $ferry)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100 hover:bg-blue-50/50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-full bg-white border border-slate-200 flex items-center justify-center shadow-sm"><i class="fas fa-ship text-slate-400"></i></div>
                                <div>
                                    <p class="text-sm font-bold text-slate-800">{{ $ferry->name }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $ferry->capacity ?? 0 }} pax</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-xl text-[10px] font-bold uppercase {{ match($ferry->status) { 'active' => 'bg-emerald-100 text-emerald-700', 'maintenance' => 'bg-amber-100 text-amber-700', default => 'bg-slate-100 text-slate-600' } }}">{{ $ferry->status }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400 text-center py-6">No vessels registered.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white/90 backdrop-blur-xl border border-slate-200/60 rounded-3xl p-7 shadow-sm">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <div class="p-2 bg-rose-100 rounded-lg text-rose-600"><i class="fas fa-shield-halved"></i></div>
                        Action Required
                    </h3>
                    <span class="bg-rose-100 text-rose-700 text-xs font-bold px-2 py-1 rounded-lg">{{ $opsData['pendingDC']->count() }}</span>
                </div>
                <div class="space-y-3">
                    @forelse ($opsData['pendingDC'] as $dc)
                        <a href="{{ route('admin.dual-control.show', $dc) }}" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100 hover:bg-rose-50/50 transition-all group block">
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-full bg-white border border-slate-200 flex items-center justify-center shadow-sm"><i class="fas fa-file-signature text-slate-400 group-hover:text-rose-500 transition-colors"></i></div>
                                <div>
                                    <p class="text-sm font-bold text-slate-800">{{ ucfirst(str_replace('_', ' ', $dc->operation_type)) }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $dc->maker?->full_name }} · {{ $dc->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-slate-300 group-hover:text-rose-500"></i>
                        </a>
                    @empty
                        <div class="text-center py-8">
                            <div class="h-14 w-14 mx-auto bg-emerald-50 rounded-full flex items-center justify-center mb-2"><i class="fas fa-check text-xl text-emerald-500"></i></div>
                            <p class="text-sm font-bold text-slate-700">All caught up!</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- TAB 2: FINANCE --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    @if($activeTab === 'finance' && !empty($finData))
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
            @php
                $finCards = [
                    ['label' => "Today's Revenue", 'value' => 'TZS ' . number_format($finData['today_revenue']), 'icon' => 'fa-money-bill-wave', 'color' => 'emerald'],
                    ['label' => "Monthly Transactions", 'value' => number_format($finData['total_tx_count']), 'icon' => 'fa-exchange-alt', 'color' => 'blue'],
                    ['label' => "Monthly Tx Volume", 'value' => 'TZS ' . number_format($finData['total_tx_amount']), 'icon' => 'fa-chart-area', 'color' => 'indigo'],
                    ['label' => "Float Top-Ups", 'value' => 'TZS ' . number_format($finData['float_topups']), 'icon' => 'fa-arrow-up', 'color' => 'teal'],
                    ['label' => "Commissions Paid", 'value' => 'TZS ' . number_format($finData['commissions']), 'icon' => 'fa-coins', 'color' => 'violet'],
                    ['label' => "Penalties Collected", 'value' => 'TZS ' . number_format($finData['penalties']), 'icon' => 'fa-gavel', 'color' => 'amber'],
                    ['label' => "Refunds/Reversals", 'value' => 'TZS ' . number_format($finData['refunds']), 'icon' => 'fa-undo', 'color' => 'rose'],
                    ['label' => "Total Float Pool", 'value' => 'TZS ' . number_format($finData['total_float']), 'icon' => 'fa-wallet', 'color' => 'cyan'],
                ];
            @endphp

            @foreach($finCards as $card)
                <div class="bg-white/80 backdrop-blur-xl border border-slate-200/60 rounded-2xl p-5 hover:-translate-y-1 hover:shadow-xl transition-all duration-300 group">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $card['label'] }}</p>
                    <p class="text-2xl font-black text-slate-800 mt-1">{{ $card['value'] }}</p>
                    <div class="h-10 w-10 rounded-2xl bg-{{ $card['color'] }}-50 flex items-center justify-center mt-2">
                        <i class="fas {{ $card['icon'] }} text-{{ $card['color'] }}-500"></i>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            {{-- Revenue by Payment Method --}}
            <div class="bg-white/90 backdrop-blur-xl border border-slate-200/60 rounded-3xl p-7 shadow-sm">
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2 mb-5">
                    <div class="p-2 bg-emerald-100 rounded-lg text-emerald-600"><i class="fas fa-chart-pie"></i></div>
                    Revenue by Payment Channel (Today)
                </h3>
                <canvas id="revenueByMethodChart" height="280"></canvas>
            </div>

            {{-- Security Deposit Summary --}}
            <div class="bg-white/90 backdrop-blur-xl border border-slate-200/60 rounded-3xl p-7 shadow-sm">
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2 mb-5">
                    <div class="p-2 bg-amber-100 rounded-lg text-amber-600"><i class="fas fa-shield-alt"></i></div>
                    Security Deposit Summary
                </h3>
                <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-6 text-white">
                    <p class="text-xs text-slate-400 uppercase font-bold tracking-wider">Total Security Deposits Held</p>
                    <p class="text-3xl font-black font-mono mt-2 text-amber-400">TZS {{ number_format($finData['total_sec_dep']) }}</p>
                    <div class="mt-4 pt-3 border-t border-slate-700 text-xs text-slate-400 flex justify-between">
                        <span>Active Float: <strong class="text-emerald-400">TZS {{ number_format($finData['total_float']) }}</strong></span>
                        <span>Combined: <strong class="text-white">TZS {{ number_format($finData['total_float'] + $finData['total_sec_dep']) }}</strong></span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- TAB 3: AGENT PERFORMANCE --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    @if($activeTab === 'agent_performance' && !empty($agentPerfData))
        {{-- Today's Leaderboard --}}
        <div class="bg-white/90 backdrop-blur-xl border border-slate-200/60 rounded-3xl p-7 shadow-sm mb-8">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2 mb-5">
                <div class="p-2 bg-amber-100 rounded-lg text-amber-600"><i class="fas fa-trophy"></i></div>
                Today's Sales Leaderboard
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-3 text-left">#</th>
                            <th class="py-3 px-3 text-left">Seller</th>
                            <th class="py-3 px-3 text-left">Channel</th>
                            <th class="py-3 px-3 text-center">Tickets</th>
                            <th class="py-3 px-3 text-right">Revenue (TZS)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($agentPerfData['topSellersToday'] as $idx => $seller)
                            <tr class="{{ $idx === 0 ? 'bg-gradient-to-r from-amber-50 to-yellow-50' : 'hover:bg-blue-50/50' }} transition-colors">
                                <td class="py-3 px-3">
                                    <div class="h-7 w-7 rounded-full {{ $idx === 0 ? 'bg-amber-500 text-white' : ($idx < 3 ? 'bg-blue-100 text-blue-600' : 'bg-slate-100 text-slate-500') }} flex items-center justify-center text-xs font-black">{{ $idx + 1 }}</div>
                                </td>
                                <td class="py-3 px-3 font-bold text-slate-800">{{ $seller->name }}</td>
                                <td class="py-3 px-3"><span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $seller->domain === 'agent' ? 'bg-violet-100 text-violet-700' : 'bg-blue-100 text-blue-700' }}">{{ $seller->domain }}</span></td>
                                <td class="py-3 px-3 text-center font-mono">{{ $seller->tickets }}</td>
                                <td class="py-3 px-3 text-right font-black text-slate-800">{{ number_format($seller->revenue) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-slate-400">No sales today.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Monthly Agent vs Staff Performance --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            {{-- Top Agents This Month --}}
            <div class="bg-white/90 backdrop-blur-xl border border-slate-200/60 rounded-3xl p-7 shadow-sm">
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2 mb-5">
                    <div class="p-2 bg-violet-100 rounded-lg text-violet-600"><i class="fas fa-store"></i></div>
                    Top Agencies ({{ now()->format('F') }})
                </h3>
                <div class="space-y-3">
                    @forelse ($agentPerfData['topAgentsMonthly'] as $idx => $agent)
                        <div class="flex items-center justify-between p-3 rounded-xl {{ $idx === 0 ? 'bg-gradient-to-r from-violet-50 to-purple-50 border border-violet-200' : 'bg-slate-50 border border-slate-100' }}">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full {{ $idx === 0 ? 'bg-violet-500 text-white' : 'bg-slate-200 text-slate-600' }} flex items-center justify-center text-xs font-black">{{ $idx + 1 }}</div>
                                <div>
                                    <p class="text-sm font-bold text-slate-800">{{ $agent->agent_name }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $agent->total_tickets }} tickets</p>
                                </div>
                            </div>
                            <span class="text-sm font-black {{ $idx === 0 ? 'text-violet-700' : 'text-slate-700' }}">TZS {{ number_format($agent->total_revenue) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400 text-center py-6">No agent bookings this month.</p>
                    @endforelse
                </div>
            </div>

            {{-- Top Counter Staff This Month --}}
            <div class="bg-white/90 backdrop-blur-xl border border-slate-200/60 rounded-3xl p-7 shadow-sm">
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2 mb-5">
                    <div class="p-2 bg-blue-100 rounded-lg text-blue-600"><i class="fas fa-users-cog"></i></div>
                    Top Counter Staff ({{ now()->format('F') }})
                </h3>
                <div class="space-y-3">
                    @forelse ($agentPerfData['topStaffMonthly'] as $idx => $staff)
                        <div class="flex items-center justify-between p-3 rounded-xl {{ $idx === 0 ? 'bg-gradient-to-r from-blue-50 to-sky-50 border border-blue-200' : 'bg-slate-50 border border-slate-100' }}">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full {{ $idx === 0 ? 'bg-blue-500 text-white' : 'bg-slate-200 text-slate-600' }} flex items-center justify-center text-xs font-black">{{ $idx + 1 }}</div>
                                <div>
                                    <p class="text-sm font-bold text-slate-800">{{ $staff->staff_name }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $staff->total_tickets }} tickets</p>
                                </div>
                            </div>
                            <span class="text-sm font-black {{ $idx === 0 ? 'text-blue-700' : 'text-slate-700' }}">TZS {{ number_format($staff->total_revenue) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400 text-center py-6">No counter bookings this month.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Bookings by Channel --}}
        <div class="bg-white/90 backdrop-blur-xl border border-slate-200/60 rounded-3xl p-7 shadow-sm">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2 mb-5">
                <div class="p-2 bg-teal-100 rounded-lg text-teal-600"><i class="fas fa-chart-bar"></i></div>
                Today's Bookings by Channel
            </h3>
            <canvas id="channelChart" height="200"></canvas>
        </div>
    @endif

    {{-- ═══ CHART.JS SCRIPTS ═══ --}}
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        // Live clock
        setInterval(() => {
            const el = document.getElementById('live-clock');
            if (el) el.textContent = new Date().toLocaleTimeString('en-US', { hour12: true });
        }, 1000);

        @if($activeTab === 'operations' && !empty($opsData))
        // Revenue by Route bar chart
        const routeCtx = document.getElementById('revenueByRouteChart');
        if (routeCtx) {
            new Chart(routeCtx, {
                type: 'bar',
                data: {
                    labels: @json($opsData['revenueByRoute']->pluck('route_name')),
                    datasets: [{
                        label: 'Revenue (TZS)',
                        data: @json($opsData['revenueByRoute']->pluck('revenue')),
                        backgroundColor: ['#3b82f6','#6366f1','#8b5cf6','#06b6d4','#10b981','#f59e0b'],
                        borderRadius: 8, borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: v => 'TZS ' + v.toLocaleString() } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
        @endif

        @if($activeTab === 'finance' && !empty($finData))
        // Revenue by Payment Method pie chart
        const methodCtx = document.getElementById('revenueByMethodChart');
        if (methodCtx) {
            new Chart(methodCtx, {
                type: 'doughnut',
                data: {
                    labels: @json($finData['revenueByMethod']->pluck('method')),
                    datasets: [{
                        data: @json($finData['revenueByMethod']->pluck('total')),
                        backgroundColor: ['#10b981','#3b82f6','#f59e0b','#ef4444','#8b5cf6'],
                    }]
                },
                options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
            });
        }
        @endif

        @if($activeTab === 'agent_performance' && !empty($agentPerfData))
        // Bookings by Channel bar chart
        const chCtx = document.getElementById('channelChart');
        if (chCtx) {
            const channelLabels = @json($agentPerfData['bookingsByChannel']->pluck('channel')->map(fn($c) => ucfirst(str_replace('_', ' ', $c))));
            new Chart(chCtx, {
                type: 'bar',
                data: {
                    labels: channelLabels,
                    datasets: [
                        { label: 'Tickets', data: @json($agentPerfData['bookingsByChannel']->pluck('tickets')), backgroundColor: '#6366f1', borderRadius: 6 },
                        { label: 'Revenue (TZS)', data: @json($agentPerfData['bookingsByChannel']->pluck('revenue')), backgroundColor: '#10b981', borderRadius: 6, yAxisID: 'y1' },
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true, position: 'left', title: { display: true, text: 'Tickets' } },
                        y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { callback: v => 'TZS ' + v.toLocaleString() } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
        @endif
    </script>
    @endpush
</x-layouts.admin>
