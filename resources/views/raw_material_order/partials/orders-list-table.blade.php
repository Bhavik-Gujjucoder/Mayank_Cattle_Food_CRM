<table>
    <thead>
        <tr>
            <th>Sr No</th>
            <th>Order ID</th>
            <th>Supplier Broker</th>
            <th>Supplier</th>
            <th>Supplier Order ID</th>
            <th>Price Basis</th>
            <th>Order Date</th>
            <th class="text-right">Total Qty (tons)</th>
            <th class="text-right">Total Price (₹)</th>
            <th class="text-right">Total Freight (₹)</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($orders as $index => $order)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $order->order_unique_id }}</td>
                <td>{{ $order->supplierBroker?->name ?? '—' }}</td>
                <td>{{ $order->supplier?->name ?? '—' }}</td>
                <td>{{ $order->supplier_order_id ?: '—' }}</td>
                <td>{{ $order->price_basis ?: '—' }}</td>
                <td>{{ $order->order_date?->format('d M Y') ?? '—' }}</td>
                <td class="text-right">{{ $order->total_qty }}</td>
                <td class="text-right">{{ number_format((float) $order->total_price, 2) }}</td>
                <td class="text-right">{{ number_format((float) $order->total_freight, 2) }}</td>
                <td>{{ \App\Services\RawMaterial\RawMaterialFilterService::orderStatusLabel((int) $order->status) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
