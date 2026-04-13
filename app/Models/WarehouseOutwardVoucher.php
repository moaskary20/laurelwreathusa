<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseOutwardVoucher extends Model
{
    public static function nextVoucherNumber(int $companyId): int
    {
        return (int) (static::query()->where('company_id', $companyId)->max('voucher_number') ?? 0) + 1;
    }

    protected $fillable = [
        'company_id',
        'voucher_number',
        'voucher_date',
        'warehouse_requisition_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'voucher_date' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouseRequisition(): BelongsTo
    {
        return $this->belongsTo(WarehouseRequisition::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(WarehouseOutwardVoucherLine::class)->orderBy('sort_order')->orderBy('id');
    }
}
