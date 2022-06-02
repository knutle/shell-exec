<?php

namespace Knutle\ShellExec\Tests;

use Knutle\ShellExec\ShellExecServiceProvider;
use Knutle\TestStubs\InteractsWithStubs;
use Orchestra\Testbench\TestCase as Orchestra;
use Pest\TestSuite;
use ReflectionClass;
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

    protected function getSnapshotDirectory(): string
    {
        return implode(DIRECTORY_SEPARATOR, [
            TestSuite::getInstance()->rootPath,
            'tests',
            '__snapshots__',
        ]);
    }

    protected function getSnapshotId(): string
    {
        return (new ReflectionClass($this))->getShortName().'__'.
            str_replace(' ', '_', $this->getName()).'__'.
            $this->snapshotIncrementor;
    }
}
