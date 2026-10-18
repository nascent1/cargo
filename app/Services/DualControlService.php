<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\DualControlConfig;
use App\Models\DualControlRequest;
use App\Models\SystemConfig;
use App\Models\User;
use App\Services\DualControl\DualControlException;
use App\Services\DualControl\DualControlRegistry;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Dual Control (Maker-Checker) Service per SRS §5.6
 *
 * A change is submitted as a request. When its rule (dual_control_configs) is off, it is approved
 * automatically; otherwise a checker approves it. Either way the request records who, what and when.
 *
 * Timing (config/dual_control.php):
 *  - the maker may choose when the change takes effect (commence_at). Approved before that moment,
 *    "scheduled" changes wait for dual-control:process; "dated" records (fares, exchange rates) are
 *    written straight away with that start. Approved after it, the change applies at approval and
 *    shows as commenced late.
 *  - a record has at most one open (pending or scheduled) change at a time (subject_key).
 */
class DualControlService
{
    public const HEARTBEAT_KEY = 'dual_control:heartbeat';

    private const MASKED_KEYS = ['password', 'password_confirmation', 'plain_password', 'token', 'pin', 'secret'];

    public function requiresDualControl(string $operation): bool
    {
        $config = DualControlConfig::where('operation_type', $operation)->first();
        if ($config) {
            return (bool) $config->requires_dual_control;
        }

        // Not registered yet: the registry default, or approval required for anything unknown
        if (!DualControlRegistry::has($operation)) {
            Log::warning("Dual control: operation '{$operation}' has no rule and is not in the registry — approval required.");
        }

        return (bool) DualControlRegistry::get($operation)['default'];
    }

    /**
     * Options:
     *  - commence_at: when the change should take effect (null or past = as soon as approved)
     *  - sealed:      dot-paths in payload['after'] holding secrets; stored encrypted, never shown
     *  - subject_key: what the change is about when it isn't one record (defaults to "type:id")
     *  - require_approval: wait for a checker even when the rule is off (e.g. a clerk's own shift extension)
     */
    public function submit(string $operation, string $resourceType, int $resourceId, array $payload, array $options = []): DualControlRequest
    {
        return DB::transaction(function () use ($operation, $resourceType, $resourceId, $payload, $options) {
            $requiresDC = !empty($options['require_approval']) || $this->requiresDualControl($operation);
            $makerId = Auth::id() ?? ($payload['after']['actor_id'] ?? 1);

            $commenceAt = $options['commence_at'] ?? null;
            if ($commenceAt && (!DualControlRegistry::canSchedule($operation) || !$commenceAt->isFuture())) {
                $commenceAt = null;
            }

            $subjectKey = $options['subject_key'] ?? ($resourceId > 0 ? "{$resourceType}:{$resourceId}" : null);
            if ($subjectKey && ($open = $this->openChangeFor($subjectKey))) {
                throw new DualControlException($this->openChangeMessage($open));
            }

            $request = DualControlRequest::create([
                'maker_id' => $makerId,
                'operation_type' => $operation,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'subject_key' => $subjectKey,
                'payload_before' => $this->scrub($payload['before'] ?? null),
                'payload_after' => $this->scrub($this->seal($payload['after'] ?? null, $options['sealed'] ?? [])),
                'commence_at' => $commenceAt,
                'status' => 'pending',
                'expires_at' => $this->expiryFor($commenceAt),
            ]);

            AuditLog::createEntry($makerId, 'create', 'dual_control_request', $request->id, null, $request->toArray(), request()->ip());

            if ($requiresDC) {
                DB::afterCommit(fn () => $this->notifyCheckers($request));
            } else {
                $request->update([
                    'checker_id' => $makerId,
                    'checker_comment' => 'Auto-approved by system configuration.',
                    'resolved_at' => now(),
                ]);
                $this->dispatch($request);
            }

            return $request->fresh();
        });
    }

