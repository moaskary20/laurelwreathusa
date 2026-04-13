<?php

namespace App\Services\Ledger;

use App\Models\CreditNote;
use App\Models\CustomerLedgerEntry;
use App\Models\SupplierLedgerEntry;

final class CreditNoteLedgerSync
{
    public const DOCUMENT_TYPE = 'credit_note';

    public function sync(CreditNote $note): void
    {
        CustomerLedgerEntry::query()
            ->where('company_id', $note->company_id)
            ->where('credit_note_id', $note->id)
            ->delete();

        SupplierLedgerEntry::query()
            ->where('company_id', $note->company_id)
            ->where('credit_note_id', $note->id)
            ->delete();

        $desc = filled($note->description) ? $note->description : 'إشعار دائن';

        if ($note->counterparty_type === CreditNote::TYPE_CUSTOMER) {
            CustomerLedgerEntry::query()->create([
                'company_id' => $note->company_id,
                'customer_id' => (int) $note->customer_id,
                'credit_note_id' => $note->id,
                'entry_date' => $note->document_date->toDateString(),
                'document_type' => self::DOCUMENT_TYPE,
                'document_number' => (string) $note->document_number,
                'description' => $desc,
                'debit' => 0,
                'credit' => (float) $note->amount,
            ]);

            return;
        }

        SupplierLedgerEntry::query()->create([
            'company_id' => $note->company_id,
            'supplier_id' => (int) $note->supplier_id,
            'credit_note_id' => $note->id,
            'entry_date' => $note->document_date->toDateString(),
            'document_type' => self::DOCUMENT_TYPE,
            'document_number' => (string) $note->document_number,
            'description' => $desc,
            'debit' => (float) $note->amount,
            'credit' => 0,
        ]);
    }
}
