<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raw_material_orders', function (Blueprint $table) {
            $table->decimal('total_qty', 12, 3)->default(0)->change();
        });

        Schema::table('raw_material_order_items', function (Blueprint $table) {
            $table->decimal('total_qty', 12, 3)->default(0)->change();
            $table->decimal('pending_qty', 12, 3)->default(0)->change();
            $table->decimal('received_qty', 12, 3)->default(0)->change();
            $table->decimal('extra_qty', 12, 3)->default(0)->change();
        });

        Schema::table('raw_material_receives', function (Blueprint $table) {
            $table->decimal('qty', 12, 3)->default(0)->change();
        });

        Schema::table('raw_materials', function (Blueprint $table) {
            $table->decimal('total_stock', 12, 3)->default(0)->change();
            $table->decimal('available_stock', 12, 3)->default(0)->change();
            $table->decimal('used_stock', 12, 3)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('raw_material_orders', function (Blueprint $table) {
            $table->unsignedInteger('total_qty')->default(0)->change();
        });

        Schema::table('raw_material_order_items', function (Blueprint $table) {
            $table->unsignedInteger('total_qty')->default(0)->change();
            $table->unsignedInteger('pending_qty')->default(0)->change();
            $table->unsignedInteger('received_qty')->default(0)->change();
            $table->unsignedInteger('extra_qty')->default(0)->change();
        });

        Schema::table('raw_material_receives', function (Blueprint $table) {
            $table->unsignedInteger('qty')->default(0)->change();
        });

        Schema::table('raw_materials', function (Blueprint $table) {
            $table->decimal('total_stock', 12, 2)->default(0)->change();
            $table->decimal('available_stock', 12, 2)->default(0)->change();
            $table->decimal('used_stock', 12, 2)->default(0)->change();
        });
    }
};
