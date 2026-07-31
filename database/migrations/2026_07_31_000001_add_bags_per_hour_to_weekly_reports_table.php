<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weekly_reports', function (Blueprint $table) {
            $table->decimal('bags_per_hour', 10, 2)->default(135)->after('production_hours')
                ->comment('Divisor for production hours: difference ÷ bags_per_hour');
        });
    }

    public function down(): void
    {
        Schema::table('weekly_reports', function (Blueprint $table) {
            $table->dropColumn('bags_per_hour');
        });
    }
};
