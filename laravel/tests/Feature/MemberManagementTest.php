<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberManagementTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_admin_can_add_an_existing_user_as_a_member()
    {
        $organization = Organization::factory()->create();
        $admin = $this->memberWithRole($organization, OrganizationRole::Admin);
        $newUser = User::factory()->create();

        $this->actingAs($admin)
            ->post('/members', ['email' => $newUser->email, 'role' => OrganizationRole::Committee->value])
            ->assertRedirect();

        $this->assertDatabaseHas('organization_memberships', [
            'organization_id' => $organization->id,
            'user_id' => $newUser->id,
            'role' => OrganizationRole::Committee->value,
        ]);
    }

    public function test_adding_a_member_by_unregistered_email_fails_with_a_validation_error()
    {
        $organization = Organization::factory()->create();
        $admin = $this->memberWithRole($organization, OrganizationRole::Admin);

        $this->actingAs($admin)
            ->post('/members', ['email' => 'nobody@rukunmuda.test', 'role' => OrganizationRole::Member->value])
            ->assertSessionHasErrors('email');
    }

    public function test_adding_an_already_existing_member_fails()
    {
        $organization = Organization::factory()->create();
        $admin = $this->memberWithRole($organization, OrganizationRole::Admin);
        $existing = $this->memberWithRole($organization, OrganizationRole::Member);

        $this->actingAs($admin)
            ->post('/members', ['email' => $existing->email, 'role' => OrganizationRole::Committee->value])
            ->assertSessionHasErrors('email');
    }

    public function test_a_plain_member_cannot_add_members()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Member);
        $newUser = User::factory()->create();

        $this->actingAs($member)
            ->post('/members', ['email' => $newUser->email, 'role' => OrganizationRole::Member->value])
            ->assertForbidden();
    }

    public function test_admin_can_change_a_members_role_but_not_promote_to_owner()
    {
        $organization = Organization::factory()->create();
        $admin = $this->memberWithRole($organization, OrganizationRole::Admin);
        $target = $this->memberWithRole($organization, OrganizationRole::Member);
        $targetMembership = $organization->memberships()->firstWhere('user_id', $target->id);

        $this->actingAs($admin)
            ->patch("/members/{$targetMembership->id}", ['role' => OrganizationRole::Treasurer->value])
            ->assertRedirect();

        $this->assertSame(OrganizationRole::Treasurer, $targetMembership->fresh()->role);

        $this->actingAs($admin)
            ->patch("/members/{$targetMembership->id}", ['role' => OrganizationRole::Owner->value])
            ->assertSessionHasErrors('role');
    }

    public function test_the_owners_role_cannot_be_changed()
    {
        $organization = Organization::factory()->create();
        $owner = $this->memberWithRole($organization, OrganizationRole::Owner);
        $admin = $this->memberWithRole($organization, OrganizationRole::Admin);
        $ownerMembership = $organization->memberships()->firstWhere('user_id', $owner->id);

        $this->actingAs($admin)
            ->patch("/members/{$ownerMembership->id}", ['role' => OrganizationRole::Admin->value])
            ->assertForbidden();
    }

    public function test_the_owner_cannot_be_removed()
    {
        $organization = Organization::factory()->create();
        $owner = $this->memberWithRole($organization, OrganizationRole::Owner);
        $admin = $this->memberWithRole($organization, OrganizationRole::Admin);
        $ownerMembership = $organization->memberships()->firstWhere('user_id', $owner->id);

        $this->actingAs($admin)
            ->delete("/members/{$ownerMembership->id}")
            ->assertForbidden();
    }

    public function test_admin_can_remove_a_plain_member()
    {
        $organization = Organization::factory()->create();
        $admin = $this->memberWithRole($organization, OrganizationRole::Admin);
        $target = $this->memberWithRole($organization, OrganizationRole::Member);
        $targetMembership = $organization->memberships()->firstWhere('user_id', $target->id);

        $this->actingAs($admin)
            ->delete("/members/{$targetMembership->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('organization_memberships', ['id' => $targetMembership->id]);
    }
}
