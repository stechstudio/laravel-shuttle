<?php

namespace STS\Shuttle;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use STS\Shuttle\Commands\ShuttleCommand;

class ShuttleServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-shuttle')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel-shuttle_table')
            ->hasCommand(ShuttleCommand::class);
    }
}
