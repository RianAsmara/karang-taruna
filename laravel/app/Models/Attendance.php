<?php

namespace App\Models;

use App\Enums\AttendanceMethod;
use App\Enums\AttendanceStatus;
use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property AttendanceStatus $status
 * @property AttendanceMethod $method
 * @property Carbon $checked_in_at
 */
class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'attendance_session_id',
        'membership_id',
        'status',
        'method',
        'checked_in_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AttendanceStatus::class,
            'method' => AttendanceMethod::class,
            'checked_in_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AttendanceSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    /**
     * @return BelongsTo<OrganizationMembership, $this>
     */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(OrganizationMembership::class);
    }
}
