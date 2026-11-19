<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('general_settings')->updateOrInsert(
            ['key' => 'late_fee_auto'],
            ['value' => '1', 'created_at' => $now, 'updated_at' => $now]
        );
    }

    public function down(): void
    {
        DB::table('general_settings')->where('key', 'late_fee_auto')->delete();
    }
};
