<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            return;
        }

        foreach (DB::table('users')->whereNull('username')->cursor() as $row) {
            DB::table('users')->where('id', $row->id)->update([
                'username' => 'user_'.$row->id,
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};
