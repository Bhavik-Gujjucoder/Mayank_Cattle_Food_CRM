<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealer_management', function (Blueprint $table) {
            $table->string('applicant_name')->nullable()->change();
            $table->string('mobile_no', 10)->nullable()->change();
            $table->string('pancard', 10)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('dealer_management', function (Blueprint $table) {
            $table->string('applicant_name')->nullable(false)->change();
            $table->string('mobile_no', 10)->nullable(false)->change();
            $table->string('pancard', 10)->nullable(false)->change();
        });
    }
};
