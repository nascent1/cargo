<x-layouts.admin>
    <x-slot:title>Request #DC-{{ $dualControlRequest->id }}</x-slot:title>
    <x-slot:header>Review Authorization</x-slot:header>

    @php
        $P = \App\Services\DualControl\DualControlPresenter::class;
        $req = $dualControlRequest;
        $op = $req->operation();
        $user = auth()->user();
        $isMaker = $req->maker_id === $user->id;
        $canApprove = $user->hasPermission('dc:approve');
        $canCancel = in_array($req->status, ['pending', 'scheduled'], true) && ($isMaker || $canApprove);
        $pricing = str_starts_with($req->operation_type, 'cargo_fare_');
        $rows = $pricing ? [] : $P::rows($req);
        $isCreate = !$req->payload_before;
    @endphp

    <div class="mb-6 flex items-start gap-4">
        <a href="{{ route('admin.dual-control.index') }}" class="text-slate-400 hover:text-slate-600 transition-colors mt-1" aria-label="Back to requests">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div class="flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-2xl font-bold text-slate-800">{{ $op['label'] }}</h2>
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold border {{ $P::statusClasses($req) }}">{{ $req->statusLabel() }}</span>
                @if ($req->commencedLate())
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold border bg-amber-50 text-amber-800 border-amber-200">Commenced late</span>
                @endif
            </div>
            <p class="text-slate-500 text-sm mt-0.5">#DC-{{ $req->id }} · {{ $op['module'] }} · {{ $P::subject($req) }} · submitted by {{ $req->maker?->full_name ?? 'unknown' }} on {{ $req->created_at->format('d M Y H:i') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">

            {{-- When --}}
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6">
                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    <div>
                        <dt class="text-xs font-bold text-slate-400 uppercase">Takes effect (requested)</dt>
                        <dd class="mt-1 font-semibold text-slate-800">{{ $req->commence_at?->format('d M Y H:i') ?? 'As soon as approved' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold text-slate-400 uppercase">Applied</dt>
                        <dd class="mt-1 font-semibold {{ $req->commencedLate() ? 'text-amber-700' : 'text-slate-800' }}">{{ $req->executed_at?->format('d M Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold text-slate-400 uppercase">Checker</dt>
                        <dd class="mt-1 text-slate-800">{{ $req->checker?->full_name ?? '—' }}{{ $req->checker_id && $req->checker_id === $req->maker_id ? ' (rule off)' : '' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold text-slate-400 uppercase">{{ $req->status === 'pending' ? 'Expires' : 'Resolved' }}</dt>
                        <dd class="mt-1 text-slate-800">{{ ($req->status === 'pending' ? $req->expires_at : $req->resolved_at)?->format('d M Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>
                @if ($req->status === 'pending' && $req->commence_at?->isPast())
                    <p class="mt-4 rounded-xl bg-amber-50 border border-amber-200 px-4 py-2.5 text-sm text-amber-800"><i class="fas fa-clock mr-1"></i>The requested start has passed. Approving now applies the change immediately and records it as commenced late.</p>
                @endif
                @if ($req->failure_reason)
                    <p class="mt-4 rounded-xl {{ $req->status === 'failed' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-slate-50 border-slate-200 text-slate-700' }} border px-4 py-2.5 text-sm"><i class="fas fa-circle-exclamation mr-1"></i>{{ $req->failure_reason }}</p>
                @endif
                @if ($req->checker_comment && $req->checker_id !== $req->maker_id)
                    <p class="mt-4 text-sm text-slate-600"><span class="font-semibold text-slate-700">Checker's comment:</span> {{ $req->checker_comment }}</p>
                @endif
            </div>

            {{-- What --}}
            @if ($pricing)
                @includeIf('admin.dual_control.partials.cargo-pricing', ['req' => $req])
            @endif

            @if (!$pricing)
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
                <h3 class="text-lg font-bold text-slate-800 px-6 pt-5 pb-3">{{ $isCreate ? 'Details' : 'Changes' }}</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-y border-slate-100 text-xs uppercase tracking-wider text-slate-500">
                                <th class="text-left px-6 py-2.5 font-bold">Field</th>
                                @unless ($isCreate)<th class="text-left px-4 py-2.5 font-bold">Before</th>@endunless
                                <th class="text-left px-4 py-2.5 font-bold">{{ $isCreate ? 'Value' : 'After' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($rows as $row)
                                <tr class="{{ !$isCreate && $row['changed'] ? 'bg-amber-50/50' : '' }}">
                                    <td class="px-6 py-2.5 font-medium text-slate-700">{{ $row['label'] }}
                                        @if (!$isCreate && $row['changed'])<span class="ml-1 text-[10px] font-bold uppercase text-amber-700">changed</span>@endif
                                    </td>
                                    @unless ($isCreate)<td class="px-4 py-2.5 text-slate-500 break-all">{{ $row['before'] }}</td>@endunless
                                    <td class="px-4 py-2.5 break-all {{ !$isCreate && $row['changed'] ? 'font-semibold text-slate-900' : 'text-slate-700' }}">{{ $row['after'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-6 py-6 text-center text-slate-500">No field details recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <details class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6 text-sm">
                <summary class="cursor-pointer font-semibold text-slate-600">Technical details (stored payload)</summary>
                <div class="mt-4 space-y-3">
                    <p class="text-xs text-slate-500">Operation <code class="font-mono">{{ $req->operation_type }}</code> · subject <code class="font-mono">{{ $req->subject_key ?? '—' }}</code>. Secrets are stored encrypted and never shown.</p>
                    <pre class="bg-slate-50 p-4 rounded-xl text-xs text-slate-700 overflow-x-auto border border-slate-200">{{ json_encode(['before' => $req->payload_before, 'after' => collect($req->payload_after)->map(fn ($v) => \App\Services\DualControlService::isSealed($v) ? '[sealed]' : $v)], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </details>
        </div>

        <div>
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6 sticky top-24 space-y-4">
                <h3 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-2">Action</h3>

                @if ($req->status === 'pending')
                    @if ($isMaker)
                        <div class="p-4 bg-amber-50 text-amber-800 rounded-xl text-sm font-medium">
                            <i class="fas fa-info-circle mr-2"></i>You submitted this request, so another person must approve or reject it.
                        </div>
                    @elseif ($req->isExpired())
                        <div class="p-4 bg-slate-50 text-slate-700 rounded-xl text-sm">This request has expired and can no longer be approved.</div>
                    @elseif ($canApprove)
                        <form action="{{ route('admin.dual-control.approve', $req) }}" method="POST">
                            @csrf
                            <label for="approve-comment" class="block text-sm font-semibold text-slate-700 mb-1">Comment (optional)</label>
                            <textarea id="approve-comment" name="comment" rows="2" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 mb-3"></textarea>
                            <button type="submit" class="w-full px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl shadow-sm transition-colors flex justify-center items-center gap-2">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <p class="mt-2 text-xs text-slate-500">
                                @if ($req->commence_at?->isFuture())
                                    Takes effect {{ $req->commence_at->format('d M Y H:i') }}.
                                @else
                                    Applies as soon as you approve.
                                @endif
                            </p>
                        </form>
                        <form action="{{ route('admin.dual-control.reject', $req) }}" method="POST" class="pt-4 border-t border-slate-100">
                            @csrf
                            <label for="reject-comment" class="block text-sm font-semibold text-slate-700 mb-1">Rejection reason <span class="text-rose-500">*</span></label>
                            <textarea id="reject-comment" name="comment" rows="2" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 mb-3"></textarea>
                            <button type="submit" class="w-full px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-medium rounded-xl shadow-sm transition-colors flex justify-center items-center gap-2">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </form>
                    @else
                        <div class="p-4 bg-slate-50 text-slate-700 rounded-xl text-sm">You don't have permission to approve requests.</div>
                    @endif
                @else
                    <p class="text-sm text-slate-600">This request is <strong>{{ strtolower($req->statusLabel()) }}</strong>.
                        @if ($req->status === 'scheduled') It applies automatically on {{ $req->commence_at->format('d M Y H:i') }}. @endif
                    </p>
                @endif

                @if ($canCancel)
                    <form action="{{ route('admin.dual-control.cancel', $req) }}" method="POST" class="pt-4 border-t border-slate-100"
                        data-confirm="Nothing will be applied."
                        data-confirm-title="Cancel {{ $req->status === 'scheduled' ? 'scheduled change' : 'request' }} #DC-{{ $req->id }}?"
                        data-confirm-button="Cancel {{ $req->status === 'scheduled' ? 'change' : 'request' }}" data-confirm-cancel="Keep it"
                        data-confirm-tone="danger" data-confirm-icon="fa-ban">
                        @csrf
                        <label for="cancel-reason" class="block text-sm font-semibold text-slate-700 mb-1">Cancel this {{ $req->status === 'scheduled' ? 'scheduled change' : 'request' }}</label>
                        <input id="cancel-reason" name="reason" type="text" maxlength="500" placeholder="Reason (optional)" class="w-full rounded-xl border-slate-300 shadow-sm text-sm mb-3">
                        <button type="submit" class="w-full px-4 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium rounded-xl transition-colors">
                            <i class="fas fa-ban mr-1"></i> Cancel request
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-layouts.admin>
