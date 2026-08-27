<?php

namespace Tests\Feature\Api;

use App\Enums\EventStatus;
use App\Enums\OrganizationRole;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventCommittee;
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
        $owner = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $this->memberWithRole($organization, OrganizationRole::Anggota);

        Sanctum::actingAs($owner);

        $this->getJson('/api/v1/members')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_only_an_organizer_can_add_a_member()
    {
        $organization = Organization::factory()->create();
        $plainMember = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $newUser = User::factory()->create();

        Sanctum::actingAs($plainMember);

        $this->postJson('/api/v1/members', [
            'email' => $newUser->email,
            'role' => 'ANGGOTA',
        ])->assertForbidden();
    }

    public function test_the_chair_can_add_an_existing_user_as_a_member()
    {
        $organization = Organization::factory()->create();
        $owner = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $newUser = User::factory()->create();

        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/members', [
            'email' => $newUser->email,
            'role' => 'BENDAHARA',
        ])->assertCreated()->assertJsonPath('data.role', 'BENDAHARA');

        $this->assertDatabaseHas('organization_memberships', [
            'organization_id' => $organization->id,
            'user_id' => $newUser->id,
            'role' => 'BENDAHARA',
        ]);
    }

    public function test_a_member_from_another_organization_cannot_view_this_organizations_member()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $membership = $organization->memberships()->where('user_id', $member->id)->firstOrFail();

        $otherOrganization = Organization::factory()->create();
        $outsider = $this->memberWithRole($otherOrganization, OrganizationRole::Ketua);

        Sanctum::actingAs($outsider);

        $this->getJson("/api/v1/members/{$membership->id}")
            ->assertForbidden();
    }

    public function test_chair_can_change_a_members_role()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $target = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $targetMembership = $organization->memberships()->where('user_id', $target->id)->firstOrFail();

        Sanctum::actingAs($chair);

        $this->patchJson("/api/v1/members/{$targetMembership->id}", ['role' => 'SEKRETARIS'])
            ->assertOk()
            ->assertJsonPath('data.role', 'SEKRETARIS');

        $this->assertSame(OrganizationRole::Sekretaris, $targetMembership->fresh()->role);
    }

    public function test_a_members_role_cannot_be_changed_to_chair()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $target = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $targetMembership = $organization->memberships()->where('user_id', $target->id)->firstOrFail();

        Sanctum::actingAs($chair);

        $this->patchJson("/api/v1/members/{$targetMembership->id}", ['role' => 'KETUA'])
            ->assertUnprocessable();
    }

    public function test_the_chairs_own_role_cannot_be_changed()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $chairMembership = $organization->memberships()->where('user_id', $chair->id)->firstOrFail();

        Sanctum::actingAs($chair);

        $this->patchJson("/api/v1/members/{$chairMembership->id}", ['role' => 'BENDAHARA'])
            ->assertForbidden();
    }

    public function test_a_plain_member_cannot_change_another_members_role()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $target = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $targetMembership = $organization->memberships()->where('user_id', $target->id)->firstOrFail();

        Sanctum::actingAs($member);

        $this->patchJson("/api/v1/members/{$targetMembership->id}", ['role' => 'BENDAHARA'])
            ->assertForbidden();
    }

    public function test_chair_can_remove_a_plain_member()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $target = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $targetMembership = $organization->memberships()->where('user_id', $target->id)->firstOrFail();

        Sanctum::actingAs($chair);

        $this->deleteJson("/api/v1/members/{$targetMembership->id}")->assertNoContent();

        // Soft-deleted, not gone — see the 30-day "Keluar" retention
        // window (MemberController::index, ADR-0017's sibling decision).
        $this->assertSoftDeleted('organization_memberships', ['id' => $targetMembership->id]);
    }

    public function test_the_chair_cannot_remove_their_own_membership()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $chairMembership = $organization->memberships()->where('user_id', $chair->id)->firstOrFail();

        Sanctum::actingAs($chair);

        $this->deleteJson("/api/v1/members/{$chairMembership->id}")->assertForbidden();
    }

    public function test_a_member_who_left_within_30_days_still_appears_with_a_left_at_timestamp()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $target = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $targetMembership = $organization->memberships()->where('user_id', $target->id)->firstOrFail();

        Sanctum::actingAs($chair);
        $this->deleteJson("/api/v1/members/{$targetMembership->id}")->assertNoContent();

        $this->getJson('/api/v1/members')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.1.leftAt', fn ($value) => $value !== null);
    }

    public function test_a_member_who_left_more_than_30_days_ago_no_longer_appears()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $target = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $targetMembership = $organization->memberships()->where('user_id', $target->id)->firstOrFail();
        $targetMembership->forceFill(['deleted_at' => now()->subDays(31)])->save();

        Sanctum::actingAs($chair);

        $this->getJson('/api/v1/members')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_re_inviting_a_member_who_left_restores_their_membership_with_the_new_role()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $target = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $targetMembership = $organization->memberships()->where('user_id', $target->id)->firstOrFail();

        Sanctum::actingAs($chair);
        $this->deleteJson("/api/v1/members/{$targetMembership->id}")->assertNoContent();

        $this->postJson('/api/v1/members', ['email' => $target->email, 'role' => 'BENDAHARA'])
            ->assertCreated()
            ->assertJsonPath('data.id', $targetMembership->id)
            ->assertJsonPath('data.role', 'BENDAHARA')
            ->assertJsonPath('data.leftAt', null);

        $this->assertDatabaseHas('organization_memberships', [
            'id' => $targetMembership->id,
            'deleted_at' => null,
        ]);
    }

    public function test_the_chair_can_transfer_the_chair_role_to_another_member()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $chairMembership = $organization->memberships()->where('user_id', $chair->id)->firstOrFail();
        $target = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $targetMembership = $organization->memberships()->where('user_id', $target->id)->firstOrFail();

        Sanctum::actingAs($chair);

        $this->postJson("/api/v1/members/{$targetMembership->id}/transfer-chair")
            ->assertOk()
            ->assertJsonPath('data.role', 'KETUA');

        $this->assertSame(OrganizationRole::Ketua, $targetMembership->fresh()->role);
        $this->assertSame(OrganizationRole::Anggota, $chairMembership->fresh()->role);
    }

    public function test_a_plain_member_cannot_transfer_the_chair_role()
    {
        $organization = Organization::factory()->create();
        $this->memberWithRole($organization, OrganizationRole::Ketua);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $target = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $targetMembership = $organization->memberships()->where('user_id', $target->id)->firstOrFail();

        Sanctum::actingAs($member);

        $this->postJson("/api/v1/members/{$targetMembership->id}/transfer-chair")->assertForbidden();
    }

    public function test_transferring_the_chair_to_the_current_chair_is_rejected()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $chairMembership = $organization->memberships()->where('user_id', $chair->id)->firstOrFail();

        Sanctum::actingAs($chair);

        $this->postJson("/api/v1/members/{$chairMembership->id}/transfer-chair")->assertUnprocessable();
    }

    public function test_pengurus_always_sees_a_members_phone_but_an_ordinary_member_only_sees_it_when_opted_in()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $memberA = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $memberA->update(['phone' => '081200000000', 'show_phone_to_members' => false]);
        $memberAMembership = $organization->memberships()->where('user_id', $memberA->id)->firstOrFail();
        $memberB = $this->memberWithRole($organization, OrganizationRole::Anggota);

        Sanctum::actingAs($chair);
        $this->getJson("/api/v1/members/{$memberAMembership->id}")->assertOk()->assertJsonPath('data.phone', '081200000000');

        Sanctum::actingAs($memberB);
        $this->getJson("/api/v1/members/{$memberAMembership->id}")->assertOk()->assertJsonPath('data.phone', null);

        $memberA->update(['show_phone_to_members' => true]);
        Sanctum::actingAs($memberB);
        $this->getJson("/api/v1/members/{$memberAMembership->id}")->assertOk()->assertJsonPath('data.phone', '081200000000');
    }

    public function test_a_member_can_set_their_own_phone_and_visibility()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);

        Sanctum::actingAs($member);

        $this->patchJson('/api/v1/profile/phone-visibility', [
            'phone' => '081311112222',
            'show_phone_to_members' => true,
        ])->assertOk()->assertJsonPath('data.phone', '081311112222');

        $this->assertSame('081311112222', $member->fresh()->phone);
        $this->assertTrue($member->fresh()->show_phone_to_members);
    }

    public function test_responsibilities_lists_only_active_event_committee_assignments()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $membership = $organization->memberships()->where('user_id', $member->id)->firstOrFail();

        $activeEvent = Event::factory()->for($organization)->create(['status' => EventStatus::Planned]);
        EventCommittee::create(['event_id' => $activeEvent->id, 'membership_id' => $membership->id, 'role_title' => 'Sie Konsumsi']);

        $completedEvent = Event::factory()->for($organization)->create(['status' => EventStatus::Completed]);
        EventCommittee::create(['event_id' => $completedEvent->id, 'membership_id' => $membership->id, 'role_title' => 'Sie Acara']);

        Sanctum::actingAs($chair);

        $this->getJson("/api/v1/members/{$membership->id}/responsibilities")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.roleTitle', 'Sie Konsumsi');
    }

    public function test_activity_returns_the_last_5_audit_log_entries_for_that_member()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $membership = $organization->memberships()->where('user_id', $member->id)->firstOrFail();

        foreach (range(1, 7) as $i) {
            AuditLog::create([
                'organization_id' => $organization->id,
                'actor_id' => $member->id,
                'action' => "transaction.approved.{$i}",
                'model_type' => 'App\\Models\\FinancialTransaction',
                'model_id' => (string) $i,
            ]);
        }

        Sanctum::actingAs($chair);

        $this->getJson("/api/v1/members/{$membership->id}/activity")
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }
}