    public function approve(DualControlRequest $request, ?string $comment = null): DualControlRequest
    {
        abort_if($request->maker_id === Auth::id(), 403, 'Maker cannot approve their own request.');
        abort_if($request->status !== 'pending', 422, 'Request is not pending.');
        abort_if($request->isExpired(), 422, 'Request has expired.');

        $request = DB::transaction(function () use ($request, $comment) {
            $before = $request->toArray();
            $request->update([
                'checker_id' => Auth::id(),
                'checker_comment' => $comment,
                'resolved_at' => now(),
            ]);

            $this->dispatch($request);

            AuditLog::createEntry(Auth::id(), 'update', 'dual_control_request', $request->id, $before, $request->fresh()->toArray(), request()->ip());

            return $request->fresh();
        });

        $this->notifyMaker($request, $request->status === 'scheduled'
            ? 'Approved — takes effect ' . $request->commence_at->format('d M Y H:i')
            : 'Approved and applied');

        return $request;
    }

    public function reject(DualControlRequest $request, string $comment): DualControlRequest
    {
        abort_if($request->maker_id === Auth::id(), 403, 'Maker cannot reject their own request.');
        abort_if($request->status !== 'pending', 422, 'Request is not pending.');

        $request = DB::transaction(function () use ($request, $comment) {
            $before = $request->toArray();
            $request->update([
                'checker_id' => Auth::id(),
                'status' => 'rejected',
                'checker_comment' => $comment,
                'resolved_at' => now(),
            ]);

            AuditLog::createEntry(Auth::id(), 'update', 'dual_control_request', $request->id, $before, $request->fresh()->toArray(), request()->ip());

            return $request->fresh();
        });

        $this->notifyMaker($request, 'Rejected: ' . $comment);

        return $request;
    }

    /**
     * Withdraw a pending request or a scheduled change before it runs. The maker or a checker may do this.
     */
    public function cancel(DualControlRequest $request, ?string $reason = null): DualControlRequest
    {
        abort_unless(in_array($request->status, ['pending', 'scheduled'], true), 422, 'Only pending or scheduled requests can be cancelled.');
        $user = Auth::user();
        abort_unless($user && ($request->maker_id === $user->id || $user->hasPermission('dc:approve')), 403, 'Only the maker or a checker can cancel this request.');

        $request = DB::transaction(function () use ($request, $reason) {
            $before = $request->toArray();
            $request->update([
                'status' => 'cancelled',
                'resolved_at' => now(),
                'failure_reason' => $reason ? 'Cancelled: ' . $reason : null,
            ]);
            AuditLog::createEntry(Auth::id(), 'update', 'dual_control_request', $request->id, $before, $request->fresh()->toArray(), request()->ip());

            return $request->fresh();
        });

        if ($request->maker_id !== Auth::id()) {
            $this->notifyMaker($request, 'Cancelled' . ($reason ? ': ' . $reason : ''));
        }

        return $request;
    }

    /**
     * Scheduler tick: apply scheduled changes that are due, expire overdue pending requests.
     *
     * @return array{applied: int, failed: int, expired: int}
     */
    public function processDue(): array
    {
        $applied = $failed = 0;

        $due = DualControlRequest::where('status', 'scheduled')->where('commence_at', '<=', now())->orderBy('commence_at')->get();
        foreach ($due as $request) {
            try {
                DB::transaction(fn () => $this->apply($request));
                $applied++;
                $this->notifyMaker($request->fresh(), 'Applied as scheduled');
            } catch (\Throwable $e) {
                $failed++;
                $reason = $e instanceof DualControlException ? $e->getMessage() : 'Could not be applied: ' . $e->getMessage();
                $request->update(['status' => 'failed', 'failure_reason' => $reason]);
                AuditLog::createEntry(null, 'update', 'dual_control_request', $request->id, null, ['status' => 'failed', 'failure_reason' => $reason], null);
                $this->notifyMaker($request, 'Failed: ' . $reason);
                if ($request->checker_id && $request->checker_id !== $request->maker_id) {
                    $this->notify($request->checker_id, $request, 'Scheduled change failed', $reason);
                }
                if (!$e instanceof DualControlException) {
                    report($e);
                }
            }
        }

        $expired = $this->expireStaleRequests();

        // Approval SMS that were bundled during a checker's 10-minute window go out as one summary
        try {
            app(SmsNotifier::class)->sendBundledApprovals();
        } catch (\Throwable $e) {
            report($e);
        }

        Cache::forever(self::HEARTBEAT_KEY, now()->toIso8601String());

        return ['applied' => $applied, 'failed' => $failed, 'expired' => $expired];
    }

