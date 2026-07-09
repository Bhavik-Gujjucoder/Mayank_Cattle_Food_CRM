@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('styles')
    @include('weekly_report.partials.confirmed-row-styles')
@endsection
@section('content')

@php
    use App\Models\WeeklyReportItem;
    use App\Support\ProductUnit;
    $canEdit = auth()->user()->can('edit-weekly-report');
    $canDelete = auth()->user()->can('delete-weekly-report');
    $canConfirm = $canEdit && auth()->user()->can('add-dispatch');
@endphp

<div class="card mb-3">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <div class="dispatch-index-eyebrow">Dispatch Prediction</div>
            <h5 class="mb-0">
                {{ $report->report_date->format('d/m/Y') }}
                — {{ strtoupper($report->report_date->format('l')) }}
            </h5>
            <p class="text-muted small mb-0 mt-1">
                Manual pick from pending orders. Confirm creates a real dispatch (sequential rules apply).
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('weekly-report.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Back
            </a>
            @can('edit-weekly-report')
                <button type="button" class="btn btn-primary" id="openAddItemModal">
                    <i class="ti ti-plus me-1"></i>Add Row
                </button>
            @endcan
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered align-middle" id="weeklyReportItemsTable">
                <thead class="thead-light">
                    <tr>
                        <th style="width:70px;">Sr No</th>
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
                        <th style="min-width:160px;">Action</th>
                    </tr>
                </thead>
                <tbody id="weeklyReportItemsBody">
                    @forelse ($report->items as $item)
                        @php
                            $dealer = $item->order?->dealer;
                            $locked = $item->isLocked();
                        @endphp
                        <tr data-item-id="{{ $item->id }}"
                            data-locked="{{ $locked ? '1' : '0' }}"
                            data-unit="{{ $item->product?->unit }}"
                            data-qty="{{ $item->quantity }}"
                            class="{{ $locked ? 'wr-row-confirmed' : '' }}">
                            <td>
                                @if ($canEdit && ! $locked)
                                    <input type="number" class="form-control form-control-sm item-sort-order"
                                        value="{{ $item->sort_order }}" min="0" style="width:70px;">
                                @else
                                    <div class="wr-readonly-value">{{ $item->sort_order }}</div>
                                @endif
                            </td>
                            <td><div class="wr-readonly-value">{{ $item->order?->unique_order_id ?? '—' }}</div></td>
                            <td><div class="wr-readonly-value">{{ $item->product?->name ?? '—' }}</div></td>
                            <td><div class="wr-readonly-value">{{ $dealer?->user?->name ?? $dealer?->firm_shop_name ?? '—' }}</div></td>
                            <td><div class="wr-readonly-value">{{ $dealer?->city?->city_name ?? '—' }}</div></td>
                            <td>
                                @if ($canEdit && ! $locked)
                                    <input type="number" class="form-control form-control-sm item-qty"
                                        value="{{ $item->quantity }}" min="1" style="width:90px;">
                                    <small class="text-muted">{{ $item->product?->unit }}</small>
                                @else
                                    <div class="wr-readonly-value">{{ ProductUnit::formatWithUnit($item->quantity, $item->product?->unit) }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($canEdit && ! $locked)
                                    <select class="form-select form-select-sm item-transport" style="min-width:140px;">
                                        <option value="">— Select —</option>
                                        @foreach ($transporters as $tp)
                                            <option value="{{ $tp->id }}"
                                                data-phone="{{ $tp->phone_no }}"
                                                {{ (int) $item->transport_id === (int) $tp->id ? 'selected' : '' }}>
                                                {{ $tp->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <div class="wr-readonly-value">{{ $item->transporter?->name ?? '—' }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($canEdit && ! $locked)
                                    <select class="form-select form-select-sm item-truck" style="min-width:130px;"
                                        {{ $item->transport_id ? '' : 'disabled' }}
                                        data-current="{{ $item->truck_number }}">
                                        <option value="">— Select —</option>
                                        @if ($item->truck_number)
                                            <option value="{{ $item->truck_number }}" selected>{{ $item->truck_number }}</option>
                                        @endif
                                    </select>
                                @else
                                    <div class="wr-readonly-value">{{ $item->truck_number ?? '—' }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($canEdit && ! $locked)
                                    <input type="text" class="form-control form-control-sm item-contact"
                                        value="{{ $item->driver_contact }}" style="min-width:120px;">
                                @else
                                    <div class="wr-readonly-value">{{ $item->driver_contact ?? '—' }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($canEdit && ! $locked)
                                    <textarea class="form-control form-control-sm item-note" rows="2"
                                        style="min-width:160px;">{{ $item->note }}</textarea>
                                @else
                                    <div class="wr-readonly-value wr-readonly-value--note">{{ $item->note ?: '—' }}</div>
                                @endif
                            </td>
                            <td><div class="wr-readonly-value">{!! $item->statusBadge() !!}</div></td>
                            <td>
                                @if ($locked)
                                    <div class="wr-locked-action">
                                        <span class="badge bg-success-light text-success">
                                            <i class="ti ti-lock me-1"></i>Confirmed
                                        </span>
                                        @if ($item->dispatch_id)
                                            <a href="{{ route('dispatch.orderHistory', $item->order_id) }}"
                                                class="small text-decoration-none">View dispatch</a>
                                        @endif
                                    </div>
                                @else
                                    @if ($canEdit)
                                        <button type="button" class="btn btn-sm btn-outline-primary save-item-btn mb-1">
                                            Save
                                        </button>
                                    @endif
                                    @if ($canConfirm)
                                        <button type="button" class="btn btn-sm btn-success confirm-item-btn mb-1"
                                            data-item-id="{{ $item->id }}">
                                            Confirm
                                        </button>
                                    @endif
                                    @if ($canDelete)
                                        <form action="{{ route('weekly-report.items.destroy', [$report->id, $item->id]) }}"
                                            method="POST" class="d-inline delete-item-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr id="emptyRowsMessage">
                            <td colspan="12" class="text-center text-muted py-4">
                                No rows yet. Click <strong>Add Row</strong> to pick a pending order line.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer summary --}}
        <div class="weekly-report-footer mt-4 pt-3 border-top">
            <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-2">
                <h6 class="mb-0 text-muted text-uppercase small fw-semibold">Production summary</h6>
                @if ($canEdit)
                    <button type="button" class="btn btn-primary btn-sm" id="saveFooterBtn">
                        <i class="ti ti-device-floppy me-1"></i>Save summary
                    </button>
                @endif
            </div>
            <div class="row g-3">
                <div class="col-6 col-lg-3">
                    <div class="wr-footer-card h-100">
                        <label class="wr-footer-label" for="footerTotal">Total Quantity (bags)</label>
                        <input type="text" class="form-control form-control-lg wr-footer-input" id="footerTotal"
                            value="{{ number_format($totalBags, 2, '.', '') }}" readonly>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="wr-footer-card h-100">
                        <label class="wr-footer-label" for="footerAlreadyProduced">Already produced / ready stock</label>
                        <input type="number" class="form-control form-control-lg wr-footer-input" id="footerAlreadyProduced"
                            min="0" step="0.01" max="{{ number_format($totalBags, 2, '.', '') }}"
                            value="{{ number_format((float) $report->already_produced, 2, '.', '') }}"
                            {{ $canEdit ? '' : 'readonly' }}>
                        <div class="text-danger small mt-1" id="footerAlreadyProducedError"></div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="wr-footer-card h-100">
                        <label class="wr-footer-label" for="footerDifference">Difference</label>
                        <input type="text" class="form-control form-control-lg wr-footer-input" id="footerDifference"
                            value="{{ number_format($difference, 2, '.', '') }}" readonly>
                        <small class="text-muted">Total − ready stock</small>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="wr-footer-card h-100">
                        <label class="wr-footer-label" for="footerHours">Production hours (÷ {{ $bagsPerHour }})</label>
                        <input type="number" class="form-control form-control-lg wr-footer-input" id="footerHours"
                            min="0" step="0.01"
                            value="{{ number_format($hours, 2, '.', '') }}"
                            {{ $canEdit ? '' : 'readonly' }}>
                        <small class="text-muted">Auto from difference; admin can override</small>
                    </div>
                </div>
            </div>
        </div>
        <style>
            .wr-footer-card {
                background: #f8fafc;
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                padding: 14px 16px;
            }
            .wr-footer-label {
                display: block;
                font-size: 12px;
                font-weight: 600;
                color: #64748b;
                margin-bottom: 8px;
                line-height: 1.3;
                min-height: 32px;
            }
            .wr-footer-input[readonly] {
                background: #fff;
                font-weight: 600;
            }
        </style>
    </div>
</div>

{{-- Add Row Modal --}}
@can('edit-weekly-report')
<div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-plus me-2"></i>Add Row from Pending Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addItemForm" method="POST" action="{{ route('weekly-report.items.store', $report->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="col-form-label">Pending order line <span class="text-danger">*</span></label>
                            <select name="order_item_id" id="addOrderItemId" class="form-select" required>
                                <option value="">— Search / select —</option>
                            </select>
                            <small class="text-muted" id="addPendingHint"></small>
                            <span class="text-danger small d-block order_item_id_error"></span>
                        </div>
                        <div class="col-md-4">
                            <label class="col-form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="addQuantity" class="form-control" min="1" required>
                            <span class="text-danger small d-block quantity_error"></span>
                        </div>
                        <div class="col-md-4">
                            <label class="col-form-label">Transport</label>
                            <select name="transport_id" id="addTransport" class="form-select">
                                <option value="">— Select —</option>
                                @foreach ($transporters as $tp)
                                    <option value="{{ $tp->id }}" data-phone="{{ $tp->phone_no }}">{{ $tp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="col-form-label">Truck Number</label>
                            <select name="truck_number" id="addTruck" class="form-select" disabled>
                                <option value="">— Select transporter first —</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="col-form-label">Contact Number</label>
                            <input type="text" name="driver_contact" id="addContact" class="form-control">
                        </div>
                        <div class="col-md-8">
                            <label class="col-form-label">Note</label>
                            <textarea name="note" id="addNote" class="form-control" rows="2"
                                placeholder="Merged note / EXT note"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Row</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

{{-- Confirm Payment Modal --}}
@if ($canConfirm)
<div class="modal fade" id="confirmItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-check me-2"></i>Confirm &amp; Create Dispatch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="confirmItemForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small">
                        This will create a dispatch entry for the report date using the same sequential-dispatch rules.
                        The row will become read-only after confirmation.
                    </p>
                    <div class="row">
                        @include('dispatch_management.partials.status-field', [
                            'idPrefix' => 'wrConfirm',
                            'value' => '0',
                        ])
                    </div>
                    <div class="text-danger small" id="confirmFormError"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="confirmSubmitBtn">
                        <i class="ti ti-check me-1"></i>Confirm Dispatch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

@section('script')
@include('dispatch_management.partials.status-field-script')
<script>
(function ($) {
    var reportId = {{ $report->id }};
    var bagsPerHour = {{ $bagsPerHour }};
    var csrf = $('meta[name="csrf-token"]').attr('content');
    var trucksUrl = "{{ url('dispatch/transporter-trucks') }}";
    var pendingItemsUrl = "{{ route('weekly-report.pendingItems') }}";
    var alreadyProducedUrl = "{{ route('weekly-report.alreadyProduced', $report->id) }}";
    var reorderUrl = "{{ route('weekly-report.items.reorder', $report->id) }}";
    var itemUpdateBase = "{{ url('weekly-report/' . $report->id . '/items') }}";
    var confirmBase = itemUpdateBase;

    function kgPerBag() { return 60; }
    function kgPerTon() { return 1000; }

    function toBags(qty, unit) {
        qty = parseFloat(qty) || 0;
        if (unit === 'Ton') return qty * (kgPerTon() / kgPerBag());
        if (unit === 'KG') return qty / kgPerBag();
        return qty;
    }

    function recalcFooter(opts) {
        opts = opts || {};
        var total = 0;
        $('#weeklyReportItemsBody tr[data-item-id]').each(function () {
            var $row = $(this);
            var qty = parseFloat($row.find('.item-qty').val());
            if (isNaN(qty)) qty = parseFloat($row.data('qty')) || 0;
            total += toBags(qty, $row.data('unit'));
        });
        var produced = parseFloat($('#footerAlreadyProduced').val()) || 0;
        $('#footerAlreadyProducedError').text('');
        if (produced > total) {
            produced = total;
            $('#footerAlreadyProduced').val(total.toFixed(2));
            $('#footerAlreadyProducedError').text('Cannot exceed total quantity (' + total.toFixed(2) + ').');
        }
        $('#footerAlreadyProduced').attr('max', total.toFixed(2));
        var diff = Math.max(0, total - produced);
        var hours = opts.preserveHours
            ? (parseFloat($('#footerHours').val()) || 0)
            : (diff / bagsPerHour);
        $('#footerTotal').val(total.toFixed(2));
        $('#footerDifference').val(diff.toFixed(2));
        if (!opts.preserveHours) {
            $('#footerHours').val(hours.toFixed(2));
        }
    }

    function loadTrucks($select, transporterId, selectedTruck, $contactInput) {
        $select.prop('disabled', true).html('<option value="">Loading…</option>');
        if (!transporterId) {
            $select.html('<option value="">— Select transporter first —</option>');
            return;
        }
        $.get(trucksUrl + '/' + transporterId, function (res) {
            var html = '<option value="">— Select —</option>';
            (res.trucks || []).forEach(function (t) {
                var sel = selectedTruck && selectedTruck === t.truck_number ? ' selected' : '';
                html += '<option value="' + t.truck_number + '"' + sel + '>' + t.truck_number + '</option>';
            });
            $select.html(html).prop('disabled', false);
            if ($contactInput && res.phone && !$contactInput.val()) {
                $contactInput.val(res.phone);
            }
        }).fail(function () {
            $select.html('<option value="">Failed to load trucks</option>').prop('disabled', false);
        });
    }

    /* Init trucks for existing editable rows */
    $('#weeklyReportItemsBody tr[data-locked="0"]').each(function () {
        var $row = $(this);
        var tid = $row.find('.item-transport').val();
        var $truck = $row.find('.item-truck');
        if (tid) {
            loadTrucks($truck, tid, $truck.data('current'), $row.find('.item-contact'));
        }
    });

    $(document).on('change', '.item-transport', function () {
        var $row = $(this).closest('tr');
        var phone = $(this).find(':selected').data('phone') || '';
        $row.find('.item-contact').val(phone);
        loadTrucks($row.find('.item-truck'), $(this).val(), null, $row.find('.item-contact'));
    });

    $(document).on('input change', '.item-qty', recalcFooter);

    $('#openAddItemModal').on('click', function () {
        $('#addItemForm')[0].reset();
        $('#addOrderItemId').html('<option value="">Loading…</option>');
        $('#addTruck').prop('disabled', true).html('<option value="">— Select transporter first —</option>');
        $('.order_item_id_error, .quantity_error').text('');
        $.get(pendingItemsUrl, function (res) {
            var html = '<option value="">— Select pending order line —</option>';
            (res.results || []).forEach(function (r) {
                html += '<option value="' + r.order_item_id + '" data-pending="' + r.pending_qty + '" data-unit="' + (r.product_unit || '') + '">'
                    + r.label + '</option>';
            });
            $('#addOrderItemId').html(html);
        });
        new bootstrap.Modal(document.getElementById('addItemModal')).show();
    });

    $('#addOrderItemId').on('change', function () {
        var pending = $(this).find(':selected').data('pending');
        var unit = $(this).find(':selected').data('unit') || '';
        if (pending) {
            $('#addPendingHint').text('Remaining: ' + pending + (unit ? ' ' + unit : ''));
            $('#addQuantity').attr('max', pending).val(pending);
        } else {
            $('#addPendingHint').text('');
        }
    });

    $('#addTransport').on('change', function () {
        var phone = $(this).find(':selected').data('phone') || '';
        $('#addContact').val(phone);
        loadTrucks($('#addTruck'), $(this).val(), null, $('#addContact'));
    });

    $('#addItemForm').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        $('.order_item_id_error, .quantity_error').text('');
        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            success: function () {
                window.location.reload();
            },
            error: function (xhr) {
                var errors = (xhr.responseJSON && xhr.responseJSON.errors) || {};
                $.each(errors, function (key, msgs) {
                    $('.' + key + '_error').text(msgs[0]);
                });
                if (!Object.keys(errors).length) {
                    show_error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not add row.');
                }
            }
        });
    });

    $(document).on('click', '.save-item-btn', function () {
        var $row = $(this).closest('tr');
        var id = $row.data('item-id');
        var payload = {
            quantity: $row.find('.item-qty').val(),
            transport_id: $row.find('.item-transport').val() || null,
            truck_number: $row.find('.item-truck').val() || null,
            driver_contact: $row.find('.item-contact').val() || null,
            note: $row.find('.item-note').val() || null,
            sort_order: $row.find('.item-sort-order').val(),
            _token: csrf,
            _method: 'PUT'
        };
        $.ajax({
            url: itemUpdateBase + '/' + id,
            method: 'POST',
            data: payload,
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $row.data('qty', payload.quantity);
                if (res.total !== undefined) {
                    $('#footerTotal').val(parseFloat(res.total).toFixed(2));
                    $('#footerDifference').val(parseFloat(res.difference).toFixed(2));
                    $('#footerHours').val(parseFloat(res.hours).toFixed(2));
                } else {
                    recalcFooter();
                }
                if (typeof show_success === 'function') show_success(res.message || 'Saved');
            },
            error: function (xhr) {
                var msg = 'Could not save row.';
                var errors = xhr.responseJSON && xhr.responseJSON.errors;
                if (errors) msg = Object.values(errors)[0][0];
                show_error(msg);
            }
        });
    });

    $(document).on('change', '.item-sort-order', function () {
        var orders = [];
        $('#weeklyReportItemsBody tr[data-item-id]').each(function () {
            var $r = $(this);
            var so = $r.find('.item-sort-order').val();
            if (so === undefined) return;
            orders.push({ id: $r.data('item-id'), sort_order: parseInt(so, 10) || 0 });
        });
        $.ajax({
            url: reorderUrl,
            method: 'POST',
            data: { _token: csrf, _method: 'PUT', orders: orders },
            headers: { 'Accept': 'application/json' }
        });
    });

    $('#saveFooterBtn').on('click', function () {
        $('#footerAlreadyProducedError').text('');
        $.ajax({
            url: alreadyProducedUrl,
            method: 'POST',
            data: {
                _token: csrf,
                already_produced: $('#footerAlreadyProduced').val(),
                production_hours: $('#footerHours').val()
            },
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#footerTotal').val(parseFloat(res.total).toFixed(2));
                $('#footerDifference').val(parseFloat(res.difference).toFixed(2));
                $('#footerHours').val(parseFloat(res.hours).toFixed(2));
                if (typeof show_success === 'function') show_success('Updated');
            },
            error: function (xhr) {
                var errors = xhr.responseJSON && xhr.responseJSON.errors;
                if (errors && errors.already_produced) {
                    $('#footerAlreadyProducedError').text(errors.already_produced[0]);
                } else {
                    show_error('Could not update footer values.');
                }
            }
        });
    });

    $('#footerAlreadyProduced').on('input', function () {
        recalcFooter({ preserveHours: false });
    });

    $('#footerHours').on('input', function () {
        recalcFooter({ preserveHours: true });
    });

    $(document).on('submit', '.delete-item-form', function (e) {
        e.preventDefault();
        var form = this;
        Swal.fire({
            title: 'Remove row?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, remove',
            cancelButtonText: 'Cancel',
            customClass: { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-secondary' }
        }).then(function (result) {
            if (result.isConfirmed) form.submit();
        });
    });

    @if ($canConfirm)
    var confirmModal = new bootstrap.Modal(document.getElementById('confirmItemModal'));

    $(document).on('click', '.confirm-item-btn', function () {
        var itemId = $(this).data('item-id');
        var $row = $(this).closest('tr');
        // Ensure latest transport fields are saved first is nicer UX — require save if dirty optional.
        $('#confirmFormError').text('');
        $('#confirmItemForm').attr('action', confirmBase + '/' + itemId + '/confirm');
        if (window.dispatchPaymentStatusHelpers) {
            window.dispatchPaymentStatusHelpers.setFormStatus($('#confirmItemForm'), 0, '');
        }
        confirmModal.show();
    });

    $('#confirmItemForm').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        $('#confirmFormError').text('');
        $('#confirmSubmitBtn').prop('disabled', true);
        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            success: function () {
                window.location.reload();
            },
            error: function (xhr) {
                $('#confirmSubmitBtn').prop('disabled', false);
                var errors = xhr.responseJSON && xhr.responseJSON.errors;
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Confirm failed.';
                if (errors) msg = Object.values(errors)[0][0];
                $('#confirmFormError').text(msg);
            }
        });
    });
    @endif
})(jQuery);
</script>
@endsection
