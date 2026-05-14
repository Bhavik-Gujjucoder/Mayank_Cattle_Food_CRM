<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();

            $table->enum('item_type', ['1', '2'])->comment('1-raw_material_purchase, 2-order');
            
            $table->tinyInteger('reference_type')->nullable()->comment('1-raw material order received, 2-order received');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('raw_material_purchases.id, orders.id');

            $table->enum('type', ['1', '2', '3'])->comment('1-IN, 2-OUT, 3-ADJUST');
            
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['item_type', 'reference_type', 'reference_id', 'type'], 'idx_stock_transactions_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transactions');
    }
};
