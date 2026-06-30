<?php

namespace Database\Seeders;

use App\Models\BrandManagement;
use App\Models\DealerManagement;
use App\Models\DispatchLateFeeLog;
use App\Models\DispatchManagement;
use App\Models\OrderItem;
use App\Models\OrderManagement;
use App\Models\Product;
use App\Models\User;
use App\Services\PaymentReceivableService;
use Database\Seeders\Concerns\GeneratesBulkIds;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Seeds sales orders, line items, and dispatches with mixed payment / dispatch states.
 *
 * Requires DemoFoundationSeeder first.
 * Run: php artisan db:seed --class=SalesBulkSeeder
 */
class SalesBulkSeeder extends Seeder
{
    use GeneratesBulkIds;

    /** @var list<array{id: int, broker_id: int, brand_id: int}> */
    private array $dealers = [];

    /** @var list<int> */
    private array $transporterIds = [];

    /** @var array<int, list<int>> */
    private array $productIdsByBrand = [];

    public function run(): void
    {
        Mail::fake();

        $this->loadFoundationData();

        if ($this->dealers === [] || $this->transporterIds === []) {
            $this->command?->error('Run DemoFoundationSeeder first (missing demo dealers/transporters).');

            return;
        }

        $target = $this->bulkSeedTarget();
        $existing = OrderManagement::where('delivery_address', 'like', 'Demo delivery address #%')->count();

        if ($existing >= $target && ! $this->bulkSeedForce()) {
            $this->command?->warn("Already have {$existing} sales orders (target {$target}). Set BULK_SEED_FORCE=true to add more.");

            return;
        }

        $toCreate = $this->bulkSeedForce() ? $target : max(0, $target - $existing);
        $ordersPerDealer = (int) max(1, ceil($toCreate / count($this->dealers)));
        $startSeq = $existing + 1;

        DB::connection()->disableQueryLog();

        $this->command?->info(sprintf(
            'Creating %d sales orders (~%d per dealer, %d dealers)…',
            $toCreate,
            $ordersPerDealer,
            count($this->dealers)
        ));

        $created = 0;
        $stats = [
            'no_dispatch'     => 0,
            'partial_dispatch'=> 0,
            'full_dispatch'   => 0,
            'dispatch_unpaid' => 0,
            'dispatch_paid'   => 0,
            'dispatch_partial'=> 0,
        ];

        $seq = $startSeq;

        foreach ($this->dealers as $dealer) {
            if ($created >= $toCreate) {
                break;
            }

            $priorFullyDispatched = true;

            for ($n = 0; $n < $ordersPerDealer && $created < $toCreate; $n++) {
                $dispatchScenario = $this->resolveDispatchScenario($priorFullyDispatched, $n === 0);
                $stats[$dispatchScenario === 'blocked' ? 'no_dispatch' : $dispatchScenario]++;

                $orderDate = $this->randomPastDate(10, 450);
                $order = $this->createSalesOrder($dealer, $seq, $orderDate);
                $items = $this->createOrderItems($order, (int) $dealer['brand_id']);

                $fullyDispatched = $dispatchScenario === 'full_dispatch';

                if ($dispatchScenario !== 'blocked' && $dispatchScenario !== 'no_dispatch') {
                    $fullyDispatched = $this->seedDispatches($order, $items, $dispatchScenario, $stats);
                } elseif ($dispatchScenario === 'no_dispatch') {
                    $fullyDispatched = false;
                } else {
                    $fullyDispatched = false;
                }

                $order->syncPaymentStatusFromDispatches();
                $priorFullyDispatched = $fullyDispatched;

                $seq++;
                $created++;

                if ($created % 500 === 0) {
                    $this->command?->info("  … {$created} / {$toCreate} sales orders");
                }
            }
        }

        $this->command?->info('Sales bulk seed complete: ' . json_encode($stats));
    }

    private function loadFoundationData(): void
    {
        $marker = DemoFoundationSeeder::MARKER;

        $this->dealers = DealerManagement::query()
            ->where('code_no', 'like', 'DEMO-D%')
            ->get(['id', 'broker_id', 'brand_id'])
            ->map(fn ($d) => ['id' => (int) $d->id, 'broker_id' => (int) $d->broker_id, 'brand_id' => (int) $d->brand_id])
            ->all();

        $this->transporterIds = User::role('transporter')
            ->where('email', 'like', 'demo-transporter-%@bulk.local')
            ->pluck('id')
            ->all();

        $brandIds = BrandManagement::where('name', 'like', $marker . ' Brand %')->pluck('id');

        foreach ($brandIds as $brandId) {
            $this->productIdsByBrand[(int) $brandId] = Product::where('brand_id', $brandId)
                ->pluck('id')
                ->all();
        }
    }

    private function resolveDispatchScenario(bool $priorFullyDispatched, bool $isFirstForDealer): string
    {
        if (! $priorFullyDispatched) {
            return 'blocked';
        }

        $roll = fake()->numberBetween(1, 100);

        if ($isFirstForDealer) {
            return match (true) {
                $roll <= 40 => 'partial_dispatch',
                default     => 'full_dispatch',
            };
        }

        return match (true) {
            $roll <= 30 => 'no_dispatch',
            $roll <= 55 => 'partial_dispatch',
            default     => 'full_dispatch',
        };
    }

    /**
     * @param  array{id: int, broker_id: int, brand_id: int}  $dealer
     */
    private function createSalesOrder(array $dealer, int $seq, \Carbon\Carbon $orderDate): OrderManagement
    {
        return OrderManagement::create([
            'unique_order_id'    => $this->nextSalesOrderId($seq, $orderDate),
            'broker_id'          => $dealer['broker_id'],
            'brand_id'           => $dealer['brand_id'],
            'dealer_id'          => $dealer['id'],
            'order_date'         => $orderDate->toDateString(),
            'delivery_address'   => 'Demo delivery address #' . $seq,
            'payment_status'     => 'unpaid',
            'total_order_amount' => 0,
            'grand_total'        => 0,
            'status'             => 1,
        ]);
    }

