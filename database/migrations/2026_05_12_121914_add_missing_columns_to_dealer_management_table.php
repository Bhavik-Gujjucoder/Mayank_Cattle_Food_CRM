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
        Schema::table('dealer_management', function (Blueprint $table) {
            if (! Schema::hasColumn('dealer_management', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            }

            if (! Schema::hasColumn('dealer_management', 'state_id')) {
                $table->unsignedBigInteger('state_id')->nullable()->after('aadhar_card');
            }

            if (! Schema::hasColumn('dealer_management', 'city_id')) {
                $table->unsignedBigInteger('city_id')->nullable()->after('state_id');
            }

            if (! Schema::hasColumn('dealer_management', 'postal_code')) {
                $table->string('postal_code', 10)->nullable()->after('city_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dealer_management', function (Blueprint $table) {
            if (Schema::hasColumn('dealer_management', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            foreach (['state_id', 'city_id', 'postal_code'] as $col) {
                if (Schema::hasColumn('dealer_management', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
