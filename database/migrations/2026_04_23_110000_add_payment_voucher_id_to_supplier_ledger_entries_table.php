<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_ledger_entries', function (Blueprint $table) {
            $table->foreignId('payment_voucher_id')
                ->nullable()
                ->constrained('payment_vouchers')
                ->cascadeOnDelete();
            $table->index(['company_id', 'payment_voucher_id']);
        });
    }

    public function down(): void
    {
        Schema::table('supplier_ledger_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_voucher_id');
        });
    }
};
