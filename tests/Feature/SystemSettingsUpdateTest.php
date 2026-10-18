<?php

namespace Tests\Feature;

use App\Models\DualControlConfig;
use App\Models\DualControlRequest;
use App\Models\SystemConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Settings › General save: every submitted setting is considered (typed ones are checked first),
 * and changes follow the setting_update dual-control rule.
 *
 * Runs against the configured DB inside a transaction (DatabaseTransactions) — never refreshes it.
 */
class SystemSettingsUpdateTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::whereHas('roles', fn ($q) => $q->where('slug', 'admin'))->first()
            ?? $this->markTestSkipped('Needs an admin user.');
        SystemConfig::setValue('exchange_rate_last_updated', today()->toDateString());
        SystemConfig::setValue('sms_enabled', '0');
        SystemConfig::setValue('default_shift_hours', '8');
        DualControlConfig::syncFromRegistry();
    }

    private function save(array $settings)
    {
        return $this->actingAs($this->admin)->withSession(['2fa_verified' => true])
            ->from('/admin/settings')->post('/admin/settings', ['settings' => $settings]);
    }

    public function test_settings_without_a_typed_rule_are_saved_alongside_typed_ones(): void
    {
        DualControlConfig::where('operation_type', 'setting_update')->update(['requires_dual_control' => false]);

        // As the form posts it: the toggle's hidden 0 is overridden by the checked 1
        $this->save(['sms_enabled' => '1', 'default_shift_hours' => '9'])
            ->assertRedirect('/admin/settings')->assertSessionHas('success', 'Settings: 2 updated.');

        $this->assertSame('1', SystemConfig::where('key_name', 'sms_enabled')->value('value'));
        $this->assertSame('9', SystemConfig::where('key_name', 'default_shift_hours')->value('value'));
    }

    public function test_a_bad_typed_value_is_refused_and_nothing_changes(): void
    {
        $this->save(['sms_enabled' => '1', 'default_shift_hours' => 'abc'])->assertSessionHasErrors('settings.default_shift_hours');

        $this->assertSame('0', SystemConfig::where('key_name', 'sms_enabled')->value('value'));
        $this->assertSame('8', SystemConfig::where('key_name', 'default_shift_hours')->value('value'));
    }

    public function test_with_the_rule_on_the_change_waits_for_approval(): void
    {
        DualControlConfig::where('operation_type', 'setting_update')->update(['requires_dual_control' => true]);

        $this->save(['sms_enabled' => '1'])->assertSessionHas('success', 'Settings: 1 awaiting approval.');

        $this->assertSame('0', SystemConfig::where('key_name', 'sms_enabled')->value('value'));
        $this->assertTrue(DualControlRequest::where('operation_type', 'setting_update')->where('status', 'pending')
            ->where('payload_after->key_name', 'sms_enabled')->exists());
    }
}
