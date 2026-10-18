<?php

namespace App\Http\Controllers\Admin;

use App\Gateways\Payment\Push\PushGatewayManager;
use App\Http\Controllers\Concerns\SubmitsDualControl;
use App\Http\Controllers\Controller;
use App\Services\DualControl\DualControlException;
use App\Services\DualControl\Handlers\PaymentChannelHandler;
use App\Services\DualControlService;
use App\Models\PaymentMethod;
use App\Models\SystemConfig;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Settings › Payment Channels: which channels checkout offers, how each collects money,
 * Lipa Namba details, per-provider credentials and the payment window rules.
 */
class PaymentChannelController extends Controller
{
    use SubmitsDualControl;

    private const RULES = [
        'cargo_ussd_timeout_minutes'      => 'required|integer|min:2|max:120',
        'cargo_lipa_timeout_minutes'      => 'required|integer|min:5|max:1440',
        'cargo_ussd_max_pushes'           => 'required|integer|min:1|max:10',
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

        $before = $paymentMethod->only(PaymentChannelHandler::METHOD_FIELDS);

        return $this->submitChange($request, 'payment_method_update', 'payment_method', $paymentMethod->id,
            ['before' => $before, 'after' => array_merge($before, $data)], "{$paymentMethod->name} updated.");
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

        // The slug is chosen when the channel is created (PaymentChannelHandler)
        return $this->submitChange($request, 'payment_method_create', 'payment_method', 0, ['after' => $data], "{$data['name']} added.");
    }

    /**
     * Provider credentials through dual control (payment_provider_update). Secrets are sealed (encrypted) in the
     * request and never shown; a secret left blank keeps the stored one.
     */
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

        $config = (new PushGatewayManager())->integration($driver);
        $fields = [];
        $sealed = [];
        $before = ['environment' => $config?->environment, 'sandbox_url' => $config?->sandbox_url, 'production_url' => $config?->production_url,
            'callback_url' => $config?->callback_url, 'is_active' => (bool) $config?->is_active];
        foreach ($meta['fields'] as $field => $f) {
            $value = $validated[$this->inputName($field)] ?? null;
            $current = $config ? PushGatewayManager::fieldValue($config, $field) : null;
            if ($f['secret'] ?? false) {
                $before["secret.{$field}"] = filled($current) ? 'set' : 'not set';
                if (blank($value)) {
                    continue; // blank secret = keep the stored one
                }
                $sealed[] = "fields.{$field}";
            } else {
                $before["fields.{$field}"] = $current;
            }
            data_set($fields, $field, $value);
        }

        $dualControl = app(DualControlService::class);
        try {
            $dcRequest = $dualControl->submit('payment_provider_update', 'integration_config', $config?->id ?? 0, [
                'before' => $before,
                'after' => [
                    'driver' => $driver,
                    'environment' => $validated['environment'],
                    'sandbox_url' => $validated['sandbox_url'] ?? null,
                    'production_url' => $validated['production_url'] ?? null,
                    'callback_url' => $validated['callback_url'] ?? null,
                    'is_active' => $request->boolean('is_active'),
                    'fields' => $fields,
                ],
            ], [
                'sealed' => $sealed,
                'subject_key' => "payment_provider:{$driver}",
                'commence_at' => $this->takesEffect($request, 'payment_provider_update'),
            ]);
        } catch (DualControlException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($dcRequest->status !== 'approved') {
            return back()->with('success', $dualControl->outcomeMessage($dcRequest, ''));
        }

        $saved = (new PushGatewayManager())->integration($driver);
        if ($request->boolean('is_active') && !$saved?->is_active) {
            return back()->with('error', "{$meta['label']} saved but left inactive: fill in every required field and the {$saved?->environment} URL.");
        }

        return back()->with('success', "{$meta['label']} credentials saved" . ($saved?->is_active ? ' and activated.' : '.'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate(self::RULES);

        $after = [];
        foreach (self::RULES as $key => $rule) {
            $after[$key] = $rule === 'boolean' ? ($request->boolean($key) ? '1' : '0') : (string) $validated[$key];
        }
        $before = collect(array_keys(self::RULES))->mapWithKeys(fn ($k) => [$k => SystemConfig::getValue($k)])->all();

        return $this->submitChange($request, 'payment_rules_update', 'system_config', 0, ['before' => $before, 'after' => $after],
            'Payment rules updated.', null, ['subject_key' => 'payment_rules']);
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
