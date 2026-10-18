<x-layouts.admin>
    <x-slot:title>Notification Delivery Log</x-slot:title>
    <x-slot:header>Communication Settings</x-slot:header>

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Delivery Log</h2>
            <p class="text-slate-500 text-sm">Every SMS the system tried to send — delivered, failed, or skipped by the SMS policy. Login details are never shown.</p>
        </div>
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <select name="channel" class="rounded-xl border-slate-200 bg-white px-3 py-2 text-sm">
                <option value="">All channels</option>
                @foreach(['sms', 'email', 'push', 'in_app'] as $ch)
                    <option value="{{ $ch }}" @selected(request('channel') === $ch)>{{ strtoupper($ch) }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-xl border-slate-200 bg-white px-3 py-2 text-sm">
                <option value="">All statuses</option>
                @foreach(['sent', 'failed', 'skipped', 'pending'] as $st)
                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                @endforeach
            </select>
            <button class="rounded-xl bg-slate-800 text-white px-4 py-2 text-sm font-medium">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200/60">
                    <tr>
                        <th class="px-5 py-3 font-semibold text-slate-700">When</th>
                        <th class="px-5 py-3 font-semibold text-slate-700">Event</th>
                        <th class="px-5 py-3 font-semibold text-slate-700">Recipient</th>
                        <th class="px-5 py-3 font-semibold text-slate-700">Status</th>
                        <th class="px-5 py-3 font-semibold text-slate-700">Provider</th>
                        <th class="px-5 py-3 font-semibold text-slate-700">Message / reason</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/50 align-top">
                            <td class="px-5 py-3 whitespace-nowrap text-slate-500">{{ $log->created_at?->format('d M H:i') }}</td>
                            <td class="px-5 py-3 whitespace-nowrap font-medium text-slate-800">{{ ucwords(str_replace('_', ' ', $log->event)) }}</td>
                            <td class="px-5 py-3 whitespace-nowrap font-mono text-xs">
                                {{ $log->channel === 'sms' ? \App\Support\PhoneNumber::mask($log->recipient) : $log->recipient }}
                                @if($log->user)<div class="font-sans text-slate-400">{{ $log->user->full_name }}</div>@endif
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ match($log->status) { 'sent' => 'bg-emerald-100 text-emerald-700', 'failed' => 'bg-rose-100 text-rose-700', 'skipped' => 'bg-slate-100 text-slate-500', default => 'bg-amber-100 text-amber-700' } }}">{{ ucfirst($log->status) }}</span>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-slate-600">
                                {{ $log->provider ?? '—' }}
                                @if($log->provider_message_id)<div class="font-mono text-[10px] text-slate-400">{{ $log->provider_message_id }}</div>@endif
                            </td>
                            <td class="px-5 py-3 text-slate-600 max-w-xl">
                                <div class="break-words">{{ \Illuminate\Support\Str::limit($log->content, 200) ?: '—' }}</div>
                                @if($log->error_message)<div class="mt-1 text-xs {{ $log->status === 'sent' ? 'text-amber-700' : 'text-rose-600' }}">{{ $log->error_message }}</div>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-slate-400">No notifications logged yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">{{ $logs->links() }}</div>
        @endif
    </div>
</x-layouts.admin>
