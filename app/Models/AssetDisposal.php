<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDisposal extends Model
{
    protected $fillable = [
        'company_id',
        'fixed_asset_id',
        'disposal_type',
        'disposal_date',
        'historical_cost',
        'accumulated_depreciation',
        'net_book_value',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'disposal_date' => 'date',
            'historical_cost' => 'decimal:2',
            'accumulated_depreciation' => 'decimal:2',
            'net_book_value' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function disposalTypeLabel(): string
    {
        return match ($this->disposal_type) {
            'sale' => 'بيع',
            'scrap' => 'اتلاف',
            default => $this->disposal_type,
        };
    }
}
