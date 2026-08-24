<?php

use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationLandingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::get('org/{organization:slug}', [OrganizationLandingController::class, 'show'])->name('organizations.landing');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::post('organizations', [OrganizationController::class, 'store'])->name('organizations.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/organization.php';
