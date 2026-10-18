<x-layouts.admin>
    <x-slot:title>Edit Route</x-slot:title>
    <x-slot:header>Register Route</x-slot:header>

    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('admin.cargo.routes.index') }}" class="text-slate-400 hover:text-slate-600 transition-colors">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Edit Route</h2>
            <p class="text-slate-500 text-sm">Update the details of this sailing path.</p>
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
        <form action="{{ route('admin.cargo.routes.update', $route) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-end relative">
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 block">Origin Station <span class="text-rose-500">*</span></label>
                    <select id="origin_station_id" name="origin_station_id" required class="w-full rounded-xl border-slate-200 bg-slate-50 hover:bg-white focus:bg-white px-4 py-3.5 text-sm font-medium text-slate-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 transition-colors">
                        <option value="" data-code="">Select Origin...</option>
                        @foreach($stations as $port)
                            <option value="{{ $port->id }}" data-code="{{ $port->code }}" {{ old('origin_station_id', $route->origin_port_id) == $port->id ? 'selected' : '' }}>
                                {{ $port->name }} ({{ $port->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="hidden md:flex absolute left-1/2 top-1/2 -translate-x-1/2 translate-y-2 items-center justify-center w-8 h-8 rounded-full bg-slate-100 border border-slate-200 z-10">
                    <i class="fas fa-arrow-right text-slate-400 text-xs"></i>
                </div>

                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 block">Destination Station <span class="text-rose-500">*</span></label>
                    <select id="destination_station_id" name="destination_station_id" required class="w-full rounded-xl border-slate-200 bg-slate-50 hover:bg-white focus:bg-white px-4 py-3.5 text-sm font-medium text-slate-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 transition-colors">
                        <option value="" data-code="">Select Destination...</option>
                        @foreach($stations as $port)
                            <option value="{{ $port->id }}" data-code="{{ $port->code }}" {{ old('destination_station_id', $route->destination_port_id) == $port->id ? 'selected' : '' }}>
                                {{ $port->name }} ({{ $port->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-6">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 block">Distance (Nautical Miles)</label>
                <input type="number" step="0.1" name="distance_nm" value="{{ old('distance_nm', $route->distance_nm) }}" class="w-1/2 rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g. 45.5">
            </div>

            <x-dual-control.takes-effect op="route_update" class="mt-6" />

            <div class="mt-8 pt-6 border-t border-slate-100 flex justify-end gap-3">
                <a href="{{ route('admin.cargo.routes.index') }}" class="px-6 py-2.5 bg-slate-50 border border-slate-200 hover:bg-slate-100 text-slate-600 font-medium rounded-xl transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-600 text-white font-medium rounded-xl shadow-sm transition-colors flex items-center gap-2">
                    <i class="fas fa-check"></i> Submit Update
                </button>
            </div>
        </form>
    </div>
</x-layouts.admin>
