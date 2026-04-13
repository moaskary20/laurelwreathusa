<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('deposit_number');
            $table->date('deposit_date');
            $table->foreignId('from_account_group_id')->constrained('account_groups')->restrictOnDelete();
            $table->foreignId('to_account_group_id')->constrained('account_groups')->restrictOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'deposit_number']);
            $table->index(['company_id', 'deposit_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_deposits');
    }
};
