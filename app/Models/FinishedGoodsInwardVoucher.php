<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinishedGoodsInwardVoucher extends Model
{
    public static function nextVoucherNumber(int $companyId): int
    {
        return (int) (static::query()->where('company_id', $companyId)->max('voucher_number') ?? 0) + 1;
    }

    protected $fillable = [
        'company_id',
        'voucher_number',
        'voucher_date',
        'credit_account_group_id',
        'total_cost',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'voucher_date' => 'datetime',
            'total_cost' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creditAccountGroup(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class, 'credit_account_group_id');
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(FinishedGoodsInwardVoucherLine::class)->orderBy('sort_order')->orderBy('id');
    }
}
