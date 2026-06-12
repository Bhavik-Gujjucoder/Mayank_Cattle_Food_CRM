/**
 * Raw Material purchase order form — supplier broker filter, category → material rows, totals.
 */
(function ($, window) {
    'use strict';

    function initRmOrderForm(config) {
        config = config || {};
        var materials = config.materials || [];
        var categories = config.categories || [];
        var isEdit = !!config.isEdit;

        flatpickr('.flatpickr', {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd-m-Y',
            allowInput: true
        });

        $('#supplier_broker_id, #supplier_id, #price_basis').select2({ width: '100%' });

        function setSelectDisabled($el, disabled) {
            $el.prop('disabled', !!disabled);
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.trigger('change.select2');
            }
        }

        function supplierOptionsHtml(selectedId) {
            var brokerId = $('#supplier_broker_id').val();
            var html = '<option value="">-- Select Supplier --</option>';
            (config.suppliers || []).forEach(function (s) {
                if (brokerId && String(s.supplier_broker_id) !== String(brokerId)) {
                    return;
                }
                var sel = selectedId && String(selectedId) === String(s.id) ? ' selected' : '';
                html += '<option value="' + s.id + '" data-broker-id="' + (s.supplier_broker_id || '') + '"' + sel + '>' + s.name + '</option>';
            });
            return html;
        }

        function refreshSupplierSelect(preserve) {
            var brokerId = $('#supplier_broker_id').val();
            var current = preserve ? $('#supplier_id').val() : '';
            $('#supplier_id').html(supplierOptionsHtml(current));
            if (!$('#supplier_id').val()) {
                $('#supplier_id').val('').trigger('change.select2');
            }
            setSelectDisabled($('#supplier_id'), !brokerId);
        }

        $('#supplier_broker_id').on('change', function () {
            refreshSupplierSelect(false);
        });

        function categoryOptionsHtml(selectedId) {
            var html = '<option value="">-- Select Category --</option>';
            categories.forEach(function (c) {
                var sel = selectedId && String(selectedId) === String(c.id) ? ' selected' : '';
                html += '<option value="' + c.id + '"' + sel + '>' + c.name + '</option>';
            });
            return html;
        }

        function materialOptionsHtml(categoryId, selectedMaterialId) {
            var html = '<option value="">-- Select Material --</option>';
            materials.forEach(function (m) {
                if (categoryId && String(m.category_id) !== String(categoryId)) {
                    return;
                }
                var sel = selectedMaterialId && String(selectedMaterialId) === String(m.id) ? ' selected' : '';
                html += '<option value="' + m.id + '" data-price="' + m.price + '" data-category-id="' + m.category_id + '"' + sel + '>' +
                    m.name + ' (' + m.unit + ')</option>';
            });
            return html;
        }

        function initCategorySelect($el) {
            $el.select2({ width: '100%' });
        }

        function initMaterialSelect($el) {
            $el.select2({ width: '100%' });
        }

        function bindRow($row, rowData) {
            rowData = rowData || {};
            var $cat = $row.find('.category-select');
            var $mat = $row.find('.material-select');

            if ($cat.hasClass('select2-hidden-accessible')) {
                $cat.select2('destroy');
            }
            if ($mat.hasClass('select2-hidden-accessible')) {
                $mat.select2('destroy');
            }

            $cat.html(categoryOptionsHtml(rowData.category_id || ''));
            $mat.html(materialOptionsHtml(rowData.category_id || '', rowData.material_id || ''));

            if (rowData.qty) {
                $row.find('.qty-field').val(rowData.qty);
            }
            if (rowData.price) {
                $row.find('.price-field').val(rowData.price);
            }
            if (rowData.other_expense !== undefined && rowData.other_expense !== '') {
                $row.find('.other-expense-field').val(rowData.other_expense);
            }

            initCategorySelect($cat);
            initMaterialSelect($mat);
            setSelectDisabled($mat, !(rowData.category_id || $cat.val()));
        }

        function updateRowButtons() {
            var $rows = $('#itemTableBody .item-row');
            $rows.find('#addRowBtn').hide();
            $rows.last().find('#addRowBtn').show();
            if ($rows.length > 1) {
                $rows.find('.remove-row-btn').show();
            } else {
                $rows.find('.remove-row-btn').hide();
            }
        }

        function reindexRows() {
            $('#itemTableBody .item-row').each(function (i) {
                $(this).find('.row-index').text(i + 1);
            });
        }

        function rowMaterialTotal(qty, price) {
            return (parseFloat(qty) || 0) * 1000 * (parseFloat(price) || 0);
        }

        function calculateTotals() {
            var totalQty = 0;
            var grandTotal = 0;
            $('#itemTableBody .item-row').each(function () {
                var qty = parseFloat($(this).find('.qty-field').val()) || 0;
                var price = parseFloat($(this).find('.price-field').val()) || 0;
                var other = parseFloat($(this).find('.other-expense-field').val()) || 0;
                var lineTotal = rowMaterialTotal(qty, price);
                $(this).find('.total-field').val(lineTotal > 0 ? lineTotal.toFixed(2) : '');
                totalQty += qty;
                grandTotal += lineTotal + other;
            });
            $('#display_total_qty').text(totalQty);
            $('#display_grand_total').text(grandTotal.toFixed(2));
        }

        $('#itemTableBody .item-row').each(function () {
            bindRow($(this));
        });
        updateRowButtons();
        calculateTotals();
        refreshSupplierSelect(true);

        $(document).on('click', '#addRowBtn', function () {
            var tplNode = document.getElementById('itemRowTpl');
            var $nodes = $(tplNode.content.cloneNode(true).children);
            $('#itemTableBody').append($nodes);
            bindRow($('#itemTableBody .item-row:last'));
            updateRowButtons();
            reindexRows();
            $('#itemTableBody .item-row:last')[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });

        $(document).on('click', '.remove-row-btn', function () {
            var $row = $(this).closest('tr');
            $row.next('.item-row-error').remove();
            $row.find('.category-select, .material-select').select2('destroy');
            $row.remove();
            reindexRows();
            updateRowButtons();
            calculateTotals();
        });

        $(document).on('change', '.category-select', function () {
            var $row = $(this).closest('tr');
            var categoryId = $(this).val();
            var $mat = $row.find('.material-select');
            if ($mat.hasClass('select2-hidden-accessible')) {
                $mat.select2('destroy');
            }
            $mat.html(materialOptionsHtml(categoryId, ''));
            initMaterialSelect($mat);
            setSelectDisabled($mat, !categoryId);
            $(this).removeClass('is-invalid');
            calculateTotals();
        });

        $(document).on('change', '.material-select', function () {
            var $row = $(this).closest('tr');
            var price = parseFloat($(this).find(':selected').data('price')) || 0;
            if (price > 0 && (!isEdit || !$.trim($row.find('.price-field').val()))) {
                $row.find('.price-field').val(price.toFixed(2));
            }
            $(this).removeClass('is-invalid');
            calculateTotals();
        });

        $(document).on('input', '.qty-field, .price-field, .other-expense-field', function () {
            $(this).removeClass('is-invalid');
            var $row = $(this).closest('tr');
            if (!$row.find('.is-invalid').length) {
                $row.next('.item-row-error').hide();
            }
            calculateTotals();
        });

        function validateForm() {
            var isValid = true;
            $('.supplier_broker_id_error, .supplier_id_error, .order_date_error, .price_basis_error').text('');
            rmSetInvalid($('#supplier_broker_id'), false);
            rmSetInvalid($('#supplier_id'), false);
            rmSetInvalid($('#order_date'), false);
            rmSetInvalid($('#price_basis'), false);
            $('.item-row-error').hide();

            if (!$('#supplier_broker_id').val()) {
                $('.supplier_broker_id_error').text('Please select a supplier broker.');
                rmSetInvalid($('#supplier_broker_id'), true);
                isValid = false;
            }
            if (!$('#supplier_id').val()) {
                $('.supplier_id_error').text('Please select a supplier.');
                rmSetInvalid($('#supplier_id'), true);
                isValid = false;
            }
            if (!$.trim($('#order_date').val())) {
                $('.order_date_error').text('Please select an order date.');
                rmSetInvalid($('#order_date'), true);
                isValid = false;
            }
            if (!$('#price_basis').val()) {
                $('.price_basis_error').text('Please select price basis.');
                rmSetInvalid($('#price_basis'), true);
                isValid = false;
            }

            $('#itemTableBody .item-row').each(function () {
                var $row = $(this);
                rmSetInvalid($row.find('.category-select'), false);
                rmSetInvalid($row.find('.material-select'), false);
                rmSetInvalid($row.find('.qty-field'), false);
                rmSetInvalid($row.find('.price-field'), false);

                var categoryId = $row.find('.category-select').val();
                var materialId = $row.find('.material-select').val();
                var qty = $.trim($row.find('.qty-field').val());
                var price = $.trim($row.find('.price-field').val());

                if (!categoryId || !materialId || !qty || !price) {
                    if (!categoryId) rmSetInvalid($row.find('.category-select'), true);
                    if (!materialId) rmSetInvalid($row.find('.material-select'), true);
                    if (!qty) rmSetInvalid($row.find('.qty-field'), true);
                    if (!price) rmSetInvalid($row.find('.price-field'), true);
                    $row.next('.item-row-error').show();
                    isValid = false;
                }
            });

            return isValid;
        }

        function enableFieldsForSubmit() {
            setSelectDisabled($('#supplier_id'), false);
            $('.material-select').each(function () {
                setSelectDisabled($(this), false);
            });
        }

        function restoreFieldDisabledState() {
            setSelectDisabled($('#supplier_id'), !$('#supplier_broker_id').val());
            $('#itemTableBody .item-row').each(function () {
                var $row = $(this);
                setSelectDisabled($row.find('.material-select'), !$row.find('.category-select').val());
            });
        }

        $('#submitOrderBtn').on('click', function () {
            enableFieldsForSubmit();
            if (validateForm()) {
                $('#rmOrderForm').submit();
            } else {
                restoreFieldDisabledState();
                rmScrollToFirstInvalid('#rmOrderForm');
            }
        });

        if (config.oldRows && config.oldRows.length) {
            $('#itemTableBody').empty();
            config.oldRows.forEach(function (rowData, index) {
                var tplNode = document.getElementById('itemRowTpl');
                var $nodes = $(tplNode.content.cloneNode(true).children);
                $('#itemTableBody').append($nodes);
                var $row = $('#itemTableBody .item-row').last();
                $row.find('.row-index').text(index + 1);
                bindRow($row, rowData);
            });
            updateRowButtons();
            calculateTotals();
        }
    }

    window.initRmOrderForm = initRmOrderForm;
})(jQuery, window);
