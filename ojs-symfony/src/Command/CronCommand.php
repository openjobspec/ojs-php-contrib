<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\Command;

use OpenJobSpec\Symfony\Scheduling\CronManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'ojs:cron', description: 'Manage OJS cron jobs (list, register, unregister)')]
class CronCommand extends Command
{
    public function __construct(private readonly CronManager $cronManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::REQUIRED, 'Action to perform: list, register, unregister')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Cron job name')
            ->addOption('cron', null, InputOption::VALUE_REQUIRED, 'Cron expression (e.g., "*/5 * * * *")')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Job type to schedule')
            ->addOption('queue', null, InputOption::VALUE_REQUIRED, 'Queue name', 'default');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = $input->getArgument('action');

        return match ($action) {
            'list' => $this->listCrons($io),
            'register' => $this->registerCron($io, $input),
            'unregister' => $this->unregisterCron($io, $input),
            default => $this->invalidAction($io, $action),
        };
    }

    private function listCrons(SymfonyStyle $io): int
    {
        try {
            $crons = $this->cronManager->list();
        } catch (\Throwable $e) {
            $io->error("Failed to list cron jobs: {$e->getMessage()}");
            return Command::FAILURE;
        }

        if ($crons === []) {
            $io->info('No cron jobs registered.');
            return Command::SUCCESS;
        }

        $rows = array_map(fn($c) => [$c->name, $c->cron, $c->type, $c->queue], $crons);
        $io->table(['Name', 'Schedule', 'Job Type', 'Queue'], $rows);

        return Command::SUCCESS;
    }

    private function registerCron(SymfonyStyle $io, InputInterface $input): int
    {
        $name = $input->getOption('name');
        $cron = $input->getOption('cron');
        $type = $input->getOption('type');
        $queue = $input->getOption('queue');

        if (!$name || !$cron || !$type) {
            $io->error('Options --name, --cron, and --type are required for register.');
            return Command::FAILURE;
        }

        try {
            $cronJob = new \OpenJobSpec\CronJob(
                name: $name,
                cron: $cron,
                type: $type,
                queue: $queue,
            );
            $this->cronManager->register($cronJob);
            $io->success("Cron job '{$name}' registered.");
        } catch (\Throwable $e) {
            $io->error("Failed to register cron job: {$e->getMessage()}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function unregisterCron(SymfonyStyle $io, InputInterface $input): int
    {
        $name = $input->getOption('name');

        if (!$name) {
            $io->error('Option --name is required for unregister.');
            return Command::FAILURE;
        }

        try {
            $this->cronManager->unregister($name);
            $io->success("Cron job '{$name}' unregistered.");
        } catch (\Throwable $e) {
            $io->error("Failed to unregister cron job: {$e->getMessage()}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function invalidAction(SymfonyStyle $io, string $action): int
    {
        $io->error("Unknown action '{$action}'. Use: list, register, unregister");
        return Command::FAILURE;
    }
}
