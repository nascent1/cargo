<x-layouts.admin>
    <x-slot:title>Dual Control</x-slot:title>
    <x-slot:header>Dual Control</x-slot:header>

    @php
        $P = \App\Services\DualControl\DualControlPresenter::class;
        $tabs = ['inbox' => ['Awaiting my approval', 'fa-inbox'], 'mine' => ['My requests', 'fa-user-pen'], 'scheduled' => ['Scheduled changes', 'fa-clock'], 'all' => ['All', 'fa-list']];
    @endphp

    @include('admin.dual_control.partials.scheduler-banner')

    <div class="flex flex-wrap items-center gap-2 mb-4" role="tablist">
        @foreach ($tabs as $key => [$text, $icon])
            <a href="{{ route('admin.dual-control.index', ['tab' => $key]) }}" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border transition-colors {{ $tab === $key ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}">
                <i class="fas {{ $icon }} text-xs"></i> {{ $text }}
                @if (isset($counts[$key]) && $counts[$key] > 0)
                    <span class="px-1.5 rounded-full text-[11px] {{ $tab === $key ? 'bg-white/20' : 'bg-slate-100' }}">{{ $counts[$key] }}</span>
                @endif
            </a>
        @endforeach
        <a href="{{ route('admin.settings.dual-control-configs.index') }}" class="ml-auto text-sm font-medium text-brand-700 hover:underline"><i class="fas fa-sliders mr-1"></i>Rules</a>
    </div>

    <form method="GET" class="flex flex-wrap gap-3 items-center mb-4">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="relative flex-1 min-w-[200px] max-w-md">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by action or #DC number"
                class="w-full pl-11 pr-4 py-2.5 rounded-xl border-slate-300 bg-white shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
        </div>
        <select name="module" aria-label="Module" class="rounded-xl border-slate-300 bg-white px-4 py-2.5 text-sm shadow-sm" onchange="this.form.submit()">
            <option value="">All modules</option>
            @foreach ($modules as $module)
                <option value="{{ $module }}" @selected(request('module') === $module)>{{ $module }}</option>
            @endforeach
        </select>
        @if ($tab !== 'scheduled' && $tab !== 'inbox')
            <select name="status" aria-label="Status" class="rounded-xl border-slate-300 bg-white px-4 py-2.5 text-sm shadow-sm" onchange="this.form.submit()">
                <option value="">Any status</option>
                @foreach (\App\Models\DualControlRequest::STATUS_LABELS as $value => $text)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $text }}</option>
                @endforeach
            </select>
        @endif
        <button type="submit" class="rounded-xl bg-white border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Filter</button>
    </form>

    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                        <th class="p-4 font-bold">Request</th>
                        <th class="p-4 font-bold">Record</th>
                        <th class="p-4 font-bold">Maker</th>
                        <th class="p-4 font-bold">Takes effect</th>
                        <th class="p-4 font-bold">Status</th>
                        <th class="p-4 font-bold">Submitted</th>
                        <th class="p-4 font-bold text-right"><span class="sr-only">Open</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($requests as $req)
                        @php $op = $req->operation(); @endphp
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-4">
                                <div class="font-semibold text-slate-800">{{ $op['label'] }}</div>
                                <div class="text-xs text-slate-500">#DC-{{ $req->id }} · {{ $op['module'] }}</div>
                            </td>
                            <td class="p-4 text-sm text-slate-700">{{ $P::subject($req) }}</td>
                            <td class="p-4 text-sm text-slate-700">{{ $req->maker?->full_name ?? '—' }}</td>
                            <td class="p-4 text-sm text-slate-700 whitespace-nowrap">
                                @if ($req->commence_at)
                                    {{ $req->commence_at->format('d M Y H:i') }}
                                    @if ($req->commencedLate())
                                        <div class="text-[11px] font-semibold text-amber-700">Applied late {{ $req->executed_at->format('d M H:i') }}</div>
                                    @endif
                                @else
                                    <span class="text-slate-500">{{ $req->status === 'pending' ? 'When approved' : ($req->executed_at?->format('d M Y H:i') ?? '—') }}</span>
                                @endif
                            </td>
                            <td class="p-4">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold border {{ $P::statusClasses($req) }}">{{ $req->statusLabel() }}</span>
                            </td>
                            <td class="p-4 text-sm text-slate-500 whitespace-nowrap" title="{{ $req->created_at->format('d M Y H:i') }}">{{ $req->created_at->diffForHumans() }}</td>
                            <td class="p-4 text-right">
                                @if (auth()->user()->hasPermission('dc:read'))
                                    <a href="{{ route('admin.dual-control.show', $req) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-brand-50 text-brand-700 hover:bg-brand-100 transition-colors" aria-label="Open request #DC-{{ $req->id }}">
                                        <i class="fas fa-arrow-right text-sm"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-10 text-center text-slate-500">
                                <i class="fas fa-inbox text-4xl mb-3 text-slate-300 block"></i>
                                {{ ['inbox' => 'Nothing is waiting for your approval.', 'mine' => 'You have no requests.', 'scheduled' => 'No changes are scheduled.', 'all' => 'No requests match.'][$tab] }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($requests->hasPages())
            <div class="p-4 border-t border-slate-100">{{ $requests->links() }}</div>
        @endif
    </div>
</x-layouts.admin>
