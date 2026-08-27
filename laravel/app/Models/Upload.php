<?php

namespace App\Models;

use Database\Factories\UploadFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Upload extends Model
{
    /** @use HasFactory<UploadFactory> */
    use HasFactory, HasUlids;

    const UPDATED_AT = null;

    protected $fillable = [
        'organization_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'uploaded_by',
    ];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
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
        static::deleting(function (self $upload) {
            Storage::disk($upload->disk)->delete($upload->path);
        });
    }
}
