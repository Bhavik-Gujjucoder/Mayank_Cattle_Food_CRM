<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_management', function (Blueprint $table) {
            $table->decimal('accrued_late_fee', 12, 2)->default(0)->after('partial_paid_amount');
            $table->date('late_fee_last_accrued_on')->nullable()->after('accrued_late_fee');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_management', function (Blueprint $table) {
            $table->dropColumn(['accrued_late_fee', 'late_fee_last_accrued_on']);
        });
    }
};
