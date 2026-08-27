<?php

namespace Tests\Feature\Api;

use App\Enums\OrganizationRole;
use App\Models\Event;
use App\Models\EventTask;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventTaskTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_a_member_can_list_an_events_tasks()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $event = Event::factory()->create(['organization_id' => $organization->id]);
        EventTask::factory()->count(3)->create(['event_id' => $event->id]);

        Sanctum::actingAs($member);

        $this->getJson("/api/v1/events/{$event->id}/tasks")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_the_event_manager_can_create_a_task_but_a_plain_member_cannot()
    {
        $organization = Organization::factory()->create();
        $owner = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $event = Event::factory()->create(['organization_id' => $organization->id]);

        Sanctum::actingAs($member);

        $this->postJson("/api/v1/events/{$event->id}/tasks", [
            'title' => 'Booking tempat',
            'priority' => 'MEDIUM',
        ])->assertForbidden();

        Sanctum::actingAs($owner);

        $this->postJson("/api/v1/events/{$event->id}/tasks", [
            'title' => 'Booking tempat',
            'priority' => 'MEDIUM',
        ])->assertCreated()->assertJsonPath('data.title', 'Booking tempat');
    }

    public function test_the_assignee_can_update_their_own_task_status_but_another_member_cannot()
    {
        $organization = Organization::factory()->create();
        $assignee = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $other = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $event = Event::factory()->create(['organization_id' => $organization->id]);
        $assigneeMembership = $assignee->memberships()->first();
        $task = EventTask::factory()->create([
            'event_id' => $event->id,
            'assignee_membership_id' => $assigneeMembership->id,
        ]);

        Sanctum::actingAs($other);

        $this->patchJson("/api/v1/events/{$event->id}/tasks/{$task->id}/status", ['status' => 'DONE'])
            ->assertForbidden();

        Sanctum::actingAs($assignee);

        $this->patchJson("/api/v1/events/{$event->id}/tasks/{$task->id}/status", ['status' => 'DONE'])
            ->assertOk()
            ->assertJsonPath('data.status', 'DONE');
    }
}
