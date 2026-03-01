<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\Command;

use OpenJobSpec\JobContext;
use OpenJobSpec\Worker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'ojs:work', description: 'Start the OJS worker to process background jobs')]
class WorkCommand extends Command
{
    public function __construct(private readonly Worker $worker)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('queues', null, InputOption::VALUE_REQUIRED, 'Comma-separated list of queues', 'default')
            ->addOption('concurrency', null, InputOption::VALUE_REQUIRED, 'Number of concurrent processors', '10');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $queues = explode(',', $input->getOption('queues'));

        $io->title('OJS Worker');
        $io->listing(array_map(fn($q) => "Queue: {$q}", $queues));
        $io->info('Worker started. Press Ctrl+C to stop.');

        $this->worker->start();

        $io->success('Worker stopped.');
        return Command::SUCCESS;
    }
}
