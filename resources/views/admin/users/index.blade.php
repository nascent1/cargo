<x-layouts.admin>
    <x-slot:title>Users</x-slot:title>
    <x-slot:header>User Management</x-slot:header>

    <div class="mb-6 flex items-center justify-between">
        <form method="GET" class="flex gap-3 items-center">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search users..."
                class="rounded-xl border-surface-200 bg-white px-4 py-2.5 text-sm shadow-sm focus:ring-brand-500 focus:border-brand-500 w-64">
            <select name="domain" class="rounded-xl border-surface-200 bg-white px-4 py-2.5 text-sm shadow-sm">
                <option value="">All Domains</option>
                <option value="system_user" {{ request('domain') == 'system_user' ? 'selected' : '' }}>System Users</option>
                <option value="beekeeper" {{ request('domain') == 'beekeeper' ? 'selected' : '' }}>Beekeepers</option>
                <option value="consumer" {{ request('domain') == 'consumer' ? 'selected' : '' }}>Consumers</option>
            </select>
            <select name="status" class="rounded-xl border-surface-200 bg-white px-4 py-2.5 text-sm shadow-sm" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                <option value="locked" {{ request('status') == 'locked' ? 'selected' : '' }}>Locked</option>
            </select>
            <button type="submit" class="rounded-xl bg-brand-600 text-white px-4 py-2.5 text-sm font-medium hover:bg-brand-700 transition">Filter</button>
        </form>
        @php $canCreate = auth()->user()->hasPermission('user:create'); @endphp
        @if($canCreate)
            <a href="{{ route('admin.users.create') }}" class="rounded-xl bg-surface-900 text-white px-4 py-2.5 text-sm font-medium hover:bg-surface-800 transition">
                <i class="fas fa-plus mr-1"></i> Add User
            </a>
        @else
            <button disabled class="rounded-xl bg-surface-100 text-surface-400 px-4 py-2.5 text-sm font-medium cursor-not-allowed border border-surface-200" title="Add User (No Permission)">
                <i class="fas fa-plus mr-1"></i> Add User
            </button>
        @endif
    </div>

    <div class="glass-card rounded-2xl shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-surface-200">
            <thead class="bg-surface-50/80">
                <tr>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-surface-600 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-surface-600 uppercase tracking-wider">Domain</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-surface-600 uppercase tracking-wider">Roles</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-surface-600 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-surface-600 uppercase tracking-wider">Created</th>
                    <th class="relative px-6 py-3.5"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @forelse ($users as $user)
                <tr class="hover:bg-surface-50/60 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-full bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center">
                                <span class="text-xs font-bold text-white">{{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name, 0, 1) }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-surface-900">{{ $user->full_name }}</p>
                                <p class="text-xs text-surface-500">{{ $user->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4"><span class="px-2.5 py-1 rounded-full text-xs font-medium bg-surface-100 text-surface-700">{{ ucfirst(str_replace('_', ' ', $user->domain)) }}</span></td>
                    <td class="px-6 py-4"><div class="flex gap-1">@foreach($user->roles as $role)<span class="px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-700">{{ $role->display_name }}</span>@endforeach</div></td>
                    <td class="px-6 py-4">
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ match($user->status) { 'active' => 'bg-emerald-100 text-emerald-700', 'locked' => 'bg-red-100 text-red-700', 'suspended' => 'bg-amber-100 text-amber-700', default => 'bg-surface-100 text-surface-600' } }}">{{ ucfirst($user->status) }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-surface-500">{{ $user->created_at?->format('M d, Y') }}</td>
                    <td class="px-6 py-4 text-right">
                        @php $canRead = auth()->user()->hasPermission('user:read'); @endphp
                        @if($canRead)
                            <a href="{{ route('admin.users.show', $user) }}" class="text-brand-600 hover:text-brand-700 text-sm font-medium">View</a>
                        @else
                            <span class="text-surface-300 text-sm font-medium cursor-not-allowed" title="View (No Permission)">View</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-12 text-center text-surface-400">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $users->links() }}</div>
</x-layouts.admin>
