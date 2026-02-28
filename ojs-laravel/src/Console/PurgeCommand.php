<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Console;

use Illuminate\Console\Command;
use OpenJobSpec\Client;

class PurgeCommand extends Command
{
    protected $signature = 'ojs:purge
        {--queue=default : Queue to purge dead letters from}
        {--force : Skip confirmation}';

    protected $description = 'Purge dead-lettered jobs from a queue';

    public function handle(Client $client): int
    {
        $queue = $this->option('queue');

        try {
            $deadJobs = $client->getDeadLetterJobs($queue);
        } catch (\Throwable $e) {
            $this->error("Failed to fetch dead letter jobs: {$e->getMessage()}");
            return Command::FAILURE;
        }

        if (empty($deadJobs)) {
            $this->info("No dead-lettered jobs in queue '{$queue}'.");
            return Command::SUCCESS;
        }

        $this->warn("Found " . count($deadJobs) . " dead-lettered job(s) in queue '{$queue}'.");

        if (!$this->option('force') && !$this->confirm('Permanently discard all dead-lettered jobs?')) {
            $this->info('Cancelled.');
            return Command::SUCCESS;
        }

        $discarded = 0;
        foreach ($deadJobs as $job) {
            try {
                $client->discardDeadLetter($job->id);
                $discarded++;
            } catch (\Throwable $e) {
                $this->warn("Failed to discard {$job->id}: {$e->getMessage()}");
            }
        }

        $this->info("Discarded {$discarded} job(s).");
        return Command::SUCCESS;
    }
}
