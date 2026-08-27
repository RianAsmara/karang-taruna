<?php

use App\Http\Controllers\Superadmin\OrganizationController;
use Illuminate\Support\Facades\Route;

// Deliberately outside 'current-org' — a superadmin has no membership of
// their own to resolve an organization from; these routes take the
// target organization from the URL instead. Read-only, cross-organization,
// every view audited — see EnsureSuperadmin and Superadmin\OrganizationController.
Route::middleware(['auth', 'superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('organizations', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::get('organizations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
});
