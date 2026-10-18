<x-layouts.admin>
    <x-slot:title>Route Profile: {{ $route->name }}</x-slot:title>
    <x-slot:header>Route Management</x-slot:header>

    @include('admin.cargo.partials.ui')

    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.cargo.routes.index') }}" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-800">{{ $route->name }}</h2>
                <x-dual-control.pending-badge subject="route:{{ $route->id }}" />
                <p class="text-slate-500 text-sm">Code: {{ $route->code }} &bull; Distance: {{ $route->distance_nm }} NM</p>
            </div>
        </div>
        <div>
            <span class="inline-flex items-center rounded-md {{ $route->status === 'active' ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-slate-50 text-slate-600 ring-slate-500/10' }} px-2 py-1 text-xs font-medium ring-1 ring-inset">
                {{ ucfirst($route->status) }}
            </span>
        </div>
    </div>

    <div x-data="{ activeTab: 'prices' }" class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden min-h-[600px]">
        <!-- Tabs Header -->
        <div class="border-b border-slate-200 bg-slate-50/50">
            <nav class="-mb-px flex space-x-8 px-8" aria-label="Tabs">
                <button @click="activeTab = 'details'"
                        :class="activeTab === 'details' ? 'border-brand-500 text-brand-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    <i class="fas fa-info-circle mr-2"></i> Route Details
                </button>
                <button @click="activeTab = 'prices'"
                        :class="activeTab === 'prices' ? 'border-brand-500 text-brand-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    <i class="fas fa-money-bill-wave mr-2"></i> Route Prices
                </button>
                <button @click="activeTab = 'frequency'"
                        :class="activeTab === 'frequency' ? 'border-brand-500 text-brand-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    <i class="fas fa-clock mr-2"></i> Route Trips
                </button>
                <button @click="activeTab = 'agents'"
                        :class="activeTab === 'agents' ? 'border-brand-500 text-brand-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    <i class="fas fa-users-cog mr-2"></i> Authorized Agents
                </button>
            </nav>
        </div>

        <!-- Tab Contents -->
        <div class="p-8">
            <!-- Route Details -->
            <div x-show="activeTab === 'details'" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100">
                        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4">Origin</h3>
                        <p class="text-xl font-semibold text-slate-800">{{ $route->origin->name }}</p>
                        <p class="text-slate-500 mt-1">Code: {{ $route->origin->code }}</p>
                    </div>
                    <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100">
                        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4">Destination</h3>
                        <p class="text-xl font-semibold text-slate-800">{{ $route->destination->name }}</p>
                        <p class="text-slate-500 mt-1">Code: {{ $route->destination->code }}</p>
                    </div>
                </div>
            </div>

            <!-- Route Prices (Livewire) -->
            <div x-show="activeTab === 'prices'" x-cloak>
                @include('admin.cargo.partials.route-rate-card', ['route' => $route, 'priceCard' => $priceCard])
            </div>

            <!-- Route Frequency (Livewire) -->
            <div x-show="activeTab === 'frequency'" x-cloak>
                <livewire:admin.route-frequency-manager :route="$route" />
            </div>

            <!-- Authorized Agents -->
            <div x-show="activeTab === 'agents'" x-cloak x-data="{ editAgents: false }">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-slate-800 text-lg">Agents Authorized for this Route</h3>
                    <button @click="editAgents = !editAgents" type="button" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-sm transition">
                        <i class="fas" :class="editAgents ? 'fa-times' : 'fa-edit'"></i> <span x-text="editAgents ? 'Cancel' : 'Manage Agents'"></span>
                    </button>
                </div>
                
                <!-- Display Mode -->
                <div x-show="!editAgents" class="space-y-3">
                    @forelse($route->agents as $agent)
                        <div class="flex items-center justify-between p-4 rounded-xl border {{ $agent->pivot->status === 'active' ? 'bg-white border-slate-200' : 'bg-red-50 border-red-100' }}">
                            <div class="flex flex-col">
                                <span class="font-bold text-slate-800">{{ $agent->agency_name }}</span>
                                <span class="text-xs text-slate-500">{{ $agent->agent_code }}</span>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $agent->pivot->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($agent->pivot->status) }}
                            </span>
                        </div>
                    @empty
                        <div class="p-6 text-center text-slate-500 border-2 border-dashed border-slate-200 rounded-2xl">
                            <i class="fas fa-users-slash text-3xl mb-3 text-slate-300"></i>
                            <p>No agents are currently authorized to sell tickets for this route.</p>
                        </div>
                    @endforelse
                </div>

                <!-- Edit Mode -->
                <form x-show="editAgents" action="{{ route('admin.cargo.routes.update-agents', $route) }}" method="POST" class="space-y-4" style="display: none;">
                    @csrf
                    @method('PUT')
                    @php $assignedAgents = $route->agents->keyBy('id'); @endphp
                    <div class="space-y-2 max-h-96 overflow-y-auto pr-2">
                        @foreach($allAgents as $agent)
                            @php $isAssigned = $assignedAgents->has($agent->id); $status = $isAssigned ? $assignedAgents->get($agent->id)->pivot->status : 'active'; @endphp
                            <div class="flex items-center justify-between p-3 rounded-xl border {{ $isAssigned ? 'bg-brand-50 border-brand-200' : 'bg-white border-slate-200' }}">
                                <label class="flex items-center gap-3 cursor-pointer flex-1">
                                    <input type="hidden" name="agents[{{ $agent->id }}][selected]" value="0">
                                    <input type="checkbox" name="agents[{{ $agent->id }}][selected]" value="1" {{ $isAssigned ? 'checked' : '' }} class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 h-4 w-4">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-800 text-sm">{{ $agent->agency_name }}</span>
                                        <span class="text-xs text-slate-500">{{ $agent->agent_code }}</span>
                                    </div>
                                </label>
                                <select name="agents[{{ $agent->id }}][status]" class="text-xs border-slate-300 rounded-lg py-1 px-2 focus:ring-brand-500 ml-4">
                                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="suspended" {{ $status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                </select>
                            </div>
                        @endforeach
                    </div>
                    <x-dual-control.takes-effect op="route_agents_update" />
                    <div class="flex justify-end pt-4 border-t border-slate-100">
                        <button type="submit" class="px-6 py-2.5 bg-brand-600 text-white font-bold rounded-xl hover:bg-brand-700 transition flex items-center gap-2">
                            <i class="fas fa-save"></i> Save Agent Authorizations
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @include('admin.cargo.partials.fare-editor')
</x-layouts.admin>
