<x-layouts.admin>
    <x-slot:title>Seat Layout Editor</x-slot:title>
    <x-slot:header>Vessel Layout Studio</x-slot:header>

    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.ferries.show', $ferry->id) }}" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Layout Studio: {{ $ferry->name }}</h2>
                <p class="text-slate-500 text-sm">Design, modify, and publish seating zones for this vessel.</p>
            </div>
        </div>
    </div>

    <!-- Vue App Mount Point -->
    <div id="layout-editor-app" 
         data-ferry="{{ json_encode($ferry) }}" 
         data-layout="{{ json_encode($ferry->seat_layout_json) }}"
         data-save-url="{{ route('admin.ferries.update-layout', $ferry->id) }}">
    </div>

    @push('scripts')
        @vite('resources/js/admin/editor.js')
    @endpush
</x-layouts.admin>
