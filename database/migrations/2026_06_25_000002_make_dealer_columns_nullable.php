<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealer_management', function (Blueprint $table) {
            if (Schema::hasColumn('dealer_management', 'applicant_name')) {
                $table->string('applicant_name')->nullable()->change();
            }

            if (Schema::hasColumn('dealer_management', 'mobile_no')) {
                $table->string('mobile_no', 10)->nullable()->change();
            }

            if (Schema::hasColumn('dealer_management', 'pancard')) {
                $table->string('pancard', 10)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('dealer_management', function (Blueprint $table) {
            if (Schema::hasColumn('dealer_management', 'applicant_name')) {
                $table->string('applicant_name')->nullable(false)->change();
            }

            if (Schema::hasColumn('dealer_management', 'mobile_no')) {
                $table->string('mobile_no', 10)->nullable(false)->change();
            }

            if (Schema::hasColumn('dealer_management', 'pancard')) {
                $table->string('pancard', 10)->nullable(false)->change();
            }
        });
    }
};
