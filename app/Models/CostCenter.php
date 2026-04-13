<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostCenter extends Model
{
    protected $fillable = [
        'company_id',
        'name_ar',
        'name_en',
        'code',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function serviceProducts(): HasMany
    {
        return $this->hasMany(ServiceProduct::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
