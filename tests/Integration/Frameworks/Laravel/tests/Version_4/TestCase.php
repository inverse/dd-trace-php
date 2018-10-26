<?php

namespace Tests\Version_4;

abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase
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
        $bootstrapScript = getenv('BOOTSTRAP_SCRIPT');

        $unitTesting = true;
        $testEnvironment = 'testing';
        return require __DIR__.'/../../' . $versionUnderTest . '/' . $bootstrapScript;
    }
}
