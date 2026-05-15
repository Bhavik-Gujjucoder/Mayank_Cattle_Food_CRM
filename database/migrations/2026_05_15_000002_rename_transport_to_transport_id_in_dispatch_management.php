<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /* Drop the old varchar column first, then add the typed FK column.
           Two separate Schema::table calls avoids driver-specific DDL issues. */
        Schema::table('dispatch_management', function (Blueprint $table) {
            $table->dropColumn('transport');
        });

        Schema::table('dispatch_management', function (Blueprint $table) {
            $table->unsignedBigInteger('transport_id')->after('no_of_bags')
                  ->comment('FK → users (transporter role)');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_management', function (Blueprint $table) {
            $table->dropColumn('transport_id');
        });

        Schema::table('dispatch_management', function (Blueprint $table) {
            $table->string('transport', 255)->after('no_of_bags');
        });
    }
};
