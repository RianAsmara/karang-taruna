<?php

namespace Tests\Feature;

use App\Enums\MemberDueType;
use App\Enums\OrganizationRole;
use App\Enums\TransactionStatus;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\MemberDue;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberDueTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_treasurer_can_generate_monthly_dues_for_every_member()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);
        $this->memberWithRole($organization, OrganizationRole::Member);
        $this->memberWithRole($organization, OrganizationRole::Resident);

        $this->actingAs($treasurer)
            ->post('/finance/dues/generate-monthly', [
                'period' => now()->startOfMonth()->toDateString(),
                'amount_due' => 25_000,
            ])
            ->assertRedirect();

        $this->assertSame(3, MemberDue::where('organization_id', $organization->id)->count());
    }

    public function test_generating_monthly_dues_twice_does_not_duplicate()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);
        $this->memberWithRole($organization, OrganizationRole::Member);

        $payload = ['period' => now()->startOfMonth()->toDateString(), 'amount_due' => 25_000];

        $this->actingAs($treasurer)->post('/finance/dues/generate-monthly', $payload);
        $this->actingAs($treasurer)->post('/finance/dues/generate-monthly', $payload);

        $this->assertSame(2, MemberDue::where('organization_id', $organization->id)->count());
    }

    public function test_recording_a_payment_creates_a_linked_ledger_transaction()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);
        $member = $this->memberWithRole($organization, OrganizationRole::Member);
        $membership = $organization->memberships()->firstWhere('user_id', $member->id);

        $due = MemberDue::factory()->create([
            'organization_id' => $organization->id,
            'membership_id' => $membership->id,
            'amount_due' => 25_000,
            'type' => MemberDueType::Monthly,
        ]);

        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);
        $category = FinancialCategory::factory()->create([
            'organization_id' => $organization->id,
            'transaction_type' => 'INCOME',
        ]);

        $this->actingAs($treasurer)
            ->post("/finance/dues/{$due->id}/payments", [
                'amount' => 25_000,
                'paid_at' => now()->toDateString(),
                'financial_account_id' => $account->id,
                'category_id' => $category->id,
            ])
            ->assertRedirect();

        $fresh = $due->fresh();
        $this->assertTrue($fresh->isPaid());
        $this->assertSame(25_000, $fresh->amountPaid());

        $payment = $fresh->payments()->first();
        $this->assertNotNull($payment->financial_transaction_id);
        $this->assertSame(TransactionStatus::Approved, $payment->financialTransaction->status);
        $this->assertSame(25_000, $account->fresh()->balance());
    }

    public function test_a_partial_payment_leaves_the_due_outstanding()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);
        $member = $this->memberWithRole($organization, OrganizationRole::Member);
        $membership = $organization->memberships()->firstWhere('user_id', $member->id);

        $due = MemberDue::factory()->create([
            'organization_id' => $organization->id,
            'membership_id' => $membership->id,
            'amount_due' => 25_000,
        ]);
        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);
        $category = FinancialCategory::factory()->create([
            'organization_id' => $organization->id,
            'transaction_type' => 'INCOME',
        ]);

        $this->actingAs($treasurer)->post("/finance/dues/{$due->id}/payments", [
            'amount' => 10_000,
            'paid_at' => now()->toDateString(),
            'financial_account_id' => $account->id,
            'category_id' => $category->id,
        ]);

        $fresh = $due->fresh();
        $this->assertFalse($fresh->isPaid());
        $this->assertSame(10_000, $fresh->amountPaid());
        $this->assertSame(15_000, $fresh->amountOutstanding());
    }

    public function test_overpayment_is_rejected()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);
        $member = $this->memberWithRole($organization, OrganizationRole::Member);
        $membership = $organization->memberships()->firstWhere('user_id', $member->id);

        $due = MemberDue::factory()->create([
            'organization_id' => $organization->id,
            'membership_id' => $membership->id,
            'amount_due' => 25_000,
        ]);
        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);
        $category = FinancialCategory::factory()->create([
            'organization_id' => $organization->id,
            'transaction_type' => 'INCOME',
        ]);

        $this->actingAs($treasurer)
            ->post("/finance/dues/{$due->id}/payments", [
                'amount' => 30_000,
                'paid_at' => now()->toDateString(),
                'financial_account_id' => $account->id,
                'category_id' => $category->id,
            ])
            ->assertSessionHasErrors('amount');
    }

    public function test_a_plain_member_cannot_generate_dues_or_record_payments()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Member);
        $membership = $organization->memberships()->firstWhere('user_id', $member->id);

        $due = MemberDue::factory()->create([
            'organization_id' => $organization->id,
            'membership_id' => $membership->id,
        ]);
        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);
        $category = FinancialCategory::factory()->create([
            'organization_id' => $organization->id,
            'transaction_type' => 'INCOME',
        ]);

        $this->actingAs($member)
            ->post('/finance/dues/generate-monthly', ['period' => now()->toDateString(), 'amount_due' => 25_000])
            ->assertForbidden();

        $this->actingAs($member)
            ->post("/finance/dues/{$due->id}/payments", [
                'amount' => 25_000,
                'paid_at' => now()->toDateString(),
                'financial_account_id' => $account->id,
                'category_id' => $category->id,
            ])
            ->assertForbidden();
    }

    public function test_a_member_can_only_see_their_own_dues()
    {
        $organization = Organization::factory()->create();
        $memberA = $this->memberWithRole($organization, OrganizationRole::Member);
        $memberB = $this->memberWithRole($organization, OrganizationRole::Member);
        $membershipA = $organization->memberships()->firstWhere('user_id', $memberA->id);
        $membershipB = $organization->memberships()->firstWhere('user_id', $memberB->id);

        MemberDue::factory()->create(['organization_id' => $organization->id, 'membership_id' => $membershipA->id]);
        MemberDue::factory()->create(['organization_id' => $organization->id, 'membership_id' => $membershipB->id]);

        $response = $this->actingAs($memberA)->get('/finance/dues');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->has('dues', 1));
    }
}
