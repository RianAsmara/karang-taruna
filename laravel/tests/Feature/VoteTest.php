<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Event;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoteOption;
use App\Models\VoteResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->actingAs($member)
            ->get('/votes')
            ->assertInertia(fn ($page) => $page->has('votes', 1));

        $this->actingAs($member)
            ->get("/votes/{$vote->id}")
            ->assertInertia(fn ($page) => $page
                ->where('vote.isOpen', true)
                ->where('canRespond', true)
                ->has('vote.options', 2)
            );
    }

    public function test_a_member_can_submit_a_response()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $vote = Vote::factory()->for($organization)->create();
        $ya = VoteOption::factory()->for($vote)->create(['label' => 'Ya']);
        VoteOption::factory()->for($vote)->create(['label' => 'Tidak']);

        $this->actingAs($member)
            ->post("/votes/{$vote->id}/responses", ['option_ids' => [$ya->id]])
            ->assertRedirect();

        $this->assertTrue($vote->hasResponded($this->membershipOf($organization, $member)));
    }

    public function test_a_member_can_change_their_response_on_an_editable_vote()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $vote = Vote::factory()->for($organization)->create(['editable' => true]);
        $ya = VoteOption::factory()->for($vote)->create(['label' => 'Ya']);
        $tidak = VoteOption::factory()->for($vote)->create(['label' => 'Tidak']);

        $this->actingAs($member)->post("/votes/{$vote->id}/responses", ['option_ids' => [$ya->id]]);
        $this->actingAs($member)->post("/votes/{$vote->id}/responses", ['option_ids' => [$tidak->id]]);

        $membership = $this->membershipOf($organization, $member);
        $this->assertSame(
            [$tidak->id],
            VoteResponse::where('vote_id', $vote->id)->where('membership_id', $membership->id)->pluck('vote_option_id')->all(),
        );
    }

    public function test_a_response_cannot_be_changed_on_a_non_editable_vote()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $vote = Vote::factory()->notEditable()->for($organization)->create();
        $ya = VoteOption::factory()->for($vote)->create();
        $tidak = VoteOption::factory()->for($vote)->create();

        $this->actingAs($member)->post("/votes/{$vote->id}/responses", ['option_ids' => [$ya->id]]);
        $this->actingAs($member)
            ->post("/votes/{$vote->id}/responses", ['option_ids' => [$tidak->id]])
            ->assertSessionHasErrors('option_ids');
    }

    public function test_a_closed_vote_rejects_a_new_response()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $vote = Vote::factory()->closed()->for($organization)->create();
        $option = VoteOption::factory()->for($vote)->create();

        $this->actingAs($member)
            ->post("/votes/{$vote->id}/responses", ['option_ids' => [$option->id]])
            ->assertForbidden();
    }

    public function test_an_ordinary_member_excluded_from_a_pengurus_only_vote_cannot_respond()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $vote = Vote::factory()->pengurusOnly()->for($organization)->create();
        $option = VoteOption::factory()->for($vote)->create();

        $this->actingAs($member)
            ->get("/votes/{$vote->id}")
            ->assertInertia(fn ($page) => $page->where('vote.isEligible', false)->where('canRespond', false));

        $this->actingAs($member)
            ->post("/votes/{$vote->id}/responses", ['option_ids' => [$option->id]])
            ->assertForbidden();
    }

    public function test_selecting_more_than_max_selections_is_rejected()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $vote = Vote::factory()->for($organization)->create(['max_selections' => 1]);
        $a = VoteOption::factory()->for($vote)->create();
        $b = VoteOption::factory()->for($vote)->create();

        $this->actingAs($member)
            ->post("/votes/{$vote->id}/responses", ['option_ids' => [$a->id, $b->id]])
            ->assertSessionHasErrors('option_ids');
    }

    public function test_results_are_shown_after_the_vote_closes_with_a_winner()
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

        $this->actingAs($chair)
            ->get("/votes/{$vote->id}")
            ->assertInertia(fn ($page) => $page
                ->where('results.participationCount', 4)
                ->where('results.winningOptionIds.0', $ya->id)
                ->where('results.isTie', false)
                ->where('results.breakdown.0.members.0.name', fn ($name) => is_string($name))
            );
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

        $this->actingAs($chair)
            ->get("/votes/{$vote->id}")
            ->assertInertia(fn ($page) => $page->where('results.breakdown', null)->where('results.options.0.percent', 100));
    }

    public function test_results_become_visible_before_closing_once_the_member_has_responded()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $vote = Vote::factory()->for($organization)->create();
        $option = VoteOption::factory()->for($vote)->create();

        $this->actingAs($member)
            ->get("/votes/{$vote->id}")
            ->assertInertia(fn ($page) => $page->where('results', null));

        $this->actingAs($member)->post("/votes/{$vote->id}/responses", ['option_ids' => [$option->id]]);

        $this->actingAs($member)
            ->get("/votes/{$vote->id}")
            ->assertInertia(fn ($page) => $page->has('results'));
    }

    public function test_a_pengurus_can_create_a_vote_with_options()
    {
        $organization = Organization::factory()->create();
        $secretary = $this->memberWithRole($organization, OrganizationRole::Sekretaris);

        $this->actingAs($secretary)->get('/votes/create')->assertOk();

        $this->actingAs($secretary)
            ->post('/votes', [
                'question' => 'Apakah kita mengadakan turnamen voli?',
                'anonymous' => false,
                'editable' => true,
                'max_selections' => 1,
                'eligible_scope' => 'ALL',
                'start_at' => now()->toDateTimeString(),
                'end_at' => now()->addWeek()->toDateTimeString(),
                'options' => ['Ya', 'Tidak'],
            ])
            ->assertRedirect();

        $vote = Vote::firstWhere('question', 'Apakah kita mengadakan turnamen voli?');
        $this->assertNotNull($vote);
        $this->assertSame($organization->id, $vote->organization_id);
        $this->assertCount(2, $vote->options);
    }

    public function test_a_plain_member_cannot_create_a_vote()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);

        $this->actingAs($member)->get('/votes/create')->assertForbidden();

        $this->actingAs($member)
            ->post('/votes', [
                'question' => 'Apakah kita mengadakan turnamen voli?',
                'anonymous' => false,
                'editable' => true,
                'max_selections' => 1,
                'eligible_scope' => 'ALL',
                'start_at' => now()->toDateTimeString(),
                'end_at' => now()->addWeek()->toDateTimeString(),
                'options' => ['Ya', 'Tidak'],
            ])
            ->assertForbidden();
    }

    public function test_creating_a_vote_with_fewer_than_two_options_is_rejected()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);

        $this->actingAs($chair)
            ->post('/votes', [
                'question' => 'Apakah kita mengadakan turnamen voli?',
                'anonymous' => false,
                'editable' => true,
                'max_selections' => 1,
                'eligible_scope' => 'ALL',
                'start_at' => now()->toDateTimeString(),
                'end_at' => now()->addWeek()->toDateTimeString(),
                'options' => ['Ya'],
            ])
            ->assertSessionHasErrors('options');
    }

    public function test_end_at_must_be_after_start_at()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);

        $this->actingAs($chair)
            ->post('/votes', [
                'question' => 'Apakah kita mengadakan turnamen voli?',
                'anonymous' => false,
                'editable' => true,
                'max_selections' => 1,
                'eligible_scope' => 'ALL',
                'start_at' => now()->toDateTimeString(),
                'end_at' => now()->subDay()->toDateTimeString(),
                'options' => ['Ya', 'Tidak'],
            ])
            ->assertSessionHasErrors('end_at');
    }

    public function test_a_vote_cannot_be_linked_to_another_organizations_event()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $otherOrganization = Organization::factory()->create();
        $otherEvent = Event::factory()->create(['organization_id' => $otherOrganization->id]);

        $this->actingAs($chair)
            ->post('/votes', [
                'question' => 'Apakah kita mengadakan turnamen voli?',
                'anonymous' => false,
                'editable' => true,
                'max_selections' => 1,
                'eligible_scope' => 'ALL',
                'event_id' => $otherEvent->id,
                'start_at' => now()->toDateTimeString(),
                'end_at' => now()->addWeek()->toDateTimeString(),
                'options' => ['Ya', 'Tidak'],
            ])
            ->assertSessionHasErrors('event_id');
    }

    public function test_a_member_from_another_organization_cannot_view_this_organizations_vote()
    {
        $organization = Organization::factory()->create();
        $vote = Vote::factory()->for($organization)->create();

        $otherOrganization = Organization::factory()->create();
        $outsider = $this->memberWithRole($otherOrganization, OrganizationRole::Ketua);

        $this->actingAs($outsider)
            ->get("/votes/{$vote->id}")
            ->assertForbidden();
    }
}
