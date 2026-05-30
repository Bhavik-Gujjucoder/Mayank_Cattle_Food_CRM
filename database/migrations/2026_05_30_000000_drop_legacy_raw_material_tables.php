<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Run only after approval — drops old raw material tables from the previous module.
 * Tables: raw_material_purchases, raw_materials (legacy schema without order/receive flow).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('raw_material_receives');
        Schema::dropIfExists('raw_material_order_items');
        Schema::dropIfExists('raw_material_orders');
        Schema::dropIfExists('raw_material_purchases');
        Schema::dropIfExists('raw_materials');
    }

    public function down(): void
    {
        // Legacy tables are not recreated on rollback; run fresh create migrations instead.
    }
};
