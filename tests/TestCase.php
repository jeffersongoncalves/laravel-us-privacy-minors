<?php

namespace JeffersonGoncalves\UsPrivacyMinors\Tests;

use JeffersonGoncalves\UsPrivacyMinors\UsPrivacyMinorsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            UsPrivacyMinorsServiceProvider::class,
        ];
    }
}
