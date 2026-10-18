<?php

use App\Services\DualControl\Handlers;

/*
|--------------------------------------------------------------------------
| Dual control (maker-checker) registry
|--------------------------------------------------------------------------
|
| Every admin action that can need a second person's approval. `php artisan dual-control:sync`
| registers each one as a rule (dual_control_configs) without touching toggles already set.
|
| module   — group on the rules page
| label    — short name shown on screens
| default  — whether a newly registered rule needs approval. Actions that needed approval
|            before they were registered default to true, so registering never loosens a control.
| timing   — immediate: applies when submitted / approved, no "Takes effect" choice
|            scheduled: the maker may pick a moment; the scheduler applies it then
|            dated:     the record carries its own start (fares, exchange rates), so approval writes it
|                       straight away with that start date
| handler  — class that applies the change (null = the built-in executor in DualControlService)
| legacy   — ferry-system actions this platform doesn't use; hidden on the rules page
|
*/

$legacy = fn (string $description) => ['module' => 'Legacy (ferry)', 'label' => null, 'description' => $description, 'default' => false, 'timing' => 'immediate', 'handler' => null, 'legacy' => true];

return [

    // Pending requests that take effect "as soon as approved" expire this many hours after submission.
    // Requests with a start time stay open until that start + the same number of hours (late approval window).
    'expiry_hours_key' => 'dc_expiry_hours',

    // The scheduler is considered stopped when dual-control:process hasn't run for this long
    'heartbeat_minutes' => 5,

    'operations' => [

        // ── Cargo Pricing ────────────────────────────────────────
        'cargo_fare_create'      => ['module' => 'Cargo Pricing', 'label' => 'Add a fare', 'description' => 'Create a cargo fare for a type and route', 'default' => false, 'timing' => 'dated', 'handler' => Handlers\CargoFareHandler::class],
        'cargo_fare_revise'      => ['module' => 'Cargo Pricing', 'label' => 'Change a fare', 'description' => "Change a fare's prices — saved as a new version, the old one is kept", 'default' => false, 'timing' => 'dated', 'handler' => Handlers\CargoFareHandler::class],
        'cargo_fare_end'         => ['module' => 'Cargo Pricing', 'label' => 'End or revoke a fare', 'description' => 'Set the last moment a fare applies, or revoke one configured incorrectly', 'default' => false, 'timing' => 'dated', 'handler' => Handlers\CargoFareHandler::class],
        'cargo_fare_delete'      => ['module' => 'Cargo Pricing', 'label' => 'Delete a fare', 'description' => 'Delete a fare that was never used and has not started', 'default' => false, 'timing' => 'immediate', 'handler' => Handlers\CargoFareHandler::class],
        'cargo_fare_bulk_update' => ['module' => 'Cargo Pricing', 'label' => 'Bulk value charge', 'description' => 'Set the value charge on several fares at once (new versions)', 'default' => false, 'timing' => 'dated', 'handler' => Handlers\CargoFareHandler::class],
        'cargo_fare_copy'        => ['module' => 'Cargo Pricing', 'label' => 'Copy route fares', 'description' => "Copy one route's fares to another route", 'default' => false, 'timing' => 'dated', 'handler' => Handlers\CargoFareHandler::class],

        // ── Cargo Setup ─────────────────────────────────────────
        'cargo_type_create' => ['module' => 'Cargo Setup', 'label' => 'Add a cargo type', 'description' => 'Create a cargo type', 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\CargoTypeHandler::class],
        'cargo_type_update' => ['module' => 'Cargo Setup', 'label' => 'Change a cargo type', 'description' => "Change a cargo type's details, limits or status", 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\CargoTypeHandler::class],
        'cargo_type_delete' => ['module' => 'Cargo Setup', 'label' => 'Delete a cargo type', 'description' => 'Delete a cargo type that has no fares', 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\CargoTypeHandler::class],

        // ── Cargo Network ───────────────────────────────────────
        'station_create'        => ['module' => 'Cargo Network', 'label' => 'Add a station', 'description' => 'Create a station', 'default' => true, 'timing' => 'scheduled', 'handler' => Handlers\GenericModelHandler::class],
        'station_update'        => ['module' => 'Cargo Network', 'label' => 'Change a station', 'description' => "Change a station's name, code or city", 'default' => true, 'timing' => 'scheduled', 'handler' => Handlers\GenericModelHandler::class],
        'station_status_update' => ['module' => 'Cargo Network', 'label' => 'Activate / suspend a station', 'description' => 'Activate or suspend a station', 'default' => true, 'timing' => 'scheduled', 'handler' => Handlers\GenericModelHandler::class],
        'route_create'          => ['module' => 'Cargo Network', 'label' => 'Add a route', 'description' => 'Create a route', 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\GenericModelHandler::class],
        'route_update'          => ['module' => 'Cargo Network', 'label' => 'Change a route', 'description' => "Change a route's stations or distance", 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\GenericModelHandler::class],
        'route_status_update'   => ['module' => 'Cargo Network', 'label' => 'Activate / suspend a route', 'description' => 'Activate or suspend a route', 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\GenericModelHandler::class],
        'route_agents_update'   => ['module' => 'Cargo Network', 'label' => 'Route agents', 'description' => 'Assign or update the agents allowed to sell a route', 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\GenericModelHandler::class],
        'trip_create'           => ['module' => 'Cargo Network', 'label' => 'Add a trip', 'description' => 'Create a trip (departure time pattern) on a route', 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\GenericModelHandler::class],
        'trip_update'           => ['module' => 'Cargo Network', 'label' => 'Change a trip', 'description' => "Change a trip's times or status", 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\GenericModelHandler::class],
        'trip_delete'           => ['module' => 'Cargo Network', 'label' => 'Delete a trip', 'description' => 'Delete a trip from a route', 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\GenericModelHandler::class],
        'cargo_transport_unit_create'        => ['module' => 'Cargo Network', 'label' => 'Add a vessel', 'description' => 'Add a vessel / transport unit with its cargo capacity', 'default' => true, 'timing' => 'scheduled', 'handler' => Handlers\GenericModelHandler::class],
        'cargo_transport_unit_update'        => ['module' => 'Cargo Network', 'label' => 'Change a vessel', 'description' => "Change a vessel's details or cargo capacity", 'default' => true, 'timing' => 'scheduled', 'handler' => Handlers\GenericModelHandler::class],
        'cargo_transport_unit_status_update' => ['module' => 'Cargo Network', 'label' => 'Vessel status', 'description' => 'Activate a vessel, take it out of service or put it in maintenance', 'default' => true, 'timing' => 'scheduled', 'handler' => Handlers\GenericModelHandler::class],
        'schedule_create'       => ['module' => 'Cargo Network', 'label' => 'Add a vessel schedule', 'description' => 'Create a recurring schedule for a vessel (departures are generated from it)', 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\GenericModelHandler::class],
        'schedule_update'       => ['module' => 'Cargo Network', 'label' => 'Change a vessel schedule', 'description' => 'Change, suspend or activate a vessel schedule', 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\GenericModelHandler::class],

        // ── Cargo Operations ────────────────────────────────────
        'cargo_capacity_update'       => ['module' => 'Cargo Operations', 'label' => 'Departure capacity', 'description' => "Change one departure's cargo capacity (lane metres, vehicle slots, weight, volume)", 'default' => true, 'timing' => 'scheduled', 'handler' => Handlers\CargoCapacityHandler::class],
        'shift_extension'             => ['module' => 'Cargo Operations', 'label' => 'Shift extension', 'description' => "Extend or reopen a sales officer's shift", 'default' => true, 'timing' => 'immediate', 'handler' => Handlers\ShiftExtensionHandler::class,
                                         'note' => "A sales officer's own request always waits for approval. This switch only covers extensions a supervisor or admin starts in Admin › Shifts."],
        'cargo_journey_status_update' => ['module' => 'Cargo Operations', 'label' => 'Departure status', 'description' => 'Change a departure to boarding, departed, arrived or cancelled', 'default' => false, 'timing' => 'immediate', 'handler' => Handlers\CargoJourneyStatusHandler::class],

        // ── Cargo Bookings ──────────────────────────────────────
        'cargo_booking_cancel'         => ['module' => 'Cargo Bookings', 'label' => 'Cancel a booking', 'description' => 'Cancel a cargo booking and release its space', 'default' => false, 'timing' => 'immediate', 'handler' => Handlers\CargoBookingCancelHandler::class],
        'cargo_payment_manual_confirm' => ['module' => 'Cargo Bookings', 'label' => 'Confirm a payment by hand', 'description' => 'Mark a cash / Lipa Namba payment as received using its reference', 'default' => false, 'timing' => 'immediate', 'handler' => Handlers\CargoManualPaymentHandler::class],

        // ── Settings ────────────────────────────────────────────
        'setting_update'            => ['module' => 'Settings', 'label' => 'System setting', 'description' => 'Change a global system setting', 'default' => true, 'timing' => 'scheduled', 'handler' => Handlers\GenericModelHandler::class],
        'commission_method_update'  => ['module' => 'Settings', 'label' => 'Commission method', 'description' => 'Change the default agent commission calculation method', 'default' => true, 'timing' => 'scheduled', 'handler' => Handlers\GenericModelHandler::class],
        'lookup_create'             => ['module' => 'Settings', 'label' => 'Add a lookup value', 'description' => 'Add a lookup value', 'default' => false, 'timing' => 'scheduled', 'handler' => null],
        'lookup_update'             => ['module' => 'Settings', 'label' => 'Change a lookup value', 'description' => 'Modify an existing lookup value', 'default' => false, 'timing' => 'scheduled', 'handler' => null],
        'lookup_delete'             => ['module' => 'Settings', 'label' => 'Delete a lookup value', 'description' => 'Remove a lookup value', 'default' => false, 'timing' => 'scheduled', 'handler' => null],
        'menu_create'               => ['module' => 'Settings', 'label' => 'Add a menu item', 'description' => 'Add a menu item', 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\GenericModelHandler::class],
        'menu_update'               => ['module' => 'Settings', 'label' => 'Change a menu item', 'description' => 'Modify an existing menu item', 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\GenericModelHandler::class],
        'menu_delete'               => ['module' => 'Settings', 'label' => 'Delete a menu item', 'description' => 'Remove a menu item', 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\GenericModelHandler::class],
        'exchange_rate_create'      => ['module' => 'Settings', 'label' => 'Set an exchange rate', 'description' => 'Set the exchange rate from a date', 'default' => false, 'timing' => 'dated', 'handler' => Handlers\ExchangeRateHandler::class],
        'exchange_rate_delete'      => ['module' => 'Settings', 'label' => 'Delete an exchange rate', 'description' => 'Delete an exchange rate', 'default' => false, 'timing' => 'immediate', 'handler' => Handlers\ExchangeRateHandler::class],
        'payment_method_create'     => ['module' => 'Settings', 'label' => 'Add a payment channel', 'description' => 'Add a payment channel (cash, mobile money, Lipa Namba)', 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\PaymentChannelHandler::class],
        'payment_method_update'     => ['module' => 'Settings', 'label' => 'Change a payment channel', 'description' => "Change a payment channel's details or switch it on / off", 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\PaymentChannelHandler::class],
        'payment_provider_update'   => ['module' => 'Settings', 'label' => 'Payment provider credentials', 'description' => "Change a payment provider's credentials, URLs or environment", 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\PaymentChannelHandler::class],
        'payment_rules_update'      => ['module' => 'Settings', 'label' => 'Payment rules', 'description' => 'Change the payment window and auto-cancel rules', 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\PaymentChannelHandler::class],
        'integration_create'          => ['module' => 'Settings', 'label' => 'Add an integration', 'description' => 'Add an SMS gateway or SMTP server', 'default' => false, 'timing' => 'immediate', 'handler' => Handlers\IntegrationHandler::class],
        'integration_update'          => ['module' => 'Settings', 'label' => 'Change an integration', 'description' => "Change an SMS gateway's or SMTP server's credentials or settings", 'default' => false, 'timing' => 'immediate', 'handler' => Handlers\IntegrationHandler::class],
        'integration_status_update'   => ['module' => 'Settings', 'label' => 'Integration on / off', 'description' => 'Activate or deactivate an SMS gateway or SMTP server', 'default' => false, 'timing' => 'immediate', 'handler' => Handlers\IntegrationHandler::class],
        'integration_priority_update' => ['module' => 'Settings', 'label' => 'Gateway priority', 'description' => 'Change which SMS gateway / SMTP server is tried first', 'default' => false, 'timing' => 'immediate', 'handler' => Handlers\IntegrationHandler::class],
        'integration_delete'          => ['module' => 'Settings', 'label' => 'Delete an integration', 'description' => 'Delete an inactive SMS gateway or SMTP server', 'default' => false, 'timing' => 'immediate', 'handler' => Handlers\IntegrationHandler::class],
        'notification_template_save'   => ['module' => 'Settings', 'label' => 'Notification template', 'description' => 'Add or change an SMS / email template', 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\NotificationContentHandler::class],
        'notification_template_toggle' => ['module' => 'Settings', 'label' => 'Template on / off', 'description' => 'Switch a notification template on or off', 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\NotificationContentHandler::class],

        // ── Users & Access ──────────────────────────────────────
        'user_create'             => ['module' => 'Users & Access', 'label' => 'Add a user', 'description' => 'Create a new system user', 'default' => false, 'timing' => 'immediate', 'handler' => null],
        'user_update'             => ['module' => 'Users & Access', 'label' => 'Change a user', 'description' => 'Update user details', 'default' => false, 'timing' => 'scheduled', 'handler' => null],
        'user_status_update'      => ['module' => 'Users & Access', 'label' => 'Activate / deactivate a user', 'description' => 'Activate or deactivate a user', 'default' => false, 'timing' => 'scheduled', 'handler' => null],
        'user_roles_update'       => ['module' => 'Users & Access', 'label' => "Change a user's roles", 'description' => "Change a user's assigned roles", 'default' => false, 'timing' => 'scheduled', 'handler' => null],
        'user_password_reset'     => ['module' => 'Users & Access', 'label' => 'Reset a password', 'description' => "Reset a user's password and send them a new one", 'default' => false, 'timing' => 'immediate', 'handler' => Handlers\UserSecurityHandler::class],
        'user_2fa_reset'          => ['module' => 'Users & Access', 'label' => 'Reset two-factor', 'description' => "Remove a user's two-factor setup so they enrol again", 'default' => false, 'timing' => 'immediate', 'handler' => Handlers\UserSecurityHandler::class],
        'role_create'             => ['module' => 'Users & Access', 'label' => 'Add a role', 'description' => 'Create a role', 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\RoleHandler::class],
        'role_update'             => ['module' => 'Users & Access', 'label' => 'Change a role', 'description' => "Change a role's name or description", 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\RoleHandler::class],
        'role_delete'             => ['module' => 'Users & Access', 'label' => 'Delete a role', 'description' => 'Delete a role', 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\RoleHandler::class],
        'role_permissions_update' => ['module' => 'Users & Access', 'label' => 'Role permissions', 'description' => "Change the permissions a role grants", 'default' => false, 'timing' => 'scheduled', 'handler' => Handlers\RoleHandler::class],

        // ── Agency Management ───────────────────────────────────
        'agent_create'         => ['module' => 'Agency Management', 'label' => 'Add an agency', 'description' => 'Create a new agency or partner', 'default' => false, 'timing' => 'immediate', 'handler' => null],
        'agent_update'         => ['module' => 'Agency Management', 'label' => 'Change an agency', 'description' => 'Update agency details, status or commission rules', 'default' => false, 'timing' => 'scheduled', 'handler' => null],
        'agent_ferries_update' => ['module' => 'Agency Management', 'label' => 'Agency vessels', 'description' => 'Assign or update the vessels an agency may sell', 'default' => false, 'timing' => 'scheduled', 'handler' => null],
        'agent_routes_update'  => ['module' => 'Agency Management', 'label' => 'Agency routes', 'description' => 'Assign or update the routes an agency may sell', 'default' => false, 'timing' => 'scheduled', 'handler' => null],
        'agent_topup'          => ['module' => 'Agency Management', 'label' => 'Agency float top-up', 'description' => "Top up an agency's float balance", 'default' => false, 'timing' => 'immediate', 'handler' => null],
        'float_topup'          => ['module' => 'Agency Management', 'label' => 'Float top-up', 'description' => 'Float top-up', 'default' => false, 'timing' => 'immediate', 'handler' => null],
        'agent_adjust_limit'   => ['module' => 'Agency Management', 'label' => 'Agency credit limit', 'description' => "Change an agency's credit limit", 'default' => false, 'timing' => 'immediate', 'handler' => null],
        'associate_create'     => ['module' => 'Agency Management', 'label' => 'Add agency staff', 'description' => 'Register a staff member for an agency', 'default' => false, 'timing' => 'immediate', 'handler' => null],
        'associate_update'     => ['module' => 'Agency Management', 'label' => 'Change agency staff', 'description' => 'Update or deactivate an agency staff member', 'default' => false, 'timing' => 'scheduled', 'handler' => null],

        // ── Legacy (ferry system, not used by the cargo platform) ──
        'ferry_create' => $legacy('Add a new ferry to the fleet'),
        'ferry_update' => $legacy('Modify an existing ferry or its classes'),
        'ferry_delete' => $legacy('Remove a ferry from the fleet'),
        'ferry_status_update' => $legacy('Suspend or activate a ferry'),
        'port_create' => $legacy('Add a new port to the system'),
        'port_update' => $legacy('Modify an existing port'),
        'port_delete' => $legacy('Remove a port from the system'),
        'port_status_update' => $legacy('Suspend or activate a port'),
        'class_create' => $legacy('Add a new ferry class'),
        'class_update' => $legacy('Modify an existing ferry class'),
        'class_delete' => $legacy('Remove a ferry class'),
        'route_delete' => $legacy('Remove a sailing route'),
        'schedule_delete' => $legacy('Remove a trip schedule'),
        'schedule_status_update' => $legacy('Suspend or activate a schedule'),
        'fare_create' => $legacy('Create a new ferry fare entry'),
        'fare_update' => $legacy('Update the ferry fare matrix'),
        'fare_delete' => $legacy('Remove a ferry fare entry'),
        'capacity_update' => $legacy('Change seat capacity for a ferry voyage'),
        'cargo_schedule_create' => $legacy('Create a departure (unused screen)'),
        'cargo_schedule_update' => $legacy('Change a departure (unused screen)'),
        'cargo_schedule_status_update' => $legacy('Change a departure status (unused screen)'),
        'translation_update' => $legacy('Update a translation string'),
        // The announcements screen has no model or table in this platform
        'announcement_create' => $legacy('Publish an announcement (screen not available)'),
        'announcement_toggle' => $legacy('Show or hide an announcement (screen not available)'),
        'announcement_delete' => $legacy('Delete an announcement (screen not available)'),
        'corporate_create' => $legacy('Create a new corporate partner account'),
        'corporate_update' => $legacy('Modify corporate partner account details or credit limit'),
        'corporate_status_update' => $legacy('Suspend, close or activate a corporate partner account'),
        'corporate_invoice_generate' => $legacy('Generate a monthly corporate invoice'),
        'corporate_payment_record' => $legacy('Record a payment against a corporate invoice'),
        'booking_apply_exemption' => $legacy('Issue a complimentary (free) ticket'),
        'booking_complete' => $legacy('Complete payment process for a held booking'),
        'booking_edit' => $legacy('Edit booking details or passenger list'),
        'booking_cancel' => $legacy('Cancel an entire ferry booking and all tickets'),
        'booking_reschedule' => $legacy('Reschedule all tickets in a booking'),
        'ticket_cancel' => $legacy('Cancel a single passenger ticket'),
        'ticket_reschedule' => $legacy('Reschedule a single passenger ticket'),
        'ticket_reprint' => $legacy('Reprint a ticket that was already printed'),
        'class_downgrade' => $legacy('Downgrade passenger ticket to a lower seating class'),
        'activate_agent' => $legacy('Activate agent booking or override'),
        'activate_booking' => $legacy('Reactivate an inactive/cancelled booking'),
        'discount_create' => $legacy('Create a new price discount'),
        'discount_update' => $legacy('Update a price discount'),
        'discount_delete' => $legacy('Delete a price discount'),
        'discount_status_update' => $legacy('Activate or deactivate a price discount'),
        'promotion_create' => $legacy('Create a new promotion'),
        'promotion_update' => $legacy('Update a promotion'),
        'promotion_delete' => $legacy('Delete a promotion'),
        'promotion_status_update' => $legacy('Activate or deactivate a promotion'),
        'coupon_create' => $legacy('Create a new coupon'),
        'coupon_update' => $legacy('Update a coupon'),
        'coupon_delete' => $legacy('Delete a coupon'),
        'coupon_status_update' => $legacy('Activate or deactivate a coupon'),
    ],

    // Actions deliberately left out of maker-checker, with the reason (shown on the rules page)
    'not_eligible' => [
        'Load / offload / collect cargo' => 'High-volume scanner steps at the port; each one is already tracked per item.',
        'Fare override during a booking' => 'Happens inside a live booking. Needs the cargo-override-fare permission and a reason, and is recorded on the booking.',
        'Resend a payment prompt / SMS instructions' => 'Routine follow-up with no money or data change.',
        'Departure weather notes' => 'Information only.',
        'Menu order' => 'Display order only.',
        'Change own password' => 'Personal security action.',
        'Switch a dual-control rule on or off' => 'Putting it under approval could lock everyone out. Every toggle is audit-logged and needs the setting:update permission.',
    ],
];
