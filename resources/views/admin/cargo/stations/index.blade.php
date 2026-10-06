<x-layouts.admin>
    <x-slot:title>Stations Management</x-slot:title>
    <x-slot:header>Stations & Destinations</x-slot:header>

    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Station Directory</h2>
            <p class="text-slate-500 text-sm">Manage arrival and departure destinations.</p>
        </div>
        @can('port:create')
        <a href="{{ route('admin.cargo.stations.create') }}" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl shadow-sm transition-colors flex items-center gap-2">
            <i class="fas fa-plus"></i> Add Station
        </a>
        @endcan
    </div>

    <!-- Filters & Search -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm mb-6 p-4 flex flex-col md:flex-row gap-4 justify-between items-center">
        <form action="{{ route('admin.cargo.stations.index') }}" method="GET" class="flex flex-col md:flex-row gap-4 w-full">
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search ports by name, code, or city..." 
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
                        <th class="px-6 py-4 font-semibold text-slate-700">Code</th>
                        <th class="px-6 py-4 font-semibold text-slate-700">Station Name</th>
                        <th class="px-6 py-4 font-semibold text-slate-700">City</th>
                        <th class="px-6 py-4 font-semibold text-slate-700 text-center">Status</th>
                        <th class="px-6 py-4 font-semibold text-slate-700 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($stations as $station)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-bold text-slate-800 bg-slate-100 px-2 py-1 rounded-md">{{ $station->code }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center">
                                        <i class="fas fa-map-marker-alt text-indigo-500"></i>
                                    </div>
                                    <span class="font-bold text-slate-800">{{ $station->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-700 font-medium">
                                {{ $station->city ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider
                                    {{ match(strtolower($station->status)) {
                                        'active' => 'bg-emerald-100 text-emerald-700',
                                        default => 'bg-slate-100 text-slate-600'
                                    } }}">
                                    {{ $station->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right flex justify-end gap-1.5">
                                @php
                                    $canUpdate = auth()->user()->can('port:update');
                                    $canDelete = auth()->user()->can('port:delete');
                                @endphp
                                
                                @if($canUpdate && strtolower($station->status) === 'active')
                                    <a href="{{ route('admin.cargo.stations.edit', $station) }}" class="h-8 w-8 rounded bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors" title="Edit">
                                        <i class="fas fa-pen text-xs"></i>
                                    </a>
                                @else
                                    <button disabled class="h-8 w-8 rounded bg-slate-50 text-slate-300 cursor-not-allowed flex items-center justify-center" title="Edit ({{ strtolower($station->status) !== 'active' ? 'Station is suspended' : 'No Permission' }})">
                                        <i class="fas fa-pen text-xs"></i>
                                    </button>
                                @endif
                                
                                @if($canDelete && strtolower($station->status) === 'active')
                                    <button type="button" 
                                            @click="$dispatch('open-action-modal', {
                                                formAction: '{{ route('admin.cargo.stations.update-status', $station) }}',
                                                formMethod: 'PATCH',
                                                statusValue: 'inactive',
                                                title: 'Suspend Station',
                                                message: 'Are you sure you want to suspend this port? This will set its status to inactive.',
                                                buttonText: 'Suspend',
                                                buttonColor: 'amber'
                                            })"
                                            class="h-8 w-8 rounded bg-amber-100 hover:bg-amber-200 text-amber-600 flex items-center justify-center transition-colors" 
                                            title="Suspend">
                                        <i class="fas fa-ban text-xs"></i>
                                    </button>
                                @else
                                    <button type="button" disabled
                                            class="h-8 w-8 rounded bg-amber-50 text-amber-300 opacity-70 cursor-not-allowed flex items-center justify-center transition-colors" 
                                            title="Suspend{{ strtolower($station->status) !== 'active' ? ' (Already suspended)' : ' (No Permission)' }}">
                                        <i class="fas fa-ban text-xs"></i>
                                    </button>
                                @endif

                                @if($canDelete && strtolower($station->status) !== 'active')
                                    <button type="button" 
                                            @click="$dispatch('open-action-modal', {
                                                formAction: '{{ route('admin.cargo.stations.update-status', $station) }}',
                                                formMethod: 'PATCH',
                                                statusValue: 'active',
                                                title: 'Activate Station',
                                                message: 'Are you sure you want to activate this port?',
                                                buttonText: 'Activate',
                                                buttonColor: 'emerald'
                                            })"
                                            class="h-8 w-8 rounded bg-emerald-100 hover:bg-emerald-200 text-emerald-600 flex items-center justify-center transition-colors" 
                                            title="Activate">
                                        <i class="fas fa-check text-xs"></i>
                                    </button>
                                @else
                                    <button type="button" disabled
                                            class="h-8 w-8 rounded bg-emerald-50 text-emerald-300 opacity-70 cursor-not-allowed flex items-center justify-center transition-colors" 
                                            title="Activate{{ strtolower($station->status) === 'active' ? ' (Already active)' : ' (No Permission)' }}">
                                        <i class="fas fa-check text-xs"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                <div class="h-16 w-16 mx-auto bg-slate-50 rounded-full flex items-center justify-center mb-3">
                                    <i class="fas fa-map text-2xl text-slate-400"></i>
                                </div>
                                <p class="font-medium">No ports registered yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($stations->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">
                {{ $stations->links() }}
            </div>
        @endif
    </div>

    <!-- Reusable Action Modal -->
    <x-admin.action-modal />
</x-layouts.admin>
