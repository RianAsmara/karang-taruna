<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\TransactionType;
use App\Models\FinancialAccount;
use App\Models\FinancialReport;
use App\Models\FinancialTransaction;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransparencyTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_the_dashboard_shows_the_current_balance_to_any_member()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Member);
        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);

        FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'financial_account_id' => $account->id,
            'transaction_type' => TransactionType::Income,
            'amount' => 1_000_000,
            'created_by' => $member->id,
        ]);
        FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'financial_account_id' => $account->id,
            'transaction_type' => TransactionType::Expense,
            'amount' => 200_000,
            'created_by' => $member->id,
        ]);

        $response = $this->actingAs($member)->get('/transparansi');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('balance', 800_000));
    }

    public function test_only_the_owner_can_toggle_public_transparency()
    {
        $organization = Organization::factory()->create();
        $owner = $this->memberWithRole($organization, OrganizationRole::Owner);
        $admin = $this->memberWithRole($organization, OrganizationRole::Admin);

        $this->actingAs($admin)->post('/transparansi/toggle-public')->assertForbidden();

        $this->actingAs($owner)->post('/transparansi/toggle-public')->assertRedirect();
        $this->assertTrue($organization->fresh()->public_transparency_enabled);
    }

    public function test_the_public_transparency_page_404s_when_disabled()
    {
        $organization = Organization::factory()->create(['public_transparency_enabled' => false]);

        $this->get("/org/{$organization->slug}/transparency")->assertNotFound();
    }

    public function test_the_public_transparency_page_shows_only_public_published_reports()
    {
        $organization = Organization::factory()->create(['public_transparency_enabled' => true]);

        $publicReport = FinancialReport::factory()->published()->publicVisibility()->create([
            'organization_id' => $organization->id,
            'title' => 'Laporan Publik',
        ]);
        FinancialReport::factory()->published()->create([
            'organization_id' => $organization->id,
            'title' => 'Laporan Anggota Saja',
            'visibility' => 'MEMBERS',
        ]);
        FinancialReport::factory()->create([
            'organization_id' => $organization->id,
            'title' => 'Masih Draf',
        ]);

        $response = $this->get("/org/{$organization->slug}/transparency");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('publicReports', 1)
            ->where('publicReports.0.title', $publicReport->title)
        );
    }
}
