<?php

namespace Tests\Feature\Api;

use App\Enums\OrganizationRole;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\MemberDue;
use App\Models\MemberPayment;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
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

    public function test_the_treasurer_sees_every_members_dues_but_a_plain_member_sees_only_their_own()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);

        $memberMembership = $organization->memberships()->where('user_id', $member->id)->firstOrFail();

        MemberDue::factory()->create(['organization_id' => $organization->id, 'membership_id' => $memberMembership->id]);
        MemberDue::factory()->create(['organization_id' => $organization->id]); // someone else's due

        Sanctum::actingAs($member);
        $this->getJson('/api/v1/finance/dues')->assertOk()->assertJsonCount(1, 'data');

        Sanctum::actingAs($treasurer);
        $this->getJson('/api/v1/finance/dues')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_amount_paid_and_outstanding_are_computed_from_payments()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $due = MemberDue::factory()->create(['organization_id' => $organization->id, 'amount_due' => 100_000]);
        MemberPayment::factory()->create(['member_due_id' => $due->id, 'amount' => 40_000]);

        Sanctum::actingAs($treasurer);

        $this->getJson('/api/v1/finance/dues')
            ->assertOk()
            ->assertJsonPath('data.0.amountPaid', 40_000)
            ->assertJsonPath('data.0.amountOutstanding', 60_000)
            ->assertJsonPath('data.0.isPaid', false);
    }

    public function test_treasurer_can_record_a_payment_which_creates_a_linked_ledger_transaction()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $membership = $organization->memberships()->where('user_id', $member->id)->firstOrFail();

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

        Sanctum::actingAs($treasurer);

        $this->postJson("/api/v1/finance/dues/{$due->id}/payments", [
            'amount' => 25_000,
            'paid_at' => now()->toDateString(),
            'financial_account_id' => $account->id,
            'category_id' => $category->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.isPaid', true)
            ->assertJsonPath('data.amountPaid', 25_000);

        $this->assertDatabaseHas('financial_transactions', [
            'organization_id' => $organization->id,
            'financial_account_id' => $account->id,
            'amount' => 25_000,
        ]);
    }

    public function test_recording_a_payment_exposes_method_and_recorder_for_history()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $due = MemberDue::factory()->create(['organization_id' => $organization->id, 'amount_due' => 25_000]);
        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);
        $category = FinancialCategory::factory()->create(['organization_id' => $organization->id, 'transaction_type' => 'INCOME']);

        Sanctum::actingAs($treasurer);

        $this->postJson("/api/v1/finance/dues/{$due->id}/payments", [
            'amount' => 25_000,
            'paid_at' => now()->toDateString(),
            'financial_account_id' => $account->id,
            'category_id' => $category->id,
            'method' => 'TRANSFER',
            'note' => 'Transfer BCA',
        ])
            ->assertOk()
            ->assertJsonPath('data.method', 'TRANSFER')
            ->assertJsonPath('data.methodLabel', 'Transfer')
            ->assertJsonPath('data.recordedByName', $treasurer->name)
            ->assertJsonPath('data.lastPaymentAt', now()->toDateString());
    }

    public function test_a_plain_member_cannot_record_a_payment()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $membership = $organization->memberships()->where('user_id', $member->id)->firstOrFail();
        $due = MemberDue::factory()->create(['organization_id' => $organization->id, 'membership_id' => $membership->id]);

        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);
        $category = FinancialCategory::factory()->create(['organization_id' => $organization->id, 'transaction_type' => 'INCOME']);

        Sanctum::actingAs($member);

        $this->postJson("/api/v1/finance/dues/{$due->id}/payments", [
            'amount' => 25_000,
            'paid_at' => now()->toDateString(),
            'financial_account_id' => $account->id,
            'category_id' => $category->id,
        ])->assertForbidden();
    }

    public function test_treasurer_can_generate_monthly_dues_for_every_member()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $this->memberWithRole($organization, OrganizationRole::Anggota);

        Sanctum::actingAs($treasurer);

        $this->postJson('/api/v1/finance/dues/generate-monthly', [
            'period' => now()->startOfMonth()->toDateString(),
            'amount_due' => 25_000,
        ])->assertOk()->assertJsonCount(2, 'data');

        $this->assertSame(2, MemberDue::where('organization_id', $organization->id)->count());
    }

    public function test_a_plain_member_cannot_generate_monthly_dues()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);

        Sanctum::actingAs($member);

        $this->postJson('/api/v1/finance/dues/generate-monthly', [
            'period' => now()->startOfMonth()->toDateString(),
            'amount_due' => 25_000,
        ])->assertForbidden();
    }

    public function test_a_member_can_notify_the_treasurer_they_have_paid()
    {
        $organization = Organization::factory()->create();
        $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $membership = $organization->memberships()->where('user_id', $member->id)->firstOrFail();
        $due = MemberDue::factory()->create(['organization_id' => $organization->id, 'membership_id' => $membership->id]);

        Sanctum::actingAs($member);

        $this->postJson("/api/v1/finance/dues/{$due->id}/notify")
            ->assertOk()
            ->assertJsonPath('data.isAwaitingConfirmation', true)
            ->assertJsonPath('data.isPaid', false);

        $this->assertNotNull($due->fresh()->notified_at);
    }

    public function test_notifying_never_marks_the_due_paid_and_treasurer_still_confirms_via_payment()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $membership = $organization->memberships()->where('user_id', $member->id)->firstOrFail();
        $due = MemberDue::factory()->create(['organization_id' => $organization->id, 'membership_id' => $membership->id, 'amount_due' => 25_000]);

        Sanctum::actingAs($member);
        $this->postJson("/api/v1/finance/dues/{$due->id}/notify")->assertOk();

        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);
        $category = FinancialCategory::factory()->create(['organization_id' => $organization->id, 'transaction_type' => 'INCOME']);

        Sanctum::actingAs($treasurer);
        $this->postJson("/api/v1/finance/dues/{$due->id}/payments", [
            'amount' => 25_000,
            'paid_at' => now()->toDateString(),
            'financial_account_id' => $account->id,
            'category_id' => $category->id,
        ])->assertOk()->assertJsonPath('data.isPaid', true)->assertJsonPath('data.isAwaitingConfirmation', false);
    }

    public function test_a_member_cannot_notify_for_someone_elses_due()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $other = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $otherMembership = $organization->memberships()->where('user_id', $other->id)->firstOrFail();
        $due = MemberDue::factory()->create(['organization_id' => $organization->id, 'membership_id' => $otherMembership->id]);

        Sanctum::actingAs($member);

        $this->postJson("/api/v1/finance/dues/{$due->id}/notify")->assertForbidden();
    }

    public function test_a_partial_payment_is_distinct_from_awaiting_confirmation()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $due = MemberDue::factory()->create(['organization_id' => $organization->id, 'amount_due' => 100_000]);
        MemberPayment::factory()->create(['member_due_id' => $due->id, 'amount' => 40_000]);

        Sanctum::actingAs($treasurer);

        $this->getJson('/api/v1/finance/dues')
            ->assertOk()
            ->assertJsonPath('data.0.isPartiallyPaid', true)
            ->assertJsonPath('data.0.isAwaitingConfirmation', false);
    }
}
