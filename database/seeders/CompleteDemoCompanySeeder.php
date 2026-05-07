<?php

namespace Database\Seeders;

use App\Models\AccountGroup;
use App\Models\AssetCategory;
use App\Models\BankAccount;
use App\Models\BankDeposit;
use App\Models\Company;
use App\Models\CompanyDocument;
use App\Models\CostCenter;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\FixedAsset;
use App\Models\GoodsInwardVoucher;
use App\Models\GoodsInwardVoucherLine;
use App\Models\GoodsOutwardVoucher;
use App\Models\InventoryOrder;
use App\Models\InventoryOrderLine;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\InvoiceText;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\MeasurementUnit;
use App\Models\Office;
use App\Models\PaymentVoucher;
use App\Models\PaymentVoucherLine;
use App\Models\PayrollAllowance;
use App\Models\PayrollDeduction;
use App\Models\PayrollRun;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceLine;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\ReceiptVoucher;
use App\Models\ReceiptVoucherLine;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\ServiceProduct;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\TradeDiscount;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Ledger\CustomerInvoiceLedgerSync;
use App\Services\Ledger\PaymentVoucherLedgerSync;
use App\Services\Ledger\PurchaseInvoiceLedgerSync;
use App\Services\Ledger\ReceiptVoucherLedgerSync;
use App\Support\Payroll\PayrollRunBuilder;
use App\Support\UserPermissionRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * شركة تجريبية كاملة تغطي أغلب خصائص النظام.
 *
 * التشغيل:
 * php artisan db:seed --class=CompleteDemoCompanySeeder
 *
 * الدخول المقترح:
 * البريد: complete-demo-admin@example.com
 * كلمة المرور: password
 */
final class CompleteDemoCompanySeeder extends Seeder
{
    /** @var array<string, AccountGroup> */
    private array $accounts = [];

    public function run(): void
    {
        DB::transaction(function (): void {
            $company = $this->seedCompany();
            $office = $this->seedOffice($company);
            $user = $this->seedUser($company, $office);

            $this->seedChartOfAccounts($company);

            $currency = $this->seedCurrencies($company);
            $bankAccount = $this->seedBankAccounts($company);
            $invoiceText = $this->seedAdministrationData($company);
            $costCenters = $this->seedCostCenters($company);
            $tax = $this->seedTaxAndDiscounts($company);
            $warehouse = $this->seedInventorySetup($company);
            $products = $this->seedServicesAndProducts($company, $costCenters, $tax);
            $customer = $this->seedCustomers($company);
            $supplier = $this->seedSuppliers($company);

            $salesOrder = $this->seedSalesOrder($company, $customer, $currency, $bankAccount, $invoiceText, $user, $products);
            $invoice = $this->seedCustomerInvoice($company, $customer, $currency, $bankAccount, $invoiceText, $user, $salesOrder, $products);
            $purchaseOrder = $this->seedPurchaseOrder($company, $supplier, $currency, $bankAccount, $invoiceText, $user, $products);
            $purchaseInvoice = $this->seedPurchaseInvoice($company, $supplier, $currency, $bankAccount, $invoiceText, $user, $purchaseOrder, $products);

            $this->seedInventoryTransactions($company, $customer, $supplier, $currency, $purchaseOrder, $salesOrder, $user, $products, $warehouse);
            $this->seedVouchersAndJournal($company, $customer, $supplier, $currency, $user, $invoice, $purchaseInvoice);
            $this->seedAssets($company, $supplier, $user);
            $this->seedPayroll($company, $costCenters);
        });
    }

