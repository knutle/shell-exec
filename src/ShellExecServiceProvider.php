<?php

namespace Knutle\ShellExec;

use Knutle\ShellExec\Commands\ShellExecCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
