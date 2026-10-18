<x-layouts.admin>
    <x-slot:title>Payment Channels</x-slot:title>
    <x-slot:header>System Settings</x-slot:header>

    @include('admin.settings._tabs')

    @php
        $canUpdate = auth()->user()?->hasPermission('setting:update');
        $flowLabels = ['cash' => 'Cash', 'ussd_push' => 'USSD push', 'lipa_namba' => 'Lipa Namba'];
        $flowColors = ['cash' => 'bg-emerald-100 text-emerald-700', 'ussd_push' => 'bg-cyan-100 text-cyan-700', 'lipa_namba' => 'bg-amber-100 text-amber-700'];
        $input = 'w-full bg-white border border-surface-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20';
    @endphp

    <div class="space-y-8" x-data="{ editing: null, adding: false }">

        {{-- ── Checkout channels ───────────────────────────────── --}}
        <section>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-lg font-bold text-surface-900 flex items-center gap-2">
                        <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-brand-50 text-brand-600"><i class="fas fa-wallet text-sm"></i></span>
                        Checkout Channels
                    </h3>
                    <p class="text-sm text-surface-500 mt-1">What cargo checkout offers in both portals, and how each channel collects money.</p>
                </div>
                @if($canUpdate)
                    <button @click="adding = true" class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-xl font-medium shadow-sm text-sm">
                        <i class="fas fa-plus"></i> Add Channel
                    </button>
                @endif
            </div>

            <div class="glass-card rounded-2xl overflow-hidden shadow-sm border border-surface-200/60">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-surface-50/80 border-b border-surface-200/80 text-xs uppercase font-bold text-surface-500 tracking-wider">
                            <tr>
                                <th class="py-3 px-5">Channel</th><th class="py-3 px-5">Flow</th><th class="py-3 px-5">Integration / Details</th>
                                <th class="py-3 px-5">At checkout</th><th class="py-3 px-5 text-right">Order</th><th class="py-3 px-5"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-100">
                            @foreach($methods as $method)
                                <tr class="{{ $method->is_active ? '' : 'bg-surface-50/60 text-surface-400' }}">
                                    <td class="py-3 px-5">
                                        <div class="font-semibold {{ $method->is_active ? 'text-surface-900' : '' }}"><i class="fas {{ $method->icon }} mr-2 w-4 text-center"></i>{{ $method->name }}</div>
                                        <div class="text-[11px] font-mono text-surface-400">{{ $method->slug }}</div>
                                    </td>
                                    <td class="py-3 px-5">
                                        @if($method->flow)
                                            <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $flowColors[$method->flow] }}">{{ $flowLabels[$method->flow] }}</span>
                                        @else
                                            <span class="text-xs italic">Suspended — integration pending</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-5 text-xs">
                                        @if($method->flow === 'ussd_push')
                                            @php $provider = $providers[$method->driver] ?? null; @endphp
                                            {{ $drivers[$method->driver] ?? $method->driver }} ·
                                            @if($provider && $provider['configured'])
                                                <span class="text-emerald-600 font-bold">Live credentials</span>
                                            @elseif($simulatorAllowed)
                                                <span class="text-violet-600 font-bold">Simulator (no credentials)</span>
                                            @else
                                                <span class="text-rose-600 font-bold">Not configured</span>
                                            @endif
                                        @elseif($method->flow === 'lipa_namba')
                                            @if($method->account_number)
                                                <span class="font-mono font-bold">{{ $method->account_number }}</span> {{ $method->account_name }}
                                            @else
                                                <span class="text-rose-600 font-bold">Set the Lipa Namba to enable</span>
                                            @endif
                                        @elseif($method->flow === 'cash')
                                            Recorded at the counter
                                        @endif
                                    </td>
                                    <td class="py-3 px-5">
                                        @if($method->checkout_ready)
                                            <span class="text-emerald-600 font-bold text-xs"><i class="fas fa-check-circle mr-1"></i>Offered</span>
                                        @elseif($method->is_active)
                                            <span class="text-amber-600 font-bold text-xs"><i class="fas fa-exclamation-circle mr-1"></i>Active, not ready</span>
                                        @else
                                            <span class="text-xs">Off</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-5 text-right font-mono text-xs">{{ $method->sort_order }}</td>
                                    <td class="py-3 px-5 text-right">
                                        @if($canUpdate)
                                            <button @click="editing = @js($method->only(['id', 'name', 'flow', 'driver', 'sort_order', 'account_number', 'account_name', 'instructions', 'is_active']))"
                                                    class="text-surface-400 hover:text-brand-600 p-1" title="Edit"><i class="fas fa-pen"></i></button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        {{-- ── Payment rules ───────────────────────────────────── --}}
        <section class="glass-card rounded-2xl shadow-sm border border-surface-200/60 p-6">
            <h3 class="text-base font-bold text-surface-900 mb-1">Payment Rules</h3>
            <p class="text-sm text-surface-500 mb-4">Unpaid bookings are auto-cancelled and their space released when the window closes.</p>
            <form method="POST" action="{{ route('admin.settings.payment-channels.rules') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                @csrf @method('PUT')
                <label class="block text-xs font-bold text-surface-600">USSD push window (minutes)
                    <input type="number" name="cargo_ussd_timeout_minutes" value="{{ $settings['cargo_ussd_timeout_minutes'] ?? 15 }}" min="2" max="120" class="{{ $input }} mt-1" @disabled(!$canUpdate)>
                </label>
                <label class="block text-xs font-bold text-surface-600">Lipa Namba window (minutes)
                    <input type="number" name="cargo_lipa_timeout_minutes" value="{{ $settings['cargo_lipa_timeout_minutes'] ?? 30 }}" min="5" max="1440" class="{{ $input }} mt-1" @disabled(!$canUpdate)>
                </label>
                <label class="block text-xs font-bold text-surface-600">Max push attempts per booking
                    <input type="number" name="cargo_ussd_max_pushes" value="{{ $settings['cargo_ussd_max_pushes'] ?? 3 }}" min="1" max="10" class="{{ $input }} mt-1" @disabled(!$canUpdate)>
                </label>
                <p class="text-xs text-surface-500 sm:col-span-2"><i class="fas fa-comment-sms mr-1"></i>Booking-details SMS to the sender and receiver are set in <a href="{{ route('admin.settings.integrations.index') }}" class="text-brand-600 hover:underline">Integrations › SMS policy</a>.</p>
                @unless($isProduction)
                    <label class="flex items-center gap-2 text-sm text-surface-700">
                        <input type="checkbox" name="cargo_payment_simulator_enabled" value="1" @checked(($settings['cargo_payment_simulator_enabled'] ?? '0') === '1') @disabled(!$canUpdate)>
                        Simulate push channels without credentials <span class="text-xs text-surface-400">(testing only, local — payments auto-complete ~8s later, no money moves)</span>
                    </label>
                @endunless
                @if($canUpdate)
                    <x-dual-control.takes-effect op="payment_rules_update" class="sm:col-span-3" />
                    <div class="sm:col-start-3 text-right"><button class="bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-xl text-sm font-medium">Save rules</button></div>
                @endif
            </form>
        </section>

        {{-- ── Provider credentials ────────────────────────────── --}}
        <section>
            <h3 class="text-lg font-bold text-surface-900 mb-1">USSD Push Providers</h3>
            <p class="text-sm text-surface-500 mb-4">Credentials are encrypted at rest. Leave a secret blank to keep the stored value. Give each provider its callback URL.</p>
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
                @foreach($providers as $driver => $provider)
                    @php $config = $provider['config']; @endphp
                    <form method="POST" action="{{ route('admin.settings.payment-channels.provider', $driver) }}" class="glass-card rounded-2xl shadow-sm border border-surface-200/60 p-5 space-y-3">
                        @csrf @method('PUT')
                        <div class="flex items-center justify-between">
                            <h4 class="font-bold text-surface-900">{{ $provider['label'] }}</h4>
                            @if($provider['configured'])
                                <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">Live · {{ $config->environment }}</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full bg-surface-100 text-surface-500 text-xs font-bold">Not configured</span>
                            @endif
                        </div>
                        @if(in_array($driver, ['tigo_pesa', 'halopesa']))
                            <p class="text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                                Endpoint paths and field names follow the usual push shape — confirm them against your {{ $provider['label'] }} integration document before going live.
                            </p>
                        @endif
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <label class="block text-xs font-bold text-surface-600">Environment
                                <select name="environment" class="{{ $input }} mt-1" @disabled(!$canUpdate)>
                                    <option value="sandbox" @selected(($config->environment ?? 'sandbox') === 'sandbox')>Sandbox</option>
                                    <option value="production" @selected(($config->environment ?? '') === 'production')>Production</option>
                                </select>
                            </label>
                            <label class="block text-xs font-bold text-surface-600 sm:col-span-2">Sandbox URL
                                <input type="url" name="sandbox_url" value="{{ $config->sandbox_url ?? '' }}" class="{{ $input }} mt-1" @disabled(!$canUpdate)>
                            </label>
                            <label class="block text-xs font-bold text-surface-600 sm:col-span-3">Production URL
                                <input type="url" name="production_url" value="{{ $config->production_url ?? '' }}" class="{{ $input }} mt-1" @disabled(!$canUpdate)>
                            </label>
                            @foreach($provider['fields'] as $field => $meta)
                                @php $name = 'f_' . str_replace('.', '__', $field); $value = $provider['values'][$field] ?? null; @endphp
                                <label class="block text-xs font-bold text-surface-600 {{ ($meta['type'] ?? '') === 'textarea' ? 'sm:col-span-3' : '' }}">
                                    {{ $meta['label'] }} @if($meta['required'] ?? false)<span class="text-rose-500">*</span>@endif
                                    @if(($meta['type'] ?? '') === 'textarea')
                                        <textarea name="{{ $name }}" rows="3" class="{{ $input }} mt-1 font-mono text-xs" @disabled(!$canUpdate)>{{ $value }}</textarea>
                                    @elseif($meta['secret'] ?? false)
                                        <input type="password" name="{{ $name }}" autocomplete="new-password" placeholder="{{ $value ? '•••••••• saved' : '' }}" class="{{ $input }} mt-1" @disabled(!$canUpdate)>
                                    @else
                                        <input type="text" name="{{ $name }}" value="{{ $value }}" placeholder="{{ $meta['placeholder'] ?? '' }}" class="{{ $input }} mt-1" @disabled(!$canUpdate)>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-surface-600">Callback URL override <span class="font-normal text-surface-400">(optional)</span>
                                <input type="url" name="callback_url" value="{{ $config->callback_url ?? '' }}" class="{{ $input }} mt-1" @disabled(!$canUpdate)>
                            </label>
                            <p class="text-[11px] text-surface-500 mt-1">Register with {{ $provider['label'] }}: <code class="font-mono break-all text-surface-700">{{ $provider['callback_url'] }}</code></p>
                        </div>
                        @if($canUpdate)
                            <x-dual-control.takes-effect op="payment_provider_update" />
                            <x-dual-control.pending-badge subject="payment_provider:{{ $driver }}" />
                        @endif
                        <div class="flex items-center justify-between pt-2 border-t border-surface-100">
                            <label class="flex items-center gap-2 text-sm text-surface-700">
                                <input type="checkbox" name="is_active" value="1" @checked($config?->is_active) @disabled(!$canUpdate)> Active
                            </label>
                            @if($canUpdate)
                                <button class="bg-surface-900 hover:bg-surface-800 text-white px-4 py-2 rounded-xl text-sm font-medium">Save {{ $provider['label'] }}</button>
                            @endif
                        </div>
                    </form>
                @endforeach
            </div>
        </section>

        {{-- ── Edit / Add channel modal ────────────────────────── --}}
        <template x-if="editing || adding">
            <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="editing = null; adding = false"></div>
                <form method="POST" :action="editing ? '{{ url('admin/settings/payment-channels') }}/' + editing.id : '{{ route('admin.settings.payment-channels.store') }}'"
                      x-data="{ m: editing ? { ...editing } : { name: '', flow: 'lipa_namba', driver: '', sort_order: 80, account_number: '', account_name: '', instructions: '', is_active: true } }"
                      class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg p-6 space-y-3">
                    @csrf
                    <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>
                    <h3 class="text-lg font-bold text-surface-900" x-text="editing ? 'Edit ' + m.name : 'Add Channel'"></h3>
                    <label class="block text-xs font-bold text-surface-600">Display name
                        <input name="name" x-model="m.name" required class="{{ $input }} mt-1">
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="block text-xs font-bold text-surface-600">Flow
                            <select name="flow" x-model="m.flow" class="{{ $input }} mt-1">
                                <option value="">— Suspended —</option>
                                <option value="cash">Cash</option>
                                <option value="ussd_push">USSD push</option>
                                <option value="lipa_namba">Lipa Namba</option>
                            </select>
                        </label>
                        <label class="block text-xs font-bold text-surface-600">Display order
                            <input type="number" name="sort_order" x-model="m.sort_order" min="0" class="{{ $input }} mt-1">
                        </label>
                    </div>
                    <label x-show="m.flow === 'ussd_push'" class="block text-xs font-bold text-surface-600">Provider
                        <select name="driver" x-model="m.driver" class="{{ $input }} mt-1">
                            <option value="">Choose…</option>
                            @foreach($drivers as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                    </label>
                    <div x-show="m.flow === 'lipa_namba'" class="grid grid-cols-2 gap-3">
                        <label class="block text-xs font-bold text-surface-600">Lipa Namba
                            <input name="account_number" x-model="m.account_number" class="{{ $input }} mt-1 font-mono">
                        </label>
                        <label class="block text-xs font-bold text-surface-600">Business name shown
                            <input name="account_name" x-model="m.account_name" class="{{ $input }} mt-1">
                        </label>
                    </div>
                    <label x-show="m.flow === 'lipa_namba'" class="block text-xs font-bold text-surface-600">Instructions for the customer
                        <textarea name="instructions" x-model="m.instructions" rows="3" class="{{ $input }} mt-1"></textarea>
                    </label>
                    <label class="flex items-center gap-2 text-sm text-surface-700">
                        <input type="checkbox" name="is_active" value="1" x-model="m.is_active"> Active
                    </label>
                    <x-dual-control.takes-effect op="payment_method_update" />
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="editing = null; adding = false" class="px-4 py-2 rounded-xl border border-surface-200 text-sm">Cancel</button>
                        <button class="px-4 py-2 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium">Save</button>
                    </div>
                </form>
            </div>
        </template>
    </div>
</x-layouts.admin>
