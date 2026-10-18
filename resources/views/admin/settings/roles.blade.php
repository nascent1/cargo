<x-layouts.admin>
    <x-slot:title>Role Management</x-slot:title>
    <x-slot:header>Settings — Roles & Permissions Matrix</x-slot:header>

    @include('admin.settings._tabs')

        <div x-data="{ 
        activeRole: null, 
        roleSearch: '', 
        permSearch: '', 
        showCreateModal: false, 
        showEditModal: false, 
        editRole: { id: null, display_name: '', description: '' }, 
        showDeleteModal: false, 
        deleteRole: { id: null, display_name: '' } 
    }">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {{-- Left Pane: Role List --}}
        <div class="lg:col-span-4 space-y-4 flex flex-col h-[800px]">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-surface-500 uppercase tracking-wider flex items-center gap-2">
                    <i class="fas fa-shield-halved text-brand-500"></i> Roles
                </h3>
                <div class="flex items-center gap-2">
                    <span class="text-xs bg-surface-100 text-surface-500 px-2 py-1 rounded-md">{{ $roles->count() }} Total</span>
                    @can('role:manage')
                    <button @click="showCreateModal = true" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-xs font-bold shadow-sm transition-all">
                        <i class="fas fa-plus text-[10px]"></i> New Role
                    </button>
                    @endcan
                </div>
            </div>

            <!-- Role Search -->
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-surface-400 text-sm"></i>
                <input type="text" x-model="roleSearch" placeholder="Search roles..." class="w-full bg-white border border-surface-200 rounded-xl pl-9 pr-4 py-2.5 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors placeholder:text-surface-400 text-surface-900 shadow-sm">
            </div>

            <div class="overflow-y-auto space-y-3 pr-2 custom-scrollbar flex-1">
                @foreach($roles as $role)
                    <div x-show="roleSearch === '' || '{{ strtolower($role->display_name . ' ' . $role->name) }}'.includes(roleSearch.toLowerCase())"
                         @click="activeRole = activeRole === {{ $role->id }} ? null : {{ $role->id }}; permSearch = ''"
                         :class="activeRole === {{ $role->id }} ? 'ring-2 ring-brand-500 bg-gradient-to-br from-brand-50 to-white shadow-md transform scale-[1.02]' : 'bg-white hover:bg-surface-50'"
                         class="glass-card rounded-xl p-4 cursor-pointer transition-all duration-300 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-semibold text-surface-900">{{ $role->display_name }}</h4>
                                <p class="text-xs text-surface-400 mt-0.5">{{ $role->description ?: $role->name }}</p>
                            </div>
                            <div class="flex flex-col items-end gap-1.5">
                                @if($role->is_system)
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold bg-surface-100 text-surface-500 uppercase">System</span>
                                @endif
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium bg-blue-100 text-blue-700">
                                    {{ $role->permissions_count }} perms
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Right Pane: Permission Assignment Panel --}}
        <div class="lg:col-span-8 flex flex-col h-[800px] relative">
            @foreach($roles as $role)
                <div x-show="activeRole === {{ $role->id }}" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="absolute inset-0 flex flex-col">
                    <form method="POST" action="{{ route('admin.settings.roles.permissions', $role) }}" class="flex flex-col h-full">
                        @csrf
                        <div class="glass-card rounded-2xl shadow-sm overflow-hidden flex flex-col h-full">
                            <!-- Header & Search -->
                            <div class="px-6 py-4 border-b border-surface-100 bg-surface-50/50 flex items-center justify-between shrink-0">
                                <div>
                                    <h3 class="font-semibold text-surface-900 flex items-center gap-3">
                                        {{ $role->display_name }}
                                        @if($role->slug === 'super_admin')
                                            <span class="px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-200 text-xs font-medium text-amber-700">
                                                <i class="fas fa-crown mr-1"></i> Bypasses all checks
                                            </span>
                                        @endif
                                    </h3>
                                    <p class="text-xs text-surface-400 mt-1">Select permissions for this role</p>
                                    <div class="flex items-center gap-2 mt-2">
                                        @if($role->slug !== 'super_admin' && auth()->user()->hasPermission('role:manage'))
                                            <button type="button" @click.prevent="editRole = { id: {{ $role->id }}, display_name: '{{ addslashes($role->display_name) }}', description: '{{ addslashes($role->description) }}' }; showEditModal = true" class="px-3 py-1.5 rounded-lg bg-surface-100 text-surface-600 hover:bg-surface-200 text-xs font-semibold transition-colors flex items-center gap-1.5">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            @if(!$role->is_system)
                                                <button type="button" @click.prevent="deleteRole = { id: {{ $role->id }}, display_name: '{{ addslashes($role->display_name) }}' }; showDeleteModal = true" class="px-3 py-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 text-xs font-semibold transition-colors flex items-center gap-1.5">
                                                    <i class="fas fa-trash-alt"></i> Delete
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-4">
                                    <div class="relative w-64">
                                        <i class="fas fa-filter absolute left-3 top-1/2 -translate-y-1/2 text-surface-400 text-xs"></i>
                                        <input type="text" x-model="permSearch" placeholder="Filter permissions..." class="w-full bg-white border border-surface-200 rounded-lg pl-8 pr-3 py-1.5 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors placeholder:text-surface-400 text-surface-900 shadow-sm">
                                    </div>
                                </div>
                            </div>

                            <!-- Permissions List -->
                            <div class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar bg-white">
                                @foreach($permissions as $module => $perms)
                                    <div x-show="Array.from($el.querySelectorAll('.perm-item')).some(el => el.style.display !== 'none')">
                                        <!-- Module Header -->
                                        <h4 class="text-xs font-bold text-surface-500 uppercase tracking-wider mb-2 flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-brand-400"></span>
                                            {{ str_replace('_', ' ', ucfirst($module)) }}
                                        </h4>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            @foreach($perms as $perm)
                                                <label class="perm-item flex items-center justify-between p-3.5 rounded-xl border border-surface-200 bg-white shadow-sm hover:border-brand-300 hover:shadow-md transition-all cursor-pointer group"
                                                       x-show="permSearch === '' || '{{ strtolower($perm->display_name . ' ' . $perm->name) }}'.includes(permSearch.toLowerCase())">
                                                    <div>
                                                        <span class="text-sm font-semibold text-surface-800 group-hover:text-brand-600 transition-colors">{{ $perm->display_name }}</span>
                                                        <span class="block text-[10px] text-surface-400 font-mono mt-0.5">{{ $perm->name }}</span>
                                                    </div>
                                                    <div class="relative inline-flex items-center cursor-pointer shrink-0">
                                                        <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                                                            {{ $role->permissions->contains('id', $perm->id) ? 'checked' : '' }}
                                                            {{ $role->slug === 'super_admin' ? 'disabled' : '' }}
                                                            class="sr-only peer">
                                                        <div class="w-11 h-6 bg-surface-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-brand-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-surface-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-500 {{ $role->slug === 'super_admin' ? 'opacity-50 cursor-not-allowed' : '' }}"></div>
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if($role->slug !== 'super_admin')
                            <div class="px-6 pt-4 shrink-0"><x-dual-control.takes-effect op="role_permissions_update" /></div>
                            <div class="px-6 py-4 border-t border-surface-100 flex justify-end bg-surface-50/50 shrink-0">
                                @php $canManageRole = auth()->user()->hasPermission('role:manage'); @endphp
                                @if($canManageRole)
                                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-500/25 hover:shadow-brand-500/40 hover:-translate-y-0.5 transition-all">
                                    <i class="fas fa-save text-xs"></i> Save Permissions
                                </button>
                                @else
                                <button type="button" disabled class="inline-flex items-center gap-2 rounded-xl bg-slate-200 px-6 py-2.5 text-sm font-semibold text-slate-400 shadow-sm cursor-not-allowed" title="Save Permissions (No Permission)">
                                    <i class="fas fa-save text-xs"></i> Save Permissions
                                </button>
                                @endif
                            </div>
                            @endif
                        </div>
                    </form>
                </div>
            @endforeach

            {{-- Empty state --}}
            <div x-show="!activeRole" class="glass-card rounded-2xl p-12 text-center shadow-sm h-full flex flex-col items-center justify-center">
                <div class="inline-flex items-center justify-center h-16 w-16 rounded-full bg-surface-100 mb-4">
                    <i class="fas fa-user-shield text-surface-300 text-2xl"></i>
                </div>
                <h3 class="text-sm font-semibold text-surface-600 mb-1">Select a Role</h3>
                <p class="text-xs text-surface-400">Click on a role from the left panel to manage its permissions.</p>
            </div>
        </div>
    </div>

    {{-- Create Role Modal --}}
    <div x-show="showCreateModal" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
        <div @click.outside="showCreateModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
            <div class="px-6 py-4 border-b border-surface-100 bg-surface-50/50">
                <h3 class="font-semibold text-surface-900 flex items-center gap-2">
                    <i class="fas fa-plus-circle text-brand-500"></i> Create New Role
                </h3>
            </div>
            <form method="POST" action="{{ route('admin.settings.roles.create') }}" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-surface-700 mb-1.5">Display Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="display_name" required placeholder="e.g. Senior Agent"
                           class="w-full bg-white border border-surface-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors placeholder:text-surface-400 text-surface-900 shadow-sm">
                    <p class="text-[11px] text-surface-400 mt-1">A slug will be auto-generated from this name.</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-surface-700 mb-1.5">Description</label>
                    <textarea name="description" rows="2" placeholder="Brief description of this role's purpose..."
                              class="w-full bg-white border border-surface-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors placeholder:text-surface-400 text-surface-900 shadow-sm resize-none"></textarea>
                </div>
                <x-dual-control.takes-effect op="role_create" />
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 rounded-xl border border-surface-300 text-surface-600 font-semibold text-sm hover:bg-surface-50 transition-colors">Cancel</button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-brand-500/25 hover:shadow-brand-500/40 transition-all">
                        <i class="fas fa-plus text-xs"></i> Create Role
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    </div>


    {{-- Edit Role Modal --}}
    <div x-show="showEditModal" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
        <div @click.outside="showEditModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
            <div class="px-6 py-4 border-b border-surface-100 bg-surface-50/50">
                <h3 class="font-semibold text-surface-900 flex items-center gap-2">
                    <i class="fas fa-edit text-brand-500"></i> Edit Role
                </h3>
            </div>
            <form method="POST" :action="`{{ url('admin/settings/roles') }}/${editRole.id}`" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-semibold text-surface-700 mb-1.5">Display Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="display_name" x-model="editRole.display_name" required
                           class="w-full bg-white border border-surface-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-surface-700 mb-1.5">Description</label>
                    <textarea name="description" x-model="editRole.description" rows="2"
                              class="w-full bg-white border border-surface-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors shadow-sm resize-none"></textarea>
                </div>
                <x-dual-control.takes-effect op="role_update" />
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 rounded-xl border border-surface-300 text-surface-600 font-semibold text-sm hover:bg-surface-50 transition-colors">Cancel</button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-brand-500/25 hover:shadow-brand-500/40 transition-all">
                        <i class="fas fa-save text-xs"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Role Modal --}}
    <div x-show="showDeleteModal" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
        <div @click.outside="showDeleteModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
            <div class="px-6 py-4 border-b border-surface-100 bg-rose-50/50">
                <h3 class="font-semibold text-rose-700 flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle"></i> Delete Role
                </h3>
            </div>
            <form method="POST" :action="`{{ url('admin/settings/roles') }}/${deleteRole.id}`" class="p-6">
                @csrf
                @method('DELETE')
                <p class="text-sm text-surface-600 mb-6">
                    Are you sure you want to delete the <span class="font-bold text-surface-900" x-text="deleteRole.display_name"></span> role? This action cannot be undone and will fail if users are still assigned to it.
                </p>
                <x-dual-control.takes-effect op="role_delete" class="mb-4" />
                <div class="flex items-center justify-end gap-3">
                    <button type="button" @click="showDeleteModal = false" class="px-4 py-2 rounded-xl border border-surface-300 text-surface-600 font-semibold text-sm hover:bg-surface-50 transition-colors">Cancel</button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-rose-500/25 hover:bg-rose-700 transition-all">
                        <i class="fas fa-trash-alt text-xs"></i> Delete Role
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: #cbd5e1; /* slate-300 / surface-300 */
            border-radius: 20px;
        }
        .custom-scrollbar:hover::-webkit-scrollbar-thumb {
            background-color: #94a3b8; /* slate-400 / surface-400 */
        }
    </style>
</x-layouts.admin>
