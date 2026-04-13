<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountGroup extends Model
{
    protected $fillable = [
        'company_id',
        'parent_id',
        'name_ar',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return array<int, string> id => indented label for searchable selects
     */
    public static function indentedOptionsForCompany(int $companyId): array
    {
        $all = static::query()
            ->where('company_id', $companyId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return self::buildIndentedOptions($all, null, 0);
    }

    /**
     * @return array<int, string>
     */
    protected static function buildIndentedOptions(Collection $all, ?int $parentId, int $depth): array
    {
        $out = [];
        $items = $all->filter(function (AccountGroup $g) use ($parentId): bool {
            if ($parentId === null) {
                return $g->parent_id === null;
            }

            return (int) $g->parent_id === $parentId;
        })->values();

        foreach ($items as $g) {
            $out[$g->id] = ($depth > 0 ? str_repeat('— ', $depth) : '').$g->name_ar;
            $out += self::buildIndentedOptions($all, $g->id, $depth + 1);
        }

        return $out;
    }
}
