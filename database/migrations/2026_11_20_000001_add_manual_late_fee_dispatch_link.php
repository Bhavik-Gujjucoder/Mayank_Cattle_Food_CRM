<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weekly_report_items', function (Blueprint $table) {
            $table->decimal('accrued_late_fee', 12, 2)->default(0)->after('less_freight_per_qty');
            $table->foreignId('late_fee_dispatch_id')
                ->nullable()
                ->after('accrued_late_fee')
                ->constrained('dispatch_management')
                ->nullOnDelete();
        });

        Schema::table('dispatch_management', function (Blueprint $table) {
            $table->foreignId('late_fee_dispatch_id')
                ->nullable()
                ->after('late_fee_source')
                ->constrained('dispatch_management')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_management', function (Blueprint $table) {
            $table->dropConstrainedForeignId('late_fee_dispatch_id');
        });

        Schema::table('weekly_report_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('late_fee_dispatch_id');
            $table->dropColumn('accrued_late_fee');
        });
    }
};
