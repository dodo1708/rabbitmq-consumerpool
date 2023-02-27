<?php

require_once __DIR__ . '/vendor/autoload.php';

function init_app()
{
    $containerBuilder = new \DI\ContainerBuilder();

    $container = $containerBuilder->build();
    \Slim\Factory\AppFactory::setContainer($container);

    $app = \Slim\Factory\AppFactory::create();
    \App\Application\Application::getInstance()->setApp($app);
}

function init_console_app(): \Symfony\Component\Console\Application
{
    $container = \App\Application\Application::getInstance()->getApp()->getContainer();
    if (!$container) {
        throw new \RuntimeException('Init app first.');
    }

    $application = new \Symfony\Component\Console\Application();
    $application->add($container->make(\App\Command\WorkerControllerCommand::class, ['name' => \App\Command\WorkerControllerCommand::getDefaultName()]));
    $application->add($container->make(\App\Command\SendMessagesCommand::class, ['name' => \App\Command\SendMessagesCommand::getDefaultName()]));
    return $application;
}

init_app();
$application = init_console_app();
$application->run();
