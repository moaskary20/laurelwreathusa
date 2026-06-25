<?php

use App\Support\CompanyDocumentCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('company_documents', 'category')) {
            Schema::table('company_documents', function (Blueprint $table) {
                $table->string('category')
                    ->default(CompanyDocumentCategory::SALES)
                    ->after('company_id');
                $table->index(['company_id', 'category']);
            });
        }

        DB::table('company_documents')
            ->whereNull('category')
            ->orWhere('category', '')
            ->update(['category' => CompanyDocumentCategory::SALES]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('company_documents', 'category')) {
            Schema::table('company_documents', function (Blueprint $table) {
                $table->dropIndex(['company_id', 'category']);
                $table->dropColumn('category');
            });
        }
    }
};
