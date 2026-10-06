<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tablesAndColumns = [
            'regions' => ['name'],
            'districts' => ['name'],
            'wards' => ['name'],
            'memberships' => ['tier_name', 'description', 'benefits', 'amenities'],
        ];

        foreach ($tablesAndColumns as $table => $columns) {
            if (!Schema::hasTable($table)) continue;

            // 1. Manually format existing string data to JSON {"en": "Value"}
            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) continue;
                // For longtext or mediumtext that might already be JSON or null, be careful
                DB::statement("UPDATE {$table} SET {$column} = JSON_OBJECT('en', {$column}) WHERE JSON_VALID({$column}) = 0 AND {$column} IS NOT NULL");
            }

            if ($table === 'regions') {
                try { DB::statement("ALTER TABLE regions DROP INDEX regions_name_unique"); } catch (\Exception $e) {}
                try { DB::statement("ALTER TABLE regions DROP INDEX name"); } catch (\Exception $e) {}
            }
            if ($table === 'wards') {
                try { DB::statement("ALTER TABLE wards DROP INDEX wards_district_id_name_index"); } catch (\Exception $e) {}
            }

            // Running raw alter for MariaDB/MySQL compatibility
            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) continue;
                try {
                    DB::statement("ALTER TABLE {$table} MODIFY {$column} JSON");
                } catch (\Exception $e) {
                    // Fallback or re-try
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
