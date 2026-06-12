<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('supplier_broker_id')->nullable()->after('id')
                ->constrained('supplier_brokers')->nullOnDelete();
            $table->index(['supplier_broker_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropForeign(['supplier_broker_id']);
            $table->dropIndex(['supplier_broker_id', 'status']);
            $table->dropColumn('supplier_broker_id');
        });
    }
};
