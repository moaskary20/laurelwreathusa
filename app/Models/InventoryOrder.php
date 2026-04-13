<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryOrder extends Model
{
    public static function nextOrderNumber(int $companyId): int
    {
        return (int) (static::query()->where('company_id', $companyId)->max('order_number') ?? 0) + 1;
    }

    protected $fillable = [
        'company_id',
        'customer_id',
        'order_number',
        'order_date',
        'total_value',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'datetime',
            'total_value' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InventoryOrderLine::class)->orderBy('sort_order')->orderBy('id');
    }
}
