<?php

declare(strict_types=1);

namespace App\Command;

use App\Utility\CommandBuilderUtility;
use App\Utility\QueueUtility;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Settings\WorkerSettings;


class WorkerControllerCommand extends AbstractWorkerCommand
{
    protected static $defaultName = 'app:start-worker-controller';

    protected function configure()
    {
        $this
            ->setDescription('Start a worker pool for incoming messages.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->logSuccess('*************************************');
        $this->logSuccess(json_encode(WorkerSettings::getSettings(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $this->logSuccess('*************************************');

        // the process pool is only for receiving and delegating messages from rabbit
        $pool = new \OpenSwoole\Process\Pool(WorkerSettings::getNumberOfWorkers());

        $pool->on("WorkerStart", function (\OpenSwoole\Process\Pool $pool, $workerId) {
            $callback = function (AMQPMessage $msg) use ($workerId) {
                $msgContent = $msg->getBody();
                $this->logInfo(sprintf('%s - Received message %s', $workerId, substr($msgContent, 0, 1000)));
                $command = CommandBuilderUtility::buildCommand($msgContent);
                $this->logInfo(sprintf('%s - Running command %s', $workerId, substr($command, 0, 1000)));
                exec($command, $output, $exitCode);
                $outputContent = implode("\n", $output);
                if ($exitCode === 0) {
                    $this->logSuccess($outputContent);
                } else {
                    $this->logError($outputContent);
                }
                $this->logInfo(sprintf('%s - Done processing message. Exit code: %s', $workerId, $exitCode));
            };

            $qUtil = new QueueUtility(WorkerSettings::getRabbitQueueName(), true);
            $qUtil->consume($callback, true);
        });

        $pool->on("WorkerStop", function (\OpenSwoole\Process\Pool $pool, $workerId) {
            echo "Worker#{$workerId} is stopped\n";
        });

        $pool->start();

        $this->logError(sprintf('Pool "%s" stopped unexpectedly!', $this->workerName));

        return Command::SUCCESS;
    }
}
