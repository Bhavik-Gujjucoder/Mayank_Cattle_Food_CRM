@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('content')

<form action="{{ route('order.store') }}" id="orderForm" method="POST" enctype="multipart/form-data">
@csrf

{{-- ══════════════════════════════════════════════════════════════
     CARD 1 — ORDER DETAILS
═══════════════════════════════════════════════════════════════ --}}
<div class="card mb-3">
    <div class="card-body">

        {{-- ── Section: Order Info ──────────────────────────────── --}}
        <p class="form-section-title"><i class="ti ti-file-description me-1"></i>Order Information</p>
        <div class="row">

            {{-- Order ID --}}
            <div class="col-md-4 mb-3">
                <label class="col-form-label">Order ID</label>
                <input type="text" name="unique_order_id" id="unique_order_id"
                       value="{{ $order_id }}"
                       class="form-control fw-semibold" readonly>
            </div>

            {{-- Broker --}}

            <div class="col-md-4 mb-3">
                <label class="col-form-label">Broker <span class="text-danger">*</span></label>
                <select name="broker_id" id="broker_id" class="form-select search-select" {{ auth()->user()->hasRole('broker') ? 'disabled' : '' }} {{ auth()->user()->hasRole('broker') ? 'readonly' : '' }}>
                    <option value="">-- Select Broker --</option>
                    @foreach ($brokers as $broker)
                        {{-- <option value="{{ $broker->id }}" {{ old('broker_id') == $broker->id ? 'selected' : '' }}> --}}
                             <option value="{{ $broker->id }}"
                                {{
                                    auth()->user()->hasRole('broker')
                                        ? (auth()->id() == $broker->id ? 'selected' : '')
                                        : (old('broker_id') == $broker->id ? 'selected' : '')
                                }}>{{ $broker->name }}</option>
                    @endforeach
                </select>
                @can('add-broker')
                    @unless(auth()->user()->hasRole('broker'))
                        <div class="mt-1">
                            <a href="javascript:void(0)" id="addBrokerLink" class="text-primary fw-semibold small">
                                <i class="ti ti-plus me-1"></i>Add Broker
                            </a>
                        </div>
                    @endunless
                @endcan
                <span class="text-danger small broker_id_error">@error('broker_id'){{ $message }}@enderror</span>
            </div>


            {{-- Brand --}}
            <div class="col-md-4 mb-3">
                <label class="col-form-label">Brand <span class="text-danger">*</span></label>
                <select name="brand_id" id="brand_id" class="form-select search-select">
                    <option value="">-- Select Brand --</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
                            {{ $brand->name }}
                        </option>
                    @endforeach
                </select>
                @can('add-brand')
                    <div id="addBrandLinkWrap" class="mt-1" style="display:none;">
                        <a href="javascript:void(0)" id="addBrandLink" class="text-primary fw-semibold small">
                            <i class="ti ti-plus me-1"></i>Add Brand
                        </a>
                    </div>
                @endcan
                <span class="text-danger small brand_id_error">@error('brand_id'){{ $message }}@enderror</span>
            </div>

            {{-- Dealer --}}
            <div class="col-md-4 mb-3">
                <label class="col-form-label">Dealer <span class="text-danger">*</span></label>
                @if (!empty($locked_dealer))
                    <input type="hidden" name="dealer_id" value="{{ $locked_dealer->id }}">
                    <input type="text" class="form-control" readonly
                           value="{{ $locked_dealer->user?->name ?? $locked_dealer->firm_shop_name }}">
                    <small class="text-muted">Your dealer account is fixed for new orders.</small>
                @else
                    <select name="dealer_id" id="dealer_id" class="form-select search-select" disabled>
                        <option value="">-- Select Dealer --</option>
                    </select>
                    <small class="dealer-address-hint text-muted">Select broker &amp; brand first to load dealers.</small>
                    @can('add-dealer')
                        <div id="addDealerLinkWrap" class="mt-1" style="display:none;">
                            <a href="javascript:void(0)" id="addDealerLink" class="text-primary fw-semibold small">
                                <i class="ti ti-plus me-1"></i>Add Dealer
                            </a>
                        </div>
                    @endcan
                @endif
                <span class="text-danger small dealer_id_error">@error('dealer_id'){{ $message }}@enderror</span>
            </div>

            {{-- Order Date --}}
            <div class="col-md-4 mb-3">
                <label class="col-form-label">Order Date <span class="text-danger">*</span></label>
                <div class="icon-form">
                    <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                    <input type="date" name="order_date" id="order_date"
                           value="{{ old('order_date', now()->format('Y-m-d')) }}"
                           class="form-control flatpickr" placeholder="DD-MM-YYYY" autocomplete="off">
                </div>
                <span class="text-danger small order_date_error">@error('order_date'){{ $message }}@enderror</span>
            </div>

            {{-- Delivery Address (auto-populated from dealer) --}}
            <div class="col-md-4 mb-3">
                <label class="col-form-label">
                    Delivery Address <span class="text-danger">*</span>
                    <small class="text-muted fw-normal ms-1">(auto-fills on dealer selection)</small>
                </label>
                <textarea name="delivery_address" id="delivery_address" rows="2"
                          class="form-control" placeholder="Select a dealer to auto-populate delivery address">{{ old('delivery_address') }}</textarea>
                <span class="text-danger small delivery_address_error">@error('delivery_address'){{ $message }}@enderror</span>
            </div>

        </div>{{-- /row --}}
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     CARD 2 — PRODUCT ITEMS
═══════════════════════════════════════════════════════════════ --}}
<div class="card mb-3">
    <div class="card-body">

        <p class="form-section-title"><i class="ti ti-packages me-1"></i>Product Items</p>

        <div class="table-responsive">
            <table class="table table-bordered order-product-table" id="productTable">
                <thead>
                    <tr>
                        <th style="width:50px;">S.No</th>
                        <th style="min-width:200px;">Product Name <span class="text-danger">*</span></th>
                        <th style="width:100px;">QTY <span class="text-danger">*</span></th>
                        <th style="width:160px;">Unit Price <span class="text-danger">*</span></th>
                        <th style="width:140px;" class="d-none">Total Price</th>
                        <th style="width:100px;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="productTableBody">
                    {{-- Row template — first row --}}
                    <tr class="product-row">
                        <td class="row-index text-center fw-semibold">1</td>

                        <td>
                            <select name="product_id[]" class="form-select product-select" style="min-width:180px;">
                                <option value="">-- Select Product --</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}"
                                            data-gst="{{ $product->gst ?? 0 }}"
                                            data-unit="{{ $product->unit }}"
                                            data-brand="{{ $product->brand_id }}"
                                            data-price="{{ $product->price }}">
                                        {{ $product->name }}
                                        ({{ $product->unit }})
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        <td>
                            <input type="number" name="qty[]" class="form-control qty-field"
                                   placeholder="0" min="0" step="1">
                        </td>

                        <td>
                            <input type="number" name="price[]" class="form-control price-field"
                                   placeholder="0.00" min="0" step="0.01">
                            <small class="last-price-hint">
                                Last unit price: <span class="last-price-val">—</span>
                            </small>
                        </td>

                        <td class="d-none">
                            <input type="number" name="total[]" class="form-control total-field"
                                   placeholder="0.00" readonly>
                        </td>

                        <td class="text-center row-actions">
                            <button type="button" class="btn btn-primary btn-sm" id="addRowBtn">
                                <i class="ti ti-plus me-1"></i>Add New
                            </button>
                            <button type="button" class="btn-remove-row remove-row-btn" title="Remove row" style="display:none;">
                                <i class="ti ti-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="product-row-error" style="display:none;">
                        <td colspan="6" class="pt-0 pb-2 border-top-0">
                            <small class="text-danger">
                                <i class="ti ti-alert-circle me-1"></i>All fields are required.
                            </small>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="productRowError" class="text-danger small mt-1" style="display:none;">
            Please fill all required fields in each product row.
        </div>

    </div>
