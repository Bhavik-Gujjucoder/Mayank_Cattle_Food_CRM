@php
    $lineCount = $order->items->count();
@endphp
<div class="ol-detail-panel">
    <div class="ol-detail-head">
        <span class="ol-detail-title">
            <i class="ti ti-packages me-1"></i>Product line items
            <span class="ol-detail-order-tag">{{ $order->unique_order_id }}</span>
        </span>
        <span class="ol-detail-meta">
            {{ $lineCount }} product{{ $lineCount !== 1 ? 's' : '' }}
            · Order total <strong>₹ {{ number_format($order->grand_total, 2) }}</strong>
            · Avg <strong>₹ {{ number_format($order->weightedAvgUnitPrice(), 2) }}</strong> / bag
        </span>
    </div>

    @if ($order->items->isEmpty())
        <p class="text-muted small mb-0">No products on this order.</p>
    @else
        <div class="table-responsive ol-detail-table-wrap">
            <table class="table table-sm table-bordered ol-detail-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Product</th>
                        <th class="text-end">Unit price</th>
                        <th class="text-center">Ordered</th>
                        <th class="text-center">Dispatched</th>
                        <th class="text-center">Pending</th>
                        <th class="text-end">Line total</th>
                        <th style="min-width:120px;">Progress</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        @php
                            $ordered    = (int) $item->qty;
                            $dispatched = (int) $item->dispatches->sum('no_of_bags');
                            $pending    = max(0, $ordered - $dispatched);
                            $pct        = $ordered > 0 ? (int) round(($dispatched / $ordered) * 100) : 0;
                        @endphp
                        <tr>
                            <td>
                                <i class="ti ti-package me-1 text-primary"></i>
                                {{ $item->product?->name ?? '—' }}
                            </td>
                            <td class="text-end">₹ {{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-center">{{ $ordered }}</td>
                            <td class="text-center">
                                <span class="ol-badge-dispatched">{{ $dispatched }}</span>
                            </td>
                            <td class="text-center">
                                <span class="{{ $pending > 0 ? 'ol-badge-pending' : 'ol-badge-done' }}">
                                    {{ $pending }}
                                </span>
                            </td>
                            <td class="text-end fw-semibold">₹ {{ number_format($item->total_price, 2) }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress ol-detail-prog flex-grow-1">
                                        <div class="progress-bar {{ $pct >= 100 ? 'bg-success' : 'bg-primary' }}"
                                            role="progressbar" style="width:{{ $pct }}%"></div>
                                    </div>
                                    <span class="small text-muted">{{ $pct }}%</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
