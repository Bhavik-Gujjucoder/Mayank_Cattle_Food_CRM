<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raw_material_order_items', function (Blueprint $table) {
            $table->unsignedInteger('extra_qty')->default(0)->after('received_qty');
        });
    }

    public function down(): void
    {
        Schema::table('raw_material_order_items', function (Blueprint $table) {
            $table->dropColumn('extra_qty');
        });
    }
};
