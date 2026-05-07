<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingPeriod extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'company_id',
        'period_month',
        'status',
        'closed_by',
        'closed_at',
        'opened_by',
        'opened_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'closed_at' => 'datetime',
            'opened_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
