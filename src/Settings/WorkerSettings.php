<?php

declare(strict_types=1);

namespace App\Command;

class WorkerSettings
{
    private const DEFAULT_RABBIT_USER = 'rabbit';
    private const DEFAULT_RABBIT_PASSWORD = '';
    private const DEFAULT_RABBIT_HOST = 'rabbit';
    private const DEFAULT_RABBIT_PORT = '5672';
    private const DEFAULT_BINARY_PATH = '/usr/local/bin/php';
    private const DEFAULT_COMMAND = '-v';
    private const DEFAULT_BASE64 = true;
    # TODO: error reporting?
    # TODO: max exec time?

    public static function getNumberOfWorkers(): int
    {
        $customVal = getenv('RC_NUMBER_OF_WORKERS');
        if ($customVal && (int)$customVal > 0) {
            return (int)$customVal;
        }
        // it will use the number of available cores reported by the OS
        exec('nproc', $availableCores);
        $count = reset($availableCores);
        return (int)$count ?: 1;
    }

    public static function getRabbitUser(): string
    {
        $customVal = getenv('RABBIT_USER');
        if ($customVal && (string)$customVal !== '') {
            return (string)$customVal;
        }
        return self::DEFAULT_RABBIT_USER;
    }

    public static function getRabbitPassword(): string
    {
        $customVal = getenv('RABBIT_PASSWORD');
        if ($customVal) {
            return (string)$customVal;
        }
        return self::DEFAULT_RABBIT_PASSWORD;
    }

    public static function getRabbitHost(): string
    {
        $customVal = getenv('RABBIT_HOST');
        if ($customVal && (string)$customVal !== '') {
            return (string)$customVal;
        }
        return self::DEFAULT_RABBIT_HOST;
    }

    public static function getRabbitPort(): string
    {
        $customVal = getenv('RABBIT_PORT');
        if ($customVal && (string)$customVal !== '') {
            return (string)$customVal;
        }
        return self::DEFAULT_RABBIT_PORT;
    }

    public static function getRabbitQueueName(): string
    {
        $customVal = getenv('RABBIT_QUEUE_NAME');
        if ($customVal && (string)$customVal > 0) {
            return (string)$customVal;
        }
        throw new \RuntimeException('RABBIT_QUEUE_NAME must be defined!');
    }

    public static function getBinaryPath(): string
    {
        $customVal = getenv('RC_BINARY_PATH');
        if ($customVal && (string)$customVal !== '') {
            return (string)$customVal;
        }
        return self::DEFAULT_BINARY_PATH;
    }

    public static function getCommand(): string
    {
        $customVal = getenv('RC_COMMAND');
        if ($customVal !== false) {
            return (string)$customVal;
        }
        return self::DEFAULT_COMMAND;
    }

    public static function getBase64(): bool
    {
        $customVal = getenv('RC_BASE64');
        if ($customVal !== false) {
            return (bool)$customVal;
        }
        return self::DEFAULT_BASE64;
    }
}
