<x-layouts.admin>
    <x-slot:title>Audit Log #{{ $auditLog->id }}</x-slot:title>
    <x-slot:header>Audit Log Detail</x-slot:header>

    <div class="max-w-3xl space-y-6">
        {{-- Overview --}}
        <div class="glass-card rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-surface-900"><i class="fas fa-shield-alt text-brand-500 mr-2"></i> Entry #{{ $auditLog->sequence_number ?? $auditLog->id }}</h3>
                <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ match($auditLog->action) { 'create' => 'bg-emerald-100 text-emerald-700', 'update' => 'bg-blue-100 text-blue-700', 'delete' => 'bg-red-100 text-red-700', default => 'bg-surface-200 text-surface-600' } }}">
                    {{ ucfirst($auditLog->action) }}
                </span>
            </div>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-surface-500">Action</dt><dd class="font-medium text-surface-900">{{ ucfirst($auditLog->action) }}</dd></div>
                <div><dt class="text-surface-500">Entity</dt><dd class="font-medium text-surface-900">{{ $auditLog->entity_type }} #{{ $auditLog->entity_id }}</dd></div>
                <div><dt class="text-surface-500">User</dt><dd class="font-medium text-surface-900">{{ $auditLog->user?->full_name ?? 'System' }}</dd></div>
                <div><dt class="text-surface-500">Timestamp</dt><dd class="font-medium text-surface-900">{{ $auditLog->created_at?->format('d M Y H:i:s') }}</dd></div>
                <div><dt class="text-surface-500">IP Address</dt><dd class="font-medium text-surface-900 font-mono text-xs">{{ $auditLog->ip_address ?? '—' }}</dd></div>
                <div>
                    <dt class="text-surface-500">Hash Valid</dt>
                    <dd class="font-medium">
                        @if($auditLog->hash)
                            <span class="text-xs font-mono text-surface-400">{{ substr($auditLog->hash, 0, 16) }}…</span>
                        @else
                            <span class="text-surface-400">—</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Before / After State --}}
        <div class="glass-card rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-surface-50/80 border-b border-surface-200/60">
                <h3 class="text-sm font-semibold text-surface-900"><i class="fas fa-code text-brand-500 mr-2"></i> State Changes</h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h4 class="text-xs font-semibold text-red-500 uppercase mb-2"><i class="fas fa-minus-circle mr-1"></i> Before</h4>
                    <pre class="bg-red-50/50 rounded-xl p-4 text-xs text-surface-700 overflow-auto max-h-80 font-mono">{{ $auditLog->before_state ? json_encode($auditLog->before_state, JSON_PRETTY_PRINT) : '(empty — new entity)' }}</pre>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-emerald-600 uppercase mb-2"><i class="fas fa-plus-circle mr-1"></i> After</h4>
                    <pre class="bg-emerald-50/50 rounded-xl p-4 text-xs text-surface-700 overflow-auto max-h-80 font-mono">{{ $auditLog->after_state ? json_encode($auditLog->after_state, JSON_PRETTY_PRINT) : '(empty — deleted entity)' }}</pre>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-6"><a href="{{ route('admin.audit.index') }}" class="text-sm text-surface-500 hover:text-surface-700"><i class="fas fa-arrow-left mr-1"></i> Back to audit logs</a></div>
</x-layouts.admin>
