<?php

namespace Database\Seeders;

use App\Actions\Finance\ApproveFinancialReportAction;
use App\Actions\Finance\ApproveFinancialTransactionAction;
use App\Actions\Finance\GenerateFinancialReportAction;
use App\Actions\Finance\GenerateMonthlyDuesAction;
use App\Actions\Finance\PublishFinancialReportAction;
use App\Actions\Finance\RecordDuePaymentAction;
use App\Actions\Finance\SubmitReportForReviewAction;
use App\Actions\Finance\SubmitTransactionAction;
use App\Actions\Organization\CreateOrganizationAction;
use App\Enums\DocumentCategory;
use App\Enums\EventStatus;
use App\Enums\EventTaskStatus;
use App\Enums\FinancialReportType;
use App\Enums\FinancialReportVisibility;
use App\Enums\InventoryCondition;
use App\Enums\OrganizationRole;
use App\Enums\SponsorContributionStatus;
use App\Enums\SponsorType;
use App\Enums\TransactionType;
use App\Models\Announcement;
use App\Models\Document;
use App\Models\Event;
use App\Models\InventoryItem;
use App\Models\MemberDue;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Sponsor;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Seeder;

/**
 * Multi-organization fixture for exercising tenant isolation, RBAC, and
 * the superadmin cross-org view against something richer than one
 * lonely organization. NOT part of the default `db:seed` run —
 * `DatabaseSeeder` stays the lightweight single-org fixture most local
 * dev/testing already depends on (owner@rukunmuda.test etc). Run this
 * one explicitly:
 *
 *   php artisan db:seed --class=MultiOrganizationSeeder
 *
 * Creates 3 organizations, each with 14 members (1 Ketua, 1 Bendahara,
 * 1 Sekretaris, 11+ Anggota) and a full spread of domain data per org:
 * events (with committee/tasks/participants), finance (accounts,
 * categories, an approved transaction, a still-PENDING one to exercise
 * the approval queue, monthly dues with some paid), a financial report
 * — each organization's report deliberately sits at a *different*
 * workflow stage (published / awaiting review / approved-not-published)
 * so all three states have live data — announcements, inventory,
 * a sponsor + contribution, a document, and a vote with responses.
 * Every organization's data is generated independently, nothing shared
 * or copied between them, so any cross-org leak in a Policy or query
 * scope shows up immediately as one org's member seeing another org's
 * rows.
 *
 * Log in as any seeded user with password `password`, e.g.
 * ketua@org1.test, bendahara@org2.test, anggota3@org3.test.
 *
 * Superadmin is deliberately NOT granted here — grant is CLI-only by
 * design (see App\Console\Commands\GrantSuperadmin). A plain user is
 * seeded ready for it; after seeding, run:
 *
 *   php artisan superadmin:grant superadmin@rukunmuda.test
 */
class MultiOrganizationSeeder extends Seeder
{
    private const ORG_BLUEPRINTS = [
        ['slug' => 'org1', 'name' => 'Karang Taruna Melati Indah', 'anggota' => 11, 'public' => true],
        ['slug' => 'org2', 'name' => 'Pemuda RW 05 Sukamaju', 'anggota' => 12, 'public' => false],
        ['slug' => 'org3', 'name' => 'Karang Taruna Bina Remaja', 'anggota' => 13, 'public' => true],
    ];

    public function run(): void
    {
        // A plain user, ready for `superadmin:grant` — not a member of
        // any organization, matching the platform-level (not
        // org-scoped) design of the role.
        User::factory()->create([
            'name' => 'Superadmin',
            'email' => 'superadmin@rukunmuda.test',
        ]);

        foreach (self::ORG_BLUEPRINTS as $index => $blueprint) {
            $this->seedOrganization($blueprint, $index);
        }
    }

