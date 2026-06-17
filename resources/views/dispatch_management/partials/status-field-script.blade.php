<script>
(function ($) {
    if ($.validator.methods.dispatchPartialAmount) {
        return;
    }

    function isPartialStatus($form) {
        return $form.find('input[name="status"]:checked').val() === '2';
    }

    function toggleDispatchPartialWrap($form) {
        var $radios = $form.find('.dispatch-payment-status-radio');
        if (!$radios.length) {
            return;
        }

        var prefix = $radios.first().data('prefix');
        if (!prefix) {
            return;
        }

        var isPartial = $form.find('input[name="status"]:checked').val() === '2';

        $('#' + prefix + '_partial_amount_wrap').toggle(isPartial);

        if (!isPartial) {
            $('#' + prefix + '_partial_paid_amount').val('');
        }
    }

    $.validator.addMethod('dispatchPartialAmount', function (value, element) {
        var $form = $(element).closest('form');
        if (!isPartialStatus($form)) {
            return true;
        }

        return $.trim(value) !== '' && !isNaN(value) && parseFloat(value) >= 0;
    }, 'Please enter the paid amount.');

    $(document).on('change', '.dispatch-payment-status-radio', function () {
        toggleDispatchPartialWrap($(this).closest('form'));
    });

    window.dispatchPaymentStatusHelpers = {
        statusKey: function (status) {
            var value = String(status);
            if (value === '1') return 'paid';
            if (value === '2') return 'partial';
            return 'unpaid';
        },
        setFormStatus: function ($form, status, partialPaidAmount) {
            var prefix = $form.find('.dispatch-payment-status-radio').first().data('prefix');
            if (!prefix) {
                return;
            }

            $form.find('input[name="status"]').prop('checked', false);
            $('#' + prefix + '_status_' + window.dispatchPaymentStatusHelpers.statusKey(status)).prop('checked', true);
            $('#' + prefix + '_partial_paid_amount').val(partialPaidAmount || '');
            toggleDispatchPartialWrap($form);
        },
    };
})(jQuery);
</script>
