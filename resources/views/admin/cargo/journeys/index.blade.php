<x-layouts.admin>
    <x-slot:title>Journey Dashboard</x-slot:title>
    <x-slot:header>Schedules & Journeys</x-slot:header>

    <div class="mb-6 flex justify-between items-end">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Live Journey Dashboard</h2>
            <p class="text-slate-500 text-sm">Monitor and manage daily journeys across the fleet.</p>
        </div>
        <form method="GET" action="{{ route('admin.cargo.journeys.index') }}" class="flex items-center gap-2">
            <input type="date" name="date" value="{{ $date }}" class="w-full rounded-xl border-slate-200 bg-slate-50 hover:bg-white focus:bg-white px-4 py-3.5 text-sm font-medium text-slate-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 transition-colors">
            <button type="submit" class="px-4 py-2 bg-brand-50 hover:bg-brand-100 text-brand-600 font-bold rounded-xl border border-brand-200 transition-colors">
                View Date
            </button>
        </form>
    </div>

    @php
        $canDepart = auth()->user()->hasPermission('cargo-load');
        $canArrive = auth()->user()->hasPermission('cargo-offload');
        $canStatus = auth()->user()->hasPermission('voyage:update');
    @endphp

    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
        @if($journeys->isEmpty())
            <div class="p-12 text-center flex flex-col items-center">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 border border-slate-100 shadow-sm">
                    <i class="fas fa-ship text-2xl text-slate-400"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800">No Journeys Scheduled</h3>
                <p class="text-slate-500 text-sm mt-1 max-w-sm mx-auto">There are no journeys scheduled for {{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200/60">
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Departure</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Route</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Transport Unit</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Cargo</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($journeys as $journey)
                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                <td class="py-4 px-6">
                                    <div class="font-bold text-slate-800 text-lg">{{ \Carbon\Carbon::parse($journey->departure_time)->format('H:i') }}</div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="font-bold text-brand-600">{{ $journey->route->origin->name }} &rarr; {{ $journey->route->destination->name }}</div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="font-semibold text-slate-700 flex items-center gap-2">
                                        <i class="fas fa-truck text-slate-400 text-xs"></i> {{ $journey->transportUnit->name }}
                                    </div>
                                </td>
                                @php
                                    $load = $cargo[$journey->id];
                                    $vessel = $journey->transportUnit->name ?? 'the vessel';
                                    $where = ($journey->route->origin->name ?? '') . ' → ' . ($journey->route->destination->name ?? '');
                                @endphp
                                <td class="py-4 px-6 text-xs text-slate-600 whitespace-nowrap">
                                    @if($journey->status === 'departed' || $journey->status === 'arrived')
                                        <div><strong class="text-slate-800">{{ $load['in_transit'] }}</strong> in transit</div>
                                    @else
                                        <div><strong class="text-slate-800">{{ $load['loaded'] }}</strong> loaded · <strong class="text-slate-800">{{ $load['not_loaded'] }}</strong> not loaded</div>
                                        @if($load['unpaid'])<div class="text-amber-700">{{ $load['unpaid'] }} unpaid hold(s)</div>@endif
                                    @endif
                                    @can('cargo-manifest')
                                        <a href="{{ route('admin.cargo.manifest', ['date' => $date, 'service' => $journey->id]) }}" class="text-brand-600 hover:underline">Loading manifest</a>
                                    @endcan
                                </td>
                                <td class="py-4 px-6" x-data="{ confirm: null }">
                                    <x-dual-control.pending-badge subject="transport_service:{{ $journey->id }}" class="mb-1" />
                                    <span class="inline-block text-xs font-bold uppercase tracking-wider rounded-lg px-3 py-1.5 {{ match($journey->status) {
                                        'boarding' => 'bg-blue-50 text-blue-700',
                                        'departed' => 'bg-indigo-50 text-indigo-700',
                                        'arrived' => 'bg-emerald-50 text-emerald-700',
                                        'cancelled' => 'bg-rose-50 text-rose-700',
                                        default => 'bg-slate-50 text-slate-600'
                                    } }}">{{ $journey->status }}</span>
                                    @if($journey->actual_departure_at)<div class="text-[11px] text-slate-500 mt-1">Left {{ $journey->actual_departure_at->format('d M H:i') }}</div>@endif
                                    @if($journey->actual_arrival_at)<div class="text-[11px] text-slate-500">Arrived {{ $journey->actual_arrival_at->format('d M H:i') }}</div>@endif

                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        @if(in_array($journey->status, ['scheduled', 'boarding']))
                                            @if($canDepart)
                                                <button type="button" @click="confirm = 'depart'" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700"><i class="fas fa-ship mr-1"></i>Depart</button>
                                            @endif
                                            @if($canStatus)
                                                <form method="POST" action="{{ route('admin.cargo.journeys.update-status', $journey) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <select name="status" onchange="this.form.submit()" aria-label="Change status" class="text-xs rounded-lg border-slate-300 py-1.5 pl-2 pr-7">
                                                        <option value="" selected disabled>Other…</option>
                                                        @if($journey->status !== 'scheduled')<option value="scheduled">Scheduled</option>@endif
                                                        @if($journey->status !== 'boarding')<option value="boarding">Boarding</option>@endif
                                                        <option value="cancelled">Cancelled</option>
                                                    </select>
                                                </form>
                                            @endif
                                        @elseif($journey->status === 'departed' && $canArrive)
                                            <button type="button" @click="confirm = 'arrive'" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700"><i class="fas fa-anchor mr-1"></i>Arrive</button>
                                        @endif
                                    </div>

                                    {{-- Depart / Arrive confirmation --}}
                                    <div x-show="confirm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" @keydown.escape.window="confirm = null">
                                        <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl" @click.outside="confirm = null">
                                            <div class="p-5 border-b border-slate-100">
                                                <h3 class="font-bold text-slate-900" x-text="confirm === 'depart' ? 'Depart now?' : 'Arrived now?'"></h3>
                                                <p class="text-sm text-slate-500">{{ substr((string) $journey->departure_time, 0, 5) }} · {{ $where }} · {{ $vessel }}</p>
                                            </div>
                                            <div class="p-5 space-y-2 text-sm text-slate-700">
                                                <template x-if="confirm === 'depart'">
                                                    <ul class="space-y-2">
                                                        <li><i class="fas fa-circle-check text-emerald-600 mr-1"></i><strong>{{ $load['loaded'] }}</strong> loaded booking(s) go <strong>in transit</strong>; their senders and receivers get an SMS.</li>
                                                        <li class="{{ $load['not_loaded'] ? 'text-rose-700' : 'text-slate-500' }}"><i class="fas fa-triangle-exclamation mr-1"></i><strong>{{ $load['not_loaded'] }}</strong> paid booking(s) not loaded will be <strong>left behind</strong>.</li>
                                                        @if($load['unpaid'])<li class="text-slate-500"><i class="fas fa-hourglass-half mr-1"></i>{{ $load['unpaid'] }} unpaid hold(s) stay unpaid.</li>@endif
                                                    </ul>
                                                </template>
                                                <template x-if="confirm === 'arrive'">
                                                    <p><i class="fas fa-circle-check text-emerald-600 mr-1"></i><strong>{{ $load['in_transit'] }}</strong> booking(s) become <strong>ready for collection</strong>; their senders and receivers get an SMS.</p>
                                                </template>
                                                <x-dual-control.takes-effect op="cargo_journey_status_update" />
                                            </div>
                                            <div class="p-5 pt-0 flex justify-end gap-2">
                                                <button type="button" @click="confirm = null" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-sm font-semibold">Cancel</button>
                                                <form method="POST" :action="confirm === 'depart' ? @js(route('admin.cargo.journeys.depart', $journey)) : @js(route('admin.cargo.journeys.arrive', $journey))">
                                                    @csrf
                                                    <button class="px-4 py-2 rounded-xl text-white text-sm font-bold" :class="confirm === 'depart' ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-emerald-600 hover:bg-emerald-700'" x-text="confirm === 'depart' ? 'Depart now' : 'Mark arrived'"></button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <!-- Weather Notes Trigger -->
                                    <button onclick="document.getElementById('weather-modal-{{ $journey->id }}').classList.remove('hidden')" class="text-slate-400 hover:text-brand-600 transition-colors bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-sm flex items-center gap-2 text-xs font-semibold">
                                        <i class="fas fa-cloud-sun"></i> Weather
                                        @if($journey->weather_notes)
                                            <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                                        @endif
                                    </button>

                                    <!-- Weather Modal -->
                                    <div id="weather-modal-{{ $journey->id }}" class="hidden fixed inset-0 bg-slate-900/50 z-50 flex items-center justify-center backdrop-blur-sm">
                                        <div class="bg-white rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl">
                                            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                                                <h3 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                                                    <i class="fas fa-cloud-sun text-amber-500"></i> Weather Notes
                                                </h3>
                                                <button onclick="document.getElementById('weather-modal-{{ $journey->id }}').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <form method="POST" action="{{ route('admin.cargo.journeys.update-weather', $journey) }}" class="p-6">
                                                @csrf
                                                @method('PATCH')
                                                <div class="mb-4">
                                                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 block">Notes & Conditions</label>
                                                    <textarea name="weather_notes" rows="4" class="w-full rounded-xl border-slate-300 focus:border-brand-500 focus:ring-brand-500" placeholder="e.g. High winds expected, proceeding with caution...">{{ $journey->weather_notes }}</textarea>
                                                </div>
                                                <div class="flex justify-end gap-3">
                                                    <button type="button" onclick="document.getElementById('weather-modal-{{ $journey->id }}').classList.add('hidden')" class="px-4 py-2 font-semibold text-slate-500 bg-slate-50 hover:bg-slate-100 rounded-xl transition-colors">Cancel</button>
                                                    <button type="submit" class="px-4 py-2 font-semibold text-white bg-brand-500 hover:bg-brand-600 rounded-xl shadow-sm transition-colors">Save Notes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.admin>
