<x-layouts.admin>
    <x-slot:title>Departure Capacity</x-slot:title>
    <x-slot:header>Schedules & Journeys</x-slot:header>

    <div class="mb-6 flex flex-col sm:flex-row sm:justify-between sm:items-end gap-3">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Departure Capacity</h2>
            <p class="text-slate-500 text-sm">Cargo space on each departure: vehicle deck, weight and volume. Change one departure without touching the vessel's standard capacity.</p>
        </div>
        <form method="GET" action="{{ route('admin.cargo.journeys.availability-index') }}" class="flex items-center gap-2">
            <label for="cap-date" class="sr-only">Date</label>
            <input id="cap-date" type="date" name="date" value="{{ $date }}" class="rounded-xl border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-900 shadow-sm focus:border-brand-500 focus:ring-brand-500">
            <button type="submit" class="px-4 py-2.5 bg-brand-50 hover:bg-brand-100 text-brand-700 font-bold rounded-xl border border-brand-200 transition-colors">View date</button>
        </form>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
        @if ($journeys->isEmpty())
            <div class="p-12 text-center">
                <i class="fas fa-ship text-3xl text-slate-300 mb-3 block"></i>
                <h3 class="text-lg font-bold text-slate-800">No departures</h3>
                <p class="text-slate-500 text-sm mt-1">Nothing departs on {{ \Carbon\Carbon::parse($date)->format('l, j F Y') }}.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200/60 text-xs font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-4 px-6">Departure</th>
                            <th class="py-4 px-6">Route</th>
                            <th class="py-4 px-6">Vessel</th>
                            <th class="py-4 px-6">Space</th>
                            <th class="py-4 px-6 text-right"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($journeys as $journey)
                            @php $space = $journey->space; @endphp
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-4 px-6 font-bold text-slate-800 text-lg">{{ \Carbon\Carbon::parse($journey->departure_time)->format('H:i') }}
                                    <div class="text-xs font-semibold text-slate-500">{{ \Illuminate\Support\Str::headline($journey->status) }}</div></td>
                                <td class="py-4 px-6 font-semibold text-brand-700">{{ $journey->route?->name }}</td>
                                <td class="py-4 px-6 text-slate-700">{{ $journey->transportUnit?->name }}</td>
                                <td class="py-4 px-6 w-64">
                                    @if ($space)
                                        <div class="h-1.5 rounded-full bg-slate-200 overflow-hidden"><div class="h-full rounded-full {{ $space['pct'] >= 85 ? 'bg-red-600' : 'bg-brand-500' }}" style="width: {{ max($space['pct'], 2) }}%"></div></div>
                                        <div class="text-xs mt-1 {{ $space['pct'] >= 85 ? 'text-red-700 font-semibold' : 'text-slate-600' }}">{{ $space['full'] ? 'Full' : $space['pct'] . '% full' }}</div>
                                    @else
                                        <span class="text-xs font-semibold text-amber-700">No cargo capacity set</span>
                                    @endif
                                    <x-dual-control.pending-badge subject="transport_service:{{ $journey->id }}:capacity" class="mt-1" />
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <a href="{{ route('admin.cargo.journeys.availability', $journey->id) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-xl font-bold text-sm border border-emerald-200">
                                        <i class="fas fa-sliders"></i> Capacity
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.admin>
