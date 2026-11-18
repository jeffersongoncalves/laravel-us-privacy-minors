<?php

namespace JeffersonGoncalves\UsPrivacyMinors;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use JeffersonGoncalves\UsPrivacy\StateRules;
use JeffersonGoncalves\UsPrivacyMinors\Enums\AssuranceMethod;
use JeffersonGoncalves\UsPrivacyMinors\Models\AgeAssurance;
use JeffersonGoncalves\UsPrivacyMinors\Models\GuardianLink;
use JeffersonGoncalves\UsPrivacyMinors\Models\OptIn;
use LogicException;

class MinorsManager
{
    /**
     * Record the result of an age check. Pass the age, never the document.
     */
    public function assure(Model $subject, int $age, AssuranceMethod $method, ?string $provider = null): AgeAssurance
    {
        if ($age < 0 || $age > 130) {
            throw new InvalidArgumentException("Invalid age [{$age}].");
        }

        $validFor = config('us-privacy-minors.valid_for.'.$method->value);

        return AgeAssurance::create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'age' => $age,
            'method' => $method,
            'provider' => $provider,
            'assured_at' => now(),
            'expires_at' => $validFor === null ? null : now()->add(CarbonInterval::make((string) $validFor)),
        ]);
    }

    /**
     * Current age, or null when unknown. Reliable results outweigh self-declaration; among the
     * results that count, the youngest wins.
     */
    public function age(?Model $subject): ?int
    {
        return $this->decide($subject)['age'];
    }

    public function isVerifiedAdult(?Model $subject): bool
    {
        $decision = $this->decide($subject);

        return $decision['reliable'] && $decision['age'] !== null && $decision['age'] >= 18;
    }

    /**
     * Known to be under the minor age of $rules (13, 16, 17 or 18), or unknown when
     * treat_unknown_as_minor is on. Without a state law ($rules null) only the COPPA age of 13 counts.
     */
    public function isMinorUnder(?Model $subject, ?StateRules $rules): bool
    {
        $age = $this->age($subject);
        $parentAge = (int) config('us-privacy-minors.parent_age', 13);

        return $age === null
            ? (bool) config('us-privacy-minors.treat_unknown_as_minor', false)
            : $age < max($rules === null ? $parentAge : $rules->minorAge, $parentAge);
    }

    /** Under 13: only a parent or guardian can consent (COPPA, and every state law). */
    public function needsParent(?Model $subject): bool
    {
        $age = $this->age($subject);

        return $age === null
            ? (bool) config('us-privacy-minors.treat_unknown_as_minor', false)
            : $age < (int) config('us-privacy-minors.parent_age', 13);
    }

    /**
     * Consent to targeted advertising and sale for a known minor. From 13 the minor consents;
     * under 13 pass the verified guardian link of the parent who authorizes it.
     */
    public function optIn(Model $subject, ?GuardianLink $byParent = null): OptIn
    {
        if ($this->needsParent($subject)) {
            if ($byParent === null || ! $byParent->isActive() || ! $this->linkBelongsTo($byParent, $subject)) {
                throw new LogicException('Consumers under 13 need the consent of a verified parent or guardian (COPPA).');
            }
        }

        return OptIn::create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'guardian_link_id' => $byParent?->getKey(),
            'opted_in_at' => now(),
        ]);
    }

    /** The minor, or the parent, withdraws the consent. */
    public function revokeOptIn(Model $subject): void
    {
        $this->optIns($subject)->whereNull('revoked_at')->update(['revoked_at' => now()]);
    }

    public function hasOptedIn(Model $subject): bool
    {
        $optIn = $this->optIns($subject)->whereNull('revoked_at')->latest('id')->first();

        if ($optIn === null) {
            return false;
        }

        // A consumer's own consent no longer counts if they turn out to be under 13.
        return ! $this->needsParent($subject) || $optIn->byParent();
    }

    /**
     * Whether you may use this consumer's data for targeted advertising or sell it, as far as their
     * age goes under $rules (their state's law): at or over the state's minor age, or under it with
     * consent; never under 18 in Maryland. Combine with laravel-us-privacy-opt-out.
     */
    public function allows(?Model $subject, ?StateRules $rules): bool
    {
        if ($subject === null) {
            return ! config('us-privacy-minors.treat_unknown_as_minor', false);
        }

        if (! $this->isMinorUnder($subject, $rules)) {
            return true;
        }

        return $rules?->minorRule !== 'prohibited' && $this->hasOptedIn($subject);
    }

    public function hasGuardian(Model $minor): bool
    {
        return $this->links($minor)->scopes('active')->exists();
    }

    /**
     * Link a minor to their parent or guardian. Verify it before relying on it.
     */
    public function link(Model $minor, Model $guardian, ?string $relationship = null, bool $verified = false): GuardianLink
    {
        return GuardianLink::create([
            'minor_type' => $minor->getMorphClass(),
            'minor_id' => $minor->getKey(),
            'guardian_type' => $guardian->getMorphClass(),
            'guardian_id' => $guardian->getKey(),
            'relationship' => $relationship,
            'verified_at' => $verified ? now() : null,
        ]);
    }

    /**
     * @return Collection<int, Model>
     */
    public function guardians(Model $minor): Collection
    {
        return $this->links($minor)->scopes('active')->with('guardian')->get()
            ->map(fn (GuardianLink $link): ?Model => $link->guardian)
            ->filter()
            ->values();
    }

    /**
     * Users known to be minors under $rules without a valid consent: no targeted advertising or sale.
     *
     * @return Collection<int, Model>
     */
    public function withoutOptIn(?StateRules $rules): Collection
    {
        // ponytail: loads every subject with a valid result; paginate by subject if this grows large
        return AgeAssurance::query()->scopes('valid')->with('subject')->get()
            ->map(fn (AgeAssurance $assurance): ?Model => $assurance->subject)
            ->filter()
            ->unique(fn (Model $subject): string => $subject->getMorphClass().'#'.$subject->getKey())
            ->filter(fn (Model $subject): bool => $this->isMinorUnder($subject, $rules) && ! $this->hasOptedIn($subject))
            ->values();
    }

    /**
     * @return array{age: int|null, reliable: bool}
     */
    protected function decide(?Model $subject): array
    {
        if ($subject === null) {
            return ['age' => null, 'reliable' => false];
        }

        $valid = AgeAssurance::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->scopes('valid')
            ->get();

        $reliable = $valid->filter(fn (AgeAssurance $a): bool => $a->method->isReliable());
        $counted = $reliable->isNotEmpty() ? $reliable : $valid;
        $age = $counted->map(fn (AgeAssurance $a): int => $a->currentAge())->min();

        return ['age' => $age === null ? null : (int) $age, 'reliable' => $reliable->isNotEmpty()];
    }

    protected function linkBelongsTo(GuardianLink $link, Model $minor): bool
    {
        return $link->minor_type === $minor->getMorphClass() && (string) $link->minor_id === (string) $minor->getKey();
    }

    /**
     * @return Builder<GuardianLink>
     */
    protected function links(Model $minor): Builder
    {
        return GuardianLink::query()
            ->where('minor_type', $minor->getMorphClass())
            ->where('minor_id', $minor->getKey());
    }

    /**
     * @return Builder<OptIn>
     */
    protected function optIns(Model $subject): Builder
    {
        return OptIn::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }
}
