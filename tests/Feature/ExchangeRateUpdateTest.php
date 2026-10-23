<?php

namespace Tests\Feature;

use App\Models\DualControlConfig;
use App\Models\ExchangeRate;
use App\Models\SystemConfig;
use App\Models\User;
use App\Services\ExchangeRateService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The USD→TZS rate: dated rates from Exchange Rates, the update frequency, who is asked to set it
 * (exchange_rate:maintain) and the prompt that sets it.
 *
 * Runs against the configured DB inside a transaction (DatabaseTransactions) — never refreshes it.
 */
class ExchangeRateUpdateTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $finance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::whereHas('roles', fn ($q) => $q->where('slug', 'admin'))->first()
            ?? $this->markTestSkipped('Needs an admin user.');
        $this->finance = $this->createSystemUser(['first_name' => 'Finance', 'last_name' => 'Test']);
        DB::table('user_roles')->insert(['user_id' => $this->finance->id, 'role_id' => DB::table('roles')->where('slug', 'finance_officer')->value('id'),
            'created_at' => now(), 'updated_at' => now()]);

        DualControlConfig::syncFromRegistry();
        DualControlConfig::whereIn('operation_type', ['exchange_rate_create', 'setting_update'])->update(['requires_dual_control' => false]);
        SystemConfig::setValue('sms_enabled', '0');
        SystemConfig::setValue('exchange_rate_update_frequency', '1');
        SystemConfig::setValue('exchange_rate_last_updated', today()->subDay()->toDateString()); // due
    }

    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['2fa_verified' => true]);
    }

    private function usdRate(string $from, float $rate): ExchangeRate
    {
        return ExchangeRate::create(['from_currency' => 'USD', 'to_currency' => 'TZS', 'rate' => $rate, 'effective_from' => $from]);
    }

    public function test_conversions_use_the_rate_in_force_on_the_day(): void
    {
        $this->usdRate(today()->subDays(10)->toDateString(), 2500);
        $this->usdRate(today()->subDays(3)->toDateString(), 2550);
        $this->usdRate(today()->addDays(5)->toDateString(), 2700);

        $this->assertEquals(2550, ExchangeRateService::getCurrentRate('USD', 'TZS'));
        $this->assertEquals(2500, ExchangeRateService::getCurrentRate('USD', 'TZS', today()->subDays(5)));
        $this->assertEquals(2700, ExchangeRateService::getCurrentRate('USD', 'TZS', today()->addDays(6)));
        $this->assertEquals(round(1 / 2550, 6), ExchangeRateService::getCurrentRate('TZS', 'USD'));
        $this->assertEquals(1.0, ExchangeRateService::getCurrentRate('XYZ', 'TZS'), 'a currency with no rate keeps the safe fallback');

        // No entry at all: the setting, as before
        ExchangeRate::where('from_currency', 'USD')->where('to_currency', 'TZS')->delete();
        SystemConfig::setValue('usd_to_tzs_exchange_rate', '2610');
        $this->assertEquals(2610, ExchangeRateService::getCurrentRate('USD', 'TZS'));
    }

    public function test_the_frequency_decides_when_the_rate_is_due(): void
    {
        SystemConfig::setValue('exchange_rate_update_frequency', '3');
        SystemConfig::setValue('exchange_rate_last_updated', today()->subDays(2)->toDateString());
        $status = ExchangeRateService::updateStatus();
        $this->assertFalse($status['due']);
        $this->assertSame(today()->addDay()->toDateString(), $status['due_on']);

        SystemConfig::setValue('exchange_rate_last_updated', today()->subDays(3)->toDateString());
        $this->assertTrue(ExchangeRateService::updateStatus()['due']);

        SystemConfig::setValue('exchange_rate_update_frequency', '0');
        $status = ExchangeRateService::updateStatus();
        $this->assertFalse($status['due']);
        $this->assertNull($status['due_on']);
    }

    public function test_whoever_keeps_the_rate_is_asked_and_changes_wait_until_it_is_set(): void
    {
        // Background request: a clear answer the page turns into the prompt
        $this->as($this->finance)->postJson('/admin/cargo/api/booking/quote', [])
            ->assertStatus(423)->assertJsonPath('exchange_rate_due', true);

        // A form: back to the page, with the prompt
        $this->as($this->finance)->from('/admin/cargo/dashboard')->post('/admin/cargo/api/booking/quote')
            ->assertRedirect('/admin/cargo/dashboard')->assertSessionHas('force_exchange_modal');

        // Pages open, with the forced prompt and the header chip
        $this->as($this->finance)->get('/admin/cargo/dashboard')->assertOk()
            ->assertSee('Daily exchange rate required')->assertSee('USD 1 = ', false);

        // Settings stay open (e.g. to change the frequency)
        $this->as($this->admin)->from('/admin/settings')->post('/admin/settings', ['settings' => ['exchange_rate_update_frequency' => '0']])
            ->assertSessionHasNoErrors()->assertSessionMissing('force_exchange_modal');
        $this->assertSame('0', SystemConfig::getValue('exchange_rate_update_frequency'));
        $this->as($this->finance)->postJson('/admin/cargo/api/booking/quote', [])->assertStatus(403); // no longer held back
    }

    public function test_the_permission_decides_who_is_asked(): void
    {
        // A role grant can be withdrawn per user: then nobody asks them, and they can't use the prompt
        DB::table('user_permissions')->insert(['user_id' => $this->admin->id, 'permission_id' => DB::table('permissions')->where('name', 'exchange_rate:maintain')->value('id'),
            'granted' => false, 'created_at' => now(), 'updated_at' => now()]);

        $this->assertNotSame(423, $this->as($this->admin)->postJson('/admin/cargo/api/booking/quote', [])->status());
        $this->as($this->admin)->get('/admin/cargo/dashboard')->assertOk()->assertDontSee('Daily exchange rate required')->assertDontSee('USD 1 = ', false);
        $this->as($this->admin)->postJson('/admin/exchange-rate/current', ['rate' => 2650])->assertForbidden();
    }

    public function test_setting_todays_rate_from_the_prompt(): void
    {
        $this->as($this->finance)->postJson('/admin/exchange-rate/current', ['rate' => 0])->assertStatus(422)->assertJsonValidationErrors('rate');

        $this->as($this->finance)->postJson('/admin/exchange-rate/current', ['rate' => 2655.5])
            ->assertOk()->assertJsonPath('applied', true);

        $rate = ExchangeRate::where('from_currency', 'USD')->where('to_currency', 'TZS')->whereDate('effective_from', today())->firstOrFail();
        $this->assertEquals(2655.5, (float) $rate->rate);
        $this->assertSame($this->finance->id, $rate->created_by);
        $this->assertEquals(2655.5, ExchangeRateService::getCurrentRate('USD', 'TZS'));
        $this->assertEquals(2655.5, (float) SystemConfig::getValue('usd_to_tzs_exchange_rate'), 'the fallback follows the rate in force');
        $this->assertSame(today()->toDateString(), SystemConfig::getValue('exchange_rate_last_updated'));
        $this->assertFalse(ExchangeRateService::updateStatus()['due']);

        $this->as($this->finance)->postJson('/admin/cargo/api/booking/quote', [])->assertStatus(403); // no longer held back

        // Setting it again the same day replaces it
        $this->as($this->finance)->postJson('/admin/exchange-rate/current', ['rate' => 2660])->assertOk();
        $this->assertSame(1, ExchangeRate::where('from_currency', 'USD')->whereDate('effective_from', today())->count());
        $this->assertEquals(2660, ExchangeRateService::getCurrentRate('USD', 'TZS'));
    }

    public function test_with_the_rule_on_the_rate_waits_for_a_checker_but_the_prompt_clears(): void
    {
        DualControlConfig::where('operation_type', 'exchange_rate_create')->update(['requires_dual_control' => true]);
        $before = ExchangeRateService::getCurrentRate('USD', 'TZS');

        $this->as($this->finance)->postJson('/admin/exchange-rate/current', ['rate' => 2999])
            ->assertStatus(202)->assertJsonPath('status', 'pending');

        $this->assertEquals($before, ExchangeRateService::getCurrentRate('USD', 'TZS'));
        $this->assertFalse(ExchangeRateService::updateStatus()['due']);
    }

    public function test_settings_show_the_frequency_and_the_rate_read_only(): void
    {
        $this->as($this->admin)->get('/admin/settings')->assertOk()
            ->assertSee('name="settings[exchange_rate_update_frequency]"', false)
            ->assertDontSee('name="settings[usd_to_tzs_exchange_rate]"', false)
            ->assertSee("Set today's rate", false);

        $this->as($this->admin)->from('/admin/settings')->post('/admin/settings', ['settings' => ['exchange_rate_update_frequency' => '400']])
            ->assertSessionHasErrors('settings.exchange_rate_update_frequency');

        // The rate can't be changed around Exchange Rates and dual control
        $this->as($this->admin)->from('/admin/settings')->post('/admin/settings', ['settings' => ['usd_to_tzs_exchange_rate' => '1', 'exchange_rate_last_updated' => '2099-01-01']]);
        $this->assertNotSame('1', SystemConfig::getValue('usd_to_tzs_exchange_rate'));
        $this->assertNotSame('2099-01-01', SystemConfig::getValue('exchange_rate_last_updated'));
    }

    public function test_exchange_rates_page_marks_the_rate_in_force(): void
    {
        $this->usdRate(today()->toDateString(), 2640);
        $this->usdRate(today()->addDays(3)->toDateString(), 2700);

        $this->as($this->admin)->get('/admin/settings/exchange-rates')->assertOk()
            ->assertSee('In force')->assertSee('Upcoming');
    }
}
