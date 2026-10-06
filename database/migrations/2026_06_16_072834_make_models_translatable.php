<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tablesAndColumns = [
            'events' => ['title', 'description'],
            'honey_types' => ['name', 'description'],
            'product_categories' => ['name', 'description'],
            'products' => ['name', 'description', 'features'],
            'training_sessions' => ['title', 'description']
        ];

        foreach ($tablesAndColumns as $table => $columns) {
            if (!Schema::hasTable($table)) continue;

            // 1. Manually format existing string data to JSON {"en": "Value"}
            // so we don't lose data when converting to JSON column.
            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) continue;
                
                // Ignore nulls or already JSON-like data
                DB::table($table)
                    ->whereNotNull($column)
                    ->where($column, 'NOT LIKE', '{%')
                    ->update([
                        $column => DB::raw("JSON_OBJECT('en', {$column})")
                    ]);
            }

            // 2. Alter column types to JSON
            Schema::table($table, function (Blueprint $tableBlueprint) use ($columns) {
                foreach ($columns as $column) {
                    // $tableBlueprint->json($column)->change(); // This requires doctrine/dbal in some versions
                    // We'll use raw SQL to avoid dbal dependency issues just in case
                }
            });

            // Running raw alter for MariaDB/MySQL compatibility
            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) continue;
                DB::statement("ALTER TABLE {$table} MODIFY {$column} JSON");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting JSON back to TEXT
        $tablesAndColumns = [
            'events' => ['title', 'description'],
            'honey_types' => ['name', 'description'],
            'product_categories' => ['name', 'description'],
            'products' => ['name', 'description', 'features'],
            'training_sessions' => ['title', 'description']
        ];

        foreach ($tablesAndColumns as $table => $columns) {
            if (!Schema::hasTable($table)) continue;
            foreach ($columns as $column) {
                DB::statement("ALTER TABLE {$table} MODIFY {$column} TEXT");
            }
        }
    }
};