    private function seedCompany(): Company
    {
        return Company::query()->updateOrCreate(
            ['national_number' => 'DEMO-COMPLETE-001'],
            [
                'legal_name' => 'شركة الدليل التجريبية ذات المسؤولية المحدودة',
                'trade_name' => 'شركة الدليل التجريبية',
                'legal_name_en' => 'Complete Demo Company LLC',
                'legal_type' => 'limited_liability',
                'trade_category' => 'commercial',
                'registration_number' => 'REG-DEMO-001',
                'sales_invoice_start' => 1,
                'objectives' => 'بيانات تجريبية كاملة لتجربة خصائص النظام المحاسبي.',
                'email' => 'info@complete-demo.test',
                'phone' => '+96265000000',
                'tax_number' => 'TAX-DEMO-001',
                'sales_tax_number' => 'VAT-DEMO-001',
                'address' => 'عمان - شارع التجربة - مبنى 1',
                'address_en' => 'Amman - Demo Street - Building 1',
                'fax' => '+96265000001',
                'po_box' => '11118',
                'fiscal_year_end' => Carbon::now()->endOfYear()->toDateString(),
                'inventory_system' => 'perpetual',
                'inventory_pricing' => 'average',
                'commercial_registry_issuer' => 'وزارة الصناعة والتجارة',
                'branches' => [
                    ['name' => 'الفرع الرئيسي', 'city' => 'عمان'],
                    ['name' => 'فرع المبيعات', 'city' => 'إربد'],
                ],
                'partners' => [
                    ['name' => 'الشريك الأول', 'share' => '60%'],
                    ['name' => 'الشريك الثاني', 'share' => '40%'],
                ],
            ],
        );
    }

    private function seedOffice(Company $company): Office
    {
        return Office::query()->updateOrCreate(
            ['company_id' => $company->id, 'name_ar' => 'الإدارة العامة'],
            [
                'name_en' => 'Head Office',
                'email' => 'head.office@complete-demo.test',
                'phone' => '+96265000010',
                'phone_secondary' => '+96265000011',
                'address' => 'عمان - الإدارة العامة',
                'po_box' => '11118',
            ],
        );
    }

    private function seedUser(Company $company, Office $office): User
    {
        return User::query()->updateOrCreate(
            ['email' => 'complete-demo-admin@example.com'],
            [
                'name' => 'Complete Demo Admin',
                'name_ar' => 'مدير الشركة التجريبية',
                'name_en' => 'Complete Demo Admin',
                'username' => 'complete_demo_admin',
                'password' => Hash::make('password'),
                'phone' => '+962790000000',
                'office_id' => $office->id,
                'company_id' => $company->id,
                'is_main_user' => true,
                'is_super_user' => true,
                'permissions' => UserPermissionRegistry::allKeys(),
            ],
        );
    }

    private function seedChartOfAccounts(Company $company): void
    {
        $this->accounts = [];

        $this->account($company, '1000', 'الموجودات', 'asset', 'debit', null, false);
        $this->account($company, '1100', 'النقد والبنوك', 'asset', 'debit', '1000', false);
        $this->account($company, '1110', 'الصندوق الرئيسي', 'asset', 'debit', '1100');
        $this->account($company, '1120', 'البنك الرئيسي', 'asset', 'debit', '1100');
        $this->account($company, '1200', 'الذمم المدينة', 'asset', 'debit', '1000');
        $this->account($company, '1300', 'المخزون', 'asset', 'debit', '1000');
        $this->account($company, '1400', 'الأصول الثابتة', 'asset', 'debit', '1000');

        $this->account($company, '2000', 'المطلوبات', 'liability', 'credit', null, false);
        $this->account($company, '2100', 'الذمم الدائنة', 'liability', 'credit', '2000');
        $this->account($company, '2200', 'ضريبة مبيعات مستحقة', 'liability', 'credit', '2000');
        $this->account($company, '2300', 'مستحقات الضمان الاجتماعي', 'liability', 'credit', '2000');

        $this->account($company, '3000', 'حقوق الملكية', 'equity', 'credit', null, false);
        $this->account($company, '3100', 'رأس المال', 'equity', 'credit', '3000');

        $this->account($company, '4000', 'الإيرادات', 'revenue', 'credit', null, false);
        $this->account($company, '4100', 'إيرادات الخدمات', 'revenue', 'credit', '4000');
        $this->account($company, '4200', 'إيرادات المنتجات', 'revenue', 'credit', '4000');

        $this->account($company, '5000', 'المصروفات والتكاليف', 'expense', 'debit', null, false);
        $this->account($company, '5100', 'تكلفة البضاعة المباعة', 'expense', 'debit', '5000');
        $this->account($company, '5200', 'مصروفات رواتب', 'expense', 'debit', '5000');
        $this->account($company, '5300', 'خصومات تجارية', 'expense', 'debit', '5000');
    }

