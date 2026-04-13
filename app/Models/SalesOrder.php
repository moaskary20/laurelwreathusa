<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    public static function nextOrderNumber(int $companyId): int
    {
        return (int) (static::query()->where('company_id', $companyId)->max('order_number') ?? 0) + 1;
    }

    protected $fillable = [
        'company_id',
        'customer_id',
        'currency_id',
        'order_number',
        'order_date',
        'due_date',
        'total_value',
        'line_kind',
        'bank_account_id',
        'invoice_text_id',
        'user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'datetime',
            'due_date' => 'date',
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

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
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
        return $this->hasMany(SalesOrderLine::class)->orderBy('sort_order')->orderBy('id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
