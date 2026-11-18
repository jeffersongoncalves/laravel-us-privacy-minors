<?php

namespace JeffersonGoncalves\UsPrivacyMinors\Facades;

use Illuminate\Support\Facades\Facade;
use JeffersonGoncalves\UsPrivacyMinors\MinorsManager;

/**
 * @method static \JeffersonGoncalves\UsPrivacyMinors\Models\AgeAssurance assure(\Illuminate\Database\Eloquent\Model $subject, int $age, \JeffersonGoncalves\UsPrivacyMinors\Enums\AssuranceMethod $method, ?string $provider = null)
 * @method static int|null age(?\Illuminate\Database\Eloquent\Model $subject)
 * @method static bool isVerifiedAdult(?\Illuminate\Database\Eloquent\Model $subject)
 * @method static bool isMinorUnder(?\Illuminate\Database\Eloquent\Model $subject, ?\JeffersonGoncalves\UsPrivacy\StateRules $rules)
 * @method static bool needsParent(?\Illuminate\Database\Eloquent\Model $subject)
 * @method static \JeffersonGoncalves\UsPrivacyMinors\Models\OptIn optIn(\Illuminate\Database\Eloquent\Model $subject, ?\JeffersonGoncalves\UsPrivacyMinors\Models\GuardianLink $byParent = null)
 * @method static void revokeOptIn(\Illuminate\Database\Eloquent\Model $subject)
 * @method static bool hasOptedIn(\Illuminate\Database\Eloquent\Model $subject)
 * @method static bool allows(?\Illuminate\Database\Eloquent\Model $subject, ?\JeffersonGoncalves\UsPrivacy\StateRules $rules)
 * @method static bool hasGuardian(\Illuminate\Database\Eloquent\Model $minor)
 * @method static \JeffersonGoncalves\UsPrivacyMinors\Models\GuardianLink link(\Illuminate\Database\Eloquent\Model $minor, \Illuminate\Database\Eloquent\Model $guardian, ?string $relationship = null, bool $verified = false)
 * @method static \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model> guardians(\Illuminate\Database\Eloquent\Model $minor)
 * @method static \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model> withoutOptIn(?\JeffersonGoncalves\UsPrivacy\StateRules $rules)
 *
 * @see MinorsManager
 */
class UsPrivacyMinors extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MinorsManager::class;
    }
}
