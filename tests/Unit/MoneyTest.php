<?php

namespace Tests\Unit;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Pure rules: no database, no application. */
class MoneyTest extends TestCase
{
    #[Test]
    public function tzs_is_whole_shillings_and_usd_has_two_decimals(): void
    {
        $this->assertSame(0, Money::decimals('TZS'));
        $this->assertSame(2, Money::decimals('USD'));

        $this->assertSame(35600.0, Money::round(35599.5, 'TZS'));
        $this->assertSame(35599.0, Money::round(35599.49, 'TZS'));
        $this->assertSame(12.35, Money::round(12.345, 'USD'));
        $this->assertSame(12.34, Money::round(12.344, 'USD'));
        $this->assertSame(1.01, Money::round(1.005, 'USD'), 'a half cent rounds up');
    }

    #[Test]
    public function rounding_is_half_away_from_zero_and_never_gives_minus_zero(): void
    {
        $this->assertSame(-3.0, Money::round(-2.5, 'TZS'));
        $this->assertSame(3.0, Money::round(2.5, 'TZS'));
        $this->assertSame('0', (string) Money::round(-0.4, 'TZS'));
        $this->assertSame('TZS 0', Money::format(-0.4, 'TZS'));
    }

    #[Test]
    public function empty_and_text_amounts_are_read_as_numbers(): void
    {
        $this->assertSame(0.0, Money::round(null, 'TZS'));
        $this->assertSame(1500.0, Money::round('1500.00', 'TZS'), 'a decimal column value');
        $this->assertSame(19.99, Money::round('19.990000', 'USD'));
    }

    #[Test]
    public function amounts_are_formatted_with_the_currency_code(): void
    {
        $this->assertSame('TZS 35,600', Money::format(35600, 'TZS'));
        $this->assertSame('USD 5,400.00', Money::format(5400, 'USD'));
        $this->assertSame('14,040,000', Money::format(14040000, 'tzs', withCode: false));
        $this->assertSame('USD 0.50', Money::format('0.5', 'usd'));
    }

    #[Test]
    public function example_j_an_invoice_in_usd_paid_in_tzs_at_a_later_rate(): void
    {
        // Requirements 5.7: USD 5,400 issued at 2,600 and paid at 2,650.
        $atIssue = Money::toTzs(5400, 'USD', 2600);
        $atPayment = Money::toTzs(5400, 'USD', 2650);

        $this->assertSame(14040000.0, $atIssue);
        $this->assertSame(14310000.0, $atPayment);
        $this->assertSame(270000.0, $atPayment - $atIssue, 'the exchange difference');
    }

    #[Test]
    public function conversion_rounds_to_the_target_currency(): void
    {
        $this->assertSame(38.46, Money::convert(100000, 'TZS', 'USD', 2600), '100,000 / 2,600 = 38.4615…');
        $this->assertSame(26.0, Money::convert(0.01, 'USD', 'TZS', 2600));
        $this->assertSame(1300.0, Money::convert(0.4999, 'USD', 'TZS', 2600), '1,299.74 rounds to whole shillings');
        $this->assertSame(500.0, Money::convert(500.4, 'TZS', 'TZS'), 'same currency: only rounded, no rate needed');
        $this->assertSame(500.0, Money::toTzs(500, 'TZS'));
    }

    #[Test]
    public function a_conversion_without_a_rate_is_refused(): void
    {
        foreach ([null, 0, -2600, ''] as $rate) {
            try {
                Money::convert(10, 'USD', 'TZS', $rate);
                $this->fail('A conversion without a usable rate should be refused.');
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('exchange rate above zero', $e->getMessage());
            }
        }
    }

    #[Test]
    public function only_tzs_and_usd_are_accepted(): void
    {
        $this->assertSame(['TZS', 'USD'], Money::currencies());
        $this->assertTrue(Money::isSupported(' usd '));
        $this->assertFalse(Money::isSupported('KES'));
        $this->assertFalse(Money::isSupported(null));
        $this->assertSame('USD', Money::currency('usd'));

        $this->expectException(InvalidArgumentException::class);
        Money::round(10, 'KES');
    }

    #[Test]
    public function a_money_record_holds_amount_currency_rate_and_tzs_equivalent(): void
    {
        $this->assertSame(
            ['amount' => 5400.0, 'currency' => 'USD', 'exchange_rate' => 2600.0, 'amount_tzs' => 14040000.0],
            Money::record(5400, 'usd', 2600)
        );
        $this->assertSame(
            ['amount' => 35600.0, 'currency' => 'TZS', 'exchange_rate' => 2600.0, 'amount_tzs' => 35600.0],
            Money::record(35600.4, 'TZS', '2600.000000'),
            'a TZS record keeps the rate in force for reference'
        );
        $this->assertSame(
            ['amount' => 35600.0, 'currency' => 'TZS', 'exchange_rate' => null, 'amount_tzs' => 35600.0],
            Money::record(35600, 'TZS')
        );

        $this->expectException(InvalidArgumentException::class);
        Money::record(10, 'USD'); // a USD record must have a rate
    }

    #[Test]
    public function the_tzs_equivalent_is_taken_from_the_rounded_amount(): void
    {
        // 10.005 USD is stored as 10.01, so its equivalent is 10.01 × 2,600, not 10.005 × 2,600.
        $record = Money::record(10.005, 'USD', 2600);

        $this->assertSame(10.01, $record['amount']);
        $this->assertSame(26026.0, $record['amount_tzs']);
    }

    #[Test]
    public function percentages_and_sums_are_rounded_once(): void
    {
        $this->assertSame(6000.0, Money::percent(400000, 1.5, 'TZS'), 'a value charge of 1.5%');
        $this->assertSame(0.0, Money::percent(400000, 0, 'TZS'));
        $this->assertSame(1.24, Money::percent(49.5, 2.5, 'USD'), '1.2375 rounds to cents');

        $this->assertSame(1.0, Money::sum([0.4, 0.4, 0.2], 'TZS'), 'summed first, then rounded');
        $this->assertSame(0.3, Money::sum([0.1, 0.2], 'USD'), 'no floating-point tail');
        $this->assertSame(0.0, Money::sum([], 'TZS'));
        $this->assertSame(35600.0, Money::sum(['9600.00', '18000.00', '8000.00'], 'TZS'), 'example A: CBM + weight + value');
        $this->assertSame(8000.0, Money::percent(800000, 1, 'TZS'), 'example A: 1% of the declared value');
        $this->assertSame(14.04, Money::sum([3.84, 7.20, 3.00], 'USD'), 'example B: the same parcel in USD');
    }

    #[Test]
    public function amounts_are_compared_at_the_currency_precision(): void
    {
        $this->assertTrue(Money::equals(0.1 + 0.2, 0.3, 'USD'));
        $this->assertTrue(Money::equals(1000.4, 1000, 'TZS'));
        $this->assertFalse(Money::equals(1000.5, 1000, 'TZS'));
        $this->assertFalse(Money::equals(10.01, 10.02, 'USD'));
    }
}
