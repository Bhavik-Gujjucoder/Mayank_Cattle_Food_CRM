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
        Schema::create('dealer_management', function (Blueprint $table) {
            $table->id();
            $table->string('profile_image')->nullable();
            $table->unsignedBigInteger('broker_id');
            $table->foreign('broker_id')->references('id')->on('users')->onDelete('set null');
            $table->string('code_no')->unique();
            $table->string('applicant_name');
            $table->string('firm_shop_name');
            $table->text('firm_shop_address');
            $table->string('mobile_no', 10);
            $table->string('pancard', 10);
            $table->string('gstin', 15)->nullable();
            $table->string('aadhar_card', 12)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dealer_management');
    }
};
