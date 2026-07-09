<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Invoice extends Model
{
    public static function nextInvoiceNumber(int $companyId): int
    {
        return (int) (static::query()->where('company_id', $companyId)->max('invoice_number') ?? 0) + 1;
    }

    protected $fillable = [
        'company_id',
        'customer_id',
        'currency_id',
        'sales_order_id',
        'goods_issue_reference',
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
        'paper_invoice_path',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
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
        return $this->hasMany(InvoiceLine::class)->orderBy('sort_order')->orderBy('id');
    }

    protected static function booted(): void
    {
        static::deleting(function (Invoice $invoice): void {
            if ($invoice->paper_invoice_path && Storage::disk('public')->exists($invoice->paper_invoice_path)) {
                Storage::disk('public')->delete($invoice->paper_invoice_path);
            }
        });

        static::updating(function (Invoice $invoice): void {
            if (! $invoice->isDirty('paper_invoice_path')) {
                return;
            }

            $incoming = $invoice->paper_invoice_path;
            if ($incoming === null || $incoming === '') {
                $invoice->paper_invoice_path = $invoice->getOriginal('paper_invoice_path');

                return;
            }

            $previous = $invoice->getOriginal('paper_invoice_path');
            if ($previous && $previous !== $incoming && Storage::disk('public')->exists($previous)) {
                Storage::disk('public')->delete($previous);
            }
        });
    }
}
