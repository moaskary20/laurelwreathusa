<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoices', 'paper_invoice_path')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('paper_invoice_path')->nullable()->after('notes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'paper_invoice_path')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('paper_invoice_path');
            });
        }
    }
};
