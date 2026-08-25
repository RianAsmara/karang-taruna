<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\EventTaskController;
use App\Http\Controllers\Api\V1\FinancialAccountController;
use App\Http\Controllers\Api\V1\FinancialReportController;
use App\Http\Controllers\Api\V1\FinancialTransactionController;
use App\Http\Controllers\Api\V1\MemberController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\TransparencyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');

    // Mixed audience, mirroring the web's canonical /reports/{report}
    // page: a PUBLIC + PUBLISHED report is viewable without a token.
    // Authorization for every other case happens inside the controller.
    Route::get('finance/reports/{report}', [ReportController::class, 'show'])->name('finance.reports.show');
    Route::get('finance/reports/{report}/share', [ReportController::class, 'share'])->name('finance.reports.share');
    Route::get('finance/reports/{report}/qr', [ReportController::class, 'qr'])->name('finance.reports.qr');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        // Every route below resolves its Organization from the
        // authenticated user's membership via 'current-org' — never from
        // a client-supplied organization_id.
        Route::middleware('current-org')->group(function () {
            Route::get('organizations/current', [OrganizationController::class, 'current'])->name('organizations.current');

            Route::get('members', [MemberController::class, 'index'])->name('members.index');
            Route::post('members', [MemberController::class, 'store'])->name('members.store');
            Route::get('members/{member}', [MemberController::class, 'show'])->name('members.show');

            Route::get('events', [EventController::class, 'index'])->name('events.index');
            Route::post('events', [EventController::class, 'store'])->name('events.store');
            Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');
            Route::patch('events/{event}', [EventController::class, 'update'])->name('events.update');

            Route::get('events/{event}/tasks', [EventTaskController::class, 'index'])->name('events.tasks.index');
            Route::post('events/{event}/tasks', [EventTaskController::class, 'store'])->name('events.tasks.store');

            Route::get('finance/accounts', [FinancialAccountController::class, 'index'])->name('finance.accounts.index');

            Route::get('finance/transactions', [FinancialTransactionController::class, 'index'])->name('finance.transactions.index');
            Route::post('finance/transactions', [FinancialTransactionController::class, 'store'])->name('finance.transactions.store');

            Route::get('finance/reports', [FinancialReportController::class, 'index'])->name('finance.reports.index');
            Route::post('finance/reports/{report}/publish', [FinancialReportController::class, 'publish'])->name('finance.reports.publish');
            Route::post('finance/reports/{report}/archive', [FinancialReportController::class, 'archive'])->name('finance.reports.archive');

            Route::get('transparency', [TransparencyController::class, 'index'])->name('transparency.index');
        });
    });
});
