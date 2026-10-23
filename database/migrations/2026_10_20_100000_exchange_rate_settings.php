<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Exchange rate upkeep, as in the ferry platform but permission-based:
 * - the fallback USD→TZS rate and the update frequency settings (cargo never had them, so the
 *   rate typed into the prompt was dropped); existing values are kept
 * - exchange_rate:maintain: who is prompted when the rate is due and may set it from the prompt
 *   (super_admin, admin and finance_officer to start with; assignable to any role)
 */
return new class extends Migration
{
    private const SETTINGS = [
        'usd_to_tzs_exchange_rate' => ['2600', 'Fallback USD to TZS rate, used only when no rate is in force under Exchange Rates.'],
        'exchange_rate_update_frequency' => ['1', 'How often the USD to TZS rate must be set, in days (0 = never prompt).'],
        'exchange_rate_last_updated' => ['2000-01-01', 'When the USD to TZS rate was last set.'],
    ];

    private const PERMISSION = 'exchange_rate:maintain';

    public function up(): void
    {
        $now = now();

        foreach (self::SETTINGS as $key => [$value, $description]) {
            DB::table('system_configs')->insertOrIgnore([
                'key_name' => $key, 'value' => $value, 'description' => $description,
                'group' => 'payment', 'is_sensitive' => false, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        // last_updated was created on the fly under "financial"; keep the three together
        DB::table('system_configs')->where('key_name', 'exchange_rate_last_updated')
            ->update(['group' => 'payment', 'description' => self::SETTINGS['exchange_rate_last_updated'][1]]);

        DB::table('permissions')->updateOrInsert(['name' => self::PERMISSION], [
            'display_name' => 'Keep the exchange rate up to date', 'module' => 'setting',
            'description' => 'Prompted to set the USD to TZS rate when it is due, and can set it from the prompt',
            'is_system' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $permissionId = DB::table('permissions')->where('name', self::PERMISSION)->value('id');
        foreach (DB::table('roles')->whereIn('slug', ['super_admin', 'admin', 'finance_officer'])->pluck('id') as $roleId) {
            DB::table('role_permissions')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', self::PERMISSION)->value('id');
        DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
        DB::table('user_permissions')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('id', $permissionId)->delete();

        DB::table('system_configs')->whereIn('key_name', ['usd_to_tzs_exchange_rate', 'exchange_rate_update_frequency'])->delete();
        DB::table('system_configs')->where('key_name', 'exchange_rate_last_updated')->update(['group' => 'financial']);
    }
};
