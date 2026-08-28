<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationSwitchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_single_org_user_resolves_their_only_membership_with_no_preference_set()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['name' => 'Karang Taruna Melati']);
        $organization->memberships()->create(['user_id' => $user->id, 'role' => OrganizationRole::Ketua]);

        $this->assertNull($user->active_organization_id);
        $this->assertSame($organization->id, $user->currentMembership()->organization_id);
    }

    public function test_a_user_can_switch_between_organizations_they_belong_to()
    {
        $user = User::factory()->create();
        $orgA = Organization::factory()->create(['name' => 'Karang Taruna Melati']);
        $orgB = Organization::factory()->create(['name' => 'Pemuda Kampung Sejahtera']);
        $orgA->memberships()->create(['user_id' => $user->id, 'role' => OrganizationRole::Ketua]);
        $orgB->memberships()->create(['user_id' => $user->id, 'role' => OrganizationRole::Anggota]);

        // Defaults to the first membership (orgA) with no preference set.
        $this->assertSame($orgA->id, $user->fresh()->currentMembership()->organization_id);

        $this->actingAs($user)
            ->post('/organizations/switch', ['organization_id' => $orgB->id])
            ->assertRedirect();

        $this->assertSame($orgB->id, $user->fresh()->currentMembership()->organization_id);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page->where('currentOrganization.name', 'Pemuda Kampung Sejahtera'));
    }

    public function test_switching_to_an_organization_the_user_does_not_belong_to_is_rejected()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => OrganizationRole::Anggota]);

        $otherOrganization = Organization::factory()->create();

        $this->actingAs($user)
            ->post('/organizations/switch', ['organization_id' => $otherOrganization->id])
            ->assertSessionHasErrors('organization_id');

        $this->assertNull($user->fresh()->active_organization_id);
    }

    public function test_the_organizations_list_shows_every_membership_with_its_role()
    {
        $user = User::factory()->create();
        $orgA = Organization::factory()->create(['name' => 'Karang Taruna Melati']);
        $orgB = Organization::factory()->create(['name' => 'Pemuda Kampung Sejahtera']);
        $orgA->memberships()->create(['user_id' => $user->id, 'role' => OrganizationRole::Ketua]);
        $orgB->memberships()->create(['user_id' => $user->id, 'role' => OrganizationRole::Anggota]);

        $response = $this->actingAs($user)->getJson('/organizations/mine');

        $response->assertOk();
        $organizations = $response->json('organizations');
        $this->assertCount(2, $organizations);
        $this->assertSame('Karang Taruna Melati', $organizations[0]['name']);
        $this->assertSame('Ketua', $organizations[0]['roleLabel']);
    }

    public function test_a_stale_active_organization_falls_back_gracefully_once_membership_is_gone()
    {
        $user = User::factory()->create();
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $orgA->memberships()->create(['user_id' => $user->id, 'role' => OrganizationRole::Ketua]);
        $membershipB = $orgB->memberships()->create(['user_id' => $user->id, 'role' => OrganizationRole::Anggota]);

        $user->forceFill(['active_organization_id' => $orgB->id])->save();
        $membershipB->delete();

        $this->assertSame($orgA->id, $user->fresh()->currentMembership()->organization_id);
    }
}
