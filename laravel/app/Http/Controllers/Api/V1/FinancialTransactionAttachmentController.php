<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FinancialTransactionAttachment\StoreFinancialTransactionAttachmentRequest;
use App\Http\Resources\FinancialTransactionAttachmentResource;
use App\Models\FinancialTransaction;
use App\Models\FinancialTransactionAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialTransactionAttachmentController extends Controller
{
    public function store(StoreFinancialTransactionAttachmentRequest $request, FinancialTransaction $transaction): JsonResponse
    {
        $file = $request->file('file');
        $disk = config('filesystems.default');

        $path = $file->store("financial-transaction-attachments/{$transaction->id}", $disk);

        $attachment = $transaction->attachments()->create([
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'uploaded_by' => Auth::id(),
        ]);

        return (new FinancialTransactionAttachmentResource($attachment->load('uploader:id,name')))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(FinancialTransaction $transaction, FinancialTransactionAttachment $attachment): JsonResponse
    {
        $this->authorize('manageEvidence', $transaction);
        abort_unless($attachment->financial_transaction_id === $transaction->id, 404);

        $attachment->delete();

        return response()->json(null, 204);
    }

    public function download(FinancialTransaction $transaction, FinancialTransactionAttachment $attachment): StreamedResponse
    {
        $this->authorize('view', $transaction);
        abort_unless($attachment->financial_transaction_id === $transaction->id, 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }
}
