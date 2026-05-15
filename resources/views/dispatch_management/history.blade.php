@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('content')

<div class="card">

    {{-- ══════════════════════════════════════════════════════════════
         HEADER — order reference + Add New Dispatch button
    ══════════════════════════════════════════════════════════════ --}}
    <div class="card-header dh-header">

        {{-- Left: icon box + two-line label --}}
        <div class="dh-header-left">
            <div class="dh-icon-box">
                <i class="ti ti-truck"></i>
            </div>
            <div class="dh-header-text">
                <span class="dh-label">Dispatch History For</span>
                <span class="dh-order-id">{{ $order->unique_order_id }}</span>
            </div>
        </div>

        {{-- Right: action button --}}
        @can('add-dispatch')
        <button type="button" class="btn dh-add-btn"
                data-bs-toggle="modal" data-bs-target="#addDispatchModal">
            <i class="ti ti-circle-plus me-1"></i>Add New Dispatch
        </button>
        @endcan

    </div>

    {{-- ══════════════════════════════════════════════════════════════
         PENDING DISPATCH SUMMARY
    ══════════════════════════════════════════════════════════════ --}}
    <div class="card-body py-3 border-bottom">
        <div class="pd-summary-grid">
            @foreach ($order->items as $item)
                @php
                    $totalQty      = (int) $item->qty;
                    $pendingQty    = $item->pendingQty();
                    $dispatchedQty = $totalQty - $pendingQty;
                    $pct           = $totalQty > 0 ? round(($dispatchedQty / $totalQty) * 100) : 0;
                @endphp
                <div class="pd-item-card">

                    {{-- Product Name --}}
                    <div class="pd-item-product">
                        <i class="ti ti-package me-1"></i>
                        {{ $item->product?->name ?? '—' }}
                    </div>

                    {{-- Three stat counters --}}
                    <div class="pd-item-stats">
                        <div class="pd-stat">
                            <span class="pd-stat-value {{ $pendingQty > 0 ? 'pd-val-pending' : 'pd-val-done' }}">
                                {{ $pendingQty }}
                            </span>
                            <span class="pd-stat-label">Pending</span>
                        </div>
                        <div class="pd-stat-divider"></div>
                        <div class="pd-stat">
                            <span class="pd-stat-value pd-val-dispatched">{{ $dispatchedQty }}</span>
                            <span class="pd-stat-label">Dispatched</span>
                        </div>
                        <div class="pd-stat-divider"></div>
                        <div class="pd-stat">
                            <span class="pd-stat-value pd-val-total">{{ $totalQty }}</span>
                            <span class="pd-stat-label">Total Order</span>
                        </div>
                    </div>

                    {{-- Progress bar --}}
                    <div class="pd-item-progress">
                        <div class="progress pd-progress-bar">
                            <div class="progress-bar {{ $pct >= 100 ? 'bg-success' : 'bg-primary' }}"
                                 role="progressbar"
                                 style="width:{{ $pct }}%"
                                 aria-valuenow="{{ $pct }}"
                                 aria-valuemin="0"
                                 aria-valuemax="100">
                            </div>
                        </div>
                        <span class="pd-progress-label">{{ $pct }}% Dispatched</span>
                    </div>

                </div>
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         FLASH MESSAGES
    ══════════════════════════════════════════════════════════════ --}}
    {{-- @if (session('success'))
    <div class="card-body pb-0">
        <div class="alert alert-success alert-dismissible fade show mb-0">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    @endif

    @if ($errors->any())
    <div class="card-body pb-0">
        <div class="alert alert-danger alert-dismissible fade show mb-0">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    @endif --}}

    {{-- ══════════════════════════════════════════════════════════════
         DISPATCH HISTORY TABLE
    ══════════════════════════════════════════════════════════════ --}}
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th class="dh-col-sr">Sr no</th>
                        <th>Product</th>
                        <th class="dh-col-bags">No of bags/ton</th>
                        <th class="dh-col-date">Dispatch date</th>
                        <th>Transport</th>
                        <th>Truck number</th>
                        <th>Driver contact</th>
                        <th class="text-center dh-col-action">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php $hasRows = false; @endphp
                    @foreach ($order->items as $itemIndex => $item)
                        @foreach ($item->dispatches as $dispatch)
                            @php $hasRows = true; @endphp
                            <tr>
                                <td class="text-center">{{ $itemIndex + 1 }}</td>
                                <td>{{ $item->product?->name ?? '—' }}</td>
                                <td>{{ $dispatch->no_of_bags }}</td>
                                <td>{{ $dispatch->dispatch_date?->format('d-m-Y') ?? '—' }}</td>
                                <td>{{ $dispatch->transporter?->name ?? '—' }}</td>
                                <td>{{ $dispatch->truck_number }}</td>
                                <td>{{ $dispatch->driver_contact }}</td>
                                <td class="text-center">
                                    <div class="dh-action-btns">

                                        @can('edit-dispatch')
                                        <button type="button"
                                                class="btn btn-sm btn-outline-warning edit-dispatch-btn"
                                                title="Edit"
                                                data-id="{{ $dispatch->id }}"
                                                data-no-of-bags="{{ $dispatch->no_of_bags }}"
                                                data-dispatch-date="{{ $dispatch->dispatch_date?->format('Y-m-d') }}"
                                                data-transport-id="{{ $dispatch->transport_id }}"
                                                data-truck-number="{{ $dispatch->truck_number }}"
                                                data-driver-contact="{{ $dispatch->driver_contact }}"
                                                data-product-name="{{ $item->product?->name ?? '' }}"
                                                data-effective-pending="{{ $item->pendingQty() + $dispatch->no_of_bags }}"
                                                data-update-url="{{ route('dispatch.update', $dispatch->id) }}">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                        @endcan

                                        @can('delete-dispatch')
                                        <form action="{{ route('dispatch.destroy', $dispatch->id) }}"
                                              method="POST"
                                              class="delete-dispatch-form d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger delete-dispatch-btn"
                                                    title="Delete">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                        @endcan

                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach

                    @if (! $hasRows)
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No dispatch entries found for this order.
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <a href="{{ route('order.index') }}" class="btn btn-light">
                <i class="ti ti-arrow-left me-1"></i>Back to Orders
            </a>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     ADD NEW DISPATCH — MODAL
