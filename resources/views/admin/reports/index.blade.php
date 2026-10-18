<x-layouts.admin>
    <x-slot:title>Reports</x-slot:title>
    <x-slot:header>Reports</x-slot:header>

    <div class="py-2 space-y-6">
        <div>
            <h1 class="text-2xl font-extrabold text-surface-900 tracking-tight">Cargo reports</h1>
            <p class="text-sm text-surface-600 mt-0.5">Pick a report, set the filters, then view it on screen{{ auth()->user()->hasPermission('cargo-export-report') ? ' or export it to Excel or PDF' : '' }}.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($reports as $key => $report)
                <a href="{{ route('admin.reports.show', $key) }}"
                   class="group bg-white rounded-2xl border border-surface-200 shadow-sm p-5 hover:border-brand-400 hover:shadow-md transition-all flex gap-4">
                    <div class="h-11 w-11 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center shrink-0 group-hover:bg-brand-600 group-hover:text-white transition-colors">
                        <i class="fas {{ $report['icon'] }}"></i>
                    </div>
                    <div class="min-w-0">
                        <h2 class="font-bold text-surface-900 group-hover:text-brand-700">{{ $report['title'] }}</h2>
                        <p class="text-sm text-surface-600 mt-1 leading-snug">{{ $report['description'] }}</p>
                        <span class="inline-flex items-center gap-1 mt-3 text-xs font-semibold text-brand-700">Open report <i class="fas fa-arrow-right text-[10px] group-hover:translate-x-0.5 transition-transform"></i></span>
                    </div>
                </a>
            @endforeach

            @if(auth()->user()->hasPermission('cargo-manifest'))
                <a href="{{ route('admin.cargo.manifest') }}"
                   class="group bg-white rounded-2xl border border-surface-200 shadow-sm p-5 hover:border-brand-400 hover:shadow-md transition-all flex gap-4">
                    <div class="h-11 w-11 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center shrink-0 group-hover:bg-brand-600 group-hover:text-white transition-colors">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="min-w-0">
                        <h2 class="font-bold text-surface-900 group-hover:text-brand-700">Loading manifest</h2>
                        <p class="text-sm text-surface-600 mt-1 leading-snug">The printable cargo list for one departure, with signature lines.</p>
                        <span class="inline-flex items-center gap-1 mt-3 text-xs font-semibold text-brand-700">Open manifest <i class="fas fa-arrow-right text-[10px] group-hover:translate-x-0.5 transition-transform"></i></span>
                    </div>
                </a>
            @endif
        </div>
    </div>
</x-layouts.admin>
