<?php

namespace App\Services\Ledger;

use App\Models\PaymentVoucher;
use App\Models\SupplierLedgerEntry;

final class PaymentVoucherLedgerSync
{
    public const DOCUMENT_TYPE = 'payment_voucher';

    public function sync(PaymentVoucher $voucher): void
    {
        SupplierLedgerEntry::query()
            ->where('company_id', $voucher->company_id)
            ->where('payment_voucher_id', $voucher->id)
            ->delete();

        SupplierLedgerEntry::query()->create([
            'company_id' => $voucher->company_id,
            'supplier_id' => $voucher->supplier_id,
            'payment_voucher_id' => $voucher->id,
            'entry_date' => $voucher->payment_date->toDateString(),
            'document_type' => self::DOCUMENT_TYPE,
            'document_number' => (string) $voucher->payment_number,
            'description' => 'سند صرف',
            'debit' => (float) $voucher->total_amount,
            'credit' => 0,
        ]);
    }
}
