@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('content')

<div class="card">
    <div class="card-header">

        <div class="cls-cardhed-part">
            <div class="cls-form-left">

                {{-- Search --}}
                <div class="common-hed-form cls-form-serc">
                    <div class="icon-form">
                        <span class="form-icon"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control" id="customSearch" placeholder="Search">
                    </div>
                </div>

                {{-- Transporter filter --}}
                <div class="common-hed-form cls-form-select-input">
                    <label class="col-form-label">Transporter</label>
                    <select class="form-select select search-dropdown" id="filterTransporter">
                        <option value="all">All Transporters</option>
                        @foreach ($transporters as $tp)
                            <option value="{{ $tp->id }}">{{ $tp->name }}</option>
                        @endforeach
                    </select>
                </div>

            </div>
            <div class="cls-form-right">
                <div class="comm-header-right-btn">
                    @can('add-truck')
                        <a href="javascript:void(0);" class="btn btn-primary" id="openTruckModal">
                            <i class="ti ti-square-rounded-plus me-2"></i>Add Truck
                        </a>
                    @endcan
                </div>
            </div>
        </div>

    </div>

    <div class="card-body">
        <div class="table-responsive custom-table">
            <table class="table dataTable no-footer" id="truck_table">
                <button class="btn btn-danger me-2" id="bulk_delete_button" style="display: none;">
                    <i class="ti ti-trash me-2"></i>Delete Selected
                </button>
                <thead class="thead-light">
                    <tr>
                        <th hidden>ID</th>
                        <th class="no-sort" scope="col">
                            <label class="checkboxs">
                                <input type="checkbox" id="select-all" class="truck_checkbox">
                                <span class="checkmarks"></span>
                            </label>
                        </th>
                        <th class="no-sort" scope="col">Sr No</th>
                        <th scope="col">Truck Number</th>
                        <th scope="col">Transporter</th>
                        <th scope="col">Status</th>
                        @canany(['edit-truck', 'delete-truck'])
                            <th scope="col">Action</th>
                        @endcanany
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>


{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- Add / Edit Modal                                              --}}
{{-- ══════════════════════════════════════════════════════════════ --}}
<div class="modal custom-modal fade" id="truckModal" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="truckModalTitle">Truck Management</h5>
                <div class="d-flex align-items-center mod-toggle">
                    <button type="button" class="btn-close close_poup" data-bs-dismiss="modal">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
            </div>

            <form id="truckForm">
                @csrf
                <input type="hidden" name="truck_id">

                <div class="modal-body">

                    {{-- Transporter --}}
                    <div class="mb-3">
                        <label class="col-form-label">
                            Transporter <span class="text-danger">*</span>
                        </label>
                        <select name="transporter_id" id="modal_transporter_id" class="form-select">
                            <option value="">-- Select Transporter --</option>
                            @foreach ($transporters as $tp)
                                <option value="{{ $tp->id }}">{{ $tp->name }}</option>
                            @endforeach
                        </select>
                        <span class="transporter_id_error text-danger small"></span>
                    </div>

                    {{-- Truck Number --}}
                    <div class="mb-3">
                        <label class="col-form-label">
                            Truck Number <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="truck_number" id="modal_truck_number"
                               class="form-control" placeholder="e.g. GJ 01 AB 1234"
                               style="text-transform: uppercase;">
                        <span class="truck_number_error text-danger small"></span>
                    </div>

                    {{-- Status --}}
                    <div class="mb-3">
                        <label class="col-form-label">Status</label>
                        <div class="d-flex align-items-center">
                            <div class="me-2">
                                <input type="radio" id="status_active" name="status" value="1" checked>
                                <label for="status_active">Active</label>
                            </div>
                            <div>
                                <input type="radio" id="status_inactive" name="status" value="0">
                                <label for="status_inactive">Inactive</label>
                            </div>
                        </div>
                        <span class="status_error text-danger small"></span>
                    </div>

                </div>

                <div class="modal-footer">
                    <div class="d-flex align-items-center justify-content-end m-0">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="truckSubmitBtn">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
