<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractLoggingCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        $outputStyle = new OutputFormatterStyle('#008fff', '');
        $output->getFormatter()->setStyle('info', $outputStyle);
        $outputStyle = new OutputFormatterStyle('green', '');
        $output->getFormatter()->setStyle('success', $outputStyle);
        return 0;
    }

    protected function logSuccess(string $message): void
    {
        $this->log($message, 'success');
    }

    protected function logInfo(string $message): void
    {
        $this->log($message, 'info');
    }

    protected function logWarning(string $message): void
    {
        $this->log($message, 'comment');
    }

    protected function logError(string $message): void
    {
        $this->log($message, 'error');
    }

    protected function log(string $message, string $type): void
    {
        $this->output->writeln(
            sprintf(
                '<%s>[%s] [%s] %s</%s>',
                $type,
                $this->getNow(),
                static::class,
                $message,
                $type
            )
        );
    }

    protected function getNow(): string
    {
        return (new \DateTime('now'))->format('Y-m-d H:i:s');
    }
}
