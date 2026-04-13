<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseRequisition extends Model
{
    public static function nextRequestNumber(int $companyId): int
    {
        return (int) (static::query()->where('company_id', $companyId)->max('request_number') ?? 0) + 1;
    }

    protected $fillable = [
        'company_id',
        'request_number',
        'request_date',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(WarehouseRequisitionLine::class)->orderBy('sort_order')->orderBy('id');
    }

    public function outwardVouchers(): HasMany
    {
        return $this->hasMany(WarehouseOutwardVoucher::class);
    }
}