    /**
     * @return list<OrderItem>
     */
    private function createOrderItems(OrderManagement $order, int $brandId): array
    {
        $productIds = $this->productIdsByBrand[$brandId] ?? [];
        $itemCount = fake()->numberBetween(1, 2);
        $items = [];
        $grandTotal = 0.0;

        for ($j = 0; $j < $itemCount; $j++) {
            $productId = $productIds[($order->id + $j) % max(1, count($productIds))] ?? $productIds[0] ?? null;

            if ($productId === null) {
                continue;
            }

            $qty = fake()->numberBetween(100, 800);
            $unitPrice = fake()->randomFloat(2, 900, 2200);
            $totalPrice = round($qty * $unitPrice, 2);
            $grandTotal += $totalPrice;

            $items[] = OrderItem::create([
                'order_id'    => $order->id,
                'product_id'  => $productId,
                'qty'         => $qty,
                'unit_price'  => $unitPrice,
                'total_price' => $totalPrice,
            ]);
        }

        $order->update([
            'total_order_amount' => $grandTotal,
            'grand_total'        => $grandTotal,
        ]);

        return $items;
    }

    /**
     * @param  list<OrderItem>  $items
     * @param  array<string, int>  $stats
     */
    private function seedDispatches(
        OrderManagement $order,
        array $items,
        string $dispatchScenario,
        array &$stats
    ): bool {
        $allComplete = true;

        DispatchManagement::withoutEvents(function () use ($order, $items, $dispatchScenario, &$stats, &$allComplete) {
            foreach ($items as $item) {
                $targetQty = match ($dispatchScenario) {
                    'full_dispatch'    => (int) $item->qty,
                    'partial_dispatch' => (int) max(1, floor($item->qty * fake()->randomFloat(2, 0.25, 0.75))),
                    default            => 0,
                };

                if ($targetQty <= 0) {
                    $allComplete = false;
                    continue;
                }

                $dispatched = 0;

                while ($dispatched < $targetQty) {
                    $remaining = $targetQty - $dispatched;
                    $bags = (int) min($remaining, fake()->numberBetween(20, min(200, $remaining)));

                    $dispatchDate = $this->randomPastDate(15, 120);
                    $baseAmount = round($bags * (float) $item->unit_price, 2);
                    $payment = $this->pickDispatchPaymentStatus($baseAmount);
                    $stats['dispatch_' . $payment['key']]++;

                    $dispatch = DispatchManagement::create([
                        'order_id'              => $order->id,
                        'order_item_id'         => $item->id,
                        'product_id'            => $item->product_id,
                        'no_of_bags'            => $bags,
                        'dispatch_date'         => $dispatchDate->toDateString(),
                        'transport_id'          => $this->transporterIds[fake()->numberBetween(0, count($this->transporterIds) - 1)],
                        'truck_number'          => 'GJ' . fake()->numerify('##') . fake()->regexify('[A-Z]{2}') . fake()->numerify('####'),
                        'driver_contact'        => fake()->numerify('98########'),
                        'status'                => $payment['status'],
                        'partial_paid_amount'   => $payment['partial'],
                        'accrued_late_fee'      => 0,
                    ]);

                    if ($payment['status'] !== DispatchManagement::STATUS_PAID && fake()->boolean(35)) {
                        $this->applyLateFee($dispatch, $dispatchDate, $bags);
                    }

                    $dispatched += $bags;
                }

                if ($dispatched < (int) $item->qty) {
                    $allComplete = false;
                }
            }
        });

        return $allComplete;
    }

    /** @return array{key: string, status: int, partial: ?float} */
    private function pickDispatchPaymentStatus(float $baseAmount): array
    {
        $roll = fake()->numberBetween(1, 100);

        if ($roll <= 45) {
            return ['key' => 'unpaid', 'status' => DispatchManagement::STATUS_UNPAID, 'partial' => null];
        }

        if ($roll <= 80) {
            return ['key' => 'paid', 'status' => DispatchManagement::STATUS_PAID, 'partial' => null];
        }

        return [
            'key'     => 'partial',
            'status'  => DispatchManagement::STATUS_PARTIAL,
            'partial' => round($baseAmount * fake()->randomFloat(2, 0.15, 0.75), 2),
        ];
    }

    private function applyLateFee(DispatchManagement $dispatch, \Carbon\Carbon $dispatchDate, int $bags): void
    {
        $service = app(PaymentReceivableService::class);

        if (! $service->isLateFeeEnabled()) {
            return;
        }

        $daysOverdue = fake()->numberBetween(5, 45);
        $dailyRate = $service->paymentDueAmountRate();
        $dailyAmount = round($dailyRate * $bags, 2);
        $accrued = round($dailyAmount * $daysOverdue, 2);

        $dispatch->update([
            'accrued_late_fee'         => $accrued,
            'late_fee_last_accrued_on' => now()->subDays(fake()->numberBetween(1, 5))->toDateString(),
        ]);

        $chargeStart = $dispatchDate->copy()->addDays($service->paymentDueDays() + 1);

        for ($d = 0; $d < min($daysOverdue, 10); $d++) {
            DispatchLateFeeLog::create([
                'dispatch_management_id' => $dispatch->id,
                'charge_date'            => $chargeStart->copy()->addDays($d)->toDateString(),
                'daily_amount'           => $dailyAmount,
                'rate_per_unit'          => $dailyRate,
                'quantity'               => $bags,
            ]);
        }
    }
}
