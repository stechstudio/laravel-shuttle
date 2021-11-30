<?php

namespace STS\Shuttle;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ShuttleServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-shuttle')
            ->hasConfigFile()
            ->hasViews();
    }
}
