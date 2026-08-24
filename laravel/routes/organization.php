<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\EventCommitteeController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventParticipantController;
use App\Http\Controllers\EventTaskController;
use App\Http\Controllers\FinancialAccountController;
use App\Http\Controllers\FinancialCategoryController;
use App\Http\Controllers\FinancialTransactionController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberDueController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'current-org'])->group(function () {
    Route::get('members', [MemberController::class, 'index'])->name('members.index');
    Route::post('members', [MemberController::class, 'store'])->name('members.store');
    Route::patch('members/{member}', [MemberController::class, 'updateRole'])->name('members.update-role');
    Route::delete('members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');

    Route::get('events', [EventController::class, 'index'])->name('events.index');
    Route::get('events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('events', [EventController::class, 'store'])->name('events.store');
    Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::get('events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
    Route::patch('events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::delete('events/{event}', [EventController::class, 'destroy'])->name('events.destroy');

    Route::post('events/{event}/committee', [EventCommitteeController::class, 'store'])->name('events.committee.store');
    Route::delete('events/{event}/committee/{committee}', [EventCommitteeController::class, 'destroy'])->name('events.committee.destroy');

    Route::post('events/{event}/tasks', [EventTaskController::class, 'store'])->name('events.tasks.store');
    Route::patch('events/{event}/tasks/{task}', [EventTaskController::class, 'update'])->name('events.tasks.update');
    Route::patch('events/{event}/tasks/{task}/status', [EventTaskController::class, 'updateStatus'])->name('events.tasks.update-status');
    Route::delete('events/{event}/tasks/{task}', [EventTaskController::class, 'destroy'])->name('events.tasks.destroy');

    Route::post('events/{event}/participants', [EventParticipantController::class, 'store'])->name('events.participants.store');
    Route::delete('events/{event}/participants/{participant}', [EventParticipantController::class, 'destroy'])->name('events.participants.destroy');

    Route::get('announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('announcements/create', [AnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('announcements/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.show');
    Route::get('announcements/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('announcements.edit');
    Route::patch('announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    Route::get('finance/accounts', [FinancialAccountController::class, 'index'])->name('finance.accounts.index');
    Route::post('finance/accounts', [FinancialAccountController::class, 'store'])->name('finance.accounts.store');
    Route::patch('finance/accounts/{account}', [FinancialAccountController::class, 'update'])->name('finance.accounts.update');
    Route::delete('finance/accounts/{account}', [FinancialAccountController::class, 'destroy'])->name('finance.accounts.destroy');

    Route::get('finance/categories', [FinancialCategoryController::class, 'index'])->name('finance.categories.index');
    Route::post('finance/categories', [FinancialCategoryController::class, 'store'])->name('finance.categories.store');
    Route::patch('finance/categories/{category}', [FinancialCategoryController::class, 'update'])->name('finance.categories.update');
    Route::delete('finance/categories/{category}', [FinancialCategoryController::class, 'destroy'])->name('finance.categories.destroy');

    Route::get('finance/transactions', [FinancialTransactionController::class, 'index'])->name('finance.transactions.index');
    Route::get('finance/transactions/create', [FinancialTransactionController::class, 'create'])->name('finance.transactions.create');
    Route::post('finance/transactions', [FinancialTransactionController::class, 'store'])->name('finance.transactions.store');
    Route::get('finance/transactions/{transaction}', [FinancialTransactionController::class, 'show'])->name('finance.transactions.show');
    Route::get('finance/transactions/{transaction}/edit', [FinancialTransactionController::class, 'edit'])->name('finance.transactions.edit');
    Route::patch('finance/transactions/{transaction}', [FinancialTransactionController::class, 'update'])->name('finance.transactions.update');
    Route::delete('finance/transactions/{transaction}', [FinancialTransactionController::class, 'destroy'])->name('finance.transactions.destroy');
    Route::post('finance/transactions/{transaction}/submit', [FinancialTransactionController::class, 'submit'])->name('finance.transactions.submit');
    Route::post('finance/transactions/{transaction}/approve', [FinancialTransactionController::class, 'approve'])->name('finance.transactions.approve');
    Route::post('finance/transactions/{transaction}/reject', [FinancialTransactionController::class, 'reject'])->name('finance.transactions.reject');

    Route::get('finance/dues', [MemberDueController::class, 'index'])->name('finance.dues.index');
    Route::post('finance/dues', [MemberDueController::class, 'store'])->name('finance.dues.store');
    Route::post('finance/dues/generate-monthly', [MemberDueController::class, 'generateMonthly'])->name('finance.dues.generate-monthly');
    Route::delete('finance/dues/{due}', [MemberDueController::class, 'destroy'])->name('finance.dues.destroy');
    Route::post('finance/dues/{due}/payments', [MemberDueController::class, 'recordPayment'])->name('finance.dues.payments.store');
});
