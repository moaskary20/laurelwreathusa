<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_ledger_entries', function (Blueprint $table) {
            $table->foreignId('invoice_id')
                ->nullable()
                ->constrained('invoices')
                ->cascadeOnDelete();
            $table->index(['company_id', 'invoice_id']);
        });

        Schema::table('supplier_ledger_entries', function (Blueprint $table) {
            $table->foreignId('purchase_invoice_id')
                ->nullable()
                ->constrained('purchase_invoices')
                ->cascadeOnDelete();
            $table->index(['company_id', 'purchase_invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_ledger_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoice_id');
        });

        Schema::table('supplier_ledger_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_invoice_id');
        });
    }
};