    public function expireStaleRequests(): int
    {
        $stale = DualControlRequest::where('status', 'pending')->where('expires_at', '<', now())->get();
        foreach ($stale as $request) {
            $request->update(['status' => 'expired', 'resolved_at' => now()]);
            $this->notifyMaker($request, 'Expired without approval');
        }

        return $stale->count();
    }

    /**
     * True when dual-control:process ran recently (scheduled changes are being applied).
     */
    public static function schedulerAlive(): bool
    {
        $last = Cache::get(self::HEARTBEAT_KEY);

        return $last && abs(now()->diffInMinutes(\Carbon\Carbon::parse($last))) <= (int) config('dual_control.heartbeat_minutes', 5);
    }

    public function openChangeFor(string $subjectKey): ?DualControlRequest
    {
        return DualControlRequest::with('maker')
            ->where('subject_key', $subjectKey)
            ->whereIn('status', ['pending', 'scheduled'])
            ->where(fn ($q) => $q->where('status', 'scheduled')->orWhere('expires_at', '>', now()))
            ->latest('id')
            ->first();
    }

    public function openChangeMessage(DualControlRequest $open): string
    {
        $who = $open->maker?->full_name ?: 'another user';
        $what = $open->status === 'scheduled'
            ? 'a change scheduled for ' . $open->commence_at->format('d M Y H:i')
            : 'a change awaiting approval';

        return "This record already has {$what} (#DC-{$open->id}, by {$who}). Cancel it or wait for it before submitting another.";
    }

    /**
     * What to tell the maker after submit().
     */
    public function outcomeMessage(DualControlRequest $request, string $appliedMessage): string
    {
        $when = $request->commence_at?->format('d M Y H:i');

        return match ($request->status) {
            'pending' => "Submitted for approval (#DC-{$request->id})" . ($when ? " — takes effect {$when} once approved." : ' — applies as soon as it is approved.'),
            'scheduled' => "Scheduled (#DC-{$request->id}) — takes effect {$when}.",
            default => $appliedMessage . ($when && $request->commence_at->isFuture() ? " It takes effect {$when}." : ''),
        };
    }

    /**
     * Decrypt sealed values (secrets) in a payload, for the handler that applies it.
     */
    public static function unseal(?array $data): ?array
    {
        if (!$data) {
            return $data;
        }
        foreach ($data as $key => $value) {
            if (self::isSealed($value)) {
                $data[$key] = Crypt::decryptString($value['__sealed']);
            } elseif (is_array($value)) {
                $data[$key] = self::unseal($value);
            }
        }

        return $data;
    }

    public static function isSealed(mixed $value): bool
    {
        return is_array($value) && array_key_exists('__sealed', $value);
    }

    /**
     * Approved: apply now, or leave it for the scheduler when it takes effect later.
     */
    private function dispatch(DualControlRequest $request): void
    {
        $timing = DualControlRegistry::timing($request->operation_type);

        if ($timing === 'scheduled' && $request->commence_at?->isFuture()) {
            $request->update(['status' => 'scheduled']);

            return;
        }

        $this->apply($request);
    }

    private function apply(DualControlRequest $request): void
    {
        $request->refresh();
        $effectiveAt = $request->commence_at && $request->commence_at->isFuture() ? $request->commence_at : now();

        $handler = DualControlRegistry::handler($request->operation_type);
        if ($handler) {
            app($handler)->execute($request, $effectiveAt);
        } else {
            $this->executeLegacy($request);
        }

        $request->update(['status' => 'approved', 'executed_at' => now(), 'failure_reason' => null]);
    }

    private function expiryFor(?CarbonInterface $commenceAt): CarbonInterface
    {
        $hours = (int) SystemConfig::getValue(config('dual_control.expiry_hours_key', 'dc_expiry_hours'), 48);

        return ($commenceAt ? $commenceAt->copy() : now())->addHours($hours);
    }

