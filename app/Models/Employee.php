<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    protected $fillable = [
        'company_id',
        'name_ar',
        'name_en',
        'national_id',
        'social_security_number',
        'hiring_date',
        'termination_date',
        'job_number',
        'basic_salary',
        'social_security_rate',
        'company_social_security_rate',
        'commission_rate',
        'marital_status',
        'phone_allowance',
        'deduction_type',
        'cost_center_id',
    ];

    protected function casts(): array
    {
        return [
            'hiring_date' => 'date',
            'termination_date' => 'date',
            'basic_salary' => 'decimal:2',
            'social_security_rate' => 'decimal:4',
            'company_social_security_rate' => 'decimal:4',
            'commission_rate' => 'decimal:4',
            'phone_allowance' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function maritalStatusLabel(): string
    {
        return match ($this->marital_status) {
            'married' => 'متزوج',
            'single' => 'اعزب',
            default => $this->marital_status,
        };
    }
}
