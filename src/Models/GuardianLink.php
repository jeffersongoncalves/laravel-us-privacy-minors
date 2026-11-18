<?php

namespace JeffersonGoncalves\UsPrivacyMinors\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Links a minor's account to a parent or guardian, who consents for consumers under 13 (COPPA).
 *
 * @property string $minor_type
 * @property int|string $minor_id
 * @property string $guardian_type
 * @property int|string $guardian_id
 * @property string|null $relationship
 * @property Carbon|null $verified_at
 * @property Carbon|null $revoked_at
 * @property-read Model|null $minor
 * @property-read Model|null $guardian
 */
class GuardianLink extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config('us-privacy-minors.tables.guardians', 'us_privacy_guardian_links');
    }

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function minor(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function guardian(): MorphTo
    {
        return $this->morphTo();
    }

    public function isActive(): bool
    {
        return $this->verified_at !== null && $this->revoked_at === null;
    }

    public function verify(): static
    {
        $this->forceFill(['verified_at' => now()])->save();

        return $this;
    }

    public function revoke(): static
    {
        $this->forceFill(['revoked_at' => now()])->save();

        return $this;
    }

    /**
     * Verified and not revoked.
     *
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNotNull('verified_at')->whereNull('revoked_at');
    }
}
