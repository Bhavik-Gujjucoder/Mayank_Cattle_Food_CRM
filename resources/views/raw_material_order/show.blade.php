@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('content')

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <p class="form-section-title mb-0"><i class="ti ti-file-description me-1"></i>Purchase Order</p>
            <div class="d-flex gap-2">
                @if ($order->isEditable() && auth()->user()->can('edit-raw-material-purchas-order'))
                    <a href="{{ route('raw-material.order.edit', $order->id) }}" class="btn btn-warning btn-sm">
                        <i class="ti ti-edit me-1"></i>Edit
                    </a>
                @endif
                <a href="{{ route('raw-material.order.exportPdf', $order->id) }}" class="btn btn-outline-primary btn-sm">
                    <i class="ti ti-file-type-pdf me-1"></i>Export PDF
                </a>
                <a href="{{ route('raw-material.order.index') }}" class="btn btn-light btn-sm">
                    <i class="ti ti-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="col-form-label text-muted">Order ID</label>
                <div class="fw-semibold">{{ $order->order_unique_id }}</div>
            </div>
            <div class="col-md-3 mb-3">
                <label class="col-form-label text-muted">Supplier</label>
                <div class="fw-semibold">{{ $order->supplier?->name ?? '—' }}</div>
            </div>
            <div class="col-md-3 mb-3">
                <label class="col-form-label text-muted">Order Date</label>
                <div class="fw-semibold">{{ $order->order_date?->format('d M Y') ?? '—' }}</div>
            </div>
            <div class="col-md-3 mb-3">
                <label class="col-form-label text-muted">Status</label>
                <div>{!! $order->statusBadge() !!}</div>
            </div>
            <div class="col-md-3 mb-3">
                <label class="col-form-label text-muted">Total Qty</label>
                <div class="fw-semibold">{{ $order->total_qty ?? 0 }} tons</div>
            </div>
            <div class="col-md-3 mb-3">
                <label class="col-form-label text-muted">Total Price</label>
                <div class="fw-semibold">₹ {{ number_format($order->total_price ?? 0, 3) }}</div>
            </div>
            <div class="col-md-3 mb-3">
                <label class="col-form-label text-muted">Total Freight</label>
                <div class="fw-semibold">₹ {{ number_format($order->total_freight ?? 0, 3) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <p class="form-section-title"><i class="ti ti-packages me-1"></i>Order Items</p>
        <div class="table-responsive custom-table">
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Sr No</th>
                        <th>Material</th>
                        <th>Total Qty (tons)</th>
                        <th>Pending Qty</th>
                        <th>Received Qty</th>
                        <th>Price/kg</th>
                        <th>Avg Price/kg</th>
                        <th>Total Price</th>
                        <th>Pending Price</th>
                        <th>Received Price</th>
                        <th>Freight</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($order->items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->rawMaterial?->name ?? '—' }}</td>
                            <td>{{ $item->total_qty }}</td>
                            <td>{{ $item->pending_qty }}</td>
                            <td>{{ $item->received_qty }}</td>
                            <td>₹ {{ number_format($item->price, 3) }}</td>
                            <td>₹ {{ number_format($item->price_avg, 3) }}</td>
                            <td>₹ {{ number_format($item->total_price, 3) }}</td>
                            <td>₹ {{ number_format($item->pending_price, 3) }}</td>
                            <td>₹ {{ number_format($item->received_price, 3) }}</td>
                            <td>₹ {{ number_format($item->total_freight, 3) }}</td>
                            <td>{!! $item->statusBadge() !!}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted py-4">No items found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <p class="form-section-title"><i class="ti ti-truck-delivery me-1"></i>Receive Entries</p>
        <div class="table-responsive custom-table">
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Sr No</th>
                        <th>Material</th>
                        <th>Qty (tons)</th>
                        <th>Freight</th>
                        <th>Received Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($order->receives as $index => $receive)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $receive->rawMaterial?->name ?? '—' }}</td>
                            <td>{{ $receive->qty }}</td>
                            <td>₹ {{ number_format($receive->freight, 3) }}/ton<br>
                                <small class="text-muted">Line: ₹ {{ number_format($receive->freight * $receive->qty, 3) }}</small></td>
                            <td>{{ $receive->received_date?->format('d M Y') ?? '—' }}</td>
                            <td>{!! $receive->statusBadge() !!}</td>
                            <td>
                                <a href="{{ route('raw-material.receive.show', $receive->id) }}" class="btn btn-sm btn-info">
                                    <i class="ti ti-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No receive entries yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
