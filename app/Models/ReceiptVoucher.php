<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceiptVoucher extends Model
{
    public static function nextReceiptNumber(int $companyId): int
    {
        return (int) (static::query()->where('company_id', $companyId)->max('receipt_number') ?? 0) + 1;
    }

    protected $fillable = [
        'company_id',
        'user_id',
        'receipt_number',
        'receipt_date',
        'customer_id',
        'payment_method',
        'payment_kind',
        'account_group_id',
        'total_amount',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'receipt_date' => 'date',
            'total_amount' => 'decimal:2',
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

    public function accountGroup(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ReceiptVoucherLine::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * أول رقم فاتورة مرتبط بتسديد الفواتير (للعرض في القائمة).
     */
    public function getFirstInvoiceNumberAttribute(): ?string
    {
        if ($this->relationLoaded('lines')) {
            $line = $this->lines->first();
        } else {
            $line = $this->lines()->with('invoice')->first();
        }

        return $line?->invoice !== null
            ? (string) $line->invoice->invoice_number
            : null;
    }
}
