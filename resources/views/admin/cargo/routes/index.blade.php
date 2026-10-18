<x-layouts.admin>
    <x-slot:title>Routes Management</x-slot:title>
    <x-slot:header>Sailing Routes</x-slot:header>

    @php $statusNeedsApproval = app(\App\Services\DualControlService::class)->requiresDualControl('route_status_update'); @endphp

    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Routes Directory</h2>
            <p class="text-slate-500 text-sm">Manage connections between ports.</p>
        </div>
        @php $canCreateRoute = auth()->user()->hasPermission('route:create'); @endphp
        @if($canCreateRoute)
        <a href="{{ route('admin.cargo.routes.create') }}" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl shadow-sm transition-colors flex items-center gap-2">
            <i class="fas fa-plus"></i> Add Route
        </a>
        @else
        <button disabled class="px-5 py-2.5 bg-slate-100 border border-slate-200 text-slate-400 font-medium rounded-xl cursor-not-allowed flex items-center gap-2" title="Add Route (No Permission)">
            <i class="fas fa-plus"></i> Add Route
        </button>
        @endif
    </div>

    <!-- Filters & Search -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm mb-6 p-4 flex flex-col md:flex-row gap-4 justify-between items-center">
        <form action="{{ route('admin.cargo.routes.index') }}" method="GET" class="flex flex-col md:flex-row gap-4 w-full">
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search routes by name or code..." 
                       class="w-full rounded-xl border-slate-200 bg-slate-50 hover:bg-white focus:bg-white pl-10 pr-4 py-3.5 text-sm font-medium text-slate-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 transition-colors">
            </div>
            <div class="w-full md:w-64">
                <select name="status" class="w-full rounded-xl border-slate-200 bg-slate-50 hover:bg-white focus:bg-white px-4 py-3.5 text-sm font-medium text-slate-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 transition-colors" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive / Suspended</option>
                </select>
            </div>
            <noscript><button type="submit" class="px-4 py-2 bg-brand-500 text-white rounded-xl">Filter</button></noscript>
        </form>
    </div>

    @if (session('success'))
        <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex items-start gap-3">
            <i class="fas fa-check-circle mt-1"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 border-b border-slate-200/60">
                    <tr>
                        <th class="px-6 py-4 font-semibold text-slate-700">Route Name</th>
                        <th class="px-6 py-4 font-semibold text-slate-700 text-center">Path</th>
                        <th class="px-6 py-4 font-semibold text-slate-700 text-center">Distance</th>
                        <th class="px-6 py-4 font-semibold text-slate-700 text-center">Status</th>
                        <th class="px-6 py-4 font-semibold text-slate-700 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($routes as $route)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-bold text-slate-800">{{ $route->name }}</span>
                                <x-dual-control.pending-badge subject="route:{{ $route->id }}" />
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-3">
                                    <span class="font-semibold text-slate-600 bg-slate-100 px-2 py-1 rounded">{{ $route->origin->code ?? 'N/A' }}</span>
                                    <i class="fas fa-arrow-right text-slate-400"></i>
                                    <span class="font-semibold text-slate-600 bg-slate-100 px-2 py-1 rounded">{{ $route->destination->code ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center text-slate-700 font-medium">
                                {{ $route->distance_nm ? $route->distance_nm . ' NM' : '-' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider
                                    {{ match($route->status) {
                                        'active' => 'bg-emerald-100 text-emerald-700',
                                        default => 'bg-slate-100 text-slate-600'
                                    } }}">
                                    {{ $route->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.cargo.routes.show', $route) }}" class="inline-flex items-center justify-center h-8 w-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-brand-600 hover:border-brand-200 hover:bg-brand-50 hover:shadow-sm transition-all" title="View Profile">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                    
                                    @php 
                                        $canUpdate = auth()->user()->hasPermission('route:update');
                                        $canDelete = auth()->user()->hasPermission('route:delete');
                                        $isActive = strtolower($route->status) === 'active';
                                    @endphp

                                    @if($canUpdate && $isActive)
                                        <a href="{{ route('admin.cargo.routes.edit', $route) }}" class="h-8 w-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors" title="Edit">
                                            <i class="fas fa-pen text-xs"></i>
                                        </a>
                                    @else
                                        <button disabled class="h-8 w-8 rounded-xl bg-slate-50 text-slate-300 cursor-not-allowed flex items-center justify-center" title="Edit ({{ !$isActive ? 'Route is suspended' : 'No Permission' }})">
                                            <i class="fas fa-pen text-xs"></i>
                                        </button>
                                    @endif
                                    
                                    @if($canDelete && $isActive)
                                        <button type="button" 
                                                @click="$dispatch('open-action-modal', {
                                                    formAction: '{{ route('admin.cargo.routes.update-status', $route) }}',
                                                    takesEffect: true, needsApproval: @js($statusNeedsApproval),
                                                    formMethod: 'PATCH',
                                                    statusValue: 'inactive',
                                                    title: 'Suspend Route',
                                                    message: 'Are you sure you want to suspend this route? This will set its status to inactive.',
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
                                                    formAction: '{{ route('admin.cargo.routes.update-status', $route) }}',
                                                    takesEffect: true, needsApproval: @js($statusNeedsApproval),
                                                    formMethod: 'PATCH',
                                                    statusValue: 'active',
                                                    title: 'Activate Route',
                                                    message: 'Are you sure you want to activate this route?',
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
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                <div class="h-16 w-16 mx-auto bg-slate-50 rounded-full flex items-center justify-center mb-3">
                                    <i class="fas fa-route text-2xl text-slate-400"></i>
                                </div>
                                <p class="font-medium">No routes registered yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($routes->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">
                {{ $routes->links() }}
            </div>
        @endif
    </div>

    <!-- Reusable Action Modal -->
    <x-admin.action-modal />
</x-layouts.admin>
