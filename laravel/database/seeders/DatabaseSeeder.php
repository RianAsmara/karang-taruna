<?php

namespace Database\Seeders;

use App\Actions\Finance\ApproveFinancialTransactionAction;
use App\Actions\Finance\GenerateFinancialReportAction;
use App\Actions\Finance\GenerateMonthlyDuesAction;
use App\Actions\Finance\PublishFinancialReportAction;
use App\Actions\Finance\RecordDuePaymentAction;
use App\Actions\Finance\SubmitTransactionAction;
use App\Enums\EventStatus;
use App\Enums\EventTaskStatus;
use App\Enums\FinancialReportType;
use App\Enums\FinancialReportVisibility;
use App\Enums\OrganizationRole;
use App\Enums\TransactionType;
use App\Models\Announcement;
use App\Models\Event;
use App\Models\MemberDue;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with a single organization, a user
     * for every role, and a sample event/task/announcement, for local
     * development — then additionally call MultiOrganizationSeeder for a
     * 3-org fixture (see its own docblock). Superadmin is never granted
     * here — that's CLI-only by design (App\Console\Commands\GrantSuperadmin)
     * and needs an interactive confirm, so run it yourself after seeding:
     * `php artisan superadmin:grant superadmin@rukunmuda.test`.
     */
    public function run(): void
    {
        $owner = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'owner@rukunmuda.test',
        ]);

        $organization = Organization::factory()->create([
            'name' => 'Karang Taruna Melati',
            'require_transaction_approval' => true,
            'public_transparency_enabled' => true,
        ]);

        $ownerMembership = $organization->memberships()->create([
            'user_id' => $owner->id,
            'role' => OrganizationRole::Ketua,
        ]);

        /** @var array<string, OrganizationMembership> $memberships */
        $memberships = ['owner' => $ownerMembership];

        // 'panitia' and 'warga' are demo logins, not distinct organization
        // roles — both hold ANGGOTA. 'panitia' is additionally assigned as
        // this event's EventCommittee below, which is exactly how the
        // per-event-only Panitia concept works (mobile-design-system.md § Roles).
        foreach (
            [
                'sekretaris' => OrganizationRole::Sekretaris,
                'bendahara' => OrganizationRole::Bendahara,
                'panitia' => OrganizationRole::Anggota,
                'anggota' => OrganizationRole::Anggota,
                'warga' => OrganizationRole::Anggota,
            ] as $localPart => $role
        ) {
            $member = User::factory()->create([
                'name' => ucfirst($localPart),
                'email' => "{$localPart}@rukunmuda.test",
            ]);

            $memberships[$localPart] = $organization->memberships()->create([
                'user_id' => $member->id,
                'role' => $role,
            ]);
        }

        $event = Event::factory()->create([
            'organization_id' => $organization->id,
            'title' => 'Rapat Persiapan HUT Kemerdekaan',
            'status' => EventStatus::Planned,
            'pic_membership_id' => $memberships['panitia']->id,
            'created_by' => $owner->id,
        ]);

        $event->committees()->create(['membership_id' => $memberships['panitia']->id, 'role_title' => 'Ketua Panitia']);
        $event->committees()->create(['membership_id' => $memberships['anggota']->id, 'role_title' => 'Sie Konsumsi']);

        $event->tasks()->create([
            'title' => 'Booking lapangan',
            'assignee_membership_id' => $memberships['panitia']->id,
            'status' => EventTaskStatus::InProgress,
            'due_date' => now()->addWeek(),
            'created_by' => $owner->id,
        ]);
        $event->tasks()->create([
            'title' => 'Pesan konsumsi',
            'assignee_membership_id' => $memberships['anggota']->id,
            'status' => EventTaskStatus::Todo,
            'due_date' => now()->addWeeks(2),
            'created_by' => $owner->id,
        ]);

        $event->participants()->create(['membership_id' => $memberships['anggota']->id]);
        $event->participants()->create(['membership_id' => $memberships['warga']->id]);

        Announcement::factory()->create([
            'organization_id' => $organization->id,
            'title' => 'Iuran Bulan Agustus Dibuka',
            'body' => 'Mohon segera membayar iuran bulan Agustus melalui bendahara sebelum tanggal 31.',
            'published_at' => now()->subDays(2),
            'created_by' => $owner->id,
        ]);

        $this->seedFinance($organization, $event, $owner, $memberships['bendahara']->user);

        // Additive: creates its own 3 organizations, doesn't touch anything
        // seeded above. See MultiOrganizationSeeder's docblock for why it
        // exists — tenant isolation / RBAC / superadmin testing needs more
        // than one lonely organization to actually catch a leak.
        $this->call(MultiOrganizationSeeder::class);
    }

    private function seedFinance(Organization $organization, Event $event, User $owner, User $treasurer): void
    {
        $kasPemuda = $organization->financialAccounts()->create(['name' => 'Kas Pemuda']);
        $kasEvent = $organization->financialAccounts()->create(['name' => 'Kas Event']);

        $iuran = $organization->financialCategories()->create(['name' => 'Iuran Anggota', 'transaction_type' => TransactionType::Income]);
        $sponsor = $organization->financialCategories()->create(['name' => 'Sponsor', 'transaction_type' => TransactionType::Income]);
        $konsumsi = $organization->financialCategories()->create(['name' => 'Konsumsi', 'transaction_type' => TransactionType::Expense]);
        $sewaTempat = $organization->financialCategories()->create(['name' => 'Sewa Tempat', 'transaction_type' => TransactionType::Expense]);

        $submitTransaction = app(SubmitTransactionAction::class);
        $approveTransaction = app(ApproveFinancialTransactionAction::class);

        // Approved opening balance and event expense — a healthy, realistic ledger.
        $opening = $organization->financialTransactions()->create([
            'financial_account_id' => $kasPemuda->id,
            'category_id' => $sponsor->id,
            'amount' => 2_000_000,
            'transaction_type' => TransactionType::Income,
            'description' => 'Donasi pembuka kas dari sponsor lokal',
            'transaction_date' => now()->subMonth(),
            'created_by' => $treasurer->id,
        ]);
        $submitTransaction->handle($opening);
        $approveTransaction->handle($opening, $owner);

        $eventExpense = $organization->financialTransactions()->create([
            'financial_account_id' => $kasEvent->id,
            'category_id' => $konsumsi->id,
            'event_id' => $event->id,
            'amount' => 350_000,
            'transaction_type' => TransactionType::Expense,
            'description' => 'Konsumsi rapat persiapan',
            'transaction_date' => now()->subDays(3),
            'created_by' => $treasurer->id,
        ]);
        $submitTransaction->handle($eventExpense);
        $approveTransaction->handle($eventExpense, $owner);

        // One transaction still awaiting approval, to demonstrate the queue.
        $pendingExpense = $organization->financialTransactions()->create([
            'financial_account_id' => $kasEvent->id,
            'category_id' => $sewaTempat->id,
            'event_id' => $event->id,
            'amount' => 500_000,
            'transaction_type' => TransactionType::Expense,
            'description' => 'Sewa tempat untuk HUT Kemerdekaan',
            'transaction_date' => now(),
            'created_by' => $treasurer->id,
        ]);
        $submitTransaction->handle($pendingExpense);

        // Monthly dues for every member, with one already paid.
        app(GenerateMonthlyDuesAction::class)->handle($organization, now()->startOfMonth(), 25_000);

        $anggotaMembership = $organization->memberships()->whereHas('user', fn ($q) => $q->where('email', 'anggota@rukunmuda.test'))->first();
        $anggotaDue = MemberDue::where('organization_id', $organization->id)
            ->where('membership_id', $anggotaMembership->id)
            ->first();

        if ($anggotaDue) {
            app(RecordDuePaymentAction::class)->handle(
                $anggotaDue,
                $treasurer,
                $anggotaDue->amount_due,
                now(),
                $kasPemuda,
                $iuran,
            );
        }

        // A published, publicly-visible report for last month — the
        // organization's public transparency page has something to show.
        $lastMonthStart = now()->subMonthNoOverflow()->startOfMonth();
        $report = app(GenerateFinancialReportAction::class)->handle($organization, $treasurer, [
            'title' => 'Laporan Kas '.$lastMonthStart->translatedFormat('F Y'),
            'report_type' => FinancialReportType::Monthly,
            'period_start' => $lastMonthStart->toDateString(),
            'period_end' => $lastMonthStart->copy()->endOfMonth()->toDateString(),
            'visibility' => FinancialReportVisibility::Public,
        ]);
        app(PublishFinancialReportAction::class)->handle($report, $owner);
    }
}
