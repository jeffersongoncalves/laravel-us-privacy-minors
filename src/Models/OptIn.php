<?php

namespace JeffersonGoncalves\UsPrivacyMinors\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Consent to targeted advertising and sale of the personal data of a known minor (under the
 * state's minor age): from the minor at 13 or over, from a parent or guardian under 13.
 *
 * @property string $subject_type
 * @property int|string $subject_id
 * @property int|null $guardian_link_id
 * @property Carbon $opted_in_at
 * @property Carbon|null $revoked_at
 * @property-read Model|null $subject
 * @property-read GuardianLink|null $guardianLink
 */
class OptIn extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config('us-privacy-minors.tables.opt_ins', 'us_privacy_minor_opt_ins');
    }

    protected function casts(): array
    {
        return [
            'opted_in_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<GuardianLink, $this>
     */
    public function guardianLink(): BelongsTo
    {
        return $this->belongsTo(GuardianLink::class);
    }

    public function byParent(): bool
    {
        return $this->guardian_link_id !== null;
    }
}
