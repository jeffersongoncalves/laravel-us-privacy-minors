<?php

namespace JeffersonGoncalves\UsPrivacyMinors;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class UsPrivacyMinorsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-us-privacy-minors')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations();
    }
}
