<?php

namespace JeffersonGoncalves\UsPrivacyMinors\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JeffersonGoncalves\UsPrivacyMinors\UsPrivacyMinors
 */
class UsPrivacyMinors extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-us-privacy-minors';
    }
}
