@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('styles')
    @include('raw_material.partials.module-responsive')
@endsection
@section('content')

<div class="raw-material-module">
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <p class="form-section-title mb-0"><i class="ti ti-packages me-1"></i>Raw Material Details</p>
            <div class="d-flex gap-2">
                @can('edit-raw-material-inventory')
                    <a href="{{ route('raw-material.edit', $raw_material->id) }}" class="btn btn-warning btn-sm">
                        <i class="ti ti-edit me-1"></i>Edit
                    </a>
                @endcan
                <a href="{{ route('raw-material.index') }}" class="btn btn-light btn-sm">
                    <i class="ti ti-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>
        <div class="row">
            <div class="col-12 col-sm-6 col-md-4 mb-3">
                <label class="col-form-label text-muted">Material ID</label>
                <div class="fw-semibold">{{ $raw_material->raw_material_unique_id }}</div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 mb-3">
                <label class="col-form-label text-muted">Name</label>
                <div class="fw-semibold">{{ $raw_material->name }}</div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 mb-3">
                <label class="col-form-label text-muted">Unit</label>
                <div class="fw-semibold">{{ $raw_material->unit }}</div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 mb-3">
                <label class="col-form-label text-muted">Total Stock</label>
                <div class="fw-semibold">{{ number_format($raw_material->total_stock, 2) }} {{ $raw_material->unit }}</div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 mb-3">
                <label class="col-form-label text-muted">Available Stock</label>
                <div class="fw-semibold">{{ number_format($raw_material->available_stock, 2) }} {{ $raw_material->unit }}</div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 mb-3">
                <label class="col-form-label text-muted">Status</label>
                <div>{!! $raw_material->statusBadge() !!}</div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 mb-3">
                <label class="col-form-label text-muted">Last Purchase Price</label>
                <div class="fw-semibold">₹ {{ number_format($raw_material->last_purchase_price, 2) }} / kg</div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 mb-3">
                <label class="col-form-label text-muted">Average Price</label>
                <div class="fw-semibold">₹ {{ number_format($raw_material->average_price, 2) }} / kg</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <p class="form-section-title"><i class="ti ti-shopping-cart me-1"></i>Purchase Order History</p>
        <div class="table-responsive custom-table">
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Sr No</th>
                        <th>Order ID</th>
                        <th>Supplier</th>
                        <th>Order Date</th>
                        <th>Total Qty (tons)</th>
                        <th>Price/kg</th>
                        <th>Total Price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($order_items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                @if ($item->order)
                                    <a href="{{ route('raw-material.order.show', $item->order->id) }}">
                                        {{ $item->order->order_unique_id }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $item->order?->supplier?->name ?? '—' }}</td>
                            <td>{{ $item->order?->order_date?->format('d M Y') ?? '—' }}</td>
                            <td>{{ $item->total_qty }}</td>
                            <td>₹ {{ number_format($item->price, 2) }}</td>
                            <td>₹ {{ number_format($item->total_price, 2) }}</td>
                            <td>{!! $item->statusBadge() !!}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No purchase order history found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

@endsection
