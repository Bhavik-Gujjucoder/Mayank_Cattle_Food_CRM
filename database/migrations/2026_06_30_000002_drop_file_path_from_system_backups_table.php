<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('system_backups', 'file_path')) {
            return;
        }

        Schema::table('system_backups', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });
    }

    public function down(): void
    {
        Schema::table('system_backups', function (Blueprint $table) {
            $table->string('file_path')->after('filename');
        });
    }
};
