<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_broker_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('supplier_broker_id')->constrained('supplier_brokers')->cascadeOnDelete();
            $table->unique(['supplier_id', 'supplier_broker_id']);
        });

        if (Schema::hasColumn('suppliers', 'supplier_broker_id')) {
            DB::table('suppliers')
                ->whereNotNull('supplier_broker_id')
                ->orderBy('id')
                ->select(['id', 'supplier_broker_id'])
                ->chunkById(200, function ($rows) {
                    $inserts = [];
                    foreach ($rows as $row) {
                        $inserts[] = [
                            'supplier_id' => $row->id,
                            'supplier_broker_id' => $row->supplier_broker_id,
                        ];
                    }
                    if ($inserts !== []) {
                        DB::table('supplier_broker_supplier')->insert($inserts);
                    }
                });

            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropForeign(['supplier_broker_id']);
                $table->dropIndex(['supplier_broker_id', 'status']);
                $table->dropColumn('supplier_broker_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('supplier_broker_id')->nullable()->after('id')
                ->constrained('supplier_brokers')->nullOnDelete();
            $table->index(['supplier_broker_id', 'status']);
        });

        $links = DB::table('supplier_broker_supplier')
            ->select('supplier_id', DB::raw('MIN(supplier_broker_id) as supplier_broker_id'))
            ->groupBy('supplier_id')
            ->get();

        foreach ($links as $link) {
            DB::table('suppliers')
                ->where('id', $link->supplier_id)
                ->update(['supplier_broker_id' => $link->supplier_broker_id]);
        }

        Schema::dropIfExists('supplier_broker_supplier');
    }
};
