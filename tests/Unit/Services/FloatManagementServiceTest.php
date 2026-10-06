<?php

namespace Tests\Unit\Services;

use App\Models\AgentAccount;
use App\Models\Booking;
use App\Models\SystemConfig;
use App\Models\SystemTransaction;
use App\Services\DualControlService;
use App\Services\FloatManagementService;
use App\Services\SystemTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FloatManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FloatManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(FloatManagementService::class);
    }

    /** @test */
    public function it_submits_for_dual_control_if_topup_exceeds_threshold(): void
    {
        $agentData = $this->createAgentWithAccount();
        SystemConfig::setValue('dual_control_float_threshold', '500000');
        
        // Mock DualControlService
        $dcMock = \Mockery::mock(DualControlService::class);
        $dcMock->shouldReceive('requiresDualControl')->with('float_topup')->andReturn(true);
        $dcMock->shouldReceive('submit')->once()->andReturn(new \App\Models\DualControlRequest());
        $this->app->instance(DualControlService::class, $dcMock);
        
        $service = app(FloatManagementService::class);

        $result = $service->requestTopup($agentData['account'], 600000, 'REF-123', $agentData['user']);
        
        $this->assertInstanceOf(\App\Models\DualControlRequest::class, $result);
    }

    /** @test */
    public function it_executes_topup_immediately_if_under_threshold(): void
    {
        $agentData = $this->createAgentWithAccount(1000000, [
            'commission_rate' => 0,
        ]);
        SystemConfig::setValue('security_deposit_default_rate', '0');
        SystemConfig::setValue('dual_control_float_threshold', '500000');
        
        // Mock DualControlService
        $dcMock = \Mockery::mock(DualControlService::class);
        $dcMock->shouldReceive('requiresDualControl')->with('float_topup')->andReturn(false);
        $dcMock->shouldReceive('requiresDualControl')->with('agent_topup')->andReturn(false);
        $dcMock->shouldReceive('submit')->never();
        $this->app->instance(DualControlService::class, $dcMock);
        
        // We'll also mock SmsService to avoid real HTTP calls if it's instantiating it internally
        $smsMock = \Mockery::mock('overload:\App\Services\SmsService');
        $smsMock->shouldReceive('sendSms')->andReturn(true);
        
        $service = app(FloatManagementService::class);

        // $agentData['agent'] has default security_deposit_rate of 0
        $result = $service->requestTopup($agentData['account'], 400000, 'REF-123', $agentData['user']);
        
        $this->assertInstanceOf(SystemTransaction::class, $result);
        
        $agentData['account']->refresh();
        // Since security deposit rate is 0 by default, all 400k goes to balance (default was 1000000)
        $this->assertEquals(1400000, $agentData['account']->balance);
    }

    /** @test */
    public function it_calculates_security_deposit_split_correctly(): void
    {
        $agentData = $this->createAgentWithAccount(0);
        $agentData['agent']->update(['security_deposit_rate' => 40, 'commission_method' => 'post_trip']);
        
        $smsMock = \Mockery::mock('overload:\App\Services\SmsService');
        $smsMock->shouldReceive('sendSms')->andReturn(true);
        
        $this->service->executeTopup($agentData['account'], 100000, 'REF-123');
        
        $agentData['account']->refresh();
        
        $this->assertEquals(60000, $agentData['account']->balance); // 60% active
        $this->assertEquals(40000, $agentData['account']->security_deposit_balance); // 40% hold
    }

    /** @test */
    public function it_deducts_float_for_ticket_sale_if_sufficient_balance(): void
    {
        $agentData = $this->createAgentWithAccount(100000);
        
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $agentData['user']);
        
        $tx = $this->service->deductForTicketSale($agentData['account'], $booking, 35000);
        
        $agentData['account']->refresh();
        
        $this->assertEquals(65000, $agentData['account']->balance);
        $this->assertEquals(35000, $tx->amount);
        $this->assertEquals(2, $tx->transaction_category_id); // ticket-sale
    }

    /** @test */
    public function it_fails_deduction_if_insufficient_balance(): void
    {
        $agentData = $this->createAgentWithAccount(10000);
        $agentData['account']->update(['credit_limit' => 0]);
        
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $agentData['user']);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient agent float balance');
        
        $this->service->deductForTicketSale($agentData['account'], $booking, 35000);
    }

    /** @test */
    public function it_credits_refund_back_to_agent_float(): void
    {
        $agentData = $this->createAgentWithAccount(50000);
        
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $agentData['user']);
        
        $tx = $this->service->creditRefund($agentData['account'], $booking, 15000, 'Customer cancelled');
        
        $agentData['account']->refresh();
        
        $this->assertEquals(65000, $agentData['account']->balance);
        $this->assertEquals(15000, $tx->amount);
        $this->assertEquals(5, $tx->transaction_category_id); // refund
    }

    /** @test */
    public function it_updates_credit_limit(): void
    {
        $agentData = $this->createAgentWithAccount();
        $agentData['account']->update(['credit_limit' => 0]);
        
        $updatedAccount = $this->service->updateCreditLimit($agentData['account'], 500000);
        
        $this->assertEquals(500000, $updatedAccount->credit_limit);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('cannot be negative');
        $this->service->updateCreditLimit($agentData['account'], -100);
    }
}
