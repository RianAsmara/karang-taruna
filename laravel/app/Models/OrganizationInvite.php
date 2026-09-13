<?php

namespace App\Models;

use Database\Factories\OrganizationInviteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A shareable join link. See the migration docblock for why accepting one
 * joins immediately instead of queuing an approval.
 *
 * @property Carbon $expires_at
 * @property Carbon|null $revoked_at
 */
class OrganizationInvite extends Model
{
    /** @use HasFactory<OrganizationInviteFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'token',
        'created_by',
        'expires_at',
        'max_uses',
        'uses',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'max_uses' => 'integer',
            'uses' => 'integer',
        ];
    }

    /**
     * 40 hex characters from a cryptographic source — this token is the only
     * thing standing between a stranger and the organization, so it must not
     * be guessable or derived from anything about the organization.
     */
    public static function generateToken(): string
    {
        return Str::random(40);
    }

    public function isActive(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        if ($this->expires_at->isPast()) {
            return false;
        }

        return $this->max_uses === null || $this->uses < $this->max_uses;
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
