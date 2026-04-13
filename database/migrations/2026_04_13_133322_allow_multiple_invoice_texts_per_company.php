<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL: the unique index backs the FK to companies; drop FK first, then unique, then re-add FK with a non-unique index.
        Schema::table('invoice_texts', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('invoice_texts', function (Blueprint $table) {
            $table->dropUnique(['company_id']);
        });

        Schema::table('invoice_texts', function (Blueprint $table) {
            $table->index('company_id');
        });

        Schema::table('invoice_texts', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_texts', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('invoice_texts', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
        });

        Schema::table('invoice_texts', function (Blueprint $table) {
            $table->unique('company_id');
        });

        Schema::table('invoice_texts', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });
    }
};
