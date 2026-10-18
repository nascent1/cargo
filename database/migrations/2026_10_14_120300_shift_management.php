<?php

use App\Models\DualControlConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shift management for sales-portal clerks.
 *
 * The 2026_07_19 shift migrations are recorded as run but their tables are missing here, so they
 * are created when absent (same schema). Shifts also record how they ended and how much time
 * extensions added; extension requests themselves are dual-control requests against the shift.
 *
 * Settings move into a "shifts" group; default_shift_hours (0.03, i.e. ~2 minutes) becomes 8.
 * Adds shift:read / shift:manage and the Operations › Shifts menu.
 */
return new class extends Migration
{
    private const SETTINGS = [
        'shift_rest_hours' => ['8', 'Hours after a shift ends before the next one starts on its own; sooner needs an approved extension'],
        'shift_warning_minutes' => ['15', 'Warn the clerk this many minutes before the shift ends'],
    ];

    private const PERMISSIONS = [
        'shift:read' => ['View shifts', 'See who is on shift, shift history and cash handover summaries', ['super_admin', 'admin', 'finance_officer']],
        'shift:manage' => ['Manage shifts', 'End a clerk\'s shift or request an extension for it', ['super_admin', 'admin']],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('shifts')) {
            Schema::create('shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamp('start_time');
                $table->timestamp('end_time')->nullable();
                $table->timestamp('actual_end_time')->nullable();
                $table->enum('status', ['active', 'ended'])->default('active');
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('shift_overtime_requests')) {
            Schema::create('shift_overtime_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->decimal('requested_hours', 5, 2);
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('reason')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('shifts', function (Blueprint $table) {
            if (!Schema::hasColumn('shifts', 'extended_minutes')) {
                $table->unsignedInteger('extended_minutes')->default(0)->after('actual_end_time');
            }
            if (!Schema::hasColumn('shifts', 'end_reason')) {
                $table->string('end_reason', 20)->nullable()->after('status'); // expired | self | admin
            }
            if (!Schema::hasColumn('shifts', 'ended_by')) {
                $table->foreignId('ended_by')->nullable()->after('end_reason')->constrained('users')->nullOnDelete();
            }
            $table->index(['user_id', 'status']);
            $table->index('start_time');
        });

        $now = now();
        DB::table('system_configs')->whereIn('key_name', ['shift_management_enabled', 'default_shift_hours'])
            ->update(['group' => 'shifts', 'updated_at' => $now]);
        DB::table('system_configs')->where('key_name', 'default_shift_hours')->whereRaw("value ~ '^[0-9.]+$' AND value::numeric < 1")
            ->update(['value' => '8', 'updated_at' => $now]);
        DB::table('system_configs')->where('key_name', 'shift_management_enabled')
            ->update(['description' => 'Sales-portal clerks work in shifts: booking, payment and cancelling stop when the shift ends', 'updated_at' => $now]);
        DB::table('system_configs')->where('key_name', 'default_shift_hours')
            ->update(['description' => 'Length of a shift in hours, from the clerk\'s first booking action', 'updated_at' => $now]);
        foreach (self::SETTINGS as $key => [$value, $description]) {
            DB::table('system_configs')->insertOrIgnore([
                'key_name' => $key, 'value' => $value, 'description' => $description,
                'is_sensitive' => false, 'group' => 'shifts', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        foreach (self::PERMISSIONS as $name => [$display, $description, $roles]) {
            DB::table('permissions')->updateOrInsert(['name' => $name], [
                'display_name' => $display, 'module' => 'shift', 'description' => $description,
                'is_system' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $permissionId = DB::table('permissions')->where('name', $name)->value('id');
            foreach (DB::table('roles')->whereIn('slug', $roles)->pluck('id') as $roleId) {
                DB::table('role_permissions')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
            }
        }

        $operations = DB::table('menus')->whereNull('parent_id')->where('title', 'Operations')->value('id');
        if ($operations && !DB::table('menus')->where('route', 'admin.shifts.index')->exists()) {
            DB::table('menus')->insert([
                'title' => 'Shifts', 'route' => 'admin.shifts.index', 'icon' => 'fas fa-user-clock',
                'parent_id' => $operations, 'permission_name' => 'shift:read', 'section' => 'main',
                'sort_order' => (int) DB::table('menus')->where('parent_id', $operations)->max('sort_order') + 1,
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // shift_extension rule (registry default: approval required)
        DualControlConfig::syncFromRegistry();
    }

    /**
     * Leaves the shift tables in place (they belong to the 2026_07_19 migrations) and the shift
     * length as set; a full revert restores the pre-task database dump.
     */
    public function down(): void
    {
        DB::table('menus')->where('route', 'admin.shifts.index')->delete();
        foreach (array_keys(self::PERMISSIONS) as $name) {
            $permissionId = DB::table('permissions')->where('name', $name)->value('id');
            DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('user_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
        DB::table('system_configs')->whereIn('key_name', array_keys(self::SETTINGS))->delete();
        DB::table('system_configs')->whereIn('key_name', ['shift_management_enabled', 'default_shift_hours'])->update(['group' => 'security']);

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['start_time']);
            $table->dropConstrainedForeignId('ended_by');
            $table->dropColumn(['extended_minutes', 'end_reason']);
        });
    }
};