</div>

{{-- ── Product options template (used by JS to build fresh rows) ── --}}
<template id="productRowTpl">
    <tr class="product-row">
        <td class="row-index text-center fw-semibold"></td>
        <td>
            <select name="product_id[]" class="form-select product-select" style="min-width:180px;">
                <option value="">-- Select Product --</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}"
                            data-unit="{{ $product->unit }}"
                            data-brand="{{ $product->brand_id }}"
                            data-price="{{ $product->price }}">
                        {{ $product->name }} ({{ $product->unit }})
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="number" name="qty[]" class="form-control qty-field"
                   placeholder="0" min="0" step="1">
        </td>
        <td>
            <input type="number" name="price[]" class="form-control price-field"
                   placeholder="0.00" min="0" step="0.01">
            <small class="last-price-hint">
                Last unit price: <span class="last-price-val">—</span>
            </small>
        </td>
        <td class="d-none">
            <input type="number" name="total[]" class="form-control total-field"
                   placeholder="0.00" readonly>
        </td>
        <td class="text-center row-actions">
            <button type="button" class="btn btn-primary btn-sm" id="addRowBtn">
                <i class="ti ti-plus me-1"></i>Add New
            </button>
            <button type="button" class="btn-remove-row remove-row-btn" title="Remove row" style="display:none;">
                <i class="ti ti-trash"></i>
            </button>
        </td>
    </tr>
    <tr class="product-row-error" style="display:none;">
        <td colspan="6" class="pt-0 pb-2 border-top-0">
            <small class="text-danger">
                <i class="ti ti-alert-circle me-1"></i>All fields are required.
            </small>
        </td>
    </tr>
