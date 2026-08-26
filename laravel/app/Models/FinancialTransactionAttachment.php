<?php

namespace App\Models;

use Database\Factories\FinancialTransactionAttachmentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Evidence (receipt, invoice, transfer proof, purchase photo) attached
 * to a transaction — supplementary documentation, not part of the
 * transaction's own historical record, so it stays attachable/removable
 * regardless of the transaction's status (see FinancialTransactionPolicy).
 * Immutable once uploaded: no update path, only create/delete.
 */
class FinancialTransactionAttachment extends Model
{
    /** @use HasFactory<FinancialTransactionAttachmentFactory> */
    use HasFactory, HasUlids;

    const UPDATED_AT = null;

    protected $fillable = [
        'financial_transaction_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'uploaded_by',
    ];

    /**
     * @return BelongsTo<FinancialTransaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class, 'financial_transaction_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    protected static function booted(): void
    {
        static::deleting(function (self $attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        });
    }
}
