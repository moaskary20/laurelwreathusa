<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollDeduction extends Model
{
    protected $fillable = [
        'company_id',
        'deduction_type',
        'amount',
        'frequency',
        'start_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'start_date' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function frequencyOptions(): array
    {
        return [
            'yearly' => 'سنوي',
            'monthly' => 'شهري',
            'quarterly' => 'ربيعي',
        ];
    }

    public function frequencyLabel(): string
    {
        return self::frequencyOptions()[$this->frequency] ?? $this->frequency;
    }
}
