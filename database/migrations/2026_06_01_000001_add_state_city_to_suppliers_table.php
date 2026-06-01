<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('state_id')->nullable()->after('address')
                ->constrained('state_management')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->after('state_id')
                ->constrained('city_management')->nullOnDelete();
            $table->index(['state_id', 'city_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropForeign(['state_id']);
            $table->dropForeign(['city_id']);
            $table->dropIndex(['state_id', 'city_id', 'status']);
            $table->dropColumn(['state_id', 'city_id']);
        });
    }
};
