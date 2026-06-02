<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_material_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->cascadeOnDelete();
            $table->foreignId('raw_material_order_id')->constrained('raw_material_orders')->cascadeOnDelete();
            $table->unsignedInteger('total_qty')->default(0);
            $table->unsignedInteger('pending_qty')->default(0);
            $table->unsignedInteger('received_qty')->default(0);
            $table->decimal('price', 15, 3)->default(0);
            $table->decimal('price_avg', 15, 3)->default(0);
            $table->decimal('total_price', 15, 3)->default(0);
            $table->decimal('pending_price', 15, 3)->default(0);
            $table->decimal('received_price', 15, 3)->default(0);
            $table->decimal('total_freight', 15, 3)->default(0)->comment('Sum of receive.freight * receive.qty per status=1');
            $table->tinyInteger('status')->default(0)->comment('0=pending,1=partial,2=received,3=cancelled');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['raw_material_id', 'raw_material_order_id', 'status'], 'rmoi_material_order_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_material_order_items');
    }
};
