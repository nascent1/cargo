{{--
    Hand-over dialog: the collector's name, ID and mobile; a code is texted to that mobile and read
    back. Differences from the booking are warnings (recorded), never a block.
    Needs showHandover in the parent x-data.
--}}
@php
    $receiver = [
        'name' => $consignment->receiver_name,
        'id' => $consignment->receiver_id_number,
        'phone' => \App\Support\PhoneNumber::digits($consignment->receiver_phone),
    ];
@endphp
<div x-show="showHandover" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4" @keydown.escape.window="showHandover = false"
     x-data="handoverForm(@js($receiver), @js(route('admin.cargo.bookings.handover.code', $consignment)), @js(old('phone', $consignment->receiver_phone)), {{ old('phone') ? 'true' : 'false' }}, @js(old('collector_name', '')), @js(old('id_number', '')))">
    <div class="absolute inset-0 bg-slate-900/60" @click="showHandover = false"></div>
    <form method="POST" action="{{ route('admin.cargo.bookings.handover', $consignment) }}" class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg max-h-[92vh] overflow-y-auto">
        @csrf
        <div class="px-6 py-5 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-800"><i class="fas fa-hand-holding text-brand-600 mr-1"></i> Hand over {{ $consignment->consignment_number }}</h3>
            <p class="text-xs text-slate-500 mt-1">Receiver on the booking: <strong>{{ $consignment->receiver_name }}</strong>
                @if($consignment->receiver_id_number) · ID <span class="font-mono">{{ $consignment->receiver_id_number }}</span>@endif
                · {{ \App\Support\PhoneNumber::mask($consignment->receiver_phone) }}</p>
        </div>

        <div class="p-6 space-y-4 text-sm">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="ho-name">Collector's full name</label>
                <input id="ho-name" name="collector_name" x-model="name" required maxlength="150" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl">
            </div>
            <div class="grid grid-cols-5 gap-3">
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="ho-idtype">ID type</label>
                    <select id="ho-idtype" name="id_type" required class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl">
                        @foreach(\App\Services\Cargo\CargoHandoverService::ID_TYPES as $key => $label)
                            <option value="{{ $key }}" @selected(old('id_type', $consignment->receiver_id_type) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-3">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="ho-idno">ID number</label>
                    <input id="ho-idno" name="id_number" x-model="idNumber" required maxlength="60" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono uppercase">
                </div>
            </div>

            {{-- Mobile + code --}}
            <div class="rounded-2xl border border-slate-200 p-4 space-y-3">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider" for="ho-phone">Collector's mobile</label>
                <div class="flex gap-2">
                    <input id="ho-phone" name="phone" x-model="phone" required maxlength="20" inputmode="tel" class="flex-1 min-w-0 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono">
                    <button type="button" @click="sendCode()" :disabled="sending" class="shrink-0 px-4 py-2.5 rounded-xl bg-brand-600 text-white text-xs font-bold hover:bg-brand-700 disabled:opacity-50">
                        <i class="fas" :class="sending ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i> <span x-text="sent === null ? 'Send code' : 'Send again'"></span>
                    </button>
                </div>
                <p x-show="message" x-text="message" class="text-xs" :class="sent === false ? 'text-rose-700' : 'text-slate-600'"></p>
                <div x-show="sent === true">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="ho-code">Code read out by the collector</label>
                    <input id="ho-code" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" :required="sent === true" class="w-40 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono text-lg tracking-widest">
                </div>
                <div x-show="sent === false">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="ho-reason">Why the mobile can't be confirmed</label>
                    <input id="ho-reason" name="unconfirmed_reason" maxlength="255" :required="sent === false" placeholder="e.g. SMS not arriving — no network at the port" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl">
                </div>
            </div>

            <template x-if="warnings().length">
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-900">
                    <p class="font-bold mb-1"><i class="fas fa-triangle-exclamation mr-1"></i>Check before handing over — these will be recorded:</p>
                    <ul class="list-disc ml-4"><template x-for="w in warnings()" :key="w"><li x-text="w"></li></template></ul>
                </div>
            </template>
        </div>

        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
            <button type="button" @click="showHandover = false" class="px-5 py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl font-semibold text-sm">Cancel</button>
            <button type="submit" :disabled="sent === null" class="px-5 py-2.5 bg-emerald-600 text-white rounded-xl font-bold text-sm hover:bg-emerald-700 disabled:opacity-50" :title="sent === null ? 'Send a code to the mobile first' : ''">
                <i class="fas fa-check mr-1"></i>Hand over
            </button>
        </div>
    </form>
</div>

@once
@push('scripts')
<script>
/** Hand-over dialog: send the code, then show the same warnings the server will record. */
function handoverForm(receiver, codeUrl, phone, alreadySent, name, idNumber) {
    const norm = s => String(s || '').toLowerCase().replace(/\s+/g, ' ').trim();
    const digits = p => {
        let d = String(p || '').replace(/\D/g, '');
        if (d.startsWith('0') && d.length === 10) d = '255' + d.slice(1);
        if (d.length === 9) d = '255' + d;
        return d;
    };
    return {
        name, idNumber, phone, sending: false, message: '',
        sent: alreadySent ? true : null, // null: not asked yet · true: delivered · false: could not be delivered
        async sendCode() {
            this.sending = true;
            try {
                const res = await fetch(codeUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                    body: JSON.stringify({ phone: this.phone }),
                });
                const r = await res.json().catch(() => ({ message: 'Unexpected answer from the server — reload the page.' }));
                if (!res.ok) throw new Error(r.message || (r.errors ? Object.values(r.errors)[0][0] : 'Could not send the code.'));
                this.sent = !!r.sent;
                this.message = r.message;
            } catch (e) {
                this.message = e.message;
            } finally {
                this.sending = false;
            }
        },
        warnings() {
            const list = [];
            const r = norm(receiver.name), c = norm(this.name);
            if (c && r && !r.includes(c) && !c.includes(r)) list.push(`The collector is not the receiver on the booking (${receiver.name}).`);
            if (this.idNumber && receiver.id && norm(this.idNumber) !== norm(receiver.id)) list.push("The ID number differs from the receiver's ID on the booking.");
            if (this.phone && receiver.phone && digits(this.phone) !== receiver.phone) list.push("The mobile differs from the receiver's number on the booking.");
            return list;
        },
    };
}
</script>
@endpush
@endonce
