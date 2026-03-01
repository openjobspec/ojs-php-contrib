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

#[AsCommand(name: 'ojs:purge', description: 'Purge dead-lettered jobs from a queue')]
class PurgeCommand extends Command
{
    public function __construct(private readonly Client $client)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('queue', null, InputOption::VALUE_REQUIRED, 'Queue to purge', 'default')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Skip confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $queue = $input->getOption('queue');

        try {
            $deadJobs = $this->client->getDeadLetterJobs($queue);
        } catch (\Throwable $e) {
            $io->error("Failed to fetch dead letter jobs: {$e->getMessage()}");
            return Command::FAILURE;
        }

        if (empty($deadJobs)) {
            $io->info("No dead-lettered jobs in queue '{$queue}'.");
            return Command::SUCCESS;
        }

        $io->warning(count($deadJobs) . " dead-lettered job(s) in queue '{$queue}'.");

        if (!$input->getOption('force') && !$io->confirm('Permanently discard all dead-lettered jobs?', false)) {
            $io->info('Cancelled.');
            return Command::SUCCESS;
        }

        $discarded = 0;
        foreach ($deadJobs as $job) {
            try {
                $this->client->discardDeadLetter($job->id);
                $discarded++;
            } catch (\Throwable $e) {
                $io->warning("Failed to discard {$job->id}: {$e->getMessage()}");
            }
        }

        $io->success("Discarded {$discarded} job(s).");
        return Command::SUCCESS;
    }
}
