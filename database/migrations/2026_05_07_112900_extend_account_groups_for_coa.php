<?php

use App\Models\AccountGroup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_groups', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->after('parent_id');
            $table->string('account_type', 20)->nullable()->after('code'); // asset/liability/equity/revenue/expense
            $table->string('normal_balance', 10)->nullable()->after('account_type'); // debit/credit
            $table->boolean('is_postable')->default(true)->after('normal_balance');
            $table->boolean('is_active')->default(true)->after('is_postable');
            $table->boolean('allow_manual_entries')->default(true)->after('is_active');
            $table->unsignedSmallInteger('level')->default(0)->after('allow_manual_entries');
            $table->string('path', 255)->nullable()->after('level');

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active', 'is_postable']);
            $table->index(['company_id', 'account_type']);
        });

        // Backfill existing rows (safe defaults + derive leaf/postable + infer type by root name).
        DB::transaction(function (): void {
            /** @var \Illuminate\Support\Collection<int, \App\Models\AccountGroup> $all */
            $all = AccountGroup::query()
                ->orderBy('id')
                ->get();

            /** @var array<int, AccountGroup> $byId */
            $byId = $all->keyBy('id')->all();

            $childrenCount = [];
            foreach ($all as $g) {
                if ($g->parent_id !== null) {
                    $pid = (int) $g->parent_id;
                    $childrenCount[$pid] = ($childrenCount[$pid] ?? 0) + 1;
                }
            }

            foreach ($all as $g) {
                $id = (int) $g->id;
                $companyId = (int) $g->company_id;

                [$level, $rootName, $path] = $this->computeMeta($g, $byId);
                [$type, $normal] = $this->inferTypeAndNormalBalance($rootName ?: $g->name_ar);

                $isLeaf = ! isset($childrenCount[$id]);

                $code = $g->code;
                if (! is_string($code) || trim($code) === '') {
                    // Default: stable 7-digit code from id (company-unique due to unique index).
                    $code = str_pad((string) $id, 7, '0', STR_PAD_LEFT);
                }

                DB::table('account_groups')
                    ->where('id', $id)
                    ->update([
                        'code' => $code,
                        'account_type' => $g->account_type ?: $type,
                        'normal_balance' => $g->normal_balance ?: $normal,
                        'is_postable' => $isLeaf ? 1 : 0,
                        'level' => $level,
                        'path' => $path,
                    ]);
            }

            // Ensure top-level roots are non-postable.
            DB::table('account_groups')
                ->whereNull('parent_id')
                ->update(['is_postable' => 0]);
        });
    }

    /**
     * @param array<int, AccountGroup> $byId
     * @return array{0:int,1:string,2:string}
     */
    private function computeMeta(AccountGroup $g, array $byId): array
    {
        $level = 0;
        $pathParts = [(string) $g->id];
        $rootName = $g->name_ar;

        $seen = [];
        $current = $g;
        while ($current->parent_id !== null) {
            $pid = (int) $current->parent_id;
            if (isset($seen[$pid])) {
                break;
            }
            $seen[$pid] = true;

            $parent = $byId[$pid] ?? null;
            if (! $parent instanceof AccountGroup) {
                break;
            }

            $level++;
            array_unshift($pathParts, (string) $parent->id);
            $rootName = $parent->name_ar;
            $current = $parent;
        }

        return [$level, (string) $rootName, implode('.', $pathParts)];
    }

    /**
     * @return array{0:string,1:string}
     */
    private function inferTypeAndNormalBalance(string $rootName): array
    {
        $name = trim($rootName);

        $type = match (true) {
            str_contains($name, 'موجود') => 'asset',
            str_contains($name, 'مطلوب') => 'liability',
            str_contains($name, 'حقوق') || str_contains($name, 'ملكية') => 'equity',
            str_contains($name, 'إيراد') => 'revenue',
            str_contains($name, 'تكلفة') => 'expense',
            str_contains($name, 'مصروف') => 'expense',
            default => 'asset',
        };

        $normal = match ($type) {
            'asset', 'expense' => 'debit',
            'liability', 'equity', 'revenue' => 'credit',
            default => 'debit',
        };

        return [$type, $normal];
    }

    public function down(): void
    {
        Schema::table('account_groups', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'account_type']);
            $table->dropIndex(['company_id', 'is_active', 'is_postable']);
            $table->dropUnique(['company_id', 'code']);

            $table->dropColumn([
                'code',
                'account_type',
                'normal_balance',
                'is_postable',
                'is_active',
                'allow_manual_entries',
                'level',
                'path',
            ]);
        });
    }
};
