<?php

namespace Knutle\ShellExec\Tests;

use Knutle\ShellExec\ShellExecServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ShellExecServiceProvider::class,
        ];
    }
}
