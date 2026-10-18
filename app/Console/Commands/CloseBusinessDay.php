<?php

namespace App\Console\Commands;

use App\Services\Cargo\CloseOfBusinessService;
use Illuminate\Console\Command;

/**
 * Scheduled every 5 minutes: closes the business day once the last cargo departure has left
 * (plus the buffer) and texts the summary to holders of "Receive close-of-business SMS".
 */
class CloseBusinessDay extends Command
{
    protected $signature = 'notifications:close-business-day
                            {--force : Close today now, even before the cut-off}
                            {--dry-run : Show the cut-off, recipients and message without saving or sending}';

    protected $description = 'Close the business day and send the close-of-business SMS summary';

    public function handle(CloseOfBusinessService $service): int
    {
        $cutoff = $service->cutoff();

        if ($this->option('dry-run')) {
            $preview = $service->preview();
            $this->info('Cut-off: ' . $cutoff['at']->format('Y-m-d H:i') . ' (' . ($cutoff['trigger'] === 'last_departure'
                ? 'last departure ' . substr((string) $cutoff['service']->departure_time, 0, 5) . ' + buffer'
                : 'fallback time, no departures today') . ')');
            $this->line('Recipients: ' . (implode(', ', $preview['recipients']) ?: 'none'));
            $this->line('Message: ' . ($preview['text'] ?? '(close_of_business template is switched off)'));

            return self::SUCCESS;
        }

        $closure = $service->closeIfDue((bool) $this->option('force'));
        if (!$closure) {
            $this->line('Not due: cut-off ' . $cutoff['at']->format('H:i') . ', or today is already closed.');

            return self::SUCCESS;
        }

        $this->info("Closed {$closure->business_date->toDateString()}: sent {$closure->sent_count}/{$closure->recipients_count}.");

        return self::SUCCESS;
    }
}
