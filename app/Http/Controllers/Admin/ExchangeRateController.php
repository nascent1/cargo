<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\SubmitsDualControl;
use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use App\Models\Currency;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ExchangeRateController extends Controller
{
    use SubmitsDualControl;

    public function index()
    {
        $rates = ExchangeRate::with('creator')->orderByDesc('effective_from')->paginate(15);
        $currencies = Currency::where('is_active', true)->get();
        // The entry each currency converts at today
        $inForceIds = $rates->pluck('from_currency')->unique()->reject(fn ($c) => $c === 'TZS')
            ->map(fn ($c) => ExchangeRateService::inForce($c)?->id)->filter()->all();
        return view('admin.exchange-rates.index', compact('rates', 'currencies', 'inForceIds'));
    }

    /**
     * Through dual control (exchange_rate_create). A rate carries its own start date, so an approved rate is
     * saved straight away with that date. Saving a pair + date that exists replaces it (shown as before / after).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3',
            'rate' => 'required|numeric|min:0.000001',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ]);

        return $this->submitRate($request, strtoupper($validated['from_currency']), strtoupper($validated['to_currency']), (float) $validated['rate'],
            Carbon::parse($validated['effective_from'])->toDateString(), $validated['effective_to'] ?? null, route('admin.settings.exchange-rates.index'));
    }

    /**
     * Today's USD→TZS rate from the rate prompt or the header chip (JSON). Open to everyone who keeps the rate
     * up to date (exchange_rate:maintain), so finance can set it without the Settings permission.
     */
    public function setCurrent(Request $request)
    {
        $validated = $request->validate(['rate' => 'required|numeric|min:1|max:1000000']);

        return $this->submitRate($request, 'USD', 'TZS', (float) $validated['rate'], today()->toDateString(), null);
    }

    private function submitRate(Request $request, string $from, string $to, float $rate, string $date, ?string $until, ?string $redirect = null)
    {
        $existing = ExchangeRate::where('from_currency', $from)->where('to_currency', $to)->whereDate('effective_from', $date)->first();
        $after = ['from_currency' => $from, 'to_currency' => $to, 'rate' => $rate, 'effective_from' => $date, 'effective_to' => $until];
        $start = Carbon::parse($date)->startOfDay();

        // Today's USD→TZS rate is the periodic update, even while it waits for a checker
        // (a refused submit means a rate for today was already submitted, which counts too)
        if ($from === 'USD' && $to === 'TZS' && $start->isToday()) {
            ExchangeRateService::markUpdated();
        }

        return $this->submitChange($request, 'exchange_rate_create', 'exchange_rate', $existing?->id ?? 0, [
            'before' => $existing ? ['rate' => (float) $existing->rate, 'effective_to' => $existing->effective_to ? Carbon::parse($existing->effective_to)->toDateString() : null] : null,
            'after' => $after,
        ], $existing ? 'Exchange rate replaced.' : 'Exchange rate saved.', $redirect, [
            'subject_key' => "exchange_rate:{$from}-{$to}:{$date}",
            'commence_at' => $start->isFuture() ? $start : null,
        ]);
    }

    public function destroy(Request $request, ExchangeRate $exchangeRate)
    {
        return $this->submitChange($request, 'exchange_rate_delete', 'exchange_rate', $exchangeRate->id, [
            'before' => $exchangeRate->only(['from_currency', 'to_currency', 'rate', 'effective_from', 'effective_to']),
        ], 'Exchange rate deleted.', route('admin.settings.exchange-rates.index'));
    }
}
