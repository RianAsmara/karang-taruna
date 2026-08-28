<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EventCommitteeController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventParticipantController;
use App\Http\Controllers\EventTaskController;
use App\Http\Controllers\FinancialAccountController;
use App\Http\Controllers\FinancialCategoryController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\FinancialTransactionAttachmentController;
use App\Http\Controllers\FinancialTransactionController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\InventoryLoanController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberDueController;
use App\Http\Controllers\SponsorController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\TransparencyController;
use App\Http\Controllers\VoteController;
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

    Route::post('events/{event}/attendance', [AttendanceController::class, 'store'])->name('events.attendance.store');
    Route::get('events/{event}/attendance', [AttendanceController::class, 'index'])->name('events.attendance.index');
    Route::get('events/{event}/attendance/qr', [AttendanceController::class, 'qr'])->name('events.attendance.qr');

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

    Route::post('finance/transactions/{transaction}/attachments', [FinancialTransactionAttachmentController::class, 'store'])->name('finance.transactions.attachments.store');
    Route::delete('finance/transactions/{transaction}/attachments/{attachment}', [FinancialTransactionAttachmentController::class, 'destroy'])->name('finance.transactions.attachments.destroy');
    Route::get('finance/transactions/{transaction}/attachments/{attachment}/download', [FinancialTransactionAttachmentController::class, 'download'])->name('finance.transactions.attachments.download');

    Route::get('finance/dues', [MemberDueController::class, 'index'])->name('finance.dues.index');
    Route::post('finance/dues', [MemberDueController::class, 'store'])->name('finance.dues.store');
    Route::post('finance/dues/generate-monthly', [MemberDueController::class, 'generateMonthly'])->name('finance.dues.generate-monthly');
    Route::delete('finance/dues/{due}', [MemberDueController::class, 'destroy'])->name('finance.dues.destroy');
    Route::post('finance/dues/{due}/payments', [MemberDueController::class, 'recordPayment'])->name('finance.dues.payments.store');

    Route::get('finance/reports', [FinancialReportController::class, 'index'])->name('finance.reports.index');
    Route::get('finance/reports/create', [FinancialReportController::class, 'create'])->name('finance.reports.create');
    Route::post('finance/reports', [FinancialReportController::class, 'store'])->name('finance.reports.store');

    Route::get('transparansi', [TransparencyController::class, 'index'])->name('transparency.index');
    Route::post('transparansi/toggle-public', [TransparencyController::class, 'toggle'])->name('transparency.toggle');

    Route::get('organisasi/tema', [ThemeController::class, 'edit'])->name('theme.edit');
    Route::post('organisasi/tema/logo', [ThemeController::class, 'uploadLogo'])->name('theme.upload-logo');
    Route::patch('organisasi/tema', [ThemeController::class, 'update'])->name('theme.update');

    Route::get('votes', [VoteController::class, 'index'])->name('votes.index');
    Route::get('votes/create', [VoteController::class, 'create'])->name('votes.create');
    Route::post('votes', [VoteController::class, 'store'])->name('votes.store');
    Route::get('votes/{vote}', [VoteController::class, 'show'])->name('votes.show');
    Route::post('votes/{vote}/responses', [VoteController::class, 'storeResponse'])->name('votes.responses.store');

    Route::get('inventory', [InventoryItemController::class, 'index'])->name('inventory.index');
    Route::get('inventory/create', [InventoryItemController::class, 'create'])->name('inventory.create');
    Route::post('inventory', [InventoryItemController::class, 'store'])->name('inventory.store');
    Route::get('inventory/{inventoryItem}', [InventoryItemController::class, 'show'])->name('inventory.show');
    Route::get('inventory/{inventoryItem}/edit', [InventoryItemController::class, 'edit'])->name('inventory.edit');
    Route::patch('inventory/{inventoryItem}', [InventoryItemController::class, 'update'])->name('inventory.update');
    Route::delete('inventory/{inventoryItem}', [InventoryItemController::class, 'destroy'])->name('inventory.destroy');

    Route::post('inventory/{inventoryItem}/loans', [InventoryLoanController::class, 'store'])->name('inventory.loans.store');
    Route::post('inventory/loans/{loan}/return', [InventoryLoanController::class, 'return'])->name('inventory.loans.return');

    Route::get('sponsors', [SponsorController::class, 'index'])->name('sponsors.index');
    Route::get('sponsors/create', [SponsorController::class, 'create'])->name('sponsors.create');
    Route::post('sponsors', [SponsorController::class, 'store'])->name('sponsors.store');
    Route::get('sponsors/{sponsor}', [SponsorController::class, 'show'])->name('sponsors.show');
    Route::patch('sponsors/{sponsor}/status', [SponsorController::class, 'updateStatus'])->name('sponsors.update-status');

    Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('documents/create', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
});
