<?php

namespace Tests\Feature\Api;

use App\Enums\OrganizationRole;
use App\Models\MemberDue;
use App\Models\MemberPayment;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MemberDueTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_the_treasurer_sees_every_members_dues_but_a_plain_member_sees_only_their_own()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);
        $member = $this->memberWithRole($organization, OrganizationRole::Member);

        $memberMembership = $organization->memberships()->where('user_id', $member->id)->firstOrFail();

        MemberDue::factory()->create(['organization_id' => $organization->id, 'membership_id' => $memberMembership->id]);
        MemberDue::factory()->create(['organization_id' => $organization->id]); // someone else's due

        Sanctum::actingAs($member);
        $this->getJson('/api/v1/finance/dues')->assertOk()->assertJsonCount(1, 'data');

        Sanctum::actingAs($treasurer);
        $this->getJson('/api/v1/finance/dues')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_amount_paid_and_outstanding_are_computed_from_payments()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);
        $due = MemberDue::factory()->create(['organization_id' => $organization->id, 'amount_due' => 100_000]);
        MemberPayment::factory()->create(['member_due_id' => $due->id, 'amount' => 40_000]);

        Sanctum::actingAs($treasurer);

        $this->getJson('/api/v1/finance/dues')
            ->assertOk()
            ->assertJsonPath('data.0.amountPaid', 40_000)
            ->assertJsonPath('data.0.amountOutstanding', 60_000)
            ->assertJsonPath('data.0.isPaid', false);
    }
}
