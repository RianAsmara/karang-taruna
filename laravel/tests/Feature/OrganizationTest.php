<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_organization_makes_the_creator_its_owner()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/organizations', ['name' => 'Karang Taruna Melati'])
            ->assertRedirect('/dashboard');

        $organization = Organization::firstWhere('name', 'Karang Taruna Melati');

        $this->assertNotNull($organization);
        $this->assertSame('karang-taruna-melati', $organization->slug);

        $membership = $organization->memberships()->firstWhere('user_id', $user->id);

        $this->assertNotNull($membership);
        $this->assertSame(OrganizationRole::Owner, $membership->role);
    }

    public function test_organization_name_is_required()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/organizations', ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_duplicate_organization_names_get_a_unique_slug()
    {
        $owner = User::factory()->create();
        Organization::factory()->create(['name' => 'Karang Taruna Melati', 'slug' => 'karang-taruna-melati']);

        $this->actingAs($owner)
            ->post('/organizations', ['name' => 'Karang Taruna Melati']);

        $this->assertDatabaseHas('organizations', [
            'name' => 'Karang Taruna Melati',
            'slug' => 'karang-taruna-melati-1',
        ]);
    }

    public function test_a_member_from_another_organization_cannot_manage_members()
    {
        $organization = Organization::factory()->create();
        $outsider = User::factory()->create();

        $this->assertFalse($outsider->can('manageMembers', $organization));
    }

    public function test_only_owner_and_admin_can_manage_members()
    {
        $organization = Organization::factory()->create();

        $owner = User::factory()->create();
        $organization->memberships()->create(['user_id' => $owner->id, 'role' => OrganizationRole::Owner]);

        $member = User::factory()->create();
        $organization->memberships()->create(['user_id' => $member->id, 'role' => OrganizationRole::Member]);

        $this->assertTrue($owner->can('manageMembers', $organization));
        $this->assertFalse($member->can('manageMembers', $organization));
    }

    public function test_dashboard_prompts_to_create_an_organization_when_user_has_none()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('currentOrganization', null));
    }

    public function test_dashboard_shows_the_users_organization_when_they_have_one()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['name' => 'Karang Taruna Melati']);
        $organization->memberships()->create(['user_id' => $user->id, 'role' => OrganizationRole::Owner]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('currentOrganization.name', 'Karang Taruna Melati')
                ->where('currentOrganization.role', 'OWNER')
            );
    }
}
