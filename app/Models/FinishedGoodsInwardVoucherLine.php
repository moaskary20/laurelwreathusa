<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinishedGoodsInwardVoucherLine extends Model
{
    protected $fillable = [
        'finished_goods_inward_voucher_id',
        'service_product_id',
        'description',
        'quantity',
        'unit_cost',
        'line_total',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function finishedGoodsInwardVoucher(): BelongsTo
    {
        return $this->belongsTo(FinishedGoodsInwardVoucher::class);
    }

    public function serviceProduct(): BelongsTo
    {
        return $this->belongsTo(ServiceProduct::class);
    }
}
