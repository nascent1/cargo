<x-layouts.admin>
    <x-slot:title>Add Port</x-slot:title>
    <x-slot:header>Register Port</x-slot:header>

    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('admin.ports.index') }}" class="text-slate-400 hover:text-slate-600 transition-colors">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Add New Port</h2>
            <p class="text-slate-500 text-sm">Register a new arrival/departure destination.</p>
        </div>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-8 max-w-3xl">
        <form action="{{ route('admin.ports.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Port Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g. Zanzibar Port">
                    @error('name') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Port Code <span class="text-rose-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 uppercase" placeholder="e.g. ZNZ" maxlength="10">
                    @error('code') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">City / Region</label>
                    <input type="text" name="city" value="{{ old('city') }}" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g. Zanzibar City">
                    @error('city') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-slate-100 flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl shadow-sm transition-colors flex items-center gap-2">
                    <i class="fas fa-check"></i> Submit Port
                </button>
            </div>
        </form>
    </div>
</x-layouts.admin>
