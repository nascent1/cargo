<x-layouts.admin>
    <x-slot:title>Add Schedule</x-slot:title>
    <x-slot:header>Register Schedule</x-slot:header>

    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('admin.schedules.index') }}" class="text-slate-400 hover:text-slate-600 transition-colors">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Add New Schedule</h2>
            <p class="text-slate-500 text-sm">Assign a vessel to a specific trip schedule.</p>
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
        <form action="{{ route('admin.schedules.store') }}" method="POST">
            @csrf
            
            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 mb-1">Select Trip <span class="text-rose-500">*</span></label>
                <select name="trip_id" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Choose a trip...</option>
                    @foreach($trips as $trip)
                        <option value="{{ $trip->id }}" {{ old('trip_id') == $trip->id ? 'selected' : '' }}>
                            {{ $trip->route->name ?? 'Unknown Route' }} 
                            ({{ \Carbon\Carbon::parse($trip->departure_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($trip->arrival_time)->format('H:i') }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 mb-1">Assign Vessel <span class="text-rose-500">*</span></label>
                <select name="ferry_id" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Choose a vessel...</option>
                    @foreach($ferries as $ferry)
                        <option value="{{ $ferry->id }}" {{ old('ferry_id') == $ferry->id ? 'selected' : '' }}>
                            {{ $ferry->name }} (Pax: {{ $ferry->capacity }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Effective Start Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">End Date <span class="text-slate-400 font-normal">(Optional)</span></label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <p class="text-xs text-slate-500 mt-1">Leave blank if the schedule is ongoing indefinitely.</p>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-slate-100 flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl shadow-sm transition-colors flex items-center gap-2">
                    <i class="fas fa-check"></i> Submit Schedule
                </button>
            </div>
        </form>
    </div>
</x-layouts.admin>
