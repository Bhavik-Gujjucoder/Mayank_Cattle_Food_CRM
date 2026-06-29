<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->index('order_id', 'oi_order_id_idx');
            $table->index('product_id', 'oi_product_id_idx');
        });

        Schema::table('dispatch_management', function (Blueprint $table) {
            $table->index('order_id', 'disp_order_id_idx');
            $table->index('order_item_id', 'disp_order_item_id_idx');
            $table->index('product_id', 'disp_product_id_idx');
            $table->index(['status', 'dispatch_date'], 'disp_status_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('oi_order_id_idx');
            $table->dropIndex('oi_product_id_idx');
        });

        Schema::table('dispatch_management', function (Blueprint $table) {
            $table->dropIndex('disp_order_id_idx');
            $table->dropIndex('disp_order_item_id_idx');
            $table->dropIndex('disp_product_id_idx');
            $table->dropIndex('disp_status_date_idx');
        });
    }
};
