<?php

declare(strict_types=1);

namespace App\Command;

use App\Utility\QueueUtility;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class SendMessagesCommand extends AbstractLoggingCommand
{
    protected static $defaultName = 'app:start-send-messages';

    protected function configure()
    {
        $this
            ->setDescription('')
            ->setHelp('');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $qUtil = new QueueUtility('testq', true);
        $i = 0;
        while (1) {
            $qUtil->sendMessage("FOOOOOOOOO $i");
            $this->logInfo("Sent message $i");
            $i++;
            usleep(100000);
        }

        return Command::SUCCESS;
    }
}
