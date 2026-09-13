<?php

namespace App\Actions\Organization;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddMemberAction
{
    /**
     * Add an already-registered user to an organization by email.
     *
     * @throws ValidationException if no such user exists, or they are
     *                             already a member — kept as validation errors (not a 404/500)
     *                             since both are simply invalid input from the requester.
     */
    public function handle(Organization $organization, string $email, OrganizationRole $role): OrganizationMembership
    {
        return DB::transaction(function () use ($organization, $email, $role) {
            $user = User::firstWhere('email', $email);

            if ($user === null) {
                throw ValidationException::withMessages([
                    'email' => 'Tidak ada pengguna terdaftar dengan email ini. Minta mereka mendaftar terlebih dahulu.',
                ]);
            }

            if ($organization->memberships()->where('user_id', $user->id)->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'Pengguna ini sudah menjadi anggota organisasi.',
                ]);
            }

            return $this->forUser($organization, $user, $role);
        });
    }

    /**
     * Attach an already-resolved user. Shared with the invite-acceptance path
     * (AcceptInviteAction) so the soft-delete handling below lives in exactly
     * one place — it is subtle enough that a second copy would drift.
     */
    public function forUser(Organization $organization, User $user, OrganizationRole $role): OrganizationMembership
    {
        return DB::transaction(function () use ($organization, $user, $role) {
            // A member who left within the last 30 days still has a
            // soft-deleted row here (the "Keluar" retention window —
            // see the organization_memberships migration). Restore it
            // rather than insert a new one: the table's
            // unique(organization_id, user_id) constraint doesn't know
            // about soft deletes, so a plain create() would collide.
            $trashed = $organization->memberships()
                ->onlyTrashed()
                ->where('user_id', $user->id)
                ->first();

            if ($trashed !== null) {
                $trashed->restore();
                $trashed->update(['role' => $role]);

                return $trashed;
            }

            return $organization->memberships()->create([
                'user_id' => $user->id,
                'role' => $role,
            ]);
        });
    }
}
