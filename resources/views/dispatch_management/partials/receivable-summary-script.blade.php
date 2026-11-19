<script>
window.dispatchReceivableHelpers = window.dispatchReceivableHelpers || {
    formatMoney: function (amount) {
        var n = parseFloat(amount);
        if (isNaN(n)) n = 0;
        return '₹ ' + n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    setFields: function (prefix, receivable) {
        receivable = receivable || {};
        var $section = $('#' + prefix + '_receivable_section');
        var baseAmount = parseFloat(receivable.base_amount) || 0;
        $section.data('baseAmount', baseAmount);
        $section.data('totalReceivable', parseFloat(receivable.total_receivable) || 0);

        $('#' + prefix + '_gross_amount').val(this.formatMoney(receivable.gross_amount));
        $('#' + prefix + '_less_freight').val(this.formatMoney(receivable.less_freight));
        $('#' + prefix + '_base_amount').val(this.formatMoney(receivable.base_amount));
        $('#' + prefix + '_accrued_late_fee').val(this.formatMoney(receivable.accrued_late_fee));
        var sourceLabel = receivable.late_fee_source_label || '';
        $('#' + prefix + '_late_fee_source_label').text(sourceLabel ? 'Saved as ' + sourceLabel : '');

        var $manual = $section.closest('form').find('.manual-late-fee-field');
        if ($manual.length && $manual.val() !== '') {
            var typedFee = parseFloat($manual.val());
            if (!isNaN(typedFee)) {
                $section.data('totalReceivable', Math.round((baseAmount + typedFee) * 100) / 100);
            }
        }

        $('#' + prefix + '_total_receivable').val(this.formatMoney($section.data('totalReceivable')));

        var overdue = parseInt(receivable.overdue_days, 10) || 0;
        var dueDays = parseInt(receivable.payment_due_days, 10) || 0;
        var overdueText = '—';
        if (overdue > 0) {
            overdueText = overdue + ' day(s) past due period (' + dueDays + ' days)';
        } else if (dueDays > 0) {
            overdueText = 'Within due period (' + dueDays + ' days)';
        }
        $('#' + prefix + '_overdue_days').val(overdueText);

        this.refreshBalanceDue(prefix);
    },
    refreshBalanceDue: function (prefix) {
        var $section = $('#' + prefix + '_receivable_section');
        if (!$section.length) {
            return;
        }

        var total = parseFloat($section.data('totalReceivable')) || 0;
        var $form = $section.closest('form');
        var status = $form.find('input[name="status"]:checked').val();
        var paid = parseFloat($form.find('.dispatch-partial-paid-field').val()) || 0;
        var balance = total;

        if (status === '1') {
            balance = 0;
        } else if (status === '2') {
            balance = Math.max(0, total - paid);
        }

        $('#' + prefix + '_balance_due').val(this.formatMoney(balance));
    },
    clearFields: function (prefix) {
        this.setFields(prefix, {});
    },
    loadForDispatch: function (prefix, dispatchId, popupUrlTemplate) {
        this.clearFields(prefix);
        if (!dispatchId) return;

        $.get(popupUrlTemplate.replace(':id', dispatchId))
            .done(function (res) {
                if (res && res.success && res.receivable) {
                    window.dispatchReceivableHelpers.setFields(prefix, res.receivable);
                }
            });
    }
};

$(document).on('input', '.manual-late-fee-field', function () {
    var $section = $(this).closest('form').find('[id$="_receivable_section"]');
    if (!$section.length) {
        return;
    }

    var prefix = $section.attr('id').replace('_receivable_section', '');
    var base = parseFloat($section.data('baseAmount')) || 0;
    var fee = parseFloat($(this).val()) || 0;
    $section.data('totalReceivable', Math.round((base + fee) * 100) / 100);
    $('#' + prefix + '_total_receivable').val(window.dispatchReceivableHelpers.formatMoney($section.data('totalReceivable')));
    window.dispatchReceivableHelpers.refreshBalanceDue(prefix);
});

$(document).on('input', '.dispatch-partial-paid-field', function () {
    var $section = $(this).closest('form').find('[id$="_receivable_section"]');
    if ($section.length) {
        var prefix = $section.attr('id').replace('_receivable_section', '');
        window.dispatchReceivableHelpers.refreshBalanceDue(prefix);
    }
});

$(document).on('change', '.dispatch-payment-status-radio', function () {
    var $section = $(this).closest('form').find('[id$="_receivable_section"]');
    if ($section.length) {
        var prefix = $section.attr('id').replace('_receivable_section', '');
        window.dispatchReceivableHelpers.refreshBalanceDue(prefix);
    }
});
</script>
