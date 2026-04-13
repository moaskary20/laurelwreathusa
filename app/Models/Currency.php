<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Currency extends Model
{
    protected $fillable = [
        'company_id',
        'name_ar',
        'name_en',
        'exchange_rate',
        'is_main',
    ];

    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:6',
            'is_main' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Currency $currency): void {
            if (! $currency->is_main) {
                return;
            }

            $query = static::query()->where('company_id', $currency->company_id);

            if ($currency->exists) {
                $query->whereKeyNot($currency->getKey());
            }

            $query->update(['is_main' => false]);
        });
    }
}
