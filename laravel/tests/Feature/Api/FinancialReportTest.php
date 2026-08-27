<?php

namespace Tests\Feature\Api;

use App\Enums\FinancialReportStatus;
use App\Enums\OrganizationRole;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\FinancialReport;
use App\Models\FinancialTransaction;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinancialReportTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_a_plain_member_only_sees_published_members_or_public_reports()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);

        FinancialReport::factory()->create(['organization_id' => $organization->id]); // draft
        FinancialReport::factory()->published()->create(['organization_id' => $organization->id]);

        Sanctum::actingAs($member);

        $this->getJson('/api/v1/finance/reports')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_an_owner_can_publish_a_draft_report_but_a_treasurer_cannot()
    {
        $organization = Organization::factory()->create();
        $owner = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $report = FinancialReport::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);

        Sanctum::actingAs($treasurer);
        $this->postJson("/api/v1/finance/reports/{$report->id}/publish")->assertForbidden();

        Sanctum::actingAs($owner);
        $this->postJson("/api/v1/finance/reports/{$report->id}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', 'PUBLISHED');
    }

    public function test_an_owner_can_archive_a_published_report()
    {
        $organization = Organization::factory()->create();
        $owner = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $report = FinancialReport::factory()->published()->create(['organization_id' => $organization->id]);

        Sanctum::actingAs($owner);

        $this->postJson("/api/v1/finance/reports/{$report->id}/archive")
            ->assertOk()
            ->assertJsonPath('data.status', 'ARCHIVED');

        $this->assertSame(FinancialReportStatus::Archived, $report->fresh()->status);
    }

    public function test_a_guest_can_view_a_public_published_report_without_a_token()
    {
        $report = FinancialReport::factory()->published()->publicVisibility()->create();

        $this->getJson("/api/v1/finance/reports/{$report->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $report->id);

        $this->getJson("/api/v1/finance/reports/{$report->id}/share")
            ->assertOk()
            ->assertJsonStructure(['shareUrl', 'qrUrl']);

        $this->get("/api/v1/finance/reports/{$report->id}/qr")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    /**
     * `show()`/`share()`/`qr()` sit outside the `auth:sanctum` middleware
     * group (deliberately, so a guest can reach a PUBLIC report) — which
     * means `Sanctum::actingAs()` is the wrong tool for testing them: it
     * fakes the resolved user directly and would pass even if the real
     * HTTP request's Bearer token was never actually parsed into a user.
     * A real token via `withToken()` exercises the actual guard resolution
     * these three actions depend on.
     */
    public function test_a_treasurer_with_a_real_bearer_token_can_view_their_orgs_draft_report()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $token = $treasurer->createToken('test')->plainTextToken;
        $report = FinancialReport::factory()->create(['organization_id' => $organization->id]); // draft, MEMBERS visibility

        $this->withToken($token)
            ->getJson("/api/v1/finance/reports/{$report->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $report->id);
    }

    public function test_a_guest_cannot_view_a_members_only_report()
    {
        $report = FinancialReport::factory()->published()->create(); // default visibility is MEMBERS

        $this->getJson("/api/v1/finance/reports/{$report->id}")->assertNotFound();
    }

    public function test_a_member_from_another_organization_cannot_view_a_private_report()
    {
        $report = FinancialReport::factory()->published()->privateVisibility()->create();
        $outsider = User::factory()->create();
        $outsider->memberships()->create([
            'organization_id' => Organization::factory()->create()->id,
            'role' => OrganizationRole::Ketua,
        ]);

        Sanctum::actingAs($outsider);

        $this->getJson("/api/v1/finance/reports/{$report->id}")->assertForbidden();
    }

    public function test_show_includes_income_and_expense_grouped_by_category()
    {
        $organization = Organization::factory()->create();
        $owner = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $report = FinancialReport::factory()->published()->publicVisibility()->create([
            'organization_id' => $organization->id,
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
        ]);

        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);
        $duesCategory = FinancialCategory::factory()->create(['organization_id' => $organization->id, 'name' => 'Iuran anggota', 'transaction_type' => TransactionType::Income]);
        $foodCategory = FinancialCategory::factory()->create(['organization_id' => $organization->id, 'name' => 'Konsumsi', 'transaction_type' => TransactionType::Expense]);

        FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'financial_account_id' => $account->id,
            'category_id' => $duesCategory->id,
            'transaction_type' => TransactionType::Income,
            'status' => TransactionStatus::Approved,
            'amount' => 500_000,
            'transaction_date' => '2026-08-10',
        ]);
        FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'financial_account_id' => $account->id,
            'category_id' => $foodCategory->id,
            'transaction_type' => TransactionType::Expense,
            'status' => TransactionStatus::Approved,
            'amount' => 200_000,
            'transaction_date' => '2026-08-12',
        ]);
        // Outside the report period — must not be counted.
        FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'financial_account_id' => $account->id,
            'category_id' => $duesCategory->id,
            'transaction_type' => TransactionType::Income,
            'status' => TransactionStatus::Approved,
            'amount' => 999_999,
            'transaction_date' => '2026-07-01',
        ]);

        Sanctum::actingAs($owner);

        $this->getJson("/api/v1/finance/reports/{$report->id}")
            ->assertOk()
            ->assertJsonPath('categoryBreakdown.income.0.label', 'Iuran anggota')
            ->assertJsonPath('categoryBreakdown.income.0.amount', 500_000)
            ->assertJsonPath('categoryBreakdown.expense.0.label', 'Konsumsi')
            ->assertJsonPath('categoryBreakdown.expense.0.amount', 200_000);
    }
}
