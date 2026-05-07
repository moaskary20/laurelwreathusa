<?php

namespace Tests\Feature;

use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\Accounting\ChartOfAccountsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ChartOfAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_posting_is_blocked_for_inactive_or_non_postable_accounts(): void
    {
        $this->seed();

        $company = Company::query()->firstOrFail();

        $header = AccountGroup::query()->create([
            'company_id' => $company->id,
            'parent_id' => null,
            'code' => '1000000',
            'name_ar' => 'الموجودات',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'is_postable' => false,
            'is_active' => true,
            'allow_manual_entries' => true,
            'sort_order' => 1,
            'level' => 0,
            'path' => null,
        ]);

        $inactive = AccountGroup::query()->create([
            'company_id' => $company->id,
            'parent_id' => $header->id,
            'code' => '1100000',
            'name_ar' => 'حساب غير مفعل',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'is_postable' => true,
            'is_active' => false,
            'allow_manual_entries' => true,
            'sort_order' => 1,
            'level' => 1,
            'path' => null,
        ]);

        $svc = app(ChartOfAccountsService::class);

        $this->expectException(ValidationException::class);
        $svc->assertCanPostToAccount($company->id, $header->id);

        try {
            $svc->assertCanPostToAccount($company->id, $inactive->id);
            $this->fail('Expected ValidationException for inactive account.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('account_group_id', $e->errors());
        }
    }

    public function test_delete_is_blocked_when_account_has_posted_usage(): void
    {
        $this->seed();

        $company = Company::query()->firstOrFail();

        $account = AccountGroup::query()->create([
            'company_id' => $company->id,
            'parent_id' => null,
            'code' => '2000000',
            'name_ar' => 'حساب طرفي',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'is_postable' => true,
            'is_active' => true,
            'allow_manual_entries' => true,
            'sort_order' => 1,
            'level' => 0,
            'path' => null,
        ]);

        $entry = JournalEntry::query()->create([
            'company_id' => $company->id,
            'user_id' => null,
            'entry_number' => 1,
            'entry_date' => now()->toDateString(),
            'currency_id' => null,
            'title' => 'اختبار',
            'notes' => null,
        ]);

        JournalEntryLine::query()->create([
            'journal_entry_id' => $entry->id,
            'account_group_id' => $account->id,
            'description' => null,
            'debit' => 10,
            'credit' => 0,
            'debit_foreign' => 0,
            'credit_foreign' => 0,
            'customer_id' => null,
            'supplier_id' => null,
            'sort_order' => 0,
        ]);

        $this->expectException(ValidationException::class);
        app(ChartOfAccountsService::class)->deleteAccount($account);
    }
}
