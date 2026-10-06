<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Tickets table was always named 'tickets' in the base migration.
     * This migration is a no-op kept for migration history continuity.
     */
    public function up(): void
    {
        // No-op: table is already named 'tickets'
    }

    public function down(): void
    {
        // No-op
    }
};
