<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CompanyDocument extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'file_path',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (CompanyDocument $document): void {
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }
        });

        static::updating(function (CompanyDocument $document): void {
            if ($document->isDirty('file_path')) {
                $incoming = $document->file_path;
                if ($incoming === null || $incoming === '') {
                    $document->file_path = $document->getOriginal('file_path');

                    return;
                }

                $previous = $document->getOriginal('file_path');
                if ($previous && $previous !== $incoming && Storage::disk('public')->exists($previous)) {
                    Storage::disk('public')->delete($previous);
                }
            }
        });
    }
}
