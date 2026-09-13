<?php

namespace Tests\Feature;

use App\Enums\MembershipExitRequestStatus;
use App\Enums\OrganizationRole;
use App\Models\MembershipExitRequest;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Notifications\MembershipExitDecided;
use App\Notifications\MembershipExitRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MembershipExitTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $chair;

    private User $member;

    private OrganizationMembership $memberMembership;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();

        $this->chair = User::factory()->create();
        $this->organization->memberships()->create(['user_id' => $this->chair->id, 'role' => OrganizationRole::Ketua]);

        $this->member = User::factory()->create();
        $this->memberMembership = $this->organization->memberships()->create([
            'user_id' => $this->member->id,
            'role' => OrganizationRole::Anggota,
        ]);
    }

    public function test_a_member_can_request_to_leave_and_the_chair_is_notified()
    {
        Notification::fake();

        $this->actingAs($this->member)
            ->post('/membership/exit-requests', ['reason' => 'Pindah domisili.'])
            ->assertRedirect();

        $exitRequest = MembershipExitRequest::firstWhere('membership_id', $this->memberMembership->id);

        $this->assertNotNull($exitRequest);
        $this->assertSame(MembershipExitRequestStatus::Pending, $exitRequest->status);
        $this->assertSame('Pindah domisili.', $exitRequest->reason);

        // Still a member until the chair approves — this is the whole point.
        $this->assertNotNull($this->member->fresh()->roleIn($this->organization));

        Notification::assertSentTo($this->chair, MembershipExitRequested::class);
    }

    public function test_the_chair_cannot_request_to_leave_without_handing_over_the_role_first()
    {
        $this->actingAs($this->chair)
            ->post('/membership/exit-requests', [])
            ->assertSessionHasErrors();

        $this->assertSame(0, MembershipExitRequest::count());
    }

    public function test_a_member_cannot_queue_two_pending_requests()
    {
        $this->actingAs($this->member)->post('/membership/exit-requests', [])->assertRedirect();
        $this->actingAs($this->member)->post('/membership/exit-requests', [])->assertSessionHasErrors();

        $this->assertSame(1, MembershipExitRequest::count());
    }

    public function test_approving_removes_the_membership_and_notifies_the_member()
    {
        Notification::fake();

        $exitRequest = MembershipExitRequest::create([
            'organization_id' => $this->organization->id,
            'membership_id' => $this->memberMembership->id,
            'status' => MembershipExitRequestStatus::Pending,
        ]);

        $this->actingAs($this->chair)
            ->post("/membership/exit-requests/{$exitRequest->id}/decide", ['approve' => true])
            ->assertRedirect();

        $this->assertSame(MembershipExitRequestStatus::Approved, $exitRequest->fresh()->status);
        $this->assertNull($this->member->fresh()->roleIn($this->organization));
        // Soft-deleted, not gone: the 30-day "Keluar" retention window.
        $this->assertNotNull(OrganizationMembership::withTrashed()->find($this->memberMembership->id));

        Notification::assertSentTo($this->member, MembershipExitDecided::class);
    }

    public function test_rejecting_keeps_the_membership()
    {
        $exitRequest = MembershipExitRequest::create([
            'organization_id' => $this->organization->id,
            'membership_id' => $this->memberMembership->id,
            'status' => MembershipExitRequestStatus::Pending,
        ]);

        $this->actingAs($this->chair)
            ->post("/membership/exit-requests/{$exitRequest->id}/decide", ['approve' => false, 'note' => 'Tolong selesaikan tugas dulu.'])
            ->assertRedirect();

        $this->assertSame(MembershipExitRequestStatus::Rejected, $exitRequest->fresh()->status);
        $this->assertNotNull($this->member->fresh()->roleIn($this->organization));
    }

    public function test_an_ordinary_member_cannot_decide_a_request()
    {
        $other = User::factory()->create();
        $this->organization->memberships()->create(['user_id' => $other->id, 'role' => OrganizationRole::Sekretaris]);

        $exitRequest = MembershipExitRequest::create([
            'organization_id' => $this->organization->id,
            'membership_id' => $this->memberMembership->id,
            'status' => MembershipExitRequestStatus::Pending,
        ]);

        $this->actingAs($other)
            ->post("/membership/exit-requests/{$exitRequest->id}/decide", ['approve' => true])
            ->assertForbidden();

        $this->assertSame(MembershipExitRequestStatus::Pending, $exitRequest->fresh()->status);
    }

    public function test_a_chair_of_another_organization_cannot_decide_this_ones_request()
    {
        $otherOrganization = Organization::factory()->create();
        $outsideChair = User::factory()->create();
        $otherOrganization->memberships()->create(['user_id' => $outsideChair->id, 'role' => OrganizationRole::Ketua]);

        $exitRequest = MembershipExitRequest::create([
            'organization_id' => $this->organization->id,
            'membership_id' => $this->memberMembership->id,
            'status' => MembershipExitRequestStatus::Pending,
        ]);

        $this->actingAs($outsideChair)
            ->post("/membership/exit-requests/{$exitRequest->id}/decide", ['approve' => true])
            ->assertForbidden();

        $this->assertSame(MembershipExitRequestStatus::Pending, $exitRequest->fresh()->status);
    }

    public function test_a_decided_request_cannot_be_decided_again()
    {
        $exitRequest = MembershipExitRequest::create([
            'organization_id' => $this->organization->id,
            'membership_id' => $this->memberMembership->id,
            'status' => MembershipExitRequestStatus::Pending,
        ]);

        $this->actingAs($this->chair)->post("/membership/exit-requests/{$exitRequest->id}/decide", ['approve' => false])->assertRedirect();
        $this->actingAs($this->chair)->post("/membership/exit-requests/{$exitRequest->id}/decide", ['approve' => true])->assertSessionHasErrors();

        $this->assertSame(MembershipExitRequestStatus::Rejected, $exitRequest->fresh()->status);
        $this->assertNotNull($this->member->fresh()->roleIn($this->organization));
    }
}
