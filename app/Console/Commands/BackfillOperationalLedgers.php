<?php

namespace App\Console\Commands;

use App\Models\CustomerLedgerEntry;
use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use App\Models\SupplierLedgerEntry;
use App\Services\Ledger\CustomerInvoiceLedgerSync;
use App\Services\Ledger\PurchaseInvoiceLedgerSync;
use Illuminate\Console\Command;

class BackfillOperationalLedgers extends Command
{
    protected $signature = 'ledger:backfill-operational';

    protected $description = 'Create missing customer and supplier ledger rows from existing invoices (does not adjust stock).';

    public function handle(CustomerInvoiceLedgerSync $customerSync, PurchaseInvoiceLedgerSync $purchaseSync): int
    {
        $customerCreated = 0;
        Invoice::query()->orderBy('id')->chunkById(100, function ($invoices) use ($customerSync, &$customerCreated): void {
            foreach ($invoices as $invoice) {
                if (CustomerLedgerEntry::query()->where('invoice_id', $invoice->id)->exists()) {
                    continue;
                }
                $customerSync->sync($invoice);
                $customerCreated++;
            }
        });

        $supplierCreated = 0;
        PurchaseInvoice::query()->orderBy('id')->chunkById(100, function ($invoices) use ($purchaseSync, &$supplierCreated): void {
            foreach ($invoices as $invoice) {
                if (SupplierLedgerEntry::query()->where('purchase_invoice_id', $invoice->id)->exists()) {
                    continue;
                }
                $purchaseSync->sync($invoice);
                $supplierCreated++;
            }
        });

        $this->info("Customer ledger rows created: {$customerCreated}");
        $this->info("Supplier ledger rows created: {$supplierCreated}");

        return self::SUCCESS;
    }
}
