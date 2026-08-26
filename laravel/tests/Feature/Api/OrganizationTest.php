<?php

namespace Tests\Feature\Api;

use App\Enums\EventParticipantStatus;
use App\Enums\EventStatus;
use App\Enums\OrganizationRole;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_can_fetch_their_current_organization()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => OrganizationRole::Treasurer]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/organizations/current')
            ->assertOk()
            ->assertJsonPath('data.id', $organization->id)
            ->assertJsonPath('data.slug', $organization->slug)
            ->assertJsonPath('membership.role', 'TREASURER');
    }

    public function test_participated_events_count_only_counts_registered_attendance_at_completed_events()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $membership = $organization->memberships()->create(['user_id' => $user->id, 'role' => OrganizationRole::Member]);

        $completedEvent = Event::factory()->for($organization)->create(['status' => EventStatus::Completed]);
        $plannedEvent = Event::factory()->for($organization)->create(['status' => EventStatus::Planned]);
        $cancelledParticipation = Event::factory()->for($organization)->create(['status' => EventStatus::Completed]);

        EventParticipant::factory()->for($completedEvent, 'event')->for($membership, 'membership')->create(['status' => EventParticipantStatus::Registered]);
        EventParticipant::factory()->for($plannedEvent, 'event')->for($membership, 'membership')->create(['status' => EventParticipantStatus::Registered]);
        EventParticipant::factory()->for($cancelledParticipation, 'event')->for($membership, 'membership')->create(['status' => EventParticipantStatus::Cancelled]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/organizations/current')
            ->assertOk()
            ->assertJsonPath('membership.participatedEventsCount', 1);
    }

    public function test_a_user_with_no_membership_gets_a_clear_error_instead_of_a_redirect()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/organizations/current')
            ->assertStatus(422)
            ->assertJsonStructure(['message']);
    }
}
