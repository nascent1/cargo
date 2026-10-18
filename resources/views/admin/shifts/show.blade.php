<x-layouts.admin>
    <x-slot:title>Shift #{{ $shift->id }}</x-slot:title>
    <x-slot:header>Shift — {{ $shift->user?->full_name }}</x-slot:header>

    @php
        $money = fn (array $byCurrency) => collect($byCurrency)->map(fn ($v, $c) => $c . ' ' . number_format($v, $c === 'TZS' ? 0 : 2))->implode(' + ') ?: (\App\Models\SystemConfig::getValue('platform_currency', 'TZS') . ' 0');
        $hours = fn ($h) => rtrim(rtrim(number_format($h, 1), '0'), '.');
        $active = $shift->isActive();
    @endphp

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 print:hidden">
        <a href="{{ route('admin.shifts.index') }}" class="text-sm text-brand-600 hover:underline"><i class="fas fa-arrow-left mr-1"></i>All shifts</a>
        <div class="flex flex-wrap gap-2" x-data="{ extend: false }">
            <button type="button" onclick="window.print()" class="rounded-xl border border-surface-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-surface-50"><i class="fas fa-print"></i> Print handover sheet</button>
            @if($canManage && $active)
                <form method="POST" action="{{ route('admin.shifts.end', $shift) }}"
                    data-confirm="They can't book or take payments until an extension is approved or the rest period passes."
                    data-confirm-title="{{ $shift->user?->full_name ? "End {$shift->user->full_name}'s shift now?" : 'End this shift now?' }}"
                    data-confirm-button="End shift" data-confirm-cancel="Keep it open" data-confirm-tone="warning" data-confirm-icon="fa-stop">
                    @csrf
                    <button class="rounded-xl border border-rose-200 text-rose-700 bg-white px-3 py-2 text-xs font-semibold hover:bg-rose-50"><i class="fas fa-stop"></i> End shift</button>
                </form>
            @endif
            @if($canManage && !$pending)
                <button type="button" @click="extend = !extend" class="rounded-xl border border-surface-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-surface-50"><i class="fas fa-clock"></i> {{ $active ? 'Extend' : 'Reopen' }}</button>
                <form x-show="extend" x-cloak method="POST" action="{{ route('admin.shifts.extend', $shift) }}" class="w-full flex flex-wrap gap-2 justify-end">
                    @csrf
                    <input type="number" name="hours" min="0.5" max="12" step="0.5" value="1" required class="w-24 rounded-xl border-surface-200 px-3 py-2 text-xs" aria-label="Hours">
                    <input type="text" name="reason" maxlength="255" required placeholder="Reason" class="w-64 rounded-xl border-surface-200 px-3 py-2 text-xs">
                    <button class="rounded-xl bg-brand-600 text-white px-3 py-2 text-xs font-semibold hover:bg-brand-700">Submit {{ $active ? 'extension' : 'reopen' }} request</button>
                </form>
            @endif
        </div>
    </div>

    @if($pending)
        <div class="mb-6 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800 print:hidden">
            <i class="fas fa-hourglass-half mr-1"></i>Extension of {{ $hours($pending->payload_after['hours'] ?? 0) }} h awaiting approval —
            <a href="{{ route('admin.dual-control.show', $pending) }}" class="underline">#DC-{{ $pending->id }}</a>.
        </div>
    @endif

    {{-- Shift facts --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        @foreach([
            'Status' => $active ? 'On shift' : $shift->endReasonLabel(),
            'Started' => $shift->start_time->format('d M Y H:i'),
            'Planned end' => $shift->end_time?->format('d M H:i') ?? '—',
            'Ended' => $shift->actual_end_time?->format('d M H:i') ?? '—',
            'Worked' => $hours($shift->start_time->diffInMinutes($shift->periodEnd()) / 60) . ' h' . ($shift->extended_minutes ? ' (+' . $hours($shift->extended_minutes / 60) . ' h extended)' : ''),
        ] as $label => $value)
            <div class="glass-card rounded-2xl p-4 shadow-sm">
                <p class="text-xs text-surface-400">{{ $label }}</p>
                <p class="text-sm font-semibold text-surface-900 mt-1">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    {{-- Handover --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
        <div class="glass-card rounded-2xl p-5 shadow-sm lg:col-span-2">
            <h3 class="font-semibold text-surface-900 mb-3">Collections</h3>
            @if(empty($summary['collections']))
                <p class="text-sm text-surface-500">No payments taken in this shift.</p>
            @else
                <table class="w-full text-sm">
                    <thead class="text-xs text-surface-400 uppercase text-left"><tr><th class="py-2">Channel</th><th class="py-2 text-right">Payments</th><th class="py-2 text-right">Amount</th></tr></thead>
                    <tbody class="divide-y divide-surface-100">
                        @foreach($summary['collections'] as $c)
                            <tr>
                                <td class="py-2">{{ $c['method'] }} @if($c['flow'] === \App\Models\CargoPayment::FLOW_CASH)<span class="text-xs text-emerald-700">(cash)</span>@endif</td>
                                <td class="py-2 text-right tabular-nums">{{ $c['count'] }}</td>
                                <td class="py-2 text-right tabular-nums">{{ $c['currency'] }} {{ number_format($c['total'], $c['currency'] === 'TZS' ? 0 : 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-surface-200 font-semibold"><tr><td class="py-2">Total</td><td></td><td class="py-2 text-right">{{ $money($summary['collected']) }}</td></tr></tfoot>
                </table>
            @endif
        </div>
        <div class="glass-card rounded-2xl p-5 shadow-sm space-y-3">
            <div>
                <p class="text-xs text-surface-400">Cash to hand over</p>
                <p class="text-2xl font-bold text-emerald-700">{{ $money($summary['cash_to_hand_over']) }}</p>
            </div>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-xs text-surface-400">Bookings</dt><dd class="font-semibold">{{ $summary['bookings'] }}</dd></div>
                <div><dt class="text-xs text-surface-400">Weight</dt><dd class="font-semibold">{{ number_format($summary['weight_kg']) }} kg</dd></div>
                <div><dt class="text-xs text-surface-400">Still unpaid</dt><dd class="font-semibold {{ $summary['unpaid']['count'] ? 'text-amber-700' : '' }}">{{ $summary['unpaid']['count'] }} · {{ $money($summary['unpaid']['value']) }}</dd></div>
                <div><dt class="text-xs text-surface-400">Cancelled</dt><dd class="font-semibold">{{ $summary['cancelled'] }}</dd></div>
            </dl>
            <p class="hidden print:block text-xs pt-8">Handed over by ____________________ &nbsp; Received by ____________________</p>
        </div>
    </div>

    {{-- Bookings --}}
    <div class="glass-card rounded-2xl shadow-sm overflow-x-auto mb-6">
        <div class="px-5 py-3 border-b border-surface-100"><h3 class="font-semibold text-surface-900">Bookings made in this shift ({{ $bookings->count() }})</h3></div>
        <table class="min-w-full text-sm">
            <thead class="bg-surface-50/80 text-left text-xs font-semibold text-surface-600 uppercase">
                <tr><th class="px-5 py-2">Time</th><th class="px-5 py-2">Booking</th><th class="px-5 py-2">Sender → Receiver</th><th class="px-5 py-2">Status</th><th class="px-5 py-2">Payment</th><th class="px-5 py-2 text-right">Amount</th></tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @forelse($bookings as $b)
                    <tr>
                        <td class="px-5 py-2 text-surface-500">{{ $b->created_at->format('H:i') }}</td>
                        <td class="px-5 py-2 font-mono"><a href="{{ route('admin.cargo.bookings.show', $b) }}" class="text-brand-700 hover:underline">{{ $b->consignment_number }}</a></td>
                        <td class="px-5 py-2">{{ $b->sender_name }} → {{ $b->receiver_name }}</td>
                        <td class="px-5 py-2">{{ str_replace('_', ' ', $b->status) }}</td>
                        <td class="px-5 py-2">{{ $b->payment_status }}</td>
                        <td class="px-5 py-2 text-right tabular-nums">{{ $b->currency }} {{ number_format((float) $b->net_amount, $b->currency === 'TZS' ? 0 : 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-6 text-center text-surface-500">No bookings in this shift.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Extensions --}}
    <div class="glass-card rounded-2xl shadow-sm overflow-x-auto print:hidden">
        <div class="px-5 py-3 border-b border-surface-100"><h3 class="font-semibold text-surface-900">Extension requests</h3></div>
        <table class="min-w-full text-sm">
            <thead class="bg-surface-50/80 text-left text-xs font-semibold text-surface-600 uppercase">
                <tr><th class="px-5 py-2">Request</th><th class="px-5 py-2">Hours</th><th class="px-5 py-2">Reason</th><th class="px-5 py-2">Asked by</th><th class="px-5 py-2">Status</th><th class="px-5 py-2">Decided by</th></tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @forelse($extensions as $x)
                    <tr>
                        <td class="px-5 py-2"><a href="{{ route('admin.dual-control.show', $x) }}" class="text-brand-700 hover:underline">#DC-{{ $x->id }}</a> <span class="text-xs text-surface-400">{{ $x->created_at->format('d M H:i') }}</span></td>
                        <td class="px-5 py-2">{{ $hours($x->payload_after['hours'] ?? 0) }} h</td>
                        <td class="px-5 py-2 text-surface-600">{{ $x->payload_after['reason'] ?? '—' }}</td>
                        <td class="px-5 py-2">{{ $x->maker?->full_name }}</td>
                        <td class="px-5 py-2">{{ ucfirst($x->status) }}</td>
                        <td class="px-5 py-2">{{ $x->checker_id && $x->checker_id !== $x->maker_id ? $x->checker?->full_name : ($x->status === 'approved' ? 'No approval needed' : '—') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-6 text-center text-surface-500">No extensions requested.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
