<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('period_month');
            $table->string('status', 20)->default('draft');
            $table->timestamp('finalized_at')->nullable();
            $table->unsignedInteger('employees_count')->default(0);
            $table->decimal('gross_total', 15, 2)->default(0);
            $table->decimal('allowances_total', 15, 2)->default(0);
            $table->decimal('deductions_total', 15, 2)->default(0);
            $table->decimal('employee_ss_total', 15, 2)->default(0);
            $table->decimal('company_ss_total', 15, 2)->default(0);
            $table->decimal('net_total', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'period_month']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('payroll_run_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('allowances_total', 15, 2)->default(0);
            $table->decimal('deductions_total', 15, 2)->default(0);
            $table->decimal('employee_social_security', 15, 2)->default(0);
            $table->decimal('company_social_security', 15, 2)->default(0);
            $table->decimal('social_security_total', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id']);
            $table->index(['payroll_run_id', 'cost_center_id']);
        });

        Schema::create('payroll_run_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_line_id')->constrained('payroll_run_lines')->cascadeOnDelete();
            $table->string('type', 20);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('label');
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['payroll_run_line_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_run_line_items');
        Schema::dropIfExists('payroll_run_lines');
        Schema::dropIfExists('payroll_runs');
    }
};
