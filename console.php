<?php

use App\Container\ContainerRef;

require_once __DIR__ . '/vendor/autoload.php';

function init_app()
{
    $containerBuilder = new \DI\ContainerBuilder();

    $container = $containerBuilder->build();
    ContainerRef::getInstance()->setContainer($container);
}

function init_console_app(): \Symfony\Component\Console\Application
{
    $container = ContainerRef::getInstance()->getContainer();
    if (!$container) {
        throw new \RuntimeException('Init container first.');
    }

    $application = new \Symfony\Component\Console\Application();
    $application->add($container->make(\App\Command\WorkerControllerCommand::class, ['name' => \App\Command\WorkerControllerCommand::getDefaultName()]));
    return $application;
}

init_app();
$application = init_console_app();
$application->run();
