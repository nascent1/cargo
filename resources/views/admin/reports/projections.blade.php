<x-layouts.admin>
    <x-slot:title>Predictive Analytics & Projections</x-slot:title>
    <x-slot:header>Business Intelligence</x-slot:header>

    {{-- Breadcrumb & Title --}}
    <div class="mb-8 flex justify-between items-end">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 font-medium mb-1">
                <a href="{{ route('admin.reports.index') }}" class="hover:text-blue-600 transition-colors"><i class="fas fa-chart-line mr-1"></i> Reports Hub</a>
                <i class="fas fa-chevron-right text-[10px]"></i>
                <span class="text-slate-800">Predictive Analytics</span>
            </div>
            <h1 class="text-3xl font-black text-slate-800 tracking-tight">AI Projections & Insights</h1>
            <p class="text-sm text-slate-500 mt-1">Forward-looking analytics, revenue forecasting, and route optimization algorithms.</p>
        </div>
        <div>
            <div class="bg-indigo-50 border border-indigo-100 text-indigo-700 px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2">
                <i class="fas fa-robot text-xl"></i> Analytics Engine Active
            </div>
        </div>
    </div>

    {{-- 1. Revenue Forecasting --}}
    <div class="mb-10">
        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i class="fas fa-chart-line text-blue-500"></i> 30-Day Revenue Forecast
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm relative overflow-hidden">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-slate-50 rounded-full -z-10"></div>
                <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-2">Previous 30 Days</h3>
                <div class="text-2xl font-black text-slate-800">TZS {{ number_format($data['forecasting']['previous_30_days_revenue']) }}</div>
            </div>
            
            <div class="bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm relative overflow-hidden">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-50 rounded-full -z-10"></div>
                <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-2">Last 30 Days</h3>
                <div class="text-2xl font-black text-blue-600">TZS {{ number_format($data['forecasting']['current_30_days_revenue']) }}</div>
            </div>
            
            <div class="bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm relative overflow-hidden">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-50 rounded-full -z-10"></div>
                <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-2">Growth Trend</h3>
                <div class="text-2xl font-black {{ $data['forecasting']['growth_rate_percent'] >= 0 ? 'text-emerald-500' : 'text-rose-500' }} flex items-center gap-2">
                    <i class="fas {{ $data['forecasting']['growth_rate_percent'] >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
                    {{ $data['forecasting']['growth_rate_percent'] }}%
                </div>
            </div>

            <div class="bg-gradient-to-br from-indigo-600 to-blue-700 rounded-3xl p-6 shadow-xl relative overflow-hidden text-white">
                <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
                <h3 class="text-sm font-bold text-indigo-100 uppercase tracking-wider mb-2">Projected Next 30 Days</h3>
                <div class="text-3xl font-black">TZS {{ number_format($data['forecasting']['projected_next_30_days']) }}</div>
                <div class="mt-2 text-xs text-indigo-200 font-medium"><i class="fas fa-sparkles"></i> AI Confidence: High</div>
            </div>
        </div>
    </div>

    {{-- 2. Loyalty Radar --}}
    <div class="mb-10">
        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i class="fas fa-radar text-violet-500"></i> Customer Churn & Loyalty Radar
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm flex items-center gap-6">
                <div class="h-16 w-16 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl">
                    <i class="fas fa-crown"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Active VIPs</h3>
                    <div class="text-3xl font-black text-slate-800">{{ number_format($data['loyalty']['active_vips']) }}</div>
                    <p class="text-xs text-slate-400 mt-1">Passengers with 5+ bookings in 90 days</p>
                </div>
            </div>
            
            <div class="bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm flex items-center gap-6">
                <div class="h-16 w-16 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center text-3xl">
                    <i class="fas fa-user-slash"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Recently Churned</h3>
                    <div class="text-3xl font-black text-slate-800">{{ number_format($data['loyalty']['recently_churned']) }}</div>
                    <p class="text-xs text-slate-400 mt-1">0 bookings in last 90 days</p>
                </div>
            </div>

            <div class="bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm flex flex-col justify-center">
                <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-2">Recommended Action</h3>
                <div class="font-bold text-violet-700 text-lg flex items-center gap-2">
                    <i class="fas fa-bullhorn"></i> {{ $data['loyalty']['retention_action'] }}
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Route Optimization --}}
    <div>
        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i class="fas fa-route text-amber-500"></i> Route Optimization & Yield Insights
        </h2>

        <div class="bg-white border border-slate-200 shadow-sm rounded-3xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 text-xs uppercase tracking-wider">
                            <th class="p-4 font-semibold">Route</th>
                            <th class="p-4 font-semibold text-center">Avg Load Factor</th>
                            <th class="p-4 font-semibold text-center">Avg No-Show</th>
                            <th class="p-4 font-semibold">AI Recommendation</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        @forelse($data['route_optimization'] as $route)
                            <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                <td class="p-4 font-bold text-slate-800">{{ $route['route'] }}</td>
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-2 font-mono font-bold {{ $route['avg_load'] > 75 ? 'text-emerald-600' : ($route['avg_load'] < 40 ? 'text-rose-600' : 'text-amber-600') }}">
                                        {{ $route['avg_load'] }}%
                                        @if($route['trend'] === 'Up') <i class="fas fa-arrow-up text-emerald-500 text-xs"></i>
                                        @elseif($route['trend'] === 'Down') <i class="fas fa-arrow-down text-rose-500 text-xs"></i>
                                        @else <i class="fas fa-minus text-slate-400 text-xs"></i> @endif
                                    </div>
                                </td>
                                <td class="p-4 text-center font-mono font-bold text-slate-600">{{ $route['no_show_rate'] }}%</td>
                                <td class="p-4">
                                    @if($route['recommendation'] === 'Increase Capacity / Premium Pricing')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                            <i class="fas fa-arrow-trend-up mr-2"></i> {{ $route['recommendation'] }}
                                        </span>
                                    @elseif($route['recommendation'] === 'Promotional Pricing / Reduce Frequency')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                                            <i class="fas fa-tags mr-2"></i> {{ $route['recommendation'] }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
                                            <i class="fas fa-check mr-2"></i> {{ $route['recommendation'] }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-12 text-center text-slate-500">
                                    <i class="fas fa-chart-pie text-4xl mb-4 text-slate-300"></i>
                                    <p>Insufficient data to generate route insights.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.admin>
