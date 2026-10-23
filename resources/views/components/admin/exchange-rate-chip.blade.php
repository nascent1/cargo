{{--
    Header chip for whoever keeps the exchange rate up to date (exchange_rate:maintain): the USD→TZS rate in force
    and when it is next due. Opens <x-admin.exchange-rate-prompt />, which tells it when a new rate is saved.
--}}
@if (auth()->user()?->hasPermission('exchange_rate:maintain'))
    @php
        $rateStatus = \App\Services\ExchangeRateService::updateStatus();
    @endphp
    <div class="flex items-center"
         x-data="{
            rate: @js($rateStatus['rate']),
            due: @js($rateStatus['due']),
            dueOn: @js($rateStatus['due_on']),
            get rateText() { return Number(this.rate).toLocaleString('en-US', { maximumFractionDigits: 2 }); },
            get dueText() {
                if (this.due) return 'Due now';
                return this.dueOn ? 'Due ' + new Date(this.dueOn + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }) : '';
            },
         }"
         @exchange-rate-saved.window="if ($event.detail.rate) rate = $event.detail.rate; due = false; dueOn = $event.detail.due_on">
        <button type="button" @click="$dispatch('exchange-rate-open')"
                class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
                :class="due ? 'bg-amber-300 border-amber-200 text-amber-950 hover:bg-amber-200' : 'bg-white/10 border-white/25 text-white hover:bg-white/20'"
                :aria-label="`USD to TZS rate ${rateText}. ${dueText}. Set the rate`">
            <i class="fas fa-coins" aria-hidden="true"></i>
            <span class="tabular-nums whitespace-nowrap"><span class="hidden sm:inline">USD 1 = </span>TZS <span x-text="rateText">{{ number_format($rateStatus['rate'], 2) }}</span></span>
            <span x-show="dueText" class="hidden md:inline whitespace-nowrap font-medium" :class="due ? '' : 'text-brand-100'" x-text="'· ' + dueText"></span>
        </button>
    </div>
@endif
