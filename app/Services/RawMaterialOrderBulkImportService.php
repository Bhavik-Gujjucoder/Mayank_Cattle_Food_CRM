<?php

namespace App\Services;

use App\Models\RawMaterial;
use App\Models\RawMaterialOrder;
use App\Models\Supplier;
use App\Models\SupplierBroker;
use App\Support\RawMaterialOrderPriceBasis;
use App\Support\SpreadsheetImport;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RawMaterialOrderBulkImportService
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

        $brokers = $this->indexByName(SupplierBroker::query()->where('status', 1)->get(['id', 'name']));
        $suppliers = Supplier::query()->where('status', 1)->get(['id', 'name', 'supplier_broker_id']);
        $materials = RawMaterial::query()->where('status', 1)->get(['id', 'name']);
        $basisMap = [];
        foreach (RawMaterialOrderPriceBasis::options() as $option) {
            $basisMap[mb_strtolower($option)] = $option;
        }

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
            $brokerName = SpreadsheetImport::text($data, 'supplier_broker');
            $supplierName = SpreadsheetImport::text($data, 'supplier');
            $supplierOrderId = SpreadsheetImport::text($data, 'supplier_order_id');
            $basisRaw = SpreadsheetImport::text($data, 'price_basis');
            $advance = SpreadsheetImport::number($data['advance_payment'] ?? '');
            if (trim((string) ($data['advance_payment'] ?? '')) === '') {
                $advance = 0.0;
            }
            $materialName = SpreadsheetImport::text($data, 'raw_material');
            $qty = SpreadsheetImport::number($data['qty'] ?? null);
            $price = SpreadsheetImport::number($data['price'] ?? null);
            $tax = SpreadsheetImport::number($data['tax_percent'] ?? '');
            if (trim((string) ($data['tax_percent'] ?? '')) === '') {
                $tax = 0.0;
            }
            $other = SpreadsheetImport::number($data['other_expense'] ?? '');
            if (trim((string) ($data['other_expense'] ?? '')) === '') {
                $other = 0.0;
            }
            $tds = SpreadsheetImport::number($data['tds_amount'] ?? '');
            if (trim((string) ($data['tds_amount'] ?? '')) === '') {
                $tds = 0.0;
            }

            if (! $date) {
                $errors[] = "Row {$rowNo}: Order date must be YYYY-MM-DD.";
            }
            if (mb_strlen($supplierOrderId) > 100) {
                $errors[] = "Row {$rowNo}: Supplier order id cannot be longer than 100 characters.";
            }
            if (! isset($basisMap[mb_strtolower($basisRaw)])) {
                $errors[] = "Row {$rowNo}: Price basis must be FOR + GST or Ex-Factory + GST.";
            }
            if ($advance === null || $advance < 0) {
                $errors[] = "Row {$rowNo}: Advance payment must be 0 or more.";
            }
            if ($materialName === '') {
                $errors[] = "Row {$rowNo}: Raw material is required.";
            }
            if ($qty === null || $qty < 0.001 || abs($qty - round($qty, 3)) > 0.000001) {
                $errors[] = "Row {$rowNo}: Qty must be at least 0.001 and at most 3 decimal places.";
            }
            if ($price === null || $price <= 0) {
                $errors[] = "Row {$rowNo}: Price must be greater than 0.";
            }
            if ($tax === null || $tax < 0 || $tax > 100) {
                $errors[] = "Row {$rowNo}: Tax percent must be between 0 and 100.";
            }
            if ($other === null || $other < 0) {
                $errors[] = "Row {$rowNo}: Other expense must be 0 or more.";
            }
            if ($tds === null || $tds < 0) {
                $errors[] = "Row {$rowNo}: TDS amount must be 0 or more.";
            }

            $basis = $basisMap[mb_strtolower($basisRaw)] ?? '';
            $header = implode('|', [
                $date ?? '',
                mb_strtolower($brokerName),
                mb_strtolower($supplierName),
                mb_strtolower($supplierOrderId),
                $basis,
                $advance === null ? '' : number_format($advance, 2, '.', ''),
            ]);

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'header' => $header,
                    'date' => $date,
                    'broker' => $brokerName,
                    'supplier' => $supplierName,
                    'supplier_order_id' => $supplierOrderId !== '' ? $supplierOrderId : null,
                    'basis' => $basis,
                    'advance' => $advance ?? 0,
                    'lines' => [],
                ];
            } elseif ($groups[$groupKey]['header'] !== $header) {
                $errors[] = "Row {$rowNo}: Order group {$groupKey} has different order details than its first row.";
            }

            $groups[$groupKey]['lines'][] = [
                'row' => $rowNo,
                'material' => $materialName,
                'qty' => $qty,
                'price' => $price,
                'tax' => $tax ?? 0,
                'other' => $other ?? 0,
                'tds' => $tds ?? 0,
            ];
        }

        $prepared = [];
        foreach ($groups as $groupKey => $group) {
            $firstRow = $group['lines'][0]['row'] ?? 0;
            $broker = $this->resolveNamed($brokers, $group['broker'], 'Supplier broker', $firstRow, $errors);
            $supplier = null;
            if ($group['supplier'] === '') {
                $errors[] = "Row {$firstRow}: Supplier is required.";
            } elseif ($broker) {
                $matches = $suppliers->filter(function (Supplier $supplier) use ($broker, $group) {
                    return (int) $supplier->supplier_broker_id === (int) $broker->id
                        && mb_strtolower(trim($supplier->name)) === mb_strtolower($group['supplier']);
                })->values();
                if ($matches->count() !== 1) {
                    $errors[] = $matches->isEmpty()
                        ? "Row {$firstRow}: supplier \"{$group['supplier']}\" was not found for this supplier broker."
                        : "Row {$firstRow}: more than one active supplier is named \"{$group['supplier']}\" for this broker.";
                } else {
                    $supplier = $matches->first();
                }
            }

            $lines = [];
            foreach ($group['lines'] as $line) {
                if ($line['material'] === '' || $line['qty'] === null || $line['price'] === null) {
                    continue;
                }
                $matches = $materials->filter(function (RawMaterial $material) use ($line) {
                    return mb_strtolower(trim($material->name)) === mb_strtolower($line['material']);
                })->values();
                if ($matches->count() !== 1) {
                    $errors[] = $matches->isEmpty()
                        ? "Row {$line['row']}: raw material \"{$line['material']}\" was not found."
                        : "Row {$line['row']}: more than one active raw material is named \"{$line['material']}\".";
                    continue;
                }
                $materialId = (int) $matches->first()->id;
                $lines[] = [
                    'raw_material_id' => $materialId,
                    'total_qty' => rmQty($line['qty']),
                    'price' => round((float) $line['price'], 2),
                    'tax_percent' => round((float) $line['tax'], 2),
                    'other_expense' => round((float) $line['other'], 2),
                    'tds_amount' => round((float) $line['tds'], 2),
                ];
            }

            if ($broker && $supplier && $group['date'] && $group['basis'] !== '' && $lines !== []) {
                $prepared[] = [
                    'date' => $group['date'],
                    'supplier_broker_id' => (int) $broker->id,
                    'supplier_id' => (int) $supplier->id,
                    'supplier_order_id' => $group['supplier_order_id'],
                    'basis' => $group['basis'],
                    'advance' => round((float) $group['advance'], 2),
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
                $order = RawMaterialOrder::create([
                    'order_unique_id' => RawMaterialIdGenerator::nextOrderId(Carbon::parse($orderData['date'])),
                    'supplier_broker_id' => $orderData['supplier_broker_id'],
                    'supplier_id' => $orderData['supplier_id'],
                    'supplier_order_id' => $orderData['supplier_order_id'],
                    'order_date' => $orderData['date'],
                    'price_basis' => $orderData['basis'],
                    'advance_payment' => $orderData['advance'],
                ]);
                foreach ($orderData['lines'] as $line) {
                    $order->items()->create($line);
                }
                $ids[] = $order->order_unique_id;
            }
        });

        return ['created' => count($ids), 'ids' => $ids];
    }

    /**
     * @param  Collection<int, SupplierBroker>  $models
     * @return array<string, SupplierBroker|false>
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
        $unique = array_values(array_unique($errors));
        $shown = array_slice($unique, 0, 15);
        $extra = count($unique) - count($shown);
        if ($extra > 0) {
            $shown[] = $extra . ' more error(s). Fix the rows above and import again.';
        }

        throw ValidationException::withMessages(['file' => $shown]);
    }
}
