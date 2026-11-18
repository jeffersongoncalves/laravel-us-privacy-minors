<?php

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\UsPrivacy\Facades\UsPrivacy;
use JeffersonGoncalves\UsPrivacyMinors\Enums\AssuranceMethod;
use JeffersonGoncalves\UsPrivacyMinors\Facades\UsPrivacyMinors;
use JeffersonGoncalves\UsPrivacyMinors\Tests\Fixtures\Person;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-17'));
    $this->person = Person::create(['name' => 'Emma']);
});

it('allows targeted ads and sale for unknown ages by default, unless configured otherwise', function () {
    expect(UsPrivacyMinors::age($this->person))->toBeNull()
        ->and(UsPrivacyMinors::allows($this->person, UsPrivacy::state('CO')))->toBeTrue();

    config()->set('us-privacy-minors.treat_unknown_as_minor', true);

    expect(UsPrivacyMinors::allows($this->person, UsPrivacy::state('CO')))->toBeFalse()
        ->and(UsPrivacyMinors::needsParent($this->person))->toBeTrue();
});

it('follows each state\'s minor age', function () {
    UsPrivacyMinors::assure($this->person, 16, AssuranceMethod::StoreSignal);

    expect(UsPrivacyMinors::allows($this->person, UsPrivacy::state('VA')))->toBeTrue()   // under 13 only
        ->and(UsPrivacyMinors::allows($this->person, UsPrivacy::state('OR')))->toBeTrue()  // under 16
        ->and(UsPrivacyMinors::allows($this->person, UsPrivacy::state('NJ')))->toBeFalse() // under 17
        ->and(UsPrivacyMinors::allows($this->person, UsPrivacy::state('CT')))->toBeFalse() // under 18
        ->and(UsPrivacyMinors::allows($this->person, null))->toBeTrue();                   // no state law: COPPA only
});

it('lets a minor consent, and the consent be withdrawn', function () {
    UsPrivacyMinors::assure($this->person, 15, AssuranceMethod::StoreSignal);
    $co = UsPrivacy::state('CO');

    expect(UsPrivacyMinors::allows($this->person, $co))->toBeFalse()
        ->and(UsPrivacyMinors::withoutOptIn($co)->pluck('id')->all())->toBe([$this->person->id]);

    UsPrivacyMinors::optIn($this->person);

    expect(UsPrivacyMinors::allows($this->person, $co))->toBeTrue()
        ->and(UsPrivacyMinors::withoutOptIn($co))->toBeEmpty();

    UsPrivacyMinors::revokeOptIn($this->person);

    expect(UsPrivacyMinors::allows($this->person, $co))->toBeFalse();
});

it('prohibits targeted ads and sale under 18 in Maryland, consent or not', function () {
    UsPrivacyMinors::assure($this->person, 17, AssuranceMethod::Document);
    UsPrivacyMinors::optIn($this->person);

    expect(UsPrivacyMinors::allows($this->person, UsPrivacy::state('MD')))->toBeFalse()
        ->and(UsPrivacyMinors::allows($this->person, UsPrivacy::state('CO')))->toBeTrue();
});

it('needs a verified parent under 13', function () {
    $mother = Person::create(['name' => 'Anna']);
    UsPrivacyMinors::assure($this->person, 10, AssuranceMethod::Document);

    expect(fn () => UsPrivacyMinors::optIn($this->person))->toThrow(LogicException::class, 'under 13');

    $link = UsPrivacyMinors::link($this->person, $mother, 'mother');
    expect(fn () => UsPrivacyMinors::optIn($this->person, $link))->toThrow(LogicException::class);

    $link->verify();
    UsPrivacyMinors::optIn($this->person, $link);

    expect(UsPrivacyMinors::allows($this->person, UsPrivacy::state('VA')))->toBeTrue()
        ->and(UsPrivacyMinors::guardians($this->person)->pluck('name')->all())->toBe(['Anna']);
});

it('ignores a self consent once the consumer turns out to be under 13', function () {
    UsPrivacyMinors::assure($this->person, 14, AssuranceMethod::SelfDeclaration);
    UsPrivacyMinors::optIn($this->person);

    UsPrivacyMinors::assure($this->person, 11, AssuranceMethod::Document);

    expect(UsPrivacyMinors::hasOptedIn($this->person))->toBeFalse();
});

it('prefers reliable results', function () {
    UsPrivacyMinors::assure($this->person, 30, AssuranceMethod::SelfDeclaration);
    expect(UsPrivacyMinors::allows($this->person, UsPrivacy::state('CT')))->toBeTrue();

    UsPrivacyMinors::assure($this->person, 15, AssuranceMethod::FacialEstimation);
    expect(UsPrivacyMinors::age($this->person))->toBe(15)
        ->and(UsPrivacyMinors::allows($this->person, UsPrivacy::state('CT')))->toBeFalse();
});

it('gates adult routes and ad tags', function () {
    Route::get('/adult', fn () => 'ok')->middleware('us-privacy.adult');
    $view = '@usPrivacyMinorAllowed pixel @else none @endusPrivacyMinorAllowed';

    UsPrivacyMinors::assure($this->person, 14, AssuranceMethod::Document);
    $this->actingAs($this->person)->get('/adult')->assertForbidden();
    expect(trim(Blade::render($view)))->toBe('none'); // unknown state: strictest rules

    $adult = Person::create(['name' => 'Anna']);
    UsPrivacyMinors::assure($adult, 40, AssuranceMethod::Document);
    $this->actingAs($adult)->get('/adult')->assertOk();
    expect(trim(Blade::render($view)))->toBe('pixel');
});

it('reports minors without consent', function () {
    UsPrivacyMinors::assure($this->person, 16, AssuranceMethod::Guardian);

    $this->artisan('us-privacy:minors --state=CT')->expectsOutputToContain('1 minor(s) without consent under Connecticut.')->assertSuccessful();
    $this->artisan('us-privacy:minors --state=VA')->expectsOutputToContain('0 minor(s) without consent under Virginia.')->assertSuccessful();
});
