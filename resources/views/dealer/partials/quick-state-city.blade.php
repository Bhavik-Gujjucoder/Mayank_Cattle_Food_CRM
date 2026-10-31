@can('add-state')
    <div class="modal custom-modal fade" id="quickDealerStateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add State</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form id="quickDealerStateForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="col-form-label">State Name <span class="text-danger">*</span></label>
                            <input type="text" id="dqs_state_name" class="form-control" placeholder="State Name" maxlength="255">
                            <span class="dqs_state_name_error text-danger small"></span>
                        </div>
                        <div class="mb-0">
                            <label class="col-form-label">Status</label>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <input type="radio" class="status-radio" id="dqs_active" name="dqs_status" value="1" checked>
                                    <label for="dqs_active">Active</label>
                                </div>
                                <div>
                                    <input type="radio" class="status-radio" id="dqs_inactive" name="dqs_status" value="0">
                                    <label for="dqs_inactive">Inactive</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="quickDealerStateSubmitBtn">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan

@can('add-city')
    <div class="modal custom-modal fade" id="quickDealerCityModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add City</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form id="quickDealerCityForm">
                    <div class="modal-body">
                        <p class="text-muted small mb-3">City will be added under <strong id="quickDealerCityStateLabel"></strong>.</p>
                        <div class="mb-3">
                            <label class="col-form-label">City Name <span class="text-danger">*</span></label>
                            <input type="text" id="dqc_city_name" class="form-control" placeholder="City Name" maxlength="255">
                            <span class="dqc_city_name_error text-danger small"></span>
                        </div>
                        <div class="mb-0">
                            <label class="col-form-label">Status</label>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <input type="radio" class="status-radio" id="dqc_active" name="dqc_status" value="1" checked>
                                    <label for="dqc_active">Active</label>
                                </div>
                                <div>
                                    <input type="radio" class="status-radio" id="dqc_inactive" name="dqc_status" value="0">
                                    <label for="dqc_inactive">Inactive</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="quickDealerCitySubmitBtn">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan

<script>
    $(function () {
        function appendDealerSortedOption($select, value, label) {
            if ($select.find('option[value="' + value + '"]').length) {
                return;
            }

            var $option = $('<option>', { value: value, text: label });
            var placed = false;

            $select.find('option').each(function (index) {
                if (index === 0) {
                    return;
                }
                if ($(this).text().localeCompare(label, undefined, { sensitivity: 'base' }) > 0) {
                    $(this).before($option);
                    placed = true;
                    return false;
                }
            });

            if (!placed) {
                $select.append($option);
            }
        }

        function loadDealerCities(stateId, selectedCityId) {
            var $city = $('#cityDropdown');
            if (!stateId) {
                $city.html('<option value="">Select city</option>');
                return;
            }

            $city.html('<option value="">Loading...</option>');
            $.ajax({
                url: "{{ route('get.cities') }}",
                type: 'POST',
                data: {
                    state_id: stateId,
                    _token: "{{ csrf_token() }}"
                },
                success: function (data) {
                    $city.empty().append('<option value="">Select city</option>');
                    $.each(data, function (key, city) {
                        var selected = String(selectedCityId) === String(city.id) ? ' selected' : '';
                        $city.append('<option value="' + city.id + '"' + selected + '>' + city.city_name + '</option>');
                    });
                }
            });
        }

        @can('add-state')
        (function () {
            var modalEl = document.getElementById('quickDealerStateModal');
            if (!modalEl) return;

            var modal = new bootstrap.Modal(modalEl);

            function clearErrors() {
                $('#dqs_state_name').removeClass('is-invalid');
                $('.dqs_state_name_error').text('');
            }

            $('#addDealerStateLink').on('click', function () {
                clearErrors();
                $('#quickDealerStateForm')[0].reset();
                $('#dqs_active').prop('checked', true);
                modal.show();
            });

            $('#quickDealerStateForm').on('submit', function (e) {
                e.preventDefault();
                clearErrors();

                var $btn = $('#quickDealerStateSubmitBtn');
                $btn.prop('disabled', true);

                $.ajax({
                    url: "{{ route('state.store') }}",
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    data: {
                        state_name: $('#dqs_state_name').val(),
                        status: $('input[name="dqs_status"]:checked').val()
                    },
                    success: function (response) {
                        modal.hide();
                        show_success(response.message || 'State created successfully');

                        var state = response.state;
                        if (!state || String(state.status) !== '1') {
                            return;
                        }

                        appendDealerSortedOption($('#stateDropdown'), state.id, state.state_name);
                        $('#stateDropdown').val(String(state.id)).trigger('change');
                    },
                    error: function (xhr) {
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            var message = xhr.responseJSON.errors.state_name
                                ? xhr.responseJSON.errors.state_name[0]
                                : 'Please correct the highlighted fields.';
                            $('#dqs_state_name').addClass('is-invalid');
                            $('.dqs_state_name_error').text(message);
                        } else {
                            show_error('Failed to create state. Please try again.');
                        }
                    },
                    complete: function () {
                        $btn.prop('disabled', false);
                    }
                });
            });
        })();
        @endcan

        @can('add-city')
        (function () {
            var modalEl = document.getElementById('quickDealerCityModal');
            if (!modalEl) return;

            var modal = new bootstrap.Modal(modalEl);

            function clearErrors() {
                $('#dqc_city_name').removeClass('is-invalid');
                $('.dqc_city_name_error').text('');
            }

            $('#addDealerCityLink').on('click', function () {
                var stateId = $('#stateDropdown').val();
                if (!stateId) {
                    show_error('Please select a state first.');
                    return;
                }

                clearErrors();
                $('#quickDealerCityForm')[0].reset();
                $('#dqc_active').prop('checked', true);
                $('#quickDealerCityStateLabel').text($('#stateDropdown option:selected').text());
                modal.show();
            });

            $('#quickDealerCityForm').on('submit', function (e) {
                e.preventDefault();
                clearErrors();

                var stateId = $('#stateDropdown').val();
                if (!stateId) {
                    show_error('Please select a state first.');
                    return;
                }

                var $btn = $('#quickDealerCitySubmitBtn');
                $btn.prop('disabled', true);

                $.ajax({
                    url: "{{ route('city.store') }}",
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    data: {
                        state_id: stateId,
                        city_name: $('#dqc_city_name').val(),
                        status: $('input[name="dqc_status"]:checked').val()
                    },
                    success: function (response) {
                        modal.hide();
                        show_success(response.message || 'City created successfully');

                        var city = response.city;
                        if (!city || String(city.status) !== '1') {
                            return;
                        }

                        loadDealerCities(stateId, city.id);
                    },
                    error: function (xhr) {
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            var message = xhr.responseJSON.errors.city_name
                                ? xhr.responseJSON.errors.city_name[0]
                                : (xhr.responseJSON.errors.state_id ? xhr.responseJSON.errors.state_id[0] : 'Please correct the highlighted fields.');
                            $('#dqc_city_name').addClass('is-invalid');
                            $('.dqc_city_name_error').text(message);
                        } else {
                            show_error('Failed to create city. Please try again.');
                        }
                    },
                    complete: function () {
                        $btn.prop('disabled', false);
                    }
                });
            });
        })();
        @endcan
    });
</script>
