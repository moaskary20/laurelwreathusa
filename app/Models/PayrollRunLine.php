<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRunLine extends Model
{
    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'cost_center_id',
        'basic_salary',
        'allowances_total',
        'deductions_total',
        'employee_social_security',
        'company_social_security',
        'social_security_total',
        'net_salary',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'allowances_total' => 'decimal:2',
            'deductions_total' => 'decimal:2',
            'employee_social_security' => 'decimal:2',
            'company_social_security' => 'decimal:2',
            'social_security_total' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollRunLineItem::class);
    }
}