    private function account(
        Company $company,
        string $code,
        string $name,
        string $type,
        string $normalBalance,
        ?string $parentCode = null,
        bool $isPostable = true,
    ): AccountGroup {
        $parent = $parentCode !== null ? ($this->accounts[$parentCode] ?? null) : null;

        $account = AccountGroup::query()->updateOrCreate(
            ['company_id' => $company->id, 'code' => $code],
            [
                'parent_id' => $parent?->id,
                'name_ar' => $name,
                'account_type' => $type,
                'normal_balance' => $normalBalance,
                'is_postable' => $isPostable,
                'is_active' => true,
                'allow_manual_entries' => true,
                'sort_order' => (int) $code,
                'level' => $parent ? ((int) $parent->level + 1) : 0,
            ],
        );

        $account->update([
            'path' => $parent && $parent->path
                ? $parent->path.'.'.$account->id
                : (string) $account->id,
        ]);

        $this->accounts[$code] = $account->fresh();

        return $this->accounts[$code];
    }

    private function seedCurrencies(Company $company): Currency
    {
        $jod = Currency::query()->updateOrCreate(
            ['company_id' => $company->id, 'name_ar' => 'دينار أردني'],
            ['name_en' => 'Jordanian Dinar', 'exchange_rate' => 1, 'is_main' => true],
        );

        Currency::query()->updateOrCreate(
            ['company_id' => $company->id, 'name_ar' => 'دولار أمريكي'],
            ['name_en' => 'US Dollar', 'exchange_rate' => 0.709000, 'is_main' => false],
        );

        return $jod;
    }

    private function seedBankAccounts(Company $company): BankAccount
    {
        return BankAccount::query()->updateOrCreate(
            ['company_id' => $company->id, 'account_number' => 'DEMO-100200300'],
            [
                'name_ar' => 'البنك الرئيسي',
                'name_en' => 'Main Bank',
                'branch_ar' => 'فرع عمان',
                'branch_en' => 'Amman Branch',
                'swift_code' => 'DEMOJOAX',
                'iban' => 'JO94DEMO000000100200300',
                'nickname' => 'حساب العمليات',
            ],
        );
    }

