<?php

namespace Tests\Feature;

use App\Models\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DiscountFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function agent_can_validate_and_apply_coupon(): void
    {
        $agentData = $this->createAgentWithAccount();
        
        $coupon = Coupon::create([
            'code' => 'TEST10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
            'max_uses' => 10,
            'used_count' => 0
        ]);

        $validatePayload = [
            'code' => 'TEST10',
            'amount' => 50000
        ];

        // 1. Validate Coupon
        $response = $this->actingAs($agentData['user'])->postJson('/api/v1/portal/coupons/validate', $validatePayload);
        $response->assertStatus(200)
                 ->assertJsonPath('valid', true)
                 ->assertJsonPath('data.discount_amount', 5000)
                 ->assertJsonPath('data.final_amount', 45000);
    }
}
