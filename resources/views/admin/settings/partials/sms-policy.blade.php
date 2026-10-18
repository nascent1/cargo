{{--
    SMS policy panel (under the SMS gateways on Settings › Integrations).
    @param array $smsPolicy \App\Services\SmsNotifier::policySummary()
    @param array $closure   \App\Services\Cargo\CloseOfBusinessService::panel()
    @param array $can       create/update/delete permissions
--}}
<div class="mt-6 grid grid-cols-1 xl:grid-cols-5 gap-5">
    {{-- What sends an SMS --}}
    <div class="glass-card rounded-2xl shadow-sm border border-surface-200/60 p-5 xl:col-span-3">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <h4 class="font-bold text-surface-900"><i class="fas fa-list-check text-brand-600 mr-1"></i> SMS policy — what sends an SMS</h4>
            <div class="flex items-center gap-2 text-xs">
                <a href="{{ route('admin.settings.index') }}#group-notification"
                   class="px-2 py-0.5 rounded-full font-bold {{ $smsPolicy['enabled'] ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                    Master switch {{ $smsPolicy['enabled'] ? 'ON' : 'OFF' }}
                </a>
                @unless($smsPolicy['gateway_ready'])
                    <span class="px-2 py-0.5 rounded-full font-bold bg-rose-100 text-rose-700">No live SMS gateway</span>
                @endunless
            </div>
        </div>
        @if($smsPolicy['tracking']['public'])
            <p class="mb-3 text-xs text-surface-500"><i class="fas fa-link mr-1"></i>Booking SMS and receipts link to
                <a href="{{ $smsPolicy['tracking']['url'] }}" target="_blank" rel="noopener" class="font-mono text-brand-600 hover:underline">{{ $smsPolicy['tracking']['url'] }}</a> (APP_URL in .env).</p>
        @else
            <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                <i class="fas fa-triangle-exclamation mr-1"></i>Tracking links are left out of booking SMS:
                APP_URL in <code>.env</code> is <span class="font-mono">{{ config('app.url') }}</span>, which customers' phones can't open.
                Set it to the public address (e.g. https://cargo.example.co.tz). Receipt QR codes use the same address.
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="text-surface-400 uppercase tracking-wider">
                    <tr><th class="py-2 pr-3">Event</th><th class="py-2 pr-3">Sent to</th><th class="py-2 pr-3">Own switch</th><th class="py-2 pr-3">Needs master switch</th><th class="py-2">Template</th></tr>
                </thead>
                <tbody class="divide-y divide-surface-100">
                    @foreach($smsPolicy['events'] as $event)
                        <tr>
                            <td class="py-2 pr-3 font-medium text-surface-800">{{ $event['label'] }}</td>
                            <td class="py-2 pr-3 text-surface-500">{{ $event['recipient'] }}</td>
                            <td class="py-2 pr-3">
                                @isset($event['switch'])
                                    <a href="{{ route('admin.settings.index') }}#group-sms" class="hover:underline {{ $event['switch_on'] ? 'text-emerald-700' : 'text-rose-600' }}">{{ $event['switch_on'] ? 'On' : 'Off' }}</a>
                                @else
                                    <span class="text-surface-300">—</span>
                                @endisset
                            </td>
                            <td class="py-2 pr-3">{{ $event['gated'] ? 'Yes' : 'No — always sent' }}</td>
                            <td class="py-2">
                                <a href="{{ route('admin.notifications.templates', ['event' => $event['key'], 'channel' => 'sms']) }}" class="hover:underline {{ $event['template_on'] ? 'text-emerald-700' : 'text-rose-600' }}">
                                    {{ $event['template_on'] ? 'On (' . implode('/', $event['locales']) . ')' : 'Off' }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-[11px] text-surface-400">
            Unpaid holds, lapsed holds and status updates in transit don't send SMS; booking details go out once the booking is paid.
            Approval requests are limited to one SMS per approver every {{ \App\Services\SmsNotifier::APPROVAL_SMS_WINDOW_MINUTES }} minutes (later ones are bundled).
            Account and password SMS hide the password in the delivery log.
            Edit wording or switch an event off in
            <a href="{{ route('admin.notifications.templates', ['channel' => 'sms']) }}" class="text-brand-600 hover:underline">Notifications › Templates</a>.
            Today: {{ $smsPolicy['today']['sent'] }} sent · {{ $smsPolicy['today']['failed'] }} failed · {{ $smsPolicy['today']['skipped'] }} skipped —
            <a href="{{ route('admin.notifications.logs', ['channel' => 'sms']) }}" class="text-brand-600 hover:underline">delivery log</a>.
        </p>
    </div>

    {{-- Close of business --}}
    <div class="glass-card rounded-2xl shadow-sm border border-surface-200/60 p-5 xl:col-span-2 space-y-3" x-data="{ preview: null, loading: false }">
        <h4 class="font-bold text-surface-900"><i class="fas fa-store-slash text-brand-600 mr-1"></i> Close of business</h4>

        @php $cutoff = $closure['cutoff']; @endphp
        @if($closure['closed_today'])
            <p class="text-sm text-surface-700">
                Today closed at <strong>{{ $closure['closed_today']->period_end->format('H:i') }}</strong>
                ({{ $closure['closed_today']->trigger === 'last_departure' ? 'after the last departure' : ($closure['closed_today']->trigger === 'manual' ? 'manually' : 'fallback time') }}):
                {{ $closure['closed_today']->sent_count }} of {{ $closure['closed_today']->recipients_count }} delivered.
            </p>
        @else
            <p class="text-sm text-surface-700">
                Today closes at <strong>{{ $cutoff['at']->format('H:i') }}</strong>
                @if($cutoff['trigger'] === 'last_departure')
                    — last departure {{ substr((string) $cutoff['service']->departure_time, 0, 5) }} + {{ $closure['buffer'] }} min.
                @else
                    — no departures today, so the fallback time applies.
                @endif
            </p>
        @endif
        <p class="text-[11px] text-surface-400">
            Buffer {{ $closure['buffer'] }} min · fallback {{ $closure['fallback'] }} —
            <a href="{{ route('admin.settings.index') }}#group-notification" class="text-brand-600 hover:underline">change in General › Notification</a>.
            Each summary covers the time since the previous closure. Needs the scheduler (<code>schedule:work</code> / cron) running.
        </p>

        <div>
            <p class="text-xs font-bold text-surface-600 mb-1">Recipients (users with “Receive close-of-business SMS”)</p>
            <ul class="text-xs text-surface-700 space-y-0.5">
                @forelse($closure['recipients'] as $user)
                    <li><i class="fas fa-user text-surface-300 mr-1"></i>{{ $user->full_name }} <span class="text-surface-400 font-mono">{{ \App\Support\PhoneNumber::mask($user->phone) }}</span></li>
                @empty
                    <li class="text-rose-600">Nobody with the permission has a phone number.</li>
                @endforelse
                @foreach($closure['without_phone'] as $user)
                    <li class="text-amber-700"><i class="fas fa-triangle-exclamation mr-1"></i>{{ $user->full_name }} — no valid phone, won't receive it</li>
                @endforeach
            </ul>
        </div>

        @unless($closure['template_on'])
            <p class="text-xs text-rose-600">The close_of_business SMS template is switched off.</p>
        @endunless

        <div class="flex flex-wrap gap-2">
            <button type="button" class="rounded-xl border border-surface-200 px-3 py-1.5 text-xs font-semibold hover:bg-surface-50"
                    @click="loading = true; fetch(@js(route('admin.settings.integrations.cob.preview')), { headers: { Accept: 'application/json' } }).then(r => r.json()).then(d => preview = d.text || '(template switched off)').finally(() => loading = false)">
                <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-eye'"></i> Preview tonight's SMS
            </button>
            @if($can['update'])
                <form method="POST" action="{{ route('admin.settings.integrations.cob.send-to-me') }}">
                    @csrf
                    <button class="rounded-xl border border-brand-200 text-brand-700 px-3 py-1.5 text-xs font-semibold hover:bg-brand-50"><i class="fas fa-paper-plane"></i> Send test to me</button>
                </form>
            @endif
        </div>
        <template x-if="preview">
            <p class="text-xs rounded-lg bg-surface-50 border border-surface-100 px-3 py-2 text-surface-700" x-text="preview"></p>
        </template>

        @if($closure['recent']->isNotEmpty())
            <div>
                <p class="text-xs font-bold text-surface-600 mb-1">Recent closures</p>
                <table class="w-full text-xs">
                    @foreach($closure['recent'] as $past)
                        <tr class="border-t border-surface-100">
                            <td class="py-1 pr-2">{{ $past->business_date->format('D d M') }}</td>
                            <td class="py-1 pr-2 text-surface-500">{{ $past->period_end->format('H:i') }}</td>
                            <td class="py-1 pr-2 {{ $past->sent_count < $past->recipients_count ? 'text-amber-700' : 'text-emerald-700' }}">{{ $past->sent_count }}/{{ $past->recipients_count }} sent</td>
                            <td class="py-1 text-right">
                                @if($can['update'] && $past->sent_count < $past->recipients_count)
                                    <form method="POST" action="{{ route('admin.settings.integrations.cob.resend', $past) }}">
                                        @csrf
                                        <button class="text-brand-600 hover:underline">Resend</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
    </div>
</div>
