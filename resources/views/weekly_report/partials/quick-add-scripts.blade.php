<script>
(function ($) {
    var wrQuickTransporterTarget = null;
    var wrQuickTruckContext = null;
    var wrQuickDealerTarget = null;
    var wrCitiesUrl = @json(route('get.cities'));
    var wrCsrf = $('meta[name="csrf-token"]').attr('content');

    function wrAppendTransporterOption(id, name, phone) {
        $('.wr-transport-select').each(function () {
            var $select = $(this);
            if (!$select.find('option[value="' + id + '"]').length) {
                var $opt = $('<option>', { value: id, text: name });
                if (phone) $opt.attr('data-phone', phone);
                $select.append($opt);
            }
        });
    }

    function wrAppendTruckOption($truckSelect, truckNumber) {
        if (!$truckSelect.length || !truckNumber) return;
        if ($truckSelect.find('option[value="' + truckNumber + '"]').length === 0) {
            $truckSelect.append($('<option>', { value: truckNumber, text: truckNumber }));
        }
        $truckSelect.prop('disabled', false).val(truckNumber);
        $truckSelect.closest('.wr-truck-field').find('.wr-truck-add-link').show();
    }

    function wrToggleTruckAddLink($scope) {
        $scope.find('.wr-truck-field').each(function () {
            var $field = $(this);
            var $transport = $field.closest('form, tr').find('.wr-transport-select').first();
            var hasTransport = !!$transport.val();
            $field.find('.wr-truck-add-link').toggle(hasTransport);
        });
    }

    function wrInitQuickTransporterForm() {
        var $form = $('#quickTransporterForm');
        if (!$form.length) return;

        $form.find('.qt-profile-input').off('change.wrqt').on('change.wrqt', function (event) {
            var file = event.target.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function (e) { $('#qt_profilePreview').attr('src', e.target.result); };
            reader.readAsDataURL(file);
        });

        $('#wrQuickTransporterModal').off('click.wrqt', '.qt-toggle-pw').on('click.wrqt', '.qt-toggle-pw', function () {
            var $input = $(this).siblings('input');
            var $icon = $(this).find('i');
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $icon.removeClass('ti-eye-off').addClass('ti-eye');
            } else {
                $input.attr('type', 'password');
                $icon.removeClass('ti-eye').addClass('ti-eye-off');
            }
        });
    }

    function wrShowTransporterErrors(errors) {
        $('#quickTransporterForm .is-invalid').removeClass('is-invalid');
        $('#quickTransporterForm .qt-field-error').text('');
        $.each(errors, function (field, messages) {
            $('#quickTransporterForm [name="' + field + '"]').addClass('is-invalid');
            $('#quickTransporterForm .qt-field-error[data-field="' + field + '"]').text(messages[0]);
        });
    }

    function wrShowTruckErrors(errors) {
        $('#quickTruckForm .is-invalid').removeClass('is-invalid');
        $('#quickTruckForm .qt-truck-field-error').text('');
        $.each(errors, function (field, messages) {
            $('#quickTruckForm [name="' + field + '"]').addClass('is-invalid');
            $('#quickTruckForm .qt-truck-field-error[data-field="' + field + '"]').text(messages[0]);
        });
    }

    function wrAppendDealerOption(id, name) {
        $('.wr-add-dealer').each(function () {
            var $select = $(this);
            if (!$select.find('option[value="' + id + '"]').length) {
                $select.append($('<option>', { value: id, text: name }));
            }
        });
    }

    function wrShowDealerErrors(errors) {
        $('#quickDealerForm .is-invalid').removeClass('is-invalid');
        $('#quickDealerForm .qd-field-error').text('');
        $.each(errors, function (field, messages) {
            $('#quickDealerForm [name="' + field + '"]').addClass('is-invalid');
            $('#quickDealerForm .qd-field-error[data-field="' + field + '"]').text(messages[0]);
        });
    }

    function wrInitQuickDealerForm() {
        var $form = $('#quickDealerForm');
        if (!$form.length) return;

        $form.find('.qd-profile-input').off('change.wrqd').on('change.wrqd', function (event) {
            var file = event.target.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function (e) { $('#qd_profilePreview').attr('src', e.target.result); };
            reader.readAsDataURL(file);
        });

        $('#qd_stateDropdown').off('change.wrqd').on('change.wrqd', function () {
            var stateID = $(this).val();
            var $city = $('#qd_cityDropdown');
            $city.html('<option value="">Loading...</option>');
            if (!stateID) {
                $city.html('<option value="">Select city</option>');
                return;
            }
            $.ajax({
                url: wrCitiesUrl,
                type: 'POST',
                data: { state_id: stateID, _token: wrCsrf },
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

        $('#wrQuickDealerModal').off('click.wrqd', '.qd-toggle-pw').on('click.wrqd', '.qd-toggle-pw', function () {
            var $input = $(this).siblings('input');
            var $icon = $(this).find('i');
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $icon.removeClass('ti-eye-off').addClass('ti-eye');
            } else {
                $input.attr('type', 'password');
                $icon.removeClass('ti-eye').addClass('ti-eye-off');
            }
        });
    }

    $(document).on('click', '.wr-quick-add-transporter', function () {
        wrQuickTransporterTarget = $(this).closest('.wr-transport-field').find('.wr-transport-select');
        var modalEl = document.getElementById('wrQuickTransporterModal');
        if (!modalEl) return;

        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        $('#wrQuickTransporterModalBody').html(
            '<div class="text-center text-muted py-4"><i class="ti ti-loader-2 ti-spin fs-3"></i><p class="mb-0 mt-2">Loading…</p></div>'
        );
        modal.show();

        $.get(@json(route('users.transporter.quickCreateForm')))
            .done(function (html) {
                $('#wrQuickTransporterModalBody').html(html);
                wrInitQuickTransporterForm();
            })
            .fail(function () {
                $('#wrQuickTransporterModalBody').html('<div class="alert alert-danger mb-0">Failed to load form.</div>');
            });
    });

    $(document).on('click', '#wrQuickTransporterSubmitBtn', function () {
        var $form = $('#quickTransporterForm');
        if (!$form.length) return;
        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: new FormData($form[0]),
            processData: false,
            contentType: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function (res) {
                bootstrap.Modal.getInstance(document.getElementById('wrQuickTransporterModal')).hide();
                if (typeof show_success === 'function') show_success(res.message || 'Transporter created.');

                var t = res.transporter;
                if (!t || !wrQuickTransporterTarget) return;

                wrAppendTransporterOption(String(t.id), t.name, t.phone_no || '');
                wrQuickTransporterTarget.val(String(t.id)).trigger('change');

                var $scope = wrQuickTransporterTarget.closest('.wr-add-item-form, tr');
                if (t.phone_no) {
                    $scope.find('.wr-add-contact, .item-contact').val(t.phone_no);
                }
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    wrShowTransporterErrors(xhr.responseJSON.errors);
                    show_error('Please correct the highlighted fields.');
                } else {
                    show_error('Failed to create transporter.');
                }
            },
            complete: function () { $btn.prop('disabled', false); }
        });
    });

    $(document).on('click', '.wr-quick-add-truck', function () {
        var $field = $(this).closest('.wr-truck-field');
        var $scope = $field.closest('.wr-add-item-form, tr');
        var $transport = $scope.find('.wr-transport-select').first();
        var transporterId = $transport.val();

        if (!transporterId) {
            show_error('Please select a transporter first.');
            return;
        }

        wrQuickTruckContext = {
            truckSelect: $scope.find('.wr-truck-select').first(),
            contactInput: $scope.find('.wr-add-contact, .item-contact').first()
        };

        var modalEl = document.getElementById('wrQuickTruckModal');
        if (!modalEl) return;

        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        $('#wrQuickTruckModalBody').html(
            '<div class="text-center text-muted py-4"><i class="ti ti-loader-2 ti-spin fs-3"></i><p class="mb-0 mt-2">Loading…</p></div>'
        );
        modal.show();

        $.get(@json(route('truck.quickCreateForm')), { transporter_id: transporterId })
            .done(function (html) { $('#wrQuickTruckModalBody').html(html); })
            .fail(function () {
                $('#wrQuickTruckModalBody').html('<div class="alert alert-danger mb-0">Failed to load form.</div>');
            });
    });

    $(document).on('click', '#wrQuickTruckSubmitBtn', function () {
        var $form = $('#quickTruckForm');
        if (!$form.length) return;
        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function (res) {
                bootstrap.Modal.getInstance(document.getElementById('wrQuickTruckModal')).hide();
                if (typeof show_success === 'function') show_success(res.message || 'Truck added.');

                var truck = res.truck;
                if (!truck || !truck.truck_number || !wrQuickTruckContext) return;
                wrAppendTruckOption(wrQuickTruckContext.truckSelect, truck.truck_number);
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    wrShowTruckErrors(xhr.responseJSON.errors);
                    show_error('Please correct the highlighted fields.');
                } else {
                    show_error('Failed to create truck.');
                }
            },
            complete: function () { $btn.prop('disabled', false); }
        });
    });

    $(document).on('click', '.wr-quick-add-dealer', function () {
        wrQuickDealerTarget = $(this).closest('.wr-dealer-field').find('.wr-add-dealer');
        var modalEl = document.getElementById('wrQuickDealerModal');
        if (!modalEl) return;

        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        $('#wrQuickDealerModalBody').html(
            '<div class="text-center text-muted py-4"><i class="ti ti-loader-2 ti-spin fs-3"></i><p class="mb-0 mt-2">Loading…</p></div>'
        );
        modal.show();

        $.get(@json(route('dealer.quickCreateForm')))
            .done(function (html) {
                $('#wrQuickDealerModalBody').html(html);
                wrInitQuickDealerForm();
            })
            .fail(function (xhr) {
                var msg = xhr.status === 403
                    ? 'You are not allowed to add a dealer.'
                    : 'Failed to load form.';
                $('#wrQuickDealerModalBody').html('<div class="alert alert-danger mb-0">' + msg + '</div>');
            });
    });

    $(document).on('click', '#wrQuickDealerSubmitBtn', function () {
        var $form = $('#quickDealerForm');
        if (!$form.length) return;
        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: new FormData($form[0]),
            processData: false,
            contentType: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function (res) {
                bootstrap.Modal.getInstance(document.getElementById('wrQuickDealerModal')).hide();
                if (typeof show_success === 'function') show_success(res.message || 'Dealer created.');

                var d = res.dealer;
                if (!d || !wrQuickDealerTarget) return;

                wrAppendDealerOption(String(d.id), d.name || d.firm_shop_name || 'Dealer');
                wrQuickDealerTarget.val(String(d.id)).trigger('change');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    wrShowDealerErrors(xhr.responseJSON.errors);
                    show_error('Please correct the highlighted fields.');
                } else {
                    show_error('Failed to create dealer.');
                }
            },
            complete: function () { $btn.prop('disabled', false); }
        });
    });

    $(document).on('change', '.wr-transport-select', function () {
        var $scope = $(this).closest('.wr-add-item-form, tr, .wr-day-body');
        wrToggleTruckAddLink($scope);
    });

    $('.weekly-report-day-block').each(function () {
        wrToggleTruckAddLink($(this));
    });
})(jQuery);
</script>
