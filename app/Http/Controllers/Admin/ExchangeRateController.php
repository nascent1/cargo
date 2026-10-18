<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\SubmitsDualControl;
use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use App\Models\Currency;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ExchangeRateController extends Controller
{
    use SubmitsDualControl;

    public function index()
    {
        $rates = ExchangeRate::with('creator')->orderByDesc('effective_from')->paginate(15);
        $currencies = Currency::where('is_active', true)->get();
        return view('admin.exchange-rates.index', compact('rates', 'currencies'));
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
        $from = strtoupper($validated['from_currency']);
        $to = strtoupper($validated['to_currency']);
        $date = Carbon::parse($validated['effective_from'])->toDateString();

        $existing = ExchangeRate::where('from_currency', $from)->where('to_currency', $to)->whereDate('effective_from', $date)->first();
        $after = ['from_currency' => $from, 'to_currency' => $to, 'rate' => (float) $validated['rate'], 'effective_from' => $date, 'effective_to' => $validated['effective_to'] ?? null];
        $start = Carbon::parse($date)->startOfDay();

        return $this->submitChange($request, 'exchange_rate_create', 'exchange_rate', $existing?->id ?? 0, [
            'before' => $existing ? ['rate' => (float) $existing->rate, 'effective_to' => $existing->effective_to ? Carbon::parse($existing->effective_to)->toDateString() : null] : null,
            'after' => $after,
        ], $existing ? 'Exchange rate replaced.' : 'Exchange rate saved.', route('admin.settings.exchange-rates.index'), [
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
