<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DualControlRequest;
use App\Models\IntegrationConfig;
use App\Services\Cargo\CloseOfBusinessService;
use App\Services\DualControl\DualControlException;
use App\Services\DualControlService;
use App\Services\Integrations\IntegrationConfigService;
use App\Services\Integrations\IntegrationTester;
use App\Services\SmsNotifier;
use App\Support\IntegrationCatalog;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Settings › Integrations: SMS gateways and SMTP servers (payment providers live on Payment Channels),
 * plus the SMS policy and close-of-business panels. Every change goes through dual control.
 */
class IntegrationController extends Controller
{
    public function __construct(private IntegrationConfigService $service)
    {
    }

    public function index(Request $request, SmsNotifier $notifier, CloseOfBusinessService $closeOfBusiness)
    {
        $user = $request->user();
        $configs = IntegrationConfig::with('updater')->whereIn('channel', array_keys(IntegrationCatalog::CHANNELS))->byPriority()->get()->groupBy('channel');

        $channels = collect(IntegrationCatalog::CHANNELS)->map(function (array $channel, string $key) use ($configs) {
            $rows = ($configs[$key] ?? collect())->values()->map(fn (IntegrationConfig $c) => $this->card($c));

            return $channel + [
                'key' => $key,
                'providers' => IntegrationCatalog::forChannel($key),
                'configs' => $rows,
                'pending_creates' => $this->service->pendingCreates($key),
                'pending_priority' => $this->service->pendingFor(IntegrationConfigService::subjectKey($key, true)),
                'can_add' => IntegrationCatalog::allowsMultiple($key) || $rows->isEmpty(),
            ];
        });

        return view('admin.settings.integrations', [
            'channels' => $channels,
            'can' => [
                'create' => $user->hasPermission('integration:create'),
                'update' => $user->hasPermission('integration:update'),
                'delete' => $user->hasPermission('integration:delete'),
            ],
            'smsPolicy' => $notifier->policySummary(),
            'closure' => $closeOfBusiness->panel(),
            'paymentProviders' => IntegrationConfig::where('channel', 'payment')->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $provider = $request->validate(['provider' => ['required', Rule::in(array_keys(IntegrationCatalog::PROVIDERS))]])['provider'];
        $meta = IntegrationCatalog::provider($provider);

        if (!IntegrationCatalog::allowsMultiple($meta['channel']) && IntegrationConfig::where('channel', $meta['channel'])->exists()) {
            return back()->with('error', IntegrationCatalog::CHANNELS[$meta['channel']]['label'] . ' allows a single configuration; edit the existing one.');
        }

        $input = $this->input($request, $meta);

        return $this->submit(
            fn () => $this->assertActivatable($this->service->preview(null, $provider, $input)) ?? $this->service->submitCreate($provider, $input),
            "{$input['name']} added."
        );
    }

    public function update(Request $request, IntegrationConfig $integration): RedirectResponse
    {
        $input = $this->input($request, IntegrationCatalog::provider($integration->provider) ?? abort(404));

        return $this->submit(
            fn () => $this->assertActivatable($this->service->preview($integration, $integration->provider, $input)) ?? $this->service->submitUpdate($integration, $input),
            "{$integration->name} saved."
        );
    }

    public function status(Request $request, IntegrationConfig $integration): RedirectResponse
    {
        $active = $request->boolean('is_active');
        if ($active && !IntegrationCatalog::isComplete($integration)) {
            return back()->with('error', "{$integration->name} can't be activated yet — missing: " . implode(', ', IntegrationCatalog::missing($integration)) . '.');
        }

        return $this->submit(fn () => $this->service->submitStatus($integration, $active), "{$integration->name} " . ($active ? 'activated.' : 'deactivated.'));
    }

    public function priority(Request $request, string $channel): RedirectResponse
    {
        abort_unless(IntegrationCatalog::allowsMultiple($channel), 404);
        $ids = $request->validate(['order' => 'required|array|min:1', 'order.*' => 'required|string'])['order'];

        return $this->submit(fn () => $this->service->submitPriority($channel, $ids), 'Priority order updated.');
    }

    public function destroy(IntegrationConfig $integration): RedirectResponse
    {
        if ($integration->is_active) {
            return back()->with('error', "Deactivate {$integration->name} before deleting it.");
        }

        return $this->submit(fn () => $this->service->submitDelete($integration), "{$integration->name} deleted.");
    }

    /**
     * Test with the values currently in the form (blank secrets = the stored ones). Really sends.
     */
    public function test(Request $request, IntegrationTester $tester): JsonResponse
    {
        $data = $request->validate([
            'provider' => ['required', Rule::in(array_keys(IntegrationCatalog::PROVIDERS))],
            'integration_id' => 'nullable|string|exists:integration_configs,id',
            'recipient' => 'nullable|string|max:255',
        ]);
        $existing = !empty($data['integration_id']) ? IntegrationConfig::find($data['integration_id']) : null;
        $meta = IntegrationCatalog::provider($data['provider']);

        $draft = $this->service->preview($existing, $data['provider'], $this->input($request, $meta, forTest: true));
        if ($missing = IntegrationCatalog::missing($draft)) {
            return response()->json(['ok' => false, 'message' => 'Fill in first: ' . implode(', ', $missing) . '.', 'detail' => null]);
        }

        $outcome = $tester->test($draft, $data['recipient'] ?? null);
        AuditLog::createEntry($request->user()->id, 'integration_test', IntegrationConfigService::RESOURCE, $existing?->id ?? $data['provider'],
            null, ['name' => $draft->name, 'ok' => $outcome['ok'], 'message' => $outcome['message']], $request->ip());

        return response()->json($outcome);
    }

    // ── Helpers ──────────────────────────────────────────────

    /**
     * Validate the provider form and map it to the service's input shape.
     */
    private function input(Request $request, array $meta, bool $forTest = false): array
    {
        $rules = [
            'name' => $forTest ? 'nullable|string|max:100' : 'required|string|max:100',
            'clear' => 'nullable|array',
            'clear.*' => 'string',
        ];
        if ($meta['uses_urls']) {
            $rules += [
                'environment' => 'required|in:sandbox,production',
                'sandbox_url' => 'nullable|url|max:500',
                'production_url' => 'nullable|url|max:500',
            ];
        }
        foreach ($meta['fields'] as $field => $f) {
            $rules[IntegrationCatalog::inputName($field)] = match ($f['type'] ?? 'text') {
                'email' => 'nullable|email|max:255',
                'number' => 'nullable|integer|min:1|max:65535',
                'select' => ['nullable', Rule::in(array_keys($f['options'] ?? []))],
                default => ($f['secret'] ?? false) ? 'nullable|string|max:5000' : 'nullable|string|max:' . ($f['max'] ?? 500),
            };
        }
        $validated = $request->validate($rules);

        $input = [
            'fields' => [],
            'clear' => array_values(array_map(fn ($k) => str_replace('__', '.', $k), $validated['clear'] ?? [])),
        ];
        if (!$forTest || filled($validated['name'] ?? null)) {
            $input['name'] = $validated['name'] ?? $meta['label'];
        }
        if (!$forTest) {
            $input['is_active'] = $request->boolean('is_active');
        }
        if ($meta['uses_urls']) {
            $input['environment'] = $validated['environment'];
            $input['sandbox_url'] = $validated['sandbox_url'] ?? null;
            $input['production_url'] = $validated['production_url'] ?? null;
        }
        foreach ($meta['fields'] as $field => $f) {
            $input['fields'][$field] = $validated[IntegrationCatalog::inputName($field)] ?? null;
        }

        return $input;
    }

    /** An error redirect when the previewed config would be active but incomplete. */
    private function assertActivatable(IntegrationConfig $draft): ?RedirectResponse
    {
        if ($draft->is_active && $missing = IntegrationCatalog::missing($draft)) {
            return back()->withInput()->with('error', "Not saved: {$draft->name} can't be active while missing " . implode(', ', $missing) . '. Fill them in or untick Active.');
        }

        return null;
    }

    private function submit(callable $action, string $doneMessage): RedirectResponse
    {
        try {
            $result = $action();
        } catch (InvalidArgumentException|DualControlException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($result instanceof RedirectResponse) {
            return $result;
        }

        /** @var DualControlRequest $result */
        return back()->with('success', app(DualControlService::class)->outcomeMessage($result, $doneMessage));
    }

    private function card(IntegrationConfig $config): array
    {
        $meta = IntegrationCatalog::provider($config->provider);
        $values = [];
        $secretsSet = [];
        $unreadable = false;
        foreach ($meta['fields'] ?? [] as $field => $f) {
            try {
                $value = $config->fieldValue($field);
            } catch (DecryptException) {
                // Stored with another APP_KEY: treat as not set, ask for it again
                $value = null;
                $unreadable = true;
            }
            if ($f['secret'] ?? false) {
                $secretsSet[$field] = filled($value);
            } else {
                $values[$field] = $value;
            }
        }

        return [
            'model' => $config,
            'meta' => $meta,
            'status' => $meta && !$unreadable ? IntegrationCatalog::status($config) : 'unknown',
            'missing' => $meta && !$unreadable ? IntegrationCatalog::missing($config) : [],
            'values' => $values,
            'secrets_set' => $secretsSet,
            'unreadable' => $unreadable,
            'pending' => $this->service->pendingFor(IntegrationConfigService::subjectKey($config->id)),
        ];
    }
}
