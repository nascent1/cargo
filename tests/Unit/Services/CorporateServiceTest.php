<?php

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Models\Corporate;
use App\Models\CorporateInvoice;
use App\Services\CorporateService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorporateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CorporateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CorporateService();
    }

    protected function createCorporate(array $overrides = []): Corporate
    {
        return Corporate::create(array_merge([
            'name' => 'Test Corporate',
            'email' => 'corp@example.com',
            'phone' => '1234567890',
            'status' => 'active',
            'credit_limit' => 10000.00,
            'current_balance' => 0.00,
            'payment_terms_days' => 30,
        ], $overrides));
    }

    /** @test */
    public function it_charges_booking_to_corporate_account(): void
    {
        $corporate = $this->createCorporate(['credit_limit' => 1000.00]);
        $scenario = $this->createCompleteBookingScenario();
        
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user']);
        $booking->update([
            'corporate_id' => $corporate->id,
            'net_amount' => 200.00,
            'status' => 'confirmed'
        ]);

        $this->actingAs($scenario['user']);
        
        $amountCharged = $this->service->chargeToAccount($booking);

        $this->assertEquals(200.00, $amountCharged);
        
        $corporate->refresh();
        $this->assertEquals(200.00, $corporate->current_balance);
        
        $this->assertDatabaseHas('booking_logs', [
            'booking_id' => $booking->id,
            'action' => 'Corporate Account Charged'
        ]);
    }

    /** @test */
    public function it_rejects_charging_to_inactive_corporate_account(): void
    {
        $corporate = $this->createCorporate(['status' => 'inactive']);
        $scenario = $this->createCompleteBookingScenario();
        
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user']);
        $booking->update([
            'corporate_id' => $corporate->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Corporate account '{$corporate->name}' is currently inactive.");

        $this->service->chargeToAccount($booking);
    }

    /** @test */
    public function it_generates_monthly_invoice(): void
    {
        $corporate = $this->createCorporate();
        $scenario = $this->createCompleteBookingScenario();
        
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user']);
        \Illuminate\Support\Facades\DB::table('bookings')->where('id', $booking->id)->update([
            'corporate_id' => $corporate->id,
            'net_amount' => 500.00,
            'status' => 'confirmed',
            'created_at' => Carbon::parse('2026-07-15')
        ]);

        $invoice = $this->service->generateMonthlyInvoice($corporate, '2026-07');

        $this->assertEquals(500.00, $invoice->total_amount);
        $this->assertEquals('2026-07-01', $invoice->period_start instanceof \Carbon\Carbon ? $invoice->period_start->toDateString() : \Carbon\Carbon::parse($invoice->period_start)->toDateString());
        $this->assertEquals('2026-07-31', $invoice->period_end instanceof \Carbon\Carbon ? $invoice->period_end->toDateString() : \Carbon\Carbon::parse($invoice->period_end)->toDateString());
        $this->assertEquals('draft', $invoice->status);
    }

    /** @test */
    public function it_records_payment_against_invoice(): void
    {
        $corporate = $this->createCorporate(['current_balance' => 500.00]);
        
        $invoice = CorporateInvoice::create([
            'corporate_id' => $corporate->id,
            'invoice_number' => 'INV-001',
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
            'total_amount' => 500.00,
            'paid_amount' => 0.00,
            'status' => 'sent',
            'due_date' => '2026-08-30'
        ]);

        $this->service->recordPayment($invoice, 300.00, 'REF123');

        $invoice->refresh();
        $corporate->refresh();

        $this->assertEquals(300.00, $invoice->paid_amount);
        $this->assertEquals('partially_paid', $invoice->status);
        $this->assertEquals(200.00, $corporate->current_balance);
        $this->assertStringContainsString('REF123', $invoice->notes);
    }

    /** @test */
    public function it_marks_invoice_as_sent(): void
    {
        $corporate = $this->createCorporate();
        
        $invoice = CorporateInvoice::create([
            'corporate_id' => $corporate->id,
            'invoice_number' => 'INV-001',
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
            'total_amount' => 500.00,
            'status' => 'draft',
            'due_date' => '2026-08-30'
        ]);

        $this->service->markInvoiceSent($invoice);

        $invoice->refresh();
        $this->assertEquals('sent', $invoice->status);
    }
}
