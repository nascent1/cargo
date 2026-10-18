<?php

namespace App\Services\Integrations;

use App\Models\AuditLog;
use App\Models\DualControlRequest;
use App\Models\IntegrationConfig;
use App\Services\DualControl\DualControlException;
use App\Services\DualControlService;
use App\Support\IntegrationCatalog;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Changes to SMS gateways and SMTP servers, routed through dual control like every other
 * configuration change (rules integration_*; off = applied straight away, still recorded).
 *
 * Integration rows have UUID keys, so requests identify them by subject_key "integration:{uuid}"
 * (and "integration_priority:{channel}" for priority changes). New secret values travel sealed
 * (encrypted) in the request, so the approval screen and audit trail never show them.
 */
class IntegrationConfigService
{
    public const RESOURCE = 'integration_config';

    /** Non-secret columns an admin may edit; catalog fields are added per provider. */
    private const EDITABLE_COLUMNS = ['name', 'environment', 'sandbox_url', 'production_url', 'callback_url', 'is_active'];

    public function __construct(private DualControlService $dualControl)
    {
    }

    public static function subjectKey(string $idOrChannel, bool $priority = false): string
    {
        return ($priority ? 'integration_priority:' : 'integration:') . $idOrChannel;
    }

    // ── Submitting changes ───────────────────────────────────

    public function submitCreate(string $provider, array $input): DualControlRequest
    {
        $meta = $this->meta($provider);
        [$attributes, $secrets, $clear] = $this->split($meta, $input);

        return $this->submit('integration_create', null, null, $this->payload($provider, $meta, $attributes, $secrets, $clear));
    }

    public function submitUpdate(IntegrationConfig $config, array $input): DualControlRequest
    {
        $meta = $this->meta($config->provider);
        [$attributes, $secrets, $clear] = $this->split($meta, $input);

        return $this->submit('integration_update', $config, $this->snapshot($config),
            $this->payload($config->provider, $meta, $attributes, $secrets, $clear) + ['config_id' => $config->id]);
    }

    public function submitStatus(IntegrationConfig $config, bool $active): DualControlRequest
    {
        return $this->submit('integration_status_update', $config,
            ['name' => $config->name, 'is_active' => $config->is_active],
            ['name' => $config->name, 'is_active' => $active, 'config_id' => $config->id]);
    }

    /**
     * @param string[] $orderedIds every config id of the channel, primary first
     */
    public function submitPriority(string $channel, array $orderedIds): DualControlRequest
    {
        $current = IntegrationConfig::where('channel', $channel)->byPriority()->get(['id', 'name']);
        if (collect($orderedIds)->sort()->values()->all() !== $current->pluck('id')->sort()->values()->all()) {
            throw new InvalidArgumentException('The new order must list every configuration of the channel exactly once.');
        }
        $names = $current->pluck('name', 'id');

        return $this->dualControl->submit('integration_priority_update', self::RESOURCE, 0, [
            'before' => ['channel' => $channel, 'order' => $current->pluck('name')->all()],
            'after' => ['channel' => $channel, 'order' => collect($orderedIds)->map(fn ($id) => $names[$id])->all(), 'ids' => array_values($orderedIds)],
        ], ['subject_key' => self::subjectKey($channel, true)]);
    }

    public function submitDelete(IntegrationConfig $config): DualControlRequest
    {
        return $this->submit('integration_delete', $config, $this->snapshot($config), ['name' => $config->name, 'config_id' => $config->id]);
    }

    /**
     * The config as it would look after these changes (unsaved), for validation and Test.
     */
    public function preview(?IntegrationConfig $config, string $provider, array $input): IntegrationConfig
    {
        $meta = $this->meta($provider);
        [$attributes, $secrets, $clear] = $this->split($meta, $input);

        $draft = $config ? $config->replicate() : $this->newConfig($provider, $meta);
        if ($config) {
            $draft->id = $config->id;
        }
        $this->fill($draft, $attributes, $secrets, $clear);

        return $draft;
    }

    public function pendingFor(string $subjectKey): ?DualControlRequest
    {
        return $this->dualControl->openChangeFor($subjectKey);
    }

    /**
     * Requests to add a config for a channel that are still waiting.
     */
    public function pendingCreates(string $channel)
    {
        return DualControlRequest::with('maker')->where('operation_type', 'integration_create')->open()
            ->where('expires_at', '>', now())->get()
            ->filter(fn ($r) => ($r->payload_after['channel'] ?? null) === $channel)->values();
    }

    // ── Applying approved changes (IntegrationHandler) ───────

    public function applyApproved(DualControlRequest $request): void
    {
        $after = DualControlService::unseal($request->payload_after ?? []);

        match ($request->operation_type) {
            'integration_create' => $this->applyCreate($request, $after),
            'integration_update' => $this->applyUpdate($request, $after),
            'integration_status_update' => $this->applyStatus($request, $after),
            'integration_priority_update' => $this->applyPriority($request, $after),
            'integration_delete' => $this->applyDelete($request, $after),
        };
    }

