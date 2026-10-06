<x-layouts.admin>
    <x-slot:title>Voyage Dashboard</x-slot:title>
    <x-slot:header>Schedules & Voyages</x-slot:header>

    <div class="mb-6 flex justify-between items-end">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Live Voyage Dashboard</h2>
            <p class="text-slate-500 text-sm">Monitor and manage daily voyages across the fleet.</p>
        </div>
        <form method="GET" action="{{ route('admin.voyages.index') }}" class="flex items-center gap-2">
            <input type="date" name="date" value="{{ $date }}" class="px-4 py-2 rounded-xl border border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm font-semibold text-slate-700">
            <button type="submit" class="px-4 py-2 bg-brand-50 hover:bg-brand-100 text-brand-600 font-bold rounded-xl border border-brand-200 transition-colors">
                View Date
            </button>
        </form>
    </div>

    @if (session()->has('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex items-start gap-3 animate-fade-in-up">
            <i class="fas fa-check-circle mt-1"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
        @if($voyages->isEmpty())
            <div class="p-12 text-center flex flex-col items-center">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 border border-slate-100 shadow-sm">
                    <i class="fas fa-ship text-2xl text-slate-400"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800">No Voyages Scheduled</h3>
                <p class="text-slate-500 text-sm mt-1 max-w-sm mx-auto">There are no voyages scheduled for {{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200/60">
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Departure</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Route</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Ferry</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($voyages as $voyage)
                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                <td class="py-4 px-6">
                                    <div class="font-bold text-slate-800 text-lg">{{ \Carbon\Carbon::parse($voyage->departure_time)->format('H:i') }}</div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="font-bold text-brand-600">{{ $voyage->route->origin->name }} &rarr; {{ $voyage->route->destination->name }}</div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="font-semibold text-slate-700 flex items-center gap-2">
                                        <i class="fas fa-ship text-slate-400 text-xs"></i> {{ $voyage->ferry->name }}
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <form method="POST" action="{{ route('admin.voyages.update-status', $voyage) }}">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" onchange="this.form.submit()" class="text-xs font-bold uppercase tracking-wider rounded-lg border-slate-300 py-1.5 pl-3 pr-8 focus:ring-brand-500 focus:border-brand-500 {{ match($voyage->status) {
                                            'scheduled' => 'bg-slate-50 text-slate-600',
                                            'boarding' => 'bg-blue-50 text-blue-700',
                                            'departed' => 'bg-indigo-50 text-indigo-700',
                                            'arrived' => 'bg-emerald-50 text-emerald-700',
                                            'cancelled' => 'bg-rose-50 text-rose-700',
                                            default => 'bg-slate-50 text-slate-600'
                                        } }}">
                                            <option value="scheduled" {{ $voyage->status === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                            <option value="boarding" {{ $voyage->status === 'boarding' ? 'selected' : '' }}>Boarding</option>
                                            <option value="departed" {{ $voyage->status === 'departed' ? 'selected' : '' }}>Departed</option>
                                            <option value="arrived" {{ $voyage->status === 'arrived' ? 'selected' : '' }}>Arrived</option>
                                            <option value="cancelled" {{ $voyage->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-2">
                                        <!-- Weather Notes Trigger -->
                                        <button onclick="document.getElementById('weather-modal-{{ $voyage->id }}').classList.remove('hidden')" class="text-slate-400 hover:text-brand-600 transition-colors bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-sm flex items-center gap-2 text-xs font-semibold">
                                            <i class="fas fa-cloud-sun"></i> Weather
                                            @if($voyage->weather_notes)
                                                <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                                            @endif
                                        </button>

                                        @if(auth()->user()->hasPermission('availability:manage'))
                                        <a href="{{ route('admin.voyages.availability', $voyage->id) }}" class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-xl font-bold text-sm border border-emerald-200 transition-colors shadow-sm">
                                            <i class="fas fa-users-cog"></i> Manage Availability
                                        </a>
                                        @endif
                                    </div>

                                    <!-- Weather Modal -->
                                    <div id="weather-modal-{{ $voyage->id }}" class="hidden fixed inset-0 bg-slate-900/50 z-50 flex items-center justify-center backdrop-blur-sm">
                                        <div class="bg-white rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl">
                                            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                                                <h3 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                                                    <i class="fas fa-cloud-sun text-amber-500"></i> Weather Notes
                                                </h3>
                                                <button onclick="document.getElementById('weather-modal-{{ $voyage->id }}').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <form method="POST" action="{{ route('admin.voyages.update-weather', $voyage) }}" class="p-6">
                                                @csrf
                                                @method('PATCH')
                                                <div class="mb-4">
                                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Notes & Conditions</label>
                                                    <textarea name="weather_notes" rows="4" class="w-full rounded-xl border-slate-300 focus:border-brand-500 focus:ring-brand-500" placeholder="e.g. High winds expected, proceeding with caution...">{{ $voyage->weather_notes }}</textarea>
                                                </div>
                                                <div class="flex justify-end gap-3">
                                                    <button type="button" onclick="document.getElementById('weather-modal-{{ $voyage->id }}').classList.add('hidden')" class="px-4 py-2 font-semibold text-slate-500 bg-slate-50 hover:bg-slate-100 rounded-xl transition-colors">Cancel</button>
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
