<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tablesWithSlug = [
            'products',
            'product_categories',
            'events',
        ];

        foreach ($tablesWithSlug as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'slug')) continue;

            // 1. Convert existing plain string slugs to JSON format {"en": "slug-value"}
            DB::table($table)
                ->whereNotNull('slug')
                ->where('slug', 'NOT LIKE', '{%')
                ->update([
                    'slug' => DB::raw("JSON_OBJECT('en', slug)")
                ]);

            // 2. Alter column type to JSON
            DB::statement("ALTER TABLE {$table} MODIFY slug JSON");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tablesWithSlug = [
            'products',
            'product_categories',
            'events',
        ];

        foreach ($tablesWithSlug as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'slug')) continue;
            DB::statement("ALTER TABLE {$table} MODIFY slug VARCHAR(255)");
        }
    }
};
