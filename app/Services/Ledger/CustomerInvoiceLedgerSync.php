<?php

namespace App\Services\Ledger;

use App\Models\CustomerLedgerEntry;
use App\Models\Invoice;

final class CustomerInvoiceLedgerSync
{
    public const DOCUMENT_TYPE = 'customer_invoice';

    public function sync(Invoice $invoice): void
    {
        CustomerLedgerEntry::query()
            ->where('company_id', $invoice->company_id)
            ->where('invoice_id', $invoice->id)
            ->delete();

        CustomerLedgerEntry::query()->create([
            'company_id' => $invoice->company_id,
            'customer_id' => $invoice->customer_id,
            'invoice_id' => $invoice->id,
            'entry_date' => $invoice->invoice_date->toDateString(),
            'document_type' => self::DOCUMENT_TYPE,
            'document_number' => (string) $invoice->invoice_number,
            'description' => 'فاتورة مبيعات',
            'debit' => (float) $invoice->grand_total,
            'credit' => 0,
        ]);
    }
}
