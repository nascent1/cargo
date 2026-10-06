<x-layouts.admin>
    <x-slot:title>Audit Logs</x-slot:title>
    <x-slot:header>Audit Logs</x-slot:header>

    <div class="mb-6 flex items-center justify-between flex-wrap gap-3">
        <form method="GET" class="flex gap-3 items-center flex-wrap">
            <select name="action" class="rounded-xl border-surface-200 bg-white px-4 py-2.5 text-sm shadow-sm">
                <option value="">All Actions</option>
                @foreach($actions as $a)<option value="{{ $a }}" {{ request('action') == $a ? 'selected' : '' }}>{{ ucfirst($a) }}</option>@endforeach
            </select>
            <select name="entity_type" class="rounded-xl border-surface-200 bg-white px-4 py-2.5 text-sm shadow-sm">
                <option value="">All Entities</option>
                @foreach($entityTypes as $e)<option value="{{ $e }}" {{ request('entity_type') == $e ? 'selected' : '' }}>{{ $e }}</option>@endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}" class="rounded-xl border-surface-200 bg-white px-4 py-2.5 text-sm shadow-sm">
            <input type="date" name="to" value="{{ request('to') }}" class="rounded-xl border-surface-200 bg-white px-4 py-2.5 text-sm shadow-sm">
            <button type="submit" class="rounded-xl bg-brand-600 text-white px-4 py-2.5 text-sm font-medium hover:bg-brand-700">Filter</button>
        </form>
        @php $canVerifyAudit = auth()->user()->hasPermission('audit:verify'); @endphp
        @if($canVerifyAudit)
        <a href="{{ route('admin.audit.verify') }}" class="rounded-xl bg-emerald-600 text-white px-4 py-2.5 text-sm font-medium hover:bg-emerald-700">
            <i class="fas fa-shield-alt mr-1"></i> Verify Chain
        </a>
        @else
        <button disabled class="rounded-xl bg-emerald-600/50 text-white/70 px-4 py-2.5 text-sm font-medium cursor-not-allowed" title="Verify Chain (No Permission)">
            <i class="fas fa-shield-alt mr-1"></i> Verify Chain
        </button>
        @endif
    </div>

    <div class="glass-card rounded-2xl shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-surface-200">
            <thead class="bg-surface-50/80">
                <tr>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-surface-600 uppercase">#</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-surface-600 uppercase">Action</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-surface-600 uppercase">Entity</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-surface-600 uppercase">User</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-surface-600 uppercase">IP</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-surface-600 uppercase">Timestamp</th>
                    <th class="relative px-6 py-3.5"><span class="sr-only">View</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @forelse ($logs as $log)
                <tr class="hover:bg-surface-50/60 transition-colors">
                    <td class="px-6 py-4 text-xs text-surface-400 font-mono">{{ $log->sequence_number }}</td>
                    <td class="px-6 py-4"><span class="px-2.5 py-1 rounded-full text-xs font-medium {{ match($log->action) { 'create' => 'bg-emerald-100 text-emerald-700', 'update' => 'bg-blue-100 text-blue-700', 'delete' => 'bg-red-100 text-red-700', 'login' => 'bg-amber-100 text-amber-700', default => 'bg-surface-100 text-surface-600' } }}">{{ ucfirst($log->action) }}</span></td>
                    <td class="px-6 py-4 text-sm text-surface-700">{{ $log->entity_type }} <span class="text-surface-400">#{{ $log->entity_id }}</span></td>
                    <td class="px-6 py-4 text-sm text-surface-700">{{ $log->user?->full_name ?? 'System' }}</td>
                    <td class="px-6 py-4 text-xs text-surface-500 font-mono">{{ $log->ip_address }}</td>
                    <td class="px-6 py-4 text-sm text-surface-500">{{ $log->created_at?->format('M d, Y H:i') }}</td>
                    <td class="px-6 py-4 text-right">
                        @php $canReadAudit = auth()->user()->hasPermission('audit:read'); @endphp
                        @if($canReadAudit)
                        <a href="{{ route('admin.audit.show', $log) }}" class="text-brand-600 hover:text-brand-700 text-sm font-medium">Detail</a>
                        @else
                        <span class="text-slate-300 text-sm font-medium cursor-not-allowed" title="Detail (No Permission)">Detail</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-12 text-center text-surface-400">No audit logs found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $logs->links() }}</div>
</x-layouts.admin>
