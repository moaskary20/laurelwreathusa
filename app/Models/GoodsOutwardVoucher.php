<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsOutwardVoucher extends Model
{
    public static function nextVoucherNumber(int $companyId): int
    {
        return (int) (static::query()->where('company_id', $companyId)->max('voucher_number') ?? 0) + 1;
    }

    protected $fillable = [
        'company_id',
        'voucher_number',
        'voucher_date',
        'customer_id',
        'sales_order_id',
        'account_group_id',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function accountGroup(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
