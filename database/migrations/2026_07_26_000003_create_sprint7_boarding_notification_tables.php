<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Scanners — device registry for boarding/gate scanners
        if (!Schema::hasTable('scanners')) {
            Schema::create('scanners', function (Blueprint $table) {
                $table->id();
                $table->string('serial_number', 50)->unique();
                $table->string('imei', 20)->nullable();
                $table->string('sim_number', 20)->nullable();
                $table->string('device_model', 100)->nullable();
                $table->foreignId('assigned_port_id')->nullable()->constrained('ports')->nullOnDelete();
                $table->foreignId('assigned_operator_id')->nullable()->constrained('port_operators')->nullOnDelete();
                $table->string('status', 20)->default('active'); // active, inactive, maintenance
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();
            });
        }

        // 2. Boarding Events — QR scan log
        if (!Schema::hasTable('boarding_events')) {
            Schema::create('boarding_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
                $table->foreignId('voyage_id')->constrained('voyages')->cascadeOnDelete();
                $table->foreignId('scanner_id')->nullable()->constrained('scanners')->nullOnDelete();
                $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('port_id')->nullable()->constrained('ports')->nullOnDelete();
                $table->string('scan_type', 20)->default('boarding'); // boarding, exit, verification
                $table->string('qr_data', 500)->nullable();
                $table->string('result', 20); // success, duplicate, invalid, expired, wrong_voyage
                $table->text('notes')->nullable();
                $table->timestamp('scanned_at');
                $table->timestamps();

                $table->index(['voyage_id', 'scanned_at']);
                $table->index('ticket_id');
            });
        }

        // 3. Notification Templates — multi-channel, multi-locale templates
        if (!Schema::hasTable('notification_templates')) {
            Schema::create('notification_templates', function (Blueprint $table) {
                $table->id();
                $table->string('event', 100); // booking_confirmed, payment_received, departure_reminder, etc.
                $table->string('channel', 20); // sms, email, in_app, push
                $table->string('locale', 5)->default('en');
                $table->string('subject', 255)->nullable(); // email subject
                $table->text('body'); // template body with {{placeholders}}
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['event', 'channel', 'locale']);
            });
        }

        // 4. Notification Logs — delivery tracking
        if (!Schema::hasTable('notification_logs')) {
            Schema::create('notification_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('channel', 20); // sms, email, in_app
                $table->string('event', 100);
                $table->string('recipient', 255); // phone number or email
                $table->text('content')->nullable();
                $table->string('status', 20)->default('pending'); // pending, sent, delivered, failed
                $table->string('provider', 50)->nullable(); // kilakona, sendgrid, etc.
                $table->string('provider_message_id', 100)->nullable();
                $table->text('error_message')->nullable();
                $table->decimal('cost', 8, 4)->nullable(); // SMS cost tracking
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamps();

                $table->index(['channel', 'status']);
                $table->index('user_id');
            });
        }

        // 5. Announcements — system-wide or route-specific alerts
        if (!Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table) {
                $table->id();
                $table->string('title', 255);
                $table->text('message');
                $table->string('type', 30)->default('info'); // info, warning, emergency, maintenance
                $table->string('target_audience', 30)->default('all'); // all, agents, passengers, operators
                $table->unsignedBigInteger('route_id')->nullable();
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['is_active', 'start_date', 'end_date']);
            });
        }

        // 6. Integration Configs — dynamic provider management
        if (!Schema::hasTable('integration_configs')) {
            Schema::create('integration_configs', function (Blueprint $table) {
                $table->id();
                $table->string('provider', 50)->unique(); // kilakona_sms, sendgrid, etc.
                $table->string('type', 30); // sms, email, payment, push
                $table->json('config'); // API keys, endpoints, etc.
                $table->boolean('is_active')->default(true);
                $table->boolean('is_sandbox')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_configs');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('boarding_events');
        Schema::dropIfExists('scanners');
    }
};
