<x-layouts.admin>
    <x-slot:title>Create User</x-slot:title>
    <x-slot:header>Register New User</x-slot:header>

    <form method="POST" action="{{ route('admin.users.store') }}" class="max-w-2xl space-y-6">
        @csrf
        @if($errors->any())
            <div class="rounded-xl bg-red-50 border border-red-200 p-4">
                <ul class="list-disc list-inside text-sm text-red-600 space-y-0.5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        {{-- Personal Info --}}
        <div class="glass-card rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-surface-50/80 border-b border-surface-200/60">
                <h3 class="text-sm font-semibold text-surface-900"><i class="fas fa-user text-brand-500 mr-2"></i> Personal Information</h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-medium text-surface-700 mb-1.5">First Name <span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required
                        class="w-full rounded-xl border-0 py-2.5 px-4 text-sm ring-1 ring-inset ring-surface-200 focus:ring-2 focus:ring-brand-500 bg-white shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-surface-700 mb-1.5">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required
                        class="w-full rounded-xl border-0 py-2.5 px-4 text-sm ring-1 ring-inset ring-surface-200 focus:ring-2 focus:ring-brand-500 bg-white shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-surface-700 mb-1.5">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="w-full rounded-xl border-0 py-2.5 px-4 text-sm ring-1 ring-inset ring-surface-200 focus:ring-2 focus:ring-brand-500 bg-white shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-surface-700 mb-1.5">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" placeholder="+255..."
                        class="w-full rounded-xl border-0 py-2.5 px-4 text-sm ring-1 ring-inset ring-surface-200 focus:ring-2 focus:ring-brand-500 bg-white shadow-sm">
                </div>
            </div>
        </div>

        {{-- Account --}}
        <div class="glass-card rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-surface-50/80 border-b border-surface-200/60">
                <h3 class="text-sm font-semibold text-surface-900"><i class="fas fa-key text-brand-500 mr-2"></i> Account</h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-medium text-surface-700 mb-1.5">Domain <span class="text-red-500">*</span></label>
                    <select name="domain" required class="w-full rounded-xl border-0 py-2.5 px-3 text-sm ring-1 ring-inset ring-surface-200 focus:ring-2 focus:ring-brand-500 bg-white shadow-sm">
                        @foreach(['system_user' => 'System User', 'beekeeper' => 'Beekeeper', 'consumer' => 'Consumer', 'investor' => 'Investor'] as $k => $v)
                            <option value="{{ $k }}" {{ old('domain') === $k ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-surface-700 mb-1.5">Roles</label>
                    <div class="space-y-2 max-h-32 overflow-y-auto p-2 rounded-xl ring-1 ring-surface-200">
                        @foreach($roles as $role)
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" name="roles[]" value="{{ $role->id }}" {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}
                                    class="rounded border-surface-300 text-brand-600 focus:ring-brand-500">
                                {{ $role->display_name ?? $role->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <!-- Password fields removed as they are auto-generated and sent via SMS -->
            </div>
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('admin.users.index') }}" class="text-sm text-surface-500 hover:text-surface-700"><i class="fas fa-arrow-left mr-1"></i> Back</a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 px-8 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-500/25 hover:shadow-brand-500/40 hover:-translate-y-0.5 transition-all">
                <i class="fas fa-user-plus text-xs"></i> Create User
            </button>
        </div>
    </form>
</x-layouts.admin>
