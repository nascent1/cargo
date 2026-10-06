<x-layouts.admin>
    <x-slot:title>Dual Control</x-slot:title>
    <x-slot:header>Dual Control Queue</x-slot:header>

    <div class="glass-card rounded-2xl p-4 mb-6 shadow-sm">
        <form method="GET" action="{{ route('dual-control.index') }}" class="flex flex-wrap items-end gap-4">
            <div class="w-48">
                <label class="block text-xs font-medium text-surface-500 mb-1">Status</label>
                <select name="status" class="w-full rounded-xl border-0 py-2.5 px-3 text-sm ring-1 ring-inset ring-surface-200 focus:ring-2 focus:ring-brand-500 bg-white">
                    <option value="">All</option>
                    @foreach(['pending','approved','rejected','expired','cancelled'] as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-xl bg-surface-800 text-white px-5 py-2.5 text-sm font-medium hover:bg-surface-700 transition"><i class="fas fa-search mr-1.5 text-xs"></i> Filter</button>
            @if(request('status'))
                <a href="{{ route('dual-control.index') }}" class="rounded-xl bg-surface-100 text-surface-600 px-4 py-2.5 text-sm font-medium hover:bg-surface-200 transition">Clear</a>
            @endif
        </form>
    </div>

    <div class="glass-card rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-surface-200">
                <thead class="bg-surface-50/80">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-surface-500 uppercase tracking-wider">Operation</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-surface-500 uppercase tracking-wider">Resource</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-surface-500 uppercase tracking-wider">Maker</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-surface-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-surface-500 uppercase tracking-wider">Submitted</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-surface-500 uppercase tracking-wider">Expires</th>
                        <th class="px-6 py-3.5 text-right text-xs font-bold text-surface-500 uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-100">
                    @forelse($requests as $dc)
                        <tr class="hover:bg-surface-50/30 transition-colors {{ $dc->isExpired() ? 'opacity-50' : '' }}">
                            <td class="px-6 py-4 text-sm font-medium text-surface-900">{{ ucfirst(str_replace('_', ' ', $dc->operation_type)) }}</td>
                            <td class="px-6 py-4 text-sm text-surface-600">{{ $dc->resource_type }} #{{ $dc->resource_id }}</td>
                            <td class="px-6 py-4 text-sm text-surface-600">{{ $dc->maker?->full_name ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ match($dc->status) { 'pending' => 'bg-amber-100 text-amber-700', 'approved' => 'bg-emerald-100 text-emerald-700', 'rejected' => 'bg-red-100 text-red-700', default => 'bg-surface-200 text-surface-600' } }}">
                                    {{ ucfirst($dc->status) }}{{ $dc->isExpired() ? ' (expired)' : '' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-surface-500">{{ $dc->created_at?->diffForHumans() }}</td>
                            <td class="px-6 py-4 text-sm text-surface-500">{{ $dc->expires_at?->format('d M H:i') ?? '—' }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('dual-control.show', $dc) }}" class="text-brand-600 hover:text-brand-800 text-sm font-medium">Review →</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-12 text-center"><i class="fas fa-check-double text-surface-200 text-3xl mb-2"></i><p class="text-sm text-surface-500">No requests found.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($requests->hasPages())<div class="mt-4">{{ $requests->links() }}</div>@endif
</x-layouts.admin>
