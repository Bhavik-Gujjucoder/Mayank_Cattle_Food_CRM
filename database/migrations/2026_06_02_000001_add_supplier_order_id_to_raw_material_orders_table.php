<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raw_material_orders', function (Blueprint $table) {
            $table->string('supplier_order_id', 100)->nullable()->after('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::table('raw_material_orders', function (Blueprint $table) {
            $table->dropColumn('supplier_order_id');
        });
    }
};
