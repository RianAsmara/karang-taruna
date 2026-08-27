<?php

namespace Tests\Feature\Api;

use App\Enums\EventStatus;
use App\Enums\OrganizationRole;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Event;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\Organization;
use App\Models\Sponsor;
use App\Models\User;
use App\Notifications\EventCommitteeAssigned;
use App\Notifications\EventRescheduled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventWizardTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_a_treasurer_can_create_an_event()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);

        Sanctum::actingAs($treasurer);

        $this->postJson('/api/v1/events', [
            'title' => 'Rapat Bulanan',
            'start_at' => now()->addWeek()->toIso8601String(),
        ])->assertCreated();
    }

    public function test_a_plain_member_still_cannot_create_an_event()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);

        Sanctum::actingAs($member);

        $this->postJson('/api/v1/events', [
            'title' => 'Rapat Bulanan',
            'start_at' => now()->addWeek()->toIso8601String(),
        ])->assertForbidden();
    }

    public function test_creating_an_event_with_committees_assigns_them_alongside_the_creator()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $secretary = $this->memberWithRole($organization, OrganizationRole::Sekretaris);
        $secretaryMembership = $secretary->membershipIn($organization);

        Sanctum::actingAs($chair);

        $response = $this->postJson('/api/v1/events', [
            'title' => 'Kerja Bakti',
            'start_at' => now()->addWeek()->toIso8601String(),
            'committees' => [
                ['membership_id' => $secretaryMembership->id, 'role_title' => 'Dokumentasi'],
            ],
        ])->assertCreated();

        $event = Event::find($response->json('data.id'));

        $this->assertCount(2, $event->committees);
        $this->assertTrue($event->committees->contains('membership_id', $secretaryMembership->id));
        $this->assertEquals('Dokumentasi', $event->committees->firstWhere('membership_id', $secretaryMembership->id)->role_title);
    }

    public function test_creating_an_event_with_a_budget_creates_a_linked_draft_expense_transaction()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        FinancialAccount::factory()->create(['organization_id' => $organization->id]);
        FinancialCategory::factory()->create([
            'organization_id' => $organization->id,
            'transaction_type' => TransactionType::Expense,
        ]);

        Sanctum::actingAs($chair);

        $response = $this->postJson('/api/v1/events', [
            'title' => 'Kerja Bakti',
            'start_at' => now()->addWeek()->toIso8601String(),
            'budget_amount' => 350000,
        ])->assertCreated();

        $event = Event::find($response->json('data.id'));
        $transaction = $event->budgetTransaction;

        $this->assertNotNull($transaction);
        $this->assertSame(350000, $transaction->amount);
        $this->assertEquals(TransactionType::Expense, $transaction->transaction_type);
        $this->assertEquals(TransactionStatus::Draft, $transaction->status);
    }

    public function test_a_budget_cannot_be_set_without_an_existing_financial_account()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);

        Sanctum::actingAs($chair);

        $this->postJson('/api/v1/events', [
            'title' => 'Kerja Bakti',
            'start_at' => now()->addWeek()->toIso8601String(),
            'budget_amount' => 350000,
        ])->assertUnprocessable();
    }

    public function test_updating_the_budget_amount_updates_the_same_transaction_instead_of_duplicating_it()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        FinancialAccount::factory()->create(['organization_id' => $organization->id]);
        FinancialCategory::factory()->create([
            'organization_id' => $organization->id,
            'transaction_type' => TransactionType::Expense,
        ]);

        Sanctum::actingAs($chair);

        $created = $this->postJson('/api/v1/events', [
            'title' => 'Kerja Bakti',
            'start_at' => now()->addWeek()->toIso8601String(),
            'budget_amount' => 350000,
        ])->assertCreated();

        $event = Event::find($created->json('data.id'));

        $this->patchJson("/api/v1/events/{$event->id}", [
            'title' => $event->title,
            'start_at' => $event->start_at->toIso8601String(),
            'status' => $event->status->value,
            'lifecycle_stage' => $event->lifecycle_stage->value,
            'budget_amount' => 500000,
        ])->assertOk();

        $event->refresh();
        $this->assertSame(1, $event->organization->financialTransactions()->count());
        $this->assertSame(500000, $event->budgetTransaction->amount);
    }

    public function test_creating_an_event_with_a_sponsor_links_it()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $sponsor = Sponsor::factory()->create(['organization_id' => $organization->id]);

        Sanctum::actingAs($chair);

        $response = $this->postJson('/api/v1/events', [
            'title' => 'Kerja Bakti',
            'start_at' => now()->addWeek()->toIso8601String(),
            'sponsor_id' => $sponsor->id,
        ])->assertCreated();

        $this->assertSame($sponsor->id, Event::find($response->json('data.id'))->sponsor_id);
    }

    public function test_a_sponsor_from_another_organization_cannot_be_linked()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $otherOrgSponsor = Sponsor::factory()->create();

        Sanctum::actingAs($chair);

        $this->postJson('/api/v1/events', [
            'title' => 'Kerja Bakti',
            'start_at' => now()->addWeek()->toIso8601String(),
            'sponsor_id' => $otherOrgSponsor->id,
        ])->assertUnprocessable();
    }

    public function test_event_detail_exposes_category_label_sponsor_and_budget()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        FinancialAccount::factory()->create(['organization_id' => $organization->id]);
        $sponsor = Sponsor::factory()->create(['organization_id' => $organization->id, 'name' => 'Toko Maju']);

        Sanctum::actingAs($chair);

        $response = $this->postJson('/api/v1/events', [
            'title' => 'Kerja Bakti',
            'start_at' => now()->addWeek()->toIso8601String(),
            'category' => 'KERJA_BAKTI',
            'sponsor_id' => $sponsor->id,
            'budget_amount' => 350000,
        ])->assertCreated();

        $eventId = $response->json('data.id');

        $this->getJson("/api/v1/events/{$eventId}")
            ->assertOk()
            ->assertJsonPath('data.category', 'KERJA_BAKTI')
            ->assertJsonPath('data.categoryLabel', 'Kerja bakti')
            ->assertJsonPath('data.sponsor.name', 'Toko Maju')
            ->assertJsonPath('data.budgetAmount', 350000);
    }

    public function test_publishing_an_event_with_a_past_date_forces_it_directly_to_completed()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $event = Event::factory()->create([
            'organization_id' => $organization->id,
            'status' => EventStatus::Draft,
            'start_at' => now()->subDay(),
        ]);

        Sanctum::actingAs($chair);

        $response = $this->patchJson("/api/v1/events/{$event->id}", [
            'title' => $event->title,
            'start_at' => $event->start_at->toIso8601String(),
            'status' => EventStatus::Planned->value,
            'lifecycle_stage' => $event->lifecycle_stage->value,
        ])->assertOk();

        $this->assertSame(EventStatus::Completed->value, $response->json('data.status'));
    }

    public function test_publishing_an_event_notifies_committee_members_but_not_the_publisher()
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $committeeMember = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $event = Event::factory()->create([
            'organization_id' => $organization->id,
            'status' => EventStatus::Draft,
            'start_at' => now()->addWeek(),
        ]);
        $event->committees()->create(['membership_id' => $committeeMember->membershipIn($organization)->id]);
        $event->committees()->create(['membership_id' => $chair->membershipIn($organization)->id]);

        Sanctum::actingAs($chair);

        $this->patchJson("/api/v1/events/{$event->id}", [
            'title' => $event->title,
            'start_at' => $event->start_at->toIso8601String(),
            'status' => EventStatus::Planned->value,
            'lifecycle_stage' => $event->lifecycle_stage->value,
        ])->assertOk();

        Notification::assertSentTo($committeeMember, EventCommitteeAssigned::class);
        Notification::assertNotSentTo($chair, EventCommitteeAssigned::class);
    }

    public function test_rescheduling_a_published_event_notifies_the_committee()
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $committeeMember = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $event = Event::factory()->create([
            'organization_id' => $organization->id,
            'status' => EventStatus::Planned,
            'start_at' => now()->addWeek(),
        ]);
        $event->committees()->create(['membership_id' => $committeeMember->membershipIn($organization)->id]);

        Sanctum::actingAs($chair);

        $this->patchJson("/api/v1/events/{$event->id}", [
            'title' => $event->title,
            'start_at' => now()->addWeeks(2)->toIso8601String(),
            'status' => $event->status->value,
            'lifecycle_stage' => $event->lifecycle_stage->value,
        ])->assertOk();

        Notification::assertSentTo($committeeMember, EventRescheduled::class);
    }

    public function test_editing_a_draft_event_does_not_notify_anyone()
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $committeeMember = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $event = Event::factory()->create([
            'organization_id' => $organization->id,
            'status' => EventStatus::Draft,
            'start_at' => now()->addWeek(),
        ]);
        $event->committees()->create(['membership_id' => $committeeMember->membershipIn($organization)->id]);

        Sanctum::actingAs($chair);

        $this->patchJson("/api/v1/events/{$event->id}", [
            'title' => $event->title,
            'start_at' => now()->addWeeks(2)->toIso8601String(),
            'status' => EventStatus::Draft->value,
            'lifecycle_stage' => $event->lifecycle_stage->value,
        ])->assertOk();

        Notification::assertNothingSentTo($committeeMember);
    }
}
