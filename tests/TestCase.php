<?php

namespace Knutle\ShellExec\Tests;

use Knutle\ShellExec\ShellExecServiceProvider;
use Knutle\TestStubs\InteractsWithStubs;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use InteractsWithStubs;

    protected function getPackageProviders($app): array
    {
        return [
            ShellExecServiceProvider::class,
        ];
    }
}
