{{-- Settings Tab Navigation --}}
<div class="mb-6">
    <nav class="flex space-x-1 rounded-xl bg-white/60 backdrop-blur-sm p-1 shadow-sm ring-1 ring-surface-200/50" aria-label="Tabs">
        <a href="{{ route('admin.settings.index') }}"
           class="rounded-lg px-4 py-2.5 text-sm font-medium transition-all {{ request()->routeIs('admin.settings.index') ? 'bg-white text-brand-700 shadow-sm ring-1 ring-surface-200/50' : 'text-surface-500 hover:text-surface-700 hover:bg-white/50' }}">
            <i class="fas fa-cog mr-2"></i>General
        </a>
        @if(auth()->user()?->hasPermission('role:list'))
        <a href="{{ route('admin.settings.roles') }}"
           class="rounded-lg px-4 py-2.5 text-sm font-medium transition-all {{ request()->routeIs('admin.settings.roles') ? 'bg-white text-brand-700 shadow-sm ring-1 ring-surface-200/50' : 'text-surface-500 hover:text-surface-700 hover:bg-white/50' }}">
            <i class="fas fa-user-shield mr-2"></i>Roles
        </a>
        <a href="{{ route('admin.settings.permissions') }}"
           class="rounded-lg px-4 py-2.5 text-sm font-medium transition-all {{ request()->routeIs('admin.settings.permissions') ? 'bg-white text-brand-700 shadow-sm ring-1 ring-surface-200/50' : 'text-surface-500 hover:text-surface-700 hover:bg-white/50' }}">
            <i class="fas fa-key mr-2"></i>Permissions
        </a>
        @endif
        <a href="{{ route('admin.settings.menus.index') }}"
           class="rounded-lg px-4 py-2.5 text-sm font-medium transition-all {{ request()->routeIs('admin.settings.menus.*') ? 'bg-white text-brand-700 shadow-sm ring-1 ring-surface-200/50' : 'text-surface-500 hover:text-surface-700 hover:bg-white/50' }}">
            <i class="fas fa-bars mr-2"></i>Menus
        </a>

        <a href="{{ route('admin.settings.lookups.index') }}"
           class="rounded-lg px-4 py-2.5 text-sm font-medium transition-all {{ request()->routeIs('admin.settings.lookups.*') ? 'bg-white text-brand-700 shadow-sm ring-1 ring-surface-200/50' : 'text-surface-500 hover:text-surface-700 hover:bg-white/50' }}">
            <i class="fas fa-list-check mr-2"></i>Lookups & Statuses
        </a>
        <a href="{{ route('admin.settings.payment-channels.index') }}"
           class="rounded-lg px-4 py-2.5 text-sm font-medium transition-all {{ request()->routeIs('admin.settings.payment-channels.*') ? 'bg-white text-brand-700 shadow-sm ring-1 ring-surface-200/50' : 'text-surface-500 hover:text-surface-700 hover:bg-white/50' }}">
            <i class="fas fa-wallet mr-2"></i>Payment Channels
        </a>
        <a href="{{ route('admin.settings.change-password') }}"
           class="rounded-lg px-4 py-2.5 text-sm font-medium transition-all {{ request()->routeIs('admin.settings.change-password') ? 'bg-white text-brand-700 shadow-sm ring-1 ring-surface-200/50' : 'text-surface-500 hover:text-surface-700 hover:bg-white/50' }}">
            <i class="fas fa-key mr-2"></i>Change Password
        </a>
    </nav>
</div>
