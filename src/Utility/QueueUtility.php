<?php

declare(strict_types=1);

namespace App\Utility;

use App\Service\RabbitConnectionService;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPChannelClosedException;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Exception\AMQPIOException;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class QueueUtility
{
    // a message expires after 10 minutes max
    final public const DEFAULT_MESSAGE_TTL = 1000 * 60 * 60 * 10;
    final public const MAX_RETRY_ATTEMPTS = 50;

    protected RabbitConnectionService $connectionService;
    protected AMQPChannel $channel;
    protected ?array $declarationResult = null;
    protected bool $singleSuccess = false;

    public function __construct(
        protected string $qName,
        protected bool $durable = false,
        protected string $consumerTag = '',
        protected bool $passive = false,
        protected ?int $channelId = null,
        protected ?int $messageTtl = null
    ) {
        $this->setupChannel();
    }

    public function getDeclarationResult(): ?array
    {
        return $this->declarationResult;
    }

    public function sendJson(array $content): void
    {
        $serialized = json_encode($content, JSON_THROW_ON_ERROR);
        if ($serialized) {
            $this->sendMessage($serialized);
        }
    }

    public function sendMessage(string $message): void
    {
        $msg = new AMQPMessage(
            $message,
            [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]
        );
        $this->channel->basic_publish($msg, '', $this->qName);
    }

    public function close(): void
    {
        if ($this->channel->is_open()) {
            $this->channel->close();
        }
        //        if ($this->connectionService->getConnection()->isConnected()) {
        //            $this->connectionService->getConnection()->close();
        //        }
    }

    /**
     * Consume on a declared queue. The given callback is used on message.
     * If the single flag is set, consuming will stop after receiving the first message.
     * This allows a kind-of-notification behaviour via rabbit.
     *
     * @throws \ErrorException
     */
    public function consume(callable $callback, bool $no_ack = true, bool $single = false, int $timeout = 0): void
    {
        $this->singleSuccess = false;
        $attempts = 1;
        while ($attempts < self::MAX_RETRY_ATTEMPTS) {
            try {
                $this->resetConsumerTag();
                $this->consumeInternal($callback, $no_ack, $single, $timeout, $attempts);
            } catch (AMQPChannelClosedException $e) {
                // rabbit restart while running worker
                sleep(5);
                $this->setupChannel(true);
            } catch (AMQPConnectionClosedException $e) {
                // rabbit restart while running worker
                sleep(5);
                $this->setupChannel(true);
            } catch (AMQPIOException $e) {
                $attempts++;
                // establishing connection to rabbit failed
                sleep(5);
                $this->setupChannel(true);
            } catch (\Throwable $e) {
                sleep(1);
                $attempts++;
            }
        }

        if (!$single || !$this->singleSuccess) {
            $addMsg = isset($e) ? $e->getMessage() : '';
            throw new \RuntimeException(sprintf('Failed to consume on queue %s - Reason: %s', $this->qName, $addMsg));
        }
    }

    protected function consumeInternal(callable $callback, bool $no_ack = true, bool $single = false, int $timeout = 0, int &$attempts = 1): void
    {
        $realCallback = function (AMQPMessage $message) use ($callback, $single, &$attempts) {
            $callback($message);
            if ($single) {
                $this->channel->basic_cancel($this->consumerTag);
                $this->cleanupQueue();
                $this->channel->close();
                $this->singleSuccess = true;
                $attempts = self::MAX_RETRY_ATTEMPTS;
            }
        };
        $this->channel->basic_qos(null, 1, null);
        $consumerTag = $this->channel->basic_consume($this->qName, $this->consumerTag, false, $no_ack, false, false, $realCallback);
        if ($consumerTag !== $this->consumerTag) {
            $this->consumerTag = $consumerTag;
        }
        while ($this->channel->is_consuming()) {
            try {
                $this->channel->wait(null, false, $timeout);
            } catch (AMQPTimeoutException $e) {
                if ($single) {
                    $this->cleanupQueue();
                }
                $this->channel->close();
                throw $e;
            }
        }
        $this->channel->close();
    }

    protected function cleanupQueue(): void
    {
        try {
            $this->channel->queue_purge($this->qName);
            // attempt removing single usage queue
            $this->channel->queue_delete($this->qName, true, true);
        } catch (AMQPProtocolChannelException $e) {
        }
    }

    protected function setupChannel(bool $forceReconnect = false): void
    {
        if (isset($this->channel) && $this->channel->is_open()) {
            $this->channel->close();
        }
        $this->connectionService = RabbitConnectionService::getInstance();
        $connection = $this->connectionService->reconnect($forceReconnect);
        $this->channel = $connection->channel($this->channelId);

        if (!$this->messageTtl) {
            $this->messageTtl = (int)self::DEFAULT_MESSAGE_TTL;
        }
        $args = new AMQPTable();
        // TODO: find a way to migrate existing queues
        // $args->set('x-message-ttl', $this->messageTtl);
        $this->declarationResult = $this->channel->queue_declare($this->qName, $this->passive, $this->durable, false, false, false, $args);
    }

    protected function resetConsumerTag(): void
    {
        $this->consumerTag = bin2hex(random_bytes(32));
    }
}
