<?php

namespace Tests\Feature;

use App\Enums\ActivityPointSource;
use App\Enums\EventStatus;
use App\Enums\EventTaskStatus;
use App\Enums\OrganizationRole;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\EventTask;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityPointsTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_checking_in_to_an_event_awards_activity_points()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $event = Event::factory()->create(['organization_id' => $organization->id, 'status' => EventStatus::Ongoing]);

        $this->actingAs($member)->post("/events/{$event->id}/attendance");

        $membership = $organization->memberships()->where('user_id', $member->id)->first();
        $this->assertDatabaseHas('activity_logs', [
            'membership_id' => $membership->id,
            'source' => ActivityPointSource::EventAttendance->value,
            'points' => ActivityPointSource::EventAttendance->points(),
        ]);
    }

    public function test_completing_a_task_awards_points_to_its_assignee()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $assignee = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $assigneeMembership = $organization->memberships()->where('user_id', $assignee->id)->first();
        $event = Event::factory()->create(['organization_id' => $organization->id]);
        $task = EventTask::factory()->create([
            'event_id' => $event->id,
            'assignee_membership_id' => $assigneeMembership->id,
            'status' => EventTaskStatus::Todo,
        ]);

        $this->actingAs($chair)
            ->patch("/events/{$event->id}/tasks/{$task->id}/status", ['status' => 'DONE'])
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'membership_id' => $assigneeMembership->id,
            'source' => ActivityPointSource::TaskCompleted->value,
            'points' => ActivityPointSource::TaskCompleted->points(),
        ]);
    }

    public function test_a_task_with_no_assignee_awards_no_points_when_completed()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $event = Event::factory()->create(['organization_id' => $organization->id]);
        $task = EventTask::factory()->create([
            'event_id' => $event->id,
            'assignee_membership_id' => null,
            'status' => EventTaskStatus::Todo,
        ]);

        $this->actingAs($chair)->patch("/events/{$event->id}/tasks/{$task->id}/status", ['status' => 'DONE']);

        $this->assertSame(0, ActivityLog::count());
    }

    public function test_toggling_a_task_done_and_back_and_done_again_only_awards_points_once()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $assignee = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $assigneeMembership = $organization->memberships()->where('user_id', $assignee->id)->first();
        $event = Event::factory()->create(['organization_id' => $organization->id]);
        $task = EventTask::factory()->create([
            'event_id' => $event->id,
            'assignee_membership_id' => $assigneeMembership->id,
            'status' => EventTaskStatus::Todo,
        ]);

        $this->actingAs($chair)->patch("/events/{$event->id}/tasks/{$task->id}/status", ['status' => 'DONE']);
        $this->actingAs($chair)->patch("/events/{$event->id}/tasks/{$task->id}/status", ['status' => 'TODO']);
        $this->actingAs($chair)->patch("/events/{$event->id}/tasks/{$task->id}/status", ['status' => 'DONE']);

        $this->assertSame(1, ActivityLog::where('membership_id', $assigneeMembership->id)->count());
    }
}
