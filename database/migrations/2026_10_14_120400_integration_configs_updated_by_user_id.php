<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * integration_configs.updated_by was created as uuid, but it records who changed a gateway and
 * users have integer ids (as in the backend's 2026_10_13_100000 migration). It has never been set.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE integration_configs ALTER COLUMN updated_by TYPE bigint USING NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE integration_configs ALTER COLUMN updated_by TYPE uuid USING NULL');
    }
};
