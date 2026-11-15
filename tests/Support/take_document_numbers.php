<?php

/**
 * Helper process for DocumentNumberingTest: takes document numbers on its own database connection,
 * so several of these running at once compete for the same series as real users would.
 *
 *   php tests/Support/take_document_numbers.php <series> <year> <count> <start-at-unix-time>
 *
 * Prints one number per line. Refuses any database whose name does not end in "_test".
 */

use App\Services\DocumentNumberService;
use Illuminate\Contracts\Console\Kernel;
use Tests\TestCase;

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$reason = TestCase::unsafeDatabaseReason(config('database.connections.' . config('database.default') . '.database'));
if ($reason !== null) {
    fwrite(STDERR, $reason . PHP_EOL);
    exit(2);
}

[, $series, $year, $count, $startAt] = $argv + [null, null, null, 1, 0];

// All helpers wait for the same moment, so they really do ask at once.
while (microtime(true) < (float) $startAt) {
    usleep(1000);
}

$numbers = app(DocumentNumberService::class);
for ($i = 0; $i < (int) $count; $i++) {
    echo $numbers->next((string) $series, (int) $year, 'TST'), PHP_EOL;
}
