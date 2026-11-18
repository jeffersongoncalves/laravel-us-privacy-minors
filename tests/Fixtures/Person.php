<?php

namespace JeffersonGoncalves\UsPrivacyMinors\Tests\Fixtures;

use Illuminate\Foundation\Auth\User;

class Person extends User
{
    protected $table = 'people';

    protected $guarded = [];

    public $timestamps = false;
}
