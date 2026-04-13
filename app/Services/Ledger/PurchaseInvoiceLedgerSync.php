<?php

namespace App\Services\Ledger;

use App\Models\PurchaseInvoice;
use App\Models\SupplierLedgerEntry;

final class PurchaseInvoiceLedgerSync
{
    public const DOCUMENT_TYPE = 'purchase_invoice';

    public function sync(PurchaseInvoice $invoice): void
    {
        SupplierLedgerEntry::query()
            ->where('company_id', $invoice->company_id)
            ->where('purchase_invoice_id', $invoice->id)
            ->delete();

        SupplierLedgerEntry::query()->create([
            'company_id' => $invoice->company_id,
            'supplier_id' => $invoice->supplier_id,
            'purchase_invoice_id' => $invoice->id,
            'entry_date' => $invoice->invoice_date->toDateString(),
            'document_type' => self::DOCUMENT_TYPE,
            'document_number' => (string) $invoice->invoice_number,
            'description' => 'فاتورة مشتريات',
            'debit' => 0,
            'credit' => (float) $invoice->grand_total,
        ]);
    }
}
