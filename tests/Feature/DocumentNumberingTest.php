<?php

namespace Tests\Feature;

use App\Models\DocumentSequence;
use App\Services\DocumentNumberService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class DocumentNumberingTest extends TestCase
{
    use DatabaseTransactions;

    private DocumentNumberService $numbers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->numbers = app(DocumentNumberService::class);
    }

    #[Test]
    public function numbers_of_a_series_are_consecutive_and_formatted(): void
    {
        $this->assertSame('QTN-2026-00001', $this->numbers->next('test_quotation', 2026, 'QTN'));
        $this->assertSame('QTN-2026-00002', $this->numbers->next('test_quotation', 2026, 'QTN'));
        $this->assertSame('QTN-2026-00003', $this->numbers->next('test_quotation', 2026));

        $this->assertSame(4, DocumentSequence::where('series', 'test_quotation')->where('year', 2026)->value('next_number'));
    }

    #[Test]
    public function each_series_and_each_year_counts_on_its_own(): void
    {
        $this->assertSame('INV-2026-00001', $this->numbers->next('test_invoice', 2026, 'INV'));
        $this->assertSame('CRN-2026-00001', $this->numbers->next('test_credit_note', 2026, 'CRN'));
        $this->assertSame('INV-2026-00002', $this->numbers->next('test_invoice', 2026, 'INV'));
        $this->assertSame('INV-2027-00001', $this->numbers->next('test_invoice', 2027, 'INV'), 'a new year starts again at 1');
        $this->assertSame('INV-2026-00003', $this->numbers->next('test_invoice', 2026, 'INV'));
    }

    #[Test]
    public function the_year_defaults_to_this_year_and_the_prefix_to_the_series_name(): void
    {
        $this->assertSame('TEST_RECEIPT-' . now()->year . '-00001', $this->numbers->next('test_receipt'));
    }

    #[Test]
    public function prefix_and_padding_are_fixed_by_the_first_use(): void
    {
        $this->assertSame('SOF-2026-001', $this->numbers->next('test_statement', 2026, 'SOF', 3));
        $this->assertSame('SOF-2026-002', $this->numbers->next('test_statement', 2026, 'OTHER', 8), 'later calls cannot change the format');

        DocumentSequence::where('series', 'test_statement')->update(['next_number' => 1000]);
        $this->assertSame('SOF-2026-1000', $this->numbers->next('test_statement', 2026), 'padding never cuts a number short');
    }

    #[Test]
    public function peeking_shows_the_next_number_without_taking_it(): void
    {
        $this->assertSame('INV-2026-00001', $this->numbers->peek('test_peek', 2026, 'INV'));
        $this->assertSame('INV-2026-00001', $this->numbers->peek('test_peek', 2026, 'INV'));
        $this->assertFalse(DocumentSequence::where('series', 'test_peek')->exists(), 'peeking creates nothing');

        $this->assertSame('INV-2026-00001', $this->numbers->next('test_peek', 2026, 'INV'));
        $this->assertSame('INV-2026-00002', $this->numbers->peek('test_peek', 2026));
    }

    #[Test]
    public function a_number_goes_back_when_the_document_is_not_saved(): void
    {
        $this->assertSame('INV-2026-00001', $this->numbers->next('test_gap', 2026, 'INV'));

        try {
            DB::transaction(function () {
                $this->assertSame('INV-2026-00002', $this->numbers->next('test_gap', 2026, 'INV'));

                throw new \RuntimeException('saving the invoice failed');
            });
        } catch (\RuntimeException) {
            // the transaction rolled back, and the number with it
        }

        $this->assertSame('INV-2026-00002', $this->numbers->next('test_gap', 2026, 'INV'), 'no gap is left');
    }

    #[Test]
    public function a_series_needs_a_proper_name(): void
    {
        foreach (['', 'Invoice 2026', '9lives', 'drop;table', str_repeat('a', 41)] as $bad) {
            try {
                $this->numbers->next($bad, 2026);
                $this->fail("\"{$bad}\" should have been refused.");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('document series', $e->getMessage());
            }
        }

        $this->assertSame('INVOICE-2026-00001', $this->numbers->next(' Invoice ', 2026), 'case and spaces around the name are forgiven');
    }

    /**
     * Ten users issuing at the same moment (NFR-02, and the Phase 0 exit criterion).
     *
     * Ten separate processes, each with its own database connection, take ten numbers each from one
     * series, all starting at the same instant. This cannot run inside the test's transaction, so it
     * commits to a throwaway series on the test database and deletes that series when it is done.
     */
    #[Test]
    public function numbers_taken_in_parallel_are_consecutive_with_no_repeat_and_no_gap(): void
    {
        $processes = 10;
        $each = 10;
        $series = 'test_parallel_' . strtolower(Str::random(10));
        $year = (int) now()->year;

        // A second connection, outside the test's transaction: it sees and removes what the helpers commit.
        config(['database.connections.outside_test_transaction' => config('database.connections.' . config('database.default'))]);
        $outside = DB::connection('outside_test_transaction');
        $database = $outside->getDatabaseName();
        $this->assertStringEndsWith('_test', $database);

        try {
            $startAt = microtime(true) + 2;
            $running = [];
            for ($i = 0; $i < $processes; $i++) {
                $process = new Process(
                    [PHP_BINARY, base_path('tests/Support/take_document_numbers.php'), $series, $year, $each, $startAt],
                    base_path(),
                    ['APP_ENV' => 'testing', 'DB_DATABASE' => $database],
                    null,
                    60
                );
                $process->start();
                $running[] = $process;
            }

            $taken = [];
            $perProcess = [];
            foreach ($running as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput() ?: $process->getOutput());

                $lines = array_values(array_filter(array_map('trim', explode("\n", $process->getOutput()))));
                $this->assertCount($each, $lines);
                $perProcess[] = $lines;
                $taken = array_merge($taken, $lines);
            }

            $expected = [];
            for ($n = 1; $n <= $processes * $each; $n++) {
                $expected[] = DocumentNumberService::format('TST', $year, $n);
            }

            $this->assertCount($processes * $each, array_unique($taken), 'no number was given twice');
            sort($taken);
            $this->assertSame($expected, $taken, 'the numbers run from 1 to 100 with no gap');
            $this->assertSame(
                $processes * $each + 1,
                (int) $outside->table('document_sequences')->where('series', $series)->where('year', $year)->value('next_number')
            );

            // Proof that the processes really overlapped: at least one of them did not get a solid block of ten.
            $interleaved = array_filter($perProcess, fn (array $lines) => (int) substr(end($lines), -5) - (int) substr($lines[0], -5) > $each - 1);
            $this->assertNotEmpty($interleaved, 'the helper processes ran one after another, so this run proved nothing about parallel use');
        } finally {
            $outside->table('document_sequences')->where('series', $series)->delete();
            $outside->disconnect();
        }

        $this->assertFalse(DocumentSequence::where('series', $series)->exists(), 'the throwaway series is removed');
    }
}
