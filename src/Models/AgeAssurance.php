<?php

namespace JeffersonGoncalves\UsPrivacyMinors\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\UsPrivacyMinors\Enums\AssuranceMethod;

/**
 * The result of one age check: the age at the time of the check, never the document,
 * selfie or birth date used to reach it (data minimization).
 *
 * @property string $subject_type
 * @property int|string $subject_id
 * @property int $age at assured_at
 * @property AssuranceMethod $method
 * @property string|null $provider
 * @property Carbon $assured_at
 * @property Carbon|null $expires_at
 * @property-read Model|null $subject
 */
class AgeAssurance extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config('us-privacy-minors.tables.assurances', 'us_privacy_age_assurances');
    }

    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'method' => AssuranceMethod::class,
            'assured_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** The age now: the checked age plus the whole years since the check (a lower bound). */
    public function currentAge(): int
    {
        return $this->age + (int) $this->assured_at->diffInYears(now());
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeValid(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
