<?php

declare(strict_types=1);

namespace App\Service;

use App\Settings\WorkerSettings;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitConnectionService
{
    private static ?RabbitConnectionService $connectionService = null;
    private AMQPStreamConnection $connection;

    public static function getInstance(): self
    {
        if (self::$connectionService === null) {
            self::$connectionService = new RabbitConnectionService();
        }

        return self::$connectionService;
    }

    public function getConnection(): AMQPStreamConnection
    {
        if (!isset($this->connection) || !$this->connection->isConnected()) {
            $this->connect();
        }
        return $this->connection;
    }

    public function closeConnection(): void
    {
        if (isset($this->connection) && $this->connection->isConnected()) {
            $this->connection->close();
        }
    }

    public function reconnect(bool $force = false): AMQPStreamConnection
    {
        if ($force) {
            $this->closeConnection();
        }
        return $this->getConnection();
    }

    protected function connect(): void
    {
        $maxRetries = 10;
        $retries = 0;
        while ((!isset($this->connection) || !$this->connection->isConnected()) && $retries < $maxRetries) {
            try {
                /** @phpstan-ignore-next-line */
                $this->connection = new AMQPStreamConnection(
                    WorkerSettings::getRabbitHost(),
                    (int)WorkerSettings::getRabbitPort(),
                    WorkerSettings::getRabbitUser(),
                    WorkerSettings::getRabbitPassword()
                );
            } catch (\Exception $e) {
                $retries++;
                sleep(1);
            }
        }
        if ($retries >= $maxRetries && (!isset($this->connection) || !$this->connection->isConnected())) {
            throw new \RuntimeException('Could not connect to rabbit instance.');
        }
    }
}
