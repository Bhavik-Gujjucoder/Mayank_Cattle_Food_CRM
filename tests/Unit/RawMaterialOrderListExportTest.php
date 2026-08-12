<?php

use App\Models\RawMaterialOrder;
use App\Models\Supplier;
use App\Models\SupplierBroker;
use App\Support\RawMaterialOrderListExport;

test('order list export columns match listing table', function () {
    $broker = SupplierBroker::create(['name' => 'Broker A', 'status' => 1]);
    $supplier = Supplier::create([
        'name' => 'Supplier A',
        'supplier_broker_id' => $broker->id,
        'status' => 1,
    ]);

    $order = RawMaterialOrder::create([
        'order_unique_id' => 'RMO-LIST-1',
        'supplier_broker_id' => $broker->id,
        'supplier_id' => $supplier->id,
        'supplier_order_id' => 'SO-100',
        'price_basis' => 'FOR + GST',
        'order_date' => '2026-06-01',
        'total_qty' => 50,
        'total_price' => 100000,
        'total_freight' => 5000,
        'status' => 0,
    ]);

    $order->load(['supplier', 'supplierBroker']);

    expect(RawMaterialOrderListExport::headings())->toBe([
        'Sr No',
        'Order ID',
        'Supplier Broker',
        'Supplier',
        'Supplier Order ID',
        'Price Basis',
        'Order Date',
        'Total Qty (tons)',
        'Extra Qty',
        'Total Price (₹)',
        'Advance Payment (₹)',
        'Total Freight (₹)',
        'Status',
    ]);

    expect(RawMaterialOrderListExport::row($order, 1))->toBe([
        1,
        'RMO-LIST-1',
        'Broker A',
        'Supplier A',
        'SO-100',
        'FOR + GST',
        '01-06-2026',
        50,
        0,
        '100,000.00',
        '0.00',
        '5,000.00',
        'Pending',
    ]);
});

test('order list export includes advance payment amount', function () {
    $broker = SupplierBroker::create(['name' => 'Broker B', 'status' => 1]);
    $supplier = Supplier::create([
        'name' => 'Supplier B',
        'supplier_broker_id' => $broker->id,
        'status' => 1,
    ]);

    $order = RawMaterialOrder::create([
        'order_unique_id' => 'RMO-LIST-ADV',
        'supplier_broker_id' => $broker->id,
        'supplier_id' => $supplier->id,
        'supplier_order_id' => 'SO-200',
        'price_basis' => 'FOR + GST',
        'order_date' => '2026-06-01',
        'total_qty' => 20,
        'total_price' => 50000,
        'advance_payment' => 12000.5,
        'total_freight' => 1000,
        'status' => 0,
    ]);

    $order->load(['supplier', 'supplierBroker']);

    expect(RawMaterialOrderListExport::row($order, 1)[10])->toBe('12,000.50');
});
