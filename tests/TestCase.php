<?php

namespace Knutle\ShellExec\Tests;

use Knutle\ShellExec\ShellExecServiceProvider;
use Knutle\TestStubs\InteractsWithStubs;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Snapshots\MatchesSnapshots;

class TestCase extends Orchestra
{
    use InteractsWithStubs;
    use MatchesSnapshots;

    protected function getPackageProviders($app): array
    {
        return [
            ShellExecServiceProvider::class,
        ];
    }
}
