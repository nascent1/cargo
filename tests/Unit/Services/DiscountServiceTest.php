<?php

namespace Tests\Unit\Services;

use App\Models\PriceDiscount;
use App\Models\Promotion;
use App\Models\SystemConfig;
use App\Services\DiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DiscountService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        PriceDiscount::query()->delete();
        Promotion::query()->delete();
        
        $this->service = app(DiscountService::class);
    }

    /** @test */
    public function it_applies_percentage_price_discount(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        
        $discount = PriceDiscount::create([
            'name' => '10% Off Economy',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'route_id' => $scenario['route']->id,
            'travel_class_id' => $scenario['class']->id,
            'target_audience' => 'all',
            'is_active' => true,
            'priority' => 1,
        ]);

        $result = $this->service->findApplicableDiscount(
            $scenario['route']->id, 
            $scenario['class']->id, 
            null, 
            1, 
            35000, 
            'TZS'
        );

        $this->assertNotNull($result);
        $this->assertEquals($discount->id, $result['id']);
        $this->assertEquals(3500.0, $result['amount']); // 10% of 35,000
    }

    /** @test */
    public function it_applies_fixed_price_discount_and_converts_usd(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        SystemConfig::setValue('usd_to_tzs_exchange_rate', '2500');
        
        PriceDiscount::create([
            'name' => '5000 TZS Off',
            'discount_type' => 'fixed',
            'discount_value' => 5000, // TZS
            'discount_value_usd' => null, // force conversion
            'is_active' => true,
            'priority' => 1,
        ]);

        $result = $this->service->findApplicableDiscount(
            $scenario['route']->id, 
            $scenario['class']->id, 
            null, 
            1, 
            35.00, 
            'USD'
        );

        $this->assertNotNull($result);
        $this->assertEquals(2.0, $result['amount']); // 5000 / 2500 = $2
    }

    /** @test */
    public function it_respects_target_audience_restrictions(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        
        PriceDiscount::create([
            'name' => 'Resident Only',
            'discount_type' => 'fixed',
            'discount_value' => 5000,
            'target_audience' => 'residents',
            'is_active' => true,
            'priority' => 1,
        ]);

        // Residents check should fail for USD
        $usdResult = $this->service->findApplicableDiscount(
            $scenario['route']->id, $scenario['class']->id, null, 1, 35.0, 'USD'
        );
        
        // Residents check should pass for TZS
        $tzsResult = $this->service->findApplicableDiscount(
            $scenario['route']->id, $scenario['class']->id, null, 1, 35000, 'TZS'
        );

        $this->assertNull($usdResult);
        $this->assertNotNull($tzsResult);
    }

    /** @test */
    public function it_respects_group_size_minimums(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        
        PriceDiscount::create([
            'name' => 'Group of 5+',
            'discount_type' => 'fixed',
            'discount_value' => 10000,
            'min_passengers' => 5,
            'is_active' => true,
            'priority' => 1,
        ]);

        $failResult = $this->service->findApplicableDiscount(
            $scenario['route']->id, $scenario['class']->id, null, 4, 35000, 'TZS'
        );
        
        $passResult = $this->service->findApplicableDiscount(
            $scenario['route']->id, $scenario['class']->id, null, 5, 35000, 'TZS'
        );

        $this->assertNull($failResult);
        $this->assertNotNull($passResult);
    }

    /** @test */
    public function it_picks_the_highest_value_discount_when_multiple_match(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        
        PriceDiscount::create([
            'name' => '5000 Off',
            'discount_type' => 'fixed',
            'discount_value' => 5000,
            'is_active' => true,
            'priority' => 1, // High priority
        ]);
        
        PriceDiscount::create([
            'name' => '50% Off', // 17,500 off 35,000
            'discount_type' => 'percentage',
            'discount_value' => 50,
            'is_active' => true,
            'priority' => 2, // Low priority
        ]);

        $result = $this->service->findApplicableDiscount(
            $scenario['route']->id, $scenario['class']->id, null, 1, 35000, 'TZS'
        );

        $this->assertNotNull($result);
        $this->assertEquals(17500.0, $result['amount']); // Highest amount wins, ignoring priority
        $this->assertEquals('50% Off', $result['name']);
    }

    /** @test */
    public function it_applies_no_code_promotions_if_better_than_price_discount(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        
        PriceDiscount::create([
            'name' => 'Small Discount',
            'discount_type' => 'fixed',
            'discount_value' => 2000,
            'is_active' => true,
            'priority' => 1,
        ]);
        
        Promotion::create([
            'name' => 'Big Auto Promo',
            'code' => null, // No code required
            'discount_type' => 'fixed',
            'discount_value' => 8000,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'is_active' => true,
        ]);

        $result = $this->service->findApplicableDiscount(
            $scenario['route']->id, $scenario['class']->id, null, 1, 35000, 'TZS'
        );

        $this->assertNotNull($result);
        $this->assertEquals('promotion', $result['source']);
        $this->assertEquals(8000.0, $result['amount']);
        $this->assertEquals('Big Auto Promo', $result['name']);
    }

    /** @test */
    public function it_respects_promotion_budgets_and_deducts_used_budget(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        
        $promo = Promotion::create([
            'name' => 'Budgeted Promo',
            'discount_type' => 'fixed',
            'discount_value' => 10000,
            'budget' => 15000,
            'budget_used' => 0,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'is_active' => true,
        ]);
        
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user']);
        $booking->items()->update(['base_fare' => 35000, 'discount_amount' => 0, 'final_fare' => 35000]);

        $totalApplied = $this->service->applyToBooking($booking);

        $this->assertEquals(10000, $totalApplied);
        
        $promo->refresh();
        $this->assertEquals(10000, $promo->budget_used);
        
        // Second application should only have 5000 budget left
        $booking2 = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user']);
        $booking2->items()->update(['base_fare' => 35000, 'discount_amount' => 0, 'final_fare' => 35000]);
        
        $totalApplied2 = $this->service->applyToBooking($booking2);
        
        $this->assertEquals(5000, $totalApplied2);
        
        $promo->refresh();
        $this->assertEquals(15000, $promo->budget_used);
    }

    /** @test */
    public function it_can_preview_cart_with_discounts_without_saving(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        
        PriceDiscount::create([
            'name' => 'Cart Discount',
            'discount_type' => 'fixed',
            'discount_value' => 5000,
            'is_active' => true,
            'priority' => 1,
        ]);
        
        $cart = [
            [
                'passenger_index' => 0,
                'route_id' => $scenario['route']->id,
                'ferry_class_id' => $scenario['class']->id,
                'base_fare' => 35000,
                'currency' => 'TZS',
            ]
        ];

        $preview = $this->service->previewCart($cart, 1);
        
        $this->assertCount(1, $preview['items']);
        $this->assertEquals(35000, $preview['items'][0]['base_fare']);
        $this->assertEquals(5000, $preview['items'][0]['discount_amount']);
        $this->assertEquals(30000, $preview['items'][0]['final_fare']);
        $this->assertEquals('Cart Discount', $preview['items'][0]['discount_name']);
    }
}
