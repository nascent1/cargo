<x-layouts.admin>
    <x-slot:title>Dual Control Rules</x-slot:title>
    <x-slot:header>Dual Control Rules</x-slot:header>

    @php
        $canUpdate = auth()->user()->hasPermission('setting:update');
        $timing = [
            'immediate' => ['Immediate', 'bg-slate-100 text-slate-600', 'Applies when submitted (or when approved).'],
            'scheduled' => ['Can be scheduled', 'bg-sky-50 text-sky-700', 'The maker can choose when it takes effect; the scheduler applies it then.'],
            'dated' => ['Own start date', 'bg-violet-50 text-violet-700', 'The record carries its own start, so approval saves it straight away with that start.'],
        ];
    @endphp

    @include('admin.dual_control.partials.scheduler-banner')

    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            <div>
                <h3 class="text-lg font-bold text-slate-800">Which actions need a second person</h3>
                <p class="text-sm text-slate-500">When a rule is <strong>on</strong>, the action waits for a checker's approval. When it is off, it applies straight away — and is still recorded.
                    <a href="{{ route('admin.dual-control.index') }}" class="text-brand-700 font-medium hover:underline">Open the request inbox</a>.</p>
            </div>
            <form method="GET" class="flex flex-wrap items-center gap-3">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search actions" aria-label="Search actions"
                        class="pl-9 pr-3 py-2 rounded-xl border-slate-300 text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" name="legacy" value="1" @checked($showLegacy) onchange="this.form.submit()" class="rounded border-slate-300 text-brand-600">
                    Show legacy ferry actions
                </label>
                <button type="submit" class="px-3 py-2 rounded-xl border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50">Search</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                        <th class="p-4 font-bold">Action</th>
                        <th class="p-4 font-bold">Timing</th>
                        <th class="p-4 font-bold text-center">Open requests</th>
                        <th class="p-4 font-bold">Last changed</th>
                        <th class="p-4 font-bold">Rule</th>
                        <th class="p-4 font-bold text-right"><span class="sr-only">Toggle</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($groups as $module => $rows)
                        <tr class="bg-slate-100 border-y border-slate-200">
                            <td colspan="6" class="p-3 px-4 font-bold text-slate-700 uppercase tracking-wider text-xs">
                                <i class="fas fa-layer-group mr-2 text-slate-400"></i>{{ $module }}
                                <span class="ml-2 font-medium normal-case tracking-normal text-slate-500">{{ $rows->where('config.requires_dual_control', true)->count() }} of {{ $rows->count() }} need approval</span>
                            </td>
                        </tr>
                        @foreach ($rows as ['config' => $config, 'op' => $op])
                            @php [$tText, $tClass, $tHelp] = $timing[$op['timing']] ?? $timing['immediate']; $last = $lastChanges[$config->id] ?? null; @endphp
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="p-4">
                                    <div class="font-semibold text-slate-800">{{ $op['label'] }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5">{{ $config->description }}</div>
                                    @if ($op['note'])
                                        <div class="text-xs text-brand-700 mt-1"><i class="fas fa-circle-info mr-1"></i>{{ $op['note'] }}</div>
                                    @endif
                                    <div class="text-[11px] text-slate-400 font-mono mt-0.5">{{ $config->operation_type }}</div>
                                </td>
                                <td class="p-4"><span class="inline-flex px-2 py-0.5 rounded-md text-xs font-semibold {{ $tClass }}" title="{{ $tHelp }}">{{ $tText }}</span></td>
                                <td class="p-4 text-center">
                                    @if ($pending[$config->operation_type] ?? 0)
                                        <a href="{{ route('admin.dual-control.index', ['tab' => 'all', 'search' => $config->operation_type]) }}" class="inline-flex px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-xs font-bold">{{ $pending[$config->operation_type] }}</a>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="p-4 text-xs text-slate-500">
                                    @if ($last)
                                        {{ $last->user?->full_name ?? 'System' }}<br>{{ \Carbon\Carbon::parse($last->created_at)->format('d M Y H:i') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="p-4">
                                    @if ($config->requires_dual_control)
                                        <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-md text-xs font-bold"><i class="fas fa-lock mr-1"></i> Needs approval</span>
                                    @else
                                        <span class="px-2 py-1 bg-slate-100 text-slate-600 rounded-md text-xs font-bold"><i class="fas fa-unlock mr-1"></i> Off</span>
                                    @endif
                                </td>
                                <td class="p-4 text-right">
                                    <form action="{{ route('admin.settings.dual-control-configs.toggle', $config) }}" method="POST"
                                        data-confirm-title="{{ $config->requires_dual_control ? 'Stop requiring approval for' : 'Require approval for' }} “{{ $op['label'] }}”?"
                                        data-confirm="{{ $config->requires_dual_control ? 'Changes will apply without a checker’s approval.' : 'Each change will wait for a checker’s approval.' }}"
                                        data-confirm-button="{{ $config->requires_dual_control ? 'Turn off' : 'Turn on' }}"
                                        data-confirm-tone="{{ $config->requires_dual_control ? 'warning' : 'primary' }}" data-confirm-icon="{{ $config->requires_dual_control ? 'fa-lock-open' : 'fa-lock' }}">
                                        @csrf
                                        <button type="submit" @disabled(!$canUpdate)
                                            class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors disabled:opacity-40 disabled:cursor-not-allowed {{ $config->requires_dual_control ? 'bg-rose-50 text-rose-600 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100' }}"
                                            @unless ($canUpdate) title="Needs the setting:update permission" @endunless>
                                            {{ $config->requires_dual_control ? 'Turn off' : 'Turn on' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-slate-500">No actions match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6">
        <h3 class="text-base font-bold text-slate-800">Not part of dual control</h3>
        <p class="text-sm text-slate-500 mb-3">These actions are left out on purpose.</p>
        <dl class="divide-y divide-slate-100 text-sm">
            @foreach ($notEligible as $action => $reason)
                <div class="py-2.5 grid grid-cols-1 sm:grid-cols-3 gap-1">
                    <dt class="font-semibold text-slate-700">{{ $action }}</dt>
                    <dd class="sm:col-span-2 text-slate-600">{{ $reason }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</x-layouts.admin>
