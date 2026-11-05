<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_management', function (Blueprint $table) {
            $table->decimal('less_freight_per_qty', 12, 2)->nullable()->after('no_of_bags');
        });

        Schema::table('weekly_report_items', function (Blueprint $table) {
            $table->decimal('less_freight_per_qty', 12, 2)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_management', function (Blueprint $table) {
            $table->dropColumn('less_freight_per_qty');
        });

        Schema::table('weekly_report_items', function (Blueprint $table) {
            $table->dropColumn('less_freight_per_qty');
        });
    }
};
