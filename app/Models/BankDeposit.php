<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankDeposit extends Model
{
    public static function nextDepositNumber(int $companyId): int
    {
        return (int) (static::query()->where('company_id', $companyId)->max('deposit_number') ?? 0) + 1;
    }

    protected $fillable = [
        'company_id',
        'user_id',
        'deposit_number',
        'deposit_date',
        'from_account_group_id',
        'to_account_group_id',
        'currency_id',
        'amount',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'deposit_date' => 'date',
            'amount' => 'decimal:2',
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

    public function fromAccountGroup(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class, 'from_account_group_id');
    }

    public function toAccountGroup(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class, 'to_account_group_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
