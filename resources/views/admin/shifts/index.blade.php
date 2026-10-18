<x-layouts.admin>
    <x-slot:title>Shifts</x-slot:title>
    <x-slot:header>Shifts</x-slot:header>

    @php
        $money = fn (array $byCurrency, string $empty = '—') => collect($byCurrency)->map(fn ($v, $c) => $c . ' ' . number_format($v, $c === 'TZS' ? 0 : 2))->implode(' + ') ?: $empty;
        $zero = \App\Models\SystemConfig::getValue('platform_currency', 'TZS') . ' 0';
        $hours = fn ($h) => rtrim(rtrim(number_format($h, 1), '0'), '.');
    @endphp

    {{-- Rules in force --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-surface-600">
            @if($settings['enabled'])
                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">Shifts ON</span>
                Sales officers work {{ $hours($settings['hours']) }} h shifts from when they sign in. After a shift ends, the next can start from
                midnight{{ $settings['rest'] > 0 ? ' and at least ' . $hours($settings['rest']) . ' h later' : '' }}; sooner only with an approved extension.
                Warning {{ $settings['warning'] }} min before the end.
            @else
                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">Shifts OFF</span>
                Clerks can book at any time; no shifts are recorded.
            @endif
            <a href="{{ route('admin.settings.index') }}#group-shifts" class="text-brand-600 hover:underline ml-1">Change in Settings</a>
        </p>
        @if($pending->isNotEmpty())
            <a href="{{ route('admin.dual-control.index') }}" class="rounded-xl bg-amber-50 border border-amber-200 text-amber-800 px-3 py-2 text-xs font-semibold">
                <i class="fas fa-hourglass-half mr-1"></i>{{ $pending->count() }} extension request(s) awaiting approval
            </a>
        @endif
    </div>

    {{-- On shift now --}}
    <h3 class="text-base font-semibold text-surface-900 mb-3">On shift now <span class="text-surface-400 font-normal">({{ $onShift->count() }})</span></h3>
    @if($onShift->isEmpty())
        <div class="glass-card rounded-2xl p-6 text-sm text-surface-500 mb-8">Nobody is on shift.</div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mb-8">
            @foreach($onShift as $s)
                @php
                    $t = $totals[$s->id] ?? ['bookings' => 0, 'collected' => [], 'cash' => []];
                    $left = max(0, (int) now()->diffInMinutes($s->end_time, false));
                    $req = $pending[$s->id] ?? null;
                @endphp
                <div class="glass-card rounded-2xl p-5 shadow-sm border {{ $left <= $settings['warning'] ? 'border-rose-200' : 'border-surface-200/60' }}" x-data="{ extend: false }">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <a href="{{ route('admin.shifts.show', $s) }}" class="font-semibold text-surface-900 hover:text-brand-700">{{ $s->user?->full_name }}</a>
                            <p class="text-xs text-surface-500">{{ $s->start_time->format('H:i') }} → {{ $s->end_time?->format('H:i') }}
                                @if($s->extended_minutes) · <span class="text-brand-700">+{{ $hours($s->extended_minutes / 60) }} h extended</span>@endif</p>
                        </div>
                        <span class="text-sm font-bold font-mono {{ $left <= $settings['warning'] ? 'text-rose-600' : 'text-surface-800' }}">{{ intdiv($left, 60) }}:{{ str_pad($left % 60, 2, '0', STR_PAD_LEFT) }} left</span>
                    </div>
                    <dl class="mt-3 grid grid-cols-3 gap-2 text-xs">
                        <div><dt class="text-surface-400">Bookings</dt><dd class="font-semibold text-surface-800">{{ $t['bookings'] }}</dd></div>
                        <div class="col-span-2"><dt class="text-surface-400">Collected · cash</dt><dd class="font-semibold text-surface-800">{{ $money($t['collected'], $zero) }} · {{ $money($t['cash'], $zero) }}</dd></div>
                    </dl>
                    @if($req)
                        <p class="mt-3 text-xs text-amber-700"><i class="fas fa-hourglass-half mr-1"></i>Extension of {{ $hours($req->payload_after['hours'] ?? 0) }} h awaiting approval
                            (<a href="{{ route('admin.dual-control.show', $req) }}" class="underline">#DC-{{ $req->id }}</a>)</p>
                    @endif
                    @if($canManage)
                        <div class="mt-4 flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('admin.shifts.end', $s) }}"
                                data-confirm="They can't book or take payments until an extension is approved or the rest period passes."
                                data-confirm-title="{{ $s->user?->full_name ? "End {$s->user->full_name}'s shift now?" : 'End this shift now?' }}"
                                data-confirm-button="End shift" data-confirm-cancel="Keep it open" data-confirm-tone="warning" data-confirm-icon="fa-stop">
                                @csrf
                                <button class="rounded-xl border border-rose-200 text-rose-700 px-3 py-1.5 text-xs font-semibold hover:bg-rose-50"><i class="fas fa-stop"></i> End shift</button>
                            </form>
                            @unless($req)
                                <button type="button" @click="extend = !extend" class="rounded-xl border border-surface-200 px-3 py-1.5 text-xs font-semibold hover:bg-surface-50"><i class="fas fa-clock"></i> Extend</button>
                            @endunless
                        </div>
                        <form x-show="extend" x-cloak method="POST" action="{{ route('admin.shifts.extend', $s) }}" class="mt-3 grid grid-cols-3 gap-2">
                            @csrf
                            <input type="number" name="hours" min="0.5" max="12" step="0.5" value="1" required class="rounded-xl border-surface-200 px-3 py-1.5 text-xs" aria-label="Hours">
                            <input type="text" name="reason" maxlength="255" required placeholder="Reason" class="col-span-2 rounded-xl border-surface-200 px-3 py-1.5 text-xs">
                            <button class="col-span-3 rounded-xl bg-brand-600 text-white px-3 py-1.5 text-xs font-semibold hover:bg-brand-700">Submit extension</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- History --}}
    <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
        <h3 class="text-base font-semibold text-surface-900">Shift history</h3>
        <form method="GET" class="flex gap-2 items-center flex-wrap">
            <select name="user_id" class="rounded-xl border-surface-200 bg-white px-3 py-2 text-sm shadow-sm" aria-label="Clerk">
                <option value="">All clerks</option>
                @foreach($clerks as $c)<option value="{{ $c->id }}" @selected(($filters['user_id'] ?? null) == $c->id)>{{ $c->full_name }}</option>@endforeach
            </select>
            <select name="status" class="rounded-xl border-surface-200 bg-white px-3 py-2 text-sm shadow-sm" aria-label="Status">
                <option value="">Any status</option>
                <option value="active" @selected(($filters['status'] ?? null) === 'active')>On shift</option>
                <option value="ended" @selected(($filters['status'] ?? null) === 'ended')>Ended</option>
            </select>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-xl border-surface-200 bg-white px-3 py-2 text-sm shadow-sm" aria-label="From">
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-xl border-surface-200 bg-white px-3 py-2 text-sm shadow-sm" aria-label="To">
            <button type="submit" class="rounded-xl bg-brand-600 text-white px-4 py-2 text-sm font-medium hover:bg-brand-700">Filter</button>
            <a href="{{ route('admin.shifts.export', $filters) }}" class="rounded-xl border border-surface-200 bg-white px-4 py-2 text-sm font-medium hover:bg-surface-50"><i class="fas fa-file-excel text-emerald-600 mr-1"></i>Export</a>
        </form>
    </div>

    <div class="glass-card rounded-2xl shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-surface-200 text-sm">
            <thead class="bg-surface-50/80">
                <tr class="text-left text-xs font-semibold text-surface-600 uppercase">
                    <th class="px-5 py-3">Clerk</th><th class="px-5 py-3">Started</th><th class="px-5 py-3">Ended</th>
                    <th class="px-5 py-3">Worked</th><th class="px-5 py-3">How it ended</th>
                    <th class="px-5 py-3 text-right">Bookings</th><th class="px-5 py-3 text-right">Collected</th><th class="px-5 py-3 text-right">Cash</th><th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @forelse($history as $s)
                    @php $t = $totals[$s->id] ?? ['bookings' => 0, 'collected' => [], 'cash' => []]; @endphp
                    <tr class="hover:bg-surface-50/60">
                        <td class="px-5 py-3 font-medium text-surface-800">{{ $s->user?->full_name }}</td>
                        <td class="px-5 py-3 text-surface-600 whitespace-nowrap">{{ $s->start_time->format('d M Y H:i') }}</td>
                        <td class="px-5 py-3 text-surface-600 whitespace-nowrap">{{ $s->actual_end_time?->format('d M H:i') ?? 'On shift' }}</td>
                        <td class="px-5 py-3 text-surface-600 whitespace-nowrap">{{ $hours($s->start_time->diffInMinutes($s->periodEnd()) / 60) }} h
                            @if($s->extended_minutes)<span class="text-xs text-brand-700">(+{{ $hours($s->extended_minutes / 60) }})</span>@endif</td>
                        <td class="px-5 py-3 text-surface-600">{{ $s->endReasonLabel() }}</td>
                        <td class="px-5 py-3 text-right tabular-nums">{{ $t['bookings'] }}</td>
                        <td class="px-5 py-3 text-right tabular-nums whitespace-nowrap">{{ $money($t['collected']) }}</td>
                        <td class="px-5 py-3 text-right tabular-nums whitespace-nowrap">{{ $money($t['cash']) }}</td>
                        <td class="px-5 py-3 text-right"><a href="{{ route('admin.shifts.show', $s) }}" class="text-brand-600 hover:text-brand-700 font-medium">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-5 py-10 text-center text-surface-500">No shifts match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $history->links() }}</div>
</x-layouts.admin>
