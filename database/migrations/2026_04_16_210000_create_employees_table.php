<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('national_id', 50);
            $table->string('social_security_number', 50);
            $table->date('hiring_date');
            $table->date('termination_date')->nullable();
            $table->string('job_number', 50);
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('social_security_rate', 8, 4)->default(0);
            $table->decimal('company_social_security_rate', 8, 4)->default(0);
            $table->decimal('commission_rate', 8, 4)->default(0);
            $table->string('marital_status', 20)->default('single');
            $table->boolean('phone_allowance')->default(false);
            $table->string('deduction_type')->nullable();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'job_number']);
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
