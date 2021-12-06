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
            ->hasViews()
            ->hasAssets();
    }

    public function registeringPackage(): void
    {
        // TODO: Does this need to be `scoped` to support Octane?
        $this->app->singleton(ShuttleManager::class);
    }
}
