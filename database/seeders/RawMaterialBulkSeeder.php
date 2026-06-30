<?php

namespace Database\Seeders;

use App\Models\RawMaterial;
use App\Models\RawMaterialOrder;
use App\Models\RawMaterialOrderItem;
use App\Models\RawMaterialReceive;
use App\Models\Supplier;
use App\Models\SupplierBroker;
use App\Services\RawMaterialCacheService;
use Database\Seeders\Concerns\GeneratesBulkIds;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds raw material orders, items, and receives for dashboard / listing load tests.
 *
 * Requires DemoFoundationSeeder first.
 * Run: php artisan db:seed --class=RawMaterialBulkSeeder
 */
class RawMaterialBulkSeeder extends Seeder
{
    use GeneratesBulkIds;

    /** @var list<int> */
    private array $materialIds = [];

    /** @var list<int> */
    private array $supplierIds = [];

    /** @var list<int> */
    private array $supplierBrokerIds = [];

    public function run(): void
    {
        $this->loadFoundationIds();

        if ($this->materialIds === [] || $this->supplierIds === []) {
            $this->command?->error('Run DemoFoundationSeeder first (missing demo materials/suppliers).');

            return;
        }

        $target = $this->bulkSeedTarget();
        $existing = RawMaterialOrder::where('supplier_order_id', 'like', 'SO-DEMO-%')->count();

        if ($existing >= $target && ! $this->bulkSeedForce()) {
            $this->command?->warn("Already have {$existing} RM orders (target {$target}). Set BULK_SEED_FORCE=true to add more.");

            return;
        }

        $toCreate = $this->bulkSeedForce() ? $target : max(0, $target - $existing);
        $chunk = $this->bulkSeedChunk();
        $startSeq = $existing + 1;

        DB::connection()->disableQueryLog();

        $this->command?->info("Creating {$toCreate} raw material orders (chunk {$chunk})…");

        $created = 0;
        $stats = ['pending' => 0, 'on_road' => 0, 'partial' => 0, 'received' => 0, 'cancelled' => 0];

        while ($created < $toCreate) {
            $batchSize = min($chunk, $toCreate - $created);

            for ($i = 0; $i < $batchSize; $i++) {
                $seq = $startSeq + $created;
                $scenario = $this->pickRmScenario();
                $stats[$scenario]++;
                $this->createRmOrder($seq, $scenario);
                $created++;
            }

            $this->command?->info("  … {$created} / {$toCreate} RM orders");
        }

        $this->command?->info('RM bulk seed complete: ' . json_encode($stats));
    }

    private function loadFoundationIds(): void
    {
        $marker = DemoFoundationSeeder::MARKER;

        $this->materialIds = RawMaterial::where('name', 'like', $marker . ' Material %')
            ->pluck('id')
            ->all();

        $this->supplierIds = Supplier::where('name', 'like', $marker . ' Supplier %')
            ->pluck('id')
            ->all();

        $this->supplierBrokerIds = SupplierBroker::where('name', 'like', $marker . ' Supplier Broker %')
            ->pluck('id')
            ->all();
    }

    private function pickRmScenario(): string
    {
        $roll = fake()->numberBetween(1, 100);

        return match (true) {
            $roll <= 35 => 'pending',
            $roll <= 60 => 'on_road',
            $roll <= 80 => 'partial',
            $roll <= 95 => 'received',
            default     => 'cancelled',
        };
    }

