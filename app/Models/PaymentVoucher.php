<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentVoucher extends Model
{
    public static function nextPaymentNumber(int $companyId): int
    {
        return (int) (static::query()->where('company_id', $companyId)->max('payment_number') ?? 0) + 1;
    }

    protected $fillable = [
        'company_id',
        'user_id',
        'payment_number',
        'payment_date',
        'supplier_id',
        'payment_method',
        'payment_kind',
        'account_group_id',
        'total_amount',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function accountGroup(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PaymentVoucherLine::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * أول رقم فاتورة مشتريات مرتبط بتسديد الفواتير (للعرض في القائمة).
     */
    public function getFirstPurchaseInvoiceNumberAttribute(): ?string
    {
        if ($this->relationLoaded('lines')) {
            $line = $this->lines->first();
        } else {
            $line = $this->lines()->with('purchaseInvoice')->first();
        }

        return $line?->purchaseInvoice !== null
            ? (string) $line->purchaseInvoice->invoice_number
            : null;
    }
}
