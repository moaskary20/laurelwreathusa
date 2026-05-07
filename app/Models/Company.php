<?php

namespace App\Models;

use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model implements HasName
{
    protected $fillable = [
        'legal_name',
        'trade_name',
        'legal_type',
        'trade_category',
        'national_number',
        'registration_number',
        'sales_invoice_start',
        'objectives',
        'email',
        'phone',
        'tax_number',
        'sales_tax_number',
        'address',
        'fax',
        'po_box',
        'fiscal_year_end',
        'inventory_system',
        'inventory_pricing',
        'logo',
        'legal_name_en',
        'address_en',
        'commercial_registry_issuer',
        'branches',
        'partners',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year_end' => 'date',
            'branches' => 'array',
            'partners' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Company $company): void {
            if (! AssetCategory::query()->where('company_id', $company->id)->exists()) {
                AssetCategory::query()->create([
                    'company_id' => $company->id,
                    'name_ar' => 'تصنيف افتراضي',
                    'annual_depreciation_rate' => 0,
                ]);
            }
        });
    }

    public function offices(): HasMany
    {
        return $this->hasMany(Office::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function invoiceTexts(): HasMany
    {
        return $this->hasMany(InvoiceText::class);
    }

    public function currencies(): HasMany
    {
        return $this->hasMany(Currency::class);
    }

    public function accountGroups(): HasMany
    {
        return $this->hasMany(AccountGroup::class);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class);
    }

    public function tradeDiscounts(): HasMany
    {
        return $this->hasMany(TradeDiscount::class);
    }

    public function companyDocuments(): HasMany
    {
        return $this->hasMany(CompanyDocument::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function costCenters(): HasMany
    {
        return $this->hasMany(CostCenter::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function measurementUnits(): HasMany
    {
        return $this->hasMany(MeasurementUnit::class);
    }

    public function inventoryOrders(): HasMany
    {
        return $this->hasMany(InventoryOrder::class);
    }

    public function goodsOutwardVouchers(): HasMany
    {
        return $this->hasMany(GoodsOutwardVoucher::class);
    }

    public function goodsInwardVouchers(): HasMany
    {
        return $this->hasMany(GoodsInwardVoucher::class);
    }

    public function warehouseRequisitions(): HasMany
    {
        return $this->hasMany(WarehouseRequisition::class);
    }

    public function warehouseOutwardVouchers(): HasMany
    {
        return $this->hasMany(WarehouseOutwardVoucher::class);
    }

    public function finishedGoodsInwardVouchers(): HasMany
    {
        return $this->hasMany(FinishedGoodsInwardVoucher::class);
    }

    public function assetCategories(): HasMany
    {
        return $this->hasMany(AssetCategory::class);
    }

    public function fixedAssets(): HasMany
    {
        return $this->hasMany(FixedAsset::class);
    }

    public function assetDisposals(): HasMany
    {
        return $this->hasMany(AssetDisposal::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function payrollAllowances(): HasMany
    {
        return $this->hasMany(PayrollAllowance::class);
    }

    public function payrollDeductions(): HasMany
    {
        return $this->hasMany(PayrollDeduction::class);
    }

    public function payrollRuns(): HasMany
    {
        return $this->hasMany(PayrollRun::class);
    }

    public function serviceProducts(): HasMany
    {
        return $this->hasMany(ServiceProduct::class);
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getFilamentName(): string
    {
        return $this->trade_name ?: $this->legal_name;
    }
}
