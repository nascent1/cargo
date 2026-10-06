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
        Schema::create('passenger_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('code', 20)->unique();
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('min_age')->nullable();
            $table->unsignedInteger('max_age')->nullable();
            $table->timestamps();
        });

        // Insert defaults
        \Illuminate\Support\Facades\DB::table('passenger_types')->insert([
            ['name' => 'Adult', 'code' => 'adult', 'is_default' => true, 'min_age' => 12, 'max_age' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Child', 'code' => 'child', 'is_default' => false, 'min_age' => 2, 'max_age' => 11, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Infant', 'code' => 'infant', 'is_default' => false, 'min_age' => 0, 'max_age' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Add to passengers
        if (!Schema::hasColumn('passengers', 'passenger_type_id')) {
            Schema::table('passengers', function (Blueprint $table) {
                $table->foreignId('passenger_type_id')->nullable()->constrained('passenger_types')->nullOnDelete();
            });
        }
        
        // Add to booking_items
        if (!Schema::hasColumn('tickets', 'passenger_type_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->foreignId('passenger_type_id')->nullable()->constrained('passenger_types')->nullOnDelete();
            });
        }

        // Migrate data
        $types = \Illuminate\Support\Facades\DB::table('passenger_types')->pluck('id', 'code');
        foreach ($types as $code => $id) {
            \Illuminate\Support\Facades\DB::table('passengers')->where('passenger_type', $code)->orWhere('passenger_type', ucfirst($code))->update(['passenger_type_id' => $id]);
            \Illuminate\Support\Facades\DB::table('tickets')->where('passenger_type', $code)->orWhere('passenger_type', ucfirst($code))->update(['passenger_type_id' => $id]);
        }

        // Fallback for any unmapped
        $defaultId = \Illuminate\Support\Facades\DB::table('passenger_types')->where('is_default', true)->value('id');
        if ($defaultId) {
            \Illuminate\Support\Facades\DB::table('passengers')->whereNull('passenger_type_id')->update(['passenger_type_id' => $defaultId]);
            \Illuminate\Support\Facades\DB::table('tickets')->whereNull('passenger_type_id')->update(['passenger_type_id' => $defaultId]);
        }

        // Drop old columns
        if (Schema::hasColumn('passengers', 'passenger_type')) {
            Schema::table('passengers', function (Blueprint $table) {
                $table->dropColumn('passenger_type');
            });
        }
        if (Schema::hasColumn('tickets', 'passenger_type')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropColumn('passenger_type');
            });
        }

        // Add foreign key constraint to price_discounts if missing
        if (Schema::hasTable('price_discounts')) {
            Schema::table('price_discounts', function (Blueprint $table) {
                // Cannot easily check if FK exists in all dialects, so wrapping in try-catch in raw or just skip if we don't need strict constraints on old tables
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert dropping columns
        if (!Schema::hasColumn('passengers', 'passenger_type')) {
            Schema::table('passengers', function (Blueprint $table) {
                $table->string('passenger_type', 30)->default('Adult')->after('last_name');
            });
        }
        if (!Schema::hasColumn('tickets', 'passenger_type')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->string('passenger_type', 30)->default('adult')->after('passenger_name');
            });
        }

        // Revert data migration
        $types = \Illuminate\Support\Facades\DB::table('passenger_types')->pluck('code', 'id');
        foreach ($types as $id => $code) {
            \Illuminate\Support\Facades\DB::table('passengers')->where('passenger_type_id', $id)->update(['passenger_type' => ucfirst($code)]);
            \Illuminate\Support\Facades\DB::table('tickets')->where('passenger_type_id', $id)->update(['passenger_type' => $code]);
        }

        // Drop foreign keys and columns
        Schema::table('passengers', function (Blueprint $table) {
            $table->dropForeign(['passenger_type_id']);
            $table->dropColumn('passenger_type_id');
        });
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['passenger_type_id']);
            $table->dropColumn('passenger_type_id');
        });

        Schema::dropIfExists('passenger_types');
    }
};
