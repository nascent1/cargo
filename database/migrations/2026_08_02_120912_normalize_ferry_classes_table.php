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
        Schema::table('ferry_classes', function (Blueprint $table) {
            $table->foreignId('class_id')->nullable()->constrained('travel_classes')->nullOnDelete();
        });

        // Migrate Data
        $ferryClasses = DB::table('ferry_classes')->get();
        foreach ($ferryClasses as $fc) {
            $rawName = $fc->name;
            // Remove "class" case insensitively, trim spaces, title case
            $cleanName = ucwords(trim(preg_replace('/class$/i', '', $rawName)));
            
            $colors = ['#FFD700', '#C0C0C0', '#CD7F32', '#4169E1', '#2E8B57', '#FF6347', '#8A2BE2'];
            
            $travelClass = DB::table('travel_classes')->where('name', $cleanName)->first();
            if (!$travelClass) {
                $id = DB::table('travel_classes')->insertGetId([
                    'name' => $cleanName,
                    'color' => $colors[rand(0, count($colors) - 1)],
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $classId = $id;
            } else {
                $classId = $travelClass->id;
            }
            
            DB::table('ferry_classes')->where('id', $fc->id)->update(['class_id' => $classId]);
        }

        Schema::table('ferry_classes', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->renameColumn('capacity', 'no_seats');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ferry_classes', function (Blueprint $table) {
            $table->string('name', 50)->nullable();
            $table->renameColumn('no_seats', 'capacity');
        });
        
        // Data loss on reverse (names not perfectly restorable without join to travel_classes)
        DB::statement('UPDATE ferry_classes SET name = (SELECT name FROM travel_classes WHERE travel_classes.id = ferry_classes.class_id)');
        
        Schema::table('ferry_classes', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropColumn('class_id');
        });
    }
};
