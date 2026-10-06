<?php

namespace App\Http\Controllers\Admin;

use App\Gateways\Payment\Push\PushGatewayManager;
use App\Http\Controllers\Controller;
use App\Models\IntegrationConfig;
use App\Models\PaymentMethod;
use App\Models\SystemConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Settings › Payment Channels: which channels checkout offers, how each collects money,
 * Lipa Namba details, per-provider credentials and the payment window rules.
 */
class PaymentChannelController extends Controller
{
    private const RULES = [
        'cargo_ussd_timeout_minutes'      => 'required|integer|min:2|max:120',
        'cargo_lipa_timeout_minutes'      => 'required|integer|min:5|max:1440',
        'cargo_ussd_max_pushes'           => 'required|integer|min:1|max:10',
        'cargo_payment_sms_enabled'       => 'boolean',
        'cargo_payment_simulator_enabled' => 'boolean',
    ];

    public function index(PushGatewayManager $gateways)
    {
        $methods = PaymentMethod::orderByDesc('is_active')->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (PaymentMethod $m) => $m->setAttribute('checkout_ready', $m->is_active && $m->isCheckoutReady()));

        $providers = collect(PushGatewayManager::DRIVERS)->map(function (array $meta, string $driver) use ($gateways) {
            $config = $gateways->integration($driver);

            return [
                'driver'       => $driver,
                'label'        => $meta['label'],
                'fields'       => $meta['fields'],
                'config'       => $config,
                'configured'   => $gateways->isConfigured($driver),
                'callback_url' => PushGatewayManager::callbackUrl($config, $driver),
                // Secrets are never sent back to the browser — only whether one is stored
                'values'       => collect($meta['fields'])->map(function ($f, $field) use ($config) {
                    $value = $config ? PushGatewayManager::fieldValue($config, $field) : null;

                    return ($f['secret'] ?? false) ? filled($value) : $value;
                })->all(),
            ];
        });

        return view('admin.settings.payment-channels', [
            'methods'          => $methods,
            'providers'        => $providers,
            'drivers'          => collect(PushGatewayManager::DRIVERS)->map(fn ($d) => $d['label'])->all(),
            'simulatorAllowed' => $gateways->simulatorAllowed(),
            'isProduction'     => app()->environment('production'),
            'settings'         => collect(array_keys(self::RULES))->mapWithKeys(fn ($k) => [$k => SystemConfig::getValue($k)])->all(),
        ]);
    }

    public function updateMethod(Request $request, PaymentMethod $paymentMethod)
    {
        $data = $this->validateMethod($request);

        if ($data['is_active'] && empty($data['flow'])) {
            return back()->with('error', "Choose how {$paymentMethod->name} collects payment before activating it.");
        }

        $paymentMethod->update($data);

        return back()->with('success', "{$paymentMethod->name} updated.");
    }

    /**
     * Add a channel, e.g. a second Lipa Namba for another network.
     */
    public function storeMethod(Request $request)
    {
        $data = $this->validateMethod($request);

        if ($data['is_active'] && empty($data['flow'])) {
            return back()->with('error', 'Choose how the new channel collects payment before activating it.');
        }

        $base = Str::slug($data['name'], '_');
        $slug = $base;
        for ($i = 2; PaymentMethod::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}_{$i}";
        }

        PaymentMethod::create($data + ['slug' => $slug]);

        return back()->with('success', "{$data['name']} added.");
    }

    public function updateProvider(Request $request, string $driver)
    {
        abort_unless(isset(PushGatewayManager::DRIVERS[$driver]), 404);
        $meta = PushGatewayManager::DRIVERS[$driver];

        $rules = [
            'environment'    => 'required|in:sandbox,production',
            'sandbox_url'    => 'nullable|url|max:500',
            'production_url' => 'nullable|url|max:500',
            'callback_url'   => 'nullable|url|max:500',
        ];
        foreach ($meta['fields'] as $field => $f) {
            $rules[$this->inputName($field)] = ($f['type'] ?? null) === 'textarea' ? 'nullable|string|max:5000' : 'nullable|string|max:500';
        }
        $validated = $request->validate($rules);

        $config = (new PushGatewayManager())->integration($driver) ?? new IntegrationConfig([
            'code' => strtoupper($driver), 'name' => $meta['label'], 'provider' => $driver,
            'channel' => 'payment', 'is_default' => false, 'sort_order' => 100, 'currency' => 'TZS', 'country_code' => 'TZ',
        ]);

        $extra = $config->extra_credentials ?? [];
        foreach ($meta['fields'] as $field => $f) {
            $value = $validated[$this->inputName($field)] ?? null;
            if (($f['secret'] ?? false) && blank($value)) {
                continue; // blank secret = keep the stored one
            }
            if (str_starts_with($field, 'extra.')) {
                $extra[substr($field, 6)] = $value;
            } else {
                $config->{$field} = $value;
            }
        }
        // Secret token in the callback URL authenticates provider callbacks
        $extra['callback_token'] ??= Str::random(40);

        $config->fill([
            'environment'    => $validated['environment'],
            'sandbox_url'    => $validated['sandbox_url'] ?? null,
            'production_url' => $validated['production_url'] ?? null,
            'base_url'       => $validated['sandbox_url'] ?? $config->base_url,
            'callback_url'   => $validated['callback_url'] ?? null,
            'is_active'      => $request->boolean('is_active'),
        ]);
        $config->extra_credentials = $extra;
        $config->save();

        if ($config->is_active && !(new PushGatewayManager())->isConfigured($driver)) {
            $config->update(['is_active' => false]);

            return back()->with('error', "{$meta['label']} saved but left inactive: fill in every required field and the {$config->environment} URL.");
        }

        return back()->with('success', "{$meta['label']} credentials saved" . ($config->is_active ? ' and activated.' : '.'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate(self::RULES);

        foreach (self::RULES as $key => $rule) {
            $value = $rule === 'boolean' ? ($request->boolean($key) ? '1' : '0') : (string) $validated[$key];
            SystemConfig::setValue($key, $value);
        }

        return back()->with('success', 'Payment rules updated.');
    }

    private function validateMethod(Request $request): array
    {
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'flow'           => ['nullable', Rule::in(PaymentMethod::FLOWS)],
            'driver'         => ['nullable', 'required_if:flow,ussd_push', Rule::in(array_keys(PushGatewayManager::DRIVERS))],
            'sort_order'     => 'nullable|integer|min:0|max:9999',
            'account_number' => 'nullable|string|max:50',
            'account_name'   => 'nullable|string|max:100',
            'instructions'   => 'nullable|string|max:1000',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 100;
        if (($data['flow'] ?? null) !== 'ussd_push') {
            $data['driver'] = null;
        }

        return $data;
    }

    private function inputName(string $field): string
    {
        return 'f_' . str_replace('.', '__', $field);
    }
}
