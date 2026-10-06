<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExchangeRateController extends Controller
{
    public function index()
    {
        $rates = ExchangeRate::with('creator')->orderByDesc('effective_from')->paginate(15);
        $currencies = Currency::where('is_active', true)->get();
        return view('admin.exchange-rates.index', compact('rates', 'currencies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3',
            'rate' => 'required|numeric|min:0.000001',
            'effective_from' => 'required|date',
        ]);

        ExchangeRate::updateOrCreate([
            'from_currency' => strtoupper($request->from_currency),
            'to_currency' => strtoupper($request->to_currency),
            'effective_from' => $request->effective_from,
        ], [
            'rate' => $request->rate,
            'effective_to' => $request->effective_to,
            'created_by' => Auth::id() ?? 1,
        ]);

        return redirect()->route('admin.exchange-rates.index')->with('success', 'Exchange rate saved successfully.');
    }

    public function destroy(ExchangeRate $exchangeRate)
    {
        $exchangeRate->delete();
        return redirect()->route('admin.exchange-rates.index')->with('success', 'Exchange rate deleted.');
    }
}