    /**
     * @param  array{slug: string, name: string, anggota: int, public: bool}  $blueprint
     */
    private function seedOrganization(array $blueprint, int $index): void
    {
        $slug = $blueprint['slug'];
        $name = $blueprint['name'];

        $ketuaUser = User::factory()->create(['name' => "Ketua {$name}", 'email' => "ketua@{$slug}.test"]);
        $organization = app(CreateOrganizationAction::class)->handle($ketuaUser, $name);
        $organization->update([
            'require_transaction_approval' => true,
            'public_transparency_enabled' => $blueprint['public'],
        ]);

        $ketuaMembership = $organization->memberships()->where('user_id', $ketuaUser->id)->firstOrFail();

        $bendaharaUser = User::factory()->create(['name' => "Bendahara {$name}", 'email' => "bendahara@{$slug}.test"]);
        $bendaharaMembership = $organization->memberships()->create(['user_id' => $bendaharaUser->id, 'role' => OrganizationRole::Bendahara]);

        $sekretarisUser = User::factory()->create(['name' => "Sekretaris {$name}", 'email' => "sekretaris@{$slug}.test"]);
        $sekretarisMembership = $organization->memberships()->create(['user_id' => $sekretarisUser->id, 'role' => OrganizationRole::Sekretaris]);

        /** @var list<OrganizationMembership> $anggotaMemberships */
        $anggotaMemberships = [];
        for ($n = 1; $n <= $blueprint['anggota']; $n++) {
            $anggotaUser = User::factory()->create(['name' => "Anggota {$n} {$name}", 'email' => "anggota{$n}@{$slug}.test"]);
            $anggotaMemberships[] = $organization->memberships()->create(['user_id' => $anggotaUser->id, 'role' => OrganizationRole::Anggota]);
        }

        $event = Event::factory()->create([
            'organization_id' => $organization->id,
            'status' => EventStatus::Planned,
            'pic_membership_id' => $sekretarisMembership->id,
            'created_by' => $ketuaUser->id,
        ]);
        $event->committees()->create(['membership_id' => $sekretarisMembership->id, 'role_title' => 'Ketua Panitia']);
        $event->committees()->create(['membership_id' => $anggotaMemberships[0]->id, 'role_title' => 'Sie Acara']);
        $event->tasks()->create([
            'title' => 'Booking lokasi',
            'assignee_membership_id' => $sekretarisMembership->id,
            'status' => EventTaskStatus::InProgress,
            'due_date' => now()->addWeek(),
            'created_by' => $ketuaUser->id,
        ]);
        $event->tasks()->create([
            'title' => 'Siapkan konsumsi',
            'assignee_membership_id' => $anggotaMemberships[1]->id,
            'status' => EventTaskStatus::Todo,
            'due_date' => now()->addWeeks(2),
            'created_by' => $ketuaUser->id,
        ]);
        foreach (array_slice($anggotaMemberships, 0, 5) as $membership) {
            $event->participants()->create(['membership_id' => $membership->id]);
        }

        Announcement::factory()->create([
            'organization_id' => $organization->id,
            'title' => 'Rapat koordinasi bulanan',
            'published_at' => now()->subDays(3),
            'created_by' => $ketuaUser->id,
        ]);

        $this->seedFinance($organization, $event, $ketuaUser, $bendaharaUser, $anggotaMemberships, $index, $blueprint['public']);

        InventoryItem::factory()->create(['organization_id' => $organization->id, 'created_by' => $sekretarisUser->id]);
        InventoryItem::factory()->create([
            'organization_id' => $organization->id,
            'condition' => InventoryCondition::PerluPerbaikan,
            'created_by' => $sekretarisUser->id,
        ]);

        $sponsor = Sponsor::factory()->create(['organization_id' => $organization->id, 'created_by' => $ketuaUser->id]);
        $sponsor->contributions()->create([
            'organization_id' => $organization->id,
            'type' => SponsorType::Uang,
            'status' => SponsorContributionStatus::Setuju,
            'amount' => 1_500_000,
            'created_by' => $bendaharaUser->id,
        ]);

        Document::factory()->create([
            'organization_id' => $organization->id,
            'title' => 'Notulen rapat pengurus',
            'category' => DocumentCategory::Notulen,
            'uploaded_by' => $sekretarisUser->id,
        ]);

        $vote = Vote::factory()->create(['organization_id' => $organization->id, 'created_by' => $ketuaUser->id]);
        $ya = $vote->options()->create(['label' => 'Ya', 'position' => 0]);
        $tidak = $vote->options()->create(['label' => 'Tidak', 'position' => 1]);
        $vote->responses()->create(['vote_option_id' => $ya->id, 'membership_id' => $ketuaMembership->id]);
        $vote->responses()->create(['vote_option_id' => $ya->id, 'membership_id' => $bendaharaMembership->id]);
        $vote->responses()->create(['vote_option_id' => $tidak->id, 'membership_id' => $anggotaMemberships[2]->id]);
    }

