<?php

namespace JeffersonGoncalves\UsPrivacyMinors;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use JeffersonGoncalves\UsPrivacy\UsPrivacy;
use JeffersonGoncalves\UsPrivacyMinors\Commands\MinorsCommand;
use JeffersonGoncalves\UsPrivacyMinors\Http\Middleware\EnsureVerifiedAdult;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class UsPrivacyMinorsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('us-privacy-minors')
            ->hasConfigFile()
            ->hasMigrations(['create_us_privacy_minors_tables'])
            ->hasCommand(MinorsCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(MinorsManager::class);
    }

    public function packageBooted(): void
    {
        $this->app->make(Router::class)->aliasMiddleware('us-privacy.adult', EnsureVerifiedAdult::class);

        // @usPrivacyMinorAllowed ... @endusPrivacyMinorAllowed: targeted ads and sale, by the visitor's state.
        Blade::if('usPrivacyMinorAllowed', function (): bool {
            $user = auth()->user();

            return $this->app->make(MinorsManager::class)->allows(
                $user instanceof Model ? $user : null,
                $this->app->make(UsPrivacy::class)->rulesForRequest(request()),
            );
        });
    }
}
