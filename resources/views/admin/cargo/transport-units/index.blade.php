<x-layouts.admin>
    <x-slot:title>Transport Units</x-slot:title>
    <x-slot:header>Transport Units</x-slot:header>

    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Transport Units</h2>
            <p class="text-slate-500 text-sm">Manage ferries, trucks, and buses handling cargo.</p>
        </div>
        <a href="{{ route('admin.cargo.transport-units.create') }}" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl shadow-sm transition-colors flex items-center gap-2">
            <i class="fas fa-plus"></i> Add Unit
        </a>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-slate-200/60 bg-slate-50/50">
                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Registration</th>
                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Cargo Deck</th>
                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($units as $unit)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">{{ $unit->registration_number }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">{{ $unit->name }} <x-dual-control.pending-badge subject="cargo_transport_unit:{{ $unit->id }}" class="ml-1" /></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 capitalize">{{ $unit->transport_type }} ({{ $unit->type }})</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($unit->has_cargo_deck)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800"><i class="fas fa-check mr-1.5"></i> Yes</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800"><i class="fas fa-times mr-1.5"></i> No</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $unit->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                            {{ ucfirst($unit->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('admin.cargo.transport-units.show', $unit->id) }}" class="text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-lg transition-colors mr-2">View</a>
                        <a href="{{ route('admin.cargo.transport-units.edit', $unit->id) }}" class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition-colors">Edit</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-slate-200/60 bg-slate-50/30">
            {{ $units->links() }}
        </div>
    </div>
</x-layouts.admin>