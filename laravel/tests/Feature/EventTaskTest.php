<?php

namespace Tests\Feature;

use App\Enums\EventTaskStatus;
use App\Enums\OrganizationRole;
use App\Models\Event;
use App\Models\EventTask;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_owner_can_create_a_task_on_an_event()
    {
        $organization = Organization::factory()->create();
        $owner = $this->memberWithRole($organization, OrganizationRole::Owner);
        $event = Event::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($owner)
            ->post("/events/{$event->id}/tasks", [
                'title' => 'Pesan konsumsi',
                'priority' => 'MEDIUM',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('event_tasks', ['event_id' => $event->id, 'title' => 'Pesan konsumsi']);
    }

    public function test_a_plain_member_cannot_create_a_task()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Member);
        $event = Event::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($member)
            ->post("/events/{$event->id}/tasks", [
                'title' => 'Pesan konsumsi',
                'priority' => 'MEDIUM',
            ])
            ->assertForbidden();
    }

    public function test_assignee_can_update_their_own_task_status_but_not_reassign_it()
    {
        $organization = Organization::factory()->create();
        $assignee = $this->memberWithRole($organization, OrganizationRole::Member);
        $assigneeMembership = $organization->memberships()->firstWhere('user_id', $assignee->id);

        $event = Event::factory()->create(['organization_id' => $organization->id]);
        $task = EventTask::factory()->create([
            'event_id' => $event->id,
            'assignee_membership_id' => $assigneeMembership->id,
        ]);

        $this->actingAs($assignee)
            ->patch("/events/{$event->id}/tasks/{$task->id}/status", ['status' => EventTaskStatus::Done->value])
            ->assertRedirect();

        $this->assertSame(EventTaskStatus::Done, $task->fresh()->status);

        $this->actingAs($assignee)
            ->patch("/events/{$event->id}/tasks/{$task->id}", [
                'title' => 'Diubah paksa',
                'priority' => 'HIGH',
                'status' => EventTaskStatus::Done->value,
            ])
            ->assertForbidden();
    }

    public function test_a_non_assignee_member_cannot_update_task_status()
    {
        $organization = Organization::factory()->create();
        $assignee = $this->memberWithRole($organization, OrganizationRole::Member);
        $assigneeMembership = $organization->memberships()->firstWhere('user_id', $assignee->id);
        $otherMember = $this->memberWithRole($organization, OrganizationRole::Member);

        $event = Event::factory()->create(['organization_id' => $organization->id]);
        $task = EventTask::factory()->create([
            'event_id' => $event->id,
            'assignee_membership_id' => $assigneeMembership->id,
        ]);

        $this->actingAs($otherMember)
            ->patch("/events/{$event->id}/tasks/{$task->id}/status", ['status' => EventTaskStatus::Done->value])
            ->assertForbidden();
    }
}