@section('script')
<script>
    const isShowAction   = {{ auth()->user()->canAny(['edit-truck', 'delete-truck']) ? 'true' : 'false' }};
    const isShowCheckbox = {{ auth()->user()->can('delete-truck') ? 'true' : 'false' }};

    /* ── DataTable ──────────────────────────────────────────────── */
    var truck_table = $('#truck_table').DataTable({
        pageLength: 10,
        deferRender: true,
        processing:  true,
        serverSide:  true,
        responsive:  true,
        dom:         'lrtip',
        order:       [[0, 'desc']],
        ajax: {
            url: "{{ route('truck.index') }}",
            data: function(d) {
                d.transporter_id = $('#filterTransporter').val();
            }
        },
        columns: [
            { data: 'id',             name: 'id',             visible: false, searchable: false },
            { data: 'checkbox',       name: 'checkbox',       orderable: false, searchable: false, visible: isShowCheckbox },
            { data: 'DT_RowIndex',    name: 'DT_RowIndex',    orderable: false, searchable: false },
            { data: 'truck_number',   name: 'truck_number',   searchable: true },
            { data: 'transporter_name', name: 'transporter_name', orderable: false, searchable: false },
            { data: 'status',         name: 'status',         searchable: false },
            { data: 'action',         name: 'action',         orderable: false, searchable: false, visible: isShowAction },
        ]
    });

    /* ── Filters ────────────────────────────────────────────────── */
    $('#filterTransporter').on('change', function() { truck_table.draw(); });
    $('#customSearch').on('keyup', function() { truck_table.search(this.value).draw(); });

    /* ── Select2 inside modal ───────────────────────────────────── */
    function initTruckModalSelect2() {
        $('#modal_transporter_id').select2({
            dropdownParent: $('#truckModal'),
            placeholder: '-- Select Transporter --',
            width: '100%'
        });
    }

    $('#truckModal').on('shown.bs.modal', function() { initTruckModalSelect2(); });
    $('#truckModal').on('hidden.bs.modal', function() {
        if ($('#modal_transporter_id').hasClass('select2-hidden-accessible')) {
            $('#modal_transporter_id').select2('destroy');
        }
    });

    /* ── Open modal — Add ───────────────────────────────────────── */
    $('#openTruckModal').on('click', function() {
        $('#truckForm')[0].reset();
        $('#truckModalTitle').text('Add Truck');
        $('#truckSubmitBtn').text('Create');
        $('input[name="truck_id"]').val('');
        $('input[name="status"][value="1"]').prop('checked', true);
        clearTruckErrors();
        $('#truckModal').modal('show');
    });

    /* ── Open modal — Edit ──────────────────────────────────────── */
    $(document).on('click', '.edit-truck-btn', function() {
        var truck_id = $(this).data('id');
        clearTruckErrors();

        $.get('{{ route('truck.edit', ':id') }}'.replace(':id', truck_id), function(truck) {
            $('#truckModalTitle').text('Edit Truck');
            $('#truckSubmitBtn').text('Update');
            $('input[name="truck_id"]').val(truck_id);
            $('#modal_truck_number').val(truck.truck_number);
            $('input[name="status"][value="' + truck.status + '"]').prop('checked', true);
            $('#modal_transporter_id').val(truck.transporter_id).trigger('change');
            $('#truckModal').modal('show');
        });
    });

    /* ── Form submit (Add & Edit) ───────────────────────────────── */
    $('#truckForm').on('submit', function(e) {
        e.preventDefault();
        clearTruckErrors();

        var truck_id = $('input[name="truck_id"]').val();
        var url    = truck_id
            ? '{{ route('truck.update', ':id') }}'.replace(':id', truck_id)
            : '{{ route('truck.store') }}';
        var method = truck_id ? 'PUT' : 'POST';

        $.ajax({
            url:  url,
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: $(this).serialize() + '&_method=' + method,
            success: function(response) {
                $('#truckModal').modal('hide');
                truck_table.ajax.reload();
                show_success(response.message);
            },
            error: function(response) {
                if (response.responseJSON && response.responseJSON.errors) {
                    displayTruckErrors(response.responseJSON.errors);
                }
            }
        });
    });

    /* ── Delete single ──────────────────────────────────────────── */
    $(document).on('click', '.deleteTruck', function(e) {
        e.preventDefault();
        var truckId = $(this).data('id');
        var form    = $('#delete-truck-form-' + truckId);
        confirmTruckDeletion(function() { form.submit(); });
    });

    /* ── Bulk delete ────────────────────────────────────────────── */
    $('#select-all').on('change', function() {
        $('.truck_checkbox').prop('checked', this.checked);
        toggleBulkBtn();
    });

    $(document).on('change', '.truck_checkbox', function() { toggleBulkBtn(); });

    function toggleBulkBtn() {
        var count = $('.truck_checkbox:checked').not('#select-all').length;
        count > 0 ? $('#bulk_delete_button').show() : $('#bulk_delete_button').hide();
    }

    $('#bulk_delete_button').on('click', function() {
        confirmTruckDeletion(function() {
            var selectedIds = $('.truck_checkbox:checked').not('#select-all').map(function() {
                return $(this).data('id');
            }).get();

            if (selectedIds.length > 0) {
                $.ajax({
                    url:    "{{ route('truck.bulkDelete') }}",
                    method: 'POST',
                    data:   { ids: selectedIds, _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        show_success(response.message);
                        truck_table.ajax.reload();
                        $('#bulk_delete_button').hide();
                        $('#select-all').prop('checked', false);
                    },
                    error: function() {
                        show_error('An error occurred while deleting.');
                    }
                });
            }
        });
    });

    /* ── Helpers ────────────────────────────────────────────────── */
    function clearTruckErrors() {
        $('#truckForm .text-danger.small').text('');
        $('#truckForm').find('.is-invalid').removeClass('is-invalid');
    }

    function displayTruckErrors(errors) {
        clearTruckErrors();
        $.each(errors, function(key, value) {
            $('[name="' + key + '"]').addClass('is-invalid');
            $('.' + key + '_error').text(value[0]);
        });
    }

    function confirmTruckDeletion(callback) {
        Swal.fire({
            title: 'Are you sure?',
            text:  'You want to remove this Truck? Once deleted, it cannot be recovered.',
            icon:  'warning',
            showCancelButton:  true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText:  'Cancel',
            customClass: {
                popup:         'my-custom-popup',
                title:         'my-custom-title',
                confirmButton: 'btn btn-primary',
                cancelButton:  'btn btn-secondary',
                icon:          'my-custom-icon swal2-warning'
            }
        }).then(function(result) {
            if (result.isConfirmed) callback();
        });
    }
</script>
@endsection
