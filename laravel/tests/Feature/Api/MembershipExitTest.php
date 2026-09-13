<?php

namespace Tests\Feature\Api;

use App\Enums\MembershipExitRequestStatus;
use App\Enums\OrganizationRole;
use App\Models\MembershipExitRequest;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MembershipExitTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_a_member_can_submit_an_exit_request()
    {
        $organization = Organization::factory()->create();
        $this->memberWithRole($organization, OrganizationRole::Ketua);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);

        Sanctum::actingAs($member);

        $this->postJson('/api/v1/membership/exit-requests', ['reason' => 'Pindah kota.'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'PENDING')
            ->assertJsonPath('data.reason', 'Pindah kota.');

        $this->assertNotNull($member->fresh()->roleIn($organization));
    }

    public function test_mine_reports_the_members_pending_request()
    {
        $organization = Organization::factory()->create();
        $this->memberWithRole($organization, OrganizationRole::Ketua);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);

        Sanctum::actingAs($member);

        $this->getJson('/api/v1/membership/exit-requests/mine')
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('canRequest', true);

        $this->postJson('/api/v1/membership/exit-requests', [])->assertCreated();

        $this->getJson('/api/v1/membership/exit-requests/mine')
            ->assertOk()
            ->assertJsonPath('data.status', 'PENDING');
    }

    public function test_the_chair_sees_and_approves_the_pending_queue()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $membership = $member->fresh()->membershipIn($organization);

        $exitRequest = MembershipExitRequest::create([
            'organization_id' => $organization->id,
            'membership_id' => $membership->id,
            'status' => MembershipExitRequestStatus::Pending,
        ]);

        Sanctum::actingAs($chair);

        $this->getJson('/api/v1/membership/exit-requests')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $exitRequest->id);

        $this->postJson("/api/v1/membership/exit-requests/{$exitRequest->id}/decide", ['approve' => true])
            ->assertOk()
            ->assertJsonPath('data.status', 'APPROVED');

        $this->assertNull($member->fresh()->roleIn($organization));
        $this->assertNotNull(OrganizationMembership::withTrashed()->find($membership->id));
    }

    public function test_a_non_chair_cannot_see_the_queue_or_decide()
    {
        $organization = Organization::factory()->create();
        $this->memberWithRole($organization, OrganizationRole::Ketua);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $membership = $member->fresh()->membershipIn($organization);

        $exitRequest = MembershipExitRequest::create([
            'organization_id' => $organization->id,
            'membership_id' => $membership->id,
            'status' => MembershipExitRequestStatus::Pending,
        ]);

        Sanctum::actingAs($treasurer);

        $this->getJson('/api/v1/membership/exit-requests')->assertForbidden();
        $this->postJson("/api/v1/membership/exit-requests/{$exitRequest->id}/decide", ['approve' => true])->assertForbidden();

        $this->assertNotNull($member->fresh()->roleIn($organization));
    }

    public function test_a_chair_cannot_submit_an_exit_request()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);

        Sanctum::actingAs($chair);

        $this->postJson('/api/v1/membership/exit-requests', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('membership');
    }

    public function test_a_chair_of_another_organization_cannot_decide_this_ones_request()
    {
        $organization = Organization::factory()->create();
        $this->memberWithRole($organization, OrganizationRole::Ketua);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $membership = $member->fresh()->membershipIn($organization);

        $otherOrganization = Organization::factory()->create();
        $outsideChair = $this->memberWithRole($otherOrganization, OrganizationRole::Ketua);

        $exitRequest = MembershipExitRequest::create([
            'organization_id' => $organization->id,
            'membership_id' => $membership->id,
            'status' => MembershipExitRequestStatus::Pending,
        ]);

        Sanctum::actingAs($outsideChair);

        $this->postJson("/api/v1/membership/exit-requests/{$exitRequest->id}/decide", ['approve' => true])
            ->assertForbidden();

        $this->assertSame(MembershipExitRequestStatus::Pending, $exitRequest->fresh()->status);
    }
}
