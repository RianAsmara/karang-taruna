<?php

namespace Tests\Feature\Api;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoteOption;
use App\Models\VoteResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VoteTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    private function membershipOf(Organization $organization, User $user): OrganizationMembership
    {
        return $organization->memberships()->where('user_id', $user->id)->firstOrFail();
    }

    public function test_any_member_can_list_and_view_an_open_vote()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $vote = Vote::factory()->for($organization)->create();
        VoteOption::factory()->for($vote)->create(['label' => 'Ya']);
        VoteOption::factory()->for($vote)->create(['label' => 'Tidak']);

        Sanctum::actingAs($member);

        $this->getJson('/api/v1/votes')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/votes/{$vote->id}")
            ->assertOk()
            ->assertJsonPath('data.isOpen', true)
            ->assertJsonCount(2, 'data.options');
    }

    public function test_a_member_can_submit_a_response()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $vote = Vote::factory()->for($organization)->create();
        $ya = VoteOption::factory()->for($vote)->create(['label' => 'Ya']);
        VoteOption::factory()->for($vote)->create(['label' => 'Tidak']);

        Sanctum::actingAs($member);

        $this->postJson("/api/v1/votes/{$vote->id}/responses", ['option_ids' => [$ya->id]])
            ->assertOk()
            ->assertJsonPath('data.hasResponded', true)
            ->assertJsonPath('data.myOptionIds.0', $ya->id);
    }

    public function test_a_member_can_change_their_response_on_an_editable_vote()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $vote = Vote::factory()->for($organization)->create(['editable' => true]);
        $ya = VoteOption::factory()->for($vote)->create(['label' => 'Ya']);
        $tidak = VoteOption::factory()->for($vote)->create(['label' => 'Tidak']);

        Sanctum::actingAs($member);

        $this->postJson("/api/v1/votes/{$vote->id}/responses", ['option_ids' => [$ya->id]])->assertOk();
        $this->postJson("/api/v1/votes/{$vote->id}/responses", ['option_ids' => [$tidak->id]])
            ->assertOk()
            ->assertJsonPath('data.myOptionIds.0', $tidak->id)
            ->assertJsonCount(1, 'data.myOptionIds');
    }

    public function test_a_response_cannot_be_changed_on_a_non_editable_vote()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $vote = Vote::factory()->notEditable()->for($organization)->create();
        $ya = VoteOption::factory()->for($vote)->create();
        $tidak = VoteOption::factory()->for($vote)->create();

        Sanctum::actingAs($member);

        $this->postJson("/api/v1/votes/{$vote->id}/responses", ['option_ids' => [$ya->id]])->assertOk();
        $this->postJson("/api/v1/votes/{$vote->id}/responses", ['option_ids' => [$tidak->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('option_ids');
    }

    public function test_a_closed_vote_rejects_a_new_response()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $vote = Vote::factory()->closed()->for($organization)->create();
        $option = VoteOption::factory()->for($vote)->create();

        Sanctum::actingAs($member);

        $this->postJson("/api/v1/votes/{$vote->id}/responses", ['option_ids' => [$option->id]])->assertForbidden();
    }

    public function test_an_ordinary_member_excluded_from_a_pengurus_only_vote_cannot_respond()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $vote = Vote::factory()->pengurusOnly()->for($organization)->create();
        $option = VoteOption::factory()->for($vote)->create();

        Sanctum::actingAs($member);

        // Still visible (view), just can't respond.
        $this->getJson("/api/v1/votes/{$vote->id}")->assertOk()->assertJsonPath('data.isEligible', false);
        $this->postJson("/api/v1/votes/{$vote->id}/responses", ['option_ids' => [$option->id]])->assertForbidden();
    }

    public function test_selecting_more_than_max_selections_is_rejected()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $vote = Vote::factory()->for($organization)->create(['max_selections' => 1]);
        $a = VoteOption::factory()->for($vote)->create();
        $b = VoteOption::factory()->for($vote)->create();

        Sanctum::actingAs($member);

        $this->postJson("/api/v1/votes/{$vote->id}/responses", ['option_ids' => [$a->id, $b->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('option_ids');
    }

    public function test_results_show_counts_and_a_winner()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $vote = Vote::factory()->closed()->for($organization)->create();
        $ya = VoteOption::factory()->for($vote)->create(['label' => 'Ya']);
        $tidak = VoteOption::factory()->for($vote)->create(['label' => 'Tidak']);

        foreach (range(1, 3) as $i) {
            $voter = $this->memberWithRole($organization, OrganizationRole::Anggota);
            VoteResponse::factory()->create([
                'vote_id' => $vote->id,
                'vote_option_id' => $ya->id,
                'membership_id' => $this->membershipOf($organization, $voter)->id,
            ]);
        }
        $noVoter = $this->memberWithRole($organization, OrganizationRole::Anggota);
        VoteResponse::factory()->create([
            'vote_id' => $vote->id,
            'vote_option_id' => $tidak->id,
            'membership_id' => $this->membershipOf($organization, $noVoter)->id,
        ]);

        Sanctum::actingAs($chair);

        $this->getJson("/api/v1/votes/{$vote->id}/results")
            ->assertOk()
            ->assertJsonPath('data.participationCount', 4)
            ->assertJsonPath('data.winningOptionIds.0', $ya->id)
            ->assertJsonPath('data.isTie', false)
            ->assertJsonPath('data.breakdown.0.members.0.name', fn ($name) => is_string($name));
    }

    public function test_an_anonymous_votes_breakdown_is_never_exposed_even_to_the_chair()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $vote = Vote::factory()->anonymous()->closed()->for($organization)->create();
        $option = VoteOption::factory()->for($vote)->create();

        foreach (range(1, 5) as $i) {
            $voter = $this->memberWithRole($organization, OrganizationRole::Anggota);
            VoteResponse::factory()->create([
                'vote_id' => $vote->id,
                'vote_option_id' => $option->id,
                'membership_id' => $this->membershipOf($organization, $voter)->id,
            ]);
        }

        Sanctum::actingAs($chair);

        $this->getJson("/api/v1/votes/{$vote->id}/results")
            ->assertOk()
            ->assertJsonPath('data.breakdown', null)
            ->assertJsonPath('data.options.0.percent', 100);
    }

    public function test_percentages_are_suppressed_for_an_anonymous_vote_with_fewer_than_three_voters()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $vote = Vote::factory()->anonymous()->closed()->for($organization)->create();
        $option = VoteOption::factory()->for($vote)->create();
        $voter = $this->memberWithRole($organization, OrganizationRole::Anggota);
        VoteResponse::factory()->create([
            'vote_id' => $vote->id,
            'vote_option_id' => $option->id,
            'membership_id' => $this->membershipOf($organization, $voter)->id,
        ]);

        Sanctum::actingAs($chair);

        $this->getJson("/api/v1/votes/{$vote->id}/results")
            ->assertOk()
            ->assertJsonPath('data.options.0.percent', null)
            ->assertJsonPath('data.options.0.count', 1);
    }

    public function test_a_member_from_another_organization_cannot_view_this_organizations_vote()
    {
        $organization = Organization::factory()->create();
        $vote = Vote::factory()->for($organization)->create();

        $otherOrganization = Organization::factory()->create();
        $outsider = $this->memberWithRole($otherOrganization, OrganizationRole::Ketua);

        Sanctum::actingAs($outsider);

        $this->getJson("/api/v1/votes/{$vote->id}")->assertForbidden();
    }
}
