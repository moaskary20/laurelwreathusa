<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('legal_name_en')->nullable()->after('trade_name');
            $table->text('address_en')->nullable()->after('address');
            $table->string('commercial_registry_issuer')->nullable()->after('registration_number');
            $table->json('branches')->nullable()->after('po_box');
            $table->json('partners')->nullable()->after('branches');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'legal_name_en',
                'address_en',
                'commercial_registry_issuer',
                'branches',
                'partners',
            ]);
        });
    }
};
