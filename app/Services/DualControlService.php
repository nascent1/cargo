<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\DualControlRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Dual Control (Maker-Checker) Service per SRS §5.6
 */
class DualControlService
{
    public function requiresDualControl(string $operation): bool
    {
        $config = \App\Models\DualControlConfig::where('operation_type', $operation)->first();
        return $config ? (bool) $config->requires_dual_control : true;
    }

    public function submit(string $operation, string $resourceType, int $resourceId, array $payload): DualControlRequest
    {
        return DB::transaction(function () use ($operation, $resourceType, $resourceId, $payload) {
            $requiresDC = $this->requiresDualControl($operation);
            $makerId = Auth::id() ?? ($payload['after']['actor_id'] ?? 1);

            $scrubber = function (?array $data) use (&$scrubber) {
                if (!$data) return null;
                $keys = ['password', 'password_confirmation', 'plain_password', 'token', 'pin', 'secret'];
                foreach ($data as $k => $v) {
                    if (in_array(strtolower($k), $keys)) $data[$k] = '********';
                    elseif (is_array($v)) $data[$k] = $scrubber($v);
                }
                return $data;
            };

            $request = DualControlRequest::create([
                'maker_id' => $makerId,
                'operation_type' => $operation,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'payload_before' => $scrubber($payload['before'] ?? null),
                'payload_after' => $scrubber($payload['after'] ?? null),
                'status' => $requiresDC ? 'pending' : 'approved',
                'checker_id' => $requiresDC ? null : $makerId,
                'checker_comment' => $requiresDC ? null : 'Auto-approved by system configuration.',
                'resolved_at' => $requiresDC ? null : now(),
                'expires_at' => now()->addHours((int) \App\Models\SystemConfig::getValue('dc_expiry_hours', 48)),
            ]);

            AuditLog::createEntry($makerId, 'create', 'dual_control_request', $request->id, null, $request->toArray(), request()->ip());

            if (!$requiresDC) {
                $this->executeApproval($request);
            }

            return $request;
        });
    }

    public function approve(DualControlRequest $request, ?string $comment = null): DualControlRequest
    {
        abort_if($request->maker_id === Auth::id(), 403, 'Maker cannot approve their own request.');
        abort_if($request->status !== 'pending', 422, 'Request is not pending.');
        abort_if($request->isExpired(), 422, 'Request has expired.');

        return DB::transaction(function () use ($request, $comment) {
            $before = $request->toArray();
            $request->update([
                'checker_id' => Auth::id(),
                'status' => 'approved',
                'checker_comment' => $comment,
                'resolved_at' => now(),
            ]);

            $this->executeApproval($request);

            AuditLog::createEntry(Auth::id(), 'update', 'dual_control_request', $request->id, $before, $request->fresh()->toArray(), request()->ip());

            return $request->fresh();
        });
    }

    public function reject(DualControlRequest $request, string $comment): DualControlRequest
    {
        abort_if($request->maker_id === Auth::id(), 403, 'Maker cannot reject their own request.');
        abort_if($request->status !== 'pending', 422, 'Request is not pending.');

        return DB::transaction(function () use ($request, $comment) {
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
    }

    public function cancel(DualControlRequest $request): DualControlRequest
    {
        abort_if($request->status !== 'pending', 422, 'Only pending requests can be cancelled.');

        return DB::transaction(function () use ($request) {
            $request->update(['status' => 'cancelled', 'resolved_at' => now()]);
            return $request->fresh();
        });
    }

    public function expireStaleRequests(): int
    {
        return DualControlRequest::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired', 'resolved_at' => now()]);
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
            'cargo_station' => \App\Models\Station::class,
            'cargo_route' => \App\Models\Route::class,
            'cargo_transport_unit' => \App\Models\TransportUnit::class,
            'cargo_schedule' => \App\Models\TransportService::class,
        ];
        return $map[$resourceType] ?? null;
    }

    private function executeApproval(DualControlRequest $request): void
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

                if (!empty($user->phone)) {
                    $sms = new \App\Services\SmsService();
                    $sms->sendSms($user->phone, "Welcome to Victoria Ferries. Your Agent account is created. Username: {$user->email}, Password: {$plainPassword}");
                }
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

            if ($model instanceof \App\Models\User && !empty($model->phone) && !empty($plainPassword)) {
                $sms = new \App\Services\SmsService();
                $sms->sendSms($model->phone, "Welcome to Victoria Ferries. Your account is created. Username: {$model->email}, Password: {$plainPassword}");
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
