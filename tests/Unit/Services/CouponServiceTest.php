<?php

namespace Tests\Unit\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Promotion;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CouponService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(CouponService::class);
    }

    /** @test */
    public function it_validates_valid_coupon_successfully(): void
    {
        $coupon = Coupon::create([
            'code' => 'VALID123',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
        ]);

        $validated = $this->service->validate('VALID123', 50000, 'TZS');

        $this->assertEquals($coupon->id, $validated->id);
    }

    /** @test */
    public function it_rejects_invalid_or_expired_coupon(): void
    {
        $coupon = Coupon::create([
            'code' => 'EXPIRED',
            'discount_type' => 'fixed',
            'discount_value' => 5000,
            'is_active' => true,
            'valid_to' => now()->subDay(),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('has expired');

        $this->service->validate('EXPIRED', 50000, 'TZS');
    }

    /** @test */
    public function it_rejects_coupon_if_max_uses_reached(): void
    {
        $coupon = Coupon::create([
            'code' => 'MAXEDOUT',
            'discount_type' => 'fixed',
            'discount_value' => 5000,
            'is_active' => true,
            'max_uses' => 5,
            'current_uses' => 5,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('maximum usage limit');

        $this->service->validate('MAXEDOUT', 50000, 'TZS');
    }

    /** @test */
    public function it_respects_target_audience_restrictions(): void
    {
        Coupon::create([
            'code' => 'RESIDENTSONLY',
            'discount_type' => 'fixed',
            'discount_value' => 5000,
            'is_active' => true,
            'target_audience' => 'residents',
        ]);

        // Should pass for TZS
        $validated = $this->service->validate('RESIDENTSONLY', 50000, 'TZS');
        $this->assertNotNull($validated);

        // Should fail for USD
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('only valid for Residents');

        $this->service->validate('RESIDENTSONLY', 50, 'USD');
    }

    /** @test */
    public function it_previews_coupon_discount(): void
    {
        Coupon::create([
            'code' => 'PREVIEWCODE',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'is_active' => true,
        ]);

        $preview = $this->service->preview('PREVIEWCODE', 100000, 'TZS');

        $this->assertEquals('PREVIEWCODE', $preview['code']);
        $this->assertEquals(15000.0, $preview['discount_amount']);
        $this->assertEquals(85000.0, $preview['final_amount']);
    }

    /** @test */
    public function it_redeems_coupon_and_records_usage(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user']);
        
        $promo = Promotion::create([
            'name' => 'Test Promo',
            'discount_type' => 'fixed',
            'discount_value' => 5000,
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'current_uses' => 0,
            'budget_used' => 0,
        ]);

        $coupon = Coupon::create([
            'code' => 'REDEEMME',
            'promotion_id' => $promo->id,
            'discount_type' => 'fixed',
            'discount_value' => 5000,
            'is_active' => true,
            'current_uses' => 0,
        ]);

        $usage = $this->service->redeem('REDEEMME', $booking, 35000, 'TZS');

        $this->assertInstanceOf(CouponUsage::class, $usage);
        $this->assertEquals($coupon->id, $usage->coupon_id);
        $this->assertEquals(5000.0, $usage->discount_applied);
        
        $coupon->refresh();
        $this->assertEquals(1, $coupon->current_uses);
        
        $promo->refresh();
        $this->assertEquals(1, $promo->current_uses);
        $this->assertEquals(5000.0, $promo->budget_used);
    }

    /** @test */
    public function it_prevents_redeeming_same_coupon_twice_for_same_booking(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user']);
        
        $coupon = Coupon::create([
            'code' => 'ONCEONLY',
            'discount_type' => 'fixed',
            'discount_value' => 5000,
            'max_uses' => 10,
            'is_active' => true,
        ]);

        $this->service->redeem('ONCEONLY', $booking, 35000, 'TZS');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('already been applied');

        $this->service->redeem('ONCEONLY', $booking, 35000, 'TZS');
    }
}
