<?php

namespace Knutle\ShellExec;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Knutle\ShellExec\Commands\ShellExecCommand;

class ShellExecServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('shell-exec')
            ->hasConfigFile()
            ->hasCommand(ShellExecCommand::class);
    }
}
