<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseRequisitionLine extends Model
{
    protected $fillable = [
        'warehouse_requisition_id',
        'service_product_id',
        'description',
        'quantity',
        'cost_center_id',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
        ];
    }

    public function warehouseRequisition(): BelongsTo
    {
        return $this->belongsTo(WarehouseRequisition::class);
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
