<?php

namespace App\Http\Controllers;

use App\Actions\Finance\ApproveFinancialTransactionAction;
use App\Actions\Finance\RejectFinancialTransactionAction;
use App\Actions\Finance\SubmitTransactionAction;
use App\Enums\TransactionType;
use App\Http\Requests\FinancialTransaction\StoreFinancialTransactionRequest;
use App\Http\Requests\FinancialTransaction\UpdateFinancialTransactionRequest;
use App\Models\FinancialTransaction;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class FinancialTransactionController extends Controller
{
    public function index(Request $request, Organization $organization): Response
    {
        $this->authorize('viewAny', [FinancialTransaction::class, $organization]);

        $user = Auth::user();
        $isTreasurer = $user->isTreasurerOf($organization);

        $query = $organization->financialTransactions()
            ->with(['financialAccount:id,name', 'category:id,name', 'event:id,title'])
            ->orderByDesc('transaction_date');

        if (! $isTreasurer) {
            $query->approved();
        }

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->string('event_id')->value());
        }

        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $query->where('description', 'like', '%'.$search.'%');
        }

        $transactions = $query->get()->map(fn (FinancialTransaction $transaction) => [
            'id' => $transaction->id,
            'amount' => $transaction->amount,
            'transactionType' => $transaction->transaction_type->value,
            'transactionTypeLabel' => $transaction->transaction_type->label(),
            'status' => $transaction->status->value,
            'statusLabel' => $transaction->status->label(),
            'description' => $transaction->description,
            'transactionDate' => $transaction->transaction_date->toDateString(),
            'accountName' => $transaction->financialAccount->name,
            'categoryName' => $transaction->category?->name,
            'eventTitle' => $transaction->event?->title,
        ]);

        return Inertia::render('finance/transactions/index', [
            'transactions' => $transactions,
            'isTreasurer' => $isTreasurer,
            'canCreate' => Auth::user()->can('create', [FinancialTransaction::class, $organization]),
            'eventId' => $request->string('event_id')->value() ?: null,
            'filters' => ['search' => $search !== '' ? $search : null],
        ]);
    }

    public function create(Organization $organization): Response
    {
        $this->authorize('create', [FinancialTransaction::class, $organization]);

        return Inertia::render('finance/transactions/create', $this->formOptions($organization));
    }

    public function store(StoreFinancialTransactionRequest $request, Organization $organization): RedirectResponse
    {
        $transaction = $organization->financialTransactions()->create([
            ...$request->validated(),
            'created_by' => Auth::id(),
        ]);

        return to_route('finance.transactions.show', $transaction);
    }

    public function show(FinancialTransaction $transaction): Response
    {
        $this->authorize('view', $transaction);

        $transaction->load([
            'financialAccount:id,name',
            'relatedAccount:id,name',
            'category:id,name',
            'event:id,title',
            'creator:id,name',
            'reviewer:id,name',
            'attachments.uploader:id,name',
        ]);

        return Inertia::render('finance/transactions/show', [
            'transaction' => [
                'id' => $transaction->id,
                'amount' => $transaction->amount,
                'transactionType' => $transaction->transaction_type->value,
                'transactionTypeLabel' => $transaction->transaction_type->label(),
                'status' => $transaction->status->value,
                'statusLabel' => $transaction->status->label(),
                'description' => $transaction->description,
                'transactionDate' => $transaction->transaction_date->toDateString(),
                'accountName' => $transaction->financialAccount->name,
                'relatedAccountName' => $transaction->relatedAccount?->name,
                'categoryName' => $transaction->category?->name,
                'eventTitle' => $transaction->event?->title,
                'creatorName' => $transaction->creator->name,
                'reviewerName' => $transaction->reviewer?->name,
                'reviewedAt' => $transaction->reviewed_at?->toIso8601String(),
                'attachments' => $transaction->attachments->map(fn ($attachment) => [
                    'id' => $attachment->id,
                    'originalName' => $attachment->original_name,
                    'mimeType' => $attachment->mime_type,
                    'sizeBytes' => $attachment->size_bytes,
                    'uploaderName' => $attachment->uploader->name,
                    'uploadedAt' => $attachment->created_at->toIso8601String(),
                    'downloadUrl' => route('finance.transactions.attachments.download', [$transaction, $attachment]),
                ]),
            ],
            'canEdit' => Auth::user()->can('update', $transaction),
            'canDelete' => Auth::user()->can('delete', $transaction),
            'canSubmit' => Auth::user()->can('submit', $transaction),
            'canReview' => Auth::user()->can('review', $transaction),
            'canManageEvidence' => Auth::user()->can('manageEvidence', $transaction),
        ]);
    }

    public function edit(FinancialTransaction $transaction): Response
    {
        $this->authorize('update', $transaction);

        return Inertia::render('finance/transactions/edit', [
            ...$this->formOptions($transaction->organization),
            'transaction' => [
                'id' => $transaction->id,
                'financial_account_id' => $transaction->financial_account_id,
                'related_account_id' => $transaction->related_account_id,
                'category_id' => $transaction->category_id,
                'event_id' => $transaction->event_id,
                'amount' => $transaction->amount,
                'transaction_type' => $transaction->transaction_type->value,
                'description' => $transaction->description,
                'transaction_date' => $transaction->transaction_date->toDateString(),
            ],
        ]);
    }

    public function update(UpdateFinancialTransactionRequest $request, FinancialTransaction $transaction): RedirectResponse
    {
        $transaction->update($request->validated());

        return to_route('finance.transactions.show', $transaction);
    }

    public function destroy(FinancialTransaction $transaction): RedirectResponse
    {
        $this->authorize('delete', $transaction);

        $transaction->delete();

        return to_route('finance.transactions.index');
    }

    public function submit(FinancialTransaction $transaction, SubmitTransactionAction $submitTransaction): RedirectResponse
    {
        $this->authorize('submit', $transaction);

        $submitTransaction->handle($transaction);

        return back();
    }

    public function approve(FinancialTransaction $transaction, ApproveFinancialTransactionAction $approveTransaction): RedirectResponse
    {
        $this->authorize('review', $transaction);

        $approveTransaction->handle($transaction, Auth::user());

        return back();
    }

    public function reject(FinancialTransaction $transaction, RejectFinancialTransactionAction $rejectTransaction): RedirectResponse
    {
        $this->authorize('review', $transaction);

        $rejectTransaction->handle($transaction, Auth::user());

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(Organization $organization): array
    {
        return [
            'accounts' => $organization->financialAccounts()->orderBy('name')->get(['id', 'name']),
            'categories' => $organization->financialCategories()->orderBy('name')->get(['id', 'name', 'transaction_type']),
            'events' => $organization->events()->orderByDesc('start_at')->get(['id', 'title']),
            'transactionTypes' => array_map(
                fn (TransactionType $t) => ['value' => $t->value, 'label' => $t->label()],
                TransactionType::cases(),
            ),
        ];
    }
}