══════════════════════════════════════════════════════════════ --}}
@can('add-dispatch')
<div class="modal fade" id="addDispatchModal" tabindex="-1"
     aria-labelledby="addDispatchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="addDispatchModalLabel">
                    <i class="ti ti-truck me-2"></i>Add New Dispatch
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            {{-- novalidate disables browser native popups; jQuery Validate handles everything --}}
            <form action="{{ route('dispatch.store') }}" method="POST"
                  id="dispatchForm" novalidate>
                @csrf
                <input type="hidden" name="order_id"   value="{{ $order->id }}">
                <input type="hidden" name="product_id" id="dispatchProductId">

                <div class="modal-body">
                    <div class="row">

                        {{-- ── Product / Order Item ──────────────────────── --}}
                        <div class="col-md-12 mb-3">
                            <label class="col-form-label">
                                Product <span class="text-danger">*</span>
                            </label>
                            <select name="order_item_id" id="dispatchOrderItemId"
                                    class="form-select">
                                <option value="">-- Select Product --</option>
                                @foreach ($order->items as $item)
                                    @php $pending = $item->pendingQty(); @endphp
                                    <option value="{{ $item->id }}"
                                            data-product-id="{{ $item->product_id }}"
                                            data-pending="{{ $pending }}"
                                            {{ $pending <= 0 ? 'disabled' : '' }}>
                                        {{ $item->product?->name }}
                                        — Ordered: {{ $item->qty }},&nbsp;Pending: {{ $pending }}
                                    </option>
                                @endforeach
                            </select>
                            {{-- jQuery Validate error target --}}
                            <span class="field-error" id="order_item_id-error"></span>
                            <small class="text-info fw-semibold d-block mt-1"
                                   id="dispatchPendingHint"></small>
                        </div>

                        {{-- ── No of Bags ────────────────────────────────── --}}
                        <div class="col-md-6 mb-3">
                            <label class="col-form-label">
                                No of Bags/Ton <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="no_of_bags" id="dispatchNoBags"
                                   class="form-control" placeholder="0" min="1">
                            <span class="field-error" id="no_of_bags-error"></span>
                        </div>

                        {{-- ── Dispatch Date ─────────────────────────────── --}}
                        <div class="col-md-6 mb-3">
                            <label class="col-form-label">
                                Dispatch Date <span class="text-danger">*</span>
                            </label>
                            <div class="icon-form">
                                <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                                <input type="text" name="dispatch_date" id="dispatchDate"
                                       class="form-control flatpickr"
                                       placeholder="DD-MM-YYYY" autocomplete="off">
                            </div>
                            <span class="field-error" id="dispatch_date-error"></span>
                        </div>

                        {{-- ── Transport (transporter dropdown) ─────────── --}}
                        <div class="col-md-4 mb-3">
                            <label class="col-form-label">
                                Transport <span class="text-danger">*</span>
                            </label>
                            <select name="transport_id" id="dispatchTransport"
                                    class="form-select">
                                <option value="">-- Select Transporter --</option>
                                @forelse ($transporters as $transporter)
                                    <option value="{{ $transporter->id }}"
                                        {{ old('transport_id') == $transporter->id ? 'selected' : '' }}>
                                        {{ $transporter->name }}
                                    </option>
                                @empty
                                    <option value="" disabled>No transporters found</option>
                                @endforelse
                            </select>
                            <span class="field-error" id="transport_id-error"></span>
                        </div>

                        {{-- ── Truck Number ──────────────────────────────── --}}
                        <div class="col-md-4 mb-3">
                            <label class="col-form-label">
                                Truck Number <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="truck_number" id="dispatchTruckNumber"
                                   class="form-control" placeholder="e.g. GJ 01 AB 1234"
                                   value="{{ old('truck_number') }}">
                            <span class="field-error" id="truck_number-error"></span>
                        </div>

                        {{-- ── Driver Contact ────────────────────────────── --}}
                        <div class="col-md-4 mb-3">
                            <label class="col-form-label">
                                Driver Contact <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="driver_contact" id="dispatchDriverContact"
                                   class="form-control" placeholder="Mobile number"
                                   value="{{ old('driver_contact') }}">
                            <span class="field-error" id="driver_contact-error"></span>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="saveDispatchBtn">
                        <i class="ti ti-check me-1"></i>Save Dispatch
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
@endcan

