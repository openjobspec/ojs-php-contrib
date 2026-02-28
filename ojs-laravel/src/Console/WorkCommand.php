<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Console;

use Illuminate\Console\Command;
use OpenJobSpec\JobContext;
use OpenJobSpec\Worker;
use OpenJobSpec\Laravel\HandlerRegistry;

class WorkCommand extends Command
{
    protected $signature = 'ojs:work
        {--queues= : Comma-separated list of queues to process}
        {--concurrency=10 : Number of concurrent job processors}
        {--poll-interval=2.0 : Seconds between poll cycles}
        {--shutdown-timeout=25 : Seconds to wait for in-flight jobs on shutdown}';

    protected $description = 'Start the OJS worker to process background jobs';

    public function handle(Worker $worker, HandlerRegistry $registry): int
    {
        $queues = $this->option('queues')
            ? explode(',', $this->option('queues'))
            : config('ojs.worker.queues', ['default']);

        $this->info("Starting OJS worker...");
        $this->info("  Queues: " . implode(', ', $queues));
        $this->info("  Concurrency: " . $this->option('concurrency'));

        foreach ($registry->all() as $type => $handler) {
            $worker->register($type, $handler);
            $this->line("  Registered: {$type}");
        }

        if (empty($registry->all())) {
            $this->warn('No job handlers registered. Use #[OjsJob] attribute on handler classes.');
            return Command::FAILURE;
        }

        $this->info("Worker started. Press Ctrl+C to stop.");
        $worker->start();

        $this->info("Worker stopped.");
        return Command::SUCCESS;
    }
}
