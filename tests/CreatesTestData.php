<?php

namespace Tests;

use App\Models\Agent;
use App\Models\AgentAccount;
use App\Models\Booking;
use App\Models\BookingLock;
use App\Models\Corporate;
use App\Models\Fare;
use App\Models\Ferry;
use App\Models\FerryClass;
use App\Models\Passenger;
use App\Models\PassengerType;
use App\Models\PaymentMethod;
use App\Models\PaymentType;
use App\Models\Permission;
use App\Models\Port;
use App\Models\Role;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\SystemConfig;
use App\Models\Ticket;
use App\Models\TransactionCategory;
use App\Models\TransactionStatus;
use App\Models\TravelClass;
use App\Models\User;
use App\Models\Voyage;
use Carbon\Carbon;

trait CreatesTestData
{
    // ── Reference Data Seeders ───────────────────────────────

    protected function seedReferenceData(): void
    {
        // Gracefully handle live DB where data already exists
        try {
            if (\Schema::hasTable('passenger_types')) $this->seedPassengerTypes();
            if (\Schema::hasTable('payment_types')) $this->seedPaymentTypes();
            if (\Schema::hasTable('payment_methods')) $this->seedPaymentMethods();
            if (\Schema::hasTable('transaction_categories')) $this->seedTransactionCategories();
            if (\Schema::hasTable('transaction_statuses')) $this->seedTransactionStatuses();
            if (\Schema::hasTable('system_configs')) $this->seedSystemConfigs();
        } catch (\Throwable $e) {
            // Data already seeded in live DB — safe to ignore
        }
    }

    protected function seedPassengerTypes(): void
    {
        PassengerType::firstOrCreate(['code' => 'ADULT'], ['name' => 'Adult', 'is_default' => true, 'min_age' => 13, 'max_age' => 120]);
        PassengerType::firstOrCreate(['code' => 'CHILD'], ['name' => 'Child', 'is_default' => false, 'min_age' => 2, 'max_age' => 12]);
        PassengerType::firstOrCreate(['code' => 'INFANT'], ['name' => 'Infant', 'is_default' => false, 'min_age' => 0, 'max_age' => 1]);
    }

    protected function seedPaymentTypes(): void
    {
        PaymentType::firstOrCreate(['slug' => 'cash'], ['name' => 'Cash', 'is_active' => true]);
        PaymentType::firstOrCreate(['slug' => 'credit'], ['name' => 'Credit', 'is_active' => true]);
        PaymentType::firstOrCreate(['slug' => 'debit'], ['name' => 'Debit', 'is_active' => true]);
        PaymentType::firstOrCreate(['slug' => 'wallet'], ['name' => 'Wallet', 'is_active' => true]);
        PaymentType::firstOrCreate(['slug' => 'reverse'], ['name' => 'Reverse', 'is_active' => true]);
    }

    protected function seedPaymentMethods(): void
    {
        PaymentMethod::firstOrCreate(['slug' => 'cash'], ['name' => 'Cash', 'is_active' => true]);
        PaymentMethod::firstOrCreate(['slug' => 'mobile-money'], ['name' => 'Mobile Money', 'is_active' => true]);
        PaymentMethod::firstOrCreate(['slug' => 'card'], ['name' => 'Card', 'is_active' => true]);
        PaymentMethod::firstOrCreate(['slug' => 'agent-wallet-deduction'], ['name' => 'Agent Wallet Deduction', 'is_active' => true]);
        PaymentMethod::firstOrCreate(['slug' => 'agent-float'], ['name' => 'Agent Float', 'is_active' => true]);
    }

    protected function seedTransactionCategories(): void
    {
        \App\Models\TransactionCategory::unguarded(function () {
            \App\Models\TransactionCategory::firstOrCreate(['slug' => 'float-top-up'], ['id' => 10, 'name' => 'Float Top-Up', 'type' => 'credit', 'sort_order' => 1]);
            \App\Models\TransactionCategory::firstOrCreate(['slug' => 'ticket-sale'], ['id' => 11, 'name' => 'Ticket Sale', 'type' => 'debit', 'sort_order' => 2]);
            \App\Models\TransactionCategory::firstOrCreate(['slug' => 'commission'], ['id' => 12, 'name' => 'Commission', 'type' => 'credit', 'sort_order' => 3]);
            \App\Models\TransactionCategory::firstOrCreate(['slug' => 'cancellation-refund'], ['id' => 13, 'name' => 'Cancellation Refund', 'type' => 'credit', 'sort_order' => 4]);
            \App\Models\TransactionCategory::firstOrCreate(['slug' => 'cancellation_penalty'], ['id' => 14, 'name' => 'Cancellation Penalty', 'type' => 'debit', 'sort_order' => 5]);
            \App\Models\TransactionCategory::firstOrCreate(['slug' => 'adjustment-credit-or-debit'], ['id' => 15, 'name' => 'Adjustment', 'type' => 'both', 'sort_order' => 6]);
        });
    }

