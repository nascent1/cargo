<?php

namespace App\Services\DualControl\Handlers;

use App\Gateways\Payment\Push\PushGatewayManager;
use App\Models\DualControlRequest;
use App\Models\IntegrationConfig;
use App\Models\PaymentMethod;
use App\Models\SystemConfig;
use App\Services\DualControl\DualControlException;
use App\Services\DualControl\DualControlHandler;
use App\Services\DualControl\StaleChangeGuard;
use App\Services\DualControlService;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Settings › Payment Channels changes: channels, provider credentials (sealed secrets) and payment rules.
 */
class PaymentChannelHandler implements DualControlHandler
{
    public const METHOD_FIELDS = ['name', 'flow', 'driver', 'sort_order', 'account_number', 'account_name', 'instructions', 'is_active'];

    public function execute(DualControlRequest $request, CarbonInterface $effectiveAt): void
    {
        match ($request->operation_type) {
            'payment_method_create' => $this->createMethod($request),
            'payment_method_update' => $this->updateMethod($request),
            'payment_provider_update' => $this->updateProvider($request),
            'payment_rules_update' => $this->updateRules($request),
        };
    }

    private function createMethod(DualControlRequest $request): void
    {
        $data = Arr::only($request->payload_after ?? [], self::METHOD_FIELDS);
        $base = Str::slug($data['name'], '_');
        $slug = $base;
        for ($i = 2; PaymentMethod::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}_{$i}";
        }

        $method = PaymentMethod::create($data + ['slug' => $slug]);
        $request->update(['resource_id' => $method->id, 'subject_key' => "payment_method:{$method->id}"]);
    }

    private function updateMethod(DualControlRequest $request): void
    {
        $method = PaymentMethod::find($request->resource_id) ?? throw new DualControlException('This payment channel no longer exists.');
        $after = Arr::only($request->payload_after ?? [], self::METHOD_FIELDS);
        StaleChangeGuard::assertFresh($request->payload_before, $after, $method->only(self::METHOD_FIELDS));

        $method->update(Arr::only($after, StaleChangeGuard::changedKeys($request->payload_before, $after)));
    }

    /**
     * Same rules as the screen always had: a blank secret keeps the stored one, a callback token is created
     * once, and a provider switched on without every required field is saved but left off.
     */
    private function updateProvider(DualControlRequest $request): void
    {
        $after = DualControlService::unseal($request->payload_after ?? []);
        $driver = $after['driver'] ?? null;
        $meta = PushGatewayManager::DRIVERS[$driver] ?? throw new DualControlException('Unknown payment provider.');

        $config = (new PushGatewayManager())->integration($driver) ?? new IntegrationConfig([
            'code' => strtoupper($driver), 'name' => $meta['label'], 'provider' => $driver,
            'channel' => 'payment', 'is_default' => false, 'sort_order' => 100, 'currency' => 'TZS', 'country_code' => 'TZ',
        ]);

        $extra = $config->extra_credentials ?? [];
        foreach ($meta['fields'] as $field => $f) {
            if (!Arr::has($after['fields'] ?? [], $field)) {
                continue; // a secret left blank: keep the stored one
            }
            $value = data_get($after['fields'], $field);
            if (str_starts_with($field, 'extra.')) {
                $extra[substr($field, 6)] = $value;
            } else {
                $config->{$field} = $value;
            }
        }
        $extra['callback_token'] ??= Str::random(40);

        $config->fill([
            'environment' => $after['environment'],
            'sandbox_url' => $after['sandbox_url'] ?? null,
            'production_url' => $after['production_url'] ?? null,
            'base_url' => ($after['sandbox_url'] ?? null) ?: $config->base_url,
            'callback_url' => $after['callback_url'] ?? null,
            'is_active' => (bool) ($after['is_active'] ?? false),
        ]);
        $config->extra_credentials = $extra;
        $config->save();

        if ($config->is_active && !(new PushGatewayManager())->isConfigured($driver)) {
            $config->update(['is_active' => false]);
        }
        $request->update(['resource_id' => $config->id]);
    }

    private function updateRules(DualControlRequest $request): void
    {
        $after = $request->payload_after ?? [];
        $current = collect(array_keys($after))->mapWithKeys(fn ($k) => [$k => SystemConfig::getValue($k)])->all();
        StaleChangeGuard::assertFresh($request->payload_before, $after, $current);

        foreach ($after as $key => $value) {
            SystemConfig::setValue($key, (string) $value);
        }
    }
}