{{-- ══════════════════════════════════════════════════════════════
     EDIT DISPATCH — MODAL
══════════════════════════════════════════════════════════════ --}}
@can('edit-dispatch')
<div class="modal fade" id="editDispatchModal" tabindex="-1"
     aria-labelledby="editDispatchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="editDispatchModalLabel">
                    <i class="ti ti-edit me-2"></i>Edit Dispatch
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="editDispatchForm" method="POST" action="" novalidate>
                @csrf
                @method('PUT')

                <div class="modal-body">
                    <div class="row">

                        {{-- ── Product (read-only display) ──────────────── --}}
                        <div class="col-md-12 mb-3">
                            <label class="col-form-label">Product</label>
                            <div class="edit-product-display">
                                <i class="ti ti-package"></i>
                                <span id="editProductName">—</span>
                            </div>
                            <small class="text-info fw-semibold d-block mt-1"
                                   id="editPendingHint"></small>
                        </div>

                        {{-- ── No of Bags ────────────────────────────────── --}}
                        <div class="col-md-6 mb-3">
                            <label class="col-form-label">
                                No of Bags/Ton <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="no_of_bags" id="editNoBags"
                                   class="form-control" placeholder="0" min="1">
                            <span class="field-error" id="edit_no_of_bags-error"></span>
                        </div>

                        {{-- ── Dispatch Date ─────────────────────────────── --}}
                        <div class="col-md-6 mb-3">
                            <label class="col-form-label">
                                Dispatch Date <span class="text-danger">*</span>
                            </label>
                            <div class="icon-form">
                                <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                                <input type="text" name="dispatch_date" id="editDispatchDate"
                                       class="form-control flatpickr"
                                       placeholder="DD-MM-YYYY" autocomplete="off">
                            </div>
                            <span class="field-error" id="edit_dispatch_date-error"></span>
                        </div>

                        {{-- ── Transport ─────────────────────────────────── --}}
                        <div class="col-md-4 mb-3">
                            <label class="col-form-label">
                                Transport <span class="text-danger">*</span>
                            </label>
                            <select name="transport_id" id="editTransport" class="form-select">
                                <option value="">-- Select Transporter --</option>
                                @forelse ($transporters as $transporter)
                                    <option value="{{ $transporter->id }}">
                                        {{ $transporter->name }}
                                    </option>
                                @empty
                                    <option value="" disabled>No transporters found</option>
                                @endforelse
                            </select>
                            <span class="field-error" id="edit_transport_id-error"></span>
                        </div>

                        {{-- ── Truck Number ──────────────────────────────── --}}
                        <div class="col-md-4 mb-3">
                            <label class="col-form-label">
                                Truck Number <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="truck_number" id="editTruckNumber"
                                   class="form-control" placeholder="e.g. GJ 01 AB 1234">
                            <span class="field-error" id="edit_truck_number-error"></span>
                        </div>

                        {{-- ── Driver Contact ────────────────────────────── --}}
                        <div class="col-md-4 mb-3">
                            <label class="col-form-label">
                                Driver Contact <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="driver_contact" id="editDriverContact"
                                   class="form-control" placeholder="Mobile number">
                            <span class="field-error" id="edit_driver_contact-error"></span>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-warning" id="updateDispatchBtn">
                        <i class="ti ti-check me-1"></i>Update Dispatch
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
@endcan

