<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    public static function nextInvoiceNumber(int $companyId): int
    {
        return (int) (static::query()->where('company_id', $companyId)->max('invoice_number') ?? 0) + 1;
    }

    protected $fillable = [
        'company_id',
        'supplier_id',
        'currency_id',
        'purchase_order_id',
        'entry_voucher_reference',
        'supplier_invoice_number',
        'invoice_number',
        'invoice_date',
        'due_date',
        'line_kind',
        'subtotal_before_discount',
        'discount_amount',
        'total_after_discount',
        'tax_amount',
        'grand_total',
        'total_in_words',
        'bank_account_id',
        'invoice_text_id',
        'user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'datetime',
            'due_date' => 'date',
            'subtotal_before_discount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_after_discount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
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

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function invoiceText(): BelongsTo
    {
        return $this->belongsTo(InvoiceText::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceLine::class)->orderBy('sort_order')->orderBy('id');
    }
}
