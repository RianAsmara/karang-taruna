<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_owner_can_create_an_event_and_is_added_to_the_committee()
    {
        $organization = Organization::factory()->create();
        $owner = $this->memberWithRole($organization, OrganizationRole::Owner);

        $this->actingAs($owner)
            ->post('/events', [
                'title' => 'Rapat Bulanan',
                'start_at' => now()->addWeek()->toDateTimeString(),
            ])
            ->assertRedirect();

        $event = Event::firstWhere('title', 'Rapat Bulanan');

        $this->assertNotNull($event);
        $this->assertSame($organization->id, $event->organization_id);
        $this->assertTrue(
            $event->committees()->whereHas('membership', fn ($q) => $q->where('user_id', $owner->id))->exists()
        );
    }

    public function test_a_plain_member_cannot_create_an_event()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Member);

        $this->actingAs($member)
            ->post('/events', [
                'title' => 'Rapat Bulanan',
                'start_at' => now()->addWeek()->toDateTimeString(),
            ])
            ->assertForbidden();
    }

    public function test_event_pic_can_update_the_event_but_not_delete_it()
    {
        $organization = Organization::factory()->create();
        $pic = $this->memberWithRole($organization, OrganizationRole::Committee);
        $picMembership = $organization->memberships()->firstWhere('user_id', $pic->id);

        $event = Event::factory()->create([
            'organization_id' => $organization->id,
            'pic_membership_id' => $picMembership->id,
        ]);

        $this->actingAs($pic)
            ->patch("/events/{$event->id}", [
                'title' => 'Judul Baru',
                'start_at' => $event->start_at->toDateTimeString(),
                'status' => $event->status->value,
                'lifecycle_stage' => $event->lifecycle_stage->value,
            ])
            ->assertRedirect();

        $this->assertSame('Judul Baru', $event->fresh()->title);

        $this->actingAs($pic)
            ->delete("/events/{$event->id}")
            ->assertForbidden();
    }

    public function test_a_member_from_another_organization_cannot_view_the_event()
    {
        $event = Event::factory()->create();
        $outsider = User::factory()->create();
        $outsider->memberships()->create([
            'organization_id' => Organization::factory()->create()->id,
            'role' => OrganizationRole::Owner,
        ]);

        $this->actingAs($outsider)
            ->get("/events/{$event->id}")
            ->assertForbidden();
    }
}
