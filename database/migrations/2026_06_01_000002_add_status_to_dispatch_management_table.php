<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_management', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(0)->after('driver_contact');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_management', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
