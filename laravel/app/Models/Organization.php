<?php

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'slug',
        'require_transaction_approval',
        'public_transparency_enabled',
    ];

    protected function casts(): array
    {
        return [
            'require_transaction_approval' => 'boolean',
            'public_transparency_enabled' => 'boolean',
            'settings' => 'array',
        ];
    }

    /**
     * @return HasMany<OrganizationMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * @return HasMany<Announcement, $this>
     */
    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    /**
     * @return HasMany<FinancialAccount, $this>
     */
    public function financialAccounts(): HasMany
    {
        return $this->hasMany(FinancialAccount::class);
    }

    /**
     * @return HasMany<FinancialCategory, $this>
     */
    public function financialCategories(): HasMany
    {
        return $this->hasMany(FinancialCategory::class);
    }

    /**
     * @return HasMany<FinancialTransaction, $this>
     */
    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    /**
     * @return HasMany<MemberDue, $this>
     */
    public function memberDues(): HasMany
    {
        return $this->hasMany(MemberDue::class);
    }

    /**
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * @return HasMany<FinancialReport, $this>
     */
    public function financialReports(): HasMany
    {
        return $this->hasMany(FinancialReport::class);
    }
}
