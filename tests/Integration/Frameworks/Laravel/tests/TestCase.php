<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        // This is set in phpunit.xml
        $versionUnderTest = getenv('LARAVEL_VERSION');
        $app = require __DIR__.'/../' . $versionUnderTest . '/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
