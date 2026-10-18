<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tracking links now come from APP_URL in .env (+ /track), so the two settings that held a
 * separate address are removed. The booking SMS templates end with {{tracking_line}}, which is
 * empty while APP_URL is a local address, instead of "Track: {{tracking_url}}" (a dead link then).
 * Templates are only changed while they still have the default wording.
 */
return new class extends Migration
{
    private const TEMPLATES = [
        ['cargo_booking_sender', 'en', 'Receiver: {{receiver_name}}. Track: {{tracking_url}}', 'Receiver: {{receiver_name}}.{{tracking_line}}'],
        ['cargo_booking_sender', 'sw', 'Mpokeaji: {{receiver_name}}. Fuatilia: {{tracking_url}}', 'Mpokeaji: {{receiver_name}}.{{tracking_line}}'],
        ['cargo_booking_receiver', 'en', 'this booking number to collect. Track: {{tracking_url}}', 'this booking number to collect.{{tracking_line}}'],
        ['cargo_booking_receiver', 'sw', 'ya booking kuuchukua. Fuatilia: {{tracking_url}}', 'ya booking kuuchukua.{{tracking_line}}'],
    ];

    private const REMOVED = [
        'tracking_public_url' => ['/track', 'Public tracking URL path'],
        'tracking_qr_base_url' => ['http://127.0.0.1:8001/track', 'Base URL for QR code tracking links'],
    ];

    public function up(): void
    {
        $this->swapEndings(fn ($t) => [$t[2], $t[3]]);
        DB::table('system_configs')->whereIn('key_name', array_keys(self::REMOVED))->delete();
    }

    public function down(): void
    {
        $this->swapEndings(fn ($t) => [$t[3], $t[2]]);
        $now = now();
        foreach (self::REMOVED as $key => [$value, $description]) {
            DB::table('system_configs')->insertOrIgnore([
                'key_name' => $key, 'value' => $value, 'description' => $description,
                'is_sensitive' => false, 'group' => 'tracking', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    private function swapEndings(callable $pick): void
    {
        foreach (self::TEMPLATES as $template) {
            [$from, $to] = $pick($template);
            $row = DB::table('notification_templates')->where(['event' => $template[0], 'channel' => 'sms', 'locale' => $template[1]])->first();
            if ($row && str_ends_with($row->body, $from)) {
                DB::table('notification_templates')->where('id', $row->id)
                    ->update(['body' => substr($row->body, 0, -strlen($from)) . $to, 'updated_at' => now()]);
            }
        }
    }
};
