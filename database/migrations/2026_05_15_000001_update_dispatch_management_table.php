<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_management', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')       ->after('id');
            $table->unsignedBigInteger('order_item_id')  ->after('order_id');
            $table->unsignedBigInteger('product_id')     ->after('order_item_id');
            $table->unsignedInteger('no_of_bags')        ->after('product_id');
            $table->date('dispatch_date')                ->after('no_of_bags');
            $table->string('transport', 255)             ->after('dispatch_date');
            $table->string('truck_number', 100)          ->after('transport');
            $table->string('driver_contact', 20)         ->after('truck_number');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_management', function (Blueprint $table) {
            $table->dropColumn([
                'order_id', 'order_item_id', 'product_id', 'no_of_bags',
                'dispatch_date', 'transport', 'truck_number', 'driver_contact',
                'deleted_at',
            ]);
        });
    }
};
