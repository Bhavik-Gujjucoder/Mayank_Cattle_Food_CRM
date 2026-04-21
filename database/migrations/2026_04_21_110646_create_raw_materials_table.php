<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_materials', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('unit');
            $table->decimal('total_stock', 12, 2)->default(0);
            $table->decimal('available_stock', 12, 2)->default(0);
            $table->decimal('used_stock', 12, 2)->default(0);
            $table->decimal('last_purchase_price', 12, 2)->default(0);
            $table->decimal('average_price', 12, 2)->default(0);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_materials');
    }
};
