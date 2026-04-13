<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsInwardVoucherLine extends Model
{
    protected $fillable = [
        'goods_inward_voucher_id',
        'service_product_id',
        'description',
        'unit_label',
        'quantity',
        'unit_price',
        'amount_before_tax',
        'tax_rate',
        'tax_amount',
        'line_total',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'amount_before_tax' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function goodsInwardVoucher(): BelongsTo
    {
        return $this->belongsTo(GoodsInwardVoucher::class);
    }

    public function serviceProduct(): BelongsTo
    {
        return $this->belongsTo(ServiceProduct::class);
    }
}