</template>

{{-- ══════════════════════════════════════════════════════════════
     CARD 3 — TOTALS + PAYMENT STATUS
═══════════════════════════════════════════════════════════════ --}}
<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-start">

            {{-- Payment Status (left) --}}
            <div class="col-md-6">
                <p class="form-section-title"><i class="ti ti-credit-card me-1"></i>Payment Status</p>

                <div class="payment-status-group mb-3">
                    <div class="form-check">
                        <input class="form-check-input payment-status-radio" type="radio"
                               name="payment_status" id="ps_unpaid" value="unpaid"
                               {{ old('payment_status', 'unpaid') == 'unpaid' ? 'checked' : '' }}>
                        <label class="form-check-label" for="ps_unpaid">Unpaid</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input payment-status-radio" type="radio"
                               name="payment_status" id="ps_paid" value="paid"
                               {{ old('payment_status') == 'paid' ? 'checked' : '' }}>
                        <label class="form-check-label" for="ps_paid">Paid</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input payment-status-radio" type="radio"
                               name="payment_status" id="ps_partial" value="partial"
                               {{ old('payment_status') == 'partial' ? 'checked' : '' }}>
                        <label class="form-check-label" for="ps_partial">Partial Payment</label>
                    </div>
                </div>

                {{-- Partial amount (shown only when "Partial Payment" selected) --}}
                <div class="partial-amount-wrap"
                     style="{{ old('payment_status') == 'partial' ? '' : 'display:none;' }}">
                    <label class="col-form-label">Paid Amount <span class="text-danger">*</span></label>
                    <div class="input-group" style="max-width:260px;">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="partial_paid_amount" id="partial_paid_amount"
                               value="{{ old('partial_paid_amount') }}"
                               class="form-control" placeholder="0.00" min="0" step="0.01">
                    </div>
                    <span class="text-danger small partial_paid_amount_error">
                        @error('partial_paid_amount'){{ $message }}@enderror
                    </span>
                </div>

            </div>

            {{-- Order Totals (right) — display hidden, calculations still run via JS --}}
            <div class="col-md-6">
                {{-- Section title hidden --}}
                <p class="form-section-title text-end d-none"><i class="ti ti-calculator me-1"></i>Order Summary</p>

                {{-- Totals display hidden — spans still updated by calculateTotals() --}}
                <div class="totals-box ms-auto d-none" style="max-width:360px;">
                    <div class="totals-row">
                        <span class="totals-label">Sub Total</span>
                        <span class="totals-value">
                            ₹ <span id="display_subtotal">0.00</span>
                        </span>
                    </div>
                    <div class="totals-row totals-grand d-flex justify-content-between align-items-center">
                        <span class="totals-label-grand">Grand Total</span>
                        <span class="totals-value-grand">
                            ₹ <span id="display_grand_total">0.00</span>
                        </span>
                    </div>
                </div>

                {{-- Hidden fields for submission — always submitted regardless of visibility --}}
                <input type="hidden" name="total_order_amount" id="total_order_amount">
                <input type="hidden" name="grand_total"        id="grand_total">
            </div>

        </div>{{-- /row --}}
    </div>
</div>

