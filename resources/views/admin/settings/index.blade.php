<x-layouts.admin>
    <x-slot:title>Settings</x-slot:title>
    <x-slot:header>System Settings</x-slot:header>

    @include('admin.settings._tabs')

    <!-- General Settings Content -->
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf

        @forelse($configs as $group => $groupSettings)
            <div class="mb-8">
                <h3 class="text-base font-semibold text-surface-900 capitalize mb-4 flex items-center gap-2">
                    <span class="inline-flex items-center justify-center h-7 w-7 rounded-lg bg-brand-50">
                        @switch($group)
                            @case('general') <i class="fas fa-globe text-brand-600 text-xs"></i> @break
                            @case('security') <i class="fas fa-shield-alt text-brand-600 text-xs"></i> @break
                            @case('governance') <i class="fas fa-check-double text-brand-600 text-xs"></i> @break
                            @case('quality') <i class="fas fa-star text-brand-600 text-xs"></i> @break
                            @case('payment') <i class="fas fa-credit-card text-brand-600 text-xs"></i> @break
                            @case('traceability') <i class="fas fa-qrcode text-brand-600 text-xs"></i> @break
                            @default <i class="fas fa-cog text-brand-600 text-xs"></i>
                        @endswitch
                    </span>
                    {{ ucfirst($group) }} Settings
                </h3>
                <div class="glass-card rounded-2xl overflow-hidden shadow-sm">
                    <div class="divide-y divide-surface-100">
                        @foreach($groupSettings as $setting)
                            <div class="px-6 py-4 hover:bg-surface-50/30 transition-colors">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex-1">
                                        <label for="setting_{{ $setting->key_name }}" class="font-medium text-surface-800 text-sm">
                                            {{ str_replace('_', ' ', ucwords($setting->key_name, '_')) }}
                                        </label>
                                        @if($setting->description)
                                            <p class="text-xs text-surface-400 mt-0.5">{{ $setting->description }}</p>
                                        @endif
                                    </div>
                                    <div class="w-72 sm:w-80">
                                        @if($setting->key_name === 'commission_default_method')
                                            <select id="setting_{{ $setting->key_name }}"
                                                name="settings[{{ $setting->key_name }}]"
                                                class="block w-full rounded-xl border border-slate-300 py-2.5 px-4 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-brand-500 sm:text-sm shadow-sm bg-white transition-all font-semibold">
                                                <option value="post_sale" {{ $setting->value === 'post_sale' ? 'selected' : '' }}>Post-Sale (% on Ticket Sale)</option>
                                                <option value="pre_deposit" {{ in_array($setting->value, ['pre_deposit', 'pre_sale']) ? 'selected' : '' }}>Pre-Deposit / Pre-Sale (% Upfront on Float Top-Up)</option>
                                            </select>
                                        @elseif($setting->key_name === 'cancellation_refund_mode')
                                            <select id="setting_{{ $setting->key_name }}"
                                                name="settings[{{ $setting->key_name }}]"
                                                class="block w-full rounded-xl border border-slate-300 py-2.5 px-4 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-brand-500 sm:text-sm shadow-sm bg-white transition-all font-semibold">
                                                <option value="immediate" {{ $setting->value === 'immediate' ? 'selected' : '' }}>Immediate (Direct to Agent Wallet)</option>
                                                <option value="hold_until_submission" {{ $setting->value === 'hold_until_submission' ? 'selected' : '' }}>Hold Until Physical Ticket Submission</option>
                                            </select>
                                        @elseif($setting->key_name === 'infant_free_mode')
                                            <select id="setting_{{ $setting->key_name }}"
                                                name="settings[{{ $setting->key_name }}]"
                                                class="block w-full rounded-xl border border-slate-300 py-2.5 px-4 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-brand-500 sm:text-sm shadow-sm bg-white transition-all font-semibold">
                                                <option value="per_adult" {{ $setting->value === 'per_adult' ? 'selected' : '' }}>Per Adult</option>
                                                <option value="per_booking" {{ $setting->value === 'per_booking' ? 'selected' : '' }}>Per Booking</option>
                                            </select>
                                        @elseif(in_array($setting->key_name, ['restrict_agent_routes', 'restrict_agent_ferries', 'shift_management_enabled', 'sms_enabled', 'enable_2fa', 'enable_exchange_rate_conversion', 'agent_can_book_foreigners']))
                                            <div class="flex items-center justify-end">
                                                <label class="relative inline-flex items-center cursor-pointer">
                                                    <input type="hidden" name="settings[{{ $setting->key_name }}]" value="0">
                                                    <input type="checkbox" name="settings[{{ $setting->key_name }}]" value="1" {{ $setting->value == '1' || strtolower($setting->value) === 'true' ? 'checked' : '' }} class="sr-only peer">
                                                    <div class="w-11 h-6 bg-surface-200 peer-focus:ring-4 peer-focus:ring-brand-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-surface-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-500"></div>
                                                </label>
                                            </div>
                                        @elseif($setting->is_sensitive)
                                            <input type="password"
                                                id="setting_{{ $setting->key_name }}"
                                                name="settings[{{ $setting->key_name }}]"
                                                value="{{ $setting->value }}"
                                                class="block w-full rounded-xl border border-slate-300 py-2.5 px-4 text-surface-900 ring-1 ring-inset ring-surface-200 focus:ring-2 focus:ring-brand-500 sm:text-sm shadow-sm bg-white transition-all">
                                        @else
                                            <input type="text"
                                                id="setting_{{ $setting->key_name }}"
                                                name="settings[{{ $setting->key_name }}]"
                                                value="{{ $setting->value }}"
                                                class="block w-full rounded-xl border border-slate-300 py-2.5 px-4 text-surface-900 ring-1 ring-inset ring-surface-200 focus:ring-2 focus:ring-brand-500 sm:text-sm shadow-sm bg-white transition-all">
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            <div class="glass-card rounded-2xl p-12 text-center shadow-sm">
                <div class="inline-flex items-center justify-center h-14 w-14 rounded-full bg-surface-100 mb-4">
                    <i class="fas fa-cog text-surface-400 text-xl"></i>
                </div>
                <p class="text-sm text-surface-500">No settings configured yet.</p>
                <p class="text-xs text-surface-400 mt-1">Run the database seeders to populate settings.</p>
            </div>
        @endforelse

        @if($configs->count() > 0)
        <div class="flex justify-end mt-4">
            @php $canUpdateSettings = auth()->user()->hasPermission('setting:update'); @endphp
            @if($canUpdateSettings)
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-500/25 hover:shadow-brand-500/40 hover:-translate-y-0.5 transition-all">
                <i class="fas fa-save text-xs"></i>
                Save Settings
            </button>
            @else
            <button type="button" disabled class="inline-flex items-center gap-2 rounded-xl bg-slate-200 px-6 py-2.5 text-sm font-semibold text-slate-400 shadow-sm cursor-not-allowed" title="Save Settings (No Permission)">
                <i class="fas fa-save text-xs"></i>
                Save Settings
            </button>
            @endif
        </div>
        @endif
    </form>
</x-layouts.admin>
