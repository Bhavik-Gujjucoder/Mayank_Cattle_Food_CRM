@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('content')
    <form action="{{ route('order.update', $order->id) }}" id="orderForm" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- ══════════════════════════════════════════════════════════════
     CARD 1 — ORDER DETAILS
═══════════════════════════════════════════════════════════════ --}}
        <div class="card mb-3">
            <div class="card-body">

                <p class="form-section-title"><i class="ti ti-file-description me-1"></i>Order Information</p>
                <div class="row">

                    {{-- Order ID --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Order ID</label>
                        <input type="text" name="unique_order_id" id="unique_order_id"
                            value="{{ $order->unique_order_id }}" class="form-control fw-semibold" readonly>
                    </div>

                    {{-- Broker --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Broker <span class="text-danger">*</span></label>
                        <select name="broker_id" id="broker_id" class="form-select search-select"
                            {{ auth()->user()->hasRole('broker') ? 'disabled' : '' }}>
                            <option value="">-- Select Broker --</option>
                            @foreach ($brokers as $broker)
                                {{-- <option value="{{ $broker->id }}"
                            {{ $order->broker_id == $broker->id ? 'selected' : '' }}> --}}
                                <option value="{{ $broker->id }}"
                                    {{ auth()->user()->hasRole('broker')
                                        ? (auth()->id() == $broker->id
                                            ? 'selected'
                                            : '')
                                        : ($order->broker_id == $broker->id
                                            ? 'selected'
                                            : '') }}>
                                    {{ $broker->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="text-danger small broker_id_error">
                            @error('broker_id')
                                {{ $message }}
                            @enderror
                        </span>
                    </div>

                    {{-- Brand --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Brand <span class="text-danger">*</span></label>
                        <select name="brand_id" id="brand_id" class="form-select search-select">
                            <option value="">-- Select Brand --</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" {{ $order->brand_id == $brand->id ? 'selected' : '' }}>
                                    {{ $brand->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="text-danger small brand_id_error">
                            @error('brand_id')
                                {{ $message }}
                            @enderror
                        </span>
                    </div>

                    {{-- Dealer (pre-loaded for current broker + brand) --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Dealer <span class="text-danger">*</span></label>
                        <select name="dealer_id" id="dealer_id" class="form-select search-select">
                            <option value="">-- Select Dealer --</option>
                            @foreach ($dealers as $dealer)
                                <option value="{{ $dealer->id }}" data-address="{{ $dealer->firm_shop_address }}"
                                    {{ $order->dealer_id == $dealer->id ? 'selected' : '' }}>
                                    {{ $dealer->user?->name ?? $dealer->firm_shop_name }}
                                    @if ($dealer->firm_shop_name)
                                        — {{ $dealer->firm_shop_name }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="dealer-address-hint text-muted">
                            Change broker &amp; brand to reload dealers.
                        </small>
                        <span class="text-danger small dealer_id_error">
                            @error('dealer_id')
                                {{ $message }}
                            @enderror
                        </span>
                    </div>

                    {{-- Order Date --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Order Date <span class="text-danger">*</span></label>
                        <div class="icon-form">
                            <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                            <input type="text" name="order_date" id="order_date"
                                value="{{ $order->order_date?->format('Y-m-d') }}" class="form-control flatpickr"
                                placeholder="DD-MM-YYYY" autocomplete="off">
                        </div>
                        <span class="text-danger small order_date_error">
                            @error('order_date')
                                {{ $message }}
                            @enderror
                        </span>
                    </div>

                    {{-- Delivery Address --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">
                            Delivery Address <span class="text-danger">*</span>
                            <small class="text-muted fw-normal ms-1">(auto-fills on dealer selection)</small>
                        </label>
                        <textarea name="delivery_address" id="delivery_address" rows="2" class="form-control"
                            placeholder="Select a dealer to auto-populate delivery address">{{ $order->delivery_address }}</textarea>
                        <span class="text-danger small delivery_address_error">
                            @error('delivery_address')
                                {{ $message }}
                            @enderror
                        </span>
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
                                <th style="width:140px;">Total Price</th>
                                <th style="width:130px;" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="productTableBody">

                            @foreach ($order->items as $index => $item)
                                <tr class="product-row">
                                    <td class="row-index text-center fw-semibold">
                                        {{ $index + 1 }}
                                        {{-- Carries the existing order_item ID so the controller
                                 can update this record instead of deleting it --}}
                                        <input type="hidden" name="item_id[]" value="{{ $item->id }}">
                                    </td>

                                    <td>
                                        <select name="product_id[]" class="form-select product-select"
                                            style="min-width:180px;">
                                            <option value="">-- Select Product --</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}" data-unit="{{ $product->unit }}"
                                                    data-brand="{{ $product->brand_id }}"
                                                    data-price="{{ $product->price }}"
                                                    {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                                    {{ $product->name }} ({{ $product->unit }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>

                                    <td>
                                        <input type="number" name="qty[]" class="form-control qty-field"
                                            value="{{ $item->qty }}" placeholder="0" min="0" step="1">
                                    </td>

                                    <td>
                                        <input type="number" name="price[]" class="form-control price-field"
                                            value="{{ $item->unit_price }}" placeholder="0.00" min="0"
                                            step="0.01">
                                        <small class="last-price-hint">
                                            Last unit price: <span class="last-price-val">—</span>
                                        </small>
                                    </td>

                                    <td>
                                        <input type="number" name="total[]" class="form-control total-field"
                                            value="{{ $item->total_price }}" placeholder="0.00" readonly>
                                    </td>

                                    <td class="text-center row-actions">
                                        <button type="button" class="btn btn-primary btn-sm" id="addRowBtn">
                                            <i class="ti ti-plus me-1"></i>Add New
                                        </button>
                                        <button type="button" class="btn-remove-row remove-row-btn" title="Remove row"
                                            style="display:none;">
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
                            @endforeach

                        </tbody>
                    </table>
                </div>

                <div id="productRowError" class="text-danger small mt-1" style="display:none;">
                    Please fill all required fields in each product row.
                </div>

            </div>
        </div>

        {{-- ── Product row template (for JS-added rows) ── --}}
        <template id="productRowTpl">
            <tr class="product-row">
                <td class="row-index text-center fw-semibold">
                    {{-- Empty item_id = new row, will be inserted not updated --}}
                    <input type="hidden" name="item_id[]" value="">
                </td>
                <td>
                    <select name="product_id[]" class="form-select product-select" style="min-width:180px;">
                        <option value="">-- Select Product --</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" data-unit="{{ $product->unit }}"
                                data-brand="{{ $product->brand_id }}" data-price="{{ $product->price }}">
                                {{ $product->name }} ({{ $product->unit }})
                            </option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="number" name="qty[]" class="form-control qty-field" placeholder="0" min="0"
                        step="1">
                </td>
                <td>
                    <input type="number" name="price[]" class="form-control price-field" placeholder="0.00"
                        min="0" step="0.01">
                    <small class="last-price-hint">
                        Last unit price: <span class="last-price-val">—</span>
                    </small>
                </td>
                <td>
                    <input type="number" name="total[]" class="form-control total-field" placeholder="0.00" readonly>
                </td>
                <td class="text-center row-actions">
                    <button type="button" class="btn btn-primary btn-sm" id="addRowBtn">
                        <i class="ti ti-plus me-1"></i>Add New
                    </button>
                    <button type="button" class="btn-remove-row remove-row-btn" title="Remove row"
                        style="display:none;">
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

                    {{-- Payment Status --}}
                    <div class="col-md-6">
                        <p class="form-section-title"><i class="ti ti-credit-card me-1"></i>Payment Status</p>

                        <div class="payment-status-group mb-3">
                            <div class="form-check">
                                <input class="form-check-input payment-status-radio" type="radio" name="payment_status"
                                    id="ps_unpaid" value="unpaid"
                                    {{ $order->payment_status === 'unpaid' ? 'checked' : '' }}>
                                <label class="form-check-label" for="ps_unpaid">Unpaid</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input payment-status-radio" type="radio" name="payment_status"
                                    id="ps_paid" value="paid"
                                    {{ $order->payment_status === 'paid' ? 'checked' : '' }}>
                                <label class="form-check-label" for="ps_paid">Paid</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input payment-status-radio" type="radio" name="payment_status"
                                    id="ps_partial" value="partial"
                                    {{ $order->payment_status === 'partial' ? 'checked' : '' }}>
                                <label class="form-check-label" for="ps_partial">Partial Payment</label>
                            </div>
                        </div>

                        <div class="partial-amount-wrap"
                            style="{{ $order->payment_status === 'partial' ? '' : 'display:none;' }}">
                            <label class="col-form-label">Paid Amount <span class="text-danger">*</span></label>
                            <div class="input-group" style="max-width:260px;">
                                <span class="input-group-text">₹</span>
                                <input type="number" name="partial_paid_amount" id="partial_paid_amount"
                                    value="{{ $order->partial_paid_amount }}" class="form-control" placeholder="0.00"
                                    min="0" step="0.01">
                            </div>
                            <span class="text-danger small partial_paid_amount_error">
                                @error('partial_paid_amount')
                                    {{ $message }}
                                @enderror
                            </span>
                        </div>
                    </div>

                    {{-- Order Totals --}}
                    <div class="col-md-6">
                        <p class="form-section-title text-end"><i class="ti ti-calculator me-1"></i>Order Summary</p>

                        <div class="totals-box ms-auto" style="max-width:360px;">
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

                        <input type="hidden" name="total_order_amount" id="total_order_amount">
                        <input type="hidden" name="grand_total" id="grand_total">
                    </div>

                </div>{{-- /row --}}
            </div>
        </div>

        {{-- ── Action Buttons ───────────────────────────────────────── --}}
        <div class="d-flex align-items-center justify-content-end gap-2 mb-4">
            <a href="{{ route('order.index') }}" class="btn btn-light px-4">Cancel</a>
            <button type="button" class="btn btn-primary px-5" id="submitOrderBtn">
                {{-- <i class="ti ti-check me-1"></i> --}}
                Update Order
            </button>
        </div>

    </form>
@endsection
@section('script')
    <script>
        $(document).ready(function() {

            /* ── Flatpickr on date fields ────────────────────────────── */
            flatpickr('.flatpickr', {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd-m-Y',
                allowInput: true
            });

            /* ── Select2 on dropdowns ────────────────────────────────── */
            $('#broker_id, #brand_id').select2({
                width: '100%'
            });

            /* ── Dealer dropdown: reload via AJAX when broker/brand change ─ */
            function loadDealers() {
                var brokerId = $('#broker_id').val();
                var brandId = $('#brand_id').val();

                $('#dealer_id').val('').prop('disabled', true);
                $('#delivery_address').val('');

                if (!brokerId || !brandId) {
                    $('.dealer-address-hint').text('Select broker & brand first to load dealers.');
                    return;
                }

                $('.dealer-address-hint').text('Loading dealers…');

                $.get('{{ route('get.dealers') }}', {
                    broker_id: brokerId,
                    brand_id: brandId
                }, function(dealers) {
                    var $select = $('#dealer_id');
                    $select.find('option:not(:first)').remove();

                    if (dealers.length === 0) {
                        $('.dealer-address-hint').text('No dealers found for the selected broker & brand.');
                        return;
                    }

                    $.each(dealers, function(i, dealer) {
                        $select.append(
                            $('<option>', {
                                value: dealer.id,
                                text: dealer.name + (dealer.firm_shop_name ? ' — ' + dealer
                                    .firm_shop_name : ''),
                            }).data('address', dealer.firm_shop_address)
                        );
                    });

                    $select.prop('disabled', false);
                    $('.dealer-address-hint').text('Select a dealer to auto-fill delivery address.');
                }).fail(function() {
                    $('.dealer-address-hint').text('Failed to load dealers. Please try again.');
                });
            }

            $('#broker_id, #brand_id').on('change', function() {
                loadDealers();
                filterProductsByBrand(null, false); // non-silent: reset invalid selections
            });

            /* ── Auto-fill delivery address when dealer is selected ──── */
            $(document).on('change', '#dealer_id', function() {
                var address = $('option:selected', this).data('address') || '';
                if (address) $('#delivery_address').val(address);

                /* Refresh last unit price hint for every product row */
                var dealerId = $(this).val();
                $('#productTableBody .product-row').each(function() {
                    var $row = $(this);
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
                $.get('{{ route('order.lastItemPrice') }}', {
                    product_id: productId,
                    dealer_id: dealerId
                }, function(res) {
                    $hint.text(res.price ? '₹ ' + res.price : '—');
                }).fail(function() {
                    $hint.text('—');
                });
            }

            /* ── Filter product options by selected brand ─────────────────
               silent = true  → only show/hide options, never reset the value
                                (used on initial page load to preserve pre-filled data)
               silent = false → also reset + trigger change when selected option
                                is now hidden (used when user changes brand)        */
            function filterProductsByBrand($target, silent) {
                var brandId = $('#brand_id').val();
                var $selects = $target ? $target : $('#productTableBody .product-select');

                $selects.each(function() {
                    var $select = $(this);
                    var currentVal = $select.val();

                    $select.find('option').each(function() {
                        var optBrand = $(this).data('brand');
                        if (!$(this).val()) return;
                        if (!brandId || String(optBrand) === String(brandId)) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });

                    /* Only reset the selection when not in silent (initial-load) mode */
                    if (!silent && currentVal &&
                        $select.find('option[value="' + currentVal + '"]:visible').length === 0) {
                        $select.val('').trigger('change');
                    }
                });
            }

            /* ── Sync Add New / Remove button visibility ─────────────── */
            function updateRowButtons() {
                var $rows = $('#productTableBody .product-row');
                var count = $rows.length;

                $rows.find('#addRowBtn').hide();
                $rows.last().find('#addRowBtn').show();

                if (count > 1) {
                    $rows.find('.remove-row-btn').show();
                } else {
                    $rows.find('.remove-row-btn').hide();
                }
            }

            /* ── Add new product row ─────────────────────────────────── */
            $(document).on('click', '#addRowBtn', function() {
                var tplNode = document.getElementById('productRowTpl');
                var $nodes = $(tplNode.content.cloneNode(true).children);

                $('#productTableBody').append($nodes);
                updateRowButtons();
                reindexRows();

                var $newRow = $('#productTableBody .product-row:last');
                filterProductsByBrand($newRow.find('.product-select'), false);
                $newRow[0].scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            });

            /* ── Remove product row ──────────────────────────────────── */
            $(document).on('click', '.remove-row-btn', function() {
                var $row = $(this).closest('tr');
                $row.next('.product-row-error').remove();
                $row.remove();
                reindexRows();
                updateRowButtons();
                calculateTotals();
            });

            function reindexRows() {
                $('#productTableBody .product-row').each(function(i) {
                    $(this).find('.row-index').text(i + 1);
                });
            }

            /* ── Auto-fill Unit Price when product is selected ──────── */
            $(document).on('change', '.product-select', function() {
                var $row = $(this).closest('tr');
                var $selected = $(this).find(':selected');
                var price = parseFloat($selected.data('price')) || 0;

                $(this).removeClass('is-invalid');
                if (!$row.find('.is-invalid').length) {
                    $row.next('.product-row-error').hide();
                }

                $row.find('.price-field').val(price > 0 ? price.toFixed(2) : '');

                var qty = parseFloat($row.find('.qty-field').val()) || 0;
                $row.find('.total-field').val((qty * price).toFixed(2));

                calculateTotals();

                /* Show the dealer's last unit price for this product */
                fetchLastPrice($row, $(this).val(), $('#dealer_id').val());
            });

            /* ── Recalculate row total on qty / price change ─────────── */
            $(document).on('input', '.qty-field, .price-field', function() {
                var $row = $(this).closest('tr');
                var qty = parseFloat($row.find('.qty-field').val()) || 0;
                var price = parseFloat($row.find('.price-field').val()) || 0;
                $row.find('.total-field').val((qty * price).toFixed(2));
                calculateTotals();

                $(this).removeClass('is-invalid');
                if (!$row.find('.is-invalid').length) {
                    $row.next('.product-row-error').hide();
                }
            });

            /* ── Total calculation ───────────────────────────────────── */
            function calculateTotals() {
                var subtotal = 0;
                $('#productTableBody .product-row').each(function() {
                    var qty = parseFloat($(this).find('.qty-field').val()) || 0;
                    var price = parseFloat($(this).find('.price-field').val()) || 0;
                    subtotal += qty * price;
                });

                $('#display_subtotal').text(subtotal.toFixed(2));
                $('#display_grand_total').text(subtotal.toFixed(2));
                $('#total_order_amount').val(subtotal.toFixed(2));
                $('#grand_total').val(subtotal.toFixed(2));
            }

            /* ── Payment status: show / hide partial amount field ─────── */
            $(document).on('change', '.payment-status-radio', function() {
                if ($(this).val() === 'partial') {
                    $('.partial-amount-wrap').show();
                } else {
                    $('.partial-amount-wrap').hide();
                    $('#partial_paid_amount').val('');
                }
            });

            /* ════════════════════════════════════════════════════════════
               FORM VALIDATION
            ════════════════════════════════════════════════════════════ */

            function clearValidationErrors() {
                $('[class$="_error"]').text('');
                $('.is-invalid').removeClass('is-invalid');
                $('.product-row-error').hide();
            }

            function showFieldError(fieldName, message) {
                $('.' + fieldName + '_error').text(message);
                $('[name="' + fieldName + '"]').addClass('is-invalid');
            }

            function validateForm() {
                clearValidationErrors();
                var isValid = true;

                if (!$('#broker_id').val()) {
                    showFieldError('broker_id', 'Please select a broker.');
                    isValid = false;
                }
                if (!$('#brand_id').val()) {
                    showFieldError('brand_id', 'Please select a brand.');
                    isValid = false;
                }
                if (!$('#dealer_id').val()) {
                    showFieldError('dealer_id', 'Please select a dealer.');
                    isValid = false;
                }
                if (!$.trim($('#order_date').val())) {
                    showFieldError('order_date', 'Please select an order date.');
                    isValid = false;
                }
                if (!$.trim($('#delivery_address').val())) {
                    showFieldError('delivery_address', 'Delivery address is required.');
                    isValid = false;
                }

                $('#productTableBody .product-row').each(function() {
                    var $row = $(this);
                    var pid = $row.find('.product-select').val();
                    var qty = $.trim($row.find('.qty-field').val());
                    var price = $.trim($row.find('.price-field').val());

                    if (!pid || !qty || !price) {
                        if (!pid) $row.find('.product-select').addClass('is-invalid');
                        if (!qty) $row.find('.qty-field').addClass('is-invalid');
                        if (!price) $row.find('.price-field').addClass('is-invalid');
                        $row.next('.product-row-error').show();
                        isValid = false;
                    }
                });

                if ($('input[name="payment_status"]:checked').val() === 'partial') {
                    if (!$.trim($('#partial_paid_amount').val())) {
                        showFieldError('partial_paid_amount', 'Please enter the paid amount.');
                        isValid = false;
                    }
                }

                return isValid;
            }

            $(document).on('change', '#broker_id, #brand_id, #dealer_id', function() {
                var name = $(this).attr('name') || $(this).attr('id');
                $('.' + name + '_error').text('');
                $(this).removeClass('is-invalid');
            });
            $(document).on('input change', '#order_date, #delivery_address, #partial_paid_amount', function() {
                var name = $(this).attr('name') || $(this).attr('id');
                $('.' + name + '_error').text('');
                $(this).removeClass('is-invalid');
            });

            $('#submitOrderBtn').on('click', function() {
                if (validateForm()) {
                    $('#orderForm').submit();
                }
            });

            /* ── Initial render ─────────────────────────────────────────
               Order matters:
               1. Buttons  — no side-effects
               2. Filter   — silent=true so pre-filled selections & prices are
                              never reset or overwritten by the change handler
               3. Totals   — reads the now-stable qty/price fields and populates
                              Sub Total + Grand Total from existing order data
               4. Last prices — fetch hint for every pre-loaded product row    */
            updateRowButtons();
            filterProductsByBrand(null, true);
            calculateTotals();

            /* Fetch last unit price hints for all existing product rows */
            var initDealerId = $('#dealer_id').val();
            if (initDealerId) {
                $('#productTableBody .product-row').each(function() {
                    var $row = $(this);
                    var productId = $row.find('.product-select').val();
                    fetchLastPrice($row, productId, initDealerId);
                });
            }

        });
    </script>
@endsection
