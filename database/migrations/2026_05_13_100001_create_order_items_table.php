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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->comment('FK → order_management');
            $table->unsignedBigInteger('product_id')->comment('FK → products');
            $table->unsignedInteger('qty')->comment('Quantity ordered');
            $table->decimal('unit_price', 20, 2)->comment('Unit price at time of order');
            $table->decimal('total_price', 20, 2)->comment('qty × unit_price');
            $table->timestamps();
            // $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
