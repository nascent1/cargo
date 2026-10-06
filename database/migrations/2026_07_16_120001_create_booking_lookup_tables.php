<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. ID Types Lookup
        Schema::create('id_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('label', 100);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. Customer Statuses Lookup
        Schema::create('customer_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('label', 100);
            $table->string('color_badge', 50)->default('slate'); // slate, green, red, amber
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // 3. Add optional agent_code override to users
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'agent_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('agent_code', 3)->nullable()->after('domain');
            });
        }

        // Seed initial values right away
        $now = now();
        DB::table('id_types')->insert([
            ['code' => 'passport', 'label' => 'Passport', 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'national_id', 'label' => 'National ID / NIDA', 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'voters_id', 'label' => 'Voter Card', 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'driving_license', 'label' => 'Driving License', 'is_active' => true, 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'none', 'label' => 'None / Child', 'is_active' => true, 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('customer_statuses')->insert([
            ['code' => 'active', 'label' => 'Active', 'color_badge' => 'green', 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'suspended', 'label' => 'Suspended', 'color_badge' => 'amber', 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'blacklisted', 'label' => 'Blacklisted', 'color_badge' => 'red', 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Seed ticket prefix configs into system_configs if not exists
        if (Schema::hasTable('system_configs')) {
            DB::table('system_configs')->insertOrIgnore([
                [
                    'key_name' => 'ticket_prefix_default',
                    'value' => 'VFF',
                    'description' => 'Default 3-character initial prefix for standard tickets (e.g. VFF for Victoria Ferries)',
                    'is_sensitive' => false,
                    'group' => 'booking',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'key_name' => 'ticket_prefix_agent',
                    'value' => 'VAF',
                    'description' => '3-character initial prefix for tickets booked by agents (e.g. VAF for Victoria Agent Ferries)',
                    'is_sensitive' => false,
                    'group' => 'booking',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'agent_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('agent_code');
            });
        }
        Schema::dropIfExists('customer_statuses');
        Schema::dropIfExists('id_types');
    }
};
