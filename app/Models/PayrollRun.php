<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_FINALIZED = 'finalized';

    protected $fillable = [
        'company_id',
        'period_month',
        'status',
        'finalized_at',
        'employees_count',
        'gross_total',
        'allowances_total',
        'deductions_total',
        'employee_ss_total',
        'company_ss_total',
        'net_total',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'finalized_at' => 'datetime',
            'gross_total' => 'decimal:2',
            'allowances_total' => 'decimal:2',
            'deductions_total' => 'decimal:2',
            'employee_ss_total' => 'decimal:2',
            'company_ss_total' => 'decimal:2',
            'net_total' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PayrollRunLine::class);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}
