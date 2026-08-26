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

class MyTasksTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_it_returns_only_tasks_assigned_to_the_current_user_across_every_event()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Member);
        $membership = $organization->memberships()->where('user_id', $member->id)->firstOrFail();
        $otherMember = $this->memberWithRole($organization, OrganizationRole::Member);
        $otherMembership = $organization->memberships()->where('user_id', $otherMember->id)->firstOrFail();

        $eventOne = Event::factory()->create(['organization_id' => $organization->id]);
        $eventTwo = Event::factory()->create(['organization_id' => $organization->id]);

        EventTask::factory()->create(['event_id' => $eventOne->id, 'assignee_membership_id' => $membership->id, 'title' => 'Task A']);
        EventTask::factory()->create(['event_id' => $eventTwo->id, 'assignee_membership_id' => $membership->id, 'title' => 'Task B']);
        EventTask::factory()->create(['event_id' => $eventOne->id, 'assignee_membership_id' => $otherMembership->id, 'title' => 'Not mine']);
        EventTask::factory()->create(['event_id' => $eventOne->id, 'assignee_membership_id' => null, 'title' => 'Unassigned']);

        Sanctum::actingAs($member);

        $response = $this->getJson('/api/v1/my/tasks')->assertOk()->assertJsonCount(2, 'data');
        $tasks = collect($response->json('data'));

        $this->assertEqualsCanonicalizing(['Task A', 'Task B'], $tasks->pluck('title')->all());
        $this->assertEqualsCanonicalizing(
            [$eventOne->id, $eventTwo->id],
            $tasks->pluck('event.id')->all(),
        );
    }

    public function test_a_member_with_no_assigned_tasks_gets_an_empty_list()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Member);

        Sanctum::actingAs($member);

        $this->getJson('/api/v1/my/tasks')->assertOk()->assertJsonCount(0, 'data');
    }
}
