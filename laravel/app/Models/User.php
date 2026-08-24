<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\OrganizationRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUlids, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return HasMany<OrganizationMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }

    /**
     * The user's organization, until multi-organization switching exists.
     */
    public function currentMembership(): ?OrganizationMembership
    {
        return $this->memberships()->with('organization')->first();
    }

    /**
     * The user's role within the given organization, or null if they are
     * not a member. Used throughout Policies instead of duplicating this
     * lookup — never trust a role passed in from the client.
     */
    public function roleIn(Organization $organization): ?OrganizationRole
    {
        return $this->membershipIn($organization)?->role;
    }

    /**
     * The user's membership record within the given organization, or null.
     */
    public function membershipIn(Organization $organization): ?OrganizationMembership
    {
        return $this->memberships->firstWhere('organization_id', $organization->id);
    }

    /**
     * OWNER/ADMIN — the roles that manage organization structure (members,
     * events, announcements) rather than day-to-day bookkeeping.
     */
    public function isOrganizerOf(Organization $organization): bool
    {
        return in_array($this->roleIn($organization), [
            OrganizationRole::Owner,
            OrganizationRole::Admin,
        ], true);
    }

    /**
     * OWNER/ADMIN/TREASURER — the roles that may create and manage
     * day-to-day financial records (accounts, categories, transactions,
     * dues). Approval of a PENDING transaction is still restricted to
     * isOrganizerOf() — see FinancialTransactionPolicy.
     */
    public function isTreasurerOf(Organization $organization): bool
    {
        return in_array($this->roleIn($organization), [
            OrganizationRole::Owner,
            OrganizationRole::Admin,
            OrganizationRole::Treasurer,
        ], true);
    }
}
