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
        // Drop the existing table if it exists
        Schema::dropIfExists('integration_configs');

        // Recreate it with the FundiConnect schema
        Schema::create('integration_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('provider', 100)->nullable();
            $table->string('channel', 30);
            $table->string('description', 500)->nullable();
            $table->enum('environment', ['sandbox', 'production'])->default('sandbox');
            
            $table->string('base_url', 500)->nullable();
            $table->string('sandbox_url', 500)->nullable();
            $table->string('production_url', 500)->nullable();
            
            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->text('access_token')->nullable();
            $table->text('merchant_id')->nullable();
            $table->text('passkey')->nullable();
            $table->text('extra_credentials')->nullable();
            
            $table->string('callback_url', 500)->nullable();
            $table->string('timeout_url', 500)->nullable();
            $table->string('result_url', 500)->nullable();
            
            $table->boolean('is_active')->default(0);
            $table->boolean('is_default')->default(0);
            
            $table->string('currency', 5)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('icon', 100)->nullable();
            $table->string('country_code', 5)->nullable();
            
            $table->json('metadata')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('channel', 'integration_configs_channel_index');
            $table->index(['channel', 'is_active'], 'integration_configs_channel_is_active_index');
            $table->index(['channel', 'is_default'], 'integration_configs_channel_is_default_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_configs');

        // Optionally, recreate the old schema if we need to rollback
        Schema::create('integration_configs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->unique();
            $table->string('type', 30);
            $table->json('config');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_sandbox')->default(true);
            $table->timestamps();
        });
    }
};
