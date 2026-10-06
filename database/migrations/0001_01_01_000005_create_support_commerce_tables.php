<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════
        // 24. NOTIFICATIONS
        // ═══════════════════════════════════════════════════════════
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('channel', 20)->default('in_app'); // in_app, sms, email
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });

        // ═══════════════════════════════════════════════════════════
        // 25. SYSTEM CONFIGS
        // ═══════════════════════════════════════════════════════════
        Schema::create('system_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key_name', 100)->unique();
            $table->text('value')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->string('group', 50)->default('general');
            $table->timestamps();
        });

        // ═══════════════════════════════════════════════════════════
        // 26. MENUS
        // ═══════════════════════════════════════════════════════════
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->string('route', 100)->nullable();
            $table->string('icon', 50)->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('menus')->cascadeOnDelete();
            $table->string('permission_name', 100)->nullable();
            $table->string('section', 50)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
        Schema::dropIfExists('system_configs');
        Schema::dropIfExists('notifications');

    }
};