    private function seal(?array $after, array $paths): ?array
    {
        foreach ($paths as $path) {
            $value = data_get($after, $path);
            if ($value !== null && $value !== '' && !is_array($value)) {
                data_set($after, $path, ['__sealed' => Crypt::encryptString((string) $value)]);
            }
        }

        return $after;
    }

    /**
     * Mask secrets that weren't sealed so they never sit in plain text in a request or the audit log.
     */
    private function scrub(?array $data): ?array
    {
        if (!$data) {
            return $data;
        }
        foreach ($data as $key => $value) {
            if (self::isSealed($value)) {
                continue;
            }
            if (is_array($value)) {
                $data[$key] = $this->scrub($value);
            } elseif (is_string($key) && $this->isSecretKey($key) && $value !== null && $value !== '') {
                $data[$key] = '********';
            }
        }

        return $data;
    }

    private function isSecretKey(string $key): bool
    {
        $key = strtolower($key);

        return in_array($key, self::MASKED_KEYS, true)
            || str_contains($key, 'password') || str_contains($key, 'secret') || str_ends_with($key, 'api_key') || str_ends_with($key, '_token');
    }

    private function notifyCheckers(DualControlRequest $request): void
    {
        $label = DualControlRegistry::label($request->operation_type);
        $maker = $request->maker?->full_name ?: 'A user';
        $when = $request->commence_at ? ' (takes effect ' . $request->commence_at->format('d M Y H:i') . ')' : '';

        $notifier = app(SmsNotifier::class);
        User::where('status', 'active')->where('id', '!=', $request->maker_id)
            ->where(fn ($q) => $q->whereNull('domain')->orWhere('domain', '!=', 'agent'))->get()
            ->filter(fn (User $u) => $u->hasPermission('dc:approve'))
            ->each(function (User $u) use ($request, $label, $maker, $when, $notifier) {
                $this->notify($u->id, $request, "Approval needed: {$label}", "{$maker} submitted #DC-{$request->id}{$when}.");
                // SMS too (SMS policy: dual_control_request; one per checker per 10 minutes, later ones bundled)
                try {
                    $notifier->approvalNeeded($u, $request);
                } catch (\Throwable $e) {
                    report($e);
                }
            });
    }

    private function notifyMaker(DualControlRequest $request, string $what): void
    {
        $this->notify($request->maker_id, $request, DualControlRegistry::label($request->operation_type) . " — #DC-{$request->id}", $what);
    }

    private function notify(?int $userId, DualControlRequest $request, string $title, string $body): void
    {
        $user = $userId ? User::find($userId) : null;
        if (!$user) {
            return;
        }
        try {
            app(NotificationService::class)->send($user, 'dual_control', 'in_app', $title, $body, [
                'url' => route('admin.dual-control.show', $request->id, false),
                'request_id' => $request->id,
            ]);
        } catch (\Throwable $e) {
            report($e); // a notification failure must never undo the change
        }
    }

    private function resolveModelClass(string $resourceType): ?string
    {
        $map = [
            'user' => \App\Models\User::class,
            'ferry' => \App\Models\Ferry::class,
            'port' => \App\Models\Port::class,
            'route' => \App\Models\Route::class,
            'trip' => \App\Models\Trip::class,
            'schedule' => \App\Models\Schedule::class,
            'fare' => \App\Models\Fare::class,
            'lookup' => \App\Models\Lookup::class,
            'menu' => \App\Models\Menu::class,
            'system_config' => \App\Models\SystemConfig::class,
            'translation' => \App\Models\PortalTranslation::class,
            'agent_account' => \App\Models\AgentAccount::class,
            'agent' => \App\Models\Agent::class,
            'associate_agent' => \App\Models\AssociateAgent::class,
            'discount' => \App\Models\PriceDiscount::class,
            'promotion' => \App\Models\Promotion::class,
            'coupon' => \App\Models\Coupon::class,
            'ferry_class' => \App\Models\FerryClass::class,
            'station' => \App\Models\Station::class,
            'cargo_station' => \App\Models\Station::class,
            'cargo_route' => \App\Models\Route::class,
            'cargo_transport_unit' => \App\Models\TransportUnit::class,
            'cargo_schedule' => \App\Models\TransportService::class,
        ];
        return $map[$resourceType] ?? null;
    }

