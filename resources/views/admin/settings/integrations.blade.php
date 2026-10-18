<x-layouts.admin>
    <x-slot:title>Integrations</x-slot:title>
    <x-slot:header>System Settings</x-slot:header>

    @include('admin.settings._tabs')

    @php
        $input = 'block w-full rounded-xl border border-slate-300 py-2 px-3 text-sm text-slate-900 shadow-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white disabled:bg-slate-50 disabled:text-slate-500';
        $statusStyle = [
            'live'       => ['bg-emerald-100 text-emerald-700', 'Live'],
            'sandbox'    => ['bg-amber-100 text-amber-700', 'Sandbox'],
            'incomplete' => ['bg-rose-100 text-rose-700', 'Incomplete'],
            'inactive'   => ['bg-slate-100 text-slate-500', 'Inactive'],
            'unknown'    => ['bg-slate-100 text-slate-500', 'Unknown provider'],
        ];
    @endphp

    <div x-data="integrationsPage(@js(old('provider')))" class="space-y-10">
        <p class="text-sm text-surface-500 max-w-3xl">
            SMS gateways and email servers. Credentials are encrypted at rest and never shown again after saving — leave a secret blank to keep it.
            Changes follow the <a href="{{ route('admin.settings.dual-control-configs.index') }}" class="text-brand-600 hover:underline">Dual Control Rules</a>:
            when a rule is on, the change waits for a checker's approval.
        </p>
        <div class="rounded-2xl border border-surface-200 bg-white px-5 py-3 text-sm text-surface-700 flex flex-wrap items-center justify-between gap-2">
            <span><i class="fas fa-wallet text-brand-600 mr-1"></i> Mobile-money and Lipa Namba providers ({{ $paymentProviders }} configured) are set on <strong>Payment Channels</strong>.</span>
            <a href="{{ route('admin.settings.payment-channels.index') }}" class="text-xs font-semibold text-brand-700 hover:underline">Open Payment Channels</a>
        </div>

        @foreach($channels as $key => $channel)
            <section id="channel-{{ $key }}">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-surface-900 flex items-center gap-2">
                            <span class="inline-flex items-center justify-center h-7 w-7 rounded-lg bg-brand-50"><i class="fas {{ $channel['icon'] }} text-brand-600 text-xs"></i></span>
                            {{ $channel['label'] }}
                        </h3>
                        <p class="text-xs text-surface-400 mt-1">{{ $channel['help'] }}</p>
                    </div>
                    @if($can['create'] && $channel['can_add'])
                        <div class="flex flex-wrap gap-2">
                            @foreach($channel['providers'] as $providerKey => $provider)
                                <button type="button" @click="adding = '{{ $providerKey }}'"
                                        class="inline-flex items-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-600 text-white px-3 py-2 text-xs font-semibold shadow-sm">
                                    <i class="fas fa-plus"></i> Add {{ $provider['label'] }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                @foreach($channel['pending_creates'] as $pendingCreate)
                    <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-xs text-amber-800">
                        <i class="fas fa-hourglass-half mr-1"></i>
                        New “{{ $pendingCreate->payload_after['attributes']['name'] ?? $pendingCreate->payload_after['provider'] }}” is {{ $pendingCreate->status === 'scheduled' ? 'scheduled' : 'waiting for approval' }}
                        (submitted by {{ $pendingCreate->maker?->full_name }}).
                        <a href="{{ route('admin.dual-control.show', $pendingCreate) }}" class="underline">View request</a>
                    </div>
                @endforeach
                @if($channel['pending_priority'])
                    <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-xs text-amber-800">
                        <i class="fas fa-hourglass-half mr-1"></i> A priority change is waiting for approval.
                        <a href="{{ route('admin.dual-control.show', $channel['pending_priority']) }}" class="underline">View request</a>
                    </div>
                @endif

                @if($key === 'sms' && !$smsPolicy['enabled'])
                    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 flex flex-wrap items-center justify-between gap-2">
                        <span><i class="fas fa-triangle-exclamation mr-1"></i> SMS notifications are <strong>off</strong> (General › Notification › Sms Enabled): booking, cancellation, close-of-business and approval SMS are not sent. Account and password SMS still go out.</span>
                        <a href="{{ route('admin.settings.index') }}#setting_sms_enabled" class="text-xs font-semibold underline">Change in General settings</a>
                    </div>
                @endif

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
                    @forelse($channel['configs'] as $i => $card)
                        @php
                            $c = $card['model'];
                            $meta = $card['meta'];
                            $locked = (bool) $card['pending'];
                            $editable = $can['update'] && !$locked && $meta;
                            [$badgeClass, $badgeLabel] = $statusStyle[$card['status']] ?? $statusStyle['unknown'];
                            $ids = $channel['configs']->pluck('model.id')->values();
                        @endphp
                        <div class="glass-card rounded-2xl shadow-sm border border-surface-200/60 p-5 space-y-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <h4 class="font-bold text-surface-900">{{ $c->name }}</h4>
                                    <p class="text-[11px] text-surface-400 font-mono">{{ $meta['label'] ?? $c->provider }} · {{ $c->code }}</p>
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @if($channel['multiple'])
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $c->sort_order === 1 ? 'bg-brand-100 text-brand-700' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $c->sort_order }} · {{ $c->sort_order === 1 ? 'Primary' : 'Failover' }}
                                        </span>
                                    @endif
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $badgeClass }}">{{ $badgeLabel }}</span>
                                    @if($locked)
                                        <a href="{{ route('admin.dual-control.show', $card['pending']) }}" class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700 hover:underline">
                                            <i class="fas fa-hourglass-half"></i> {{ $card['pending']->status === 'scheduled' ? 'Change scheduled' : 'Pending approval' }}
                                        </a>
                                    @endif
                                </div>
                            </div>

                            @if($locked)
                                <p class="text-xs text-amber-700">A change by {{ $card['pending']->maker?->full_name }} is waiting (#DC-{{ $card['pending']->id }}); editing is locked until it is approved, rejected or cancelled.</p>
                            @endif
                            @if($card['status'] === 'incomplete' || ($card['missing'] && $c->is_active))
                                <p class="text-xs text-rose-600">Missing: {{ implode(', ', $card['missing']) }}</p>
                            @endif
                            @if($card['unreadable'])
                                <p class="text-xs text-rose-600">The stored credentials can't be decrypted (was APP_KEY changed?), so they are treated as not set. Enter them again and save.</p>
                            @endif

                            @if($meta)
                                <form method="POST" action="{{ route('admin.settings.integrations.update', $c) }}" class="space-y-4">
                                    @csrf @method('PUT')

                                    @if($editable && !empty($meta['presets']))
                                        <div class="flex flex-wrap items-center gap-2 text-xs">
                                            <span class="text-surface-500">Preset:</span>
                                            @foreach($meta['presets'] as $preset)
                                                <button type="button" class="rounded-lg border border-surface-200 px-2 py-1 hover:bg-surface-50"
                                                        @click="applyPreset($el.closest('form'), @js(collect($preset['values'])->mapWithKeys(fn ($v, $f) => [\App\Support\IntegrationCatalog::inputName($f) => $v])))">
                                                    {{ $preset['label'] }}
                                                </button>
                                                <span class="text-surface-400">{{ $preset['note'] }}</span>
                                            @endforeach
                                        </div>
                                    @endif

                                    @include('admin.settings.partials.integration-fields', [
                                        'meta' => $meta, 'values' => $card['values'], 'secretsSet' => $card['secrets_set'],
                                        'config' => $c, 'editable' => $editable, 'input' => $input,
                                    ])

                                    <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-surface-100">
                                        <label class="flex items-center gap-2 text-sm text-surface-700">
                                            <input type="checkbox" name="is_active" value="1" @checked($c->is_active) @disabled(!$editable) class="rounded border-slate-300"> Active
                                        </label>
                                        @if($editable)
                                            <button class="bg-surface-900 hover:bg-surface-800 text-white px-4 py-2 rounded-xl text-sm font-medium">Save</button>
                                        @endif
                                    </div>

                                    @if($can['update'] && !$locked)
                                        @php
                                            $recipient = $c->channel === 'sms' ? auth()->user()->phone : (in_array($c->channel, ['email'], true) ? auth()->user()->email : null);
                                        @endphp
                                        <div class="rounded-xl bg-surface-50 border border-surface-100 p-3 space-y-2" x-data="{ recipient: @js($recipient) }">
                                            <div class="flex flex-wrap items-center gap-2">
                                                @if(in_array($c->channel, ['sms', 'email'], true))
                                                    <input type="text" x-model="recipient" placeholder="{{ $c->channel === 'sms' ? 'Phone, e.g. 0712 345 678' : 'Email address' }}" class="{{ $input }} !w-56 !py-1.5">
                                                @endif
                                                <button type="button" class="inline-flex items-center gap-1 rounded-xl border border-brand-200 bg-white px-3 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50 disabled:opacity-50"
                                                        :disabled="testing['{{ $c->id }}']"
                                                        @click="runTest($el.closest('form'), '{{ $c->provider }}', '{{ $c->id }}', recipient)">
                                                    <i class="fas" :class="testing['{{ $c->id }}'] ? 'fa-spinner fa-spin' : 'fa-vial'"></i>
                                                    {{ match ($c->channel) { 'sms' => 'Send test SMS', 'email' => 'Send test email', default => 'Test connection' } }}
                                                </button>
                                                <span class="text-[11px] text-surface-400">Uses the values in this form.</span>
                                            </div>
                                            <template x-if="results['{{ $c->id }}']">
                                                <div class="text-xs rounded-lg px-3 py-2" :class="results['{{ $c->id }}'].ok ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-700'">
                                                    <p x-text="results['{{ $c->id }}'].message"></p>
                                                    <pre x-show="results['{{ $c->id }}'].detail" x-text="results['{{ $c->id }}'].detail" class="mt-1 whitespace-pre-wrap break-all font-mono text-[10px] opacity-80"></pre>
                                                </div>
                                            </template>
                                        </div>
                                    @endif
                                </form>

                                <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                                    <span class="text-surface-400">
                                        @if($c->updater) Last changed by {{ $c->updater->full_name }} · @endif
                                        {{ $c->updated_at?->diffForHumans() }}
                                    </span>
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @if($can['update'] && $channel['multiple'] && $ids->count() > 1 && !$channel['pending_priority'])
                                            @foreach(['up' => -1, 'down' => 1] as $dir => $step)
                                                @php $target = $i + $step; @endphp
                                                @if($target >= 0 && $target < $ids->count())
                                                    @php $order = $ids->all(); [$order[$i], $order[$target]] = [$order[$target], $order[$i]]; @endphp
                                                    <form method="POST" action="{{ route('admin.settings.integrations.priority', $key) }}">
                                                        @csrf @method('PUT')
                                                        @foreach($order as $id)<input type="hidden" name="order[]" value="{{ $id }}">@endforeach
                                                        <button class="rounded-lg border border-surface-200 px-2 py-1 hover:bg-surface-50" title="Move {{ $dir }}"><i class="fas fa-arrow-{{ $dir }}"></i></button>
                                                    </form>
                                                @endif
                                            @endforeach
                                        @endif
                                        @if($editable)
                                            <form method="POST" action="{{ route('admin.settings.integrations.status', $c) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="is_active" value="{{ $c->is_active ? 0 : 1 }}">
                                                <button class="rounded-lg border border-surface-200 px-2 py-1 hover:bg-surface-50">{{ $c->is_active ? 'Deactivate' : 'Activate' }}</button>
                                            </form>
                                        @endif
                                        @if($can['delete'] && !$c->is_active && !$locked)
                                            @php $approveDelete ??= app(\App\Services\DualControlService::class)->requiresDualControl('integration_delete'); @endphp
                                            <form method="POST" action="{{ route('admin.settings.integrations.destroy', $c) }}"
                                                data-confirm="Its stored credentials are removed.{{ $approveDelete ? ' A checker must approve it first.' : '' }}"
                                                data-confirm-title="Delete {{ $c->name }}?" data-confirm-button="{{ $approveDelete ? 'Request deletion' : 'Delete' }}" data-confirm-tone="danger">
                                                @csrf @method('DELETE')
                                                <button class="rounded-lg border border-rose-200 text-rose-600 px-2 py-1 hover:bg-rose-50"><i class="fas fa-trash"></i></button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <p class="text-xs text-surface-500">Provider “{{ $c->provider }}” has no driver in this version; it can't be edited here.</p>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-surface-200 p-6 text-sm text-surface-400 xl:col-span-2">
                            Nothing configured yet.
                            @if($key === 'email') Email is going to the {{ config('mail.default') === 'log' ? 'application log (not delivered)' : '.env mail settings' }}.@endif
                        </div>
                    @endforelse
                </div>

                @if($key === 'sms')
                    @include('admin.settings.partials.sms-policy', ['smsPolicy' => $smsPolicy, 'closure' => $closure, 'can' => $can])
                @endif
            </section>
        @endforeach

        {{-- ── Add integration dialog ───────────────────────── --}}
        @if($can['create'])
            @foreach(\App\Support\IntegrationCatalog::PROVIDERS as $providerKey => $meta)
                <template x-if="adding === '{{ $providerKey }}'">
                    <div class="fixed inset-0 z-[300] flex items-center justify-center p-4">
                        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="adding = null"></div>
                        <form method="POST" action="{{ route('admin.settings.integrations.store') }}"
                              class="relative bg-white rounded-3xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto p-6 space-y-4">
                            @csrf
                            <input type="hidden" name="provider" value="{{ $providerKey }}">
                            <h3 class="text-lg font-bold text-surface-900">Add {{ $meta['label'] }}</h3>

                            @if(!empty($meta['presets']))
                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    <span class="text-surface-500">Preset:</span>
                                    @foreach($meta['presets'] as $preset)
                                        <button type="button" class="rounded-lg border border-surface-200 px-2 py-1 hover:bg-surface-50"
                                                @click="applyPreset($el.closest('form'), @js(collect($preset['values'])->mapWithKeys(fn ($v, $f) => [\App\Support\IntegrationCatalog::inputName($f) => $v])))">
                                            {{ $preset['label'] }}
                                        </button>
                                        <span class="text-surface-400">{{ $preset['note'] }}</span>
                                    @endforeach
                                </div>
                            @endif

                            @include('admin.settings.partials.integration-fields', [
                                'meta' => $meta, 'values' => [], 'secretsSet' => [], 'config' => null, 'editable' => true, 'input' => $input,
                            ])

                            <label class="flex items-center gap-2 text-sm text-surface-700">
                                <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300"> Active
                                <span class="text-xs text-surface-400">(only once every required field is filled)</span>
                            </label>
                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="adding = null" class="px-4 py-2 rounded-xl border border-surface-200 text-sm">Cancel</button>
                                <button class="px-4 py-2 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium">Add</button>
                            </div>
                        </form>
                    </div>
                </template>
            @endforeach
        @endif
    </div>

    @push('scripts')
    <script>
        function integrationsPage(adding) {
            return {
                adding,
                testing: {},
                results: {},

                applyPreset(form, values) {
                    Object.entries(values).forEach(([name, value]) => {
                        const el = form.querySelector(`[name="${name}"]`);
                        if (el) el.value = value;
                    });
                },

                copy(text) {
                    navigator.clipboard?.writeText(text).then(() => window.notify('Copied', 'success'));
                },

                async runTest(form, provider, id, recipient) {
                    this.testing[id] = true;
                    this.results[id] = null;
                    const data = new FormData(form);
                    data.delete('_method');
                    data.delete('_token');
                    data.set('provider', provider);
                    data.set('integration_id', id);
                    if (recipient) data.set('recipient', recipient);
                    try {
                        const res = await fetch(@js(route('admin.settings.integrations.test')), {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                            body: data,
                        });
                        const body = await res.json().catch(() => ({}));
                        this.results[id] = res.ok
                            ? body
                            : { ok: false, message: body.message || (res.status === 429 ? 'Too many tests — wait a minute.' : `Request failed (HTTP ${res.status}).`), detail: null };
                    } catch (e) {
                        this.results[id] = { ok: false, message: 'Network error: ' + e.message, detail: null };
                    } finally {
                        this.testing[id] = false;
                    }
                },
            };
        }
    </script>
    @endpush
</x-layouts.admin>
