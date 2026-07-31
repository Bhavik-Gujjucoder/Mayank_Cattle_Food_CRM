@php
    use App\Models\WeeklyReportItem;
    use App\Support\ProductUnit;
@endphp

<div class="wr-listing-wrap">
    <div class="wr-scroll-hint d-flex">
        <i class="ti ti-arrows-horizontal me-1"></i> Swipe or scroll horizontally for all columns
    </div>

    <div class="wr-table-scroll">
        <div class="table-responsive wr-table-wrap custom-table">
            <table class="table table-hover align-middle weekly-report-items-table mb-0">
                <colgroup>
                    <col class="wr-col-order-no">
                    <col class="wr-col-order-id">
                    <col class="wr-col-product">
                    <col class="wr-col-dealer">
                    <col class="wr-col-city">
                    <col class="wr-col-qty">
                    <col class="wr-col-transport">
                    <col class="wr-col-truck">
                    <col class="wr-col-contact">
                    <col class="wr-col-note">
                    <col class="wr-col-status">
                    <col class="wr-col-action">
                </colgroup>
                <thead>
                    <tr>
                        <th>Order No</th>
                        <th>Order ID</th>
                        <th>Product</th>
                        <th>Dealer</th>
                        <th>City</th>
                        <th>Qty</th>
                        <th>Transport</th>
                        <th>Truck No</th>
                        <th>Contact</th>
                        <th>Note</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody class="weekly-report-items-body">
                    @forelse ($report->items as $item)
                        @php
                            $dealer = $item->order?->dealer;
                            $locked = $item->isLocked();
                        @endphp
                        <tr data-item-id="{{ $item->id }}"
                            data-locked="{{ $locked ? '1' : '0' }}"
                            data-unit="{{ $item->product?->unit }}"
                            data-qty="{{ $item->quantity }}"
                            class="{{ $locked ? 'wr-row-confirmed' : 'wr-row-pending' }}">
                            <td data-label="Order No">
                                @if ($canEdit && ! $locked)
                                    <input type="number" class="form-control form-control-sm item-sort-order text-center"
                                        value="{{ $item->sort_order }}" min="0" title="Confirmation order">
                                @elseif ($canEdit && $locked)
                                    <input type="number" class="form-control form-control-sm item-sort-order wr-confirmed-sort-order text-center"
                                        value="{{ $item->sort_order }}" min="0" title="Confirmation order (editable)">
                                @else
                                    <div class="wr-readonly-value wr-readonly-value--center">{{ $item->sort_order }}</div>
                                @endif
                            </td>
                            <td data-label="Order ID">
                                <div class="wr-readonly-value wr-cell-order">{{ $item->order?->unique_order_id ?? '—' }}</div>
                            </td>
                            <td data-label="Product">
                                <div class="wr-readonly-value">{{ $item->product?->name ?? '—' }}</div>
                            </td>
                            <td data-label="Dealer">
                                <div class="wr-readonly-value">{{ $dealer?->user?->name ?? $dealer?->firm_shop_name ?? '—' }}</div>
                            </td>
                            <td data-label="City">
                                <div class="wr-readonly-value">{{ $dealer?->city?->city_name ?? '—' }}</div>
                            </td>
                            <td data-label="Qty">
                                @if ($canEdit && ! $locked)
                                    <input type="number" class="form-control form-control-sm item-qty"
                                        value="{{ $item->quantity }}" min="1">
                                    <small class="text-muted wr-unit-hint">{{ $item->product?->unit }}</small>
                                @else
                                    <div class="wr-readonly-value">{{ ProductUnit::formatWithUnit($item->quantity, $item->product?->unit) }}</div>
                                @endif
                            </td>
                            <td data-label="Transport" class="wr-cell-transport">
                                @if ($canEdit && ! $locked)
                                    @include('weekly_report.partials.transport-field', [
                                        'name' => 'transport_id',
                                        'selectClass' => 'item-transport',
                                        'transporters' => $transporters,
                                        'selected' => $item->transport_id,
                                        'label' => '',
                                    ])
                                @else
                                    <div class="wr-readonly-value">{{ $item->transporter?->name ?? '—' }}</div>
                                @endif
                            </td>
                            <td data-label="Truck No" class="wr-cell-truck">
                                @if ($canEdit && ! $locked)
                                    @include('weekly_report.partials.truck-field', [
                                        'name' => 'truck_number',
                                        'selectClass' => 'item-truck',
                                        'selected' => $item->truck_number,
                                        'disabled' => ! $item->transport_id,
                                        'label' => '',
                                    ])
                                @else
                                    <div class="wr-readonly-value">{{ $item->truck_number ?? '—' }}</div>
                                @endif
                            </td>
                            <td data-label="Contact">
                                @if ($canEdit && ! $locked)
                                    <input type="text" class="form-control form-control-sm item-contact"
                                        value="{{ $item->driver_contact }}">
                                @else
                                    <div class="wr-readonly-value">{{ $item->driver_contact ?? '—' }}</div>
                                @endif
                            </td>
                            <td data-label="Note">
                                @if ($canEdit && ! $locked)
                                    <textarea class="form-control form-control-sm item-note" rows="2">{{ $item->note }}</textarea>
                                @else
                                    <div class="wr-readonly-value wr-readonly-value--note">{{ $item->note ?: '—' }}</div>
                                @endif
                            </td>
                            <td data-label="Status" class="wr-cell-status">
                                <div class="wr-readonly-value">{!! $item->statusBadge() !!}</div>
                            </td>
                            <td data-label="Action" class="wr-cell-action">
                                @if ($locked)
                                    <div class="wr-locked-action">
                                        @if ($item->dispatch_id)
                                            <a href="{{ route('dispatch.orderHistory', $item->order_id) }}"
                                                class="btn btn-sm btn-outline-success wr-dispatch-btn">
                                                <i class="ti ti-external-link me-1"></i>View dispatch
                                            </a>
                                        @else
                                            <span class="wr-confirmed-pill">
                                                <i class="ti ti-circle-check me-1"></i>Confirmed
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <div class="wr-row-actions">
                                        @if ($canEdit)
                                            <button type="button" class="btn btn-primary save-item-btn">
                                                <i class="ti ti-device-floppy"></i><span>Save</span>
                                            </button>
                                        @endif
                                        @if ($canConfirm)
                                            <button type="button" class="btn btn-success confirm-item-btn"
                                                data-item-id="{{ $item->id }}">
                                                <i class="ti ti-check"></i><span>Confirm</span>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <form action="{{ route('weekly-report.items.destroy', [$report->id, $item->id]) }}"
                                                method="POST" class="delete-item-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="Remove">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="wr-empty-rows-message">
                            <td colspan="12">
                                <div class="wr-empty-state py-3">
                                    <i class="ti ti-inbox"></i>
                                    No orders yet — use the form above.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
