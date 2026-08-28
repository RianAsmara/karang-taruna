<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\EventTaskStatus;
use App\Enums\MemberDueType;
use App\Enums\OrganizationRole;
use App\Enums\TransactionType;
use App\Models\Announcement;
use App\Models\Event;
use App\Models\EventTask;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\MemberDue;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_organization_makes_the_creator_its_owner()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/organizations', ['name' => 'Karang Taruna Melati'])
            ->assertRedirect('/dashboard');

        $organization = Organization::firstWhere('name', 'Karang Taruna Melati');

        $this->assertNotNull($organization);
        $this->assertSame('karang-taruna-melati', $organization->slug);

        $membership = $organization->memberships()->firstWhere('user_id', $user->id);

        $this->assertNotNull($membership);
        $this->assertSame(OrganizationRole::Ketua, $membership->role);
        $this->assertSame($organization->id, $user->fresh()->active_organization_id);
    }

    public function test_a_user_with_an_existing_organization_can_create_another_and_it_becomes_active()
    {
        $user = User::factory()->create();
        $firstOrganization = Organization::factory()->create();
        $firstOrganization->memberships()->create(['user_id' => $user->id, 'role' => OrganizationRole::Ketua]);

        $this->actingAs($user)->get('/organizations/create')->assertOk();

        $this->actingAs($user)
            ->post('/organizations', ['name' => 'Pemuda Kampung Baru'])
            ->assertRedirect('/dashboard');

        $newOrganization = Organization::firstWhere('name', 'Pemuda Kampung Baru');

        $this->assertNotNull($newOrganization);
        $this->assertSame($newOrganization->id, $user->fresh()->active_organization_id);
        $this->assertSame($newOrganization->id, $user->fresh()->currentMembership()->organization_id);
    }

    public function test_organization_name_is_required()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/organizations', ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_duplicate_organization_names_get_a_unique_slug()
    {
        $owner = User::factory()->create();
        Organization::factory()->create(['name' => 'Karang Taruna Melati', 'slug' => 'karang-taruna-melati']);

        $this->actingAs($owner)
            ->post('/organizations', ['name' => 'Karang Taruna Melati']);

        $this->assertDatabaseHas('organizations', [
            'name' => 'Karang Taruna Melati',
            'slug' => 'karang-taruna-melati-1',
        ]);
    }

    public function test_a_member_from_another_organization_cannot_manage_members()
    {
        $organization = Organization::factory()->create();
        $outsider = User::factory()->create();

        $this->assertFalse($outsider->can('manageMembers', $organization));
    }

    public function test_only_the_chair_can_manage_members()
    {
        $organization = Organization::factory()->create();

        $owner = User::factory()->create();
        $organization->memberships()->create(['user_id' => $owner->id, 'role' => OrganizationRole::Ketua]);

        $member = User::factory()->create();
        $organization->memberships()->create(['user_id' => $member->id, 'role' => OrganizationRole::Anggota]);

        $this->assertTrue($owner->can('manageMembers', $organization));
        $this->assertFalse($member->can('manageMembers', $organization));
    }

    public function test_dashboard_prompts_to_create_an_organization_when_user_has_none()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('currentOrganization', null));
    }

    public function test_dashboard_shows_the_users_organization_when_they_have_one()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['name' => 'Karang Taruna Melati']);
        $organization->memberships()->create(['user_id' => $user->id, 'role' => OrganizationRole::Ketua]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('currentOrganization.name', 'Karang Taruna Melati')
                ->where('currentOrganization.role', 'KETUA')
            );
    }

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_dashboard_shows_the_organizations_cash_summary()
    {
        $organization = Organization::factory()->create();
        $user = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);
        FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'financial_account_id' => $account->id,
            'transaction_type' => TransactionType::Income,
            'amount' => 500_000,
            'transaction_date' => now(),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page->where('summary.balance', 500_000));
    }

    public function test_dashboard_shows_only_the_soonest_upcoming_planned_or_ongoing_event()
    {
        $organization = Organization::factory()->create();
        $user = $this->memberWithRole($organization, OrganizationRole::Ketua);

        Event::factory()->create([
            'organization_id' => $organization->id,
            'title' => 'Sudah lewat',
            'status' => EventStatus::Completed,
            'start_at' => now()->subDay(),
            'created_by' => $user->id,
        ]);
        Event::factory()->create([
            'organization_id' => $organization->id,
            'title' => 'Terlalu jauh',
            'status' => EventStatus::Planned,
            'start_at' => now()->addWeeks(3),
            'created_by' => $user->id,
        ]);
        Event::factory()->create([
            'organization_id' => $organization->id,
            'title' => 'Kegiatan terdekat',
            'status' => EventStatus::Planned,
            'start_at' => now()->addDays(2),
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page->where('nextEvent.title', 'Kegiatan terdekat'));
    }

    public function test_dashboard_shows_only_my_incomplete_tasks()
    {
        $organization = Organization::factory()->create();
        $user = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $membership = $user->membershipIn($organization);
        $other = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $event = Event::factory()->create(['organization_id' => $organization->id, 'created_by' => $user->id]);

        EventTask::factory()->create([
            'event_id' => $event->id,
            'title' => 'Tugas saya',
            'assignee_membership_id' => $membership->id,
            'status' => EventTaskStatus::Todo,
            'created_by' => $user->id,
        ]);
        EventTask::factory()->create([
            'event_id' => $event->id,
            'title' => 'Tugas selesai',
            'assignee_membership_id' => $membership->id,
            'status' => EventTaskStatus::Done,
            'created_by' => $user->id,
        ]);
        EventTask::factory()->create([
            'event_id' => $event->id,
            'title' => 'Tugas orang lain',
            'assignee_membership_id' => $other->membershipIn($organization)->id,
            'status' => EventTaskStatus::Todo,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page->has('myTasks', 1)
            ->where('myTasks.0.title', 'Tugas saya')
        );
    }

    public function test_dashboard_shows_my_unpaid_monthly_due_for_the_current_period()
    {
        $organization = Organization::factory()->create();
        $user = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $membership = $user->membershipIn($organization);

        MemberDue::create([
            'organization_id' => $organization->id,
            'membership_id' => $membership->id,
            'period' => Carbon::now()->startOfMonth(),
            'amount_due' => 25_000,
            'type' => MemberDueType::Monthly,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page->where('myDue.amountOutstanding', 25_000));
    }

    public function test_dashboard_hides_an_exempt_due()
    {
        $organization = Organization::factory()->create();
        $user = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $membership = $user->membershipIn($organization);

        MemberDue::create([
            'organization_id' => $organization->id,
            'membership_id' => $membership->id,
            'period' => Carbon::now()->startOfMonth(),
            'amount_due' => 25_000,
            'type' => MemberDueType::Monthly,
            'is_exempt' => true,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page->where('myDue', null));
    }

    public function test_dashboard_shows_the_most_recently_published_announcement()
    {
        $organization = Organization::factory()->create();
        $user = $this->memberWithRole($organization, OrganizationRole::Ketua);

        Announcement::factory()->create([
            'organization_id' => $organization->id,
            'title' => 'Pengumuman lama',
            'published_at' => now()->subWeek(),
            'created_by' => $user->id,
        ]);
        Announcement::factory()->create([
            'organization_id' => $organization->id,
            'title' => 'Pengumuman terbaru',
            'published_at' => now(),
            'created_by' => $user->id,
        ]);
        Announcement::factory()->draft()->create([
            'organization_id' => $organization->id,
            'title' => 'Belum terbit',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page->where('announcement.title', 'Pengumuman terbaru'));
    }
}
