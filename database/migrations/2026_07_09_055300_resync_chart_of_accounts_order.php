<?php

use App\Services\Accounting\ChartOfAccountsService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(ChartOfAccountsService::class)->syncDefaultChartForAllCompanies();
    }

    public function down(): void
    {
        // لا يمكن التراجع بأمان عن إعادة ترتيب الشجرة المحاسبية.
    }
};