{{-- ── Action Buttons ───────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-end gap-2 mb-4">
    <a href="{{ route('order.index') }}" class="btn btn-light px-4">Cancel</a>
    <button type="button" class="btn btn-primary px-5" id="submitOrderBtn">
        {{-- <i class="ti ti-check me-1"></i> --}}
        Create Order
    </button>
</div>

</form>

@can('add-broker')
    @unless(auth()->user()->hasRole('broker'))
        <div class="modal fade" id="quickBrokerModal" tabindex="-1" aria-labelledby="quickBrokerModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="quickBrokerModalLabel">
                            <i class="ti ti-user-plus me-2"></i>Add Broker
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="quickBrokerModalBody">
                        <div class="text-center text-muted py-5">
                            <i class="ti ti-loader-2 ti-spin fs-3"></i>
                            <p class="mb-0 mt-2">Loading form…</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="quickBrokerSubmitBtn">
                            <i class="ti ti-check me-1"></i>Create Broker
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endunless
@endcan

@can('add-brand')
    <div class="modal fade" id="quickBrandModal" tabindex="-1" aria-labelledby="quickBrandModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickBrandModalLabel">
                        <i class="ti ti-tag-plus me-2"></i>Add Brand
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="quickBrandModalBody">
                    <div class="text-center text-muted py-5">
                        <i class="ti ti-loader-2 ti-spin fs-3"></i>
                        <p class="mb-0 mt-2">Loading form…</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="quickBrandSubmitBtn">
                        <i class="ti ti-check me-1"></i>Create Brand
                    </button>
                </div>
            </div>
        </div>
    </div>
@endcan

@can('add-dealer')
    @if (empty($locked_dealer))
        <div class="modal fade" id="quickDealerModal" tabindex="-1" aria-labelledby="quickDealerModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="quickDealerModalLabel">
                            <i class="ti ti-user-plus me-2"></i>Add Dealer
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="quickDealerModalBody">
                        <div class="text-center text-muted py-5">
                            <i class="ti ti-loader-2 ti-spin fs-3"></i>
                            <p class="mb-0 mt-2">Loading form…</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="quickDealerSubmitBtn">
                            <i class="ti ti-check me-1"></i>Create Dealer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endcan

@endsection
@section('script')
<script>
$(document).ready(function () {

    @if (!empty($locked_dealer))
    /* Dealer login: pre-fill broker, brand, and delivery address from profile */
    $('#broker_id').val('{{ $locked_dealer->broker_id }}').trigger('change');
    $('#brand_id').val('{{ $locked_dealer->brand_id }}').trigger('change');
    $('#delivery_address').val(@json($locked_dealer->firm_shop_address ?? ''));
    @endif

    /* ── Flatpickr on date fields ────────────────────────────── */
    flatpickr('.flatpickr', {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd-m-Y',
        allowInput: true
    });

    /* ── Select2 on dropdowns ────────────────────────────────── */
    $('#broker_id, #brand_id, #priority').select2({ width: '100%' });

    /* ── Show / hide quick-add links ─────────────────────────── */
    function updateAddBrandLink() {
        var brokerId = $('#broker_id').val();
        if (brokerId) {
            $('#addBrandLinkWrap').show();
        } else {
            $('#addBrandLinkWrap').hide();
        }
    }

    function updateAddDealerLink() {
        var brokerId = $('#broker_id').val();
        var brandId  = $('#brand_id').val();
        if (brokerId && brandId) {
            $('#addDealerLinkWrap').show();
        } else {
            $('#addDealerLinkWrap').hide();
        }
    }

    /* ── Dealer dropdown: load via AJAX when broker + brand chosen ─ */
    function loadDealers(selectDealerId) {
        var brokerId = $('#broker_id').val();
        var brandId  = $('#brand_id').val();

        updateAddBrandLink();
        updateAddDealerLink();

        /* Reset dealer field and address */
        $('#dealer_id').val('').prop('disabled', true);
        $('#delivery_address').val('');

        if (!brokerId || !brandId) {
            $('.dealer-address-hint').text('Select broker & brand first to load dealers.');
            return;
        }

        $('.dealer-address-hint').text('Loading dealers…');

        $.get('{{ route('get.dealers') }}', { broker_id: brokerId, brand_id: brandId }, function (dealers) {
            var $select = $('#dealer_id');
            $select.find('option:not(:first)').remove();

            if (dealers.length === 0) {
                $('.dealer-address-hint').text('No dealers found for the selected broker & brand.');
                return;
            }

            $.each(dealers, function (i, dealer) {
                $select.append(
                    $('<option>', {
                        value: dealer.id,
                        text:  dealer.name + (dealer.firm_shop_name ? ' — ' + dealer.firm_shop_name : ''),
                    }).data('address', dealer.firm_shop_address)
                );
            });

            $select.prop('disabled', false);
            $('.dealer-address-hint').text('Select a dealer to auto-fill delivery address.');

            if (selectDealerId) {
                $select.val(String(selectDealerId)).trigger('change');
            }
        }).fail(function () {
            $('.dealer-address-hint').text('Failed to load dealers. Please try again.');
        });
    }

    function clearQuickDealerErrors() {
        $('#quickDealerForm .is-invalid').removeClass('is-invalid');
        $('#quickDealerForm .qd-field-error').text('');
    }

    function showQuickDealerErrors(errors) {
        clearQuickDealerErrors();
        $.each(errors, function (field, messages) {
            var $field = $('#quickDealerForm [name="' + field + '"]');
            $field.addClass('is-invalid');
            $('#quickDealerForm .qd-field-error[data-field="' + field + '"]').text(messages[0]);
        });
    }

    function initQuickDealerForm() {
        var $form = $('#quickDealerForm');
        if (! $form.length) return;

        $form.find('.qd-profile-input').off('change.qd').on('change.qd', function (event) {
            var file = event.target.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#qd_profilePreview').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        });

        $('#qd_stateDropdown').off('change.qd').on('change.qd', function () {
            var stateID = $(this).val();
            var $city   = $('#qd_cityDropdown');
            $city.html('<option value="">Loading...</option>');
            if (!stateID) {
                $city.html('<option value="">Select city</option>');
                return;
            }
            $.ajax({
                url: "{{ route('get.cities') }}",
                type: 'POST',
                data: { state_id: stateID, _token: "{{ csrf_token() }}" },
                success: function (data) {
                    $city.empty().append('<option value="">Select city</option>');
                    $.each(data, function (key, city) {
                        $city.append('<option value="' + city.id + '">' + city.city_name + '</option>');
                    });
                },
                error: function () {
                    $city.html('<option value="">Failed to load cities</option>');
                }
            });
        });

        $('#quickDealerModal').off('click.qd', '.qd-toggle-pw').on('click.qd', '.qd-toggle-pw', function () {
            var $input = $(this).siblings('input');
            var $icon  = $(this).find('i');
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $icon.removeClass('ti-eye-off').addClass('ti-eye');
            } else {
                $input.attr('type', 'password');
                $icon.removeClass('ti-eye').addClass('ti-eye-off');
            }
        });
    }

    function clearQuickBrokerErrors() {
        $('#quickBrokerForm .is-invalid').removeClass('is-invalid');
        $('#quickBrokerForm .qb-field-error').text('');
    }

    function showQuickBrokerErrors(errors) {
        clearQuickBrokerErrors();
        $.each(errors, function (field, messages) {
            $('#quickBrokerForm [name="' + field + '"]').addClass('is-invalid');
            $('#quickBrokerForm .qb-field-error[data-field="' + field + '"]').text(messages[0]);
        });
    }

    function initQuickBrokerForm() {
        var $form = $('#quickBrokerForm');
        if (! $form.length) return;

        $form.find('.qb-profile-input').off('change.qb').on('change.qb', function (event) {
            var file = event.target.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#qb_profilePreview').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        });

        $('#quickBrokerModal').off('click.qb', '.qb-toggle-pw').on('click.qb', '.qb-toggle-pw', function () {
            var $input = $(this).siblings('input');
            var $icon  = $(this).find('i');
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $icon.removeClass('ti-eye-off').addClass('ti-eye');
            } else {
                $input.attr('type', 'password');
                $icon.removeClass('ti-eye').addClass('ti-eye-off');
            }
        });
    }

    function clearQuickBrandErrors() {
        $('#quickBrandForm .is-invalid').removeClass('is-invalid');
        $('#quickBrandForm .qb-brand-field-error').text('');
    }

    function showQuickBrandErrors(errors) {
        clearQuickBrandErrors();
        $.each(errors, function (field, messages) {
            $('#quickBrandForm [name="' + field + '"]').addClass('is-invalid');
            $('#quickBrandForm .qb-brand-field-error[data-field="' + field + '"]').text(messages[0]);
        });
    }

    var quickBrokerModalEl = document.getElementById('quickBrokerModal');
  @can('add-broker')
    @unless(auth()->user()->hasRole('broker'))
    if (quickBrokerModalEl) {
        var quickBrokerModal = new bootstrap.Modal(quickBrokerModalEl);

        $('#addBrokerLink').on('click', function () {
            $('#quickBrokerModalBody').html(
                '<div class="text-center text-muted py-5">' +
                '<i class="ti ti-loader-2 ti-spin fs-3"></i>' +
                '<p class="mb-0 mt-2">Loading form…</p></div>'
            );
            quickBrokerModal.show();

            $.get("{{ route('users.broker.quickCreateForm') }}")
                .done(function (html) {
                    $('#quickBrokerModalBody').html(html);
                    initQuickBrokerForm();
                })
                .fail(function () {
                    $('#quickBrokerModalBody').html(
                        '<div class="alert alert-danger mb-0">Failed to load broker form. Please try again.</div>'
                    );
                });
        });

        $('#quickBrokerSubmitBtn').on('click', function () {
            var $form = $('#quickBrokerForm');
            if (! $form.length) return;

            var $btn = $(this);
            var formData = new FormData($form[0]);
            $btn.prop('disabled', true);

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function (res) {
                    quickBrokerModal.hide();
                    show_success(res.message || 'Broker created successfully.');

                    var broker = res.broker;
                    if (broker && ! $('#broker_id option[value="' + broker.id + '"]').length) {
                        $('#broker_id').append(
                            $('<option>', { value: broker.id, text: broker.name })
                        );
                    }
                    $('#broker_id').val(String(broker.id)).trigger('change');
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        showQuickBrokerErrors(xhr.responseJSON.errors);
                        show_error('Please correct the highlighted fields.');
                    } else {
                        show_error('Failed to create broker. Please try again.');
                    }
                },
                complete: function () {
                    $btn.prop('disabled', false);
                }
            });
        });
    }
    @endunless
  @endcan

    var quickBrandModalEl = document.getElementById('quickBrandModal');
  @can('add-brand')
    if (quickBrandModalEl) {
        var quickBrandModal = new bootstrap.Modal(quickBrandModalEl);

        $('#addBrandLink').on('click', function () {
            if (! $('#broker_id').val()) return;

            $('#quickBrandModalBody').html(
                '<div class="text-center text-muted py-5">' +
                '<i class="ti ti-loader-2 ti-spin fs-3"></i>' +
                '<p class="mb-0 mt-2">Loading form…</p></div>'
            );
            quickBrandModal.show();

            $.get("{{ route('brand.quickCreateForm') }}")
                .done(function (html) {
                    $('#quickBrandModalBody').html(html);
                })
                .fail(function () {
                    $('#quickBrandModalBody').html(
                        '<div class="alert alert-danger mb-0">Failed to load brand form. Please try again.</div>'
                    );
                });
        });

        $('#quickBrandSubmitBtn').on('click', function () {
            var $form = $('#quickBrandForm');
            if (! $form.length) return;

            var $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function (res) {
                    quickBrandModal.hide();
                    show_success(res.message || 'Brand created successfully.');

                    var brand = res.brand;
                    if (brand && ! $('#brand_id option[value="' + brand.id + '"]').length) {
                        $('#brand_id').append(
                            $('<option>', { value: brand.id, text: brand.name })
                        );
                    }
                    $('#brand_id').val(String(brand.id)).trigger('change');
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        showQuickBrandErrors(xhr.responseJSON.errors);
                        show_error('Please correct the highlighted fields.');
                    } else {
                        show_error('Failed to create brand. Please try again.');
                    }
                },
                complete: function () {
                    $btn.prop('disabled', false);
                }
            });
        });
    }
  @endcan

    var quickDealerModalEl = document.getElementById('quickDealerModal');
  @can('add-dealer')
    @if (empty($locked_dealer))
    if (quickDealerModalEl) {
        var quickDealerModal = new bootstrap.Modal(quickDealerModalEl);

        $('#addDealerLink').on('click', function () {
            var brokerId = $('#broker_id').val();
            var brandId  = $('#brand_id').val();
            if (!brokerId || !brandId) return;

            $('#quickDealerModalBody').html(
                '<div class="text-center text-muted py-5">' +
                '<i class="ti ti-loader-2 ti-spin fs-3"></i>' +
                '<p class="mb-0 mt-2">Loading form…</p></div>'
            );
            quickDealerModal.show();

            $.get("{{ route('dealer.quickCreateForm') }}", {
                broker_id: brokerId,
                brand_id: brandId
            }).done(function (html) {
                $('#quickDealerModalBody').html(html);
                initQuickDealerForm();
            }).fail(function (xhr) {
                var msg = xhr.status === 403
                    ? 'You are not allowed to add a dealer for this broker/brand.'
                    : 'Failed to load dealer form. Please try again.';
                $('#quickDealerModalBody').html('<div class="alert alert-danger mb-0">' + msg + '</div>');
            });
        });

        $('#quickDealerSubmitBtn').on('click', function () {
            var $form = $('#quickDealerForm');
            if (! $form.length) return;

            var $btn = $(this);
            var formData = new FormData($form[0]);
            $btn.prop('disabled', true);

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function (res) {
                    quickDealerModal.hide();
                    show_success(res.message || 'Dealer created successfully.');
                    loadDealers(res.dealer.id);
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        showQuickDealerErrors(xhr.responseJSON.errors);
                        show_error('Please correct the highlighted fields.');
                    } else {
                        show_error('Failed to create dealer. Please try again.');
                    }
                },
                complete: function () {
                    $btn.prop('disabled', false);
                }
            });
        });
    }
    @endif
  @endcan

    $('#broker_id').on('change', function () {
        updateAddBrandLink();
        loadDealers();
        filterProductsByBrand();
    });

    $('#brand_id').on('change', function () {
        updateAddDealerLink();
        loadDealers();
        filterProductsByBrand();
    });

    updateAddBrandLink();

    /* ── Auto-fill delivery address when dealer is selected ──── */
    $(document).on('change', '#dealer_id', function () {
        var address = $('option:selected', this).data('address') || '';
        $('#delivery_address').val(address);

        /* Refresh last unit price hint for every product row */
        var dealerId = $(this).val();
        $('#productTableBody .product-row').each(function () {
            var $row      = $(this);
            var productId = $row.find('.product-select').val();
            fetchLastPrice($row, productId, dealerId);
        });
    });

    /* ── Fetch and display the dealer's last unit price for a product ─
       Hits the AJAX endpoint and updates the hint span in the row.   */
    function fetchLastPrice($row, productId, dealerId) {
        var $hint = $row.find('.last-price-val');
        if (!productId || !dealerId) {
            $hint.text('—');
            return;
        }
        $.get('{{ route('order.lastItemPrice') }}', { product_id: productId, dealer_id: dealerId }, function (res) {
            $hint.text(res.price ? '₹ ' + res.price : '—');
        }).fail(function () {
            $hint.text('—');
        });
    }

    /* ── Filter product options by selected brand ───────────────
       Pass a specific $select to filter only that element (new row).
       Pass nothing to filter ALL rows (brand change).              */
    function filterProductsByBrand($target) {
        var brandId   = $('#brand_id').val();
        var $selects  = $target
            ? $target
            : $('#productTableBody .product-select');

        $selects.each(function () {
            var $select   = $(this);
            var currentVal = $select.val(); // save before hiding/showing

            $select.find('option').each(function () {
                var optBrand = $(this).data('brand');
                if (!$(this).val()) return; // always keep placeholder
                if (!brandId || String(optBrand) === String(brandId)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });

            /* Only reset selection when the chosen option is now hidden
               — never reset on a new (empty) row                       */
            if (currentVal && $select.find('option[value="' + currentVal + '"]:visible').length === 0) {
                $select.val('').trigger('change');
            }
        });
    }

    /* ── Sync button visibility after every add / remove ────── */
    /*   Add New  → visible only on the last row                 */
    /*   Remove   → visible on all rows when count > 1,          */
    /*              hidden when only 1 row remains               */
    function updateRowButtons() {
        var $rows = $('#productTableBody .product-row');
        var count = $rows.length;

        /* Hide Add New on every row, then show on last row only */
        $rows.find('#addRowBtn').hide();
        $rows.last().find('#addRowBtn').show();

        /* Remove: visible only when more than one row exists */
        if (count > 1) {
            $rows.find('.remove-row-btn').show();
        } else {
            $rows.find('.remove-row-btn').hide();
        }
    }

    /* ── Add new product row (built from <template>, never cloned) ─ */
    $(document).on('click', '#addRowBtn', function () {

        /* Clone the full template fragment — includes both the
           product-row <tr> and its paired product-row-error <tr> */
        var tplNode = document.getElementById('productRowTpl');
        var $nodes  = $(tplNode.content.cloneNode(true).children);

        $('#productTableBody').append($nodes);

        /* Show Remove on all rows now that we have 2+ */
        updateRowButtons();

        /* Renumber rows & filter products for the new row only */
        reindexRows();
        var $newRow = $('#productTableBody .product-row:last');
        filterProductsByBrand($newRow.find('.product-select'));

        /* Smooth-scroll so the new row is visible */
        $newRow[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });

    /* ── Remove product row ──────────────────────────────────── */
    $(document).on('click', '.remove-row-btn', function () {
        var $row = $(this).closest('tr');
        $row.next('.product-row-error').remove(); // remove paired error row
        $row.remove();
        reindexRows();
        updateRowButtons();
        calculateTotals();
    });

    function reindexRows() {
        $('#productTableBody .product-row').each(function (i) {
            $(this).find('.row-index').text(i + 1);
        });
    }

    /* ── Auto-fill Unit Price when product is selected ──────── */
    $(document).on('change', '.product-select', function () {
        var $row      = $(this).closest('tr');
        var $selected = $(this).find(':selected');
        var price     = parseFloat($selected.data('price')) || 0;

        /* Clear validation state for this field */
        $(this).removeClass('is-invalid');
        if (!$row.find('.is-invalid').length) {
            $row.next('.product-row-error').hide();
        }

        /* Fill Unit Price field */
        $row.find('.price-field').val(price > 0 ? price.toFixed(2) : '');

        /* Recalculate row Total Price */
        var qty = parseFloat($row.find('.qty-field').val()) || 0;
        $row.find('.total-field').val((qty * price).toFixed(2));

        /* Update Sub Total & Grand Total */
        calculateTotals();

        /* Show the dealer's last unit price for this product */
        fetchLastPrice($row, $(this).val(), $('#dealer_id').val());
    });

    /* ── Recalculate row total on qty / price change ─────────── */
    $(document).on('input', '.qty-field, .price-field', function () {
        var $row  = $(this).closest('tr');
        var qty   = parseFloat($row.find('.qty-field').val())   || 0;
        var price = parseFloat($row.find('.price-field').val()) || 0;
        $row.find('.total-field').val((qty * price).toFixed(2));
        calculateTotals();

        /* Clear validation state for this field */
        $(this).removeClass('is-invalid');
        if (!$row.find('.is-invalid').length) {
            $row.next('.product-row-error').hide();
        }
    });

    /* ── Total calculation ───────────────────────────────────── */
    function calculateTotals() {
        var subtotal = 0;
        $('#productTableBody .product-row').each(function () {
            var qty   = parseFloat($(this).find('.qty-field').val())   || 0;
            var price = parseFloat($(this).find('.price-field').val()) || 0;
            subtotal += qty * price;
        });

        /* Update display */
        $('#display_subtotal').text(subtotal.toFixed(2));
        $('#display_grand_total').text(subtotal.toFixed(2));

        /* Update hidden fields for form submission */
        $('#total_order_amount').val(subtotal.toFixed(2));
        $('#grand_total').val(subtotal.toFixed(2));
    }

    /* ── Initialise button state on page load ───────────────── */
    updateRowButtons();

    /* ── Payment status: show partial amount field ───────────── */
    $(document).on('change', '.payment-status-radio', function () {
        if ($(this).val() === 'partial') {
            $('.partial-amount-wrap').show();
        } else {
            $('.partial-amount-wrap').hide();
            $('#partial_paid_amount').val('');
        }
    });

    /* ── Initial render ──────────────────────────────────────── */
    calculateTotals();
    filterProductsByBrand();
    updateRowButtons();
    updateAddDealerLink();
    @if (!empty($locked_dealer))
    loadDealers();
    @endif

    /* ════════════════════════════════════════════════════════════
       FORM VALIDATION
    ════════════════════════════════════════════════════════════ */

    /* Clear all previous error states */
    function clearValidationErrors() {
        /* Error text spans */
        $('[class$="_error"]').text('');
        /* Invalid highlights */
        $('.is-invalid').removeClass('is-invalid');
        /* Product row error messages */
        $('.product-row-error').hide();
    }

    /* Show error under a named field */
    function showFieldError(fieldName, message) {
        $('.' + fieldName + '_error').text(message);
        $('[name="' + fieldName + '"]').addClass('is-invalid');
    }

    /* Run full validation; returns true if form is valid */
    function validateForm() {
        clearValidationErrors();
        var isValid = true;

        /* Broker */
        if (!$('#broker_id').val()) {
            showFieldError('broker_id', 'Please select a broker.');
            isValid = false;
        }

        /* Brand */
        if (!$('#brand_id').val()) {
            showFieldError('brand_id', 'Please select a brand.');
            isValid = false;
        }

        /* Dealer */
        if (!$('#dealer_id').val()) {
            showFieldError('dealer_id', 'Please select a dealer.');
            isValid = false;
        }

        /* Order Date */
        if (!$.trim($('#order_date').val())) {
            showFieldError('order_date', 'Please select an order date.');
            isValid = false;
        }

        /* Delivery Address */
        if (!$.trim($('#delivery_address').val())) {
            showFieldError('delivery_address', 'Delivery address is required.');
            isValid = false;
        }

        /* Product Rows — one common message per invalid row */
        $('#productTableBody .product-row').each(function () {
            var $row  = $(this);
            var pid   = $row.find('.product-select').val();
            var qty   = $.trim($row.find('.qty-field').val());
            var price = $.trim($row.find('.price-field').val());

            if (!pid || !qty || !price) {
                if (!pid)   $row.find('.product-select').addClass('is-invalid');
                if (!qty)   $row.find('.qty-field').addClass('is-invalid');
                if (!price) $row.find('.price-field').addClass('is-invalid');
                $row.next('.product-row-error').show();
                isValid = false;
            }
        });

        /* Partial paid amount (only when "Partial Payment" is selected) */
        if ($('input[name="payment_status"]:checked').val() === 'partial') {
            if (!$.trim($('#partial_paid_amount').val())) {
                showFieldError('partial_paid_amount', 'Please enter the paid amount.');
                isValid = false;
            }
        }

        return isValid;
    }

    /* Clear top-level field errors on user interaction */
    $(document).on('change', '#broker_id, #brand_id, #dealer_id', function () {
        var name = $(this).attr('name') || $(this).attr('id');
        $('.' + name + '_error').text('');
        $(this).removeClass('is-invalid');
    });
    $(document).on('input change', '#order_date, #delivery_address, #partial_paid_amount', function () {
        var name = $(this).attr('name') || $(this).attr('id');
        $('.' + name + '_error').text('');
        $(this).removeClass('is-invalid');
    });

    /* Submit button triggers validation then submits */
    $('#submitOrderBtn').on('click', function () {
        if (validateForm()) {
            $('#orderForm').submit();
        }
    });

});
</script>
@endsection
