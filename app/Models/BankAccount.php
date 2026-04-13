<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccount extends Model
{
    protected $fillable = [
        'company_id',
        'name_ar',
        'name_en',
        'branch_ar',
        'branch_en',
        'swift_code',
        'iban',
        'account_number',
        'nickname',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
