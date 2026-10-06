<x-layouts.admin>
    <x-slot:title>Change Password</x-slot:title>
    <x-slot:header>Settings — Change Password</x-slot:header>

    @include('admin.settings._tabs')

    <div class="max-w-xl mx-auto">
        @if (session()->has('success'))
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex items-start gap-3 animate-fade-in-up">
                <i class="fas fa-check-circle mt-1"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl shadow-sm text-sm">
                <div class="font-bold mb-1 flex items-center gap-2"><i class="fas fa-exclamation-circle"></i> Please fix the following:</div>
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="glass-card rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-surface-100 bg-surface-50/50">
                <h3 class="font-semibold text-surface-900 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-brand-500 text-white flex items-center justify-center shadow-md shadow-brand-500/25">
                        <i class="fas fa-key"></i>
                    </div>
                    Change Your Password
                </h3>
                <p class="text-xs text-surface-400 mt-2 ml-[52px]">
                    Password must be at least 8 characters with uppercase, lowercase, numbers, and symbols.
                </p>
            </div>

            <form method="POST" action="{{ route('admin.settings.change-password.update') }}" class="p-6 space-y-5">
                @csrf

                <!-- Current Password -->
                <div>
                    <label for="current_password" class="block text-sm font-semibold text-surface-700 mb-1.5">
                        Current Password <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-surface-400 text-sm"></i>
                        <input type="password" name="current_password" id="current_password" required
                               class="w-full bg-white border border-surface-200 rounded-xl pl-10 pr-4 py-3 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors placeholder:text-surface-400 text-surface-900 shadow-sm"
                               placeholder="Enter your current password">
                    </div>
                </div>

                <!-- New Password -->
                <div>
                    <label for="new_password" class="block text-sm font-semibold text-surface-700 mb-1.5">
                        New Password <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <i class="fas fa-shield-alt absolute left-3 top-1/2 -translate-y-1/2 text-surface-400 text-sm"></i>
                        <input type="password" name="new_password" id="new_password" required
                               class="w-full bg-white border border-surface-200 rounded-xl pl-10 pr-4 py-3 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors placeholder:text-surface-400 text-surface-900 shadow-sm"
                               placeholder="Enter a strong new password">
                    </div>
                    <div class="mt-2 grid grid-cols-2 gap-2 text-[11px] text-surface-400">
                        <span><i class="fas fa-check-circle text-surface-300 mr-1"></i> Min 8 characters</span>
                        <span><i class="fas fa-check-circle text-surface-300 mr-1"></i> Uppercase & lowercase</span>
                        <span><i class="fas fa-check-circle text-surface-300 mr-1"></i> At least one number</span>
                        <span><i class="fas fa-check-circle text-surface-300 mr-1"></i> At least one symbol</span>
                    </div>
                </div>

                <!-- Confirm New Password -->
                <div>
                    <label for="new_password_confirmation" class="block text-sm font-semibold text-surface-700 mb-1.5">
                        Confirm New Password <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <i class="fas fa-shield-alt absolute left-3 top-1/2 -translate-y-1/2 text-surface-400 text-sm"></i>
                        <input type="password" name="new_password_confirmation" id="new_password_confirmation" required
                               class="w-full bg-white border border-surface-200 rounded-xl pl-10 pr-4 py-3 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors placeholder:text-surface-400 text-surface-900 shadow-sm"
                               placeholder="Re-enter new password">
                    </div>
                </div>

                <!-- Submit -->
                <div class="pt-4 border-t border-surface-100">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-500/25 hover:shadow-brand-500/40 hover:-translate-y-0.5 transition-all">
                        <i class="fas fa-save text-xs"></i> Update Password
                    </button>
                </div>
            </form>
        </div>

        @if(auth()->user()->password_changed_at)
            <div class="mt-4 text-center text-xs text-surface-400">
                <i class="fas fa-clock mr-1"></i>
                Last changed: {{ auth()->user()->password_changed_at->diffForHumans() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
