<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Columns were already named 'ticket_id' in the base migrations.
     * This migration is a no-op kept for migration history continuity.
     */
    public function up(): void
    {
        // No-op: columns are already named 'ticket_id'
    }

    public function down(): void
    {
        // No-op
    }
};
