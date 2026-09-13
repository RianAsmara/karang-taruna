<?php

namespace App\Actions\Invite;

use App\Actions\Organization\AddMemberAction;
use App\Enums\OrganizationRole;
use App\Models\OrganizationInvite;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptOrganizationInviteAction
{
    public function __construct(
        private readonly AddMemberAction $addMember,
    ) {}

    /**
     * Join the organization behind an invite link.
     *
     * An invite always grants ANGGOTA and never anything higher — a link that
     * could confer BENDAHARA or KETUA would turn a forwarded WhatsApp message
     * into a privilege escalation. The chair promotes afterwards, deliberately,
     * through the member list.
     *
     * @throws ValidationException when the link is revoked, expired or used up
     */
    public function handle(OrganizationInvite $invite, User $user): OrganizationMembership
    {
        return DB::transaction(function () use ($invite, $user) {
            // Re-read inside the transaction and lock it: two people tapping
            // the same single-use link at once must not both get through.
            $locked = OrganizationInvite::whereKey($invite->id)->lockForUpdate()->first();

            if ($locked === null || ! $locked->isActive()) {
                throw ValidationException::withMessages([
                    'token' => 'Tautan undangan ini sudah tidak berlaku. Minta tautan baru dari ketua.',
                ]);
            }

            $existing = $user->memberships()->where('organization_id', $locked->organization_id)->first();

            // Already a member: joining again must never re-grade an existing
            // role down to ANGGOTA, and must not burn a use.
            if ($existing !== null) {
                $this->setActive($user, $locked->organization_id);

                return $existing;
            }

            $membership = $this->addMember->forUser($locked->organization, $user, OrganizationRole::Anggota);

            $locked->increment('uses');

            $this->setActive($user, $locked->organization_id);

            return $membership;
        });
    }

    private function setActive(User $user, string $organizationId): void
    {
        $user->forceFill(['active_organization_id' => $organizationId])->save();
    }
}
