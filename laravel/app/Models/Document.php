<?php

namespace App\Models;

use App\Enums\DocumentCategory;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property DocumentCategory $category
 * @property Carbon|null $archived_at
 */
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'title',
        'category',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'event_id',
        'uploaded_by',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'archived_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    protected static function booted(): void
    {
        static::deleting(function (self $document) {
            Storage::disk($document->disk)->delete($document->path);
        });
    }
}
