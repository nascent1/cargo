<x-layouts.admin>
    <x-slot:title>Seat Availability</x-slot:title>
    <x-slot:header>Schedules & Voyages</x-slot:header>

    <div class="mb-6 flex justify-between items-end">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Seat Availability Management</h2>
            <p class="text-slate-500 text-sm">Manage passenger capacities and extra seat allocations.</p>
        </div>
        <form method="GET" action="{{ route('admin.availability.index') }}" class="flex items-center gap-2">
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
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
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
                                <td class="py-4 px-6 text-right">
                                    <!-- Manage Availability Link -->
                                    <a href="{{ route('admin.voyages.availability', $voyage->id) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-xl font-bold text-sm border border-emerald-200 transition-colors shadow-sm">
                                        <i class="fas fa-users-cog"></i> Manage Availability
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