    /**
     * @param  list<OrganizationMembership>  $anggotaMemberships
     */
    private function seedFinance(
        Organization $organization,
        Event $event,
        User $chair,
        User $treasurer,
        array $anggotaMemberships,
        int $index,
        bool $publicTransparency,
    ): void {
        $kasUtama = $organization->financialAccounts()->create(['name' => 'Kas Utama']);
        $kasEvent = $organization->financialAccounts()->create(['name' => 'Kas Event']);

        $iuran = $organization->financialCategories()->create(['name' => 'Iuran Anggota', 'transaction_type' => TransactionType::Income]);
        $sponsorCategory = $organization->financialCategories()->create(['name' => 'Sponsor', 'transaction_type' => TransactionType::Income]);
        $konsumsi = $organization->financialCategories()->create(['name' => 'Konsumsi', 'transaction_type' => TransactionType::Expense]);
        $sewa = $organization->financialCategories()->create(['name' => 'Sewa Tempat', 'transaction_type' => TransactionType::Expense]);

        $submitTransaction = app(SubmitTransactionAction::class);
        $approveTransaction = app(ApproveFinancialTransactionAction::class);

        $opening = $organization->financialTransactions()->create([
            'financial_account_id' => $kasUtama->id,
            'category_id' => $sponsorCategory->id,
            'amount' => 3_000_000,
            'transaction_type' => TransactionType::Income,
            'description' => 'Saldo awal kas',
            'transaction_date' => now()->subMonth(),
            'created_by' => $treasurer->id,
        ]);
        $submitTransaction->handle($opening);
        $approveTransaction->handle($opening, $chair);

        $eventExpense = $organization->financialTransactions()->create([
            'financial_account_id' => $kasEvent->id,
            'category_id' => $konsumsi->id,
            'event_id' => $event->id,
            'amount' => 400_000,
            'transaction_type' => TransactionType::Expense,
            'description' => 'Konsumsi kegiatan',
            'transaction_date' => now()->subDays(5),
            'created_by' => $treasurer->id,
        ]);
        $submitTransaction->handle($eventExpense);
        $approveTransaction->handle($eventExpense, $chair);

        // Left PENDING on purpose — exercises the approval queue and
        // approve-permission RBAC (bendahara can submit, only ketua can approve).
        $pending = $organization->financialTransactions()->create([
            'financial_account_id' => $kasEvent->id,
            'category_id' => $sewa->id,
            'event_id' => $event->id,
            'amount' => 600_000,
            'transaction_type' => TransactionType::Expense,
            'description' => 'Sewa tempat kegiatan',
            'transaction_date' => now(),
            'created_by' => $treasurer->id,
        ]);
        $submitTransaction->handle($pending);

        app(GenerateMonthlyDuesAction::class)->handle($organization, now()->startOfMonth(), 20_000);
        foreach (array_slice($anggotaMemberships, 0, 2) as $membership) {
            $due = MemberDue::where('organization_id', $organization->id)
                ->where('membership_id', $membership->id)
                ->first();

            if ($due) {
                app(RecordDuePaymentAction::class)->handle($due, $treasurer, $due->amount_due, now(), $kasUtama, $iuran);
            }
        }

        $lastMonth = now()->subMonthNoOverflow()->startOfMonth();
        $report = app(GenerateFinancialReportAction::class)->handle($organization, $treasurer, [
            'title' => 'Laporan Kas '.$lastMonth->translatedFormat('F Y'),
            'report_type' => FinancialReportType::Monthly,
            'period_start' => $lastMonth->toDateString(),
            'period_end' => $lastMonth->copy()->endOfMonth()->toDateString(),
            'visibility' => $publicTransparency ? FinancialReportVisibility::Public : FinancialReportVisibility::Members,
        ]);

        match ($index) {
            0 => app(PublishFinancialReportAction::class)->handle($report, $chair),
            1 => app(SubmitReportForReviewAction::class)->handle($report, $treasurer, 'Mohon diperiksa sebelum diterbitkan, terima kasih.'),
            default => app(ApproveFinancialReportAction::class)->handle(
                app(SubmitReportForReviewAction::class)->handle($report, $treasurer, null),
                $chair,
            ),
        };
    }
}
