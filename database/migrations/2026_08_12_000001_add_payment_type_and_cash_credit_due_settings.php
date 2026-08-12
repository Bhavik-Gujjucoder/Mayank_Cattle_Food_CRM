<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_management', function (Blueprint $table) {
            $table->string('payment_type', 20)
                ->default('cash')
                ->after('payment_status')
                ->comment('cash | credit — drives late-fee grace/rate from general settings');
        });

        DB::table('order_management')->whereNull('payment_type')->update(['payment_type' => 'cash']);
        DB::table('order_management')->where('payment_type', '')->update(['payment_type' => 'cash']);

        $now = now();
        $oldDays = DB::table('general_settings')->where('key', 'payment_due_days')->value('value');
        $oldAmount = DB::table('general_settings')->where('key', 'payment_due_amount')->value('value');

        $cashDays = $oldDays !== null && $oldDays !== '' ? (string) $oldDays : '0';
        $cashAmount = $oldAmount !== null && $oldAmount !== '' ? (string) $oldAmount : '0';

        foreach ([
            'cash_due_days'      => $cashDays,
            'cash_due_amount'    => $cashAmount,
            'credit_due_days'    => $cashDays,
            'credit_due_amount'  => $cashAmount,
        ] as $key => $value) {
            DB::table('general_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => $now, 'created_at' => $now]
            );
        }

        DB::table('general_settings')->whereIn('key', [
            'payment_due_days',
            'payment_due_amount',
        ])->delete();
    }

    public function down(): void
    {
        $now = now();
        $cashDays = DB::table('general_settings')->where('key', 'cash_due_days')->value('value');
        $cashAmount = DB::table('general_settings')->where('key', 'cash_due_amount')->value('value');

        foreach ([
            'payment_due_days'   => $cashDays !== null && $cashDays !== '' ? (string) $cashDays : '0',
            'payment_due_amount' => $cashAmount !== null && $cashAmount !== '' ? (string) $cashAmount : '0',
        ] as $key => $value) {
            DB::table('general_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => $now, 'created_at' => $now]
            );
        }

        DB::table('general_settings')->whereIn('key', [
            'cash_due_days',
            'cash_due_amount',
            'credit_due_days',
            'credit_due_amount',
        ])->delete();

        Schema::table('order_management', function (Blueprint $table) {
            $table->dropColumn('payment_type');
        });
    }
};
