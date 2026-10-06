<x-layouts.admin>
    <x-slot:title>Dual Control — {{ ucfirst(str_replace('_', ' ', $dualControlRequest->operation_type)) }}</x-slot:title>
    <x-slot:header>Review Request</x-slot:header>

    <div class="max-w-3xl space-y-6">
        {{-- Request Info --}}
        <div class="glass-card rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-surface-900"><i class="fas fa-check-double text-brand-500 mr-2"></i> Request #{{ $dualControlRequest->id }}</h3>
                <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ match($dualControlRequest->status) { 'pending' => 'bg-amber-100 text-amber-700', 'approved' => 'bg-emerald-100 text-emerald-700', 'rejected' => 'bg-red-100 text-red-700', default => 'bg-surface-200 text-surface-600' } }}">
                    {{ ucfirst($dualControlRequest->status) }}{{ $dualControlRequest->isExpired() ? ' (EXPIRED)' : '' }}
                </span>
            </div>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-surface-500">Operation</dt><dd class="font-medium text-surface-900">{{ ucfirst(str_replace('_', ' ', $dualControlRequest->operation_type)) }}</dd></div>
                <div><dt class="text-surface-500">Resource</dt><dd class="font-medium text-surface-900">{{ $dualControlRequest->resource_type }} #{{ $dualControlRequest->resource_id }}</dd></div>
                <div><dt class="text-surface-500">Maker</dt><dd class="font-medium text-surface-900">{{ $dualControlRequest->maker?->full_name ?? '—' }}</dd></div>
                <div><dt class="text-surface-500">Submitted</dt><dd class="font-medium text-surface-900">{{ $dualControlRequest->created_at?->format('d M Y H:i') }}</dd></div>
                @if($dualControlRequest->checker)
                    <div><dt class="text-surface-500">Checker</dt><dd class="font-medium text-surface-900">{{ $dualControlRequest->checker->full_name }}</dd></div>
                    <div><dt class="text-surface-500">Resolved</dt><dd class="font-medium text-surface-900">{{ $dualControlRequest->resolved_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                @endif
                @if($dualControlRequest->expires_at)
                    <div><dt class="text-surface-500">Expires</dt><dd class="font-medium {{ $dualControlRequest->isExpired() ? 'text-red-600' : 'text-surface-900' }}">{{ $dualControlRequest->expires_at->format('d M Y H:i') }}</dd></div>
                @endif
            </dl>
        </div>

        {{-- Payload Before/After --}}
        <div class="glass-card rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-surface-50/80 border-b border-surface-200/60">
                <h3 class="text-sm font-semibold text-surface-900"><i class="fas fa-code text-brand-500 mr-2"></i> Payload</h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h4 class="text-xs font-semibold text-surface-500 uppercase mb-2">Before</h4>
                    <pre class="bg-surface-50 rounded-xl p-4 text-xs text-surface-700 overflow-auto max-h-64 font-mono">{{ $dualControlRequest->payload_before ? json_encode($dualControlRequest->payload_before, JSON_PRETTY_PRINT) : '(empty)' }}</pre>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-surface-500 uppercase mb-2">After</h4>
                    <pre class="bg-surface-50 rounded-xl p-4 text-xs text-surface-700 overflow-auto max-h-64 font-mono">{{ $dualControlRequest->payload_after ? json_encode($dualControlRequest->payload_after, JSON_PRETTY_PRINT) : '(empty)' }}</pre>
                </div>
            </div>
        </div>

        {{-- Checker Comment --}}
        @if($dualControlRequest->checker_comment)
            <div class="glass-card rounded-2xl p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-surface-900 mb-2"><i class="fas fa-comment text-brand-500 mr-2"></i> Checker Comment</h3>
                <p class="text-sm text-surface-700">{{ $dualControlRequest->checker_comment }}</p>
            </div>
        @endif

        {{-- Actions (only for pending, non-expired, and current user ≠ maker) --}}
        @if($dualControlRequest->status === 'pending' && !$dualControlRequest->isExpired() && auth()->id() !== $dualControlRequest->maker_id && auth()->user()->hasPermission('dc:approve'))
            <div class="glass-card rounded-2xl p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-surface-900 mb-4"><i class="fas fa-gavel text-brand-500 mr-2"></i> Your Decision</h3>
                <div class="mb-4">
                    <label class="block text-xs font-medium text-surface-700 mb-1.5">Comment</label>
                    <textarea id="dc-comment" rows="2" placeholder="Optional for approval, required for rejection"
                        class="w-full rounded-xl border-0 py-2.5 px-4 text-sm ring-1 ring-inset ring-surface-200 focus:ring-2 focus:ring-brand-500 bg-white shadow-sm"></textarea>
                </div>
                <div class="flex gap-3">
                    <form method="POST" action="{{ route('dual-control.approve', $dualControlRequest) }}" id="approve-form">
                        @csrf
                        <input type="hidden" name="comment" id="approve-comment">
                        <button type="submit" onclick="document.getElementById('approve-comment').value=document.getElementById('dc-comment').value"
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 text-white px-6 py-2.5 text-sm font-medium hover:bg-emerald-700 transition">
                            <i class="fas fa-check text-xs"></i> Approve
                        </button>
                    </form>
                    <form method="POST" action="{{ route('dual-control.reject', $dualControlRequest) }}" id="reject-form">
                        @csrf
                        <input type="hidden" name="comment" id="reject-comment">
                        <button type="submit" onclick="document.getElementById('reject-comment').value=document.getElementById('dc-comment').value"
                            class="inline-flex items-center gap-2 rounded-xl bg-red-600 text-white px-6 py-2.5 text-sm font-medium hover:bg-red-700 transition">
                            <i class="fas fa-times text-xs"></i> Reject
                        </button>
                    </form>
                </div>
            </div>
        @elseif($dualControlRequest->status === 'pending' && auth()->id() === $dualControlRequest->maker_id)
            <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-700">
                <i class="fas fa-info-circle mr-1"></i> You are the maker of this request. A different user must approve or reject it.
            </div>
        @endif
    </div>
    <div class="mt-6"><a href="{{ route('dual-control.index') }}" class="text-sm text-surface-500 hover:text-surface-700"><i class="fas fa-arrow-left mr-1"></i> Back to queue</a></div>
</x-layouts.admin>
