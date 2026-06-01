/**
 * Raw Material module — scroll to first validation error (forms).
 */
window.rmScrollToFirstInvalid = function (formSelector) {
    var $form = formSelector ? $(formSelector) : $('form').first();
    if (!$form.length || !$form.is('form')) {
        $form = $(formSelector || document).closest('form');
    }
    if (!$form.length) {
        return;
    }

    function scrollToElement($el) {
        if (!$el || !$el.length) {
            return;
        }
        var node = $el[0];
        if (node && typeof node.scrollIntoView === 'function') {
            node.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    var $invalid = $form.find('.is-invalid').first();
    if ($invalid.length) {
        var $scrollTarget = $invalid;
        if ($invalid.hasClass('select2-hidden-accessible')) {
            $scrollTarget = $invalid.next('.select2-container');
        } else if ($invalid.closest('.icon-form').length) {
            $scrollTarget = $invalid.closest('.icon-form');
            var $alt = $invalid.siblings('input.form-control');
            if ($alt.length) {
                $scrollTarget = $alt;
            }
        }
        scrollToElement($scrollTarget);
        setTimeout(function () {
            try {
                $invalid.trigger('focus');
            } catch (e) { /* select2 / readonly */ }
        }, 400);
        return;
    }

    var $itemErr = $form.find('.item-row-error:visible').first();
    if ($itemErr.length) {
        scrollToElement($itemErr);
        return;
    }

    $form.find('.text-danger.small').each(function () {
        if ($.trim($(this).text())) {
            var $field = $(this).closest('.mb-3, .col-md-3, .col-md-4, .col-md-6, td');
            scrollToElement($field.length ? $field : $(this));
            return false;
        }
    });
};

window.rmSetInvalid = function ($field, invalid) {
    if (!$field || !$field.length) {
        return;
    }
    $field.toggleClass('is-invalid', !!invalid);
    if ($field.hasClass('select2-hidden-accessible')) {
        $field.next('.select2-container').find('.select2-selection').toggleClass('is-invalid', !!invalid);
    }
};

$(function () {
    $(document).on('change input', '#rawMaterialForm input, #rawMaterialForm select', function () {
        rmSetInvalid($(this), false);
    });
    $(document).on('change input', '#receiveForm input, #receiveForm select', function () {
        rmSetInvalid($(this), false);
    });
    $(document).on('change input', '#rmOrderForm input, #rmOrderForm select', function () {
        rmSetInvalid($(this), false);
    });
});
