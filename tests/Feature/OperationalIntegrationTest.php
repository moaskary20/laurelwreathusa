<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceLine;
use App\Models\ServiceProduct;
use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use App\Services\Inventory\InvoiceProductStockSync;
use App\Services\Ledger\CustomerInvoiceLedgerSync;
use App\Services\Ledger\PurchaseInvoiceLedgerSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OperationalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_invoice_ledger_sync_creates_entry(): void
    {
        $this->seed();

        $company = Company::query()->firstOrFail();
        $customer = Customer::query()->create([
            'company_id' => $company->id,
            'name_ar' => 'عميل',
            'name_en' => 'Customer',
        ]);

        $invoice = Invoice::query()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'currency_id' => null,
            'invoice_number' => 1,
            'invoice_date' => now(),
            'due_date' => now()->addWeek(),
            'line_kind' => 'services',
            'subtotal_before_discount' => 100,
            'discount_amount' => 0,
            'total_after_discount' => 100,
            'tax_amount' => 0,
            'grand_total' => 115.5,
        ]);

        app(CustomerInvoiceLedgerSync::class)->sync($invoice);

        $this->assertDatabaseHas('customer_ledger_entries', [
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'debit' => 115.5,
            'credit' => 0,
        ]);
    }

    public function test_customer_invoice_ledger_resync_replaces_single_row(): void
    {
        $this->seed();

        $company = Company::query()->firstOrFail();
        $customer = Customer::query()->create([
            'company_id' => $company->id,
            'name_ar' => 'عميل',
            'name_en' => 'Customer',
        ]);

        $invoice = Invoice::query()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'currency_id' => null,
            'invoice_number' => 1,
            'invoice_date' => now(),
            'due_date' => now()->addWeek(),
            'line_kind' => 'services',
            'subtotal_before_discount' => 100,
            'discount_amount' => 0,
            'total_after_discount' => 100,
            'tax_amount' => 0,
            'grand_total' => 100,
        ]);

        $sync = app(CustomerInvoiceLedgerSync::class);
        $sync->sync($invoice);

        $invoice->update(['grand_total' => 80]);
        $sync->sync($invoice);

        $this->assertSame(1, CustomerLedgerEntry::query()->where('invoice_id', $invoice->id)->count());
        $this->assertDatabaseHas('customer_ledger_entries', [
            'invoice_id' => $invoice->id,
            'debit' => 80,
        ]);
    }

    public function test_purchase_invoice_supplier_ledger_sync(): void
    {
        $this->seed();

        $company = Company::query()->firstOrFail();
        $supplier = Supplier::query()->create([
            'company_id' => $company->id,
            'name_ar' => 'مورد',
            'name_en' => 'Supplier',
        ]);

        $invoice = PurchaseInvoice::query()->create([
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'currency_id' => null,
            'invoice_number' => 1,
            'invoice_date' => now(),
            'due_date' => now()->addWeek(),
            'line_kind' => 'services',
            'subtotal_before_discount' => 200,
            'discount_amount' => 0,
            'total_after_discount' => 200,
            'tax_amount' => 0,
            'grand_total' => 230,
        ]);

        app(PurchaseInvoiceLedgerSync::class)->sync($invoice);

        $this->assertDatabaseHas('supplier_ledger_entries', [
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'purchase_invoice_id' => $invoice->id,
            'debit' => 0,
            'credit' => 230,
        ]);
    }

    public function test_customer_sale_reduces_product_stock(): void
    {
        $this->seed();

        $company = Company::query()->firstOrFail();
        $customer = Customer::query()->create([
            'company_id' => $company->id,
            'name_ar' => 'عميل',
            'name_en' => 'Customer',
        ]);

        $product = ServiceProduct::query()->create([
            'company_id' => $company->id,
            'kind' => 'product',
            'name_ar' => 'صنف',
            'name_en' => 'Item',
            'code' => 'P1',
            'sale_price' => 10,
            'stock_quantity' => 100,
            'unit_cost' => 5,
        ]);

        $invoice = Invoice::query()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'currency_id' => null,
            'invoice_number' => 1,
            'invoice_date' => now(),
            'due_date' => now()->addWeek(),
            'line_kind' => 'services',
            'subtotal_before_discount' => 30,
            'discount_amount' => 0,
            'total_after_discount' => 30,
            'tax_amount' => 0,
            'grand_total' => 30,
        ]);

        InvoiceLine::query()->create([
            'invoice_id' => $invoice->id,
            'service_product_id' => $product->id,
            'description' => null,
            'quantity' => 25,
            'unit_price' => 1.2,
            'line_total' => 30,
            'sort_order' => 0,
        ]);

        DB::transaction(function () use ($invoice, $product): void {
            $invoice->refresh();
            $invoice->load('lines.serviceProduct');
            app(InvoiceProductStockSync::class)->syncCustomerInvoice($invoice, collect());
        });

        $product->refresh();
        $this->assertEquals(75.0, (float) $product->stock_quantity);
    }

    public function test_customer_sale_insufficient_stock_throws(): void
    {
        $this->seed();

        $company = Company::query()->firstOrFail();
        $customer = Customer::query()->create([
            'company_id' => $company->id,
            'name_ar' => 'عميل',
            'name_en' => 'Customer',
        ]);

        $product = ServiceProduct::query()->create([
            'company_id' => $company->id,
            'kind' => 'product',
            'name_ar' => 'صنف',
            'name_en' => 'Item',
            'code' => 'P1',
            'sale_price' => 10,
            'stock_quantity' => 5,
            'unit_cost' => 5,
        ]);

        $invoice = Invoice::query()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'currency_id' => null,
            'invoice_number' => 1,
            'invoice_date' => now(),
            'due_date' => now()->addWeek(),
            'line_kind' => 'services',
            'subtotal_before_discount' => 100,
            'discount_amount' => 0,
            'total_after_discount' => 100,
            'tax_amount' => 0,
            'grand_total' => 100,
        ]);

        InvoiceLine::query()->create([
            'invoice_id' => $invoice->id,
            'service_product_id' => $product->id,
            'description' => null,
            'quantity' => 20,
            'unit_price' => 5,
            'line_total' => 100,
            'sort_order' => 0,
        ]);

        $this->expectException(ValidationException::class);

        DB::transaction(function () use ($invoice): void {
            $invoice->refresh();
            $invoice->load('lines.serviceProduct');
            app(InvoiceProductStockSync::class)->syncCustomerInvoice($invoice, collect());
        });
    }

    public function test_backfill_command_creates_missing_ledgers(): void
    {
        $this->seed();

        $company = Company::query()->firstOrFail();
        $customer = Customer::query()->create([
            'company_id' => $company->id,
            'name_ar' => 'عميل',
            'name_en' => 'Customer',
        ]);

        $invoice = Invoice::query()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'currency_id' => null,
            'invoice_number' => 1,
            'invoice_date' => now(),
            'due_date' => now()->addWeek(),
            'line_kind' => 'services',
            'subtotal_before_discount' => 50,
            'discount_amount' => 0,
            'total_after_discount' => 50,
            'tax_amount' => 0,
            'grand_total' => 50,
        ]);

        $this->assertSame(0, CustomerLedgerEntry::query()->where('invoice_id', $invoice->id)->count());

        Artisan::call('ledger:backfill-operational');

        $this->assertDatabaseHas('customer_ledger_entries', [
            'invoice_id' => $invoice->id,
            'debit' => 50,
        ]);
    }

    public function test_purchase_increases_stock_and_average_cost(): void
    {
        $this->seed();

        $company = Company::query()->firstOrFail();
        $company->update(['inventory_pricing' => 'average']);

        $supplier = Supplier::query()->create([
            'company_id' => $company->id,
            'name_ar' => 'مورد',
            'name_en' => 'Supplier',
        ]);

        $product = ServiceProduct::query()->create([
            'company_id' => $company->id,
            'kind' => 'product',
            'name_ar' => 'صنف',
            'name_en' => 'Item',
            'code' => 'P1',
            'sale_price' => 10,
            'stock_quantity' => 10,
            'unit_cost' => 4,
        ]);

        $invoice = PurchaseInvoice::query()->create([
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'currency_id' => null,
            'invoice_number' => 1,
            'invoice_date' => now(),
            'due_date' => now()->addWeek(),
            'line_kind' => 'services',
            'subtotal_before_discount' => 100,
            'discount_amount' => 0,
            'total_after_discount' => 100,
            'tax_amount' => 0,
            'grand_total' => 100,
        ]);

        PurchaseInvoiceLine::query()->create([
            'purchase_invoice_id' => $invoice->id,
            'service_product_id' => $product->id,
            'description' => null,
            'quantity' => 10,
            'unit_price' => 10,
            'line_total' => 100,
            'sort_order' => 0,
        ]);

        DB::transaction(function () use ($invoice, $company): void {
            $invoice->refresh();
            $invoice->load('lines.serviceProduct');
            app(InvoiceProductStockSync::class)->syncPurchaseInvoice($invoice, collect(), $company->fresh());
        });

        $product->refresh();
        $this->assertEquals(20.0, (float) $product->stock_quantity);
        $this->assertEquals(7.0, (float) $product->unit_cost);
    }
}
