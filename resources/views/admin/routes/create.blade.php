<x-layouts.admin>
    <x-slot:title>Add Route</x-slot:title>
    <x-slot:header>Register Route</x-slot:header>

    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('admin.routes.index') }}" class="text-slate-400 hover:text-slate-600 transition-colors">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Add New Route</h2>
            <p class="text-slate-500 text-sm">Create a new sailing path between two ports.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-4 p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-8 max-w-3xl">
        <form action="{{ route('admin.routes.store') }}" method="POST">
            @csrf
            


            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-end relative">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Origin Port <span class="text-rose-500">*</span></label>
                    <select id="origin_port_id" name="origin_port_id" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="" data-code="">Select Origin...</option>
                        @foreach($ports as $port)
                            <option value="{{ $port->id }}" data-code="{{ $port->code }}" {{ old('origin_port_id') == $port->id ? 'selected' : '' }}>
                                {{ $port->name }} ({{ $port->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="hidden md:flex absolute left-1/2 top-1/2 -translate-x-1/2 translate-y-2 items-center justify-center w-8 h-8 rounded-full bg-slate-100 border border-slate-200 z-10">
                    <i class="fas fa-arrow-right text-slate-400 text-xs"></i>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Destination Port <span class="text-rose-500">*</span></label>
                    <select id="destination_port_id" name="destination_port_id" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="" data-code="">Select Destination...</option>
                        @foreach($ports as $port)
                            <option value="{{ $port->id }}" data-code="{{ $port->code }}" {{ old('destination_port_id') == $port->id ? 'selected' : '' }}>
                                {{ $port->name }} ({{ $port->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-6">
                <label class="block text-sm font-semibold text-slate-700 mb-1">Distance (Nautical Miles)</label>
                <input type="number" step="0.1" name="distance_nm" value="{{ old('distance_nm') }}" class="w-1/2 rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g. 45.5">
            </div>

            <div class="mt-8 pt-6 border-t border-slate-100 flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl shadow-sm transition-colors flex items-center gap-2">
                    <i class="fas fa-check"></i> Submit Route
                </button>
            </div>
        </form>
    </div>


</x-layouts.admin>
