<?php

declare(strict_types=1);

namespace App\Application;

use Slim\App;

class Application
{
    private static ?Application $instance = null;

    private App $app;

    private function __construct()
    {
    }

    public static function getInstance(): Application
    {
        if (static::$instance === null) {
            static::$instance = new Application();
        }
        return static::$instance;
    }

    public function getApp(): App
    {
        return $this->app;
    }

    public function setApp(App $app): void
    {
        $this->app = $app;
    }
}
