<script>
    window.loadLateFeeDispatches = window.loadLateFeeDispatches || function ($select, orderId, selectedId, excludeId) {
        if (!$select || !$select.length) {
            return;
        }

        selectedId = selectedId || $select.attr('data-selected') || '';
        excludeId = excludeId || '';

        if (!orderId) {
            $select.prop('disabled', true).html('<option value="">— Select an order first —</option>');
            return;
        }

        $select.prop('disabled', true).html('<option value="">Loading…</option>');
        $.get(@json(route('dispatch.lateFeeDispatches')), {
            order_id: orderId,
            exclude_id: excludeId
        }, function (res) {
            var html = '<option value="">— Select an unpaid or partial dispatch —</option>';
            (res.results || []).forEach(function (row) {
                var selected = String(row.id) === String(selectedId) ? ' selected' : '';
                html += '<option value="' + row.id + '"' + selected + '>'
                    + $('<div>').text(row.label).html() + '</option>';
            });
            $select.html(html).prop('disabled', false);
            if (selectedId) {
                $select.val(String(selectedId));
            }
            $select.trigger('change');
        }).fail(function () {
            $select.html('<option value="">Failed to load dispatches</option>').prop('disabled', false);
        });
    };

    $(function () {
        function wrMoney(n) {
            return '₹ ' + (Number(n) || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function refreshDailyRowFinal($row) {
            var $cell = $row.find('.wr-final-amount');
            if (!$cell.length) {
                return;
            }
            var qty = parseFloat($row.find('.item-qty').val());
            if (isNaN(qty)) qty = parseFloat($row.data('qty')) || 0;
            var rate = parseFloat($row.find('.item-less-freight').val()) || 0;
            var price = parseFloat($row.data('unit-price')) || 0;
            var gross = qty * price;
            var less = qty * rate;
            var finalAmt = Math.max(0, gross - less);
            var $lateInput = $row.find('.item-late-fee');
            var late = $lateInput.length ? (parseFloat($lateInput.val()) || 0) : (parseFloat($row.data('late-fee')) || 0);
            var behalf = '';
            var $dispatch = $row.find('.item-late-fee-dispatch');
            if ($dispatch.val()) {
                behalf = $.trim($dispatch.find('option:selected').text());
            }
            var html = '<div>' + qty + ' × ' + wrMoney(price) + '</div>'
                + '<div>= ' + wrMoney(gross) + '</div>';
            if (rate > 0) html += '<div>− ' + wrMoney(less) + '</div>';
            if (late > 0) {
                html += '<div>+ ' + wrMoney(late) + ' late' + (behalf ? ' for ' + $('<div>').text(behalf).html() : '') + '</div>';
            }
            html += '<div class="wr-final-figure">' + wrMoney(finalAmt + late) + '</div>';
            $cell.html(html);
        }

        $('.item-late-fee-dispatch').each(function () {
            var $select = $(this);
            window.loadLateFeeDispatches(
                $select,
                $select.closest('tr').data('order-id'),
                $select.attr('data-selected')
            );
        });

        $(document).on('input change', '.item-qty, .item-less-freight, .item-late-fee, .item-late-fee-dispatch', function () {
            refreshDailyRowFinal($(this).closest('tr'));
        });
    });
</script>
