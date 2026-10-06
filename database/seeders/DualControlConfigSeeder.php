<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DualControlConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configs = [
            // Journey Entities
            ['module' => 'Ferry Operations', 'operation_type' => 'ferry_create', 'description' => 'Add a new ferry to the fleet', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'ferry_update', 'description' => 'Modify an existing ferry or its classes', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'ferry_delete', 'description' => 'Remove a ferry from the fleet', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'ferry_status_update', 'description' => 'Suspend or activate a ferry', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'port_create', 'description' => 'Add a new port to the system', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'port_update', 'description' => 'Modify an existing port', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'port_delete', 'description' => 'Remove a port from the system', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'port_status_update', 'description' => 'Suspend or activate a port', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'class_create', 'description' => 'Add a new ferry class', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'class_update', 'description' => 'Modify an existing ferry class', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'class_delete', 'description' => 'Remove a ferry class', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'route_create', 'description' => 'Create a new sailing route', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'route_update', 'description' => 'Modify an existing sailing route', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'route_delete', 'description' => 'Remove a sailing route', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'route_status_update', 'description' => 'Suspend or activate a route', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'route_agents_update', 'description' => 'Assign or update authorized agencies for a route', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'trip_create', 'description' => 'Create a new trip pattern', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'trip_update', 'description' => 'Modify an existing trip pattern', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'trip_delete', 'description' => 'Remove a trip pattern', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'schedule_create', 'description' => 'Create a new trip schedule', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'schedule_update', 'description' => 'Modify an existing trip schedule', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'schedule_delete', 'description' => 'Remove a trip schedule', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'schedule_status_update', 'description' => 'Suspend or activate a schedule', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'fare_create', 'description' => 'Create a new fare entry', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'fare_update', 'description' => 'Update pricing and fare matrix', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'fare_delete', 'description' => 'Remove a fare entry', 'requires_dual_control' => false],
            ['module' => 'Ferry Operations', 'operation_type' => 'capacity_update', 'description' => 'Increase or decrease physical capacity for a specific departure', 'requires_dual_control' => true],

            // Administration
            ['module' => 'User Management', 'operation_type' => 'user_create', 'description' => 'Create a new system user', 'requires_dual_control' => false],
            ['module' => 'User Management', 'operation_type' => 'user_update', 'description' => 'Update user details', 'requires_dual_control' => false],
            ['module' => 'User Management', 'operation_type' => 'user_status_update', 'description' => 'Activate or deactivate a user', 'requires_dual_control' => false],
            ['module' => 'User Management', 'operation_type' => 'user_roles_update', 'description' => 'Change a user\'s assigned roles', 'requires_dual_control' => false],
            
            ['module' => 'System Configuration', 'operation_type' => 'lookup_create', 'description' => 'Add a new lookup value', 'requires_dual_control' => false],
            ['module' => 'System Configuration', 'operation_type' => 'lookup_update', 'description' => 'Modify an existing lookup value', 'requires_dual_control' => false],
            ['module' => 'System Configuration', 'operation_type' => 'lookup_delete', 'description' => 'Remove a lookup value', 'requires_dual_control' => false],
            ['module' => 'System Configuration', 'operation_type' => 'menu_create', 'description' => 'Add a new menu item', 'requires_dual_control' => false],
            ['module' => 'System Configuration', 'operation_type' => 'menu_update', 'description' => 'Modify an existing menu item', 'requires_dual_control' => false],
            ['module' => 'System Configuration', 'operation_type' => 'menu_delete', 'description' => 'Remove a menu item', 'requires_dual_control' => false],
            ['module' => 'System Configuration', 'operation_type' => 'translation_update', 'description' => 'Update a translation string', 'requires_dual_control' => false],
            ['module' => 'System Configuration', 'operation_type' => 'setting_update', 'description' => 'Change a global system setting', 'requires_dual_control' => true],
            ['module' => 'System Configuration', 'operation_type' => 'commission_method_update', 'description' => 'Change default agent commission calculation method', 'requires_dual_control' => true],

            // Booking & Ticket Management
            ['module' => 'Booking Management', 'operation_type' => 'booking_complete',   'description' => 'Complete payment process for a held booking', 'requires_dual_control' => false],
            ['module' => 'Booking Management', 'operation_type' => 'booking_edit',       'description' => 'Edit booking details or passenger list',      'requires_dual_control' => false],
            ['module' => 'Booking Management', 'operation_type' => 'booking_cancel',     'description' => 'Cancel an entire booking and all tickets',    'requires_dual_control' => false],
            ['module' => 'Booking Management', 'operation_type' => 'booking_reschedule', 'description' => 'Reschedule all tickets in a booking',         'requires_dual_control' => false],
            ['module' => 'Booking Management', 'operation_type' => 'ticket_cancel',      'description' => 'Cancel a single passenger ticket',            'requires_dual_control' => false],
            ['module' => 'Booking Management', 'operation_type' => 'ticket_reschedule',  'description' => 'Reschedule a single passenger ticket',        'requires_dual_control' => false],
            ['module' => 'Booking Management', 'operation_type' => 'activate_agent',     'description' => 'Activate agent booking or override',          'requires_dual_control' => false],
            ['module' => 'Booking Management', 'operation_type' => 'activate_booking',   'description' => 'Reactivate an inactive/cancelled booking',    'requires_dual_control' => false],
            ['module' => 'Booking Management', 'operation_type' => 'ticket_reprint',     'description' => 'Reprint a ticket that was already printed',   'requires_dual_control' => true],
            ['module' => 'Booking Management', 'operation_type' => 'class_downgrade',    'description' => 'Downgrade passenger ticket to a lower seating class', 'requires_dual_control' => true],
            ['module' => 'Booking Management', 'operation_type' => 'booking_apply_exemption', 'description' => 'Issue a complimentary (free) ticket with 100% fare exemption', 'requires_dual_control' => false],

            // Corporate Management
            ['module' => 'Corporate Management', 'operation_type' => 'corporate_create', 'description' => 'Create a new corporate partner account', 'requires_dual_control' => false],
            ['module' => 'Corporate Management', 'operation_type' => 'corporate_update', 'description' => 'Modify corporate partner account details or credit limit', 'requires_dual_control' => true],
            ['module' => 'Corporate Management', 'operation_type' => 'corporate_status_update', 'description' => 'Suspend, close or activate a corporate partner account', 'requires_dual_control' => true],
            ['module' => 'Corporate Management', 'operation_type' => 'corporate_invoice_generate', 'description' => 'Generate a monthly corporate invoice', 'requires_dual_control' => false],
            ['module' => 'Corporate Management', 'operation_type' => 'corporate_payment_record', 'description' => 'Record a payment against a corporate invoice', 'requires_dual_control' => true],

            // Agency & Float Management
            ['module' => 'Agency Management', 'operation_type' => 'agent_create',         'description' => 'Create a new ticketing agency or partner',           'requires_dual_control' => false],
            ['module' => 'Agency Management', 'operation_type' => 'agent_update',         'description' => 'Update agency details, status, or commission rules', 'requires_dual_control' => false],
            ['module' => 'Agency Management', 'operation_type' => 'agent_topup',          'description' => 'Top up agency virtual float balance',                'requires_dual_control' => false],
            ['module' => 'Agency Management', 'operation_type' => 'float_topup',          'description' => 'Float top-up threshold trigger check',               'requires_dual_control' => false],
            ['module' => 'Agency Management', 'operation_type' => 'agent_adjust_limit',   'description' => 'Adjust agency overdraft credit limit',               'requires_dual_control' => false],
            ['module' => 'Agency Management', 'operation_type' => 'associate_create',     'description' => 'Register a new associate staff member for an agency','requires_dual_control' => false],
            ['module' => 'Agency Management', 'operation_type' => 'associate_update',     'description' => 'Update associate staff member details or limits',    'requires_dual_control' => false],
            ['module' => 'Agency Management', 'operation_type' => 'agent_ferries_update', 'description' => 'Assign or update authorized ferries for an agency partner', 'requires_dual_control' => false],
            ['module' => 'Agency Management', 'operation_type' => 'agent_routes_update',  'description' => 'Assign or update authorized routes for an agency partner',  'requires_dual_control' => false],
            
            // Discounts & Promotions
            ['module' => 'Commercial Engine', 'operation_type' => 'discount_create', 'description' => 'Create a new price discount', 'requires_dual_control' => false],
            ['module' => 'Commercial Engine', 'operation_type' => 'discount_update', 'description' => 'Update a price discount', 'requires_dual_control' => false],
            ['module' => 'Commercial Engine', 'operation_type' => 'discount_delete', 'description' => 'Delete a price discount', 'requires_dual_control' => false],
            ['module' => 'Commercial Engine', 'operation_type' => 'discount_status_update', 'description' => 'Activate or deactivate a price discount', 'requires_dual_control' => false],
            ['module' => 'Commercial Engine', 'operation_type' => 'promotion_create', 'description' => 'Create a new promotion', 'requires_dual_control' => false],
            ['module' => 'Commercial Engine', 'operation_type' => 'promotion_update', 'description' => 'Update a promotion', 'requires_dual_control' => false],
            ['module' => 'Commercial Engine', 'operation_type' => 'promotion_delete', 'description' => 'Delete a promotion', 'requires_dual_control' => false],
            ['module' => 'Commercial Engine', 'operation_type' => 'promotion_status_update', 'description' => 'Activate or deactivate a promotion', 'requires_dual_control' => false],
            ['module' => 'Commercial Engine', 'operation_type' => 'coupon_create', 'description' => 'Create a new coupon', 'requires_dual_control' => false],
            ['module' => 'Commercial Engine', 'operation_type' => 'coupon_update', 'description' => 'Update a coupon', 'requires_dual_control' => false],
            ['module' => 'Commercial Engine', 'operation_type' => 'coupon_delete', 'description' => 'Delete a coupon', 'requires_dual_control' => false],
            ['module' => 'Commercial Engine', 'operation_type' => 'coupon_status_update', 'description' => 'Activate or deactivate a coupon', 'requires_dual_control' => false],
        ];

        foreach ($configs as $config) {
            \App\Models\DualControlConfig::updateOrCreate(
                ['operation_type' => $config['operation_type']],
                $config
            );
        }
    }
}
