<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_management', function (Blueprint $table) {
            $table->unsignedBigInteger('transport_id')->nullable()->change();
            $table->string('truck_number', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_management', function (Blueprint $table) {
            $table->unsignedBigInteger('transport_id')->nullable(false)->change();
            $table->string('truck_number', 100)->nullable(false)->change();
        });
    }
};
