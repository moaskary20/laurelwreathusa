<?php

namespace App\Services\Ledger;

use App\Models\CustomerLedgerEntry;
use App\Models\ReceiptVoucher;

final class ReceiptVoucherLedgerSync
{
    public const DOCUMENT_TYPE = 'receipt_voucher';

    public function sync(ReceiptVoucher $voucher): void
    {
        CustomerLedgerEntry::query()
            ->where('company_id', $voucher->company_id)
            ->where('receipt_voucher_id', $voucher->id)
            ->delete();

        CustomerLedgerEntry::query()->create([
            'company_id' => $voucher->company_id,
            'customer_id' => $voucher->customer_id,
            'receipt_voucher_id' => $voucher->id,
            'entry_date' => $voucher->receipt_date->toDateString(),
            'document_type' => self::DOCUMENT_TYPE,
            'document_number' => (string) $voucher->receipt_number,
            'description' => 'سند قبض',
            'debit' => 0,
            'credit' => (float) $voucher->total_amount,
        ]);
    }
}
