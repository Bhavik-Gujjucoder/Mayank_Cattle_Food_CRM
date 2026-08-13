<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raw_material_order_items', function (Blueprint $table) {
            $table->decimal('tax_percent', 8, 3)->default(0)->after('price');
            $table->decimal('tds_amount', 15, 3)->default(0)->after('other_expense');
        });

        $items = DB::table('raw_material_order_items')->whereNull('deleted_at')->get();

        foreach ($items as $item) {
            $material = round((int) $item->total_qty * 1000 * (float) $item->price, 3);
            $tax      = round($material * ((float) $item->tax_percent / 100), 3);
            $other    = round((float) $item->other_expense, 3);
            $tds      = round((float) $item->tds_amount, 3);
            $total    = max(0, round($material + $tax + $other - $tds, 3));

            DB::table('raw_material_order_items')->where('id', $item->id)->update([
                'total_price' => $total,
            ]);
        }

        $orders = DB::table('raw_material_orders')
            ->whereNull('deleted_at')
            ->where('status', '!=', 3)
            ->get();

        foreach ($orders as $order) {
            $orderItems = DB::table('raw_material_order_items')
                ->where('raw_material_order_id', $order->id)
                ->whereNull('deleted_at')
                ->get();

            $totalPrice = 0;
            foreach ($orderItems as $item) {
                $extraMaterial = round((int) $item->extra_qty * 1000 * (float) $item->price, 3);
                $extraTax      = round($extraMaterial * ((float) $item->tax_percent / 100), 3);
                $totalPrice += (float) $item->total_price + $extraMaterial + $extraTax;
            }

            DB::table('raw_material_orders')->where('id', $order->id)->update([
                'total_price' => $totalPrice,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('raw_material_order_items', function (Blueprint $table) {
            $table->dropColumn(['tax_percent', 'tds_amount']);
        });
    }
};
