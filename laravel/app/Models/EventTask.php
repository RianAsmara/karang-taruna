<?php

namespace App\Models;

use App\Enums\EventTaskPriority;
use App\Enums\EventTaskStatus;
use Database\Factories\EventTaskFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property EventTaskStatus $status
 * @property EventTaskPriority $priority
 * @property Carbon|null $due_date
 */
class EventTask extends Model
{
    /** @use HasFactory<EventTaskFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'event_id',
        'title',
        'description',
        'assignee_membership_id',
        'status',
        'priority',
        'due_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => EventTaskStatus::class,
            'priority' => EventTaskPriority::class,
            'due_date' => 'date',
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
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(OrganizationMembership::class, 'assignee_membership_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
