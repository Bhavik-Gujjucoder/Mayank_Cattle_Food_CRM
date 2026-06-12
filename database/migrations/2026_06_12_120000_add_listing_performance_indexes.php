<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_management', function (Blueprint $table) {
            $table->index('broker_id', 'om_broker_id_idx');
            $table->index('brand_id', 'om_brand_id_idx');
            $table->index('dealer_id', 'om_dealer_id_idx');
            $table->index('order_date', 'om_order_date_idx');
            $table->index(['order_date', 'brand_id', 'broker_id'], 'om_date_brand_broker_idx');
        });

        Schema::table('dealer_management', function (Blueprint $table) {
            $table->index('broker_id', 'dm_broker_id_idx');
            $table->index('brand_id', 'dm_brand_id_idx');
            $table->index('created_at', 'dm_created_at_idx');
        });

        if (Schema::hasColumn('dealer_management', 'city_id')) {
            Schema::table('dealer_management', function (Blueprint $table) {
                $table->index('city_id', 'dm_city_id_idx');
            });
        }

        Schema::table('products', function (Blueprint $table) {
            $table->index(['brand_id', 'status'], 'products_brand_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('order_management', function (Blueprint $table) {
            $table->dropIndex('om_broker_id_idx');
            $table->dropIndex('om_brand_id_idx');
            $table->dropIndex('om_dealer_id_idx');
            $table->dropIndex('om_order_date_idx');
            $table->dropIndex('om_date_brand_broker_idx');
        });

        Schema::table('dealer_management', function (Blueprint $table) {
            $table->dropIndex('dm_broker_id_idx');
            $table->dropIndex('dm_brand_id_idx');
            $table->dropIndex('dm_created_at_idx');
            if (Schema::hasColumn('dealer_management', 'city_id')) {
                $table->dropIndex('dm_city_id_idx');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_brand_status_idx');
        });
    }
};
