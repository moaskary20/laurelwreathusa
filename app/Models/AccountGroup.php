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
        'code',
        'name_ar',
        'account_type',
        'normal_balance',
        'is_postable',
        'is_active',
        'allow_manual_entries',
        'sort_order',
        'level',
        'path',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_postable' => 'boolean',
            'is_active' => 'boolean',
            'allow_manual_entries' => 'boolean',
            'level' => 'integer',
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

    public function labelWithCode(): string
    {
        $code = is_string($this->code) && $this->code !== '' ? $this->code.' - ' : '';

        return $code.$this->name_ar;
    }

    /**
     * @return array<int, string> id => indented label for searchable selects
     */
    public static function indentedOptionsForCompany(int $companyId): array
    {
        $all = static::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return self::buildIndentedOptions($all, null, 0);
    }

    /**
     * حسابات الترحيل فقط (postable + active) لاستخدامها في القيود والبنوك.
     *
     * @return array<int, string>
     */
    public static function indentedPostingOptionsForCompany(int $companyId): array
    {
        $all = static::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_postable', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return self::buildIndentedOptions($all, null, 0);
    }

    /**
     * كل الحسابات (تشمل غير المفعلة) لاستخدام شاشة شجرة الحسابات.
     *
     * @return array<int, string>
     */
    public static function indentedAllOptionsForCompany(int $companyId): array
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
            $label = $g->labelWithCode();
            $out[$g->id] = ($depth > 0 ? str_repeat('— ', $depth) : '').$label;
            $out += self::buildIndentedOptions($all, $g->id, $depth + 1);
        }

        return $out;
    }
}
