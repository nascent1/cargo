<x-layouts.admin>
    <x-slot:title>Transport Unit Profile: {{ $unit->name }}</x-slot:title>
    <x-slot:header>Fleet Management</x-slot:header>

    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">{{ $unit->name }}</h2>
            <p class="text-slate-500 text-sm">Reg: {{ $unit->registration_number }} &bull; Type: {{ ucfirst($unit->type) }}</p>
        </div>
        <a href="{{ route('admin.cargo.transport-units.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-xl transition-colors">Back</a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex items-start gap-3">
            <i class="fas fa-check-circle mt-1"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl flex items-start gap-3">
            <i class="fas fa-exclamation-circle mt-1"></i>
            <div>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div x-data="{ activeTab: 'details' }" class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden min-h-[600px]">
        <!-- Tabs Header -->
        <div class="border-b border-slate-200 bg-slate-50/50 overflow-x-auto">
            <nav class="-mb-px flex space-x-8 px-8" aria-label="Tabs">
                <button @click="activeTab = 'details'"
                        :class="activeTab === 'details' ? 'border-brand-500 text-brand-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    <i class="fas fa-info-circle mr-2"></i> Details
                </button>
                <button @click="activeTab = 'capacity'"
                        :class="activeTab === 'capacity' ? 'border-brand-500 text-brand-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    <i class="fas fa-box-open mr-2"></i> Capacity
                </button>
                <button @click="activeTab = 'schedules'"
                        :class="activeTab === 'schedules' ? 'border-brand-500 text-brand-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    <i class="fas fa-calendar-alt mr-2"></i> Schedules
                </button>
                
                <button @click="activeTab = 'prices'"
                        :class="activeTab === 'prices' ? 'border-brand-500 text-brand-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    <i class="fas fa-tags mr-2"></i> Cargo Prices
                </button>
            </nav>
        </div>

        <!-- Tab Contents -->
        <div class="p-8">
            <!-- Details Tab -->
            <div x-show="activeTab === 'details'" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100">
                        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-200">
                            <i class="fas fa-ship text-brand-500 text-xl"></i>
                            <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Specifications</h3>
                        </div>
                        <div class="space-y-4">
                            <div class="grid grid-cols-2">
                                <span class="text-slate-500 text-sm font-medium">Name</span>
                                <span class="text-slate-800 font-semibold">{{ $unit->name }}</span>
                            </div>
                            <div class="grid grid-cols-2">
                                <span class="text-slate-500 text-sm font-medium">Type</span>
                                <span class="text-slate-800 font-semibold">{{ ucfirst($unit->type) }}</span>
                            </div>
                            <div class="grid grid-cols-2">
                                <span class="text-slate-500 text-sm font-medium">Mode</span>
                                <span class="text-slate-800 font-semibold">{{ ucfirst($unit->transport_type) }}</span>
                            </div>
                            <div class="grid grid-cols-2">
                                <span class="text-slate-500 text-sm font-medium">Status</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $unit->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ ucfirst($unit->status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Capacity Tab -->
            <div x-show="activeTab === 'capacity'" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @php 
                        $cap = $unit->cargoCapacities()->first(); 
                        $hasDeck = $unit->has_cargo_deck && $cap;
                    @endphp
                    
                    @if($hasDeck)
                        <div class="p-6 bg-slate-50 rounded-2xl border border-slate-200 shadow-sm text-center">
                            <i class="fas fa-road text-3xl text-brand-500 mb-3"></i>
                            <h4 class="text-slate-500 font-medium mb-1">Lane Meters</h4>
                            <p class="text-2xl font-bold text-slate-800">{{ number_format($cap->total_lane_meters, 1) }} m</p>
                        </div>
                        <div class="p-6 bg-slate-50 rounded-2xl border border-slate-200 shadow-sm text-center">
                            <i class="fas fa-weight-hanging text-3xl text-amber-500 mb-3"></i>
                            <h4 class="text-slate-500 font-medium mb-1">Weight Capacity</h4>
                            <p class="text-2xl font-bold text-slate-800">{{ number_format($cap->total_weight_capacity_kg, 1) }} KG</p>
                        </div>
                        <div class="p-6 bg-slate-50 rounded-2xl border border-slate-200 shadow-sm text-center">
                            <i class="fas fa-cubes text-3xl text-emerald-500 mb-3"></i>
                            <h4 class="text-slate-500 font-medium mb-1">Volume Capacity</h4>
                            <p class="text-2xl font-bold text-slate-800">{{ number_format($cap->total_volume_capacity_cbm, 1) }} CBM</p>
                        </div>
                    @else
                        <div class="col-span-3 p-8 text-center text-slate-500 border-2 border-dashed border-slate-200 rounded-2xl">
                            <i class="fas fa-times-circle text-4xl mb-3 text-slate-300"></i>
                            <p>This transport unit is not configured with a dedicated cargo deck.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Schedules Tab (Livewire) -->
            <div x-show="activeTab === 'schedules'" x-cloak>
                <livewire:admin.transport-unit-schedule-manager :transportUnit="$unit" />
            </div>

            <!-- Cargo Prices Tab -->
            <div x-show="activeTab === 'prices'" x-cloak>
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-slate-800 text-lg">Unit Cargo Prices</h3>
                        <p class="text-sm text-slate-500">View fares currently configured explicitly for this transport unit.</p>
                    </div>
                    <a href="{{ route('admin.cargo.fares') }}" class="px-4 py-2 bg-brand-50 text-brand-700 hover:bg-brand-100 rounded-xl font-medium border border-brand-200 transition-colors">
                        <i class="fas fa-external-link-alt mr-2"></i> Manage Fares
                    </a>
                </div>

                @php 
                    $fares = \App\Models\CargoFare::where('transport_unit_id', $unit->id)->with(['cargoType', 'route'])->get(); 
                @endphp

                <div class="overflow-x-auto bg-white border border-slate-200 rounded-2xl shadow-sm">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-500 uppercase">
                                <th class="px-6 py-4">Cargo Type</th>
                                <th class="px-6 py-4">Route</th>
                                <th class="px-6 py-4">Price (TZS)</th>
                                <th class="px-6 py-4">Price / KG (TZS)</th>
                                <th class="px-6 py-4">Min Charge</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($fares as $fare)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $fare->cargoType->name ?? 'All' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $fare->route->name ?? 'All Routes' }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-emerald-600">{{ number_format($fare->price_tzs, 2) }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-emerald-600">{{ number_format($fare->price_per_kg_tzs, 2) }}</td>
                                <td class="px-6 py-4 text-sm text-slate-500">{{ number_format($fare->min_charge_tzs, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                    No cargo fares explicitly configured for this transport unit.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-layouts.admin>
