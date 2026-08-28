<?php

namespace Tests\Feature\Api;

use App\Enums\EventStatus;
use App\Enums\OrganizationRole;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_a_member_can_check_in_to_an_ongoing_event()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $event = Event::factory()->create(['organization_id' => $organization->id, 'status' => EventStatus::Ongoing]);

        Sanctum::actingAs($member);

        $this->postJson("/api/v1/events/{$event->id}/attendance")->assertOk();

        $this->assertSame(1, Attendance::count());
    }

    public function test_a_member_cannot_check_in_twice()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $event = Event::factory()->create(['organization_id' => $organization->id, 'status' => EventStatus::Ongoing]);

        Sanctum::actingAs($member);

        $this->postJson("/api/v1/events/{$event->id}/attendance")->assertOk();
        $this->postJson("/api/v1/events/{$event->id}/attendance")->assertUnprocessable();

        $this->assertSame(1, Attendance::count());
    }

    public function test_a_member_cannot_check_in_to_an_event_that_isnt_ongoing()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $event = Event::factory()->create(['organization_id' => $organization->id, 'status' => EventStatus::Planned]);

        Sanctum::actingAs($member);

        $this->postJson("/api/v1/events/{$event->id}/attendance")->assertForbidden();
    }

    public function test_the_event_detail_reflects_my_attendance_status()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $event = Event::factory()->create(['organization_id' => $organization->id, 'status' => EventStatus::Ongoing]);

        Sanctum::actingAs($member);

        $this->getJson("/api/v1/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('data.attendance.myStatus', null)
            ->assertJsonPath('data.attendance.canCheckIn', true);

        $this->postJson("/api/v1/events/{$event->id}/attendance");

        $this->getJson("/api/v1/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('data.attendance.myStatus', 'HADIR')
            ->assertJsonPath('data.attendance.count', 1);
    }

    public function test_only_the_chair_or_pic_can_list_or_view_the_qr_for_attendance()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $event = Event::factory()->create(['organization_id' => $organization->id, 'status' => EventStatus::Ongoing]);

        Sanctum::actingAs($member);
        $this->postJson("/api/v1/events/{$event->id}/attendance")->assertOk();

        Sanctum::actingAs($chair);
        $this->getJson("/api/v1/events/{$event->id}/attendance")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', $member->name);
        $this->get("/api/v1/events/{$event->id}/attendance/qr")->assertOk()->assertHeader('Content-Type', 'image/png');

        Sanctum::actingAs($member);
        $this->getJson("/api/v1/events/{$event->id}/attendance")->assertForbidden();
        $this->get("/api/v1/events/{$event->id}/attendance/qr")->assertForbidden();
    }

    public function test_a_member_from_another_organization_cannot_check_in_to_this_events()
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $organization->id, 'status' => EventStatus::Ongoing]);

        $otherOrganization = Organization::factory()->create();
        $outsider = $this->memberWithRole($otherOrganization, OrganizationRole::Ketua);

        Sanctum::actingAs($outsider);

        $this->postJson("/api/v1/events/{$event->id}/attendance")->assertForbidden();
    }

    public function test_a_second_check_in_attempt_reuses_the_same_attendance_session()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $other = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $event = Event::factory()->create(['organization_id' => $organization->id, 'status' => EventStatus::Ongoing]);

        Sanctum::actingAs($member);
        $this->postJson("/api/v1/events/{$event->id}/attendance")->assertOk();

        Sanctum::actingAs($other);
        $this->postJson("/api/v1/events/{$event->id}/attendance")->assertOk();

        $this->assertSame(1, AttendanceSession::where('event_id', $event->id)->count());
        $this->assertSame(2, Attendance::count());
    }
}
