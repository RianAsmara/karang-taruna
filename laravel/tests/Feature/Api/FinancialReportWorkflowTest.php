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
use App\Notifications\FinancialReportReviewed;
use App\Notifications\ReportSubmittedForReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinancialReportWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_a_treasurer_can_submit_a_draft_report_for_review_and_the_chair_is_notified()
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $report = FinancialReport::factory()->create(['organization_id' => $organization->id]);

        Sanctum::actingAs($treasurer);

        $this->postJson("/api/v1/finance/reports/{$report->id}/submit", ['note' => 'Sudah lengkap.'])
            ->assertOk()
            ->assertJsonPath('data.status', FinancialReportStatus::Diperiksa->value)
            ->assertJsonPath('data.treasurerNote', 'Sudah lengkap.');

        Notification::assertSentTo($chair, ReportSubmittedForReview::class);
    }

    public function test_a_treasurer_can_save_the_note_on_a_draft_report_without_submitting_it()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $report = FinancialReport::factory()->create(['organization_id' => $organization->id]);

        Sanctum::actingAs($treasurer);

        $this->patchJson("/api/v1/finance/reports/{$report->id}/note", ['note' => 'Masih menunggu bukti.'])
            ->assertOk()
            ->assertJsonPath('data.status', FinancialReportStatus::Draft->value)
            ->assertJsonPath('data.treasurerNote', 'Masih menunggu bukti.');
    }

    public function test_a_plain_member_cannot_submit_a_report()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $report = FinancialReport::factory()->create(['organization_id' => $organization->id]);

        Sanctum::actingAs($member);

        $this->postJson("/api/v1/finance/reports/{$report->id}/submit")->assertForbidden();
    }

    public function test_the_chair_can_approve_a_submitted_report_and_the_treasurer_is_notified()
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $report = FinancialReport::factory()->create([
            'organization_id' => $organization->id,
            'status' => FinancialReportStatus::Diperiksa,
            'submitted_by' => $treasurer->id,
        ]);

        Sanctum::actingAs($chair);

        $this->postJson("/api/v1/finance/reports/{$report->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', FinancialReportStatus::Disetujui->value);

        Notification::assertSentTo($treasurer, FinancialReportReviewed::class);
    }

    public function test_a_treasurer_cannot_approve_their_own_submission()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $report = FinancialReport::factory()->create([
            'organization_id' => $organization->id,
            'status' => FinancialReportStatus::Diperiksa,
            'submitted_by' => $treasurer->id,
        ]);

        Sanctum::actingAs($treasurer);

        $this->postJson("/api/v1/finance/reports/{$report->id}/approve")->assertForbidden();
    }

    public function test_the_chair_can_request_a_revision_with_a_reason_and_the_treasurer_is_notified()
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $report = FinancialReport::factory()->create([
            'organization_id' => $organization->id,
            'status' => FinancialReportStatus::Diperiksa,
            'submitted_by' => $treasurer->id,
        ]);

        Sanctum::actingAs($chair);

        $this->postJson("/api/v1/finance/reports/{$report->id}/request-revision", [
            'reason' => '2 transaksi belum punya bukti.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', FinancialReportStatus::Draft->value)
            ->assertJsonPath('data.revisionReason', '2 transaksi belum punya bukti.');

        Notification::assertSentTo($treasurer, FinancialReportReviewed::class);
    }

    public function test_requesting_a_revision_without_a_reason_is_rejected()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $report = FinancialReport::factory()->create([
            'organization_id' => $organization->id,
            'status' => FinancialReportStatus::Diperiksa,
        ]);

        Sanctum::actingAs($chair);

        $this->postJson("/api/v1/finance/reports/{$report->id}/request-revision")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');
    }

    public function test_a_report_awaiting_review_cannot_be_published_directly()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $report = FinancialReport::factory()->create([
            'organization_id' => $organization->id,
            'status' => FinancialReportStatus::Diperiksa,
        ]);

        Sanctum::actingAs($chair);

        $this->postJson("/api/v1/finance/reports/{$report->id}/publish")->assertForbidden();
    }

    public function test_an_approved_report_can_be_published()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $report = FinancialReport::factory()->create([
            'organization_id' => $organization->id,
            'status' => FinancialReportStatus::Disetujui,
        ]);

        Sanctum::actingAs($chair);

        $this->postJson("/api/v1/finance/reports/{$report->id}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', FinancialReportStatus::Published->value);
    }

    public function test_the_chair_can_still_publish_a_draft_report_directly_when_composing_it_themselves()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $report = FinancialReport::factory()->create(['organization_id' => $organization->id]);

        Sanctum::actingAs($chair);

        $this->postJson("/api/v1/finance/reports/{$report->id}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', FinancialReportStatus::Published->value);
    }

    public function test_viewing_an_approved_report_whose_transactions_changed_since_reverts_it_to_draft()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);
        $category = FinancialCategory::factory()->create([
            'organization_id' => $organization->id,
            'transaction_type' => TransactionType::Income,
        ]);
        $report = FinancialReport::factory()->create([
            'organization_id' => $organization->id,
            'status' => FinancialReportStatus::Disetujui,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'opening_balance' => 0,
            'total_income' => 0,
            'total_expense' => 0,
            'closing_balance' => 0,
        ]);

        // A transaction approved after the report was reviewed changes its figures.
        FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'financial_account_id' => $account->id,
            'category_id' => $category->id,
            'transaction_type' => TransactionType::Income,
            'status' => TransactionStatus::Approved,
            'amount' => 250000,
            'transaction_date' => now(),
        ]);

        Sanctum::actingAs($chair);

        $this->getJson("/api/v1/finance/reports/{$report->id}")
            ->assertOk()
            ->assertJsonPath('data.status', FinancialReportStatus::Draft->value)
            ->assertJsonPath('data.revisionReason', 'Ada transaksi yang berubah setelah disetujui.')
            ->assertJsonPath('data.totalIncome', 250000);
    }
}
