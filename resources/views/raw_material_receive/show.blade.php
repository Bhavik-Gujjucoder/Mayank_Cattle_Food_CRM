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
            <p class="form-section-title mb-0"><i class="ti ti-truck-delivery me-1"></i>Received Entry</p>
            <div class="d-flex gap-2">
                @if ($receive->isEditable() && auth()->user()->can('edit-raw-material-purchas-order'))
                    <a href="{{ route('raw-material.receive.edit', $receive->id) }}" class="btn btn-warning btn-sm">
                        <i class="ti ti-edit me-1"></i>Edit
                    </a>
                    <form action="{{ route('raw-material.receive.markReceived', $receive->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="ti ti-check me-1"></i>Mark Received
                        </button>
                    </form>
                @endif
                <a href="{{ route('raw-material.receive.index') }}" class="btn btn-light btn-sm">
                    <i class="ti ti-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>
        <div class="row">
            <div class="col-12 col-sm-6 col-md-4 mb-3">
                <label class="col-form-label text-muted">Order ID</label>
                <div class="fw-semibold">
                    @if ($receive->order)
                        <a href="{{ route('raw-material.order.show', $receive->order->id) }}">
                            {{ $receive->order->order_unique_id }}
                        </a>
                    @else
                        —
                    @endif
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 mb-3">
                <label class="col-form-label text-muted">Supplier</label>
                <div class="fw-semibold">{{ $receive->order?->supplier?->name ?? '—' }}</div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 mb-3">
                <label class="col-form-label text-muted">Material</label>
                <div class="fw-semibold">{{ $receive->rawMaterial?->name ?? '—' }}</div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 mb-3">
                <label class="col-form-label text-muted">Quantity</label>
                <div class="fw-semibold">{{ $receive->qty }} tons</div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 mb-3">
                <label class="col-form-label text-muted">Freight per ton</label>
                <div class="fw-semibold">₹ {{ number_format($receive->freight, 3) }}</div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 mb-3">
                <label class="col-form-label text-muted">Line Freight (freight × qty)</label>
                <div class="fw-semibold">₹ {{ number_format($receive->freight * $receive->qty, 3) }}</div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 mb-3">
                <label class="col-form-label text-muted">Received Date</label>
                <div class="fw-semibold">{{ $receive->received_date?->format('d M Y') ?? '—' }}</div>
            </div>
            <div class="col-12 col-sm-6 col-md-4 mb-3">
                <label class="col-form-label text-muted">Status</label>
                <div>{!! $receive->statusBadge() !!}</div>
            </div>
        </div>
    </div>
</div>

@if ($receive->orderItem)
<div class="card">
    <div class="card-body">
        <p class="form-section-title"><i class="ti ti-list-details me-1"></i>Order Item Details</p>
        <div class="row">
            <div class="col-12 col-sm-6 col-md-3 mb-3">
                <label class="col-form-label text-muted">Total Qty</label>
                <div class="fw-semibold">{{ $receive->orderItem->total_qty }} tons</div>
            </div>
            <div class="col-12 col-sm-6 col-md-3 mb-3">
                <label class="col-form-label text-muted">Pending Qty</label>
                <div class="fw-semibold">{{ $receive->orderItem->pending_qty }} tons</div>
            </div>
            <div class="col-12 col-sm-6 col-md-3 mb-3">
                <label class="col-form-label text-muted">Received Qty</label>
                <div class="fw-semibold">{{ $receive->orderItem->received_qty }} tons</div>
            </div>
            <div class="col-12 col-sm-6 col-md-3 mb-3">
                <label class="col-form-label text-muted">Price / kg</label>
                <div class="fw-semibold">₹ {{ number_format($receive->orderItem->price, 3) }}</div>
            </div>
        </div>
    </div>
</div>
@endif
</div>

@endsection
