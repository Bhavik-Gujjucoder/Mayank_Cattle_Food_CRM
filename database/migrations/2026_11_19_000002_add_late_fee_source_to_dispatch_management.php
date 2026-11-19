<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_management', function (Blueprint $table) {
            $table->string('late_fee_source', 20)
                ->nullable()
                ->after('accrued_late_fee')
                ->comment('auto | manual | mixed — how the stored late fee was added');
        });

        $loggedIds = DB::table('dispatch_late_fee_logs')
            ->distinct()
            ->pluck('dispatch_management_id');

        if ($loggedIds->isNotEmpty()) {
            DB::table('dispatch_management')
                ->where('accrued_late_fee', '>', 0)
                ->whereIn('id', $loggedIds)
                ->update(['late_fee_source' => 'auto']);
        }

        DB::table('dispatch_management')
            ->where('accrued_late_fee', '>', 0)
            ->whereNull('late_fee_source')
            ->update(['late_fee_source' => 'manual']);
    }

    public function down(): void
    {
        Schema::table('dispatch_management', function (Blueprint $table) {
            $table->dropColumn('late_fee_source');
        });
    }
};
