@include('dispatch_management.partials.status-field-script')
<script>
(function ($) {
    var defaultBagsPerHour = {{ \App\Models\WeeklyReport::BAGS_PER_HOUR }};
    var csrf = $('meta[name="csrf-token"]').attr('content');
    var trucksUrl = "{{ url('dispatch/transporter-trucks') }}";
    var pendingItemsUrl = "{{ route('weekly-report.pendingItems') }}";
    var alreadyProducedUrlTemplate = "{{ url('weekly-report') }}/REPORT_ID/already-produced";
    var reorderUrlTemplate = "{{ url('weekly-report') }}/REPORT_ID/items/reorder";
    var itemUpdateBaseTemplate = "{{ url('weekly-report') }}/REPORT_ID/items";
    var confirmBaseTemplate = "{{ url('weekly-report') }}/REPORT_ID/items";

    function kgPerBag() { return 60; }
    function kgPerTon() { return 1000; }

    function toBags(qty, unit) {
        qty = parseFloat(qty) || 0;
        if (unit === 'Ton') return qty * (kgPerTon() / kgPerBag());
        if (unit === 'KG') return qty / kgPerBag();
        return qty;
    }

    function reportUrl(template, reportId) {
        return template.replace('REPORT_ID', reportId);
    }

    function getBagsPerHour($block) {
        var val = parseFloat($block.find('.footer-bags-per-hour').val());
        return (val > 0) ? val : defaultBagsPerHour;
    }

    function updateHoursLabel($block, rate) {
        var display = (Math.round(rate) === rate) ? rate : rate.toFixed(2);
        $block.find('.footer-hours-label').text('Production hours (÷ ' + display + ')');
    }

    function recalcFooter($block, opts) {
        opts = opts || {};
        var total = 0;
        $block.find('.weekly-report-items-body tr[data-item-id]').each(function () {
            var $row = $(this);
            var qty = parseFloat($row.find('.item-qty').val());
            if (isNaN(qty)) qty = parseFloat($row.data('qty')) || 0;
            total += toBags(qty, $row.data('unit'));
        });
        var $produced = $block.find('.footer-already-produced');
        var produced = parseFloat($produced.val()) || 0;
        $block.find('.footer-already-produced-error').text('');
        if (produced > total) {
            produced = total;
            $produced.val(total.toFixed(2));
            $block.find('.footer-already-produced-error').text('Cannot exceed total quantity (' + total.toFixed(2) + ').');
        }
        $produced.attr('max', total.toFixed(2));
        var diff = Math.max(0, total - produced);
        var bagsPerHour = getBagsPerHour($block);
        updateHoursLabel($block, bagsPerHour);
        var hours = opts.preserveHours
            ? (parseFloat($block.find('.footer-hours').val()) || 0)
            : (diff / bagsPerHour);
        $block.find('.footer-total').val(total.toFixed(2));
        $block.find('.footer-difference').val(diff.toFixed(2));
        if (!opts.preserveHours) {
            $block.find('.footer-hours').val(hours.toFixed(2));
        }
    }

    function loadTrucks($select, transporterId, selectedTruck, $contactInput) {
        var $field = $select.closest('.wr-truck-field');
        $select.prop('disabled', true).html('<option value="">Loading…</option>');
        if (!transporterId) {
            $select.html('<option value="">Select transporter first</option>');
            if ($field.length) $field.find('.wr-truck-add-link').hide();
            return;
        }
        if ($field.length) $field.find('.wr-truck-add-link').show();
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
            $select.html('<option value="">Failed to load</option>').prop('disabled', false);
        });
    }

    function loadPendingOptions($select) {
        $select.html('<option value="">Loading…</option>');
        $.get(pendingItemsUrl, function (res) {
            var html = '<option value="">— Select pending order line —</option>';
            (res.results || []).forEach(function (r) {
                html += '<option value="' + r.order_item_id + '" data-pending="' + r.pending_qty + '" data-unit="' + (r.product_unit || '') + '">'
                    + r.label + '</option>';
            });
            $select.html(html);
        }).fail(function () {
            $select.html('<option value="">Failed to load pending orders</option>');
        });
    }

    function initDayBlock($block) {
        var reportId = $block.data('report-id');
        if (!reportId) return;

        var itemUpdateBase = reportUrl(itemUpdateBaseTemplate, reportId);
        var reorderUrl = reportUrl(reorderUrlTemplate, reportId);
        var alreadyProducedUrl = reportUrl(alreadyProducedUrlTemplate, reportId);

        $block.find('.weekly-report-items-body tr[data-locked="0"]').each(function () {
            var $row = $(this);
            var tid = $row.find('.wr-transport-select').val();
            var $truck = $row.find('.wr-truck-select');
            if (tid) {
                loadTrucks($truck, tid, $truck.data('current'), $row.find('.item-contact'));
            }
        });

        var $addForm = $block.find('.wr-add-item-form');
        if ($addForm.length) {
            loadPendingOptions($addForm.find('.wr-add-order-item'));
        }

        $block.on('change', '.wr-transport-select', function () {
            var $scope = $(this).closest('tr, .wr-add-item-form');
            var phone = $(this).find(':selected').data('phone') || '';
            $scope.find('.item-contact, .wr-add-contact').val(phone);
            loadTrucks(
                $scope.find('.wr-truck-select'),
                $(this).val(),
                null,
                $scope.find('.item-contact, .wr-add-contact')
            );
        });

        $block.on('input change', '.item-qty', function () {
            recalcFooter($block);
        });

        $block.on('change', '.wr-add-order-item', function () {
            var pending = $(this).find(':selected').data('pending');
            var unit = $(this).find(':selected').data('unit') || '';
            var $hint = $block.find('.wr-add-pending-hint');
            var $qty = $block.find('.wr-add-quantity');
            if (pending) {
                $hint.text('Remaining: ' + pending + (unit ? ' ' + unit : ''));
                $qty.attr('max', pending).val(pending);
            } else {
                $hint.text('');
            }
        });

        $block.on('submit', '.wr-add-item-form', function (e) {
            e.preventDefault();
            var $form = $(this);
            $form.find('.order_item_id_error, .quantity_error').text('');
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
                        $form.find('.' + key + '_error').text(msgs[0]);
                    });
                    if (!Object.keys(errors).length) {
                        show_error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not add row.');
                    }
                }
            });
        });

        $block.on('click', '.save-item-btn', function () {
            var $row = $(this).closest('tr');
            var id = $row.data('item-id');
            var payload = {
                quantity: $row.find('.item-qty').val(),
                transport_id: $row.find('.wr-transport-select').val() || null,
                truck_number: $row.find('.wr-truck-select').val() || null,
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
                        $block.find('.footer-total').val(parseFloat(res.total).toFixed(2));
                        $block.find('.footer-difference').val(parseFloat(res.difference).toFixed(2));
                        $block.find('.footer-hours').val(parseFloat(res.hours).toFixed(2));
                    } else {
                        recalcFooter($block);
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

        $block.on('change', '.item-sort-order', function () {
            var orders = [];
            $block.find('.weekly-report-items-body tr[data-item-id]').each(function () {
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

        $block.on('click', '.save-footer-btn', function () {
            $block.find('.footer-already-produced-error').text('');
            $block.find('.footer-bags-per-hour-error').text('');
            $.ajax({
                url: alreadyProducedUrl,
                method: 'POST',
                data: {
                    _token: csrf,
                    already_produced: $block.find('.footer-already-produced').val(),
                    production_hours: $block.find('.footer-hours').val(),
                    bags_per_hour: $block.find('.footer-bags-per-hour').val()
                },
                headers: { 'Accept': 'application/json' },
                success: function (res) {
                    $block.find('.footer-total').val(parseFloat(res.total).toFixed(2));
                    $block.find('.footer-difference').val(parseFloat(res.difference).toFixed(2));
                    $block.find('.footer-hours').val(parseFloat(res.hours).toFixed(2));
                    if (res.bags_per_hour !== undefined) {
                        $block.find('.footer-bags-per-hour').val(parseFloat(res.bags_per_hour).toFixed(2));
                        updateHoursLabel($block, parseFloat(res.bags_per_hour));
                    }
                    if (typeof show_success === 'function') show_success('Updated');
                },
                error: function (xhr) {
                    var errors = xhr.responseJSON && xhr.responseJSON.errors;
                    if (errors && errors.already_produced) {
                        $block.find('.footer-already-produced-error').text(errors.already_produced[0]);
                    } else if (errors && errors.bags_per_hour) {
                        $block.find('.footer-bags-per-hour-error').text(errors.bags_per_hour[0]);
                    } else {
                        show_error('Could not update footer values.');
                    }
                }
            });
        });

        $block.on('input', '.footer-already-produced', function () {
            recalcFooter($block, { preserveHours: false });
        });

        $block.on('input', '.footer-bags-per-hour', function () {
            recalcFooter($block, { preserveHours: false });
        });

        $block.on('input', '.footer-hours', function () {
            recalcFooter($block, { preserveHours: true });
        });

        $block.on('submit', '.delete-item-form', function (e) {
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

        @if (auth()->user()->can('edit-weekly-report') && auth()->user()->can('add-dispatch'))
        $block.on('click', '.confirm-item-btn', function () {
            var itemId = $(this).data('item-id');
            var confirmUrl = itemUpdateBase + '/' + itemId + '/confirm';
            $('#confirmFormError').text('');
            $('#confirmItemForm').attr('action', confirmUrl);
            if (window.dispatchPaymentStatusHelpers) {
                window.dispatchPaymentStatusHelpers.setFormStatus($('#confirmItemForm'), 0, '');
            }
            window.wrConfirmModal.show();
        });
        @endif
    }

    $('.weekly-report-day-block').each(function () {
        initDayBlock($(this));
    });

    @if (auth()->user()->can('edit-weekly-report') && auth()->user()->can('add-dispatch'))
    window.wrConfirmModal = new bootstrap.Modal(document.getElementById('confirmItemModal'));

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
