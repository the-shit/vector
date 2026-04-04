<?php

declare(strict_types=1);

namespace TheShit\Vector\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use TheShit\Vector\VectorServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [VectorServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('vector.url', 'http://localhost:6333');
        $app['config']->set('vector.api_key', 'test-key');
    }
}