    private function createRmOrder(int $seq, string $scenario): void
    {
        $orderDate = $this->randomPastDate(5, 540);
        $supplierId = $this->supplierIds[($seq - 1) % count($this->supplierIds)];

        $order = RawMaterialOrder::create([
            'order_unique_id'    => $this->nextRmoId($seq, $orderDate),
            'supplier_id'        => $supplierId,
            'supplier_broker_id' => $this->supplierBrokerIds[($seq - 1) % count($this->supplierBrokerIds)],
            'supplier_order_id'  => 'SO-DEMO-' . str_pad((string) $seq, 6, '0', STR_PAD_LEFT),
            'order_date'         => $orderDate->toDateString(),
            'price_basis'        => fake()->randomElement(['Ex-Factory', 'FOR', 'CIF']),
        ]);

        $itemCount = fake()->numberBetween(1, 2);
        $items = [];
        $touchedMaterials = [];

        for ($j = 0; $j < $itemCount; $j++) {
            $materialId = $this->materialIds[($seq + $j) % count($this->materialIds)];
            $totalQty = fake()->numberBetween(50, 400);

            $item = new RawMaterialOrderItem([
                'raw_material_id'       => $materialId,
                'raw_material_order_id' => $order->id,
                'total_qty'             => $totalQty,
                'price'                 => fake()->randomFloat(3, 35, 95),
                'other_expense'         => fake()->randomFloat(3, 0, 5000),
            ]);
            RawMaterialCacheService::initializeOrderItem($item);
            $item->saveQuietly();
            $items[] = $item;
            $touchedMaterials[$materialId] = true;
        }

        if ($scenario === 'cancelled') {
            foreach ($items as $item) {
                $item->status = 3;
                $item->saveQuietly();
            }
            $order->status = 3;
            $order->saveQuietly();

            return;
        }

        foreach ($items as $item) {
            $this->applyReceiveScenario($order, $item, $scenario, $orderDate);
        }

        RawMaterialCacheService::recalculateOrder($order);

        foreach (array_keys($touchedMaterials) as $materialId) {
            RawMaterialCacheService::recalculateMaterialPrices((int) $materialId);
        }
    }

    private function applyReceiveScenario(
        RawMaterialOrder $order,
        RawMaterialOrderItem $item,
        string $scenario,
        \Carbon\Carbon $orderDate
    ): void {
        $totalQty = (int) $item->total_qty;

        $receivedQty = match ($scenario) {
            'pending'  => 0,
            'on_road'  => 0,
            'partial'  => (int) max(1, floor($totalQty * fake()->randomFloat(2, 0.2, 0.6))),
            'received' => $totalQty,
            default    => 0,
        };

        $onRoadQty = match ($scenario) {
            'on_road' => (int) max(1, floor($totalQty * fake()->randomFloat(2, 0.15, 0.45))),
            'partial' => (int) max(0, min(
                (int) floor($totalQty * fake()->randomFloat(2, 0.05, 0.25)),
                max(0, $totalQty - $receivedQty)
            )),
            default => 0,
        };

        if ($receivedQty > 0) {
            $this->createAppliedReceive($order, $item, $receivedQty, $orderDate, 1);
        }

        if ($onRoadQty > 0) {
            RawMaterialReceive::create([
                'raw_material_id'              => $item->raw_material_id,
                'raw_material_order_id'        => $order->id,
                'raw_material_order_item_id'   => $item->id,
                'qty'                          => $onRoadQty,
                'freight'                      => fake()->randomFloat(3, 150, 900),
                'received_date'                => $orderDate->copy()->addDays(fake()->numberBetween(1, 14))->toDateString(),
                'status'                       => 0,
            ]);
        }

        if ($receivedQty > 0 || $onRoadQty > 0) {
            $item->refresh();
            RawMaterialCacheService::syncItemStatus($item);
        }
    }

    private function createAppliedReceive(
        RawMaterialOrder $order,
        RawMaterialOrderItem $item,
        int $qty,
        \Carbon\Carbon $orderDate,
        int $status
    ): void {
        $receive = new RawMaterialReceive([
            'raw_material_id'              => $item->raw_material_id,
            'raw_material_order_id'        => $order->id,
            'raw_material_order_item_id'   => $item->id,
            'qty'                          => $qty,
            'freight'                      => fake()->randomFloat(3, 200, 850),
            'received_date'                => $orderDate->copy()->addDays(fake()->numberBetween(2, 21))->toDateString(),
            'status'                       => $status,
        ]);
        $receive->saveQuietly();

        if ($status === 1) {
            RawMaterialCacheService::applyReceive($receive);
        }
    }
}
