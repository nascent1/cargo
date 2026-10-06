<x-layouts.admin>
    <x-slot:title>Edit Vessel</x-slot:title>
    <x-slot:header>Fleet Management</x-slot:header>

    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('admin.ferries.index') }}" class="text-slate-400 hover:text-slate-600 transition-colors">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Edit Vessel: {{ $ferry->name }}</h2>
            <p class="text-slate-500 text-sm">Update basic information for this ferry.</p>
        </div>
    </div>

    @livewire('admin.ferry-wizard', ['ferry' => $ferry])
</x-layouts.admin>
