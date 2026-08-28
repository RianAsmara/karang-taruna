<?php

namespace Tests\Feature;

use App\Enums\ActivityPointSource;
use App\Enums\OrganizationRole;
use App\Models\ActivityLog;
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

    public function test_chair_can_add_an_existing_user_as_a_member()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $newUser = User::factory()->create();

        $this->actingAs($chair)
            ->post('/members', ['email' => $newUser->email, 'role' => OrganizationRole::Bendahara->value])
            ->assertRedirect();

        $this->assertDatabaseHas('organization_memberships', [
            'organization_id' => $organization->id,
            'user_id' => $newUser->id,
            'role' => OrganizationRole::Bendahara->value,
        ]);
    }

    public function test_adding_a_member_by_unregistered_email_fails_with_a_validation_error()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);

        $this->actingAs($chair)
            ->post('/members', ['email' => 'nobody@rukunmuda.test', 'role' => OrganizationRole::Anggota->value])
            ->assertSessionHasErrors('email');
    }

    public function test_adding_an_already_existing_member_fails()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $existing = $this->memberWithRole($organization, OrganizationRole::Anggota);

        $this->actingAs($chair)
            ->post('/members', ['email' => $existing->email, 'role' => OrganizationRole::Bendahara->value])
            ->assertSessionHasErrors('email');
    }

    public function test_inviting_a_new_member_as_chair_is_rejected()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $newUser = User::factory()->create();

        $this->actingAs($chair)
            ->post('/members', ['email' => $newUser->email, 'role' => OrganizationRole::Ketua->value])
            ->assertSessionHasErrors('role');
    }

    public function test_a_plain_member_cannot_add_members()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $newUser = User::factory()->create();

        $this->actingAs($member)
            ->post('/members', ['email' => $newUser->email, 'role' => OrganizationRole::Anggota->value])
            ->assertForbidden();
    }

    public function test_chair_can_change_a_members_role_but_not_promote_to_chair()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $target = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $targetMembership = $organization->memberships()->firstWhere('user_id', $target->id);

        $this->actingAs($chair)
            ->patch("/members/{$targetMembership->id}", ['role' => OrganizationRole::Bendahara->value])
            ->assertRedirect();

        $this->assertSame(OrganizationRole::Bendahara, $targetMembership->fresh()->role);

        $this->actingAs($chair)
            ->patch("/members/{$targetMembership->id}", ['role' => OrganizationRole::Ketua->value])
            ->assertSessionHasErrors('role');
    }

    public function test_the_chairs_own_role_cannot_be_changed_even_by_the_chair()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $chairMembership = $organization->memberships()->firstWhere('user_id', $chair->id);

        $this->actingAs($chair)
            ->patch("/members/{$chairMembership->id}", ['role' => OrganizationRole::Bendahara->value])
            ->assertForbidden();
    }

    public function test_the_chair_cannot_remove_their_own_membership()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $chairMembership = $organization->memberships()->firstWhere('user_id', $chair->id);

        $this->actingAs($chair)
            ->delete("/members/{$chairMembership->id}")
            ->assertForbidden();
    }

    public function test_chair_can_remove_a_plain_member()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $target = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $targetMembership = $organization->memberships()->firstWhere('user_id', $target->id);

        $this->actingAs($chair)
            ->delete("/members/{$targetMembership->id}")
            ->assertRedirect();

        $this->assertSoftDeleted('organization_memberships', ['id' => $targetMembership->id]);
    }

    public function test_search_matches_a_members_name_or_email()
    {
        $organization = Organization::factory()->create();
        $chair = User::factory()->create(['name' => 'Ketua Organisasi', 'email' => 'ketua@rukunmuda.test']);
        $organization->memberships()->create(['user_id' => $chair->id, 'role' => OrganizationRole::Ketua]);
        $budi = User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@rukunmuda.test']);
        $organization->memberships()->create(['user_id' => $budi->id, 'role' => OrganizationRole::Anggota]);
        $siti = User::factory()->create(['name' => 'Siti Aminah', 'email' => 'siti@rukunmuda.test']);
        $organization->memberships()->create(['user_id' => $siti->id, 'role' => OrganizationRole::Anggota]);

        $byName = $this->actingAs($chair)->get('/members?search=Budi');
        $byName->assertInertia(fn ($page) => $page
            ->has('members', 1)
            ->where('members.0.email', 'budi@rukunmuda.test')
            ->where('filters.search', 'Budi')
        );

        $byEmail = $this->actingAs($chair)->get('/members?search=siti@rukunmuda.test');
        $byEmail->assertInertia(fn ($page) => $page
            ->has('members', 1)
            ->where('members.0.email', 'siti@rukunmuda.test')
            ->where('filters.search', 'siti@rukunmuda.test')
        );
    }

    public function test_search_with_no_matches_returns_an_empty_list()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);

        $this->actingAs($chair)
            ->get('/members?search=TidakAda')
            ->assertInertia(fn ($page) => $page->has('members', 0));
    }

    public function test_the_member_list_shows_activity_points()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $membership = $organization->memberships()->where('user_id', $member->id)->first();

        ActivityLog::factory()->create([
            'organization_id' => $organization->id,
            'membership_id' => $membership->id,
            'points' => 5,
            'source' => ActivityPointSource::EventAttendance,
        ]);
        ActivityLog::factory()->create([
            'organization_id' => $organization->id,
            'membership_id' => $membership->id,
            'points' => 10,
            'source' => ActivityPointSource::TaskCompleted,
        ]);

        $this->actingAs($chair)
            ->get('/members')
            ->assertInertia(fn ($page) => $page
                ->where('members.0.activityPoints', 0)
                ->where('members.1.activityPoints', 15)
            );
    }
}
