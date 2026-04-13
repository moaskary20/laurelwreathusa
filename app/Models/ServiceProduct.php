<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceProduct extends Model
{
    protected $fillable = [
        'company_id',
        'kind',
        'name_ar',
        'name_en',
        'code',
        'sale_price',
        'stock_quantity',
        'unit_cost',
        'account_group_id',
        'cost_center_id',
        'tax_id',
    ];

    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:2',
            'stock_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function inventoryLineValue(): float
    {
        return round((float) $this->stock_quantity * (float) $this->unit_cost, 2);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function accountGroup(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * @return array<string, string>
     */
    public static function kindOptions(): array
    {
        return [
            'service' => 'خدمة',
            'product' => 'منتج',
        ];
    }

    public function kindLabel(): string
    {
        return self::kindOptions()[$this->kind] ?? $this->kind;
    }
}
