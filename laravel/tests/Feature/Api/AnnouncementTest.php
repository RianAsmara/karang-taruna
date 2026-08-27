<?php

namespace Tests\Feature\Api;

use App\Enums\OrganizationRole;
use App\Models\Announcement;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
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

    public function test_a_member_can_list_and_view_announcements()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $announcement = Announcement::factory()->create(['organization_id' => $organization->id]);

        Sanctum::actingAs($member);

        $this->getJson('/api/v1/announcements')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', $announcement->title);

        $this->getJson("/api/v1/announcements/{$announcement->id}")
            ->assertOk()
            ->assertJsonPath('data.body', $announcement->body);
    }

    public function test_a_member_from_another_organization_cannot_view_an_announcement()
    {
        $announcement = Announcement::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $outsider = $this->memberWithRole($otherOrganization, OrganizationRole::Ketua);

        Sanctum::actingAs($outsider);

        $this->getJson("/api/v1/announcements/{$announcement->id}")->assertForbidden();
    }
}
