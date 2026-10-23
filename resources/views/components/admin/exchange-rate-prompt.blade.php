{{--
    The USD→TZS rate prompt, for whoever keeps the exchange rate up to date (exchange_rate:maintain).
    - Forced (no way to dismiss) when the rate is due and ForceExchangeRateUpdate held a change back
      (this page load, or 'exchange-rate-due' from a background request answered 423).
    - Optional from the header chip ('exchange-rate-open').
    Saves today's rate through dual control without reloading the page, then sends 'exchange-rate-saved'.
--}}
@if (auth()->user()?->hasPermission('exchange_rate:maintain'))
    @php
        $rateStatus = \App\Services\ExchangeRateService::updateStatus();
        $freq = $rateStatus['frequency_days'];
    @endphp
    <div x-data="{
            open: @js($rateStatus['due'] && session('force_exchange_modal')),
            forced: @js($rateStatus['due'] && session('force_exchange_modal')),
            rate: @js($rateStatus['rate']),
            frequency: @js($freq),
            saving: false,
            error: '',
            show(forced) {
                this.forced = this.forced || forced;
                this.error = '';
                this.open = true;
                this.$nextTick(() => this.$refs.rate?.select());
            },
            close() { if (!this.forced && !this.saving) this.open = false; },
            async save() {
                this.saving = true;
                this.error = '';
                try {
                    const res = await fetch(@js(route('admin.exchange-rate.current')), {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content },
                        body: JSON.stringify({ rate: this.rate }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        this.error = data.errors?.rate?.[0] || data.message || `The rate could not be saved (${res.status}).`;
                        return;
                    }
                    const next = new Date();
                    next.setDate(next.getDate() + this.frequency);
                    window.dispatchEvent(new CustomEvent('exchange-rate-saved', { detail: {
                        rate: data.applied ? Number(this.rate) : null,
                        due_on: this.frequency > 0 ? next.toLocaleDateString('en-CA') : null,
                    } }));
                    this.forced = false;
                    this.open = false;
                    window.notify?.(data.message || 'Exchange rate saved.', data.applied === false ? 'info' : 'success');
                } catch (e) {
                    this.error = 'No connection. Try again.';
                } finally {
                    this.saving = false;
                }
            },
         }"
         x-init="open && $nextTick(() => $refs.rate?.select())"
         @exchange-rate-open.window="show(false)" @exchange-rate-due.window="show(true)">
        <div x-show="open" x-cloak class="fixed inset-0 z-[9500] flex items-end sm:items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-[2px]" @click="close()" aria-hidden="true"></div>

            <form x-show="open" @submit.prevent="save()" @keydown.escape.prevent.stop="close()"
                  role="dialog" aria-modal="true" aria-labelledby="exchange-rate-title"
                  x-transition:enter="ease-out duration-150 motion-reduce:duration-0" x-transition:enter-start="opacity-0 translate-y-3 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                  class="relative w-full max-w-md bg-white rounded-2xl border border-slate-200 shadow-2xl overflow-hidden text-left font-sans">
                <div class="px-5 pt-5 sm:px-6 sm:pt-6 flex items-start gap-4">
                    <div class="shrink-0 h-11 w-11 rounded-xl ring-1 ring-inset flex items-center justify-center text-lg"
                         :class="forced ? 'bg-amber-50 text-amber-600 ring-amber-100' : 'bg-brand-50 text-brand-600 ring-brand-100'" aria-hidden="true">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="min-w-0 flex-1 pt-0.5">
                        <h2 id="exchange-rate-title" class="text-base font-semibold text-slate-900 leading-snug">
                            <span x-show="forced">{{ [1 => 'Daily exchange rate required', 7 => 'Weekly exchange rate required'][$freq] ?? 'Exchange rate update required' }}</span>
                            <span x-show="!forced">Set today's exchange rate</span>
                        </h2>
                        <p class="mt-1 text-sm text-slate-600 leading-relaxed">
                            <span x-show="forced">The USD to TZS rate is set every {{ $freq === 1 ? 'day' : "{$freq} days" }}. Set it before making changes.</span>
                            It applies to conversions from today.
                        </p>
                    </div>
                </div>

                <div class="px-5 sm:px-6 mt-4">
                    <label for="exchange-rate-input" class="block text-xs font-semibold text-slate-700 mb-1">TZS for 1 USD</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-sm font-semibold text-slate-400" aria-hidden="true">TZS</span>
                        <input id="exchange-rate-input" x-ref="rate" type="number" step="0.01" min="1" max="1000000" required x-model="rate"
                               class="block w-full rounded-xl border border-slate-300 pl-12 pr-3 py-2.5 text-sm font-semibold tabular-nums focus:border-brand-500 focus:ring-brand-500 shadow-sm"
                               :class="error && 'border-red-400'" :aria-invalid="!!error" aria-describedby="exchange-rate-help exchange-rate-error">
                    </div>
                    <p id="exchange-rate-error" x-show="error" x-text="error" class="mt-1 text-xs font-semibold text-red-700"></p>
                    <p id="exchange-rate-help" class="mt-2 text-xs text-slate-500">
                        In force now: TZS {{ rtrim(rtrim(number_format($rateStatus['rate'], 2), '0'), '.') }}{{ $rateStatus['since'] ? ' since ' . \Carbon\Carbon::parse($rateStatus['since'])->format('j M Y') : ' (default)' }}.
                        @if ($rateStatus['last_updated'] && $rateStatus['last_updated'] > '2000-01-01')
                            Last set {{ \Carbon\Carbon::parse($rateStatus['last_updated'])->format('j M Y') }}.
                        @endif
                        @if (auth()->user()->hasPermission('setting:read'))
                            <a href="{{ route('admin.settings.exchange-rates.index') }}" class="font-semibold text-brand-700 hover:underline whitespace-nowrap">All rates ›</a>
                        @endif
                    </p>
                </div>

                <div class="mt-5 px-5 sm:px-6 py-4 bg-slate-50 border-t border-slate-100 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button" x-show="!forced" @click="close()"
                            class="h-10 px-4 rounded-xl text-sm font-semibold border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-slate-300">Cancel</button>
                    <button type="submit" :disabled="saving"
                            class="h-10 px-4 rounded-xl text-sm font-semibold text-white shadow-sm inline-flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-700 disabled:opacity-60 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-brand-400">
                        <i class="fas" :class="saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'" aria-hidden="true"></i> Save rate
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
