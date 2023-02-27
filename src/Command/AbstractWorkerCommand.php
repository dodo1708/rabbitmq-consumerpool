<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractWorkerCommand extends AbstractLoggingCommand
{
    protected string $workerName = '<Unknown worker>';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->workerName = uniqid('worker_', true);
        $this->logInfo(sprintf('Starting worker %s ...', $this->workerName));

        return Command::SUCCESS;
    }
}
