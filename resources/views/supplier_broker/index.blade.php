@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('content')
    <div class="card">
        <div class="card-header">
            <div class="cls-cardhed-part">
                <div class="cls-form-left">
                    <div class="common-hed-form cls-form-serc">
                        <div class="icon-form">
                            <span class="form-icon"><i class="ti ti-search"></i></span>
                            <input type="text" class="form-control" id="customSearch" placeholder="Search">
                        </div>
                    </div>

                    <div class="common-hed-form cls-form-select-input">
                        <label class="col-form-label">Status</label>
                        <select class="form-select select search-dropdown" id="filterStatus">
                            <option value="all">All</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>

                    <div class="common-hed-form cls-form-select-input">
                        <label class="col-form-label">State</label>
                        <select class="form-select select search-dropdown" id="filterState">
                            <option value="all">All States</option>
                            @foreach ($states as $state)
                                <option value="{{ $state->id }}">{{ $state->state_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="common-hed-form cls-form-select-input">
                        <label class="col-form-label">City</label>
                        <select class="form-select select search-dropdown" id="filterCity" disabled>
                            <option value="all">All Cities</option>
                        </select>
                    </div>

                    <div class="common-hed-form cls-form-select-input d-flex align-items-end">
                        <button type="button" class="btn btn-light" id="resetSupplierBrokerFilters">
                            <i class="ti ti-refresh me-1"></i>Reset
                        </button>
                    </div>
                </div>
                <div class="cls-form-right">
                    <div class="comm-header-right-btn">
                        @can('add-supplier-broker')
                            <a href="javascript:void(0);" class="btn btn-primary" id="openSupplierBrokerModal">
                                <i class="ti ti-square-rounded-plus me-2"></i>Add Supplier Broker
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive custom-table">
                <table class="table dataTable no-footer" id="supplier_broker_table">
                    <button class="btn btn-danger me-2" id="bulk_delete_button" style="display:none;">
                        <i class="ti ti-trash me-1"></i>Delete Selected
                    </button>
                    <thead class="thead-light">
                        <tr>
                            <th hidden>ID</th>
                            <th class="no-sort">
                                <label class="checkboxs">
                                    <input type="checkbox" id="select-all"><span class="checkmarks"></span>
                                </label>
                            </th>
                            <th class="no-sort">Sr No</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>City</th>
                            <th>Status</th>
                            @canany(['edit-supplier-broker', 'delete-supplier-broker'])
                                <th class="no-sort">Action</th>
                            @endcanany
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal custom-modal fade" id="supplierBrokerModal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="supplierBrokerModalTitle">Add Supplier Broker</h5>
                    <div class="d-flex align-items-center mod-toggle">
                        <button type="button" class="btn-close close_poup" data-bs-dismiss="modal">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                </div>

                <form id="supplierBrokerForm">
                    @csrf
                    <input type="hidden" name="supplier_broker_id">

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="col-form-label">Name <span class="text-dangers">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Supplier broker name"
                                    maxlength="255">
                                <span class="name_error text-danger small"></span>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="col-form-label">Mobile</label>
                                <input type="text" name="mobile" class="form-control" placeholder="Mobile number"
                                    maxlength="10" oninput="this.value = this.value.replace(/[^0-9+\-\s]/g,'')">
                                <span class="mobile_error text-danger small"></span>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="col-form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="Email address"
                                    maxlength="255">
                                <span class="email_error text-danger small"></span>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="col-form-label">Address</label>
                                <textarea name="address" class="form-control" rows="3" placeholder="Supplier broker address"></textarea>
                                <span class="address_error text-danger small"></span>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="col-form-label">State <span class="text-dangers">*</span></label>
                                <select name="state_id" id="supplier_broker_state_id" class="form-select">
                                    <option value="">-- Select State --</option>
                                    @foreach ($states as $state)
                                        <option value="{{ $state->id }}">{{ $state->state_name }}</option>
                                    @endforeach
                                </select>
                                <span class="state_id_error text-danger small"></span>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="col-form-label">City <span class="text-dangers">*</span></label>
                                <select name="city_id" id="supplier_broker_city_id" class="form-select" disabled>
                                    <option value="">-- Select City --</option>
                                </select>
                                <span class="city_id_error text-danger small"></span>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="col-form-label">Status</label>
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <input type="radio" class="status-radio" id="sb_active" name="status"
                                            value="1" checked>
                                        <label for="sb_active">Active</label>
                                    </div>
                                    <div>
                                        <input type="radio" class="status-radio" id="sb_inactive" name="status"
                                            value="0">
                                        <label for="sb_inactive">Inactive</label>
                                    </div>
                                </div>
                                <span class="status_error text-danger small"></span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <div class="d-flex align-items-center justify-content-end m-0">
                            <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="supplierBrokerSubmitBtn">Save</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        const isShowAction = {{ auth()->user()->canAny(['edit-supplier-broker', 'delete-supplier-broker']) ? 'true' : 'false' }};
        const isShowCheckbox = {{ auth()->user()->can('delete-supplier-broker') ? 'true' : 'false' }};
        var supplier_broker_table = $('#supplier_broker_table').DataTable({
            pageLength: 10,
            deferRender: true,
            processing: true,
            serverSide: true,
            responsive: true,
            dom: 'lrtip',
            order: [[0, 'desc']],
            ajax: {
                url: "{{ route('supplier-broker.index') }}",
                data: function(d) {
                    d.status = $('#filterStatus').val();
                    d.state_id = $('#filterState').val();
                    d.city_id = $('#filterCity').val();
                }
            },
            columns: [
                { data: 'id', name: 'id', visible: false, searchable: false },
                { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false, visible: isShowCheckbox },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'mobile', name: 'mobile' },
                { data: 'email', name: 'email' },
                { data: 'address', name: 'address', orderable: false },
                { data: 'city_name', name: 'city_name', orderable: false },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false, visible: isShowAction },
            ]
        });

        bindDebouncedDataTableSearch('#customSearch', supplier_broker_table);

        $('#filterStatus, #filterCity').on('change', function() {
            supplier_broker_table.draw();
        });

        function loadSupplierBrokerCities(stateId, selectedCityId) {
            $('#supplier_broker_city_id').html('<option value="">Loading...</option>');

            if (!stateId) {
                $('#supplier_broker_city_id').prop('disabled', true)
                    .html('<option value="">-- Select City --</option>');
                return;
            }

            $.ajax({
                url: "{{ route('get.cities') }}",
                type: 'POST',
                data: { state_id: stateId, _token: "{{ csrf_token() }}" },
                success: function(cities) {
                    $('#supplier_broker_city_id').empty().prop('disabled', false)
                        .append('<option value="">-- Select City --</option>');
                    $.each(cities, function(key, city) {
                        let selected = (selectedCityId == city.id) ? 'selected' : '';
                        $('#supplier_broker_city_id').append(
                            '<option value="' + city.id + '" ' + selected + '>' + city.city_name + '</option>'
                        );
                    });
                }
            });
        }

        function loadFilterCities(stateId, selectedCityId) {
            $('#filterCity').html('<option value="all">Loading...</option>');

            if (!stateId || stateId === 'all') {
                $('#filterCity').prop('disabled', true).html('<option value="all">All Cities</option>');
                return;
            }

            $.ajax({
                url: "{{ route('get.cities') }}",
                type: 'POST',
                data: { state_id: stateId, _token: "{{ csrf_token() }}" },
                success: function(cities) {
                    $('#filterCity').empty().prop('disabled', false)
                        .append('<option value="all">All Cities</option>');
                    $.each(cities, function(key, city) {
                        let selected = (selectedCityId == city.id) ? 'selected' : '';
                        $('#filterCity').append(
                            '<option value="' + city.id + '" ' + selected + '>' + city.city_name + '</option>'
                        );
                    });
                }
            });
        }

        $('#supplier_broker_state_id').on('change', function() {
            loadSupplierBrokerCities($(this).val(), null);
        });

        $('#filterState').on('change', function() {
            loadFilterCities($(this).val(), null);
            supplier_broker_table.draw();
        });

        $('#resetSupplierBrokerFilters').on('click', function() {
            $('#customSearch').val('');
            supplier_broker_table.search('');
            $('#filterStatus, #filterState').val('all');
            $('#filterCity').prop('disabled', true).html('<option value="all">All Cities</option>');
            $('.search-dropdown').trigger('change.select2');
            supplier_broker_table.draw();
        });

        function resetSupplierBrokerLocationFields() {
            $('#supplier_broker_state_id').val('');
            $('#supplier_broker_city_id').prop('disabled', true)
                .html('<option value="">-- Select City --</option>');
        }

        $('#openSupplierBrokerModal').on('click', function() {
            $('#supplierBrokerForm')[0].reset();
            $('input[name="supplier_broker_id"]').val('');
            $('#supplierBrokerModalTitle').text('Add Supplier Broker');
            $('#supplierBrokerSubmitBtn').text('Save');
            clearSupplierBrokerErrors();
            $('input[name="status"][value="1"]').prop('checked', true);
            resetSupplierBrokerLocationFields();
            $('#supplierBrokerModal').modal('show');
        });

        $(document).on('click', '.edit-supplier-broker-btn', function() {
            let id = $(this).data('id');
            clearSupplierBrokerErrors();

            $.get('{{ route('supplier-broker.edit', ':id') }}'.replace(':id', id), function(data) {
                $('#supplierBrokerModalTitle').text('Edit Supplier Broker');
                $('#supplierBrokerSubmitBtn').text('Update');
                $('input[name="supplier_broker_id"]').val(data.id);
                $('input[name="name"]').val(data.name);
                $('input[name="mobile"]').val(data.mobile);
                $('input[name="email"]').val(data.email);
                $('textarea[name="address"]').val(data.address);
                $('input[name="status"][value="' + data.status + '"]').prop('checked', true);
                $('#supplier_broker_state_id').val(data.state_id);
                loadSupplierBrokerCities(data.state_id, data.city_id);
                $('#supplierBrokerModal').modal('show');
            });
        });

        $('#supplierBrokerForm').on('submit', function(e) {
            e.preventDefault();
            clearSupplierBrokerErrors();

            let id = $('input[name="supplier_broker_id"]').val();
            let url = id ? '{{ route('supplier-broker.update', ':id') }}'.replace(':id', id) :
                '{{ route('supplier-broker.store') }}';
            let method = id ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: $(this).serialize() + '&_method=' + method,
                success: function(response) {
                    $('#supplierBrokerModal').modal('hide');
                    supplier_broker_table.ajax.reload();
                    show_success(response.message);
                },
                error: function(response) {
                    displaySupplierBrokerErrors(response.responseJSON.errors);
                }
            });
        });

        $(document).on('click', '.delete-supplier-broker-btn', function(e) {
            e.preventDefault();
            let id = $(this).data('id');
            let form = $('#delete-supplier-broker-form-' + id);

            Swal.fire({
                title: 'Are you sure?',
                text: 'This supplier broker will be deleted permanently.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-secondary'
                }
            }).then(result => {
                if (result.isConfirmed) form.submit();
            });
        });

        $('#select-all').on('change', function() {
            $('.supplier_broker_checkbox').prop('checked', this.checked);
            toggleBulkDeleteBtn();
        });

        $(document).on('change', '.supplier_broker_checkbox', function() {
            toggleBulkDeleteBtn();
            if (!this.checked) $('#select-all').prop('checked', false);
        });

        function toggleBulkDeleteBtn() {
            let count = $('.supplier_broker_checkbox:checked').length;
            count > 0 ? $('#bulk_delete_button').show() : $('#bulk_delete_button').hide();
        }

        $('#bulk_delete_button').on('click', function() {
            let ids = $('.supplier_broker_checkbox:checked').map(function() {
                return $(this).data('id');
            }).get();

            Swal.fire({
                title: 'Are you sure?',
                text: 'You want to remove this supplier broker.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete!',
                cancelButtonText: 'Cancel',
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-secondary'
                }
            }).then(result => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route('supplier-broker.bulkDelete') }}',
                        method: 'POST',
                        data: { ids: ids, _token: '{{ csrf_token() }}' },
                        success: function(response) {
                            show_success(response.message);
                            supplier_broker_table.ajax.reload();
                            $('#bulk_delete_button').hide();
                            $('#select-all').prop('checked', false);
                        },
                        error: function(xhr) {
                            show_error(xhr.responseJSON?.message || 'An error occurred while deleting.');
                        }
                    });
                }
            });
        });

        function clearSupplierBrokerErrors() {
            $('#supplierBrokerForm .text-danger').text('');
            $('#supplierBrokerForm .is-invalid').removeClass('is-invalid');
        }

        function displaySupplierBrokerErrors(errors) {
            $.each(errors, function(key, value) {
                $('input[name="' + key + '"], select[name="' + key + '"], textarea[name="' + key + '"]').addClass('is-invalid');
                $('.' + key + '_error').text(value[0]);
            });
        }
    </script>
@endsection
