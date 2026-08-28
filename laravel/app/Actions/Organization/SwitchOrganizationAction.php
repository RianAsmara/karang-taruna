<?php

namespace App\Actions\Organization;

use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SwitchOrganizationAction
{
    /**
     * Never trust a client-supplied organization id at face value — this
     * only ever succeeds when the acting user genuinely holds a
     * membership there.
     */
    public function handle(User $user, string $organizationId): OrganizationMembership
    {
        $membership = $user->memberships()->with('organization')->where('organization_id', $organizationId)->first();

        if ($membership === null) {
            throw ValidationException::withMessages([
                'organization_id' => 'Anda bukan anggota organisasi tersebut.',
            ]);
        }

        $user->forceFill(['active_organization_id' => $organizationId])->save();

        return $membership;
    }

    /**
     * @return array<int, array{id: string, name: string, role: string, roleLabel: string}>
     */
    public function options(User $user): array
    {
        return $user->memberships()
            ->with('organization')
            ->get()
            ->map(fn (OrganizationMembership $membership) => [
                'id' => $membership->organization->id,
                'name' => $membership->organization->name,
                'role' => $membership->role->value,
                'roleLabel' => $membership->role->label(),
            ])
            ->all();
    }
}