    /**
     * The original executor, kept for operations that have no handler in config/dual_control.php
     * (lookups, users, agents and the legacy ferry actions).
     */
    private function executeLegacy(DualControlRequest $request): void
    {
        if ($request->operation_type === 'float_topup' || $request->operation_type === 'agent_topup') {
            $account = \App\Models\AgentAccount::find($request->resource_id);
            if ($account) {
                app(\App\Services\FloatManagementService::class)->executeTopup(
                    $account,
                    (float) ($request->payload_after['amount'] ?? 0),
                    $request->payload_after['reference'] ?? 'DC-TOPUP-' . $request->id,
                    $request->maker_id,
                    $request->checker_id
                );
            }
            return;
        } elseif ($request->operation_type === 'agent_adjust_limit') {
            $account = \App\Models\AgentAccount::find($request->resource_id);
            if ($account) {
                app(\App\Services\FloatManagementService::class)->updateCreditLimit(
                    $account,
                    (float) ($request->payload_after['credit_limit'] ?? 0),
                    $request->maker_id
                );
            }
            return;
        } elseif ($request->operation_type === 'agent_create') {
            DB::transaction(function () use ($request) {
                $payload = $request->payload_after;
                $plainPassword = \Illuminate\Support\Str::password(10, true, true, false, false);
                $user = \App\Models\User::create([
                    'email'      => $payload['email'],
                    'phone'      => $payload['contact_mobile'] ?? null,
                    'password'   => \Illuminate\Support\Facades\Hash::make($plainPassword),
                    'password_changed_at' => null,
                    'first_name' => $payload['first_name'],
                    'last_name'  => $payload['last_name'],
                    'domain'     => 'agent',
                    'status'     => 'active',
                ]);

                $latestId = \App\Models\Agent::max('id') ?? 0;
                $agentCode = 'AGT-' . str_pad($latestId + 1, 4, '0', STR_PAD_LEFT);

                $agent = \App\Models\Agent::create([
                    'user_id'           => $user->id,
                    'agent_code'        => $agentCode,
                    'agency_name'       => $payload['agency_name'],
                    'contact_mobile'    => $payload['contact_mobile'] ?? null,
                    'email'             => $payload['email'],
                    'address'           => $payload['address'] ?? null,
                    'agent_type'        => $payload['agent_type'],
                    'is_active'         => true,
                    'commission_method' => !empty($payload['commission_method']) ? $payload['commission_method'] : null,
                    'commission_rate'   => !empty($payload['commission_rate']) ? (float)$payload['commission_rate'] : null,
                ]);

                \App\Models\AgentAccount::create([
                    'agent_id'                 => $agent->id,
                    'account_number'           => 'ACC-' . $agentCode,
                    'currency'                 => 'TZS',
                    'balance'                  => 0.0000,
                    'security_deposit_balance' => 0.0000,
                    'credit_limit'             => round((float) ($payload['credit_limit'] ?? 0), 4),
                    'is_active'                => true,
                ]);

                $user->assignRole('agent');
                $request->update(['resource_id' => $agent->id]);

                // Login details by SMS (SMS policy: account_credentials) and email, after commit
                \App\Notifications\AccountCredentialsNotification::sendAfterCommit($user, $plainPassword);
            });
            return;
        } elseif ($request->operation_type === 'ferry_create') {
            DB::transaction(function () use ($request) {
                $payload = $request->payload_after;
                $ferry = \App\Models\Ferry::create([
                    'name' => $payload['name'],
                    'type' => $payload['type'] ?? null,
                    'port_of_registry' => $payload['port_of_registry'] ?? null,
                    'capacity' => $payload['capacity'],
                    'status' => $payload['status'] ?? 'active',
                    'has_layout' => $payload['has_layout'] ?? false,
                    'layout_image_path' => $payload['layout_image_path'] ?? null,
                    'seat_layout_json' => $payload['seat_layout_json'] ?? null,
                ]);

                if (isset($payload['classes']) && is_array($payload['classes'])) {
                    foreach ($payload['classes'] as $index => $classData) {
                        $cleanName = ucwords(trim(preg_replace('/class$/i', '', $classData['name'])));
                        $travelClass = \App\Models\TravelClass::firstOrCreate(
                            ['name' => $cleanName],
                            ['status' => 'active', 'color' => ['#FFD700', '#C0C0C0', '#CD7F32', '#4169E1', '#2E8B57', '#FF6347', '#8A2BE2'][rand(0, 6)]]
                        );

                        $ferryClass = \App\Models\FerryClass::create([
                            'ferry_id' => $ferry->id,
                            'class_id' => $travelClass->id,
                            'no_seats' => $classData['capacity'],
                            'is_room_based' => $classData['is_room_based'] ?? false,
                            'sort_order' => $index,
                        ]);

                        if (!empty($classData['is_room_based']) && !empty($classData['rooms'])) {
                            foreach ($classData['rooms'] as $room) {
                                \App\Models\FerryClassRoom::create([
                                    'ferry_class_id' => $ferryClass->id,
                                    'name' => $room['name'],
                                    'capacity' => $room['capacity'],
                                    'status' => 'active',
                                ]);
                            }
                        }
                    }
                }

                if (isset($payload['seat_layout_json']) && $payload['seat_layout_json'] === 'processing') {
                    // For v2 layouts, dispatch one job per deck
                    $layoutJson = $ferry->seat_layout_json;
                    if (isset($layoutJson['decks']) && is_array($layoutJson['decks'])) {
                        foreach (array_keys($layoutJson['decks']) as $deckIndex) {
                            \App\Jobs\ProcessFerryLayoutJob::dispatch($ferry, $deckIndex);
                        }
                    } else {
                        // v1 legacy single-deck dispatch
                        \App\Jobs\ProcessFerryLayoutJob::dispatch($ferry);
                    }
                }

                $request->update(['resource_id' => $ferry->id]);
            });
            return;
        } elseif ($request->operation_type === 'cargo_transport_unit_create') {
            DB::transaction(function () use ($request) {
                $payload = $request->payload_after;
                $hasCargoDeck = $payload['has_cargo_deck'] ?? false;
                
                $unit = \App\Models\TransportUnit::create(\Illuminate\Support\Arr::except($payload, ['capacity_lane_meters', 'capacity_weight_kg', 'capacity_volume_cbm']));
                
                if ($hasCargoDeck) {
                    $unit->cargoCapacities()->create([
                        'total_lane_meters' => $payload['capacity_lane_meters'] ?? 0,
                        'total_weight_capacity_kg' => $payload['capacity_weight_kg'] ?? 0,
                        'total_volume_capacity_cbm' => $payload['capacity_volume_cbm'] ?? 0,
                    ]);
                }
                $request->update(['resource_id' => $unit->id]);
            });
            return;
        } elseif ($request->operation_type === 'cargo_schedule_create') {
            DB::transaction(function () use ($request) {
                $payload = $request->payload_after;
                $service = \App\Models\TransportService::create($payload);
                
                // Auto-create inventory based on TransportUnit capacity
                $unit = \App\Models\TransportUnit::with('cargoCapacities')->find($payload['transport_unit_id']);
                if ($unit && $unit->cargoCapacities->isNotEmpty()) {
                    $cap = $unit->cargoCapacities->first();
                    \App\Models\CargoInventory::create([
                        'transport_service_id' => $service->id,
                        'total_lane_meters' => $cap->total_lane_meters,
                        'available_lane_meters' => $cap->total_lane_meters,
                        'total_weight_capacity_kg' => $cap->total_weight_capacity_kg,
                        'available_weight_capacity_kg' => $cap->total_weight_capacity_kg,
                        'total_volume_capacity_cbm' => $cap->total_volume_capacity_cbm,
                        'available_volume_capacity_cbm' => $cap->total_volume_capacity_cbm,
                    ]);
                }
                
                $request->update(['resource_id' => $service->id]);
            });
            return;
        } elseif ($request->resource_type === 'lookup') {
            $table = $request->payload_after['_table'] ?? null;
            if (!$table) return;
            $payload = $request->payload_after;
            unset($payload['_table']);

            if ($request->operation_type === 'lookup_create') {
                DB::table($table)->insert(array_merge($payload, ['created_at' => now(), 'updated_at' => now()]));
            } elseif ($request->operation_type === 'lookup_update') {
                DB::table($table)->where('id', $request->resource_id)->update(array_merge($payload, ['updated_at' => now()]));
            } elseif ($request->operation_type === 'lookup_delete') {
                DB::table($table)->where('id', $request->resource_id)->delete();
            }
            return;
        } elseif ($request->operation_type === 'booking_complete') {
            $booking = app(\App\Services\FastBookingService::class)->executeBooking($request->payload_after, $request->maker_id);
            $request->update(['resource_id' => $booking->id]);
            return;
        } elseif ($request->operation_type === 'capacity_update') {
            foreach ($request->payload_after as $invData) {
                $inventory = \App\Models\SeatInventory::findOrFail($invData['id']);
                $inventory->update([
                    'total_seats' => $invData['total_seats']
                ]);
                
                if (isset($invData['allocations'])) {
                    // Sync allocations
                    $inventory->allocations()->delete();
                    foreach ($invData['allocations'] as $allocation) {
                        if (!empty($allocation['agent_id']) && $allocation['allocated_seats'] > 0) {
                            $inventory->allocations()->create([
                                'agent_id' => $allocation['agent_id'],
                                'allocated_seats' => $allocation['allocated_seats']
                            ]);
                        }
                    }
                }
            }
            return;
        }

        $modelClass = $this->resolveModelClass($request->resource_type);
        if (!$modelClass) return;

        if (str_ends_with($request->operation_type, '_create')) {
            $payload = $request->payload_after;
            $roles = $payload['roles'] ?? [];
            $employeeId = $payload['employee_id'] ?? null;
            $department = $payload['department'] ?? null;
            $position = $payload['position'] ?? null;
            
            $plainPassword = null;
            if ($modelClass === \App\Models\User::class) {
                $plainPassword = \Illuminate\Support\Str::password(10, true, true, false, false);
                $payload['password'] = \Illuminate\Support\Facades\Hash::make($plainPassword);
                $payload['password_changed_at'] = null; // force change on login
            }
            
            unset($payload['roles'], $payload['employee_id'], $payload['department'], $payload['position'], $payload['plain_password']);

            $model = $modelClass::create($payload);
            $request->update(['resource_id' => $model->id]);

            if (!empty($roles) && method_exists($model, 'syncRoles')) {
                $model->syncRoles($roles);
            }

            if ($model instanceof \App\Models\User && $model->domain === 'system_user') {
                \App\Models\SystemUser::firstOrCreate(['user_id' => $model->id], [
                    'employee_id' => $employeeId ?: ('EMP-' . str_pad($model->id, 4, '0', STR_PAD_LEFT)),
                    'department' => $department ?: 'General Operations',
                    'position' => $position ?: 'Staff Officer',
                    'status' => 'active',
                ]);
            }

            if ($model instanceof \App\Models\User && !empty($plainPassword)) {
                \App\Notifications\AccountCredentialsNotification::sendAfterCommit($model, $plainPassword);
            }

            if ($request->operation_type === 'promotion_create') {
                $redemptionMethod = $payload['redemption_method'] ?? 'none';
                if ($redemptionMethod === 'single' && !empty($payload['single_code'])) {
                    \App\Models\Coupon::create([
                        'promotion_id' => $model->id,
                        'code' => strtoupper(trim($payload['single_code'])),
                        'max_uses' => $model->max_uses ?? 0,
                        'valid_from' => $model->start_date,
                        'valid_to' => $model->end_date,
                        'is_active' => true,
                    ]);
                } elseif ($redemptionMethod === 'multiple' && !empty($payload['multiple_count'])) {
                    $count = (int) $payload['multiple_count'];
                    $prefix = substr(strtoupper(preg_replace('/[^A-Z0-9]/', '', $model->name)), 0, 4);
                    if (empty($prefix)) $prefix = 'PRM';
                    $insertData = [];
                    for ($i = 0; $i < $count; $i++) {
                        $insertData[] = [
                            'promotion_id' => $model->id,
                            'code' => $prefix . '-' . strtoupper(\Illuminate\Support\Str::random(6)),
                            'max_uses' => 1,
                            'valid_from' => $model->start_date,
                            'valid_to' => $model->end_date,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    \App\Models\Coupon::insert($insertData);
                }
            } elseif ($request->operation_type === 'class_create') {
                if ($model->is_room_based && !empty($request->payload_after['rooms'])) {
                    foreach ($request->payload_after['rooms'] as $room) {
                        \App\Models\FerryClassRoom::create([
                            'ferry_class_id' => $model->id,
                            'name' => $room['name'],
                            'capacity' => $room['capacity'],
                            'status' => 'active',
                        ]);
                    }
                }
            }
        } elseif (str_ends_with($request->operation_type, '_update') || str_ends_with($request->operation_type, 'status_update') || str_ends_with($request->operation_type, 'roles_update')) {
            $model = $modelClass::find($request->resource_id);
            if ($model) {
                if ($request->operation_type === 'user_roles_update') {
                    $model->syncRoles($request->payload_after['roles'] ?? []);
                } else {
                    $payload = $request->payload_after;
                    if (isset($payload['ferries']) && method_exists($model, 'ferries')) {
                        $model->ferries()->sync($payload['ferries']);
                        unset($payload['ferries']);
                    }
                    if (isset($payload['routes']) && method_exists($model, 'routes')) {
                        $model->routes()->sync($payload['routes']);
                        unset($payload['routes']);
                    }
                    if (isset($payload['agents']) && method_exists($model, 'agents')) {
                        $model->agents()->sync($payload['agents']);
                        unset($payload['agents']);
                    }

                    if ($request->operation_type === 'class_update') {
                        $rooms = $payload['rooms'] ?? [];
                        unset($payload['rooms']);
                        
                        $model->update($payload);

                        if ($model->is_room_based && !empty($rooms)) {
                            // Sync rooms
                            $existingRoomIds = [];
                            foreach ($rooms as $room) {
                                $ferryRoom = \App\Models\FerryClassRoom::updateOrCreate(
                                    ['ferry_class_id' => $model->id, 'name' => $room['name']],
                                    ['capacity' => $room['capacity'], 'status' => 'active']
                                );
                                $existingRoomIds[] = $ferryRoom->id;
                            }
                            \App\Models\FerryClassRoom::where('ferry_class_id', $model->id)
                                ->whereNotIn('id', $existingRoomIds)
                                ->update(['status' => 'inactive']);
                        } else {
                            \App\Models\FerryClassRoom::where('ferry_class_id', $model->id)->update(['status' => 'inactive']);
                        }
                    } else {
                        if (!empty($payload)) {
                            $model->update($payload);

                            if ($request->operation_type === 'ferry_update' && isset($payload['seat_layout_json']) && $payload['seat_layout_json'] === 'processing') {
                                $layoutJson = $model->seat_layout_json;
                                if (isset($layoutJson['decks']) && is_array($layoutJson['decks'])) {
                                    foreach (array_keys($layoutJson['decks']) as $deckIndex) {
                                        \App\Jobs\ProcessFerryLayoutJob::dispatch($model, $deckIndex);
                                    }
                                } else {
                                    \App\Jobs\ProcessFerryLayoutJob::dispatch($model);
                                }
                            }
                            
                            if ($request->operation_type === 'cargo_transport_unit_update' && $model->has_cargo_deck) {
                                $cap = $model->cargoCapacities()->first();
                                if ($cap) {
                                    $cap->update([
                                        'total_lane_meters' => $payload['capacity_lane_meters'] ?? 0,
                                        'total_weight_capacity_kg' => $payload['capacity_weight_kg'] ?? 0,
                                        'total_volume_capacity_cbm' => $payload['capacity_volume_cbm'] ?? 0,
                                    ]);
                                } else {
                                    $model->cargoCapacities()->create([
                                        'total_lane_meters' => $payload['capacity_lane_meters'] ?? 0,
                                        'total_weight_capacity_kg' => $payload['capacity_weight_kg'] ?? 0,
                                        'total_volume_capacity_cbm' => $payload['capacity_volume_cbm'] ?? 0,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        } elseif (str_ends_with($request->operation_type, '_delete')) {
            $model = $modelClass::find($request->resource_id);
            if ($model) {
                $model->delete();
            }
        }
    }
}
