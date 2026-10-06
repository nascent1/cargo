<x-layouts.admin>
    <x-slot:title>Dual Control Inbox</x-slot:title>
    <x-slot:header>Pending Authorizations</x-slot:header>

    <div class="mb-6 flex items-center justify-between">
        <form method="GET" class="flex gap-3 items-center w-full">
            <div class="relative flex-1 max-w-md">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by operation or entity..."
                    class="w-full pl-11 pr-4 py-2.5 rounded-xl border-slate-300 bg-white shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
            </div>
            <select name="status" class="rounded-xl border-slate-300 bg-white px-4 py-2.5 text-sm shadow-sm" onchange="this.form.submit()">
                <option value="pending" {{ request('status', 'pending') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                <option value="">All Statuses</option>
            </select>
            <noscript><button type="submit" class="rounded-xl bg-brand-600 text-white px-4 py-2.5 text-sm font-medium hover:bg-brand-700 transition">Filter</button></noscript>
        </form>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                        <th class="p-4 font-bold">Operation</th>
                        <th class="p-4 font-bold">Entity</th>
                        <th class="p-4 font-bold">Maker</th>
                        <th class="p-4 font-bold">Requested At</th>
                        <th class="p-4 font-bold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($requests as $req)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-4">
                                <div class="font-semibold text-slate-800">{{ Str::headline($req->operation_type) }}</div>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-1 bg-slate-100 text-slate-600 rounded-md text-xs font-bold">{{ $req->entity_type }} #{{ $req->entity_id }}</span>
                            </td>
                            <td class="p-4 text-sm text-slate-600">
                                {{ $req->maker->full_name ?? $req->maker->email }}
                            </td>
                            <td class="p-4 text-sm text-slate-500">
                                {{ $req->created_at->diffForHumans() }}
                            </td>
                            <td class="p-4 text-right">
                                @php $canReadDC = auth()->user()->hasPermission('dc:read'); @endphp
                                @if($canReadDC)
                                <a href="{{ route('admin.dual-control.show', $req) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors" title="View Request">
                                    <i class="fas fa-eye text-sm"></i>
                                </a>
                                @else
                                <button disabled class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 text-slate-400 cursor-not-allowed" title="View Request (No Permission)">
                                    <i class="fas fa-eye text-sm"></i>
                                </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-500">
                                <i class="fas fa-inbox text-4xl mb-3 text-slate-300"></i>
                                <p>No pending authorizations.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requests->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
