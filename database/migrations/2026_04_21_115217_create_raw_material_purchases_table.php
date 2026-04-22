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
        Schema::create('raw_material_purchases', function (Blueprint $table) {
            $table->id();

            $table->string('purchase_unique_id')->unique()->comment('unique id for the purchase');
            
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('raw_material_id');
            //$table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            //$table->foreign('raw_material_id')->references('id')->on('raw_materials')->onDelete('cascade');
            
            $table->string('invoice_no')->nullable();
            $table->timestamp('invoice_date')->nullable();
            
            $table->decimal('quantity', 15, 2)->comment('kg');
            $table->decimal('unit_price', 15, 2)->comment('price per kg');
            $table->decimal('total_price', 15, 2)->comment('quantity * unit_price');
            
            $table->tinyInteger('status')->default('0')->comment('0-pending, 1-received, 2-cancelled');
            
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('due_amount', 15, 2)->default(0);

            $table->text('remarks')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index(['supplier_id', 'raw_material_id', 'invoice_no', 'invoice_date', 'status'],'idx_raw_material_purchase_index');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_material_purchases');
    }
};
