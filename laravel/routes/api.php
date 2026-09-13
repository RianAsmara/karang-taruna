<?php

use App\Http\Controllers\Api\V1\AnnouncementController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\EventTaskController;
use App\Http\Controllers\Api\V1\FinancialAccountController;
use App\Http\Controllers\Api\V1\FinancialCategoryController;
use App\Http\Controllers\Api\V1\FinancialReportController;
use App\Http\Controllers\Api\V1\FinancialTransactionAttachmentController;
use App\Http\Controllers\Api\V1\FinancialTransactionController;
use App\Http\Controllers\Api\V1\InventoryItemController;
use App\Http\Controllers\Api\V1\InventoryLoanController;
use App\Http\Controllers\Api\V1\MemberController;
use App\Http\Controllers\Api\V1\MemberDueController;
use App\Http\Controllers\Api\V1\MembershipExitRequestController;
use App\Http\Controllers\Api\V1\MyTasksController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\OrganizationThemeController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SponsorController;
use App\Http\Controllers\Api\V1\Superadmin\OrganizationController as SuperadminOrganizationController;
use App\Http\Controllers\Api\V1\TransparencyController;
use App\Http\Controllers\Api\V1\UploadController;
use App\Http\Controllers\Api\V1\VoteController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');

    // Mixed audience, mirroring the web's canonical /reports/{report}
    // page: a PUBLIC + PUBLISHED report is viewable without a token.
    // Authorization for every other case happens inside the controller.
    Route::get('finance/reports/{report}', [ReportController::class, 'show'])->name('finance.reports.show');
    Route::get('finance/reports/{report}/share', [ReportController::class, 'share'])->name('finance.reports.share');
    Route::get('finance/reports/{report}/qr', [ReportController::class, 'qr'])->name('finance.reports.qr');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('auth/sessions', [AuthController::class, 'sessions'])->name('auth.sessions.index');
        Route::delete('auth/sessions/{tokenId}', [AuthController::class, 'destroySession'])->name('auth.sessions.destroy');

        // Outside 'current-org' on purpose — a user with no organization yet
        // must still be able to create their first one.
        Route::post('organizations', [OrganizationController::class, 'store'])->name('organizations.store');
        Route::get('organizations/mine', [OrganizationController::class, 'mine'])->name('organizations.mine');
        Route::post('organizations/switch', [OrganizationController::class, 'switch'])->name('organizations.switch');

        // User-level, not organization-scoped — outside 'current-org'.
        Route::patch('profile/phone-visibility', [ProfileController::class, 'updatePhoneVisibility'])->name('profile.phone-visibility.update');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

        // Every route below resolves its Organization from the
        // authenticated user's membership via 'current-org' — never from
        // a client-supplied organization_id.
        Route::middleware('current-org')->group(function () {
            Route::get('organizations/current', [OrganizationController::class, 'current'])->name('organizations.current');
            Route::get('organizations/current/theme', [OrganizationThemeController::class, 'show'])->name('organizations.theme.show');
            Route::put('organizations/current/theme', [OrganizationThemeController::class, 'update'])->name('organizations.theme.update');

            Route::get('members', [MemberController::class, 'index'])->name('members.index');
            Route::post('members', [MemberController::class, 'store'])->name('members.store');
            Route::get('members/{member}', [MemberController::class, 'show'])->name('members.show');
            Route::patch('members/{member}', [MemberController::class, 'updateRole'])->name('members.update-role');
            Route::post('members/{member}/transfer-chair', [MemberController::class, 'transferChair'])->name('members.transfer-chair');
            Route::delete('members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');
            Route::get('members/{member}/responsibilities', [MemberController::class, 'responsibilities'])->name('members.responsibilities');
            Route::get('members/{member}/activity', [MemberController::class, 'activity'])->name('members.activity');

            Route::get('events', [EventController::class, 'index'])->name('events.index');
            Route::post('events', [EventController::class, 'store'])->name('events.store');
            Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');
            Route::patch('events/{event}', [EventController::class, 'update'])->name('events.update');

            Route::get('events/{event}/tasks', [EventTaskController::class, 'index'])->name('events.tasks.index');
            Route::post('events/{event}/tasks', [EventTaskController::class, 'store'])->name('events.tasks.store');
            Route::patch('events/{event}/tasks/{task}/status', [EventTaskController::class, 'updateStatus'])->name('events.tasks.update-status');

            Route::post('events/{event}/attendance', [AttendanceController::class, 'store'])->name('events.attendance.store');
            Route::get('events/{event}/attendance', [AttendanceController::class, 'index'])->name('events.attendance.index');
            Route::get('events/{event}/attendance/qr', [AttendanceController::class, 'qr'])->name('events.attendance.qr');

            Route::get('my/tasks', [MyTasksController::class, 'index'])->name('my.tasks.index');

            // Leaving is a request the chair decides on — see the
            // membership_exit_requests migration for why.
            Route::get('membership/exit-requests', [MembershipExitRequestController::class, 'index'])->name('membership.exit-requests.index');
            Route::get('membership/exit-requests/mine', [MembershipExitRequestController::class, 'mine'])->name('membership.exit-requests.mine');
            Route::post('membership/exit-requests', [MembershipExitRequestController::class, 'store'])->name('membership.exit-requests.store');
            Route::post('membership/exit-requests/{exitRequest}/decide', [MembershipExitRequestController::class, 'decide'])->name('membership.exit-requests.decide');

            Route::get('announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
            Route::get('announcements/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.show');

            Route::get('finance/dues', [MemberDueController::class, 'index'])->name('finance.dues.index');
            Route::post('finance/dues/generate-monthly', [MemberDueController::class, 'generateMonthly'])->name('finance.dues.generate-monthly');
            Route::post('finance/dues/{due}/payments', [MemberDueController::class, 'recordPayment'])->name('finance.dues.payments.store');
            Route::post('finance/dues/{due}/notify', [MemberDueController::class, 'notify'])->name('finance.dues.notify');

            Route::get('finance/accounts', [FinancialAccountController::class, 'index'])->name('finance.accounts.index');
            Route::get('finance/categories', [FinancialCategoryController::class, 'index'])->name('finance.categories.index');

            Route::get('finance/transactions', [FinancialTransactionController::class, 'index'])->name('finance.transactions.index');
            Route::post('finance/transactions', [FinancialTransactionController::class, 'store'])->name('finance.transactions.store');
            Route::get('finance/transactions/{transaction}', [FinancialTransactionController::class, 'show'])->name('finance.transactions.show');

            Route::post('finance/transactions/{transaction}/attachments', [FinancialTransactionAttachmentController::class, 'store'])->name('finance.transactions.attachments.store');
            Route::delete('finance/transactions/{transaction}/attachments/{attachment}', [FinancialTransactionAttachmentController::class, 'destroy'])->name('finance.transactions.attachments.destroy');
            Route::get('finance/transactions/{transaction}/attachments/{attachment}/download', [FinancialTransactionAttachmentController::class, 'download'])->name('finance.transactions.attachments.download');

            Route::get('finance/reports', [FinancialReportController::class, 'index'])->name('finance.reports.index');
            Route::post('finance/reports', [FinancialReportController::class, 'store'])->name('finance.reports.store');
            Route::patch('finance/reports/{report}/note', [FinancialReportController::class, 'updateNote'])->name('finance.reports.update-note');
            Route::post('finance/reports/{report}/submit', [FinancialReportController::class, 'submit'])->name('finance.reports.submit');
            Route::post('finance/reports/{report}/approve', [FinancialReportController::class, 'approve'])->name('finance.reports.approve');
            Route::post('finance/reports/{report}/request-revision', [FinancialReportController::class, 'requestRevision'])->name('finance.reports.request-revision');
            Route::post('finance/reports/{report}/publish', [FinancialReportController::class, 'publish'])->name('finance.reports.publish');
            Route::post('finance/reports/{report}/archive', [FinancialReportController::class, 'archive'])->name('finance.reports.archive');

            Route::get('transparency', [TransparencyController::class, 'index'])->name('transparency.index');

            Route::get('inventory', [InventoryItemController::class, 'index'])->name('inventory.index');
            Route::post('inventory', [InventoryItemController::class, 'store'])->name('inventory.store');
            Route::get('inventory/{inventoryItem}', [InventoryItemController::class, 'show'])->name('inventory.show');
            Route::patch('inventory/{inventoryItem}', [InventoryItemController::class, 'update'])->name('inventory.update');
            Route::delete('inventory/{inventoryItem}', [InventoryItemController::class, 'destroy'])->name('inventory.destroy');

            Route::post('inventory/{inventoryItem}/loans', [InventoryLoanController::class, 'store'])->name('inventory.loans.store');
            Route::post('inventory/loans/{loan}/return', [InventoryLoanController::class, 'return'])->name('inventory.loans.return');

            Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
            Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
            Route::get('documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
            Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
            Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

            Route::get('sponsors', [SponsorController::class, 'index'])->name('sponsors.index');
            Route::post('sponsors', [SponsorController::class, 'store'])->name('sponsors.store');
            Route::get('sponsors/{sponsor}', [SponsorController::class, 'show'])->name('sponsors.show');
            Route::patch('sponsors/{sponsor}/status', [SponsorController::class, 'updateStatus'])->name('sponsors.update-status');

            // No POST /votes — "who may create a vote" (mobile-ux.md §
            // Open product decisions) is now resolved on web (pengurus,
            // via VotePolicy::create), but mobile's own design stays
            // read+submit-only for Voting by deliberate choice ("no
            // creation flow in this phase"), so the API mirrors that —
            // not a gap, a still-current design decision.
            Route::get('votes', [VoteController::class, 'index'])->name('votes.index');
            Route::get('votes/{vote}', [VoteController::class, 'show'])->name('votes.show');
            Route::post('votes/{vote}/responses', [VoteController::class, 'storeResponse'])->name('votes.responses.store');
            Route::get('votes/{vote}/results', [VoteController::class, 'results'])->name('votes.results');

            // One upload endpoint for every file (jpg/jpeg/png/pdf) —
            // domain-specific tables (financial_transaction_attachments,
            // documents) stay separate since they carry their own FKs and
            // authorization rules; this is for callers that just need a
            // generic authorized store+retrieve.
            Route::post('uploads', [UploadController::class, 'store'])->name('uploads.store');
            Route::get('uploads/{upload}', [UploadController::class, 'show'])->name('uploads.show');
        });

        // Deliberately outside 'current-org' — a superadmin has no
        // membership of their own to resolve an organization from; these
        // routes take the target organization from the URL instead.
        // Read-only, cross-organization, every view audited — see
        // EnsureSuperadmin and Superadmin\OrganizationController.
        Route::middleware('superadmin')->prefix('superadmin')->name('superadmin.')->group(function () {
            Route::get('organizations', [SuperadminOrganizationController::class, 'index'])->name('organizations.index');
            Route::get('organizations/{organization}', [SuperadminOrganizationController::class, 'show'])->name('organizations.show');
        });
    });
});
