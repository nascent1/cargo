<?php

namespace App\Services\DualControl\Handlers;

use App\Models\DualControlRequest;
use App\Models\ExchangeRate;
use App\Models\SystemConfig;
use App\Services\DualControl\DualControlException;
use App\Services\DualControl\DualControlHandler;
use App\Services\DualControl\StaleChangeGuard;
use App\Services\ExchangeRateService;
use Carbon\CarbonInterface;

class ExchangeRateHandler implements DualControlHandler
{
    public function execute(DualControlRequest $request, CarbonInterface $effectiveAt): void
    {
        if ($request->operation_type === 'exchange_rate_delete') {
            ExchangeRate::find($request->resource_id)?->delete();

            return;
        }

        $a = $request->payload_after ?? [];
        $existing = ExchangeRate::where('from_currency', $a['from_currency'])->where('to_currency', $a['to_currency'])
            ->whereDate('effective_from', $a['effective_from'])->lockForUpdate()->first();

        // Submitted as new but someone saved this date meanwhile, or the rate being replaced changed
        if ($existing && !$request->payload_before) {
            throw new DualControlException("A {$a['from_currency']}/{$a['to_currency']} rate for {$a['effective_from']} was saved after this request. Reject it and submit again.");
        }
        if ($existing) {
            StaleChangeGuard::assertFresh($request->payload_before, ['rate' => $a['rate'], 'effective_to' => $a['effective_to'] ?? null],
                ['rate' => (float) $existing->rate, 'effective_to' => $existing->effective_to ? \Carbon\Carbon::parse($existing->effective_to)->toDateString() : null]);
        }

        $rate = ExchangeRate::updateOrCreate(
            ['from_currency' => $a['from_currency'], 'to_currency' => $a['to_currency'], 'effective_from' => $a['effective_from']],
            ['rate' => $a['rate'], 'effective_to' => $a['effective_to'] ?? null, 'created_by' => $request->maker_id],
        );
        $request->update(['resource_id' => $rate->id]);

        // The setting is only the fallback, but keep it on the USD→TZS rate in force
        if ($rate->from_currency === 'USD' && $rate->to_currency === 'TZS' && ExchangeRateService::inForce('USD')?->id === $rate->id) {
            SystemConfig::setValue('usd_to_tzs_exchange_rate', (string) (float) $rate->rate);
        }
    }
}
