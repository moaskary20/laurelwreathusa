<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalYearClosing extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'company_id',
        'year_start',
        'year_end',
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
            'year_start' => 'date',
            'year_end' => 'date',
            'closed_at' => 'datetime',
            'opened_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
