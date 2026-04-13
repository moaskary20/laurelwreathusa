<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebitNote extends Model
{
    public const TYPE_CUSTOMER = 'customer';

    public const TYPE_SUPPLIER = 'supplier';

    public static function nextDocumentNumber(int $companyId): int
    {
        return (int) (static::query()->where('company_id', $companyId)->max('document_number') ?? 0) + 1;
    }

    protected $fillable = [
        'company_id',
        'user_id',
        'document_number',
        'document_date',
        'counterparty_type',
        'customer_id',
        'supplier_id',
        'currency_id',
        'account_group_id',
        'amount',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function counterpartyTypeOptions(): array
    {
        return [
            self::TYPE_CUSTOMER => 'عميل',
            self::TYPE_SUPPLIER => 'مورد',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function accountGroup(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class);
    }
}
