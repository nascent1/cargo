<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Port operations: load → depart → arrive → hand over.
 *
 * - consignments: left_behind_at (paid but not loaded when its vessel departed) and the hand-over
 *   record (collector name, ID, mobile confirmed by OTP, who handed over, warnings).
 * - Port Officer role (manifest, load, depart/arrive, hand over) and cargo-status-correct for
 *   supervisors (fix a wrong status with a reason).
 * - Journeys menu used "voyage:list", which doesn't exist (only super admins saw it): voyage:read.
 * - SMS: cargo in transit, cargo arrived (own switches, need sms_enabled) and the collection code
 *   (always sent, code hidden in the delivery log).
 */
return new class extends Migration
{
    private const PORT_OFFICER_PERMISSIONS = [
        'admin_portal:access', 'portal:access', 'dashboard:view', 'cargo-view', 'booking:read', 'voyage:read',
        'cargo-manifest', 'cargo-load', 'cargo-offload', 'cargo-collect',
    ];

    private const TEMPLATES = [
        ['cargo_in_transit', 'en', '{{company_name}}: Cargo {{booking_reference}} ({{item_count}} item(s)) has left {{origin}} on {{vessel}} and is on the way to {{destination}}.{{tracking_line}}'],
        ['cargo_in_transit', 'sw', '{{company_name}}: Mzigo {{booking_reference}} (vipande {{item_count}}) umeondoka {{origin}} kwa {{vessel}} kuelekea {{destination}}.{{tracking_line}}'],
        ['cargo_arrived', 'en', '{{company_name}}: Cargo {{booking_reference}} has arrived at {{destination}}. {{receiver_name}} can collect it with an ID and this booking number.{{tracking_line}}'],
        ['cargo_arrived', 'sw', '{{company_name}}: Mzigo {{booking_reference}} umefika {{destination}}. {{receiver_name}} anaweza kuuchukua kwa kitambulisho na namba hii ya booking.{{tracking_line}}'],
        ['cargo_collection_otp', 'en', '{{company_name}}: your code to collect cargo {{booking_reference}} is {{code}}. It expires in {{minutes}} minutes. Give it only to our staff at the counter.'],
        ['cargo_collection_otp', 'sw', '{{company_name}}: namba yako ya kuchukua mzigo {{booking_reference}} ni {{code}}. Inaisha baada ya dakika {{minutes}}. Mpe mhudumu wetu kaunta tu.'],
    ];

    private const SETTINGS = [
        'sms_cargo_in_transit' => ['1', 'SMS the sender and receiver when the vessel departs with their cargo'],
        'sms_cargo_arrived' => ['1', 'SMS the sender and receiver when the cargo arrives at the destination port'],
    ];

    public function up(): void
    {
        Schema::table('consignments', function (Blueprint $table) {
            $table->timestamp('left_behind_at')->nullable();
            $table->string('collected_by_name', 150)->nullable();
            $table->string('collected_id_type', 30)->nullable();
            $table->string('collected_id_number', 60)->nullable();
            $table->string('collected_phone', 20)->nullable();
            $table->boolean('collected_phone_verified')->default(false);
            $table->foreignId('handed_over_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('collection_warnings')->nullable();
        });

        $now = now();
        DB::table('roles')->insertOrIgnore([
            'name' => 'Port Officer', 'slug' => 'port_officer', 'display_name' => 'Port Officer',
            'description' => 'Loads cargo, departs and arrives vessels, hands cargo over to receivers',
            'is_system' => false, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $roleId = DB::table('roles')->where('slug', 'port_officer')->value('id');
        foreach (DB::table('permissions')->whereIn('name', self::PORT_OFFICER_PERMISSIONS)->pluck('id') as $permissionId) {
            DB::table('role_permissions')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
        }

        DB::table('permissions')->updateOrInsert(['name' => 'cargo-status-correct'], [
            'display_name' => 'Correct cargo status', 'module' => 'cargo',
            'description' => 'Set a booking to any status with a reason (fixing mistakes)', 'is_system' => true,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $correctId = DB::table('permissions')->where('name', 'cargo-status-correct')->value('id');
        foreach (DB::table('roles')->whereIn('slug', ['super_admin', 'admin'])->pluck('id') as $id) {
            DB::table('role_permissions')->insertOrIgnore(['role_id' => $id, 'permission_id' => $correctId]);
        }

        DB::table('menus')->where('route', 'admin.cargo.journeys.index')->where('permission_name', 'voyage:list')
            ->update(['permission_name' => 'voyage:read', 'updated_at' => $now]);

        foreach (self::TEMPLATES as [$event, $locale, $body]) {
            DB::table('notification_templates')->insertOrIgnore([
                'event' => $event, 'channel' => 'sms', 'locale' => $locale, 'subject' => null,
                'body' => $body, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        foreach (self::SETTINGS as $key => [$value, $description]) {
            DB::table('system_configs')->insertOrIgnore([
                'key_name' => $key, 'value' => $value, 'description' => $description,
                'is_sensitive' => false, 'group' => 'sms', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('system_configs')->whereIn('key_name', array_keys(self::SETTINGS))->delete();
        foreach (self::TEMPLATES as [$event, $locale]) {
            DB::table('notification_templates')->where(['event' => $event, 'channel' => 'sms', 'locale' => $locale])->delete();
        }
        DB::table('menus')->where('route', 'admin.cargo.journeys.index')->where('permission_name', 'voyage:read')
            ->update(['permission_name' => 'voyage:list']);

        $correctId = DB::table('permissions')->where('name', 'cargo-status-correct')->value('id');
        DB::table('role_permissions')->where('permission_id', $correctId)->delete();
        DB::table('user_permissions')->where('permission_id', $correctId)->delete();
        DB::table('permissions')->where('id', $correctId)->delete();

        $roleId = DB::table('roles')->where('slug', 'port_officer')->value('id');
        DB::table('role_permissions')->where('role_id', $roleId)->delete();
        DB::table('user_roles')->where('role_id', $roleId)->delete();
        DB::table('roles')->where('id', $roleId)->delete();

        Schema::table('consignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('handed_over_by');
            $table->dropColumn(['left_behind_at', 'collected_by_name', 'collected_id_type', 'collected_id_number',
                'collected_phone', 'collected_phone_verified', 'collection_warnings']);
        });
    }
};
