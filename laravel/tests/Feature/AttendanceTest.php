<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\OrganizationRole;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_a_member_can_confirm_their_own_attendance_at_an_ongoing_event()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $event = Event::factory()->create(['organization_id' => $organization->id, 'status' => EventStatus::Ongoing]);

        $this->actingAs($member)
            ->post("/events/{$event->id}/attendance")
            ->assertRedirect();

        $membership = $organization->memberships()->where('user_id', $member->id)->first();
        $this->assertDatabaseHas('attendances', [
            'membership_id' => $membership->id,
            'method' => 'MANUAL',
        ]);
    }

    public function test_a_member_cannot_check_in_twice()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $event = Event::factory()->create(['organization_id' => $organization->id, 'status' => EventStatus::Ongoing]);

        $this->actingAs($member)->post("/events/{$event->id}/attendance");

        $this->actingAs($member)
            ->post("/events/{$event->id}/attendance")
            ->assertSessionHasErrors('attendance');

        $this->assertSame(1, Attendance::count());
    }

    public function test_a_member_cannot_check_in_when_the_event_is_not_ongoing()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $event = Event::factory()->create(['organization_id' => $organization->id, 'status' => EventStatus::Planned]);

        $this->actingAs($member)
            ->post("/events/{$event->id}/attendance")
            ->assertForbidden();
    }

    public function test_only_the_chair_or_pic_can_view_the_attendance_list()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $event = Event::factory()->create(['organization_id' => $organization->id, 'status' => EventStatus::Ongoing]);

        $this->actingAs($chair)
            ->get("/events/{$event->id}/attendance")
            ->assertOk();

        $this->actingAs($member)
            ->get("/events/{$event->id}/attendance")
            ->assertForbidden();
    }

    public function test_the_events_own_pic_can_manage_attendance_without_being_chair()
    {
        $organization = Organization::factory()->create();
        $pic = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $picMembership = $organization->memberships()->where('user_id', $pic->id)->first();
        $event = Event::factory()->create([
            'organization_id' => $organization->id,
            'status' => EventStatus::Ongoing,
            'pic_membership_id' => $picMembership->id,
        ]);

        $this->actingAs($pic)
            ->get("/events/{$event->id}/attendance")
            ->assertOk();
    }

    public function test_the_attendance_list_shows_who_checked_in()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $event = Event::factory()->create(['organization_id' => $organization->id, 'status' => EventStatus::Ongoing]);

        $this->actingAs($member)->post("/events/{$event->id}/attendance");

        $this->actingAs($chair)
            ->get("/events/{$event->id}/attendance")
            ->assertInertia(fn ($page) => $page
                ->has('attendances', 1)
                ->where('attendances.0.name', $member->name)
                ->where('attendances.0.method', 'MANUAL')
            );
    }

    public function test_only_the_chair_or_pic_can_view_the_attendance_qr()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $event = Event::factory()->create(['organization_id' => $organization->id, 'status' => EventStatus::Ongoing]);

        $this->actingAs($chair)
            ->get("/events/{$event->id}/attendance/qr")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $this->actingAs($member)
            ->get("/events/{$event->id}/attendance/qr")
            ->assertForbidden();

        $this->assertSame(1, AttendanceSession::where('event_id', $event->id)->count());
    }

    public function test_the_event_show_page_reflects_my_attendance_status()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $event = Event::factory()->create(['organization_id' => $organization->id, 'status' => EventStatus::Ongoing]);

        $this->actingAs($member)
            ->get("/events/{$event->id}")
            ->assertInertia(fn ($page) => $page
                ->where('attendance.myStatus', null)
                ->where('attendance.canCheckIn', true)
                ->where('attendance.count', 0)
            );

        $this->actingAs($member)->post("/events/{$event->id}/attendance");

        $this->actingAs($member)
            ->get("/events/{$event->id}")
            ->assertInertia(fn ($page) => $page
                ->where('attendance.myStatus', 'HADIR')
                ->where('attendance.count', 1)
            );
    }

    public function test_scanning_the_qr_code_lets_a_member_check_in()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $event = Event::factory()->create(['organization_id' => $organization->id, 'status' => EventStatus::Ongoing]);
        $session = AttendanceSession::create(['event_id' => $event->id, 'created_by' => $chair->id]);

        $this->actingAs($member)
            ->get("/attendance/{$session->qr_token}")
            ->assertInertia(fn ($page) => $page
                ->where('alreadyCheckedIn', false)
                ->where('canCheckIn', true)
            );

        $this->actingAs($member)
            ->post("/attendance/{$session->qr_token}")
            ->assertRedirect();

        $membership = $organization->memberships()->where('user_id', $member->id)->first();
        $this->assertDatabaseHas('attendances', [
            'membership_id' => $membership->id,
            'method' => 'QR',
        ]);

        $this->actingAs($member)
            ->get("/attendance/{$session->qr_token}")
            ->assertInertia(fn ($page) => $page->where('alreadyCheckedIn', true));
    }

    public function test_a_member_from_another_organization_cannot_use_a_qr_token_that_isnt_theirs()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $event = Event::factory()->create(['organization_id' => $organization->id, 'status' => EventStatus::Ongoing]);
        $session = AttendanceSession::create(['event_id' => $event->id, 'created_by' => $chair->id]);

        $otherOrganization = Organization::factory()->create();
        $outsider = $this->memberWithRole($otherOrganization, OrganizationRole::Ketua);

        $this->actingAs($outsider)
            ->get("/attendance/{$session->qr_token}")
            ->assertForbidden();

        $this->actingAs($outsider)
            ->post("/attendance/{$session->qr_token}")
            ->assertForbidden();
    }
}
