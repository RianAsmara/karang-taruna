<?php

namespace App\Http\Resources;

use App\Models\FinancialTransactionAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FinancialTransactionAttachment */
class FinancialTransactionAttachmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'originalName' => $this->original_name,
            'mimeType' => $this->mime_type,
            'sizeBytes' => $this->size_bytes,
            'uploaderName' => $this->whenLoaded('uploader', fn () => $this->uploader?->name),
            'uploadedAt' => $this->created_at->toIso8601String(),
            'downloadUrl' => route('api.v1.finance.transactions.attachments.download', [
                'transaction' => $this->financial_transaction_id,
                'attachment' => $this->id,
            ]),
        ];
    }
}
