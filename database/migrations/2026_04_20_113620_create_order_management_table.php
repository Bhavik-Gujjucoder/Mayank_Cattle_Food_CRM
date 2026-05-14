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
        Schema::create('order_management', function (Blueprint $table) {
            $table->id();
            $table->string('unique_order_id')->unique()->comment('Auto-generated: ORD/YYYY-YY/NNNN');
            $table->unsignedBigInteger('broker_id')->comment('FK → users (broker role)');
            $table->unsignedBigInteger('brand_id')->comment('FK → brand_management');
            $table->unsignedBigInteger('dealer_id')->comment('FK → dealer_management');
            $table->date('order_date');
            $table->text('delivery_address');
            $table->enum('payment_status', ['unpaid', 'paid', 'partial'])
                  ->default('unpaid')
                  ->comment('unpaid | paid | partial');
            $table->decimal('partial_paid_amount', 20, 2)
                  ->nullable()
                  ->comment('Filled only when payment_status = partial');
            $table->decimal('total_order_amount', 20, 2)->default(0.00)->comment('Sum of all item totals');
            $table->decimal('grand_total', 20, 2)->default(0.00)->comment('Grand total after adjustments');
            $table->tinyInteger('status')->default(1)->comment('1 = active, 0 = inactive');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_management');
    }
};
