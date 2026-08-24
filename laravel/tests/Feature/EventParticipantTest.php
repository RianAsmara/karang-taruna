<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventParticipantTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_a_member_can_register_for_an_event()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Member);
        $event = Event::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($member)
            ->post("/events/{$event->id}/participants")
            ->assertRedirect();

        $membership = $organization->memberships()->firstWhere('user_id', $member->id);
        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'membership_id' => $membership->id,
        ]);
    }

    public function test_a_member_cannot_register_twice()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Member);
        $event = Event::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($member)->post("/events/{$event->id}/participants");

        $this->actingAs($member)
            ->post("/events/{$event->id}/participants")
            ->assertSessionHasErrors('participant');
    }

    public function test_a_member_can_cancel_their_own_registration_but_not_someone_elses()
    {
        $organization = Organization::factory()->create();
        $memberA = $this->memberWithRole($organization, OrganizationRole::Member);
        $memberB = $this->memberWithRole($organization, OrganizationRole::Member);
        $event = Event::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($memberA)->post("/events/{$event->id}/participants");
        $participant = $event->participants()->first();

        $this->actingAs($memberB)
            ->delete("/events/{$event->id}/participants/{$participant->id}")
            ->assertForbidden();

        $this->actingAs($memberA)
            ->delete("/events/{$event->id}/participants/{$participant->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('event_participants', ['id' => $participant->id]);
    }
}
