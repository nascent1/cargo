<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cargo reports menu entry, menu fixes, and indexes for dashboard/report queries.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Reports: top-level entry under Dashboard / Dual Control
        if (!DB::table('menus')->where('route', 'admin.reports.index')->exists()) {
            DB::table('menus')->insert([
                'title' => 'Reports',
                'route' => 'admin.reports.index',
                'icon' => 'fa-chart-line',
                'parent_id' => null,
                'permission_name' => 'cargo-report',
                'section' => 'main',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Dashboard points at the cargo dashboard directly (so it highlights when open)
        DB::table('menus')->where('route', 'admin.dashboard')->whereNull('parent_id')
            ->update(['route' => 'admin.cargo.dashboard', 'updated_at' => now()]);

        // "cargo-create" doesn't exist — booking is cargo-book
        DB::table('menus')->where('route', 'admin.cargo.booking')->where('permission_name', 'cargo-create')
            ->update(['permission_name' => 'cargo-book', 'updated_at' => now()]);

        Schema::table('consignments', function (Blueprint $table) {
            $table->index('created_at', 'idx_consignments_created_at');
            $table->index('payment_status', 'idx_consignments_payment_status');
        });
        Schema::table('cargo_payments', function (Blueprint $table) {
            $table->index(['status', 'confirmed_at'], 'idx_cargo_payments_status_confirmed');
        });
    }

    public function down(): void
    {
        Schema::table('cargo_payments', function (Blueprint $table) {
            $table->dropIndex('idx_cargo_payments_status_confirmed');
        });
        Schema::table('consignments', function (Blueprint $table) {
            $table->dropIndex('idx_consignments_created_at');
            $table->dropIndex('idx_consignments_payment_status');
        });

        DB::table('menus')->where('route', 'admin.cargo.booking')->where('permission_name', 'cargo-book')
            ->update(['permission_name' => 'cargo-create', 'updated_at' => now()]);
        DB::table('menus')->where('route', 'admin.cargo.dashboard')->whereNull('parent_id')
            ->update(['route' => 'admin.dashboard', 'updated_at' => now()]);
        DB::table('menus')->where('route', 'admin.reports.index')->delete();
    }
};
