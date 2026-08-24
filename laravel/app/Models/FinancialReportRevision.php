<?php

namespace App\Models;

use Database\Factories\FinancialReportRevisionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialReportRevision extends Model
{
    /** @use HasFactory<FinancialReportRevisionFactory> */
    use HasFactory, HasUlids;

    const UPDATED_AT = null;

    protected $fillable = [
        'financial_report_id',
        'revision_number',
        'snapshot',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
