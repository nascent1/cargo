<x-layouts.admin>
    <x-slot:title>Departure Capacity</x-slot:title>
    <x-slot:header>Schedules & Journeys</x-slot:header>

    <div class="mb-6">
        <a href="{{ route('admin.cargo.journeys.availability-index', ['date' => \Carbon\Carbon::parse($journey->departure_date)->toDateString()]) }}" class="text-sm font-bold text-slate-500 hover:text-brand-600 inline-flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Departures
        </a>
        <h2 class="text-2xl font-bold text-slate-800 mt-2">{{ $journey->route?->name }} — {{ \Carbon\Carbon::parse($journey->departure_date)->format('D j M Y') }} {{ \Carbon\Carbon::parse($journey->departure_time)->format('H:i') }}</h2>
        <p class="text-slate-500 text-sm">{{ $journey->transportUnit?->name }} · {{ \Illuminate\Support\Str::headline($journey->status) }}
            <x-dual-control.pending-badge subject="transport_service:{{ $journey->id }}:capacity" class="ml-1" /></p>
    </div>

    @if (!$inventory)
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"><i class="fas fa-circle-info mr-1"></i>This departure has no cargo capacity yet. The vessel's standard capacity is filled in below; saving sets it for this departure.</div>
    @endif

    <form method="POST" action="{{ route('admin.cargo.journeys.availability.update', $journey->id) }}" class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
        @csrf
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                        <th class="text-left px-6 py-3">Space</th>
                        <th class="text-right px-4 py-3">Booked</th>
                        <th class="text-right px-4 py-3">Held</th>
                        <th class="text-right px-4 py-3">Free now</th>
                        <th class="text-right px-4 py-3">Vessel standard</th>
                        <th class="text-left px-6 py-3 w-56">Capacity for this departure</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($dimensions as $d)
                        @php $taken = $d['used'] + $d['held']; @endphp
                        <tr>
                            <td class="px-6 py-4 font-semibold text-slate-800">{{ $d['label'] }}</td>
                            <td class="px-4 py-4 text-right tabular-nums text-slate-700">{{ number_format($d['used'], 2) }}</td>
                            <td class="px-4 py-4 text-right tabular-nums text-slate-700">{{ number_format($d['held'], 2) }}</td>
                            <td class="px-4 py-4 text-right tabular-nums font-semibold {{ $d['total'] - $taken <= 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ number_format(max(0, $d['total'] - $taken), 2) }}</td>
                            <td class="px-4 py-4 text-right tabular-nums text-slate-500">{{ number_format($d['standard'], 2) }}</td>
                            <td class="px-6 py-3">
                                <div class="relative">
                                    <input type="number" name="{{ $d['key'] }}" step="0.01" min="{{ $taken }}" value="{{ old($d['key'], $d['total']) }}" required aria-label="{{ $d['label'] }} capacity"
                                        class="w-full rounded-xl border border-slate-300 px-3 py-2 pr-12 text-right tabular-nums focus:border-brand-500 focus:ring-brand-500 @error($d['key']) border-red-400 @enderror">
                                    <span class="absolute inset-y-0 right-3 flex items-center text-xs text-slate-500">{{ $d['unit'] }}</span>
                                </div>
                                @error($d['key']) <p class="text-xs text-red-700 mt-1">{{ $message }}</p> @enderror
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-6 space-y-4 border-t border-slate-100">
            <div>
                <label for="cap-reason" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Reason <span class="font-normal normal-case text-slate-400">(optional)</span></label>
                <input id="cap-reason" type="text" name="reason" maxlength="255" value="{{ old('reason') }}" placeholder="e.g. Deck space reserved for a project shipment" class="w-full rounded-xl border border-slate-300 px-3 py-2 focus:border-brand-500 focus:ring-brand-500">
            </div>
            <x-dual-control.takes-effect op="cargo_capacity_update" />
            <p class="text-xs text-slate-500">A capacity can't be set below what is already booked or held. Bookings are only refused once the space is used up.</p>
            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.cargo.journeys.availability-index') }}" class="px-6 py-2.5 rounded-xl border border-slate-300 text-slate-700 font-bold hover:bg-slate-50">Cancel</a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold"><i class="fas fa-save mr-1"></i> Save capacity</button>
            </div>
        </div>
    </form>
</x-layouts.admin>
