<?php

namespace App\Models;

use App\Enums\ReportShareChannel;
use Database\Factories\ReportShareLogFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property ReportShareChannel $channel
 * @property Carbon $shared_at
 */
class ReportShareLog extends Model
{
    /** @use HasFactory<ReportShareLogFactory> */
    use HasFactory, HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'financial_report_id',
        'channel',
        'shared_by',
    ];

    protected function casts(): array
    {
        return [
            'channel' => ReportShareChannel::class,
            'shared_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<FinancialReport, $this>
     */
    public function financialReport(): BelongsTo
    {
        return $this->belongsTo(FinancialReport::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sharer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }
}
