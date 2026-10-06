<x-layouts.admin>
    <x-slot:title>Ferries Management</x-slot:title>
    <x-slot:header>Ferries Fleet</x-slot:header>

    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="text-3xl font-extrabold text-slate-800 tracking-tight">Fleet Overview</h2>
            <p class="text-slate-500 text-sm mt-1">Manage all registered vessels, schedules, and route prices in your fleet.</p>
        </div>
        @php $canCreateFerry = auth()->user()->hasPermission('ferry:create'); @endphp
        @if($canCreateFerry)
            <a href="{{ route('admin.ferries.create') }}" class="px-5 py-2.5 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-600 hover:to-brand-700 text-white font-semibold rounded-xl shadow-md hover:shadow-lg transition-all flex items-center gap-2 transform hover:-translate-y-0.5">
                <i class="fas fa-ship"></i> Register New Vessel
            </a>
        @else
            <button disabled class="px-5 py-2.5 bg-slate-100 text-slate-400 font-semibold rounded-xl border border-slate-200 cursor-not-allowed flex items-center gap-2" title="Register New Vessel (No Permission)">
                <i class="fas fa-ship"></i> Register New Vessel
            </button>
        @endif
    </div>

    <!-- Filters & Search -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm mb-6 p-4 flex flex-col md:flex-row gap-4 justify-between items-center">
        <form action="{{ route('admin.ferries.index') }}" method="GET" class="flex flex-col md:flex-row gap-4 w-full">
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search vessels by name, type, or port..." 
                       class="w-full pl-11 pr-4 py-2.5 rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
            </div>
            <div class="w-full md:w-64">
                <select name="status" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive / Suspended</option>
                    <option value="maintenance" {{ request('status') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                </select>
            </div>
            <noscript><button type="submit" class="px-4 py-2 bg-brand-500 text-white rounded-xl">Filter</button></noscript>
        </form>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 border-b border-slate-200/60">
                    <tr>
                        <th class="px-6 py-4 font-semibold text-slate-700">Vessel Name</th>
                        <th class="px-6 py-4 font-semibold text-slate-700">Type / Port</th>
                        <th class="px-6 py-4 font-semibold text-slate-700 text-center">Classes</th>
                        <th class="px-6 py-4 font-semibold text-slate-700 text-center">Total Pax</th>
                        <th class="px-6 py-4 font-semibold text-slate-700 text-center">Status</th>
                        <th class="px-6 py-4 font-semibold text-slate-700 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($ferries as $ferry)
                        <tr class="hover:bg-slate-50/80 transition-all group">
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-brand-50 to-brand-100 border border-brand-200/50 flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform">
                                        <i class="fas fa-ship text-brand-600 text-lg"></i>
                                    </div>
                                    <div>
                                        <span class="font-bold text-slate-800 text-base block">{{ $ferry->name }}</span>
                                        <span class="text-xs text-slate-500 font-medium tracking-wide uppercase">{{ $ferry->type ?? 'Passenger Ferry' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-map-marker-alt text-slate-300"></i>
                                    <span class="text-slate-700 font-medium">{{ $ferry->port_of_registry ?? 'Unregistered' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-5 text-center">
                                <span class="bg-slate-100/80 border border-slate-200 text-slate-600 px-3 py-1.5 rounded-lg font-semibold text-xs shadow-sm">
                                    <i class="fas fa-couch text-slate-400 mr-1"></i> {{ $ferry->classes->count() }} Classes
                                </span>
                            </td>
                            <td class="px-6 py-5 text-center font-bold text-slate-700">
                                {{ number_format($ferry->capacity) }} <span class="text-xs text-slate-400 font-normal">Pax</span>
                            </td>
                            <td class="px-6 py-5 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider shadow-sm border
                                    {{ match($ferry->status) {
                                        'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200/60',
                                        'maintenance' => 'bg-amber-50 text-amber-700 border-amber-200/60',
                                        default => 'bg-slate-50 text-slate-600 border-slate-200/60'
                                    } }}">
                                    @if($ferry->status === 'active') <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span> @endif
                                    {{ $ferry->status }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.ferries.show', $ferry) }}" class="inline-flex items-center justify-center h-8 w-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-brand-600 hover:border-brand-200 hover:bg-brand-50 hover:shadow-sm transition-all" title="View Profile">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                    
                                    @php 
                                        $canUpdate = auth()->user()->hasPermission('ferry:update');
                                        $canDelete = auth()->user()->hasPermission('ferry:delete');
                                        $isActive = strtolower($ferry->status) === 'active';
                                    @endphp

                                    @if($canUpdate && $isActive)
                                        <a href="{{ route('admin.ferries.edit', $ferry) }}" class="h-8 w-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors" title="Edit">
                                            <i class="fas fa-pen text-xs"></i>
                                        </a>
                                    @else
                                        <button disabled class="h-8 w-8 rounded-xl bg-slate-50 text-slate-300 cursor-not-allowed flex items-center justify-center" title="Edit ({{ !$isActive ? 'Ferry is suspended' : 'No Permission' }})">
                                            <i class="fas fa-pen text-xs"></i>
                                        </button>
                                    @endif
                                    
                                    @if($canDelete && $isActive)
                                        <button type="button" 
                                                @click="$dispatch('open-action-modal', {
                                                    formAction: '{{ route('admin.ferries.update-status', $ferry) }}',
                                                    formMethod: 'PATCH',
                                                    statusValue: 'inactive',
                                                    title: 'Suspend Ferry',
                                                    message: 'Are you sure you want to suspend this ferry? This will set its status to inactive.',
                                                    buttonText: 'Suspend',
                                                    buttonColor: 'amber'
                                                })"
                                                class="h-8 w-8 rounded-xl bg-amber-100 hover:bg-amber-200 text-amber-600 flex items-center justify-center transition-colors" 
                                                title="Suspend">
                                            <i class="fas fa-ban text-xs"></i>
                                        </button>
                                    @else
                                        <button type="button" disabled
                                                class="h-8 w-8 rounded-xl bg-amber-50 text-amber-300 opacity-70 cursor-not-allowed flex items-center justify-center transition-colors" 
                                                title="Suspend{{ !$isActive ? ' (Already suspended)' : ' (No Permission)' }}">
                                            <i class="fas fa-ban text-xs"></i>
                                        </button>
                                    @endif

                                    @if($canDelete && !$isActive)
                                        <button type="button" 
                                                @click="$dispatch('open-action-modal', {
                                                    formAction: '{{ route('admin.ferries.update-status', $ferry) }}',
                                                    formMethod: 'PATCH',
                                                    statusValue: 'active',
                                                    title: 'Activate Ferry',
                                                    message: 'Are you sure you want to activate this ferry?',
                                                    buttonText: 'Activate',
                                                    buttonColor: 'emerald'
                                                })"
                                                class="h-8 w-8 rounded-xl bg-emerald-100 hover:bg-emerald-200 text-emerald-600 flex items-center justify-center transition-colors" 
                                                title="Activate">
                                            <i class="fas fa-check text-xs"></i>
                                        </button>
                                    @else
                                        <button type="button" disabled
                                                class="h-8 w-8 rounded-xl bg-emerald-50 text-emerald-300 opacity-70 cursor-not-allowed flex items-center justify-center transition-colors" 
                                                title="Activate{{ $isActive ? ' (Already active)' : ' (No Permission)' }}">
                                            <i class="fas fa-check text-xs"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                <div class="h-16 w-16 mx-auto bg-slate-50 rounded-full flex items-center justify-center mb-3">
                                    <i class="fas fa-anchor text-2xl text-slate-400"></i>
                                </div>
                                <p class="font-medium">No ferries registered yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($ferries->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">
                {{ $ferries->links() }}
            </div>
        @endif
    </div>

    <!-- Reusable Action Modal -->
    <x-admin.action-modal />
</x-layouts.admin>
