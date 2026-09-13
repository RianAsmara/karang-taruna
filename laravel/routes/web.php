<?php

use App\Http\Controllers\AttendanceScanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\MembershipExitRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationInviteController;
use App\Http\Controllers\OrganizationLandingController;
use App\Http\Controllers\PublicTransparencyController;
use App\Http\Controllers\ReportController;
use App\Models\Organization;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

// Public, unauthenticated pages — throttled (see AppServiceProvider's
// 'public-pages' limiter): generous for real visitors, but not
// unbounded for scraping/DoS-ish abuse the way an unthrottled public
// GET would be.
Route::middleware('throttle:public-pages')->group(function () {
    Route::get('org/{organization:slug}', [OrganizationLandingController::class, 'show'])->name('organizations.landing');
    Route::get('org/{organization:slug}/transparency', [PublicTransparencyController::class, 'show'])->name('organizations.transparency');

    // The canonical, shareable report page — intentionally not gated by
    // 'auth': a PUBLIC + PUBLISHED report is meant to be viewable while
    // logged out. Visibility for every other case is enforced inside the
    // controller itself.
    Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::post('reports/{report}/share', [ReportController::class, 'share'])->name('reports.share');
    Route::get('reports/{report}/qr', [ReportController::class, 'qr'])->name('reports.qr');
    Route::get('reports/{report}/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
});

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('organizations/create', function () {
        return Inertia::render('organizations/create');
    })->can('create', Organization::class)->name('organizations.create');
    Route::post('organizations', [OrganizationController::class, 'store'])->name('organizations.store');

    // Accepting an invite needs auth but NOT an existing organization — a
    // newcomer has none, so this one stays outside the 'current-org' group
    // that the invite management routes live in (routes/organization.php).
    Route::get('join/{token}', [OrganizationInviteController::class, 'accept'])->name('organizations.invites.accept');

    // Leaving is a request the chair decides on — see the
    // membership_exit_requests migration for why.
    Route::post('membership/exit-requests', [MembershipExitRequestController::class, 'store'])->name('membership.exit-requests.store');
    Route::post('membership/exit-requests/{exitRequest}/decide', [MembershipExitRequestController::class, 'decide'])->name('membership.exit-requests.decide');
    Route::get('organizations/mine', [OrganizationController::class, 'options'])->name('organizations.options');
    Route::post('organizations/switch', [OrganizationController::class, 'switch'])->name('organizations.switch');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

    Route::delete('reports/{report}', [FinancialReportController::class, 'destroy'])->name('reports.destroy');
    Route::post('reports/{report}/submit', [FinancialReportController::class, 'submit'])->name('reports.submit');
    Route::post('reports/{report}/approve', [FinancialReportController::class, 'approve'])->name('reports.approve');
    Route::post('reports/{report}/request-revision', [FinancialReportController::class, 'requestRevision'])->name('reports.request-revision');
    Route::post('reports/{report}/publish', [FinancialReportController::class, 'publish'])->name('reports.publish');
    Route::post('reports/{report}/archive', [FinancialReportController::class, 'archive'])->name('reports.archive');
    Route::post('reports/{report}/revise', [FinancialReportController::class, 'revise'])->name('reports.revise');

    // Not org-scoped by 'current-org' — the QR token identifies the
    // event/organization directly, independent of the scanning user's
    // own "current" org.
    Route::get('attendance/{qrToken}', [AttendanceScanController::class, 'show'])->name('attendance.scan');
    Route::post('attendance/{qrToken}', [AttendanceScanController::class, 'store'])->name('attendance.scan.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/organization.php';
require __DIR__.'/superadmin.php';