@endsection
@section('script')

{{-- jQuery Validate plugin --}}
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>

<script>
$(document).ready(function () {

    /* ════════════════════════════════════════════════════════════
       ADD DISPATCH — flatpickr, product select, validate
    ════════════════════════════════════════════════════════════ */

    /* ── Flatpickr — Add form ──────────────────────────────────── */
    flatpickr('#dispatchDate', {
        dateFormat : 'Y-m-d',
        altInput   : true,
        altFormat  : 'd-m-Y',
        allowInput : true,
        onChange   : function () {
            $('#dispatch_date-error').text('');
            $('#dispatchDate').next('.flatpickr-input').removeClass('is-invalid');
        }
    });

    /* ── Product select: populate product_id + pending hint ─────── */
    $('#dispatchOrderItemId').on('change', function () {
        var $opt   = $(this).find(':selected');
        var pending = parseInt($opt.data('pending')) || 0;
        var prodId  = $opt.data('product-id') || '';
        $('#dispatchProductId').val(prodId);
        $('#dispatchPendingHint').text($opt.val() ? 'Available pending qty: ' + pending : '');
    });

    /* ── Custom rule: bags ≤ pending (Add form) ─────────────────── */
    $.validator.addMethod('maxPending', function (value) {
        var $opt = $('#dispatchOrderItemId').find(':selected');
        if (!$opt.val()) return true;
        return parseInt(value) <= (parseInt($opt.data('pending')) || 0);
    }, 'The entered quantity cannot exceed the pending quantity.');

    /* ── Validate — Add form ───────────────────────────────────── */
    $('#dispatchForm').validate({
        ignore: ':hidden:not(#dispatchDate)',
        rules: {
            order_item_id  : { required: true },
            no_of_bags     : { required: true, number: true, min: 1, maxPending: true },
            dispatch_date  : { required: true },
            transport_id   : { required: true },
            truck_number   : { required: true },
            driver_contact : { required: true },
        },
        messages: {
            order_item_id  : { required: 'Please select a product.' },
            no_of_bags     : {
                required : 'No of bags/ton is required.',
                number   : 'Please enter a valid number.',
                min      : 'Must be at least 1.',
            },
            dispatch_date  : { required: 'Please select a dispatch date.' },
            transport_id   : { required: 'Please select a transporter.' },
            truck_number   : { required: 'Truck number is required.' },
            driver_contact : { required: 'Driver contact is required.' },
        },
        errorElement : 'span',
        errorClass   : 'text-danger small d-block mt-1',
        errorPlacement: function (error, element) {
            var $target = $('#' + element.attr('name') + '-error');
            if ($target.length) {
                $target.html(error);
            } else if (element.attr('id') === 'dispatchDate') {
                error.appendTo('#dispatch_date-error');
            } else {
                error.insertAfter(element);
            }
        },
        highlight: function (element) {
            var $el = $(element);
            $el.attr('id') === 'dispatchDate'
                ? $el.next('.flatpickr-input').addClass('is-invalid')
                : $el.addClass('is-invalid');
        },
        unhighlight: function (element) {
            var $el = $(element);
            $el.attr('id') === 'dispatchDate'
                ? $el.next('.flatpickr-input').removeClass('is-invalid')
                : $el.removeClass('is-invalid');
        },
        submitHandler: function (form) { form.submit(); },
    });

    /* ── Re-open Add modal on server validation failure ─────────── */
    @if (!session('edit_dispatch_id') && $errors->any())
        (new bootstrap.Modal(document.getElementById('addDispatchModal'))).show();
    @endif


    /* ════════════════════════════════════════════════════════════
       EDIT DISPATCH — flatpickr, custom rule, validate, populate
    ════════════════════════════════════════════════════════════ */

    /* ── Flatpickr — Edit form ─────────────────────────────────── */
    var editDatePicker = flatpickr('#editDispatchDate', {
        dateFormat : 'Y-m-d',
        altInput   : true,
        altFormat  : 'd-m-Y',
        allowInput : true,
        onChange   : function () {
            $('#edit_dispatch_date-error').text('');
            $('#editDispatchDate').next('.flatpickr-input').removeClass('is-invalid');
        }
    });

    /* Tracks the maximum bags allowed for the dispatch being edited */
    var editEffectivePending = 0;

    /* ── Custom rule: bags ≤ effective pending (Edit form) ──────── */
    $.validator.addMethod('maxEditPending', function (value) {
        return parseInt(value) <= editEffectivePending;
    }, 'The entered quantity cannot exceed the pending quantity.');

    /* ── Validate — Edit form ──────────────────────────────────── */
    $('#editDispatchForm').validate({
        ignore: ':hidden:not(#editDispatchDate)',
        rules: {
            no_of_bags     : { required: true, number: true, min: 1, maxEditPending: true },
            dispatch_date  : { required: true },
            transport_id   : { required: true },
            truck_number   : { required: true },
            driver_contact : { required: true },
        },
        messages: {
            no_of_bags     : {
                required : 'No of bags/ton is required.',
                number   : 'Please enter a valid number.',
                min      : 'Must be at least 1.',
            },
            dispatch_date  : { required: 'Please select a dispatch date.' },
            transport_id   : { required: 'Please select a transporter.' },
            truck_number   : { required: 'Truck number is required.' },
            driver_contact : { required: 'Driver contact is required.' },
        },
        errorElement : 'span',
        errorClass   : 'text-danger small d-block mt-1',
        errorPlacement: function (error, element) {
            var $target = $('#edit_' + element.attr('name') + '-error');
            if ($target.length) {
                $target.html(error);
            } else if (element.attr('id') === 'editDispatchDate') {
                error.appendTo('#edit_dispatch_date-error');
            } else {
                error.insertAfter(element);
            }
        },
        highlight: function (element) {
            var $el = $(element);
            $el.attr('id') === 'editDispatchDate'
                ? $el.next('.flatpickr-input').addClass('is-invalid')
                : $el.addClass('is-invalid');
        },
        unhighlight: function (element) {
            var $el = $(element);
            $el.attr('id') === 'editDispatchDate'
                ? $el.next('.flatpickr-input').removeClass('is-invalid')
                : $el.removeClass('is-invalid');
        },
        submitHandler: function (form) { form.submit(); },
    });

    /* ── Edit button click — populate modal and open it ─────────── */
    $(document).on('click', '.edit-dispatch-btn', function () {
        var $btn = $(this);

        /* Set the effective pending for the custom rule */
        editEffectivePending = parseInt($btn.data('effective-pending')) || 0;

        /* Point the form at the correct update URL */
        $('#editDispatchForm').attr('action', $btn.data('update-url'));

        /* Fill every field */
        $('#editProductName').text($btn.data('product-name') || '—');
        $('#editNoBags').val($btn.data('no-of-bags'));
        $('#editTransport').val($btn.data('transport-id'));
        $('#editTruckNumber').val($btn.data('truck-number'));
        $('#editDriverContact').val($btn.data('driver-contact'));
        editDatePicker.setDate($btn.data('dispatch-date'), false);

        /* Pending hint */
        $('#editPendingHint').text('Maximum allowed: ' + editEffectivePending + ' bags/ton');

        /* Reset previous validation state */
        var editValidator = $('#editDispatchForm').validate();
        editValidator.resetForm();
        $('#editDispatchForm .is-invalid').removeClass('is-invalid');
        $('#editDispatchDate').next('.flatpickr-input').removeClass('is-invalid');
        $('#editDispatchForm .field-error').empty();

        (new bootstrap.Modal(document.getElementById('editDispatchModal'))).show();
    });

    /* ── Re-open Edit modal on server validation failure ─────────── */
    @if (session('edit_dispatch_id'))
        @php
            /* Find the dispatch + its order item from already-loaded data */
            $reopenId       = session('edit_dispatch_id');
            $reopenDispatch = null;
            $reopenItem     = null;
            foreach ($order->items as $_item) {
                foreach ($_item->dispatches as $_d) {
                    if ($_d->id == $reopenId) {
                        $reopenDispatch = $_d;
                        $reopenItem     = $_item;
                        break 2;
                    }
                }
            }
            if ($reopenDispatch && $reopenItem) {
                /* Sum every OTHER dispatch for this item (collection, no extra query) */
                $reopenOtherBags       = (int) $reopenItem->dispatches
                                                 ->where('id', '!=', $reopenDispatch->id)
                                                 ->sum('no_of_bags');
                $reopenEffectivePending = max(0, (int) $reopenItem->qty - $reopenOtherBags);
            }
        @endphp
        @if ($reopenDispatch && $reopenItem)
        (function () {
            editEffectivePending = {{ $reopenEffectivePending }};
            $('#editDispatchForm').attr('action', '{{ route('dispatch.update', $reopenDispatch->id) }}');
            $('#editProductName').text({{ json_encode($reopenItem->product?->name ?? '—') }});
            $('#editNoBags').val({{ json_encode(old('no_of_bags', $reopenDispatch->no_of_bags)) }});
            $('#editTransport').val({{ json_encode(old('transport_id', (string) $reopenDispatch->transport_id)) }});
            $('#editTruckNumber').val({{ json_encode(old('truck_number', $reopenDispatch->truck_number)) }});
            $('#editDriverContact').val({{ json_encode(old('driver_contact', $reopenDispatch->driver_contact)) }});
            editDatePicker.setDate({{ json_encode(old('dispatch_date', $reopenDispatch->dispatch_date?->format('Y-m-d'))) }}, false);
            $('#editPendingHint').text('Maximum allowed: ' + editEffectivePending + ' bags/ton');
            (new bootstrap.Modal(document.getElementById('editDispatchModal'))).show();
        })();
        @endif
    @endif


    /* ════════════════════════════════════════════════════════════
       DELETE DISPATCH — SweetAlert confirmation
    ════════════════════════════════════════════════════════════ */

    $(document).on('click', '.delete-dispatch-btn', function () {
        var $form = $(this).closest('.delete-dispatch-form');
        Swal.fire({
            title             : 'Are you sure?',
            text              : 'This dispatch entry will be deleted.',
            icon              : 'warning',
            showCancelButton  : true,
            confirmButtonText : 'Yes, delete it!',
            cancelButtonText  : 'Cancel',
            customClass       : {
                confirmButton : 'btn btn-primary',
                cancelButton  : 'btn btn-secondary',
            }
        }).then(function (result) {
            if (result.isConfirmed) $form.submit();
        });
    });

});
</script>

@endsection
