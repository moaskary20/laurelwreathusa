<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsInwardVoucher extends Model
{
    public static function nextVoucherNumber(int $companyId): int
    {
        return (int) (static::query()->where('company_id', $companyId)->max('voucher_number') ?? 0) + 1;
    }

    protected $fillable = [
        'company_id',
        'voucher_number',
        'voucher_date',
        'due_date',
        'supplier_id',
        'currency_id',
        'purchase_order_id',
        'subtotal_before_tax',
        'tax_total',
        'grand_total',
        'total_in_words',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'voucher_date' => 'datetime',
            'due_date' => 'date',
            'subtotal_before_tax' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GoodsInwardVoucherLine::class)->orderBy('sort_order')->orderBy('id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
