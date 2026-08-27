<?php

namespace App\Models;

use Database\Factories\VoteOptionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoteOption extends Model
{
    /** @use HasFactory<VoteOptionFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'vote_id',
        'label',
        'position',
    ];

    /**
     * @return BelongsTo<Vote, $this>
     */
    public function vote(): BelongsTo
    {
        return $this->belongsTo(Vote::class);
    }

    /**
     * @return HasMany<VoteResponse, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(VoteResponse::class);
    }
}
