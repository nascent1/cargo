<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════
        // 1. USERS
        // ═══════════════════════════════════════════════════════════
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255)->unique();
            $table->string('phone', 20)->nullable()->unique();
            $table->string('national_id', 50)->nullable()->unique();
            $table->string('password');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->enum('domain', ['system_user', 'agent', 'associate_agent', 'passenger'])->default('passenger');
            $table->boolean('mfa_enabled')->default(false);
            $table->string('mfa_secret')->nullable();
            $table->enum('status', ['active', 'locked', 'suspended', 'inactive'])->default('active');
            $table->unsignedTinyInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->string('photo_url')->nullable();
            $table->text('bio')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['email', 'status']);
        });

        // ═══════════════════════════════════════════════════════════
        // 1.1 SYSTEM USERS (Admins, Sales Officers, Ferry Staff)
        // ═══════════════════════════════════════════════════════════
        Schema::create('system_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('employee_id')->nullable()->unique();
            $table->string('department')->nullable();
            $table->string('position')->nullable();
            $table->date('hire_date')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        // ═══════════════════════════════════════════════════════════
        // 2. PASSWORD RESET TOKENS
        // ═══════════════════════════════════════════════════════════
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // ═══════════════════════════════════════════════════════════
        // 3. SESSIONS
        // ═══════════════════════════════════════════════════════════
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // ═══════════════════════════════════════════════════════════
        // 5. ROLES
        // ═══════════════════════════════════════════════════════════
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('slug', 50)->unique();
            $table->string('display_name', 100);
            $table->string('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        // ═══════════════════════════════════════════════════════════
        // 6. PERMISSIONS
        // ═══════════════════════════════════════════════════════════
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique(); // e.g. beekeeper:create
            $table->string('display_name', 150);
            $table->string('module', 50); // e.g. beekeeper, certification
            $table->string('description')->nullable();
            $table->boolean('is_system')->default(true);
            $table->timestamps();
        });

        // ═══════════════════════════════════════════════════════════
        // 7. ROLE ↔ PERMISSION PIVOT
        // ═══════════════════════════════════════════════════════════
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        // ═══════════════════════════════════════════════════════════
        // 8. USER ↔ ROLE PIVOT
        // ═══════════════════════════════════════════════════════════
        Schema::create('user_roles', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'role_id']);
        });

        // ═══════════════════════════════════════════════════════════
        // 9. USER ↔ PERMISSION PIVOT (direct overrides)
        // ═══════════════════════════════════════════════════════════
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->boolean('granted')->default(true);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->primary(['user_id', 'permission_id']);
        });

        // ═══════════════════════════════════════════════════════════
        // 10. CACHE TABLE (for Laravel cache driver)
        // ═══════════════════════════════════════════════════════════
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');

        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('system_users');
        Schema::dropIfExists('users');
    }
};
