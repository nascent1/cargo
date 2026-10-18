<?php

namespace Tests\Feature;

use App\Models\CargoType;
use App\Models\Consignment;
use App\Models\SystemConfig;
use App\Models\TrackingEvent;
use App\Models\TransportService;
use App\Services\SmsNotifier;
use App\Support\TrackingUrl;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Public tracking (no login): the page and API show status, timeline, route, departure, vessel,
 * item count and masked names — never amounts, phones, item contents or event notes. Links use
 * APP_URL and are left out of SMS while it is a local address.
 *
 * Runs against the configured DB inside a transaction (DatabaseTransactions) — never refreshes it.
 */
class PublicTrackingTest extends TestCase
{
    use DatabaseTransactions;

    private const SECRET_NOTE = 'Fare override: TZS 100,000 discount. Reason: staff relative';

    private TransportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = TransportService::with(['route.origin', 'route.destination', 'transportUnit'])->whereHas('route')->first()
            ?? $this->markTestSkipped('Needs a transport service with a route.');
        RateLimiter::clear(sha1('|127.0.0.1'));
        SystemConfig::setValue('default_locale', 'en');
    }

    private function booking(string $status = 'booked'): Consignment
    {
        $type = CargoType::first() ?? $this->markTestSkipped('Needs a cargo type.');
        $c = Consignment::create([
            'uuid' => (string) Str::uuid(), 'consignment_number' => 'CG-TRK-' . strtoupper(Str::random(5)),
            'tracking_number' => strtoupper(Str::random(10)), 'qr_code_token' => 'CQ-' . strtoupper(Str::random(12)),
            'transport_service_id' => $this->service->id, 'route_id' => $this->service->route_id,
            'sender_name' => 'Asha Mwajuma Sender', 'sender_phone' => '0754000001',
            'receiver_name' => 'Juma Receiver', 'receiver_phone' => '0754000002',
            'total_amount_tzs' => 410500, 'net_amount' => 410500, 'currency' => 'TZS', 'total_weight_kg' => 20,
            'status' => $status, 'payment_status' => $status === 'pending_payment' ? 'unpaid' : 'paid',
            'paid_at' => $status === 'pending_payment' ? null : now()->subHours(3),
        ]);
        $c->items()->create([
            'cargo_type_id' => $type->id, 'item_number' => $c->consignment_number . '-01', 'quantity' => 2,
            'weight_kg' => 20, 'final_fare' => 410500, 'status' => 'booked', 'qr_code_token' => 'CI' . strtoupper(Str::random(12)),
        ]);
        foreach (['pending_payment' => 4, 'booked' => 3, 'loaded' => 2, 'in_transit' => 1] as $to => $hoursAgo) {
            TrackingEvent::create(['consignment_id' => $c->id, 'event_type' => 'state_change', 'to_status' => $to,
                'notes' => "Internal note for {$to}", 'created_at' => now()->subHours($hoursAgo)]);
            if ($to === $status) {
                break;
            }
        }
        TrackingEvent::create(['consignment_id' => $c->id, 'event_type' => 'fare_override', 'notes' => self::SECRET_NOTE, 'created_at' => now()]);

        return $c;
    }

    private function assertNothingPrivate(string $body): void
    {
        foreach (['Asha Mwajuma', 'Juma Receiver', '0754000001', '754000002', '410,500', '410500', self::SECRET_NOTE, 'Internal note', 'qr_code_token'] as $private) {
            $this->assertStringNotContainsString($private, $body, "public tracking must not show “{$private}”");
        }
    }

    public function test_the_page_shows_the_booking_without_private_details(): void
    {
        $c = $this->booking('booked');

        $response = $this->get("/track/{$c->consignment_number}")->assertOk()
            ->assertSee($c->consignment_number)->assertSee('Booked')
            ->assertSee('Asha M*** S***')->assertSee('Juma R***')
            ->assertSee($this->service->route->origin->name)->assertSee($this->service->route->destination->name)
            ->assertHeader('X-RateLimit-Limit', 30);
        if ($this->service->transportUnit) {
            $response->assertSee($this->service->transportUnit->name);
        }
        $this->assertNothingPrivate($response->getContent());
    }

    public function test_lookup_by_tracking_number_any_case_and_the_search_form(): void
    {
        $c = $this->booking();

        $this->get('/track/' . strtolower($c->tracking_number))->assertOk()->assertSee($c->consignment_number);
        $this->get('/track?number=' . urlencode(' ' . strtolower($c->consignment_number) . ' '))->assertRedirect("/track/{$c->consignment_number}");
        $this->get('/track')->assertOk()->assertSee('Enter the booking number');
    }

    public function test_an_unknown_number_is_a_friendly_404(): void
    {
        $this->get('/track/CG-000000-NOPE0')->assertNotFound()->assertSee('couldn');
        $this->get('/track?number=' . urlencode('<script>'))->assertNotFound()->assertDontSee('<script>', false);
        $this->getJson('/api/v1/cargo/track/CG-000000-NOPE0')->assertNotFound()->assertJsonStructure(['error']);
    }

    public function test_the_api_returns_only_public_fields_and_a_timeline(): void
    {
        $c = $this->booking('in_transit');

        $response = $this->getJson("/api/v1/cargo/track/{$c->consignment_number}")->assertOk()
            ->assertJsonPath('number', $c->consignment_number)
            ->assertJsonPath('status', 'in_transit')
            ->assertJsonPath('items', 2)
            ->assertJsonPath('sender', 'Asha M*** S***');

        $this->assertSame(['number', 'status', 'status_label', 'message', 'cancelled', 'left_behind', 'route', 'departure', 'estimated_arrival',
            'items', 'sender', 'receiver', 'timeline', 'updated_at'], array_keys($response->json()));
        $timeline = collect($response->json('timeline'))->keyBy('status');
        $this->assertTrue($timeline['booked']['reached'] && $timeline['loaded']['reached'] && $timeline['in_transit']['reached']);
        $this->assertTrue($timeline['in_transit']['current']);
        $this->assertNotNull($timeline['loaded']['at']);
        $this->assertFalse($timeline['delivered']['reached']);
        $this->assertNull($timeline['delivered']['at']);
        $this->assertNothingPrivate($response->getContent());
    }

    public function test_awaiting_payment_and_cancelled_bookings_say_so(): void
    {
        $unpaid = $this->booking('pending_payment');
        $this->get("/track/{$unpaid->consignment_number}")->assertOk()->assertSee('Waiting for payment');

        $cancelled = $this->booking('booked');
        $cancelled->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        $this->getJson("/api/v1/cargo/track/{$cancelled->consignment_number}")->assertOk()
            ->assertJsonPath('cancelled', true)->assertJsonPath('timeline', []);
    }

    public function test_lookups_are_rate_limited(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->get('/track/CG-000000-NOPE' . $i % 10);
        }
        $this->get('/track/CG-000000-NOPE0')->assertStatus(429);
    }

    public function test_sms_links_use_app_url_and_are_left_out_while_it_is_local(): void
    {
        $c = $this->booking();
        $notifier = app(SmsNotifier::class);

        config(['app.url' => 'http://127.0.0.1:8000']);
        $this->assertFalse(TrackingUrl::isPublic());
        $text = $notifier->render('cargo_booking_sender', $notifier->bookingData($c));
        $this->assertStringNotContainsString('Track', $text);
        $this->assertStringEndsWith('Receiver: Juma Receiver.', $text);

        config(['app.url' => 'https://cargo.example.co.tz/']);
        $this->assertTrue(TrackingUrl::isPublic());
        $text = $notifier->render('cargo_booking_receiver', $notifier->bookingData($c));
        $this->assertStringEndsWith("to collect. Track: https://cargo.example.co.tz/track/{$c->consignment_number}", $text);
        $this->assertSame("https://cargo.example.co.tz/track/{$c->consignment_number}", TrackingUrl::for($c->consignment_number));
    }
}
