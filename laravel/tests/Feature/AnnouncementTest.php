<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Announcement;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_secretary_can_publish_an_announcement()
    {
        $organization = Organization::factory()->create();
        $secretary = $this->memberWithRole($organization, OrganizationRole::Sekretaris);

        $this->actingAs($secretary)
            ->post('/announcements', [
                'title' => 'Iuran bulan Agustus',
                'body' => 'Mohon segera membayar iuran bulan Agustus.',
                'published_at' => now()->toDateTimeString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('announcements', [
            'organization_id' => $organization->id,
            'title' => 'Iuran bulan Agustus',
        ]);
    }

    public function test_a_plain_member_cannot_publish_an_announcement()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);

        $this->actingAs($member)
            ->post('/announcements', [
                'title' => 'Iuran bulan Agustus',
                'body' => 'Mohon segera membayar iuran bulan Agustus.',
            ])
            ->assertForbidden();
    }

    public function test_any_member_can_read_a_published_announcement()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $announcement = Announcement::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($member)
            ->get("/announcements/{$announcement->id}")
            ->assertOk();
    }

    public function test_a_member_from_another_organization_cannot_read_the_announcement()
    {
        $announcement = Announcement::factory()->create();
        $outsider = User::factory()->create();
        $outsider->memberships()->create([
            'organization_id' => Organization::factory()->create()->id,
            'role' => OrganizationRole::Ketua,
        ]);

        $this->actingAs($outsider)
            ->get("/announcements/{$announcement->id}")
            ->assertForbidden();
    }
}
