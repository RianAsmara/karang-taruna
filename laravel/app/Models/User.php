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
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUlids, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'show_phone_to_members',
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
            'show_phone_to_members' => 'boolean',
            'is_superadmin' => 'boolean',
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
    /**
     * The user's `active_organization_id` preference, when they still
     * hold a membership there (switching orgs then leaving one never
     * silently strands the pointer) — otherwise falls back to their
     * first membership. Unchanged behavior for every single-org user,
     * the common case; only SwitchOrganizationAction ever sets the
     * preference, always after verifying real membership first.
     */
    public function currentMembership(): ?OrganizationMembership
    {
        if ($this->active_organization_id !== null) {
            $active = $this->memberships()->with('organization')->where('organization_id', $this->active_organization_id)->first();

            if ($active !== null) {
                return $active;
            }
        }

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
     * KETUA only — the sole chair, one per organization. Manages
     * organization structure (members, roles, events, announcements) and
     * is the only role that inherits every other role's abilities.
     */
    public function isChairOf(Organization $organization): bool
    {
        return $this->roleIn($organization) === OrganizationRole::Ketua;
    }

    /**
     * KETUA/BENDAHARA — the roles that may create and manage day-to-day
     * financial records (accounts, categories, transactions, dues). The
     * chair inherits treasurer abilities so a small organization where one
     * person holds both isn't blocked — see mobile-ux.md's report-approval
     * edge case. Approval of a PENDING transaction is still restricted to
     * isChairOf() — see FinancialTransactionPolicy.
     */
    public function isTreasurerOf(Organization $organization): bool
    {
        return in_array($this->roleIn($organization), [
            OrganizationRole::Ketua,
            OrganizationRole::Bendahara,
        ], true);
    }

    /**
     * KETUA/SEKRETARIS — documents, announcements, member invitations.
     */
    public function isSecretaryOf(Organization $organization): bool
    {
        return in_array($this->roleIn($organization), [
            OrganizationRole::Ketua,
            OrganizationRole::Sekretaris,
        ], true);
    }

    /**
     * Any role except ANGGOTA — "Pengurus" is the collective UI word for
     * anyone holding a management role (mobile-design-system.md § Roles).
     * Used where a feature is gated to "any organizer" without being
     * specific to treasury or secretarial work (inventory, sponsors).
     */
    public function isPengurusOf(Organization $organization): bool
    {
        $role = $this->roleIn($organization);

        return $role !== null && $role !== OrganizationRole::Anggota;
    }

    /**
     * This user's phone number, as visible to $viewer within $organization —
     * always visible to pengurus, opt-in only for an ordinary member
     * viewing another ordinary member. See mobile-ux.md § Open product
     * decisions ("the design assumes opt-in").
     */
    public function phoneVisibleTo(User $viewer, Organization $organization): ?string
    {
        if ($this->phone === null) {
            return null;
        }

        if ($viewer->id === $this->id || $viewer->isPengurusOf($organization) || $this->show_phone_to_members) {
            return $this->phone;
        }

        return null;
    }
}
