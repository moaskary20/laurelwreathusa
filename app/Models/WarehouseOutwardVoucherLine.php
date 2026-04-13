<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseOutwardVoucherLine extends Model
{
    protected $fillable = [
        'warehouse_outward_voucher_id',
        'service_product_id',
        'description',
        'quantity_requested',
        'quantity_disbursed',
        'difference',
        'cost_center_id',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity_requested' => 'decimal:4',
            'quantity_disbursed' => 'decimal:4',
            'difference' => 'decimal:4',
        ];
    }

    public function warehouseOutwardVoucher(): BelongsTo
    {
        return $this->belongsTo(WarehouseOutwardVoucher::class);
    }

    public function serviceProduct(): BelongsTo
    {
        return $this->belongsTo(ServiceProduct::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }
}