    private function applyCreate(DualControlRequest $request, array $after): void
    {
        $meta = $this->meta($after['provider']);
        $config = $this->newConfig($after['provider'], $meta);
        $this->fill($config, $after['attributes'] ?? [], $this->secretsOf($after), $after['secrets_cleared'] ?? []);
        $config->updated_by = $request->maker_id;
        $this->guardActivation($config);
        $config->save();
        $this->syncDefaults($config->channel);

        $request->update(['subject_key' => self::subjectKey($config->id)]);
        $this->audit($request, 'create', $config, null, $this->snapshot($config->fresh()));
    }

    private function applyUpdate(DualControlRequest $request, array $after): void
    {
        $config = $this->find($after);
        $before = $this->snapshot($config);

        $this->fill($config, $after['attributes'] ?? [], $this->secretsOf($after), $after['secrets_cleared'] ?? []);
        $config->updated_by = $request->maker_id;
        $this->guardActivation($config);
        $config->save();
        $this->syncDefaults($config->channel);

        $this->audit($request, 'update', $config, $before, $this->snapshot($config->fresh()) + [
            'secrets_changed' => $after['secrets_changed'] ?? [],
            'secrets_cleared' => $after['secrets_cleared'] ?? [],
        ]);
    }

    private function applyStatus(DualControlRequest $request, array $after): void
    {
        $config = $this->find($after);
        $before = ['is_active' => $config->is_active];

        $config->is_active = (bool) ($after['is_active'] ?? false);
        $config->updated_by = $request->maker_id;
        $this->guardActivation($config);
        $config->save();
        $this->syncDefaults($config->channel);

        $this->audit($request, 'update', $config, $before, ['is_active' => $config->is_active]);
    }

    private function applyPriority(DualControlRequest $request, array $after): void
    {
        $ids = $after['ids'] ?? [];
        $channel = $after['channel'];

        DB::transaction(function () use ($ids, $channel, $request) {
            foreach (array_values($ids) as $i => $id) {
                IntegrationConfig::where('id', $id)->where('channel', $channel)->update(['sort_order' => $i + 1, 'updated_by' => $request->maker_id]);
            }
            $this->syncDefaults($channel);
        });

        AuditLog::createEntry($request->maker_id, 'update', self::RESOURCE, $channel,
            ['order' => $request->payload_before['order'] ?? []], ['order' => $after['order'] ?? []], request()?->ip());
    }

    private function applyDelete(DualControlRequest $request, array $after): void
    {
        $config = $this->find($after);
        if ($config->is_active) {
            throw new DualControlException("{$config->name} is active; deactivate it before deleting.");
        }

        $before = $this->snapshot($config);
        $channel = $config->channel;
        $config->delete();
        $this->syncDefaults($channel);

        $this->audit($request, 'delete', $config, $before, null);
    }

    // ── Helpers ──────────────────────────────────────────────

    private function submit(string $operation, ?IntegrationConfig $config, ?array $before, array $after): DualControlRequest
    {
        return $this->dualControl->submit($operation, self::RESOURCE, 0, ['before' => $before, 'after' => $after], [
            'subject_key' => $config ? self::subjectKey($config->id) : null,
            'sealed' => isset($after['secret_values']) ? ['secret_values'] : [],
        ]);
    }

    private function find(array $after): IntegrationConfig
    {
        return IntegrationConfig::find($after['config_id'] ?? null)
            ?? throw new DualControlException('This integration no longer exists.');
    }

    private function meta(string $provider): array
    {
        return IntegrationCatalog::provider($provider)
            ?? throw new InvalidArgumentException("Unknown integration provider [{$provider}].");
    }

    /**
     * Split form input into non-secret attributes, new secret values and secrets to clear.
     * Blank secrets mean "keep the stored value".
     *
     * @return array{0: array<string, mixed>, 1: array<string, string>, 2: string[]}
     */
    private function split(array $meta, array $input): array
    {
        $attributes = [];
        foreach (self::EDITABLE_COLUMNS as $column) {
            if (array_key_exists($column, $input)) {
                $attributes[$column] = $input[$column];
            }
        }

        $secrets = [];
        foreach ($meta['fields'] as $field => $f) {
            $value = $input['fields'][$field] ?? null;
            if ($f['secret'] ?? false) {
                if (filled($value)) {
                    $secrets[$field] = (string) $value;
                }
            } elseif (array_key_exists($field, $input['fields'] ?? [])) {
                $attributes[$field] = filled($value) ? (string) $value : null;
            }
        }

        $secretFields = array_keys(array_filter($meta['fields'], fn ($f) => $f['secret'] ?? false));
        $clear = array_values(array_diff(array_intersect($input['clear'] ?? [], $secretFields), array_keys($secrets)));

        return [$attributes, $secrets, $clear];
    }

