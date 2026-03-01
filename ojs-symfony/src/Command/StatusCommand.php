<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\Command;

use OpenJobSpec\Client;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'ojs:status', description: 'Show OJS backend status and queue statistics')]
class StatusCommand extends Command
{
    public function __construct(private readonly Client $client)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('queue', null, InputOption::VALUE_REQUIRED, 'Queue to check', 'default');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $health = $this->client->health();
            $io->success("Backend status: " . ($health['status'] ?? 'unknown'));
        } catch (\Throwable $e) {
            $io->error("Cannot reach OJS backend: {$e->getMessage()}");
            return Command::FAILURE;
        }

        $queue = $input->getOption('queue');

        try {
            $stats = $this->client->getQueueStats($queue);
            $io->table(
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
            $io->warning("Could not fetch stats for queue '{$queue}': {$e->getMessage()}");
        }

        return Command::SUCCESS;
    }
}
