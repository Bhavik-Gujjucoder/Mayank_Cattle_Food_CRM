<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_material_receives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->cascadeOnDelete();
            $table->foreignId('raw_material_order_id')->constrained('raw_material_orders')->cascadeOnDelete();
            $table->foreignId('raw_material_order_item_id')->constrained('raw_material_order_items')->cascadeOnDelete();
            $table->unsignedInteger('qty')->default(0);
            $table->decimal('freight', 15, 3)->default(0);
            $table->date('received_date');
            $table->tinyInteger('status')->default(0)->comment('0=on road,1=received,2=cancelled');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['raw_material_order_id', 'raw_material_order_item_id', 'status', 'received_date'], 'rmr_order_item_status_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_material_receives');
    }
};
