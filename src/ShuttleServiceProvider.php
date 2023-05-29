<?php

declare(strict_types=1);

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
            ->hasAssets()
            ->hasMigration('create_uploads_table')
            ->hasTranslations();
    }

    public function registeringPackage(): void
    {
        $this->app->singleton(ShuttleManager::class);

        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'shuttle');
    }
}
