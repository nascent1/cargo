<?php

namespace App\Services;

use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Hands out document numbers that never repeat and never skip (AD-07).
 *
 * Each series has one row per year in document_sequences. The row is locked while a number is
 * taken, so two users issuing at the same moment wait for each other and get consecutive numbers.
 *
 * Take the number in the same database transaction that saves the document: if the save fails
 * and the transaction rolls back, the number goes back with it and no gap is left.
 */
class DocumentNumberService
{
    /**
     * Takes the next number of a series, for example "INV-2026-00042".
     *
     * @param  string       $series   lower-case name of the series: "quotation", "invoice", "credit_note"…
     * @param  int|null     $year     the year the series belongs to; this year when left out
     * @param  string|null  $prefix   used only when the series is taken for the first time in that year;
     *                                the series name in capitals when left out
     * @param  int          $padding  digits in the running number, also only on first use
     */
    public function next(string $series, ?int $year = null, ?string $prefix = null, int $padding = 5): string
    {
        $series = $this->series($series);
        $year ??= (int) now()->year;

        return DB::transaction(function () use ($series, $year, $prefix, $padding) {
            $sequence = $this->lockedSequence($series, $year, $prefix, $padding);

            $number = $sequence->next_number;
            $sequence->update(['next_number' => $number + 1]);

            return self::format($sequence->prefix, $year, $number, $sequence->padding);
        });
    }

    /** The number the next call would give, without taking it. */
    public function peek(string $series, ?int $year = null, ?string $prefix = null, int $padding = 5): string
    {
        $series = $this->series($series);
        $year ??= (int) now()->year;

        $sequence = DocumentSequence::where('series', $series)->where('year', $year)->first();

        return $sequence
            ? self::format($sequence->prefix, $year, $sequence->next_number, $sequence->padding)
            : self::format($prefix ?? strtoupper($series), $year, 1, $padding);
    }

    public static function format(string $prefix, int $year, int $number, int $padding = 5): string
    {
        return sprintf('%s-%d-%s', $prefix, $year, str_pad((string) $number, $padding, '0', STR_PAD_LEFT));
    }

    /** The series row for that year, created on first use and locked until the transaction ends. */
    private function lockedSequence(string $series, int $year, ?string $prefix, int $padding): DocumentSequence
    {
        // Two first uses at the same moment: one inserts, the other waits and then does nothing.
        DB::table('document_sequences')->insertOrIgnore([
            'series' => $series,
            'year' => $year,
            'prefix' => $prefix ?? strtoupper($series),
            'next_number' => 1,
            'padding' => max(1, min(12, $padding)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DocumentSequence::where('series', $series)->where('year', $year)->lockForUpdate()->firstOrFail();
    }

    private function series(string $series): string
    {
        $series = strtolower(trim($series));

        if (!preg_match('/^[a-z][a-z0-9_]{0,39}$/', $series)) {
            throw new InvalidArgumentException('A document series is a short lower-case name, such as "invoice" or "credit_note".');
        }

        return $series;
    }
}
