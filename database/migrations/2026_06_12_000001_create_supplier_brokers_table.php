<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_brokers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('mobile', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->foreignId('state_id')->nullable()
                ->constrained('state_management')->nullOnDelete();
            $table->foreignId('city_id')->nullable()
                ->constrained('city_management')->nullOnDelete();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['state_id', 'city_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_brokers');
    }
};
