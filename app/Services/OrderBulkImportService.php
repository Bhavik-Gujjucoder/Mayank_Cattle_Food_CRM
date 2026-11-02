<?php

namespace App\Services;

use App\Models\BrandManagement;
use App\Models\DealerManagement;
use App\Models\OrderManagement;
use App\Models\Product;
use App\Models\User;
use App\Support\SalesScope;
use App\Support\SpreadsheetImport;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderBulkImportService
{
    private const MAX_ROWS = 500;

    /**
     * @return array{created: int, ids: list<string>}
     */
    public function import(UploadedFile $file): array
    {
        $rows = SpreadsheetImport::rows($file);
        if ($rows->isEmpty()) {
            $this->fail(['The file has no order rows. Put data on the first sheet under the sample headings.']);
        }
        if ($rows->count() > self::MAX_ROWS) {
            $this->fail(['The file has more than ' . self::MAX_ROWS . ' data rows.']);
        }

        $brokers = $this->indexByName(
            User::query()->brokers()->active()->get(['id', 'name'])
        );
        $brands = $this->indexByName(
            BrandManagement::query()->where('status', 1)->get(['id', 'name'])
        );
        $dealers = DealerManagement::query()->get(['id', 'broker_id', 'brand_id', 'code_no']);
        $dealersByCode = [];
        foreach ($dealers as $dealer) {
            $dealersByCode[mb_strtolower(trim((string) $dealer->code_no))] = $dealer;
        }
        $products = Product::query()->where('status', 1)->get(['id', 'name', 'brand_id']);

        $groups = [];
        $errors = [];

        foreach ($rows as $entry) {
            $rowNo = $entry['row'];
            $data = $entry['data'];
            $groupKey = SpreadsheetImport::text($data, 'order_group');
            if ($groupKey === '') {
                $errors[] = "Row {$rowNo}: Order group is required.";
                continue;
            }

            $date = SpreadsheetImport::date($data['order_date'] ?? null);
            $brokerName = SpreadsheetImport::text($data, 'broker');
            $brandName = SpreadsheetImport::text($data, 'brand');
            $dealerCode = SpreadsheetImport::text($data, 'dealer_code');
            $address = SpreadsheetImport::text($data, 'delivery_address');
            $paymentStatus = mb_strtolower(SpreadsheetImport::text($data, 'payment_status'));
            $paymentType = mb_strtolower(SpreadsheetImport::text($data, 'payment_type'));
            $partialRaw = $data['partial_paid_amount'] ?? '';
            $productName = SpreadsheetImport::text($data, 'product');
            $qty = SpreadsheetImport::wholeNumber($data['qty'] ?? null);
            $price = SpreadsheetImport::number($data['unit_price'] ?? null);

            if (! $date) {
                $errors[] = "Row {$rowNo}: Order date must be YYYY-MM-DD.";
            }
            if ($address === '') {
                $errors[] = "Row {$rowNo}: Delivery address is required.";
            }
            if (! in_array($paymentStatus, ['unpaid', 'paid', 'partial'], true)) {
                $errors[] = "Row {$rowNo}: Payment status must be unpaid, paid, or partial.";
            }
            if (! in_array($paymentType, ['cash', 'credit'], true)) {
                $errors[] = "Row {$rowNo}: Payment type must be cash or credit.";
            }

            $partial = null;
            if ($paymentStatus === 'partial') {
                $partial = SpreadsheetImport::number($partialRaw);
                if ($partial === null || $partial < 0) {
                    $errors[] = "Row {$rowNo}: Partial paid amount is required when payment status is partial.";
                    $partial = null;
                }
            }

            if ($productName === '') {
                $errors[] = "Row {$rowNo}: Product is required.";
            }
            if ($qty === null || $qty < 1) {
                $errors[] = "Row {$rowNo}: Qty must be a whole number of at least 1.";
            }
            if ($price === null || $price < 0) {
                $errors[] = "Row {$rowNo}: Unit price must be a number of 0 or more.";
            }

            $header = implode('|', [
                $date ?? '',
                mb_strtolower($brokerName),
                mb_strtolower($brandName),
                mb_strtolower($dealerCode),
                mb_strtolower($address),
                $paymentStatus,
                $paymentType,
                $paymentStatus === 'partial' ? (string) $partial : '',
            ]);

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'header' => $header,
                    'date' => $date,
                    'broker' => $brokerName,
                    'brand' => $brandName,
                    'dealer_code' => $dealerCode,
                    'address' => $address,
                    'payment_status' => $paymentStatus,
                    'payment_type' => $paymentType,
                    'partial' => $partial,
                    'lines' => [],
                ];
            } elseif ($groups[$groupKey]['header'] !== $header) {
                $errors[] = "Row {$rowNo}: Order group {$groupKey} has different order details than its first row.";
            }

            $groups[$groupKey]['lines'][] = [
                'row' => $rowNo,
                'product' => $productName,
                'qty' => $qty,
                'price' => $price,
            ];
        }

        $user = auth()->user();
        $prepared = [];

        foreach ($groups as $groupKey => $group) {
            $firstRow = $group['lines'][0]['row'] ?? 0;
            if ($user && SalesScope::isBroker($user) && $group['broker'] === '') {
                $broker = $user;
            } else {
                $broker = $this->resolveNamed($brokers, $group['broker'], 'Broker', $firstRow, $errors);
                if ($user && SalesScope::isBroker($user) && $broker && (int) $broker->id !== (int) $user->id) {
                    $errors[] = 'Order group ' . $groupKey . ': you can only import orders for your own broker account.';
                    $broker = null;
                }
            }
            $brand = $this->resolveNamed($brands, $group['brand'], 'Brand', $firstRow, $errors);

            $dealer = null;
            $codeKey = mb_strtolower($group['dealer_code']);
            if ($codeKey === '') {
                $errors[] = 'Order group ' . $groupKey . ': Dealer code is required.';
            } elseif (! isset($dealersByCode[$codeKey])) {
                $errors[] = 'Order group ' . $groupKey . ': dealer code "' . $group['dealer_code'] . '" was not found.';
            } else {
                $dealer = $dealersByCode[$codeKey];
                if ($broker && $brand && ((int) $dealer->broker_id !== (int) $broker->id || (int) $dealer->brand_id !== (int) $brand->id)) {
                    $errors[] = 'Order group ' . $groupKey . ': dealer code "' . $group['dealer_code'] . '" does not belong to that broker and brand.';
                    $dealer = null;
                }
            }

            if ($user && SalesScope::isDealer($user)) {
                $own = DealerManagement::where('user_id', $user->id)->first();
                if (! $own || ! $dealer || (int) $dealer->id !== (int) $own->id) {
                    $errors[] = 'Order group ' . $groupKey . ': you can only import orders for your own dealer code.';
                    $dealer = null;
                }
            }

            $lines = [];
            foreach ($group['lines'] as $line) {
                if ($line['product'] === '' || $line['qty'] === null || $line['price'] === null || ! $brand) {
                    continue;
                }
                $matches = $products->filter(function (Product $product) use ($brand, $line) {
                    return (int) $product->brand_id === (int) $brand->id
                        && mb_strtolower(trim($product->name)) === mb_strtolower($line['product']);
                })->values();

                if ($matches->count() !== 1) {
                    $errors[] = $matches->isEmpty()
                        ? "Row {$line['row']}: product \"{$line['product']}\" was not found for this brand."
                        : "Row {$line['row']}: more than one active product is named \"{$line['product']}\" for this brand.";
                    continue;
                }

                $productId = (int) $matches->first()->id;
                $lines[] = [
                    'product_id' => $productId,
                    'qty' => (int) $line['qty'],
                    'unit_price' => round((float) $line['price'], 2),
                    'total_price' => round((int) $line['qty'] * (float) $line['price'], 2),
                ];
            }

            if ($broker && $brand && $dealer && $group['date'] && $group['address'] !== '' && $lines !== []) {
                $prepared[] = [
                    'date' => $group['date'],
                    'broker_id' => (int) $broker->id,
                    'brand_id' => (int) $brand->id,
                    'dealer_id' => (int) $dealer->id,
                    'address' => $group['address'],
                    'payment_status' => $group['payment_status'],
                    'payment_type' => $group['payment_type'],
                    'partial' => $group['payment_status'] === 'partial' ? $group['partial'] : null,
                    'lines' => $lines,
                ];
            }
        }

        if ($errors !== []) {
            $this->fail($errors);
        }

        $ids = [];
        DB::transaction(function () use ($prepared, &$ids) {
            foreach ($prepared as $orderData) {
                $date = Carbon::parse($orderData['date']);
                $order = OrderManagement::create([
                    'unique_order_id' => $this->nextOrderId($date),
                    'broker_id' => $orderData['broker_id'],
                    'brand_id' => $orderData['brand_id'],
                    'dealer_id' => $orderData['dealer_id'],
                    'order_date' => $orderData['date'],
                    'delivery_address' => $orderData['address'],
                    'payment_status' => $orderData['payment_status'],
                    'payment_type' => $orderData['payment_type'],
                    'partial_paid_amount' => $orderData['partial'],
                    'total_order_amount' => array_sum(array_column($orderData['lines'], 'total_price')),
                    'grand_total' => array_sum(array_column($orderData['lines'], 'total_price')),
                    'status' => 1,
                ]);
                $order->items()->createMany($orderData['lines']);
                $ids[] = $order->unique_order_id;
            }
        });

        return ['created' => count($ids), 'ids' => $ids];
    }

    private function nextOrderId(Carbon $date): string
    {
        $startYear = $date->month >= 4 ? $date->year : $date->year - 1;
        $prefix = 'ORD/' . $startYear . '-' . substr((string) ($startYear + 1), -2) . '/';
        $max = 0;

        foreach (OrderManagement::withTrashed()->where('unique_order_id', 'like', $prefix . '%')->pluck('unique_order_id') as $id) {
            if (preg_match('/(\d+)$/', (string) $id, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return $prefix . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  Collection<int, User|BrandManagement>  $models
     * @return array<string, User|BrandManagement|false>
     */
    private function indexByName(Collection $models): array
    {
        $map = [];
        foreach ($models as $model) {
            $key = mb_strtolower(trim((string) $model->name));
            $map[$key] = array_key_exists($key, $map) ? false : $model;
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $map
     * @param  list<string>  $errors
     */
    private function resolveNamed(array $map, string $name, string $label, int $rowNo, array &$errors): mixed
    {
        if ($name === '') {
            $errors[] = "Row {$rowNo}: {$label} is required.";

            return null;
        }

        $key = mb_strtolower($name);
        if (! array_key_exists($key, $map) || $map[$key] === false) {
            $errors[] = array_key_exists($key, $map)
                ? "Row {$rowNo}: more than one active {$label} is named \"{$name}\"."
                : "Row {$rowNo}: {$label} \"{$name}\" was not found.";

            return null;
        }

        return $map[$key];
    }

    /** @param  list<string>  $errors */
    private function fail(array $errors): void
    {
        $shown = array_slice(array_values(array_unique($errors)), 0, 15);
        $extra = count(array_unique($errors)) - count($shown);
        if ($extra > 0) {
            $shown[] = $extra . ' more error(s). Fix the rows above and import again.';
        }

        throw ValidationException::withMessages(['file' => $shown]);
    }
}