    private function payload(string $provider, array $meta, array $attributes, array $secrets, array $clear): array
    {
        $label = fn ($field) => $meta['fields'][$field]['label'] ?? $field;

        return array_filter([
            'provider' => $provider,
            'channel' => $meta['channel'],
            'attributes' => $attributes,
            'secrets_changed' => array_map($label, array_keys($secrets)),
            'secrets_cleared' => $clear,
            // Sealed by DualControlService: encrypted in the request, decrypted only when applied
            'secret_values' => $secrets ? json_encode($secrets) : null,
        ], fn ($v) => $v !== null);
    }

    private function secretsOf(array $after): array
    {
        return isset($after['secret_values']) ? (json_decode($after['secret_values'], true) ?: []) : [];
    }

    private function fill(IntegrationConfig $config, array $attributes, array $secrets, array $clear): void
    {
        $extra = $config->extra_credentials ?? [];

        foreach ($attributes + $secrets as $field => $value) {
            if (str_starts_with($field, 'extra.')) {
                $key = substr($field, 6);
                if ($value === null) {
                    unset($extra[$key]);
                } else {
                    $extra[$key] = $value;
                }
            } elseif ($field === 'is_active') {
                $config->is_active = (bool) $value;
            } else {
                $config->{$field} = $value;
            }
        }
        foreach ($clear as $field) {
            if (str_starts_with($field, 'extra.')) {
                unset($extra[substr($field, 6)]);
            } else {
                $config->{$field} = null;
            }
        }

        $config->extra_credentials = $extra ?: null;
        if (!$config->base_url && ($config->production_url || $config->sandbox_url)) {
            $config->base_url = $config->production_url ?: $config->sandbox_url;
        }
    }

    private function newConfig(string $provider, array $meta): IntegrationConfig
    {
        $code = $meta['code'];
        for ($i = 2; IntegrationConfig::where('code', $code)->exists(); $i++) {
            $code = $meta['code'] . '_' . $i;
        }

        $defaults = [];
        foreach ($meta['fields'] as $field => $f) {
            if (isset($f['default']) && str_starts_with($field, 'extra.')) {
                $defaults[substr($field, 6)] = $f['default'];
            }
        }

        return new IntegrationConfig([
            'code' => $code,
            'name' => $meta['label'],
            'provider' => $provider,
            'channel' => $meta['channel'],
            'environment' => 'production',
            'sandbox_url' => $meta['urls']['sandbox'] ?? null,
            'production_url' => $meta['urls']['production'] ?? null,
            'base_url' => $meta['urls']['production'] ?? null,
            'extra_credentials' => $defaults ?: null,
            'is_active' => false,
            'is_default' => false,
            'sort_order' => (int) IntegrationConfig::where('channel', $meta['channel'])->max('sort_order') + 1,
            'country_code' => 'TZ',
        ]);
    }

    /** An incomplete config is saved but never left active. */
    private function guardActivation(IntegrationConfig $config): void
    {
        if ($config->is_active && !IntegrationCatalog::isComplete($config)) {
            throw new DualControlException("{$config->name} can't be activated yet — missing: " . implode(', ', IntegrationCatalog::missing($config)) . '.');
        }
    }

    /** is_default mirrors "priority 1" so older lookups keep working. */
    private function syncDefaults(string $channel): void
    {
        $rows = IntegrationConfig::where('channel', $channel)->byPriority()->get(['id']);
        foreach ($rows as $i => $row) {
            IntegrationConfig::where('id', $row->id)->update(['sort_order' => $i + 1, 'is_default' => $i === 0]);
        }
    }

    /** Non-secret view of a config: values for plain fields, set / not set for secrets. */
    public function snapshot(IntegrationConfig $config): array
    {
        $meta = IntegrationCatalog::provider($config->provider) ?? ['fields' => []];
        $fields = [];
        foreach ($meta['fields'] as $field => $f) {
            $fields[$f['label']] = ($f['secret'] ?? false)
                ? (filled($config->fieldValue($field)) ? 'set' : 'not set')
                : $config->fieldValue($field);
        }

        return [
            'name' => $config->name,
            'provider' => $config->provider,
            'environment' => $config->environment,
            'production_url' => $config->production_url,
            'sandbox_url' => $config->sandbox_url,
            'is_active' => $config->is_active,
            'priority' => $config->sort_order,
            'fields' => $fields,
        ];
    }

    private function audit(DualControlRequest $request, string $action, IntegrationConfig $config, ?array $before, ?array $after): void
    {
        AuditLog::createEntry($request->maker_id, $action, self::RESOURCE, $config->id, $before, $after, request()?->ip());
    }
}
