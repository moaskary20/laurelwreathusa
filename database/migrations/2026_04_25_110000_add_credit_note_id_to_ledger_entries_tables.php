<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_ledger_entries', function (Blueprint $table) {
            $table->foreignId('credit_note_id')
                ->nullable()
                ->constrained('credit_notes')
                ->cascadeOnDelete();
            $table->index(['company_id', 'credit_note_id']);
        });

        Schema::table('supplier_ledger_entries', function (Blueprint $table) {
            $table->foreignId('credit_note_id')
                ->nullable()
                ->constrained('credit_notes')
                ->cascadeOnDelete();
            $table->index(['company_id', 'credit_note_id']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_ledger_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('credit_note_id');
        });

        Schema::table('supplier_ledger_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('credit_note_id');
        });
    }
};
