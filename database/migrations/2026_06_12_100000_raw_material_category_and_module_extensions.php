<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_material_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_unique_id', 20)->unique();
            $table->string('name')->index();
            $table->tinyInteger('status')->default(1)->comment('1=active, 0=inactive');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('raw_materials', function (Blueprint $table) {
            $table->foreignId('raw_material_category_id')
                ->nullable()
                ->after('raw_material_unique_id')
                ->constrained('raw_material_categories')
                ->nullOnDelete();
        });

        Schema::table('raw_material_orders', function (Blueprint $table) {
            $table->foreignId('supplier_broker_id')
                ->nullable()
                ->after('supplier_id')
                ->constrained('supplier_brokers')
                ->nullOnDelete();
            $table->string('price_basis', 50)->nullable()->after('order_date');
        });

        Schema::table('raw_material_order_items', function (Blueprint $table) {
            $table->decimal('other_expense', 15, 3)->default(0)->after('total_price');
        });
    }

    public function down(): void
    {
        Schema::table('raw_material_order_items', function (Blueprint $table) {
            $table->dropColumn('other_expense');
        });

        Schema::table('raw_material_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_broker_id');
            $table->dropColumn('price_basis');
        });

        Schema::table('raw_materials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('raw_material_category_id');
        });

        Schema::dropIfExists('raw_material_categories');
    }
};
