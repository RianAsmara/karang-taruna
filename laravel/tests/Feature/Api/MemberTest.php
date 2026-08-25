<?php

namespace Tests\Feature\Api;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MemberTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_a_member_can_list_their_organizations_members()
    {
        $organization = Organization::factory()->create();
        $owner = $this->memberWithRole($organization, OrganizationRole::Owner);
        $this->memberWithRole($organization, OrganizationRole::Member);

        Sanctum::actingAs($owner);

        $this->getJson('/api/v1/members')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_only_an_organizer_can_add_a_member()
    {
        $organization = Organization::factory()->create();
        $plainMember = $this->memberWithRole($organization, OrganizationRole::Member);
        $newUser = User::factory()->create();

        Sanctum::actingAs($plainMember);

        $this->postJson('/api/v1/members', [
            'email' => $newUser->email,
            'role' => 'MEMBER',
        ])->assertForbidden();
    }

    public function test_an_owner_can_add_an_existing_user_as_a_member()
    {
        $organization = Organization::factory()->create();
        $owner = $this->memberWithRole($organization, OrganizationRole::Owner);
        $newUser = User::factory()->create();

        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/members', [
            'email' => $newUser->email,
            'role' => 'COMMITTEE',
        ])->assertCreated()->assertJsonPath('data.role', 'COMMITTEE');

        $this->assertDatabaseHas('organization_memberships', [
            'organization_id' => $organization->id,
            'user_id' => $newUser->id,
            'role' => 'COMMITTEE',
        ]);
    }

    public function test_a_member_from_another_organization_cannot_view_this_organizations_member()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Member);
        $membership = $organization->memberships()->where('user_id', $member->id)->firstOrFail();

        $otherOrganization = Organization::factory()->create();
        $outsider = $this->memberWithRole($otherOrganization, OrganizationRole::Owner);

        Sanctum::actingAs($outsider);

        $this->getJson("/api/v1/members/{$membership->id}")
            ->assertForbidden();
    }
}
