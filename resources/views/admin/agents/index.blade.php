<x-layouts.admin>
<x-slot:title>Agency Partners & Float Management</x-slot:title>
<x-slot:header>Sales Agencies & Float Management</x-slot:header>

<div class="px-4 sm:px-6 lg:px-8 py-6 space-y-5">

    {{-- Alerts --}}
    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 flex items-center gap-3">
            <i class="fas fa-check-circle text-emerald-500"></i>
            <p class="text-sm font-semibold text-emerald-800">{{ session('success') }}</p>
        </div>
    @endif
    @if($errors->any())
        <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3">
            <p class="font-bold text-red-800 flex items-center gap-2 mb-1"><i class="fas fa-exclamation-triangle"></i> Please fix the errors below:</p>
            <ul class="list-disc list-inside text-sm space-y-0.5 text-red-700">
                @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <div x-data="{ showCreateModal: false }">

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
            <div>
                <h1 class="text-2xl font-extrabold text-surface-900 tracking-tight">Agency Partners</h1>
                <p class="text-sm text-surface-500 mt-0.5">Manage float balances, commission rates, and agency accounts.</p>
            </div>
            <button @click="showCreateModal = true"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm shadow-brand-200">
                <i class="fas fa-plus text-xs"></i> Register Agency
            </button>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-2xl border border-surface-200/80 shadow-sm px-5 py-4 mb-5">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[200px] space-y-1">
                    <label class="text-xs font-semibold text-surface-500 uppercase tracking-wider">Search</label>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-surface-400 text-xs"></i>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Agency name or code..."
                            class="w-full pl-8 pr-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none bg-surface-50 focus:bg-white transition-all">
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-surface-500 uppercase tracking-wider">Type</label>
                    <select name="type" onchange="this.form.submit()"
                        class="px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 outline-none bg-surface-50 text-surface-700">
                        <option value="">All Types</option>
                        <option value="direct" {{ request('type')=='direct'?'selected':'' }}>Direct Partner</option>
                        <option value="reseller" {{ request('type')=='reseller'?'selected':'' }}>Reseller</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-surface-500 uppercase tracking-wider">Status</label>
                    <select name="status" onchange="this.form.submit()"
                        class="px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 outline-none bg-surface-50 text-surface-700">
                        <option value="">All Statuses</option>
                        <option value="1" {{ request('status')==='1'?'selected':'' }}>Active</option>
                        <option value="0" {{ request('status')==='0'?'selected':'' }}>Inactive</option>
                    </select>
                </div>
                <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl transition-colors">
                    <i class="fas fa-filter text-xs"></i> Filter
                </button>
                @if(request()->hasAny(['search','type','status']))
                    <a href="{{ route('admin.agents.index') }}"
                        class="text-xs font-semibold text-surface-500 hover:text-surface-800 transition py-2.5">Reset</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-surface-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-50 border-b border-surface-100">
                            <th class="text-left px-5 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Agency & Code</th>
                            <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Contact</th>
                            <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Type / Commission</th>
                            <th class="text-left px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Float & Credit</th>
                            <th class="text-center px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Staff</th>
                            <th class="text-center px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Status</th>
                            <th class="text-center px-4 py-3 text-[11px] font-bold text-surface-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($agents as $agent)
                        <tr class="border-b border-surface-50 hover:bg-surface-50/80 transition-colors">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-xl bg-brand-50 border border-brand-100 flex items-center justify-center text-brand-600 shrink-0">
                                        <i class="fas fa-building text-sm"></i>
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.agents.show', $agent) }}"
                                            class="font-bold text-surface-900 hover:text-brand-600 transition text-sm block">
                                            {{ $agent->agency_name }}
                                        </a>
                                        <span class="font-mono text-[11px] font-bold bg-surface-100 text-surface-600 px-1.5 py-0.5 rounded mt-0.5 inline-block">
                                            {{ $agent->agent_code }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-medium text-surface-800 text-sm">{{ $agent->user?->first_name }} {{ $agent->user?->last_name }}</p>
                                <p class="text-xs text-surface-500 flex items-center gap-1 mt-0.5">
                                    <i class="fas fa-envelope text-surface-400 text-[10px]"></i>
                                    {{ $agent->email ?? $agent->user?->email }}
                                </p>
                                @if($agent->contact_mobile)
                                <p class="text-xs text-surface-500 flex items-center gap-1 mt-0.5">
                                    <i class="fas fa-phone text-surface-400 text-[10px]"></i>
                                    {{ $agent->contact_mobile }}
                                </p>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold border
                                    {{ $agent->agent_type === 'direct' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-violet-50 text-violet-700 border-violet-200' }}">
                                    {{ ucfirst($agent->agent_type) }}
                                </span>
                                <p class="text-xs text-surface-500 mt-1">
                                    Commission: <span class="font-bold text-brand-600">{{ $agent->commission_rate }}%</span>
                                </p>
                            </td>
                            <td class="px-4 py-4">
                                @if($agent->account)
                                <div class="space-y-1">
                                    <div class="flex justify-between gap-3 text-xs">
                                        <span class="text-surface-400">Float:</span>
                                        <span class="font-mono font-bold {{ $agent->account->balance < 100000 ? 'text-amber-600' : 'text-emerald-600' }}">
                                            {{ number_format($agent->account->balance, 0) }} {{ $agent->account->currency }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between gap-3 text-xs">
                                        <span class="text-surface-400">Credit:</span>
                                        <span class="font-mono font-semibold text-surface-700">
                                            {{ number_format($agent->account->credit_limit, 0) }} {{ $agent->account->currency }}
                                        </span>
                                    </div>
                                </div>
                                @else
                                    <span class="text-xs font-semibold text-red-500 italic">No Account</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-surface-100 text-surface-700 text-xs font-bold">
                                    <i class="fas fa-users text-surface-400 text-[10px]"></i> {{ $agent->associates_count }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                @if($agent->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200 text-[11px] font-bold">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-red-100 text-red-700 border border-red-200 text-[11px] font-bold">
                                        <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span> Suspended
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-center">
                                <a href="{{ route('admin.agents.show', $agent) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-brand-600 bg-brand-50 hover:bg-brand-100 border border-brand-100 rounded-lg transition-colors">
                                    Manage <i class="fas fa-chevron-right text-[9px]"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-14 text-center">
                                <i class="fas fa-store-alt text-3xl text-surface-300 mb-3 block"></i>
                                <p class="text-surface-400 text-sm">No agencies found matching your filters.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($agents->hasPages())
            <div class="bg-surface-50 px-6 py-4 border-t border-surface-100">
                {{ $agents->links() }}
            </div>
            @endif
        </div>

        {{-- Register Agency Modal --}}
        <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-surface-950/50 backdrop-blur-sm" @click="showCreateModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

                <div class="sticky top-0 flex items-center justify-between px-6 py-4 border-b border-surface-100"
                    style="background: linear-gradient(135deg, #0f172a, #0e2240)">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-xl flex items-center justify-center" style="background: rgba(250,189,0,0.15)">
                            <i class="fas fa-store-alt text-sm" style="color: #FABD00"></i>
                        </div>
                        <h3 class="font-bold text-white text-sm">Register Agency Partner</h3>
                    </div>
                    <button @click="showCreateModal = false" class="h-7 w-7 flex items-center justify-center rounded-lg transition-all" style="color: rgba(255,255,255,0.4)">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                <form action="{{ route('admin.agents.store') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-surface-600 uppercase tracking-wider">Agency / Company Name *</label>
                            <input type="text" name="agency_name" required placeholder="e.g. Zanzibar Express Travel Ltd"
                                class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-surface-50 focus:bg-white">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-surface-600 uppercase tracking-wider">Partner Type *</label>
                            <select name="agent_type" required
                                class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-white">
                                <option value="direct">Direct Partner (Primary agency)</option>
                                <option value="reseller">Reseller (Sub-distributor)</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-surface-600 uppercase tracking-wider">Primary Email *</label>
                            <input type="email" name="email" required placeholder="agency@example.com"
                                class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-surface-50 focus:bg-white">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-surface-600 uppercase tracking-wider">Contact Mobile</label>
                            <input type="text" name="contact_mobile" placeholder="+255 712 345 678"
                                class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-surface-50 focus:bg-white">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-surface-600 uppercase tracking-wider">Owner First Name *</label>
                            <input type="text" name="first_name" required placeholder="John"
                                class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-surface-50 focus:bg-white">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-surface-600 uppercase tracking-wider">Owner Last Name *</label>
                            <input type="text" name="last_name" required placeholder="Doe"
                                class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-surface-50 focus:bg-white">
                        </div>
                        <div class="sm:col-span-2 space-y-1.5">
                            <label class="block text-xs font-bold text-surface-600 uppercase tracking-wider">Office Address</label>
                            <textarea name="address" rows="2" placeholder="Street, Building, City..."
                                class="w-full px-3 py-2.5 text-sm rounded-xl border border-surface-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all bg-surface-50 focus:bg-white resize-none"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-2 border-t border-surface-100">
                        <button type="button" @click="showCreateModal = false"
                            class="px-5 py-2.5 text-sm font-semibold text-surface-600 rounded-xl border border-surface-200 hover:bg-surface-50 transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-6 py-2.5 text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition-colors shadow-sm flex items-center gap-2">
                            <i class="fas fa-save text-xs"></i> Create Agency
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
</x-layouts.admin>
