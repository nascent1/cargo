<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        DB::table('role_permissions')->truncate();
        DB::table('permissions')->truncate();
        DB::table('roles')->truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $now = now();

        // ── ROLES (10 per SRS §3.1) ─────────────────────────────
        $roles = [
            ['name' => 'super_admin',       'slug' => 'super_admin',       'display_name' => 'Super Administrator',   'description' => 'Full system access', 'is_system' => true],
            ['name' => 'admin',             'slug' => 'admin',             'display_name' => 'Administrator',         'description' => 'Platform administration', 'is_system' => true],
            ['name' => 'finance_officer',   'slug' => 'finance_officer',   'display_name' => 'Finance Officer',       'description' => 'Payment and financial operations', 'is_system' => false],
            ['name' => 'sales_officer',     'slug' => 'sales_officer',     'display_name' => 'Sales Officer',         'description' => 'Counter sales and ticketing officer', 'is_system' => true],
            ['name' => 'booking_officer',   'slug' => 'booking_officer',   'display_name' => 'Booking Officer',       'description' => 'Ferry booking and check-in officer', 'is_system' => true],
            ['name' => 'counter_staff',     'slug' => 'counter_staff',     'display_name' => 'Counter Staff',         'description' => 'POS counter ticketing staff', 'is_system' => true],
            ['name' => 'agent',             'slug' => 'agent',             'display_name' => 'Travel Agent',          'description' => 'Certified external travel partner agency', 'is_system' => false],
            ['name' => 'associate_agent',   'slug' => 'associate_agent',   'display_name' => 'Associate Agent',       'description' => 'Sub-agent operating under agency ceiling', 'is_system' => false],
        ];

        foreach ($roles as &$role) {
            $role['created_at'] = $now;
            $role['updated_at'] = $now;
        }
        DB::table('roles')->insert($roles);

        // ── PERMISSIONS (41 per SRS §3.2) ─────────────────────────
        $permissions = [
            // User Management
            ['name' => 'user:list',     'display_name' => 'List Users',       'module' => 'user'],
            ['name' => 'user:read',     'display_name' => 'View User',        'module' => 'user'],
            ['name' => 'user:create',   'display_name' => 'Create User',      'module' => 'user'],
            ['name' => 'user:update',   'display_name' => 'Update User',      'module' => 'user'],
            ['name' => 'user:delete',   'display_name' => 'Delete User',      'module' => 'user'],
            // Role
            ['name' => 'role:list',     'display_name' => 'List Roles',       'module' => 'role'],
            ['name' => 'role:assign',   'display_name' => 'Assign Role',      'module' => 'role'],
            ['name' => 'role:manage',   'display_name' => 'Manage Roles',     'module' => 'role'],
           // Dual Control
            ['name' => 'dc:list',    'display_name' => 'List DC Requests',    'module' => 'dual_control'],
            ['name' => 'dc:read',    'display_name' => 'View DC Request',     'module' => 'dual_control'],
            ['name' => 'dc:approve', 'display_name' => 'Approve/Reject DC',   'module' => 'dual_control'],
            // Audit
            ['name' => 'audit:read',   'display_name' => 'View Audit Logs',      'module' => 'audit'],
            ['name' => 'audit:verify', 'display_name' => 'Verify Audit Chain',   'module' => 'audit'],
            // Settings
            ['name' => 'setting:read',   'display_name' => 'View Settings',     'module' => 'setting'],
            ['name' => 'setting:update', 'display_name' => 'Update Settings',   'module' => 'setting'],
            // Report
            ['name' => 'report:view',     'display_name' => 'View Reports',     'module' => 'report'],
            ['name' => 'report:export',   'display_name' => 'Export Reports',   'module' => 'report'],
            ['name' => 'report:view_all', 'display_name' => 'View All Reports', 'module' => 'report'],
            // Dashboard (3-tab system)
            ['name' => 'dashboard:view',           'display_name' => 'View Dashboard',             'module' => 'dashboard'],
            ['name' => 'dashboard:operations',     'display_name' => 'View Operations Tab',        'module' => 'dashboard'],
            ['name' => 'dashboard:finance',        'display_name' => 'View Finance Tab',           'module' => 'dashboard'],
            ['name' => 'dashboard:agent_perf',     'display_name' => 'View Agent Performance Tab', 'module' => 'dashboard'],
            
            // Ferry Operations
            ['name' => 'ferry:list',    'display_name' => 'List Ferries',       'module' => 'ferry'],
            ['name' => 'ferry:create',  'display_name' => 'Create Ferry',       'module' => 'ferry'],
            ['name' => 'ferry:update',  'display_name' => 'Update Ferry',       'module' => 'ferry'],
            ['name' => 'ferry:delete',  'display_name' => 'Delete Ferry',       'module' => 'ferry'],
            
            ['name' => 'class:list',    'display_name' => 'List Ferry Classes', 'module' => 'ferry_class'],
            ['name' => 'class:create',  'display_name' => 'Create Ferry Class', 'module' => 'ferry_class'],
            ['name' => 'class:update',  'display_name' => 'Update Ferry Class', 'module' => 'ferry_class'],
            ['name' => 'class:delete',  'display_name' => 'Delete Ferry Class', 'module' => 'ferry_class'],
            
            ['name' => 'port:list',     'display_name' => 'List Ports',         'module' => 'port'],
            ['name' => 'port:create',   'display_name' => 'Create Port',        'module' => 'port'],
            ['name' => 'port:update',   'display_name' => 'Update Port',        'module' => 'port'],
            ['name' => 'port:delete',   'display_name' => 'Delete Port',        'module' => 'port'],
            
            ['name' => 'route:list',    'display_name' => 'List Routes',        'module' => 'route'],
            ['name' => 'route:create',  'display_name' => 'Create Route',       'module' => 'route'],
            ['name' => 'route:update',  'display_name' => 'Update Route',       'module' => 'route'],
            ['name' => 'route:delete',  'display_name' => 'Delete Route',       'module' => 'route'],
            
            ['name' => 'trip:list',     'display_name' => 'List Trips',         'module' => 'trip'],
            ['name' => 'trip:create',   'display_name' => 'Create Trip',        'module' => 'trip'],
            ['name' => 'trip:update',   'display_name' => 'Update Trip',        'module' => 'trip'],
            ['name' => 'trip:delete',   'display_name' => 'Delete Trip',        'module' => 'trip'],
            
            ['name' => 'schedule:list', 'display_name' => 'List Schedules',     'module' => 'schedule'],
            ['name' => 'schedule:create','display_name' => 'Create Schedule',    'module' => 'schedule'],
            ['name' => 'schedule:update','display_name' => 'Update Schedule',    'module' => 'schedule'],
            ['name' => 'schedule:delete','display_name' => 'Delete Schedule',    'module' => 'schedule'],
            
            ['name' => 'fare:list',     'display_name' => 'List Fares',         'module' => 'fare'],
            ['name' => 'fare:create',   'display_name' => 'Create Fare',        'module' => 'fare'],
            ['name' => 'fare:update',   'display_name' => 'Update Fare',        'module' => 'fare'],
            ['name' => 'fare:delete',   'display_name' => 'Delete Fare',        'module' => 'fare'],

            ['name' => 'voyage:read',       'display_name' => 'View Voyages',           'module' => 'voyage'],
            ['name' => 'voyage:update',     'display_name' => 'Update Voyages',         'module' => 'voyage'],
            ['name' => 'booking:read',      'display_name' => 'View Bookings',          'module' => 'booking'],
            ['name' => 'booking:view_all',  'display_name' => 'View All Bookings',      'module' => 'booking'],
            ['name' => 'booking:create',    'display_name' => 'Create Bookings',        'module' => 'booking'],
            ['name' => 'booking:update',    'display_name' => 'Update Bookings',        'module' => 'booking'],
            ['name' => 'booking:complete',  'display_name' => 'Complete Booking',       'module' => 'booking'],
            ['name' => 'booking:edit',      'display_name' => 'Edit Booking',           'module' => 'booking'],
            ['name' => 'booking:cancel',    'display_name' => 'Cancel Booking',         'module' => 'booking'],
            ['name' => 'booking:reschedule','display_name' => 'Reschedule Booking',     'module' => 'booking'],
            ['name' => 'booking:print',     'display_name' => 'Print All Tickets',      'module' => 'booking'],
            ['name' => 'booking:multi_edit','display_name' => 'Multi-Edit Booking',     'module' => 'booking'],
            ['name' => 'booking:downgrade_class', 'display_name' => 'Downgrade Booking Class', 'module' => 'booking'],
            ['name' => 'booking:reprint_unlimited', 'display_name' => 'Reprint Tickets Without Approval', 'module' => 'booking'],
            ['name' => 'booking:apply_exemption', 'display_name' => 'Apply Fare Exemption (Complimentary)', 'module' => 'booking'],
            ['name' => 'booking:corporate_booking', 'display_name' => 'Create Corporate Bookings', 'module' => 'booking'],
            ['name' => 'corporate:manage', 'display_name' => 'Manage Corporate Accounts', 'module' => 'corporate'],
            ['name' => 'ticket:cancel',     'display_name' => 'Cancel Single Ticket',   'module' => 'ticket'],
            ['name' => 'ticket:reschedule', 'display_name' => 'Reschedule Single Ticket','module' => 'ticket'],
            ['name' => 'ticket:print',      'display_name' => 'Print Single Ticket',    'module' => 'ticket'],
            ['name' => 'ticket:multi_edit', 'display_name' => 'Multi-Edit Single Ticket', 'module' => 'ticket'],
            ['name' => 'ticket:downgrade_class', 'display_name' => 'Downgrade Single Ticket Class', 'module' => 'ticket'],
            ['name' => 'ticket:reprint_unlimited', 'display_name' => 'Reprint Single Ticket Without Approval', 'module' => 'ticket'],
            ['name' => 'activate_agent',    'display_name' => 'Activate Agent Booking', 'module' => 'booking'],
            ['name' => 'activate_booking',  'display_name' => 'Activate Booking',       'module' => 'booking'],
            ['name' => 'portal:access',     'display_name' => 'Access Sales Portal',    'module' => 'portal'],
            ['name' => 'admin_portal:access','display_name' => 'Access Admin Portal',   'module' => 'portal'],
            ['name' => 'ticketing:issue',   'display_name' => 'Issue POS Tickets',      'module' => 'portal'],
            ['name' => 'wallet:view',       'display_name' => 'View Agency Wallet',     'module' => 'portal'],
            ['name' => 'wallet:topup',      'display_name' => 'Request Wallet Top-Up',  'module' => 'portal'],
            ['name' => 'associates:manage', 'display_name' => 'Manage Sub-Agents',      'module' => 'portal'],

            // Payment
            ['name' => 'payment:read',   'display_name' => 'View Payments',      'module' => 'payment'],
            ['name' => 'payment:update', 'display_name' => 'Update Payments',    'module' => 'payment'],
            ['name' => 'payment:refund', 'display_name' => 'Process Refunds',    'module' => 'payment'],

            // Discount & Promotions
            ['name' => 'discount:read',   'display_name' => 'View Discounts',     'module' => 'discount'],
            ['name' => 'discount:create', 'display_name' => 'Create Discounts',   'module' => 'discount'],
            ['name' => 'discount:update', 'display_name' => 'Update Discounts',   'module' => 'discount'],
            ['name' => 'discount:delete', 'display_name' => 'Delete Discounts',   'module' => 'discount'],

            // Boarding & Operations
            ['name' => 'boarding:read',   'display_name' => 'View Boarding',       'module' => 'boarding'],
            ['name' => 'boarding:create', 'display_name' => 'Create Boarding',     'module' => 'boarding'],
            ['name' => 'boarding:update', 'display_name' => 'Update Boarding',     'module' => 'boarding'],
            ['name' => 'boarding:scan',   'display_name' => 'Scan Boarding Pass',  'module' => 'boarding'],

            // Notifications & Announcements
            ['name' => 'notification:read',   'display_name' => 'View Notifications',    'module' => 'notification'],
            ['name' => 'notification:create', 'display_name' => 'Create Notifications',  'module' => 'notification'],
            ['name' => 'notification:update', 'display_name' => 'Update Notifications',  'module' => 'notification'],
            ['name' => 'notification:delete', 'display_name' => 'Delete Notifications',  'module' => 'notification'],

            // Availability
            ['name' => 'availability:read',   'display_name' => 'View Availability',     'module' => 'availability'],
            ['name' => 'availability:manage', 'display_name' => 'Manage Availability',   'module' => 'availability'],
        ];

        foreach ($permissions as &$perm) {
            $perm['is_system'] = true;
            $perm['description'] = $perm['display_name'];
            $perm['created_at'] = $now;
            $perm['updated_at'] = $now;
        }
        DB::table('permissions')->insert($permissions);

        // ── ROLE → PERMISSION MAPPINGS ─────────────────────────────
        $allPermIds = DB::table('permissions')->pluck('id', 'name');

        // Super Admin gets every permission explicitly
        $superAdminRole = DB::table('roles')->where('slug', 'super_admin')->value('id');
        $allPermArray = $allPermIds->values()->toArray();
        foreach ($allPermArray as $pid) {
            DB::table('role_permissions')->insert(['role_id' => $superAdminRole, 'permission_id' => $pid]);
        }

        // Admin gets everything as well
        $adminRole = DB::table('roles')->where('slug', 'admin')->value('id');
        foreach ($allPermArray as $pid) {
            DB::table('role_permissions')->insert(['role_id' => $adminRole, 'permission_id' => $pid]);
        }





        // Finance Officer
        $foFinRole = DB::table('roles')->where('slug', 'finance_officer')->value('id');
        foreach (['admin_portal:access','dashboard:view','dashboard:finance','report:view','report:view_all','report:export','corporate:manage'] as $p) {
            if (isset($allPermIds[$p])) {
                DB::table('role_permissions')->insert(['role_id' => $foFinRole, 'permission_id' => $allPermIds[$p]]);
            }
        }



        // Sales Officer
        $salesOfficerRole = DB::table('roles')->where('slug', 'sales_officer')->value('id');
        foreach (['portal:access','ticketing:issue','dashboard:view','booking:read','booking:view_all','booking:create','booking:print','ticket:print','booking:apply_exemption','booking:corporate_booking'] as $p) {
            if (isset($allPermIds[$p])) {
                DB::table('role_permissions')->insert(['role_id' => $salesOfficerRole, 'permission_id' => $allPermIds[$p]]);
            }
        }

        // Booking Officer & Counter Staff
        foreach (['booking_officer', 'counter_staff'] as $roleSlug) {
            $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');
            foreach (['portal:access','ticketing:issue','dashboard:view','booking:read','booking:create','booking:print','ticket:print'] as $p) {
                if (isset($allPermIds[$p]) && $roleId) {
                    DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $allPermIds[$p]]);
                }
            }
        }

        // Travel Agent
        $agentRole = DB::table('roles')->where('slug', 'agent')->value('id');
        foreach (['portal:access','ticketing:issue','wallet:view','wallet:topup','associates:manage','dashboard:view','booking:read','booking:create','booking:print','ticket:print'] as $p) {
            if (isset($allPermIds[$p]) && $agentRole) {
                DB::table('role_permissions')->insert(['role_id' => $agentRole, 'permission_id' => $allPermIds[$p]]);
            }
        }

        // Associate Agent (Sub-Agent)
        $assocRole = DB::table('roles')->where('slug', 'associate_agent')->value('id');
        foreach (['portal:access','ticketing:issue','wallet:view','dashboard:view','booking:read','booking:create','booking:print','ticket:print'] as $p) {
            if (isset($allPermIds[$p]) && $assocRole) {
                DB::table('role_permissions')->insert(['role_id' => $assocRole, 'permission_id' => $allPermIds[$p]]);
            }
        }
    }
}
