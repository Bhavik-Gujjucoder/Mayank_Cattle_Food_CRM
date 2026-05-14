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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Cottonseed Cake, Soybean Meal, Wheat Straw (Bhusa), etc.');
            $table->unsignedBigInteger('brand_id');
            $table->string('unit')->comment('Bag, Ton');
            $table->decimal('price', 20, 2)->comment('Price per unit');
            $table->tinyInteger('status')->default(1)->comment('1-active, 0-inactive');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
