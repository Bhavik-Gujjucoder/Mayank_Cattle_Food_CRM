<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raw_material_orders', function (Blueprint $table) {
            $table->decimal('advance_payment', 15, 2)->default(0)->after('total_freight');
        });
    }

    public function down(): void
    {
        Schema::table('raw_material_orders', function (Blueprint $table) {
            $table->dropColumn('advance_payment');
        });
    }
};
