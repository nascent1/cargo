<x-layouts.admin>
    <x-slot:title>Permissions</x-slot:title>
    <x-slot:header>Settings — Permissions</x-slot:header>

    @include('admin.settings._tabs')

    <div class="space-y-6">
        @foreach($permissions as $module => $perms)
            <div class="glass-card rounded-2xl shadow-sm overflow-hidden border border-surface-200/50 hover:shadow-md transition-shadow">
                <div class="px-6 py-4 bg-gradient-to-r from-surface-50 to-white border-b border-surface-200/60 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-surface-900 flex items-center gap-2 uppercase tracking-wide">
                        <div class="w-8 h-8 rounded-full bg-brand-100 flex items-center justify-center">
                            <span class="w-3 h-3 rounded-full bg-brand-500 shadow-sm shadow-brand-500/50"></span>
                        </div>
                        {{ ucfirst($module) }}
                    </h3>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-surface-900 text-white shadow-sm">{{ $perms->count() }}</span>
                </div>
                <div class="p-5 bg-white/50">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($perms as $perm)
                            <div class="p-4 rounded-xl border border-surface-200 bg-white shadow-sm hover:border-brand-300 hover:shadow-md transition-all group">
                                <div class="mb-3">
                                    <h4 class="text-sm font-bold text-surface-800 group-hover:text-brand-600 transition-colors">{{ $perm->display_name }}</h4>
                                    <p class="text-[10px] text-surface-400 font-mono mt-1 bg-surface-50 inline-block px-2 py-0.5 rounded">{{ $perm->name }}</p>
                                </div>
                                <div class="pt-3 border-t border-surface-100 flex flex-wrap gap-1.5">
                                    @php
                                        $assignedRoles = $roles->filter(fn($r) => $r->permissions->contains('id', $perm->id));
                                    @endphp
                                    @forelse($assignedRoles as $role)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold shadow-sm
                                            {{ match($role->slug) {
                                                'super_admin' => 'bg-gradient-to-r from-amber-100 to-amber-50 text-amber-700 border border-amber-200',
                                                'admin' => 'bg-gradient-to-r from-violet-100 to-violet-50 text-violet-700 border border-violet-200',
                                                default => 'bg-gradient-to-r from-blue-50 to-white text-blue-600 border border-blue-200',
                                            } }}">
                                            {{ $role->display_name }}
                                        </span>
                                    @empty
                                        <span class="text-[10px] text-surface-400 italic bg-surface-50 px-2 py-0.5 rounded border border-surface-200">Unassigned</span>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Summary --}}
    <div class="mt-6 glass-card rounded-2xl p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-surface-900 mb-3"><i class="fas fa-chart-bar text-brand-500 mr-2"></i> Permission Summary</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-xl bg-surface-50 p-4 text-center">
                <p class="text-2xl font-bold text-surface-900">{{ $permissions->flatten()->count() }}</p>
                <p class="text-xs text-surface-500 mt-1">Total Permissions</p>
            </div>
            <div class="rounded-xl bg-surface-50 p-4 text-center">
                <p class="text-2xl font-bold text-surface-900">{{ $permissions->count() }}</p>
                <p class="text-xs text-surface-500 mt-1">Modules</p>
            </div>
            <div class="rounded-xl bg-surface-50 p-4 text-center">
                <p class="text-2xl font-bold text-surface-900">{{ $roles->count() }}</p>
                <p class="text-xs text-surface-500 mt-1">Roles</p>
            </div>
            <div class="rounded-xl bg-surface-50 p-4 text-center">
                <p class="text-2xl font-bold text-surface-900">{{ $roles->where('is_system', true)->count() }}</p>
                <p class="text-xs text-surface-500 mt-1">System Roles</p>
            </div>
        </div>
    </div>
</x-layouts.admin>
