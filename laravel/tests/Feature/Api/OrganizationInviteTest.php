<?php

namespace Tests\Feature\Api;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganizationInviteTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_the_chair_can_create_and_list_invite_links()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);

        Sanctum::actingAs($chair);

        $this->postJson('/api/v1/organizations/invites', [])
            ->assertCreated()
            ->assertJsonPath('data.isActive', true)
            ->assertJsonPath('data.uses', 0)
            // The client gets a ready-to-share URL, never a raw token to assemble.
            ->assertJsonStructure(['data' => ['id', 'url', 'expiresAt', 'isActive']]);

        $this->getJson('/api/v1/organizations/invites')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_a_non_chair_cannot_create_or_list_invite_links()
    {
        $organization = Organization::factory()->create();
        $this->memberWithRole($organization, OrganizationRole::Ketua);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);

        Sanctum::actingAs($treasurer);

        $this->postJson('/api/v1/organizations/invites', [])->assertForbidden();
        $this->getJson('/api/v1/organizations/invites')->assertForbidden();
    }

    public function test_the_chair_can_revoke_an_invite()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $invite = OrganizationInvite::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $chair->id,
        ]);

        Sanctum::actingAs($chair);

        $this->deleteJson("/api/v1/organizations/invites/{$invite->id}")->assertNoContent();

        $this->assertFalse($invite->fresh()->isActive());
    }

    public function test_a_chair_of_another_organization_cannot_revoke_or_see_this_ones_invites()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $invite = OrganizationInvite::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $chair->id,
        ]);

        $otherOrganization = Organization::factory()->create();
        $outsideChair = $this->memberWithRole($otherOrganization, OrganizationRole::Ketua);

        Sanctum::actingAs($outsideChair);

        $this->deleteJson("/api/v1/organizations/invites/{$invite->id}")->assertForbidden();
        // And the other org's list must not contain it.
        $this->getJson('/api/v1/organizations/invites')->assertOk()->assertJsonCount(0, 'data');

        $this->assertTrue($invite->fresh()->isActive());
    }
}
