<?php

namespace App\Http\Controllers;

use App\Http\Requests\FinancialTransactionAttachment\StoreFinancialTransactionAttachmentRequest;
use App\Models\FinancialTransaction;
use App\Models\FinancialTransactionAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialTransactionAttachmentController extends Controller
{
    public function store(StoreFinancialTransactionAttachmentRequest $request, FinancialTransaction $transaction): RedirectResponse
    {
        $file = $request->file('file');
        $disk = config('filesystems.default');

        $path = $file->store("financial-transaction-attachments/{$transaction->id}", $disk);

        $transaction->attachments()->create([
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'uploaded_by' => Auth::id(),
        ]);

        return back();
    }

    public function destroy(FinancialTransaction $transaction, FinancialTransactionAttachment $attachment): RedirectResponse
    {
        $this->authorize('manageEvidence', $transaction);
        abort_unless($attachment->financial_transaction_id === $transaction->id, 404);

        $attachment->delete();

        return back();
    }

    public function download(FinancialTransaction $transaction, FinancialTransactionAttachment $attachment): StreamedResponse
    {
        $this->authorize('view', $transaction);
        abort_unless($attachment->financial_transaction_id === $transaction->id, 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }
}
