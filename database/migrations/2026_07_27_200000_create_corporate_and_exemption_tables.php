<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Corporates ──────────────────────────────────────────
        if (!Schema::hasTable('corporates')) {
            Schema::create('corporates', function (Blueprint $table) {
                $table->id();
                $table->string('name', 200);
                $table->string('registration_number', 100)->nullable(); // TIN / Business Reg
                $table->string('contact_person', 150)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('phone', 30)->nullable();
                $table->text('address')->nullable();
                $table->unsignedInteger('payment_terms_days')->default(30); // Net-30 default
                $table->decimal('credit_limit', 15, 2)->default(0);
                $table->decimal('current_balance', 15, 2)->default(0); // Outstanding amount
                $table->string('status', 20)->default('active'); // active, suspended, closed
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('status');
            });
        }

        // ── 2. Corporate Invoices ──────────────────────────────────
        if (!Schema::hasTable('corporate_invoices')) {
            Schema::create('corporate_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('corporate_id')->constrained('corporates')->cascadeOnDelete();
                $table->string('invoice_number', 50)->unique();
                $table->date('period_start');
                $table->date('period_end');
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->string('status', 20)->default('draft'); // draft, sent, paid, overdue, partially_paid
                $table->date('due_date');
                $table->datetime('paid_at')->nullable();
                $table->decimal('paid_amount', 15, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['corporate_id', 'status']);
            });
        }

        // ── 3. Add corporate_id to bookings ────────────────────────
        if (!Schema::hasColumn('bookings', 'corporate_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->foreignId('corporate_id')->nullable()->after('notes')->constrained('corporates')->nullOnDelete();
            });
        }

        // ── 4. Add corporate_work_id to booking_items ──────────────
        if (!Schema::hasColumn('tickets', 'corporate_work_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->string('corporate_work_id', 100)->nullable()->after('nationality_type');
            });
        }

        // ── 5. Seed new permissions ────────────────────────────────
        $permissions = [
            [
                'name' => 'booking:apply_exemption',
                'display_name' => 'Apply Fare Exemption (Complimentary)',
                'module' => 'booking',
                'description' => 'Allows issuing complimentary (free) tickets for individual passengers during booking.',
                'is_system' => true,
            ],
            [
                'name' => 'booking:corporate_booking',
                'display_name' => 'Create Corporate Bookings',
                'module' => 'booking',
                'description' => 'Allows creating bookings charged to a corporate account.',
                'is_system' => true,
            ],
            [
                'name' => 'corporate:manage',
                'display_name' => 'Manage Corporate Accounts',
                'module' => 'corporate',
                'description' => 'Allows creating, editing, and managing corporate partner accounts and invoices.',
                'is_system' => true,
            ],
        ];

        foreach ($permissions as $perm) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $perm['name']],
                array_merge($perm, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // ── 6. Assign permissions to roles ─────────────────────────
        $allPermNames = ['booking:apply_exemption', 'booking:corporate_booking', 'corporate:manage'];
        $permIds = DB::table('permissions')->whereIn('name', $allPermNames)->pluck('id', 'name');

        // Super Admin and Admin get all 3 permissions
        foreach (['super_admin', 'admin'] as $roleSlug) {
            $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');
            if ($roleId) {
                foreach ($permIds as $permId) {
                    DB::table('role_permissions')->updateOrInsert([
                        'role_id' => $roleId,
                        'permission_id' => $permId
                    ]);
                }
            }
        }

        // Sales Officer gets booking:apply_exemption and booking:corporate_booking
        $salesRoleId = DB::table('roles')->where('slug', 'sales_officer')->value('id');
        if ($salesRoleId) {
            foreach (['booking:apply_exemption', 'booking:corporate_booking'] as $pName) {
                if (isset($permIds[$pName])) {
                    DB::table('role_permissions')->updateOrInsert([
                        'role_id' => $salesRoleId,
                        'permission_id' => $permIds[$pName]
                    ]);
                }
            }
        }

        // Finance Officer gets corporate:manage
        $finRoleId = DB::table('roles')->where('slug', 'finance_officer')->value('id');
        if ($finRoleId && isset($permIds['corporate:manage'])) {
            DB::table('role_permissions')->updateOrInsert([
                'role_id' => $finRoleId,
                'permission_id' => $permIds['corporate:manage']
            ]);
        }

        // ── 7. Add Corporate Partners to Admin Portal Menu ─────────
        $adminParent = DB::table('menus')->where('title', 'Administration')->where('section', 'administration')->whereNull('parent_id')->first();
        if ($adminParent) {
            DB::table('menus')->updateOrInsert(
                ['route' => 'admin.corporates.index'],
                [
                    'title' => 'Corporate Partners',
                    'icon' => 'fa-building',
                    'permission_name' => 'corporate:manage',
                    'parent_id' => $adminParent->id,
                    'section' => 'administration',
                    'sort_order' => 3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // ── 8. Register Dual Control / Dual Activity Rules ─────────
        $dcConfigs = [
            ['module' => 'Corporate Management', 'operation_type' => 'corporate_create', 'description' => 'Create a new corporate partner account', 'requires_dual_control' => false],
            ['module' => 'Corporate Management', 'operation_type' => 'corporate_update', 'description' => 'Modify corporate partner account details or credit limit', 'requires_dual_control' => true],
            ['module' => 'Corporate Management', 'operation_type' => 'corporate_status_update', 'description' => 'Suspend, close or activate a corporate partner account', 'requires_dual_control' => true],
            ['module' => 'Corporate Management', 'operation_type' => 'corporate_invoice_generate', 'description' => 'Generate a monthly corporate invoice', 'requires_dual_control' => false],
            ['module' => 'Corporate Management', 'operation_type' => 'corporate_payment_record', 'description' => 'Record a payment against a corporate invoice', 'requires_dual_control' => true],
            ['module' => 'Booking Management', 'operation_type' => 'booking_apply_exemption', 'description' => 'Issue a complimentary (free) ticket with 100% fare exemption', 'requires_dual_control' => false],
        ];

        foreach ($dcConfigs as $config) {
            DB::table('dual_control_configs')->updateOrInsert(
                ['operation_type' => $config['operation_type']],
                array_merge($config, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    public function down(): void
    {
        // Remove dual control configs
        DB::table('dual_control_configs')->whereIn('operation_type', [
            'corporate_create',
            'corporate_update',
            'corporate_status_update',
            'corporate_invoice_generate',
            'corporate_payment_record',
            'booking_apply_exemption',
        ])->delete();

        // Remove menu item
        DB::table('menus')->where('route', 'admin.corporates.index')->delete();

        // Remove role permissions and permissions
        $permIds = DB::table('permissions')->whereIn('name', [
            'booking:apply_exemption',
            'booking:corporate_booking',
            'corporate:manage',
        ])->pluck('id');

        DB::table('role_permissions')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->whereIn('id', $permIds)->delete();

        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'corporate_work_id')) {
                $table->dropColumn('corporate_work_id');
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'corporate_id')) {
                $table->dropForeign(['corporate_id']);
                $table->dropColumn('corporate_id');
            }
        });

        Schema::dropIfExists('corporate_invoices');
        Schema::dropIfExists('corporates');
    }
};
