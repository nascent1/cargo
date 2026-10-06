<x-layouts.admin>
    <x-slot:title>Schedules Management</x-slot:title>
    <x-slot:header>Vessel Schedules</x-slot:header>

    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Schedules Directory</h2>
            <p class="text-slate-500 text-sm">Assign ferries to specific trips for a time period.</p>
        </div>
        <a href="{{ route('admin.cargo.schedules.create') }}" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl shadow-sm transition-colors flex items-center gap-2">
            <i class="fas fa-plus"></i> Add Schedule
        </a>
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
                        <th class="px-6 py-4 font-semibold text-slate-700">Route & Trip</th>
                        <th class="px-6 py-4 font-semibold text-slate-700">Assigned Vessel</th>
                        <th class="px-6 py-4 font-semibold text-slate-700 text-center">Period</th>
                        <th class="px-6 py-4 font-semibold text-slate-700 text-center">Status</th>
                        <th class="px-6 py-4 font-semibold text-slate-700 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($schedules as $schedule)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-800">{{ $schedule->trip->route->name ?? 'Unknown Route' }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ \Carbon\Carbon::parse($schedule->trip->departure_time)->format('H:i') }} - 
                                    {{ \Carbon\Carbon::parse($schedule->trip->arrival_time)->format('H:i') }}
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-ship text-blue-500"></i>
                                    <span class="font-semibold text-slate-700">{{ $schedule->transportUnit->name ?? 'Unknown Transport Unit' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="text-xs font-medium text-slate-600 bg-slate-100 px-2 py-1 rounded inline-block">
                                    {{ \Carbon\Carbon::parse($schedule->start_date)->format('M d, Y') }} 
                                    @if($schedule->end_date)
                                        to {{ \Carbon\Carbon::parse($schedule->end_date)->format('M d, Y') }}
                                    @else
                                        (Ongoing)
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider
                                    {{ match($schedule->status) {
                                        'active' => 'bg-emerald-100 text-emerald-700',
                                        'suspended' => 'bg-amber-100 text-amber-700',
                                        default => 'bg-slate-100 text-slate-600'
                                    } }}">
                                    {{ $schedule->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="#" class="text-slate-400 hover:text-blue-600 transition-colors p-2"><i class="fas fa-pen"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                <p class="font-medium">No schedules registered yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($schedules->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">
                {{ $schedules->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
