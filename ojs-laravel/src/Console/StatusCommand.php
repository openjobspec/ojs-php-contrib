<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Console;

use Illuminate\Console\Command;
use OpenJobSpec\Client;

class StatusCommand extends Command
{
    protected $signature = 'ojs:status
        {--queue=default : Queue to check}';

    protected $description = 'Show OJS backend status and queue statistics';

    public function handle(Client $client): int
    {
        try {
            $health = $client->health();
            $this->info("Backend: " . ($health['status'] ?? 'unknown'));
        } catch (\Throwable $e) {
            $this->error("Cannot reach OJS backend: {$e->getMessage()}");
            return Command::FAILURE;
        }

        $queue = $this->option('queue');

        try {
            $stats = $client->getQueueStats($queue);
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Queue', $stats->name],
                    ['Available', (string) $stats->available],
                    ['Active', (string) $stats->active],
                    ['Completed', (string) $stats->completed],
                    ['Retryable', (string) $stats->retryable],
                    ['Discarded', (string) $stats->discarded],
                    ['Scheduled', (string) $stats->scheduled],
                    ['Paused', $stats->paused ? 'Yes' : 'No'],
                ],
            );
        } catch (\Throwable $e) {
            $this->warn("Could not fetch stats for queue '{$queue}': {$e->getMessage()}");
        }

        return Command::SUCCESS;
    }
}
