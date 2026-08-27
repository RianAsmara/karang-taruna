<?php

namespace App\Models;

use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed>|null $previous_values
 * @property array<string, mixed>|null $new_values
 */
class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use HasFactory, HasUlids;

    const UPDATED_AT = null;

    protected $fillable = [
        'organization_id',
        'actor_id',
        'action',
        'model_type',
        'model_id',
        'previous_values',
        'new_values',
    ];

    protected function casts(): array
    {
        return [
            'previous_values' => 'array',
            'new_values' => 'array',
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
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
