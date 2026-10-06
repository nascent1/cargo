<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════
        // 19. DUAL CONTROL REQUESTS
        // ═══════════════════════════════════════════════════════════
        Schema::create('dual_control_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maker_id')->constrained('users');
            $table->foreignId('checker_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('operation_type', 50);
            $table->string('resource_type', 50);
            $table->unsignedBigInteger('resource_id');
            $table->json('payload_before')->nullable();
            $table->json('payload_after')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired', 'cancelled'])->default('pending');
            $table->text('checker_comment')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'expires_at']);
        });

        // ═══════════════════════════════════════════════════════════
        // 21. AUDIT LOGS (hash-chained, immutable)
        // ═══════════════════════════════════════════════════════════
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 50); // create, update, delete, login, logout
            $table->string('entity_type', 100);
            $table->string('entity_id', 50)->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('hash', 64); // SHA-256
            $table->string('previous_hash', 64)->nullable();
            $table->unsignedBigInteger('sequence_number')->unique();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['user_id', 'action']);
        });

        // ═══════════════════════════════════════════════════════════
        // 22. ACCESS LOGS
        // ═══════════════════════════════════════════════════════════
        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 50); // login, logout, failed_login, password_reset
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_logs');
        Schema::dropIfExists('audit_logs');

        Schema::dropIfExists('dual_control_requests');
    }
};
