<?php

namespace App\Models;

use Database\Factories\VoteResponseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoteResponse extends Model
{
    /** @use HasFactory<VoteResponseFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'vote_id',
        'vote_option_id',
        'membership_id',
    ];

    /**
     * @return BelongsTo<Vote, $this>
     */
    public function vote(): BelongsTo
    {
        return $this->belongsTo(Vote::class);
    }

    /**
     * @return BelongsTo<VoteOption, $this>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(VoteOption::class, 'vote_option_id');
    }

    /**
     * @return BelongsTo<OrganizationMembership, $this>
     */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(OrganizationMembership::class);
    }
}
