<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dual control requests can take effect at a chosen moment instead of when they are approved.
 *
 * commence_at    — when the maker wants the change to take effect (null = as soon as it is approved)
 * executed_at    — when it was actually applied (later than commence_at = approved late)
 * failure_reason — why a scheduled change could not be applied
 * subject_key    — what the change is about ("station:4", "cargo_fare:1:3:-"), so a record has one open change at a time
 *
 * New statuses: scheduled (approved, waiting for commence_at) and failed.
 */
return new class extends Migration
{
    private const STATUSES = ['pending', 'scheduled', 'approved', 'failed', 'rejected', 'expired', 'cancelled'];

    public function up(): void
    {
        Schema::table('dual_control_requests', function (Blueprint $table) {
            $table->timestamp('commence_at')->nullable()->after('payload_after');
            $table->timestamp('executed_at')->nullable()->after('resolved_at');
            $table->text('failure_reason')->nullable()->after('checker_comment');
            $table->string('subject_key', 120)->nullable()->after('resource_id');
            $table->index(['subject_key', 'status']);
            $table->index(['status', 'commence_at']);
        });

        DB::statement('ALTER TABLE dual_control_requests DROP CONSTRAINT IF EXISTS dual_control_requests_status_check');
        DB::statement("ALTER TABLE dual_control_requests ADD CONSTRAINT dual_control_requests_status_check CHECK (status IN ('" . implode("','", self::STATUSES) . "'))");

        // Requests already applied were applied when they were resolved
        DB::table('dual_control_requests')->where('status', 'approved')->update(['executed_at' => DB::raw('resolved_at')]);
        DB::table('dual_control_requests')->where('resource_id', '>', 0)
            ->update(['subject_key' => DB::raw("resource_type || ':' || resource_id")]);
    }

    public function down(): void
    {
        DB::table('dual_control_requests')->whereIn('status', ['scheduled', 'failed'])->update(['status' => 'cancelled']);
        DB::statement('ALTER TABLE dual_control_requests DROP CONSTRAINT IF EXISTS dual_control_requests_status_check');
        DB::statement("ALTER TABLE dual_control_requests ADD CONSTRAINT dual_control_requests_status_check CHECK (status IN ('pending','approved','rejected','expired','cancelled'))");

        Schema::table('dual_control_requests', function (Blueprint $table) {
            $table->dropIndex(['subject_key', 'status']);
            $table->dropIndex(['status', 'commence_at']);
            $table->dropColumn(['commence_at', 'executed_at', 'failure_reason', 'subject_key']);
        });
    }
};
