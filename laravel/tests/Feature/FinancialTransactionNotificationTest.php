<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\FinancialTransaction;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\TransactionReviewed;
use App\Notifications\TransactionSubmittedForReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FinancialTransactionNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_submitting_a_transaction_notifies_organizers_but_not_the_submitter()
    {
        Notification::fake();

        $organization = Organization::factory()->create(['require_transaction_approval' => true]);
        $owner = $this->memberWithRole($organization, OrganizationRole::Owner);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);
        $transaction = FinancialTransaction::factory()->draft()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);

        $this->actingAs($treasurer)->post("/finance/transactions/{$transaction->id}/submit");

        Notification::assertSentTo($owner, TransactionSubmittedForReview::class);
        Notification::assertNotSentTo($treasurer, TransactionSubmittedForReview::class);
    }

    public function test_submitting_a_transaction_that_does_not_require_approval_sends_no_notification()
    {
        Notification::fake();

        $organization = Organization::factory()->create(['require_transaction_approval' => false]);
        $owner = $this->memberWithRole($organization, OrganizationRole::Owner);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);
        $transaction = FinancialTransaction::factory()->draft()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);

        $this->actingAs($treasurer)->post("/finance/transactions/{$transaction->id}/submit");

        Notification::assertNothingSentTo($owner);
    }

    public function test_approving_a_transaction_notifies_its_creator()
    {
        Notification::fake();

        $organization = Organization::factory()->create(['require_transaction_approval' => true]);
        $owner = $this->memberWithRole($organization, OrganizationRole::Owner);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);
        $transaction = FinancialTransaction::factory()->pending()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);

        $this->actingAs($owner)->post("/finance/transactions/{$transaction->id}/approve");

        Notification::assertSentTo($treasurer, TransactionReviewed::class);
    }

    public function test_rejecting_a_transaction_notifies_its_creator()
    {
        Notification::fake();

        $organization = Organization::factory()->create(['require_transaction_approval' => true]);
        $owner = $this->memberWithRole($organization, OrganizationRole::Owner);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);
        $transaction = FinancialTransaction::factory()->pending()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);

        $this->actingAs($owner)->post("/finance/transactions/{$transaction->id}/reject");

        Notification::assertSentTo($treasurer, TransactionReviewed::class);
    }
}
