<?php

declare(strict_types=1);

namespace App\Utility;

use App\Command\WorkerSettings;

class CommandBuilderUtility
{
    public static function buildCommand(string $arg): string
    {
        $bin = WorkerSettings::getBinaryPath();
        $cmd = WorkerSettings::getCommand();
        if (WorkerSettings::getBase64()) {
            $arg = base64_encode($arg);
        }
        return sprintf('%s %s %s', $bin, $cmd, $arg);
    }
}