    private function seedAdministrationData(Company $company): InvoiceText
    {
        CompanyDocument::query()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'شهادة تسجيل الشركة'],
            ['file_path' => 'demo/company-registration.pdf'],
        );

        return InvoiceText::query()->updateOrCreate(
            ['company_id' => $company->id, 'title' => 'نص الفاتورة الافتراضي'],
            [
                'text_ar' => 'شكراً لتعاملكم معنا. يرجى السداد حسب شروط الفاتورة.',
                'text_en' => 'Thank you for your business. Payment is due according to invoice terms.',
            ],
        );
    }

    /**
     * @return array<string, CostCenter>
     */
    private function seedCostCenters(Company $company): array
    {
        return [
            'admin' => CostCenter::query()->updateOrCreate(
                ['company_id' => $company->id, 'code' => 'ADM'],
                ['name_ar' => 'الإدارة', 'name_en' => 'Administration'],
            ),
            'sales' => CostCenter::query()->updateOrCreate(
                ['company_id' => $company->id, 'code' => 'SAL'],
                ['name_ar' => 'المبيعات', 'name_en' => 'Sales'],
            ),
            'warehouse' => CostCenter::query()->updateOrCreate(
                ['company_id' => $company->id, 'code' => 'WH'],
                ['name_ar' => 'المستودعات', 'name_en' => 'Warehouses'],
            ),
        ];
    }

    private function seedTaxAndDiscounts(Company $company): Tax
    {
        $tax = Tax::query()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'ضريبة مبيعات 16%'],
            ['rate' => 16, 'account_group_id' => $this->accounts['2200']->id],
        );

        TradeDiscount::query()->updateOrCreate(
            ['company_id' => $company->id, 'discount_type' => 'خصم تجاري 5%'],
            ['rate' => 5, 'account_group_id' => $this->accounts['5300']->id],
        );

        return $tax;
    }

    private function seedInventorySetup(Company $company): Warehouse
    {
        MeasurementUnit::query()->updateOrCreate(
            ['company_id' => $company->id, 'name_ar' => 'قطعة'],
            ['name_en' => 'Piece'],
        );

        MeasurementUnit::query()->updateOrCreate(
            ['company_id' => $company->id, 'name_ar' => 'كرتونة'],
            ['name_en' => 'Carton'],
        );

        return Warehouse::query()->updateOrCreate(
            ['company_id' => $company->id, 'name_ar' => 'المستودع الرئيسي'],
            ['name_en' => 'Main Warehouse'],
        );
    }

    /**
     * @param array<string, CostCenter> $costCenters
     * @return array<string, ServiceProduct>
     */
    private function seedServicesAndProducts(Company $company, array $costCenters, Tax $tax): array
    {
        return [
            'service' => ServiceProduct::query()->updateOrCreate(
                ['company_id' => $company->id, 'code' => 'SRV-001'],
                [
                    'kind' => 'service',
                    'name_ar' => 'خدمة استشارية',
                    'name_en' => 'Consulting Service',
                    'sale_price' => 250,
                    'stock_quantity' => 0,
                    'unit_cost' => 0,
                    'account_group_id' => $this->accounts['4100']->id,
                    'cost_center_id' => $costCenters['sales']->id,
                    'tax_id' => $tax->id,
                ],
            ),
            'product' => ServiceProduct::query()->updateOrCreate(
                ['company_id' => $company->id, 'code' => 'PRD-001'],
                [
                    'kind' => 'product',
                    'name_ar' => 'منتج تجريبي',
                    'name_en' => 'Demo Product',
                    'sale_price' => 75,
                    'stock_quantity' => 100,
                    'unit_cost' => 45,
                    'account_group_id' => $this->accounts['1300']->id,
                    'cost_center_id' => $costCenters['warehouse']->id,
                    'tax_id' => $tax->id,
                ],
            ),
        ];
    }

    private function seedCustomers(Company $company): Customer
    {
        return Customer::query()->updateOrCreate(
            ['company_id' => $company->id, 'email' => 'customer@complete-demo.test'],
            [
                'name_ar' => 'عميل تجريبي رئيسي',
                'name_en' => 'Main Demo Customer',
                'address_ar' => 'عمان - شارع العملاء',
                'address_en' => 'Amman - Customers Street',
                'phone' => '+962790000101',
                'fax' => '+96265000101',
                'sales_tax_number' => 'CUST-VAT-001',
                'payment_method' => 'net_30_from_invoice',
                'credit_limit' => 10000,
                'opening_balance' => 500,
                'balance' => 500,
                'account_group_id' => $this->accounts['1200']->id,
            ],
        );
    }

    private function seedSuppliers(Company $company): Supplier
    {
        return Supplier::query()->updateOrCreate(
            ['company_id' => $company->id, 'email' => 'supplier@complete-demo.test'],
            [
                'name_ar' => 'مورد تجريبي رئيسي',
                'name_en' => 'Main Demo Supplier',
                'address_ar' => 'عمان - شارع الموردين',
                'address_en' => 'Amman - Suppliers Street',
                'phone' => '+962790000202',
                'fax' => '+96265000202',
                'sales_tax_number' => 'SUP-VAT-001',
                'payment_method' => 'net_30_from_invoice',
                'credit_limit' => 8000,
                'opening_balance' => 300,
                'balance' => 300,
                'account_group_id' => $this->accounts['2100']->id,
            ],
        );
    }

    /**
     * @param array<string, ServiceProduct> $products
     */
    private function seedSalesOrder(
        Company $company,
        Customer $customer,
        Currency $currency,
        BankAccount $bankAccount,
        InvoiceText $invoiceText,
        User $user,
        array $products,
    ): SalesOrder {
        $serviceTotal = 2 * 250;
        $productTotal = 5 * 75;
        $total = $serviceTotal + $productTotal;

        $order = SalesOrder::query()->updateOrCreate(
            ['company_id' => $company->id, 'order_number' => 1],
            [
                'customer_id' => $customer->id,
                'currency_id' => $currency->id,
                'order_date' => Carbon::now()->subDays(12),
                'due_date' => Carbon::now()->addDays(18)->toDateString(),
                'total_value' => $total,
                'line_kind' => 'services',
                'bank_account_id' => $bankAccount->id,
                'invoice_text_id' => $invoiceText->id,
                'user_id' => $user->id,
                'notes' => 'أمر بيع تجريبي يغطي خدمة ومنتج.',
            ],
        );

        $this->line(SalesOrderLine::class, ['sales_order_id' => $order->id, 'sort_order' => 1], [
            'service_product_id' => $products['service']->id,
            'description' => 'خدمة استشارية شهرية',
            'quantity' => 2,
            'unit_price' => 250,
            'line_total' => $serviceTotal,
        ]);

        $this->line(SalesOrderLine::class, ['sales_order_id' => $order->id, 'sort_order' => 2], [
            'service_product_id' => $products['product']->id,
            'description' => 'بيع منتج تجريبي',
            'quantity' => 5,
            'unit_price' => 75,
            'line_total' => $productTotal,
        ]);

        return $order;
    }

    /**
     * @param array<string, ServiceProduct> $products
     */
    private function seedCustomerInvoice(
        Company $company,
        Customer $customer,
        Currency $currency,
        BankAccount $bankAccount,
        InvoiceText $invoiceText,
        User $user,
        SalesOrder $salesOrder,
        array $products,
    ): Invoice {
        $subtotal = 875.00;
        $discount = 43.75;
        $afterDiscount = 831.25;
        $tax = round($afterDiscount * 0.16, 2);
        $grandTotal = round($afterDiscount + $tax, 2);

        $invoice = Invoice::query()->updateOrCreate(
            ['company_id' => $company->id, 'invoice_number' => 1],
            [
                'customer_id' => $customer->id,
                'currency_id' => $currency->id,
                'sales_order_id' => $salesOrder->id,
                'goods_issue_reference' => 'GOV-1',
                'invoice_date' => Carbon::now()->subDays(10),
                'due_date' => Carbon::now()->addDays(20)->toDateString(),
                'line_kind' => 'services',
                'subtotal_before_discount' => $subtotal,
                'discount_amount' => $discount,
                'total_after_discount' => $afterDiscount,
                'tax_amount' => $tax,
                'grand_total' => $grandTotal,
                'total_in_words' => 'تسعمائة وأربعة وستون دينار وخمسة وعشرون قرشاً',
                'bank_account_id' => $bankAccount->id,
                'invoice_text_id' => $invoiceText->id,
                'user_id' => $user->id,
                'notes' => 'فاتورة مبيعات تجريبية.',
            ],
        );

        $this->line(InvoiceLine::class, ['invoice_id' => $invoice->id, 'sort_order' => 1], [
            'service_product_id' => $products['service']->id,
            'description' => 'خدمة استشارية شهرية',
            'quantity' => 2,
            'unit_price' => 250,
            'line_total' => 500,
        ]);

        $this->line(InvoiceLine::class, ['invoice_id' => $invoice->id, 'sort_order' => 2], [
            'service_product_id' => $products['product']->id,
            'description' => 'بيع منتج تجريبي',
            'quantity' => 5,
            'unit_price' => 75,
            'line_total' => 375,
        ]);

        app(CustomerInvoiceLedgerSync::class)->sync($invoice->fresh());

        return $invoice;
    }

    /**
     * @param array<string, ServiceProduct> $products
     */
    private function seedPurchaseOrder(
        Company $company,
        Supplier $supplier,
        Currency $currency,
        BankAccount $bankAccount,
        InvoiceText $invoiceText,
        User $user,
        array $products,
    ): PurchaseOrder {
        $order = PurchaseOrder::query()->updateOrCreate(
            ['company_id' => $company->id, 'order_number' => 1],
            [
                'supplier_id' => $supplier->id,
                'currency_id' => $currency->id,
                'supplier_invoice_number' => 'SUP-PO-001',
                'order_date' => Carbon::now()->subDays(20),
                'due_date' => Carbon::now()->addDays(10)->toDateString(),
                'total_value' => 900,
                'line_kind' => 'goods',
                'bank_account_id' => $bankAccount->id,
                'invoice_text_id' => $invoiceText->id,
                'user_id' => $user->id,
                'notes' => 'أمر شراء تجريبي.',
            ],
        );

        $this->line(PurchaseOrderLine::class, ['purchase_order_id' => $order->id, 'sort_order' => 1], [
            'service_product_id' => $products['product']->id,
            'description' => 'توريد منتج تجريبي',
            'quantity' => 20,
            'unit_price' => 45,
            'line_total' => 900,
        ]);

        return $order;
    }

    /**
     * @param array<string, ServiceProduct> $products
     */
    private function seedPurchaseInvoice(
        Company $company,
        Supplier $supplier,
        Currency $currency,
        BankAccount $bankAccount,
        InvoiceText $invoiceText,
        User $user,
        PurchaseOrder $purchaseOrder,
        array $products,
    ): PurchaseInvoice {
        $subtotal = 900.00;
        $discount = 0.00;
        $afterDiscount = 900.00;
        $tax = 144.00;
        $grandTotal = 1044.00;

        $invoice = PurchaseInvoice::query()->updateOrCreate(
            ['company_id' => $company->id, 'invoice_number' => 1],
            [
                'supplier_id' => $supplier->id,
                'currency_id' => $currency->id,
                'purchase_order_id' => $purchaseOrder->id,
                'entry_voucher_reference' => 'GIV-1',
                'supplier_invoice_number' => 'SUP-INV-001',
                'invoice_date' => Carbon::now()->subDays(18),
                'due_date' => Carbon::now()->addDays(12)->toDateString(),
                'line_kind' => 'goods',
                'subtotal_before_discount' => $subtotal,
                'discount_amount' => $discount,
                'total_after_discount' => $afterDiscount,
                'tax_amount' => $tax,
                'grand_total' => $grandTotal,
                'total_in_words' => 'ألف وأربعة وأربعون ديناراً',
                'bank_account_id' => $bankAccount->id,
                'invoice_text_id' => $invoiceText->id,
                'user_id' => $user->id,
                'notes' => 'فاتورة مشتريات تجريبية.',
            ],
        );

        $this->line(PurchaseInvoiceLine::class, ['purchase_invoice_id' => $invoice->id, 'sort_order' => 1], [
            'service_product_id' => $products['product']->id,
            'description' => 'توريد منتج تجريبي',
            'quantity' => 20,
            'unit_price' => 45,
            'line_total' => 900,
        ]);

        app(PurchaseInvoiceLedgerSync::class)->sync($invoice->fresh());

        return $invoice;
    }

    /**
     * @param array<string, ServiceProduct> $products
     */
    private function seedInventoryTransactions(
        Company $company,
        Customer $customer,
        Supplier $supplier,
        Currency $currency,
        PurchaseOrder $purchaseOrder,
        SalesOrder $salesOrder,
        User $user,
        array $products,
        Warehouse $warehouse,
    ): void {
        $inventoryOrder = InventoryOrder::query()->updateOrCreate(
            ['company_id' => $company->id, 'order_number' => 1],
            [
                'customer_id' => $customer->id,
                'order_date' => Carbon::now()->subDays(9),
                'total_value' => 375,
                'user_id' => $user->id,
            ],
        );

        $this->line(InventoryOrderLine::class, ['inventory_order_id' => $inventoryOrder->id, 'sort_order' => 1], [
            'service_product_id' => $products['product']->id,
            'description' => 'طلب صرف منتج تجريبي من '.$warehouse->name_ar,
            'quantity' => 5,
            'unit_price' => 75,
            'line_total' => 375,
        ]);

        $inward = GoodsInwardVoucher::query()->updateOrCreate(
            ['company_id' => $company->id, 'voucher_number' => 1],
            [
                'voucher_date' => Carbon::now()->subDays(18),
                'due_date' => Carbon::now()->addDays(12)->toDateString(),
                'supplier_id' => $supplier->id,
                'currency_id' => $currency->id,
                'purchase_order_id' => $purchaseOrder->id,
                'subtotal_before_tax' => 900,
                'tax_total' => 144,
                'grand_total' => 1044,
                'total_in_words' => 'ألف وأربعة وأربعون ديناراً',
                'user_id' => $user->id,
            ],
        );

        $this->line(GoodsInwardVoucherLine::class, ['goods_inward_voucher_id' => $inward->id, 'sort_order' => 1], [
            'service_product_id' => $products['product']->id,
            'description' => 'إدخال منتج تجريبي إلى '.$warehouse->name_ar,
            'unit_label' => 'قطعة',
            'quantity' => 20,
            'unit_price' => 45,
            'amount_before_tax' => 900,
            'tax_rate' => 16,
            'tax_amount' => 144,
            'line_total' => 1044,
        ]);

        GoodsOutwardVoucher::query()->updateOrCreate(
            ['company_id' => $company->id, 'voucher_number' => 1],
            [
                'voucher_date' => Carbon::now()->subDays(9),
                'customer_id' => $customer->id,
                'sales_order_id' => $salesOrder->id,
                'account_group_id' => $this->accounts['1300']->id,
                'user_id' => $user->id,
            ],
        );
    }

    private function seedVouchersAndJournal(
        Company $company,
        Customer $customer,
        Supplier $supplier,
        Currency $currency,
        User $user,
        Invoice $invoice,
        PurchaseInvoice $purchaseInvoice,
    ): void {
        $receipt = ReceiptVoucher::query()->updateOrCreate(
            ['company_id' => $company->id, 'receipt_number' => 1],
            [
                'user_id' => $user->id,
                'receipt_date' => Carbon::now()->subDays(5)->toDateString(),
                'customer_id' => $customer->id,
                'payment_method' => 'bank',
                'payment_kind' => 'invoice',
                'account_group_id' => $this->accounts['1120']->id,
                'total_amount' => 400,
                'description' => 'قبض جزئي من العميل التجريبي.',
            ],
        );

        $this->line(ReceiptVoucherLine::class, ['receipt_voucher_id' => $receipt->id, 'sort_order' => 1], [
            'invoice_id' => $invoice->id,
            'amount' => 400,
        ]);
        app(ReceiptVoucherLedgerSync::class)->sync($receipt->fresh());

        $payment = PaymentVoucher::query()->updateOrCreate(
            ['company_id' => $company->id, 'payment_number' => 1],
            [
                'user_id' => $user->id,
                'payment_date' => Carbon::now()->subDays(4)->toDateString(),
                'supplier_id' => $supplier->id,
                'payment_method' => 'bank',
                'payment_kind' => 'invoice',
                'account_group_id' => $this->accounts['1120']->id,
                'total_amount' => 300,
                'description' => 'دفع جزئي للمورد التجريبي.',
            ],
        );

        $this->line(PaymentVoucherLine::class, ['payment_voucher_id' => $payment->id, 'sort_order' => 1], [
            'purchase_invoice_id' => $purchaseInvoice->id,
            'amount' => 300,
        ]);
        app(PaymentVoucherLedgerSync::class)->sync($payment->fresh());

        $entry = JournalEntry::query()->updateOrCreate(
            ['company_id' => $company->id, 'entry_number' => 1],
            [
                'user_id' => $user->id,
                'entry_date' => Carbon::now()->subDays(3)->toDateString(),
                'currency_id' => $currency->id,
                'title' => 'قيد افتتاحي تجريبي',
                'notes' => 'قيد متوازن يوضح استخدام الحسابات الطرفية.',
            ],
        );

        JournalEntryLine::query()->where('journal_entry_id', $entry->id)->delete();
        JournalEntryLine::query()->create([
            'journal_entry_id' => $entry->id,
            'account_group_id' => $this->accounts['1110']->id,
            'description' => 'رصيد افتتاحي للصندوق',
            'debit' => 5000,
            'credit' => 0,
            'sort_order' => 1,
        ]);
        JournalEntryLine::query()->create([
            'journal_entry_id' => $entry->id,
            'account_group_id' => $this->accounts['3100']->id,
            'description' => 'رأس مال افتتاحي',
            'debit' => 0,
            'credit' => 5000,
            'sort_order' => 2,
        ]);

        BankDeposit::query()->updateOrCreate(
            ['company_id' => $company->id, 'deposit_number' => 1],
            [
                'user_id' => $user->id,
                'deposit_date' => Carbon::now()->subDays(2)->toDateString(),
                'from_account_group_id' => $this->accounts['1110']->id,
                'to_account_group_id' => $this->accounts['1120']->id,
                'currency_id' => $currency->id,
                'amount' => 1000,
                'description' => 'إيداع من الصندوق إلى البنك.',
            ],
        );
    }

    private function seedAssets(Company $company, Supplier $supplier, User $user): void
    {
        $vehicles = AssetCategory::query()->updateOrCreate(
            ['company_id' => $company->id, 'name_ar' => 'مركبات'],
            ['annual_depreciation_rate' => 20],
        );

        AssetCategory::query()->updateOrCreate(
            ['company_id' => $company->id, 'name_ar' => 'أجهزة ومعدات'],
            ['annual_depreciation_rate' => 15],
        );

        FixedAsset::query()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'سيارة مبيعات تجريبية'],
            [
                'asset_category_id' => $vehicles->id,
                'historical_cost' => 18000,
                'purchase_date' => Carbon::now()->subMonths(10)->toDateString(),
                'useful_life_years' => 5,
                'annual_depreciation_rate' => 20,
                'usage_start_date' => Carbon::now()->subMonths(9)->toDateString(),
                'depreciation_start_date' => Carbon::now()->subMonths(9)->toDateString(),
                'supplier_id' => $supplier->id,
                'user_id' => $user->id,
            ],
        );
    }

    /**
     * @param array<string, CostCenter> $costCenters
     */
    private function seedPayroll(Company $company, array $costCenters): void
    {
        PayrollAllowance::query()->updateOrCreate(
            ['company_id' => $company->id, 'allowance_type' => 'بدل هاتف'],
            ['amount' => 25, 'frequency' => 'monthly', 'start_date' => Carbon::now()->startOfYear()->toDateString()],
        );

        PayrollAllowance::query()->updateOrCreate(
            ['company_id' => $company->id, 'allowance_type' => 'مكافأة ربع سنوية'],
            ['amount' => 150, 'frequency' => 'quarterly', 'start_date' => Carbon::now()->startOfYear()->toDateString()],
        );

        PayrollDeduction::query()->updateOrCreate(
            ['company_id' => $company->id, 'deduction_type' => 'اقتطاع سلفة'],
            ['amount' => 40, 'frequency' => 'monthly', 'start_date' => Carbon::now()->startOfYear()->toDateString()],
        );

        Employee::query()->updateOrCreate(
            ['company_id' => $company->id, 'job_number' => 'EMP-DEMO-001'],
            [
                'name_ar' => 'موظف مبيعات تجريبي',
                'name_en' => 'Demo Sales Employee',
                'national_id' => '9900000001',
                'social_security_number' => 'SS-DEMO-001',
                'hiring_date' => Carbon::now()->subYear()->toDateString(),
                'termination_date' => null,
                'basic_salary' => 650,
                'social_security_rate' => 7.5,
                'company_social_security_rate' => 14.25,
                'commission_rate' => 2,
                'marital_status' => 'single',
                'phone_allowance' => true,
                'deduction_type' => 'اقتطاع سلفة',
                'cost_center_id' => $costCenters['sales']->id,
            ],
        );

        Employee::query()->updateOrCreate(
            ['company_id' => $company->id, 'job_number' => 'EMP-DEMO-002'],
            [
                'name_ar' => 'محاسب تجريبي',
                'name_en' => 'Demo Accountant',
                'national_id' => '9900000002',
                'social_security_number' => 'SS-DEMO-002',
                'hiring_date' => Carbon::now()->subMonths(8)->toDateString(),
                'termination_date' => null,
                'basic_salary' => 800,
                'social_security_rate' => 7.5,
                'company_social_security_rate' => 14.25,
                'commission_rate' => 0,
                'marital_status' => 'married',
                'phone_allowance' => false,
                'deduction_type' => null,
                'cost_center_id' => $costCenters['admin']->id,
            ],
        );

        $run = PayrollRun::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'period_month' => Carbon::now()->startOfMonth()->toDateString(),
            ],
            [
                'status' => PayrollRun::STATUS_DRAFT,
                'finalized_at' => null,
            ],
        );

        app(PayrollRunBuilder::class)->rebuild($run);
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $model
     * @param array<string, mixed> $keys
     * @param array<string, mixed> $values
     */
    private function line(string $model, array $keys, array $values): void
    {
        $model::query()->updateOrCreate($keys, $values);
    }
}
