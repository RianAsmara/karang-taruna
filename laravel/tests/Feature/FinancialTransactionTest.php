<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialTransactionTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_treasurer_can_create_a_draft_transaction()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);
        $category = FinancialCategory::factory()->create([
            'organization_id' => $organization->id,
            'transaction_type' => TransactionType::Income,
        ]);

        $this->actingAs($treasurer)
            ->post('/finance/transactions', [
                'financial_account_id' => $account->id,
                'category_id' => $category->id,
                'amount' => 350_000,
                'transaction_type' => TransactionType::Income->value,
                'transaction_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $transaction = FinancialTransaction::firstWhere('financial_account_id', $account->id);

        $this->assertNotNull($transaction);
        $this->assertSame(TransactionStatus::Draft, $transaction->status);
        $this->assertSame(350_000, $transaction->amount);
    }

    public function test_a_plain_member_cannot_create_a_transaction()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($member)
            ->post('/finance/transactions', [
                'financial_account_id' => $account->id,
                'amount' => 100_000,
                'transaction_type' => TransactionType::Expense->value,
                'transaction_date' => now()->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_a_category_of_the_wrong_transaction_type_is_rejected()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);
        $expenseCategory = FinancialCategory::factory()->create([
            'organization_id' => $organization->id,
            'transaction_type' => TransactionType::Expense,
        ]);

        $this->actingAs($treasurer)
            ->post('/finance/transactions', [
                'financial_account_id' => $account->id,
                'category_id' => $expenseCategory->id,
                'amount' => 100_000,
                'transaction_type' => TransactionType::Income->value,
                'transaction_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('category_id');
    }

    public function test_submitting_a_draft_goes_to_pending_when_approval_is_required()
    {
        $organization = Organization::factory()->create(['require_transaction_approval' => true]);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $transaction = FinancialTransaction::factory()->draft()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);

        $this->actingAs($treasurer)
            ->post("/finance/transactions/{$transaction->id}/submit")
            ->assertRedirect();

        $this->assertSame(TransactionStatus::Pending, $transaction->fresh()->status);
    }

    public function test_submitting_a_draft_auto_approves_when_approval_is_not_required()
    {
        $organization = Organization::factory()->create(['require_transaction_approval' => false]);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $transaction = FinancialTransaction::factory()->draft()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);

        $this->actingAs($treasurer)->post("/finance/transactions/{$transaction->id}/submit");

        $fresh = $transaction->fresh();
        $this->assertSame(TransactionStatus::Approved, $fresh->status);
        $this->assertNull($fresh->reviewed_by);
    }

    public function test_owner_can_approve_a_pending_transaction_but_not_their_own()
    {
        $organization = Organization::factory()->create(['require_transaction_approval' => true]);
        $owner = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);

        $othersTransaction = FinancialTransaction::factory()->pending()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);

        $this->actingAs($owner)
            ->post("/finance/transactions/{$othersTransaction->id}/approve")
            ->assertRedirect();

        $fresh = $othersTransaction->fresh();
        $this->assertSame(TransactionStatus::Approved, $fresh->status);
        $this->assertSame($owner->id, $fresh->reviewed_by);

        $ownTransaction = FinancialTransaction::factory()->pending()->create([
            'organization_id' => $organization->id,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->post("/finance/transactions/{$ownTransaction->id}/approve")
            ->assertForbidden();
    }

    public function test_treasurer_cannot_approve_transactions()
    {
        $organization = Organization::factory()->create(['require_transaction_approval' => true]);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $otherTreasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);

        $transaction = FinancialTransaction::factory()->pending()->create([
            'organization_id' => $organization->id,
            'created_by' => $otherTreasurer->id,
        ]);

        $this->actingAs($treasurer)
            ->post("/finance/transactions/{$transaction->id}/approve")
            ->assertForbidden();
    }

    public function test_owner_can_reject_a_pending_transaction()
    {
        $organization = Organization::factory()->create(['require_transaction_approval' => true]);
        $owner = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);

        $transaction = FinancialTransaction::factory()->pending()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);

        $this->actingAs($owner)->post("/finance/transactions/{$transaction->id}/reject");

        $this->assertSame(TransactionStatus::Rejected, $transaction->fresh()->status);
    }

    public function test_an_approved_transaction_cannot_be_edited_or_deleted()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);
        $transaction = FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'financial_account_id' => $account->id,
            'created_by' => $treasurer->id,
        ]);

        $this->actingAs($treasurer)
            ->patch("/finance/transactions/{$transaction->id}", [
                'financial_account_id' => $account->id,
                'amount' => 999,
                'transaction_type' => $transaction->transaction_type->value,
                'transaction_date' => now()->toDateString(),
            ])
            ->assertForbidden();

        $this->actingAs($treasurer)
            ->delete("/finance/transactions/{$transaction->id}")
            ->assertForbidden();
    }

    public function test_a_plain_member_only_sees_approved_transactions()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);

        $approved = FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);
        $draft = FinancialTransaction::factory()->draft()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);

        $this->actingAs($member)->get("/finance/transactions/{$approved->id}")->assertOk();
        $this->actingAs($member)->get("/finance/transactions/{$draft->id}")->assertForbidden();
    }

    public function test_a_member_from_another_organization_cannot_view_the_transaction()
    {
        $transaction = FinancialTransaction::factory()->create();
        $outsider = User::factory()->create();
        $outsider->memberships()->create([
            'organization_id' => Organization::factory()->create()->id,
            'role' => OrganizationRole::Ketua,
        ]);

        $this->actingAs($outsider)
            ->get("/finance/transactions/{$transaction->id}")
            ->assertForbidden();
    }

    public function test_creating_and_approving_a_transaction_writes_an_audit_trail()
    {
        $organization = Organization::factory()->create(['require_transaction_approval' => true]);
        $owner = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);
        $category = FinancialCategory::factory()->create([
            'organization_id' => $organization->id,
            'transaction_type' => TransactionType::Income,
        ]);

        $this->actingAs($treasurer)->post('/finance/transactions', [
            'financial_account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 200_000,
            'transaction_type' => TransactionType::Income->value,
            'transaction_date' => now()->toDateString(),
        ]);

        $transaction = FinancialTransaction::firstWhere('financial_account_id', $account->id);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'action' => 'transaction.created',
            'model_id' => $transaction->id,
        ]);

        $this->actingAs($treasurer)->post("/finance/transactions/{$transaction->id}/submit");
        $this->actingAs($owner)->post("/finance/transactions/{$transaction->id}/approve");

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'action' => 'transaction.submitted',
            'model_id' => $transaction->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'action' => 'transaction.approved',
            'model_id' => $transaction->id,
        ]);

        $this->assertSame(3, AuditLog::where('model_id', $transaction->id)->count());
    }

    public function test_account_balance_reflects_only_approved_transactions()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);

        FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'financial_account_id' => $account->id,
            'transaction_type' => TransactionType::Income,
            'amount' => 500_000,
            'created_by' => $treasurer->id,
        ]);
        FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'financial_account_id' => $account->id,
            'transaction_type' => TransactionType::Expense,
            'amount' => 150_000,
            'created_by' => $treasurer->id,
        ]);
        FinancialTransaction::factory()->draft()->create([
            'organization_id' => $organization->id,
            'financial_account_id' => $account->id,
            'transaction_type' => TransactionType::Income,
            'amount' => 1_000_000,
            'created_by' => $treasurer->id,
        ]);

        $this->assertSame(350_000, $account->fresh()->balance());
    }

    public function test_a_transfer_moves_balance_between_accounts()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $source = FinancialAccount::factory()->create(['organization_id' => $organization->id, 'name' => 'Kas Pemuda']);
        $destination = FinancialAccount::factory()->create(['organization_id' => $organization->id, 'name' => 'Kas Event']);

        FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'financial_account_id' => $source->id,
            'transaction_type' => TransactionType::Income,
            'amount' => 1_000_000,
            'category_id' => null,
            'created_by' => $treasurer->id,
        ]);

        $this->actingAs($treasurer)->post('/finance/transactions', [
            'financial_account_id' => $source->id,
            'related_account_id' => $destination->id,
            'amount' => 300_000,
            'transaction_type' => TransactionType::Transfer->value,
            'transaction_date' => now()->toDateString(),
        ]);

        $transferId = FinancialTransaction::where('transaction_type', TransactionType::Transfer)->value('id');
        $this->actingAs($treasurer)->post("/finance/transactions/{$transferId}/submit");

        $this->assertSame(700_000, $source->fresh()->balance());
        $this->assertSame(300_000, $destination->fresh()->balance());
    }

    public function test_search_only_returns_transactions_whose_description_matches()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'description' => 'Konsumsi rapat bulanan',
            'created_by' => $treasurer->id,
        ]);
        FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'description' => 'Sewa sound system',
            'created_by' => $treasurer->id,
        ]);

        $this->actingAs($treasurer)
            ->get('/finance/transactions?search=Konsumsi')
            ->assertInertia(fn ($page) => $page
                ->has('transactions', 1)
                ->where('transactions.0.description', 'Konsumsi rapat bulanan')
                ->where('filters.search', 'Konsumsi')
            );
    }

    public function test_search_composes_with_the_existing_event_id_filter()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $event = Event::factory()->create(['organization_id' => $organization->id]);

        $matching = FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'description' => 'Konsumsi acara',
            'created_by' => $treasurer->id,
        ]);
        FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'event_id' => null,
            'description' => 'Konsumsi lainnya',
            'created_by' => $treasurer->id,
        ]);

        $this->actingAs($treasurer)
            ->get("/finance/transactions?search=Konsumsi&event_id={$event->id}")
            ->assertInertia(fn ($page) => $page
                ->has('transactions', 1)
                ->where('transactions.0.id', $matching->id)
            );
    }

    public function test_search_with_no_matches_returns_an_empty_list()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'description' => 'Konsumsi rapat',
            'created_by' => $treasurer->id,
        ]);

        $this->actingAs($treasurer)
            ->get('/finance/transactions?search=TidakAda')
            ->assertInertia(fn ($page) => $page->has('transactions', 0));
    }
}
