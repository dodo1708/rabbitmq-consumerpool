<?php

declare(strict_types=1);

namespace App\Container;

use DI\Container;

class ContainerRef
{
    private static ?ContainerRef $instance = null;

    private Container $container;

    private function __construct()
    {
    }

    public static function getInstance(): ContainerRef
    {
        if (static::$instance === null) {
            static::$instance = new ContainerRef();
        }
        return static::$instance;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }
}
