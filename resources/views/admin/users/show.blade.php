<x-layouts.admin>
    <x-slot:title>{{ $user->full_name }}</x-slot:title>
    <x-slot:header>User Profile</x-slot:header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Profile Card --}}
        <div class="glass-card rounded-2xl p-6 shadow-sm">
            <div class="flex items-center gap-4 mb-6">
                <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center shadow-lg shadow-brand-500/25">
                    <span class="text-xl font-bold text-white">{{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name, 0, 1) }}</span>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-surface-900">{{ $user->full_name }}</h2>
                    <p class="text-sm text-surface-500">{{ $user->email }}</p>
                    <span class="mt-1 inline-block px-2.5 py-1 rounded-full text-xs font-medium
                        {{ match($user->status) { 'active' => 'bg-emerald-100 text-emerald-700', 'locked' => 'bg-red-100 text-red-700', 'suspended' => 'bg-amber-100 text-amber-700', default => 'bg-surface-100 text-surface-600' } }}">
                        {{ ucfirst($user->status) }}
                    </span>
                </div>
            </div>

            <dl class="space-y-3 text-sm border-t border-surface-100 pt-4">
                <div class="flex justify-between"><dt class="text-surface-500">Phone</dt><dd class="font-medium text-surface-900">{{ $user->phone ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-surface-500">National ID</dt><dd class="font-medium text-surface-900">{{ $user->national_id ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-surface-500">Domain</dt><dd class="font-medium text-surface-900">{{ ucfirst(str_replace('_', ' ', $user->domain)) }}</dd></div>
                <div class="flex justify-between"><dt class="text-surface-500">Locale</dt><dd class="font-medium text-surface-900">{{ strtoupper($user->locale_pref ?? 'en') }}</dd></div>
                <div class="flex justify-between"><dt class="text-surface-500">MFA</dt><dd class="font-medium {{ $user->mfa_enabled ? 'text-emerald-600' : 'text-surface-400' }}">{{ $user->mfa_enabled ? 'Enabled' : 'Disabled' }}</dd></div>
                <div class="flex justify-between"><dt class="text-surface-500">Member Since</dt><dd class="font-medium text-surface-900">{{ $user->created_at?->format('M d, Y') }}</dd></div>
            </dl>

            @if($user->domain === 'system_user' && $user->systemUser)
                <div class="mt-4 border-t border-surface-100 pt-4">
                    <h4 class="text-xs font-bold text-surface-500 uppercase tracking-wider mb-2">Staff Info</h4>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-surface-500">Employee ID</dt><dd class="font-medium text-surface-900 font-mono">{{ $user->systemUser->employee_id }}</dd></div>
                        <div class="flex justify-between"><dt class="text-surface-500">Department</dt><dd class="font-medium text-surface-900">{{ $user->systemUser->department ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-surface-500">Position</dt><dd class="font-medium text-surface-900">{{ $user->systemUser->position ?? '—' }}</dd></div>
                    </dl>
                </div>
            @endif

            @if($user->domain === 'beekeeper' && $user->beekeeper)
                <div class="mt-4 border-t border-surface-100 pt-4">
                    <h4 class="text-xs font-bold text-surface-500 uppercase tracking-wider mb-2">Beekeeper Info</h4>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-surface-500">Cooperative</dt><dd class="font-medium text-surface-900">{{ $user->beekeeper->cooperative?->name ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-surface-500">Status</dt><dd><span class="px-2 py-0.5 rounded text-xs font-medium {{ $user->beekeeper->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ ucfirst($user->beekeeper->status) }}</span></dd></div>
                        <div class="flex justify-between"><dt class="text-surface-500">Experience</dt><dd class="font-medium text-surface-900">{{ $user->beekeeper->experience_years ? $user->beekeeper->experience_years . ' years' : '—' }}</dd></div>
                    </dl>
                </div>
            @endif

            {{-- Status Actions --}}
            {{-- Status Actions --}}
            @if($user->id !== auth()->id())
            <div class="mt-6 border-t border-surface-100 pt-4">
                <h4 class="text-xs font-bold text-surface-500 uppercase tracking-wider mb-3">Account Actions</h4>
                @php $canUpdateUser = auth()->user()->hasPermission('user:update'); @endphp
                <div class="flex flex-col gap-3">
                    <form method="POST" action="{{ route('admin.users.update-status', $user) }}" class="flex gap-2">
                        @csrf @method('PATCH')
                        <select name="status" {{ !$canUpdateUser ? 'disabled' : '' }} class="flex-1 rounded-xl border-surface-200 text-sm py-2 {{ $canUpdateUser ? 'bg-white' : 'bg-surface-50 text-surface-400 cursor-not-allowed' }}">
                            <option value="active" {{ $user->status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="locked" {{ $user->status === 'locked' ? 'selected' : '' }}>Locked</option>
                            <option value="suspended" {{ $user->status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                            <option value="inactive" {{ $user->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        <button type="submit" {{ !$canUpdateUser ? 'disabled' : '' }} class="rounded-xl {{ $canUpdateUser ? 'bg-surface-800 text-white hover:bg-surface-700' : 'bg-surface-200 text-surface-400 cursor-not-allowed' }} px-4 py-2 text-sm font-medium transition" title="{{ !$canUpdateUser ? 'Update Status (No Permission)' : '' }}">Update</button>
                    </form>

                    @if($user->mfa_enabled)
                        <div x-data>
                            <button @click="$dispatch('open-reset-2fa-modal')" type="button" {{ !$canUpdateUser ? 'disabled' : '' }} class="w-full flex justify-center items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 py-2.5 text-sm font-medium transition shadow-sm {{ !$canUpdateUser ? 'opacity-50 cursor-not-allowed' : '' }}" title="{{ !$canUpdateUser ? 'Reset 2FA (No Permission)' : 'Reset 2FA' }}">
                                <i class="fas fa-shield-alt text-amber-500"></i> Reset 2FA
                            </button>
                        </div>
                    @endif

                    <!-- Reset Password Logic -->
                    <div x-data>
                        <button @click="$dispatch('open-password-modal')" type="button" {{ !$canUpdateUser ? 'disabled' : '' }} class="w-full flex justify-center items-center gap-2 rounded-xl border border-surface-200 bg-white text-surface-700 hover:bg-surface-50 py-2.5 text-sm font-medium transition shadow-sm {{ !$canUpdateUser ? 'opacity-50 cursor-not-allowed' : '' }}" title="{{ !$canUpdateUser ? 'Reset Password (No Permission)' : 'Reset Password' }}">
                            <i class="fas fa-key text-surface-400"></i> Reset Password
                        </button>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Roles & Permissions --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Roles Management --}}
            <div class="glass-card rounded-2xl p-6 shadow-sm">
                <h3 class="text-base font-semibold text-surface-900 mb-4">
                    <i class="fas fa-user-shield text-brand-500 mr-2"></i> Roles
                </h3>
                <form method="POST" action="{{ route('admin.users.update-roles', $user) }}">
                    @csrf @method('PUT')
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-4">
                        @foreach($allRoles as $role)
                            <label class="flex items-center gap-3 p-3 rounded-xl border transition-colors cursor-pointer
                                {{ $user->roles->contains('id', $role->id) ? 'bg-brand-50 border-brand-200' : 'bg-white border-surface-200 hover:border-surface-300' }}">
                                <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                                    {{ $user->roles->contains('id', $role->id) ? 'checked' : '' }}
                                    {{ !auth()->user()->hasPermission('role:assign') ? 'disabled' : '' }}
                                    class="rounded border-surface-300 text-brand-600 focus:ring-brand-500">
                                <div>
                                    <p class="text-sm font-medium text-surface-800">{{ $role->display_name }}</p>
                                    <p class="text-xs text-surface-400">{{ $role->description }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @php $canAssignRole = auth()->user()->hasPermission('role:assign'); @endphp
                    <div class="flex justify-end">
                        <button type="submit" {{ !$canAssignRole ? 'disabled' : '' }} class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-medium transition {{ $canAssignRole ? 'bg-brand-600 text-white hover:bg-brand-700' : 'bg-surface-200 text-surface-400 cursor-not-allowed' }}" title="{{ !$canAssignRole ? 'Save Roles (No Permission)' : '' }}">
                            <i class="fas fa-save text-xs"></i> Save Roles
                        </button>
                    </div>
                </form>
            </div>

            {{-- Effective Permissions --}}
            <div class="glass-card rounded-2xl p-6 shadow-sm">
                <h3 class="text-base font-semibold text-surface-900 mb-4">
                    <i class="fas fa-key text-brand-500 mr-2"></i> Effective Permissions
                </h3>
                @php
                    $effectivePermissions = $user->getAllPermissions()->sortBy('module')->groupBy('module');
                @endphp
                @forelse($effectivePermissions as $module => $perms)
                    <div class="mb-3">
                        <h4 class="text-xs font-bold text-surface-500 uppercase tracking-wider mb-1.5">{{ ucfirst($module) }}</h4>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($perms as $perm)
                                <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">{{ $perm->display_name }}</span>
                            @endforeach
                        </div>
                    </div>
                @empty
                    @if($user->isSuperAdmin())
                        <div class="rounded-xl bg-amber-50 border border-amber-200 p-4">
                            <p class="text-sm text-amber-800 font-medium"><i class="fas fa-crown text-amber-500 mr-2"></i> Super Administrator — All permissions granted via bypass</p>
                        </div>
                    @else
                        <p class="text-sm text-surface-400">No permissions assigned.</p>
                    @endif
                @endforelse
            </div>
        </div>
@push('modals')
<!-- Reset 2FA Modal -->
<div x-data="{ showResetModal: false }" @open-reset-2fa-modal.window="showResetModal = true">
    <div x-show="showResetModal" class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showResetModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity bg-slate-900/75 backdrop-blur-sm" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="showResetModal" @click.away="showResetModal = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-2xl shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6 border border-slate-100">
                <div class="sm:flex sm:items-start">
                    <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-amber-100 rounded-full sm:mx-0 sm:h-10 sm:w-10 text-amber-600">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg font-medium leading-6 text-slate-900" id="modal-title">Reset 2FA Configuration</h3>
                        <div class="mt-2">
                            <p class="text-sm text-slate-500">Are you sure you want to reset 2FA for this user? This will allow them to log in with just their password and re-configure 2FA on their next login.</p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                    <form method="POST" action="{{ route('admin.users.reset-2fa', $user) }}">
                        @csrf
                        <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-amber-600 text-base font-medium text-white hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 sm:w-auto sm:text-sm">
                            Reset 2FA
                        </button>
                    </form>
                    <button @click="showResetModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Password Reset Modal -->
<div x-data="{ showPasswordModal: false, autoGenerate: true, newPassword: Math.random().toString(36).slice(-8) + 'A1!' }" @open-password-modal.window="showPasswordModal = true">
    <div x-show="showPasswordModal" class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showPasswordModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity bg-slate-900/75 backdrop-blur-sm" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="showPasswordModal" @click.away="showPasswordModal = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-2xl shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6 border border-slate-100">
                <form method="POST" action="{{ route('admin.users.reset-password', $user) }}">
                    @csrf
                    <div class="sm:flex sm:items-start">
                        <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-amber-100 rounded-full sm:mx-0 sm:h-10 sm:w-10 text-amber-600">
                            <i class="fas fa-key"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg font-medium leading-6 text-slate-900" id="modal-title">Reset User Password</h3>
                            <div class="mt-2">
                                <p class="text-sm text-slate-500">Resetting the password will require the user to change it upon their next login. You must provide them with this temporary password.</p>
                                <div class="mt-4">
                                    <label class="flex items-center gap-2 mb-3 cursor-pointer">
                                        <input type="checkbox" x-model="autoGenerate" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                        <span class="text-sm font-medium text-slate-700">Auto-generate password</span>
                                    </label>
                                    <div x-show="!autoGenerate" x-transition>
                                        <input type="text" name="password" x-bind:required="!autoGenerate" class="block w-full rounded-xl border-surface-200 text-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Enter temporary password">
                                    </div>
                                    <div x-show="autoGenerate" x-transition>
                                        <input type="hidden" name="password" x-bind:value="newPassword">
                                        <div class="flex gap-2 items-center">
                                            <code class="px-3 py-2 bg-slate-100 rounded-lg text-lg font-mono tracking-wider flex-1 text-center select-all" x-text="newPassword"></code>
                                            <button type="button" @click="newPassword = Math.random().toString(36).slice(-8) + 'A1!'" class="p-2 text-surface-500 hover:text-brand-600" title="Generate New">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                        <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-brand-600 text-base font-medium text-white hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 sm:w-auto sm:text-sm">
                            Reset Password
                        </button>
                        <button @click="showPasswordModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endpush
</x-layouts.admin>
