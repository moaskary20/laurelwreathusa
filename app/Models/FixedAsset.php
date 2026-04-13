<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedAsset extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'asset_category_id',
        'historical_cost',
        'purchase_date',
        'useful_life_years',
        'annual_depreciation_rate',
        'usage_start_date',
        'depreciation_start_date',
        'supplier_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'historical_cost' => 'decimal:2',
            'annual_depreciation_rate' => 'decimal:2',
            'purchase_date' => 'date',
            'usage_start_date' => 'date',
            'depreciation_start_date' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assetCategory(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function accumulatedDepreciationAsOf(Carbon|string $asAt): float
    {
        $cost = (float) $this->historical_cost;
        $rate = (float) $this->annual_depreciation_rate / 100.0;
        $asAtCarbon = $asAt instanceof Carbon ? $asAt->copy()->endOfDay() : Carbon::parse($asAt)->endOfDay();
        $start = $this->depreciation_start_date
            ?? $this->usage_start_date
            ?? $this->purchase_date;
        if ($start === null) {
            return 0.0;
        }
        $startCarbon = Carbon::parse($start)->startOfDay();
        if ($asAtCarbon->lt($startCarbon)) {
            return 0.0;
        }
        $years = $startCarbon->diffInDays($asAtCarbon) / 365.25;
        $lifeCap = $this->useful_life_years !== null
            ? min($years, (float) $this->useful_life_years)
            : $years;
        $annual = $cost * $rate;

        return round(min($cost, $annual * $lifeCap), 2);
    }

    public function netBookValueAsOf(Carbon|string $asAt): float
    {
        return round((float) $this->historical_cost - $this->accumulatedDepreciationAsOf($asAt), 2);
    }
}
