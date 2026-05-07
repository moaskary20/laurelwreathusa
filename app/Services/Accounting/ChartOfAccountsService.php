<?php

namespace App\Services\Accounting;

use App\Models\AccountGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ChartOfAccountsService
{
    public function assertCanPostToAccount(int $companyId, int $accountGroupId): void
    {
        $account = AccountGroup::query()
            ->where('company_id', $companyId)
            ->whereKey($accountGroupId)
            ->first();

        if (! $account instanceof AccountGroup) {
            throw ValidationException::withMessages(['account_group_id' => 'الحساب غير موجود.']);
        }

        if (! $account->is_active) {
            throw ValidationException::withMessages(['account_group_id' => 'الحساب غير مفعل.']);
        }

        if (! $account->is_postable) {
            throw ValidationException::withMessages(['account_group_id' => 'لا يمكن الترحيل على حساب تجميعي (غير طرفي).']);
        }
    }

    public function assertNoCycle(?int $accountId, ?int $newParentId): void
    {
        if ($accountId === null || $newParentId === null) {
            return;
        }

        if ($accountId === $newParentId) {
            throw ValidationException::withMessages(['parent_id' => 'لا يمكن اختيار الحساب نفسه كأب.']);
        }

        // Walk up from new parent; if we hit accountId => cycle.
        $seen = [];
        $current = $newParentId;
        while ($current !== null) {
            if (isset($seen[$current])) {
                break;
            }
            $seen[$current] = true;

            if ($current === $accountId) {
                throw ValidationException::withMessages(['parent_id' => 'هذا الاختيار يسبب حلقة في شجرة الحسابات.']);
            }

            $current = AccountGroup::query()->whereKey($current)->value('parent_id');
            $current = $current !== null ? (int) $current : null;
        }
    }

    /**
     * Create account and ensure parent becomes non-postable.
     *
     * @param array<string, mixed> $data
     */
    public function createAccount(int $companyId, array $data): AccountGroup
    {
        $parentId = isset($data['parent_id']) && $data['parent_id'] !== '' ? (int) $data['parent_id'] : null;
        $this->assertNoCycle(null, $parentId);

        return DB::transaction(function () use ($companyId, $data, $parentId): AccountGroup {
            $parent = null;
            if ($parentId !== null) {
                $parent = AccountGroup::query()
                    ->where('company_id', $companyId)
                    ->whereKey($parentId)
                    ->first();

                if (! $parent instanceof AccountGroup) {
                    throw ValidationException::withMessages(['parent_id' => 'الحساب الأب غير موجود.']);
                }
            }

            $account = AccountGroup::query()->create([
                'company_id' => $companyId,
                'parent_id' => $parentId,
                'code' => $data['code'] ?? null,
                'name_ar' => $data['name_ar'] ?? '',
                'account_type' => $data['account_type'] ?? null,
                'normal_balance' => $data['normal_balance'] ?? null,
                'is_postable' => (bool) ($data['is_postable'] ?? true),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'allow_manual_entries' => (bool) ($data['allow_manual_entries'] ?? true),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'level' => $parent ? ((int) ($parent->level ?? 0) + 1) : 0,
                'path' => null,
            ]);

            $account->update([
                'path' => $parent && is_string($parent->path) && $parent->path !== ''
                    ? $parent->path.'.'.$account->id
                    : (string) $account->id,
            ]);

            if ($parent instanceof AccountGroup && $parent->is_postable) {
                $parent->update(['is_postable' => false]);
            }

            return $account->fresh();
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateAccount(AccountGroup $account, array $data): AccountGroup
    {
        $parentId = isset($data['parent_id']) && $data['parent_id'] !== '' ? (int) $data['parent_id'] : null;
        $this->assertNoCycle((int) $account->id, $parentId);

        return DB::transaction(function () use ($account, $data, $parentId): AccountGroup {
            $parent = null;
            if ($parentId !== null) {
                $parent = AccountGroup::query()
                    ->where('company_id', $account->company_id)
                    ->whereKey($parentId)
                    ->first();

                if (! $parent instanceof AccountGroup) {
                    throw ValidationException::withMessages(['parent_id' => 'الحساب الأب غير موجود.']);
                }
            }

            $account->update([
                'parent_id' => $parentId,
                'code' => $data['code'] ?? $account->code,
                'name_ar' => $data['name_ar'] ?? $account->name_ar,
                'account_type' => $data['account_type'] ?? $account->account_type,
                'normal_balance' => $data['normal_balance'] ?? $account->normal_balance,
                'is_postable' => (bool) ($data['is_postable'] ?? $account->is_postable),
                'is_active' => (bool) ($data['is_active'] ?? $account->is_active),
                'allow_manual_entries' => (bool) ($data['allow_manual_entries'] ?? $account->allow_manual_entries),
                'sort_order' => (int) ($data['sort_order'] ?? $account->sort_order),
                'level' => $parent ? ((int) ($parent->level ?? 0) + 1) : 0,
            ]);

            $account->refresh();
            $account->update([
                'path' => $parent && is_string($parent->path) && $parent->path !== ''
                    ? $parent->path.'.'.$account->id
                    : (string) $account->id,
            ]);

            if ($parent instanceof AccountGroup && $parent->is_postable) {
                $parent->update(['is_postable' => false]);
            }

            return $account->fresh();
        });
    }

    /**
     * Delete only if safe; otherwise throw.
     */
    public function deleteAccount(AccountGroup $account): void
    {
        $hasChildren = AccountGroup::query()
            ->where('company_id', $account->company_id)
            ->where('parent_id', $account->id)
            ->exists();

        if ($hasChildren) {
            throw ValidationException::withMessages(['account' => 'لا يمكن حذف حساب لديه حسابات فرعية.']);
        }

        // Hard usage checks (restrict tables).
        $usedInJournal = DB::table('journal_entry_lines')->where('account_group_id', $account->id)->exists();
        $usedInBankDepositFrom = DB::table('bank_deposits')->where('from_account_group_id', $account->id)->exists();
        $usedInBankDepositTo = DB::table('bank_deposits')->where('to_account_group_id', $account->id)->exists();

        if ($usedInJournal || $usedInBankDepositFrom || $usedInBankDepositTo) {
            throw ValidationException::withMessages(['account' => 'لا يمكن حذف حساب عليه حركات/مستندات. استخدم الإيقاف بدلًا من ذلك.']);
        }

        $account->delete();
    }

    public function deactivate(AccountGroup $account): void
    {
        $account->update(['is_active' => false]);
    }

    public function activate(AccountGroup $account): void
    {
        $account->update(['is_active' => true]);
    }
}
