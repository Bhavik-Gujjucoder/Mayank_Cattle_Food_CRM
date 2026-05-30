<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_material_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_unique_id', 30)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->date('order_date');
            $table->unsignedInteger('total_qty')->default(0);
            $table->decimal('total_price', 15, 3)->default(0);
            $table->decimal('total_freight', 15, 3)->default(0);
            $table->tinyInteger('status')->default(0)->comment('0=pending,1=partial,2=received,3=cancelled');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['supplier_id', 'order_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_material_orders');
    }
};
