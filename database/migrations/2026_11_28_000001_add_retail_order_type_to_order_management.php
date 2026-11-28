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
            $table->string('order_type', 20)->default('soda')->after('unique_order_id');
            $table->string('party_name')->nullable()->after('dealer_id');
            $table->text('note')->nullable()->after('delivery_address');
        });

        Schema::table('order_management', function (Blueprint $table) {
            $table->unsignedBigInteger('broker_id')->nullable()->change();
            $table->unsignedBigInteger('dealer_id')->nullable()->change();
            $table->text('delivery_address')->nullable()->change();
        });

        DB::table('order_management')->whereNull('order_type')->update(['order_type' => 'soda']);
        DB::table('order_management')->where('order_type', '')->update(['order_type' => 'soda']);

        $now = now();
        $creditAmount = DB::table('general_settings')->where('key', 'credit_due_amount')->value('value');
        foreach ([
            'retail_credit_due_days' => '2',
            'retail_credit_due_amount' => $creditAmount !== null && $creditAmount !== '' ? (string) $creditAmount : '0',
        ] as $key => $value) {
            DB::table('general_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down(): void
    {
        DB::table('general_settings')->whereIn('key', [
            'retail_credit_due_days',
            'retail_credit_due_amount',
        ])->delete();

        Schema::table('order_management', function (Blueprint $table) {
            $table->unsignedBigInteger('broker_id')->nullable(false)->change();
            $table->unsignedBigInteger('dealer_id')->nullable(false)->change();
            $table->text('delivery_address')->nullable(false)->change();
            $table->dropColumn(['order_type', 'party_name', 'note']);
        });
    }
};
