<?php

namespace Tests\Feature\Api;

use App\Enums\OrganizationRole;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_a_member_can_list_events_in_their_organization()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Member);
        Event::factory()->count(2)->create(['organization_id' => $organization->id]);

        Sanctum::actingAs($member);

        $this->getJson('/api/v1/events')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_only_an_organizer_can_create_an_event()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Member);

        Sanctum::actingAs($member);

        $this->postJson('/api/v1/events', [
            'title' => 'Rapat Bulanan',
            'start_at' => now()->addWeek()->toIso8601String(),
        ])->assertForbidden();
    }

    public function test_an_owner_can_create_and_then_view_an_event()
    {
        $organization = Organization::factory()->create();
        $owner = $this->memberWithRole($organization, OrganizationRole::Owner);

        Sanctum::actingAs($owner);

        $created = $this->postJson('/api/v1/events', [
            'title' => 'Rapat Bulanan',
            'start_at' => now()->addWeek()->toIso8601String(),
        ])->assertCreated();

        $eventId = $created->json('data.id');

        $this->getJson("/api/v1/events/{$eventId}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Rapat Bulanan');
    }

    public function test_the_pic_may_update_their_own_event_but_a_plain_member_may_not()
    {
        $organization = Organization::factory()->create();
        $owner = $this->memberWithRole($organization, OrganizationRole::Owner);
        $event = Event::factory()->create(['organization_id' => $organization->id]);

        $outsiderMember = $this->memberWithRole($organization, OrganizationRole::Member);
        Sanctum::actingAs($outsiderMember);

        $this->patchJson("/api/v1/events/{$event->id}", [
            'title' => 'Judul Baru',
            'start_at' => $event->start_at->toIso8601String(),
            'status' => $event->status->value,
            'lifecycle_stage' => $event->lifecycle_stage->value,
        ])->assertForbidden();

        Sanctum::actingAs($owner);

        $this->patchJson("/api/v1/events/{$event->id}", [
            'title' => 'Judul Baru',
            'start_at' => $event->start_at->toIso8601String(),
            'status' => $event->status->value,
            'lifecycle_stage' => $event->lifecycle_stage->value,
        ])->assertOk()->assertJsonPath('data.title', 'Judul Baru');
    }

    public function test_a_member_from_another_organization_cannot_view_this_event()
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $organization->id]);

        $otherOrganization = Organization::factory()->create();
        $outsider = $this->memberWithRole($otherOrganization, OrganizationRole::Owner);

        Sanctum::actingAs($outsider);

        $this->getJson("/api/v1/events/{$event->id}")->assertForbidden();
    }
}
