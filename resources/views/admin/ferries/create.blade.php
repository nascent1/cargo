<x-layouts.admin>
    <x-slot:title>Register New Vessel</x-slot:title>
    <x-slot:header>Register Vessel</x-slot:header>

    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('admin.ferries.index') }}" class="text-slate-400 hover:text-slate-600 transition-colors">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Vessel Registration Wizard</h2>
            <p class="text-slate-500 text-sm">Add a new ferry to the fleet and configure its seating layout.</p>
        </div>
    </div>

    @livewire('admin.ferry-wizard')
</x-layouts.admin>