    protected function seedTransactionStatuses(): void
    {
        \App\Models\TransactionStatus::unguarded(function () {
            \App\Models\TransactionStatus::firstOrCreate(['slug' => 'pending'], ['id' => 10, 'name' => 'Pending']);
            \App\Models\TransactionStatus::firstOrCreate(['slug' => 'processing'], ['id' => 11, 'name' => 'Processing']);
            \App\Models\TransactionStatus::firstOrCreate(['slug' => 'failed'], ['id' => 12, 'name' => 'Failed']);
            \App\Models\TransactionStatus::firstOrCreate(['slug' => 'completed'], ['id' => 13, 'name' => 'Completed']);
        });
    }

    protected function seedSystemConfigs(): void
    {
        SystemConfig::firstOrCreate(['key_name' => 'usd_to_tzs_exchange_rate'], ['value' => '2600', 'group' => 'finance']);
        SystemConfig::firstOrCreate(['key_name' => 'cancellation_penalty_percentage'], ['value' => '10', 'group' => 'booking']);
        SystemConfig::firstOrCreate(['key_name' => 'cancellation_penalty_enabled'], ['value' => '1', 'group' => 'booking']);
        SystemConfig::firstOrCreate(['key_name' => 'cancellation_penalty_mode'], ['value' => 'flat', 'group' => 'booking']);
        SystemConfig::firstOrCreate(['key_name' => 'cancellation_allowed_hours_before'], ['value' => '2', 'group' => 'booking']);
        SystemConfig::firstOrCreate(['key_name' => 'cancellation_full_penalty_hours'], ['value' => '2', 'group' => 'booking']);
        SystemConfig::firstOrCreate(['key_name' => 'cancellation_partial_penalty_hours'], ['value' => '24', 'group' => 'booking']);
        SystemConfig::firstOrCreate(['key_name' => 'cancellation_partial_penalty_rate'], ['value' => '25', 'group' => 'booking']);
        SystemConfig::firstOrCreate(['key_name' => 'cancellation_refund_mode'], ['value' => 'hold_until_submission', 'group' => 'booking']);
        SystemConfig::firstOrCreate(['key_name' => 'ticket_prefix_default'], ['value' => 'VFF', 'group' => 'booking']);
        SystemConfig::firstOrCreate(['key_name' => 'ticket_prefix_agent'], ['value' => 'VAF', 'group' => 'booking']);
        SystemConfig::firstOrCreate(['key_name' => 'infant_free_mode'], ['value' => 'per_adult', 'group' => 'booking']);
        SystemConfig::firstOrCreate(['key_name' => 'infant_free_limit'], ['value' => '1', 'group' => 'booking']);
        SystemConfig::firstOrCreate(['key_name' => 'commission_default_method'], ['value' => 'post_sale', 'group' => 'agent']);
        SystemConfig::firstOrCreate(['key_name' => 'commission_default_rate'], ['value' => '5', 'group' => 'agent']);
        SystemConfig::firstOrCreate(['key_name' => 'security_deposit_default_rate'], ['value' => '40', 'group' => 'agent']);
        SystemConfig::firstOrCreate(['key_name' => 'dual_control_float_threshold'], ['value' => '5000000', 'group' => 'agent']);
        SystemConfig::firstOrCreate(['key_name' => 'cancellation_refund_mode'], ['value' => 'hold_until_submission', 'group' => 'booking']);
        SystemConfig::firstOrCreate(['key_name' => 'enable_exchange_rate_conversion'], ['value' => '1', 'group' => 'finance']);
        SystemConfig::firstOrCreate(['key_name' => 'reservation_expiry_hours_before_departure'], ['value' => '2', 'group' => 'booking']);
    }

    // ── Entity Factory Helpers ───────────────────────────────

