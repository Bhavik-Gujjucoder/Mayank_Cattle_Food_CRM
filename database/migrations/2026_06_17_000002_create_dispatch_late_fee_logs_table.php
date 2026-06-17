<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_late_fee_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dispatch_management_id');
            $table->date('charge_date');
            $table->decimal('daily_amount', 12, 2);
            $table->decimal('rate_per_unit', 12, 2);
            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->foreign('dispatch_management_id')
                ->references('id')
                ->on('dispatch_management')
                ->cascadeOnDelete();

            $table->unique(['dispatch_management_id', 'charge_date'], 'dispatch_late_fee_logs_dispatch_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_late_fee_logs');
    }
};
