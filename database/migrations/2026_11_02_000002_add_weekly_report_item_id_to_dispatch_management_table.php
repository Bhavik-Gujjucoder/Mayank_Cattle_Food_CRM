<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_management', function (Blueprint $table) {
            $table->foreignId('weekly_report_item_id')
                ->nullable()
                ->after('order_item_id')
                ->constrained('weekly_report_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_management', function (Blueprint $table) {
            $table->dropConstrainedForeignId('weekly_report_item_id');
        });
    }
};
