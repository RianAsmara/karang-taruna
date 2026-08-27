<?php

namespace Tests\Feature;

use App\Enums\FinancialReportStatus;
use App\Enums\FinancialReportVisibility;
use App\Enums\OrganizationRole;
use App\Models\FinancialReport;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperadminWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_is_redirected_to_login()
    {
        $this->get('/superadmin/organizations')->assertRedirect('/login');
    }

    public function test_a_non_superadmin_cannot_reach_the_superadmin_pages()
    {
        $user = User::factory()->create(['is_superadmin' => false]);

        $this->actingAs($user)->get('/superadmin/organizations')->assertForbidden();
    }

    public function test_a_superadmin_can_list_every_organization()
    {
        $superadmin = User::factory()->create(['is_superadmin' => true]);
        Organization::factory()->count(3)->create();

        $response = $this->actingAs($superadmin)->get('/superadmin/organizations');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('superadmin/organizations/index')->has('organizations', 3));
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

        $response = $this->actingAs($superadmin)->get("/superadmin/organizations/{$organization->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('superadmin/organizations/show')
            ->where('organization.id', $organization->id)
            ->has('members', 1)
            ->has('reports', 1)
            ->where('reports.0.status', 'DRAFT')
        );
    }

    public function test_viewing_an_organization_writes_an_audit_log_entry()
    {
        $superadmin = User::factory()->create(['is_superadmin' => true]);
        $organization = Organization::factory()->create();

        $this->actingAs($superadmin)->get("/superadmin/organizations/{$organization->id}")->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'actor_id' => $superadmin->id,
            'action' => 'superadmin.viewed_organization',
        ]);
    }

    /**
     * The sidebar link itself is client-rendered React, not observable from
     * a server-side HTTP test (no SSR running here) — what's actually
     * testable, and what the sidebar's conditional render depends on, is
     * that `auth.user.is_superadmin` is present and correct in the shared
     * Inertia props on every page.
     */
    public function test_the_is_superadmin_flag_is_present_in_shared_props()
    {
        $superadmin = User::factory()->create(['is_superadmin' => true]);
        $user = User::factory()->create(['is_superadmin' => false]);

        $this->actingAs($superadmin)->get('/dashboard')
            ->assertInertia(fn ($page) => $page->where('auth.user.is_superadmin', true));

        $this->actingAs($user)->get('/dashboard')
            ->assertInertia(fn ($page) => $page->where('auth.user.is_superadmin', false));
    }
}
