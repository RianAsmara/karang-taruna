<?php

namespace Tests\Feature\Api;

use App\Enums\FinancialReportStatus;
use App\Enums\FinancialReportVisibility;
use App\Enums\OrganizationRole;
use App\Models\FinancialReport;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperadminTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_non_superadmin_cannot_reach_any_superadmin_route()
    {
        $user = User::factory()->create(['is_superadmin' => false]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/superadmin/organizations')->assertForbidden();
    }

    public function test_an_unauthenticated_request_cannot_reach_superadmin_routes()
    {
        $this->getJson('/api/v1/superadmin/organizations')->assertUnauthorized();
    }

    public function test_a_superadmin_with_no_membership_anywhere_can_list_every_organization()
    {
        $superadmin = User::factory()->create(['is_superadmin' => true]);
        Organization::factory()->count(3)->create();

        Sanctum::actingAs($superadmin);

        $this->getJson('/api/v1/superadmin/organizations')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_a_superadmin_can_view_full_detail_of_an_organization_they_are_not_a_member_of()
    {
        $superadmin = User::factory()->create(['is_superadmin' => true]);
        $organization = Organization::factory()->create();
        $member = User::factory()->create();
        $organization->memberships()->create(['user_id' => $member->id, 'role' => OrganizationRole::Anggota]);

        // A DRAFT + PRIVATE report — normally invisible to anyone but the
        // treasury team of that specific organization.
        FinancialReport::factory()->create([
            'organization_id' => $organization->id,
            'status' => FinancialReportStatus::Draft,
            'visibility' => FinancialReportVisibility::Private,
        ]);

        Sanctum::actingAs($superadmin);

        $this->getJson("/api/v1/superadmin/organizations/{$organization->id}")
            ->assertOk()
            ->assertJsonPath('organization.id', $organization->id)
            ->assertJsonCount(1, 'members.data')
            ->assertJsonCount(1, 'reports.data')
            ->assertJsonPath('reports.data.0.status', 'DRAFT');
    }

    public function test_viewing_an_organization_writes_an_audit_log_entry()
    {
        $superadmin = User::factory()->create(['is_superadmin' => true]);
        $organization = Organization::factory()->create();

        Sanctum::actingAs($superadmin);

        $this->getJson("/api/v1/superadmin/organizations/{$organization->id}")->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'actor_id' => $superadmin->id,
            'action' => 'superadmin.viewed_organization',
        ]);
    }

    public function test_a_superadmin_still_cannot_write_to_an_organization_they_are_not_a_member_of()
    {
        $superadmin = User::factory()->create(['is_superadmin' => true]);
        $organization = Organization::factory()->create();

        Sanctum::actingAs($superadmin);

        // No 'current-org' context exists for this user at all, so any
        // normal per-org write route 422s exactly like it would for any
        // other member-less user — superadmin grants no write access.
        $this->postJson('/api/v1/members', ['email' => 'x@example.com', 'role' => 'ANGGOTA'])
            ->assertStatus(422);
    }

    public function test_is_superadmin_cannot_be_set_via_mass_assignment()
    {
        $user = User::factory()->create(['is_superadmin' => false]);

        // Simulates an attempt to smuggle the flag through any endpoint
        // that mass-assigns User attributes from request input.
        $user->update(['is_superadmin' => true]);

        $this->assertFalse($user->fresh()->is_superadmin);
    }
}
