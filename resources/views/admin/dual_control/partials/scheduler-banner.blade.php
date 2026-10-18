{{-- Warns when scheduled changes can't be applied because dual-control:process isn't running --}}
@if (!\App\Services\DualControlService::schedulerAlive())
    @php $waiting = \App\Models\DualControlRequest::where('status', 'scheduled')->count(); @endphp
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 flex items-start gap-3" role="alert">
        <i class="fas fa-triangle-exclamation mt-0.5"></i>
        <div>
            <p class="font-semibold">Scheduled changes aren't being applied — the scheduler hasn't run in the last {{ config('dual_control.heartbeat_minutes', 5) }} minutes.</p>
            <p class="text-xs mt-0.5">Start it on the server with <code class="font-mono">php artisan schedule:work</code> (or the cron entry for <code class="font-mono">schedule:run</code>).
                @if ($waiting) {{ $waiting }} scheduled change{{ $waiting === 1 ? ' is' : 's are' }} waiting. @endif
                Fares and exchange rates are not affected: they carry their own start time.</p>
        </div>
    </div>
@endif
