@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">{{ $order->unique_order_id }}</h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('order.edit', $order->id) }}" class="btn btn-sm btn-warning">
                        <i class="ti ti-edit"></i> Edit
                    </a>
                    <a href="{{ route('dispatch.orderHistory', $order->id) }}" class="btn btn-sm btn-info">
                        <i class="ti ti-truck"></i> Dispatch History
                    </a>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="text-muted small">Order Date</label>
                    <div>{{ $order->order_date?->format('d M Y') ?? '—' }}</div>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small">Broker</label>
                    <div>{{ $order->broker?->name ?? '—' }}</div>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small">Brand</label>
                    <div>{{ $order->brand?->name ?? '—' }}</div>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small">Dealer</label>
                    <div>{{ $order->dealer?->user?->name ?? $order->dealer?->firm_shop_name ?? '—' }}</div>
                </div>
                <div class="col-md-12 mt-3">
                    <label class="text-muted small">Delivery Address</label>
                    <div>{{ $order->delivery_address ?: '—' }}</div>
                </div>
                <div class="col-md-3 mt-3">
                    <label class="text-muted small">Grand Total</label>
                    <div class="fw-semibold">₹ {{ number_format((float) $order->grand_total, 2) }}</div>
                </div>
                <div class="col-md-3 mt-3">
                    <label class="text-muted small">Payment Status</label>
                    <div>{!! $order->paymentBadge() !!}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">Line Items</h6>
            @include('order_management.partials.list-items-detail', ['order' => $order])
            <a href="{{ route('order.index') }}" class="btn btn-secondary mt-3">Back to list</a>
        </div>
    </div>
@endsection
