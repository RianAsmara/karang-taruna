<?php

use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationLandingController;
use App\Http\Controllers\PublicTransparencyController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

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

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::post('organizations', [OrganizationController::class, 'store'])->name('organizations.store');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

    Route::delete('reports/{report}', [FinancialReportController::class, 'destroy'])->name('reports.destroy');
    Route::post('reports/{report}/publish', [FinancialReportController::class, 'publish'])->name('reports.publish');
    Route::post('reports/{report}/archive', [FinancialReportController::class, 'archive'])->name('reports.archive');
    Route::post('reports/{report}/revise', [FinancialReportController::class, 'revise'])->name('reports.revise');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/organization.php';
