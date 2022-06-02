<?php

namespace Knutle\ShellExec\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Knutle\ShellExec\ShellExecServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ShellExecServiceProvider::class,
        ];
    }
}