    protected function createSystemUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'first_name' => 'System',
            'last_name' => 'Admin',
            'email' => 'admin-' . uniqid() . '@ferry.test',
            'password' => bcrypt('password'),
            'password_changed_at' => now(),
            'domain' => 'system_user',
            'status' => 'active',
        ], $overrides));
    }

    protected function createAgentUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'first_name' => 'Agent',
            'last_name' => 'User',
            'email' => 'agent-' . uniqid() . '@ferry.test',
            'password' => bcrypt('password'),
            'password_changed_at' => now(),
            'domain' => 'agent',
            'status' => 'active',
        ], $overrides));
    }

    protected function createPassengerUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'first_name' => 'Passenger',
            'last_name' => 'User',
            'email' => 'passenger-' . uniqid() . '@ferry.test',
            'password' => bcrypt('password'),
            'password_changed_at' => now(),
            'domain' => 'passenger',
            'status' => 'active',
        ], $overrides));
    }

    protected function createAgentWithAccount(float $balance = 1000000, array $agentOverrides = [], array $accountOverrides = []): array
    {
        $user = $this->createAgentUser();
        $agent = Agent::create(array_merge([
            'user_id' => $user->id,
            'agent_code' => 'AGT-' . strtoupper(uniqid()),
            'agency_name' => 'Test Agency ' . uniqid(),
            'contact_mobile' => '+255700' . rand(100000, 999999),
            'email' => $user->email,
            'commission_rate' => 5.00,
            'commission_method' => 'pre_deposit',
            'is_active' => true,
        ], $agentOverrides));

        $account = AgentAccount::create(array_merge([
            'agent_id' => $agent->id,
            'account_number' => 'ACC-' . strtoupper(uniqid()),
            'currency' => 'TZS',
            'balance' => $balance,
            'security_deposit_balance' => 0,
            'credit_limit' => 0,
            'is_active' => true,
        ], $accountOverrides));

        return ['user' => $user, 'agent' => $agent, 'account' => $account];
    }

    protected function createPort(array $overrides = []): Port
    {
        return Port::create(array_merge([
            'name' => 'Test Port ' . uniqid(),
            'code' => strtoupper(substr(uniqid(), -4)),
            'city' => 'Test City',
            'status' => 'active',
        ], $overrides));
    }

    protected function createRoute(?Port $origin = null, ?Port $destination = null, array $overrides = []): Route
    {
        $origin = $origin ?? $this->createPort(['name' => 'Dar es Salaam', 'code' => 'DSM-' . strtoupper(substr(uniqid(), -4))]);
        $destination = $destination ?? $this->createPort(['name' => 'Zanzibar', 'code' => 'ZNZ-' . strtoupper(substr(uniqid(), -4))]);

        return Route::create(array_merge([
            'origin_port_id' => $origin->id,
            'destination_port_id' => $destination->id,
            'name' => "{$origin->name} → {$destination->name}",
            'code' => 'RT-' . strtoupper(substr(uniqid(), -4)),
            'status' => 'active',
        ], $overrides));
    }

    protected function createFerry(array $overrides = []): Ferry
    {
        return Ferry::create(array_merge([
            'name' => 'MV Test Ferry ' . uniqid(),
            'type' => 'passenger',
            'capacity' => 300,
            'status' => 'active',
        ], $overrides));
    }

    protected function createFerryClass(Ferry $ferry, array $overrides = []): FerryClass
    {
        $travelClass = TravelClass::firstOrCreate(
            ['name' => $overrides['class_name'] ?? 'Economy'],
            ['name' => $overrides['class_name'] ?? 'Economy']
        );
        unset($overrides['class_name']);

        return FerryClass::create(array_merge([
            'ferry_id' => $ferry->id,
            'class_id' => $travelClass->id,
            'no_seats' => 100,
            'sort_order' => 3,
            'status' => 'active',
        ], $overrides));
    }

    protected function createTrip(Route $route, array $overrides = []): \App\Models\Trip
    {
        return \App\Models\Trip::create(array_merge([
            'route_id' => $route->id,
            'departure_time' => '08:00:00',
            'arrival_time' => '10:00:00',
            'status' => 'active',
        ], $overrides));
    }

    protected function createSchedule(\App\Models\Trip $trip, Ferry $ferry, array $overrides = []): \App\Models\Schedule
    {
        return \App\Models\Schedule::create(array_merge([
            'trip_id' => $trip->id,
            'ferry_id' => $ferry->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addMonths(6)->toDateString(),
            'frequency_pattern' => 'Daily',
            'status' => 'active',
        ], $overrides));
    }

    protected function createVoyage(Route $route, Ferry $ferry, array $overrides = []): Voyage
    {
        $trip = $this->createTrip($route);
        $schedule = $this->createSchedule($trip, $ferry);

        return Voyage::create(array_merge([
            'route_id' => $route->id,
            'ferry_id' => $ferry->id,
            'schedule_id' => $schedule->id,
            'departure_date' => Carbon::tomorrow()->toDateString(),
            'departure_time' => '08:00:00',
            'status' => 'scheduled',
        ], $overrides));
    }

    protected function createFare(Route $route, FerryClass $class, ?Ferry $ferry = null, array $overrides = []): Fare
    {
        return Fare::create(array_merge([
            'route_id' => $route->id,
            'ferry_id' => $ferry?->id,
            'ferry_class_id' => $class->id,
            'adult_price' => 35000,
            'child_price' => 20000,
            'adult_price_usd' => 15,
            'child_price_usd' => 8,
            'status' => 'active',
        ], $overrides));
    }

    protected function createPassenger(array $overrides = []): Passenger
    {
        return Passenger::create(array_merge([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'male',
            'nationality' => 'Tanzanian',
            'id_number' => 'PASS-' . uniqid(),
        ], $overrides));
    }

    /**
     * Create a complete booking scenario with all dependencies.
     * Returns an array with all entities needed for testing.
     */
    protected function createCompleteBookingScenario(array $options = []): array
    {
        $port1 = $this->createPort(['name' => 'Dar es Salaam', 'code' => 'DSM-' . strtoupper(substr(uniqid(), -4))]);
        $port2 = $this->createPort(['name' => 'Zanzibar', 'code' => 'ZNZ-' . strtoupper(substr(uniqid(), -4))]);
        $route = $this->createRoute($port1, $port2);
        $ferry = $this->createFerry();
        $class = $this->createFerryClass($ferry, ['class_name' => $options['class_name'] ?? 'Economy']);
        $voyage = $this->createVoyage($route, $ferry, $options['voyage_overrides'] ?? []);
        $fare = $this->createFare($route, $class, $ferry);

        $user = $options['user'] ?? $this->createSystemUser();

        // Assign ferry to agent if agent user
        if (($user->domain === 'agent' || $user->domain === 'associate_agent') && isset($options['agent'])) {
            $options['agent']->ferries()->syncWithoutDetaching([$ferry->id => ['status' => 'active']]);
        }

        return compact('port1', 'port2', 'route', 'ferry', 'class', 'voyage', 'fare', 'user');
    }

    protected function createConfirmedBooking(Voyage $voyage, FerryClass $class, User $user, int $passengerCount = 1): Booking
    {
        $booking = Booking::create([
            'booking_reference' => 'VFF-' . mt_rand(100000000000, 999999999999),
            'user_id' => $user->id,
            'booked_by_user_id' => $user->id,
            'completed_by_user_id' => $user->id,
            'booking_source' => 'WEB',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'trip_type' => 'one_way',
            'total_amount' => 0,
            'total_amount_tzs' => 0,
            'total_amount_usd' => 0,
            'net_amount' => 0,
            'currency' => 'TZS',
        ]);

        $totalAmount = 0;
        for ($i = 1; $i <= $passengerCount; $i++) {
            $passenger = $this->createPassenger(['first_name' => "Passenger{$i}"]);
            $fare = 35000;
            Ticket::create([
                'booking_id' => $booking->id,
                'ticket_number' => "{$booking->booking_reference}-{$i}",
                'voyage_id' => $voyage->id,
                'ferry_class_id' => $class->id,
                'passenger_id' => $passenger->id,
                'seat_number' => 'OPEN',
                'base_fare' => $fare,
                'discount_amount' => 0,
                'final_fare' => $fare,
                'status' => 'confirmed',
                'currency' => 'TZS',
                'nationality_type' => 1,
                'qr_code_token' => bin2hex(random_bytes(16)),
            ]);
            $totalAmount += $fare;
        }

        $booking->update([
            'total_amount' => $totalAmount,
            'total_amount_tzs' => $totalAmount,
            'net_amount' => $totalAmount,
        ]);

        return $booking->fresh(['items.passenger', 'items.ferryClass']);
    }

    protected function grantPermission(User $user, string $permissionSlug): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => $permissionSlug],
            ['display_name' => ucwords(str_replace([':', '_'], ' ', $permissionSlug)), 'module' => 'reports']
        );
        $user->permissions()->syncWithoutDetaching([$permission->id => ['granted' => true]]);
    }

    protected function createShiftForUser(User $user): Shift
    {
        return Shift::create([
            'user_id' => $user->id,
            'start_time' => now()->subHours(4),
            'end_time' => now()->addHours(4),
            'status' => 'active',
        ]);
    }
}
