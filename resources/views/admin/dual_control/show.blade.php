<x-layouts.admin>
    <x-slot:title>Review Authorization</x-slot:title>
    <x-slot:header>Review Authorization</x-slot:header>

    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('admin.dual-control.index') }}" class="text-slate-400 hover:text-slate-600 transition-colors">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-800">{{ Str::headline($dualControlRequest->operation_type) }}</h2>
            <p class="text-slate-500 text-sm">Requested by {{ $dualControlRequest->maker->full_name ?? $dualControlRequest->maker->email }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">Payload Data</h3>
                
                <div class="space-y-4">
                    <div>
                        <h4 class="text-xs font-bold text-slate-400 uppercase mb-2">Before</h4>
                        <pre class="bg-slate-50 p-4 rounded-xl text-sm text-slate-700 overflow-x-auto border border-slate-200">@json($dualControlRequest->payload_before, JSON_PRETTY_PRINT)</pre>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-400 uppercase mb-2">After / Requested</h4>
                        <pre class="bg-slate-50 p-4 rounded-xl text-sm text-slate-700 overflow-x-auto border border-slate-200">@json($dualControlRequest->payload_after, JSON_PRETTY_PRINT)</pre>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6 sticky top-24">
                <h3 class="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">Action</h3>

                @if($dualControlRequest->maker_id === auth()->id())
                    <div class="p-4 bg-amber-50 text-amber-700 rounded-xl mb-4 text-sm font-medium">
                        <i class="fas fa-info-circle mr-2"></i> You cannot approve or reject your own request.
                    </div>
                @else
                    @php $canApproveDC = auth()->user()->hasPermission('dc:approve'); @endphp
                    <form action="{{ route('admin.dual-control.approve', $dualControlRequest) }}" method="POST" class="mb-4">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Approval Comment (Optional)</label>
                            <textarea name="comment" rows="2" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" {{ !$canApproveDC ? 'disabled' : '' }}></textarea>
                        </div>
                        @if($canApproveDC)
                        <button type="submit" class="w-full px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl shadow-sm transition-colors flex justify-center items-center gap-2">
                            <i class="fas fa-check"></i> Approve Request
                        </button>
                        @else
                        <button type="button" disabled class="w-full px-4 py-2.5 bg-emerald-600/50 text-white/70 font-medium rounded-xl shadow-sm cursor-not-allowed flex justify-center items-center gap-2" title="Approve Request (No Permission)">
                            <i class="fas fa-check"></i> Approve Request
                        </button>
                        @endif
                    </form>

                    <form action="{{ route('admin.dual-control.reject', $dualControlRequest) }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Rejection Reason <span class="text-rose-500">*</span></label>
                            <textarea name="comment" rows="2" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-rose-500 focus:ring-rose-500" {{ !$canApproveDC ? 'disabled' : '' }}></textarea>
                        </div>
                        @if($canApproveDC)
                        <button type="submit" class="w-full px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-medium rounded-xl shadow-sm transition-colors flex justify-center items-center gap-2">
                            <i class="fas fa-times"></i> Reject Request
                        </button>
                        @else
                        <button type="button" disabled class="w-full px-4 py-2.5 bg-rose-600/50 text-white/70 font-medium rounded-xl shadow-sm cursor-not-allowed flex justify-center items-center gap-2" title="Reject Request (No Permission)">
                            <i class="fas fa-times"></i> Reject Request
                        </button>
                        @endif
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-layouts.admin>
