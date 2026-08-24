<?php

namespace App\Models;

use App\Enums\EventParticipantStatus;
use Database\Factories\EventParticipantFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property EventParticipantStatus $status
 */
class EventParticipant extends Model
{
    /** @use HasFactory<EventParticipantFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'event_id',
        'membership_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => EventParticipantStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<OrganizationMembership, $this>
     */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(OrganizationMembership::class);
    }
}
