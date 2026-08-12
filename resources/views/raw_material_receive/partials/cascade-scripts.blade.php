@php
    $excludeReceiveId = $excludeReceiveId ?? null;
    $initialOrderId = $initialOrderId ?? '';
    $initialItemId = $initialItemId ?? '';
@endphp
<script>
window.RECEIVE_FORM_ORDERS = @json($receivableOrders);
window.RECEIVE_FORM_SUPPLIERS = @json($allSuppliers);

function initReceiveCascadeForm(options) {
    options = options || {};
    var requireStatus = !!options.requireStatus;
    var itemsUrlTemplate = "{{ route('raw-material.order.items', '__ORDER__') }}";
    var excludeReceiveId = @json($excludeReceiveId);
    var pendingQty = 0;
    var orderedRemaining = 0;

    $('#receive_supplier_broker_id, #receive_supplier_id, #raw_material_order_id, #raw_material_order_item_id').select2({ width: '100%' });

    function resetOrderItemSelect() {
        pendingQty = 0;
        orderedRemaining = 0;
        $('#pendingQtyVal').text('—');
        $('#extraQtyHint').addClass('d-none');
        var $itemSelect = $('#raw_material_order_item_id');
        $itemSelect.empty().append('<option value="">-- Select Order First --</option>');
        $itemSelect.prop('disabled', true).trigger('change.select2');
    }

    function resetOrderSelect(keepDisabled) {
        var $orderSelect = $('#raw_material_order_id');
        $orderSelect.empty().append('<option value="">-- Select Order --</option>');
        $orderSelect.prop('disabled', !!keepDisabled).trigger('change.select2');
        resetOrderItemSelect();
    }

    function populateSuppliers(brokerId, selectedSupplierId, selectedOrderId, selectedItemId) {
        var $supplierSelect = $('#receive_supplier_id');
        $supplierSelect.empty().append('<option value="">-- Select Supplier --</option>');

        if (!brokerId) {
            $supplierSelect.prop('disabled', true).trigger('change.select2');
            resetOrderSelect(true);
            return;
        }

        $.each(window.RECEIVE_FORM_SUPPLIERS || [], function (i, supplier) {
            if (String(supplier.supplier_broker_id) !== String(brokerId)) {
                return;
            }
            var $opt = $('<option>', { value: supplier.id, text: supplier.name });
            if (selectedSupplierId && String(selectedSupplierId) === String(supplier.id)) {
                $opt.prop('selected', true);
            }
            $supplierSelect.append($opt);
        });

        $supplierSelect.prop('disabled', false).trigger('change.select2');
        populateOrders($supplierSelect.val(), selectedOrderId || null, selectedItemId || null);
    }

    function populateOrders(supplierId, selectedOrderId, selectedItemId) {
        var $orderSelect = $('#raw_material_order_id');
        $orderSelect.empty().append('<option value="">-- Select Order --</option>');
        resetOrderItemSelect();

        if (!supplierId) {
            $orderSelect.prop('disabled', true).trigger('change.select2');
            return;
        }

        var matched = 0;
        $.each(window.RECEIVE_FORM_ORDERS || [], function (i, order) {
            if (String(order.supplier_id) !== String(supplierId)) {
                return;
            }
            matched++;
            var $opt = $('<option>', { value: order.id, text: order.label });
            if (selectedOrderId && String(selectedOrderId) === String(order.id)) {
                $opt.prop('selected', true);
            }
            $orderSelect.append($opt);
        });

        if (!matched) {
            $orderSelect.append('<option value="">No open orders for supplier</option>');
            $orderSelect.prop('disabled', true).trigger('change.select2');
            return;
        }

        $orderSelect.prop('disabled', false).trigger('change.select2');
        if (selectedOrderId && $orderSelect.val()) {
            loadOrderItems(selectedOrderId, selectedItemId || null);
        }
    }

    function loadOrderItems(orderId, selectedItemId) {
        resetOrderItemSelect();
        if (!orderId) return;

        var url = itemsUrlTemplate.replace('__ORDER__', orderId);
        if (excludeReceiveId) {
            url += (url.indexOf('?') === -1 ? '?' : '&') + 'exclude_receive_id=' + encodeURIComponent(excludeReceiveId);
        }

        $.get(url, function (items) {
            var $itemSelect = $('#raw_material_order_item_id');
            $itemSelect.empty().append('<option value="">-- Select Order Item --</option>');

            if (!items.length) {
                $itemSelect.append('<option value="">No open order items</option>');
                $itemSelect.prop('disabled', true).trigger('change.select2');
                return;
            }

            $.each(items, function (i, item) {
                var remaining = parseInt(item.ordered_remaining, 10) || 0;
                var $opt = $('<option>', {
                    value: item.id,
                    text: item.label
                }).data('pending', remaining).data('remaining', remaining);
                if (selectedItemId && String(selectedItemId) === String(item.id)) {
                    $opt.prop('selected', true);
                }
                $itemSelect.append($opt);
            });

            $itemSelect.prop('disabled', false).trigger('change.select2');
            if (selectedItemId) {
                updatePendingQty();
            }
        }).fail(function () {
            if (typeof show_error === 'function') {
                show_error('Failed to load order items.');
            }
        });
    }

    function updatePendingQty() {
        var $opt = $('#raw_material_order_item_id option:selected');
        var remaining = parseInt($opt.data('remaining'), 10) || 0;
        pendingQty = remaining;
        orderedRemaining = remaining;
        $('#pendingQtyVal').text($opt.val() ? remaining : '—');
        $('#qty').removeAttr('max');
        updateExtraHint();
    }

    function updateExtraHint() {
        var qty = parseInt($.trim($('#qty').val()), 10) || 0;
        var extra = Math.max(0, qty - orderedRemaining);
        if ($('#raw_material_order_item_id').val() && qty > 0 && extra > 0) {
            $('#extraQtyVal').text(extra);
            $('#extraQtyHint').removeClass('d-none');
        } else {
            $('#extraQtyHint').addClass('d-none');
        }
    }

    function clearFieldErrors() {
        $('.receive_supplier_broker_id_error, .receive_supplier_id_error, .raw_material_order_id_error, .raw_material_order_item_id_error, .qty_error, .freight_error, .received_date_error, .status_error').text('');
        rmSetInvalid($('#receive_supplier_broker_id'), false);
        rmSetInvalid($('#receive_supplier_id'), false);
        rmSetInvalid($('#raw_material_order_id'), false);
        rmSetInvalid($('#raw_material_order_item_id'), false);
        rmSetInvalid($('#qty'), false);
        rmSetInvalid($('#freight'), false);
        rmSetInvalid($('#received_date'), false);
    }

    $('#receive_supplier_broker_id').on('change', function () {
        $(this).removeClass('is-invalid');
        $('.receive_supplier_broker_id_error').text('');
        populateSuppliers($(this).val(), null, null, null);
    });

    $('#receive_supplier_id').on('change', function () {
        $(this).removeClass('is-invalid');
        $('.receive_supplier_id_error').text('');
        populateOrders($(this).val(), null, null);
    });

    $('#raw_material_order_id').on('change', function () {
        $(this).removeClass('is-invalid');
        $('.raw_material_order_id_error').text('');
        loadOrderItems($(this).val(), null);
    });

    $('#raw_material_order_item_id').on('change', function () {
        $(this).removeClass('is-invalid');
        $('.raw_material_order_item_id_error').text('');
        updatePendingQty();
    });

    $('#qty').on('input', function () {
        $(this).removeClass('is-invalid');
        $('.qty_error').text('');
        updateExtraHint();
    });

    $('#freight').on('input', function () {
        $(this).removeClass('is-invalid');
        $('.freight_error').text('');
    });

    $('#received_date').on('change input', function () {
        $(this).removeClass('is-invalid');
        $('.received_date_error').text('');
    });

    $('input[name="status"]').on('change', function () {
        $('.status_error').text('');
    });

    function validateForm() {
        clearFieldErrors();
        var isValid = true;

        if (!$('#receive_supplier_broker_id').val()) {
            $('.receive_supplier_broker_id_error').text('Please select a supplier broker.');
            rmSetInvalid($('#receive_supplier_broker_id'), true);
            isValid = false;
        }

        if ($('#receive_supplier_id').prop('disabled') || !$('#receive_supplier_id').val()) {
            $('.receive_supplier_id_error').text('Please select a supplier.');
            rmSetInvalid($('#receive_supplier_id'), true);
            isValid = false;
        }

        if ($('#raw_material_order_id').prop('disabled') || !$('#raw_material_order_id').val()) {
            $('.raw_material_order_id_error').text('Please select a purchase order.');
            rmSetInvalid($('#raw_material_order_id'), true);
            isValid = false;
        }

        if ($('#raw_material_order_item_id').prop('disabled') || !$('#raw_material_order_item_id').val()) {
            $('.raw_material_order_item_id_error').text('Please select an order item.');
            rmSetInvalid($('#raw_material_order_item_id'), true);
            isValid = false;
        }

        var qtyVal = $.trim($('#qty').val());
        if (!qtyVal) {
            $('.qty_error').text('Please enter quantity.');
            rmSetInvalid($('#qty'), true);
            isValid = false;
        } else if (parseInt(qtyVal, 10) < 1) {
            $('.qty_error').text('Quantity must be at least 1 ton.');
            rmSetInvalid($('#qty'), true);
            isValid = false;
        }

        var freightVal = $.trim($('#freight').val());
        if (freightVal !== '' && parseFloat(freightVal) < 0) {
            $('.freight_error').text('Freight cannot be negative.');
            rmSetInvalid($('#freight'), true);
            isValid = false;
        }

        if (!$.trim($('#received_date').val())) {
            $('.received_date_error').text('Please select received date.');
            rmSetInvalid($('#received_date'), true);
            isValid = false;
        }

        if (requireStatus && !$('input[name="status"]:checked').length) {
            $('.status_error').text('Please select status.');
            isValid = false;
        }

        return isValid;
    }

    $(options.submitBtn || '#submitReceiveBtn').on('click', function () {
        if (validateForm()) {
            $('#receiveForm').submit();
        } else {
            rmScrollToFirstInvalid('#receiveForm');
        }
    });

    // Initial cascade from current selections (edit / old input).
    var initialBroker = $('#receive_supplier_broker_id').val();
    var initialSupplier = $('#receive_supplier_id').val();
    var initialOrder = @json($initialOrderId);
    var initialItem = @json($initialItemId);

    if (initialBroker) {
        populateSuppliers(initialBroker, initialSupplier || null, initialOrder || null, initialItem || null);
    } else {
        resetOrderSelect(true);
    }
}
</script>
