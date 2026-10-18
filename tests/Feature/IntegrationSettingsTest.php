<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DualControlConfig;
use App\Models\DualControlRequest;
use App\Models\IntegrationConfig;
use App\Models\SystemConfig;
use App\Models\User;
use App\Services\DualControlService;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Settings › Integrations: SMS gateways saved through dual control with encrypted credentials,
 * priority failover, connection test, and the screens around them.
 *
 * Runs against the configured DB inside a transaction (DatabaseTransactions) — never refreshes it.
 * Live gateways are switched off inside the transaction and every HTTP call is faked.
 */
class IntegrationSettingsTest extends TestCase
{
    use DatabaseTransactions;

    private const TOKEN = 'test-token-0123456789abcdef';

    private User $admin;
    private User $checker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::whereHas('roles', fn ($q) => $q->where('slug', 'admin'))->first()
            ?? $this->markTestSkipped('Needs an admin user.');
        $this->checker = User::whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))->where('id', '!=', $this->admin->id)->first()
            ?? $this->markTestSkipped('Needs a second user who can approve.');
        SystemConfig::setValue('exchange_rate_last_updated', today()->toDateString());
        DualControlConfig::syncFromRegistry();
        DualControlConfig::where('operation_type', 'like', 'integration_%')->update(['requires_dual_control' => false]);

        // Only this test's gateways are live
        IntegrationConfig::where('channel', 'sms')->update(['is_active' => false]);
    }

    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['2fa_verified' => true]);
    }

    private function gateway(string $host, int $priority): IntegrationConfig
    {
        return IntegrationConfig::create([
            'code' => 'TEST_' . strtoupper(\Illuminate\Support\Str::random(8)), 'name' => "Test {$host}", 'provider' => 'nextsms', 'channel' => 'sms',
            'environment' => 'production', 'production_url' => "https://{$host}", 'sandbox_url' => "https://{$host}",
            'access_token' => self::TOKEN, 'extra_credentials' => ['sender_id' => 'TEST'],
            'is_active' => true, 'sort_order' => $priority,
        ]);
    }

    public function test_screens_render(): void
    {
        $this->gateway('sms-a.test', 1);

        $this->as($this->admin)->get('/admin/settings/integrations')->assertOk()
            ->assertSee('SMS policy')->assertSee('Close of business')->assertSee('Test sms-a.test')
            ->assertDontSee(self::TOKEN);
        $this->as($this->admin)->get('/admin/notifications/logs')->assertOk();
        $this->as($this->admin)->getJson('/admin/settings/integrations/close-of-business/preview')
            ->assertOk()->assertJsonStructure(['text', 'recipients']);
    }

    public function test_gateway_is_created_encrypted_through_dual_control(): void
    {
        $this->as($this->admin)->post('/admin/settings/integrations', [
            'provider' => 'nextsms', 'name' => 'NextSMS test', 'environment' => 'production',
            'production_url' => 'https://sms-a.test', 'is_active' => '1',
            'f_access_token' => self::TOKEN, 'f_extra__sender_id' => 'MUST',
        ])->assertRedirect()->assertSessionHas('success');

        $config = IntegrationConfig::where('name', 'NextSMS test')->firstOrFail();
        $this->assertTrue($config->is_active);
        $this->assertSame('MUST', $config->extra('sender_id'));
        $this->assertSame(self::TOKEN, $config->access_token);
        $this->assertSame((int) $this->admin->id, (int) $config->updated_by);

        // At rest and in the trail the token is never plain text
        $raw = \DB::table('integration_configs')->where('id', $config->id)->first();
        $this->assertStringNotContainsString(self::TOKEN, json_encode($raw));
        $request = DualControlRequest::where('operation_type', 'integration_create')->latest('id')->first();
        $this->assertStringNotContainsString(self::TOKEN, json_encode($request->getAttributes()));
        $this->assertFalse(AuditLog::where('created_at', '>=', now()->subMinute())->get()->contains(fn ($a) => str_contains(json_encode($a->getAttributes()), self::TOKEN)));
    }

    public function test_with_the_rule_on_nothing_changes_until_approved(): void
    {
        DualControlConfig::where('operation_type', 'integration_create')->update(['requires_dual_control' => true]);

        $this->as($this->admin)->post('/admin/settings/integrations', [
            'provider' => 'nextsms', 'name' => 'Pending gateway', 'environment' => 'production',
            'production_url' => 'https://sms-a.test', 'is_active' => '1',
            'f_access_token' => self::TOKEN, 'f_extra__sender_id' => 'MUST',
        ])->assertRedirect();

        $this->assertFalse(IntegrationConfig::where('name', 'Pending gateway')->exists());
        $request = DualControlRequest::where('operation_type', 'integration_create')->where('status', 'pending')->latest('id')->firstOrFail();

        $this->actingAs($this->checker);
        app(DualControlService::class)->approve($request);

        $config = IntegrationConfig::where('name', 'Pending gateway')->firstOrFail();
        $this->assertSame(self::TOKEN, $config->access_token, 'the sealed token is unsealed only when applied');
    }

    public function test_an_active_gateway_needs_its_credentials(): void
    {
        $this->as($this->admin)->post('/admin/settings/integrations', [
            'provider' => 'nextsms', 'name' => 'No token', 'environment' => 'production',
            'production_url' => 'https://sms-a.test', 'is_active' => '1', 'f_extra__sender_id' => 'MUST',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertFalse(IntegrationConfig::where('name', 'No token')->exists());
    }

    public function test_sms_fails_over_to_the_next_gateway_in_priority_order(): void
    {
        $this->gateway('sms-a.test', 1);
        $backup = $this->gateway('sms-b.test', 2);
        Http::fake([
            'sms-a.test/*' => Http::response(['message' => 'Service unavailable'], 503),
            'sms-b.test/*' => Http::response(['messages' => [['messageId' => 'M-77', 'status' => ['groupName' => 'PENDING']]]], 200),
        ]);

        $result = app(SmsService::class)->send('0712075190', 'Failover test');

        $this->assertTrue($result->success);
        $this->assertSame($backup->id, $result->configId);
        $this->assertSame('M-77', $result->messageId);
        $this->assertCount(2, $result->attempts);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'sms-b.test') && $r['messages'][0]['to'] === ['255712075190'] && $r['messages'][0]['from'] === 'TEST'
            && $r->hasHeader('Authorization', 'Bearer ' . self::TOKEN));
    }

    public function test_connection_test_sends_to_the_given_number_only(): void
    {
        $config = $this->gateway('sms-a.test', 1);
        Http::fake(['sms-a.test/*' => Http::response(['messages' => [['messageId' => 'T-1', 'status' => ['groupName' => 'PENDING']]]], 200)]);

        // Saved token kept (blank secret field), the rest as on the form
        $this->as($this->admin)->postJson('/admin/settings/integrations/test', [
            'provider' => 'nextsms', 'integration_id' => $config->id, 'recipient' => '0712075190',
            'environment' => 'production', 'production_url' => 'https://sms-a.test', 'f_extra__sender_id' => 'TEST',
        ])->assertOk()->assertJson(['ok' => true]);

        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => $r['messages'][0]['to'] === ['255712075190']);
    }
}
