<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_ledger_entries', function (Blueprint $table) {
            $table->foreignId('debit_note_id')
                ->nullable()
                ->constrained('debit_notes')
                ->cascadeOnDelete();
            $table->index(['company_id', 'debit_note_id']);
        });

        Schema::table('supplier_ledger_entries', function (Blueprint $table) {
            $table->foreignId('debit_note_id')
                ->nullable()
                ->constrained('debit_notes')
                ->cascadeOnDelete();
            $table->index(['company_id', 'debit_note_id']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_ledger_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('debit_note_id');
        });

        Schema::table('supplier_ledger_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('debit_note_id');
        });
    }
};
