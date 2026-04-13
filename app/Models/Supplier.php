<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'company_id',
        'name_ar',
        'name_en',
        'address_ar',
        'address_en',
        'phone',
        'fax',
        'email',
        'sales_tax_number',
        'payment_method',
        'credit_limit',
        'opening_balance',
        'balance',
        'account_group_id',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'opening_balance' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function accountGroup(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(SupplierLedgerEntry::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    /**
     * @return array<string, string>
     */
    public static function paymentMethodOptions(): array
    {
        return [
            'cash' => 'نقدي',
            'net_10_from_invoice' => '10 ايام من تاريخ الفاتوره',
            'net_30_from_invoice' => '30 يوم من تاريخ الفاتوره',
        ];
    }

    public function paymentMethodLabel(): string
    {
        return self::paymentMethodOptions()[$this->payment_method] ?? $this->payment_method;
    }
}
