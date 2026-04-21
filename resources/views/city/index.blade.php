@extends('layouts.main')
@section('content')
@section('title')
    {{ $page_title }}
@endsection

<div class="card">
    <div class="card-header">
        <!-- Search -->
        <div class="row align-items-center">
            <div class="col-sm-4">
                <div class="icon-form mb-3 mb-sm-0">
                    <span class="form-icon"><i class="ti ti-search"></i></span>
                    <input type="text" class="form-control" id="customSearch" placeholder="Search">
                </div>
            </div>
            <div class="col-sm-8">
                <div class="d-flex align-items-center flex-wrap row-gap-2 justify-content-sm-end">
                    <a href="javascript:void(0);" id="openModal" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#edit_citymanagement"><i class="ti ti-square-rounded-plus me-2"></i>Add City</a>
                </div>
            </div>
        </div>
        <!-- /Search -->
    </div>
    <div class="card-body">
        <!-- order management List -->
        <div class="table-responsive custom-table">
            <table class="table dataTable no-footer" id="city_table">
                <button class="btn btn-primary" id="bulk_delete_button" style="display: none;">Delete Selected</button>
                <thead class="thead-light">
                    <tr>
                        <th hidden>ID</th>
                        <th class="no-sort" scope="col">
                            <label class="checkboxs">
                                <input type="checkbox" id="select-all" class="city_checkbox"><span
                                    class="checkmarks"></span>
                            </label>

                        </th>
                        <th class="no-sort" scope="col">Sr no</th>
                        <th scope="col">State Name</th>
                        <th scope="col">City Name</th>
                        <th scope="col">Status</th>
                        <th class="" scope="col">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<div class="modal custom-modal fade" id="adminModal" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">City Management</h5>
                <div class="d-flex align-items-center mod-toggle">
                    <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
            </div>
            <form id="adminForm">
                @csrf
                <input type="hidden" name="city_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="col-form-label">City Name *</label>
                        <input type="text" name="city_name" placeholder="City Name" value=""
                            class="form-control">
                        <span class="city_name_error"></span>
                    </div>
                    <div class="mb-3">
                        <label class="col-form-label">State Name *</label>
                        <select name="state_id" class="select">
                            <option value="" selected>Select state</option>
                            @foreach ($states as $state)
                                <option value="{{ $state->id }}">{{ $state->state_name }}</option>
                            @endforeach
                        </select>
                        <span class="state_id_error"></span>
                    </div>
                    <div class="mb-3">
                        <label class="col-form-label">Status</label>
                        <div class="d-flex align-items-center">
                            <div class="me-2">
                                <input type="radio" class="status-radio" id="active1" name="status" value="1"
                                    {{ old('status', '1') == '1' ? 'checked' : '' }}>
                                <label for="active1">Active</label>
                            </div>
                            <div>
                                <input type="radio" class="status-radio" id="inactive1" name="status" value="0"
                                    {{ old('status') == '0' ? 'checked' : '' }}>
                                <label for="inactive1">Inactive</label>
                            </div>
                        </div>
                        @error('status')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="d-flex align-items-center justify-content-end m-0">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
@section('script')
<script>
    var city_table = $('#city_table').DataTable({
        "pageLength": 10,
        deferRender: true, // Prevents unnecessary DOM rendering
        processing: true,
        serverSide: true,
        responsive: true,
        dom: 'lrtip',
        order: [
            [0, 'desc']
        ],
        ajax: "{{ route('city.index') }}",
        columns: [{
                data: 'id',
                name: 'id',
                visible: false,
                searchable: false
            },
            {
                data: 'checkbox',
                name: 'checkbox',
                orderable: false,
                searchable: false
            },
            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            }, {
                data: 'state_id',
                name: 'state_id',
                searchable: true
            }, {
                data: 'city_name',
                name: 'city_name',
                searchable: true
            },
            {
                data: 'status',
                name: 'status',
                searchable: true
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            },
        ],
        columnDefs: [{
                targets: 0, // ID (hidden)
                createdCell: function(td) {
                    $(td).attr('data-label', 'ID');
                }
            },
            {
                targets: 1, // Checkbox
                createdCell: function(td) {
                    $(td).attr('data-label', 'Select');
                }
            },
            {
                targets: 2, // Sr no
                createdCell: function(td) {
                    $(td).attr('data-label', 'Sr. No.');
                }
            },
            {
                targets: 3, // State Name
                createdCell: function(td) {
                    $(td).attr('data-label', 'State Name');
                }
            },
            {
                targets: 4, // City Name
                createdCell: function(td) {
                    $(td).attr('data-label', 'City Name');
                }
            },
            {
                targets: 5, // Status
                createdCell: function(td) {
                    $(td).attr('data-label', 'Status');
                }
            },
            {
                targets: 6, // Action
                createdCell: function(td) {
                    $(td).attr('data-label', 'Action');
                }
            }
        ]


    });

    // Custom Search Box
    $('#customSearch').on('keyup', function() {
        city_table.search(this.value).draw();
    });

    /* Add state */
    $('#openModal').click(function() {
        $('#adminForm')[0].reset();
        $('#modalTitle').text('City Management');
        $('#submitBtn').text('Create');
        $('input[name="city_id"]').val('');
        $('select[name="state_id"]').val('').trigger('change');
        $('#adminModal').modal('show');
        $("#adminForm .text-danger").text('');
        $('#adminForm').find('.is-invalid').removeClass('is-invalid');
    });

    //  Open Modal for Editing an Admin
    $(document).on('click', '.edit-btn', function() {
        $('#submitBtn').text('Update');
        let city_id = $(this).data('id');
        $("#adminForm .text-danger").text('');
        $('#adminForm').find('.is-invalid').removeClass('is-invalid');

        $.get('{{ route('city.edit', ':id') }}'.replace(':id', city_id),

            function(city) {
                $('#modalTitle').text('Edit City Management');

                $('input[name="city_id"]').val(city_id);
                $('input[name="status"][value="' + city.status + '"]').prop('checked', true);
                $('input[name="city_name"]').val(city.city_name);
                $('select[name="state_id"]').val(city.state_id).trigger('change');
                $('#adminModal').modal('show');
            });
    });

    // Handle Add & Edit Form Submission
    $('#adminForm').submit(function(e) {
        e.preventDefault();
        $("#adminForm .error-text").text('');
        $("#adminForm .text-danger").text('');
        let city_id = $('input[name="city_id"]').val();
        let url = city_id ? '{{ route('city.update', ':id') }}'.replace(':id', city_id) :
            "{{ route('city.store') }}";
        let method = city_id ? "PUT" : "POST";

        $.ajax({
            url: url,
            type: "POST",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: $(this).serialize() + "&_method=" + method,
            success: function(response) {
                $('#adminModal').modal('hide');
                city_table.ajax.reload();
                show_success(response.message);
            },
            error: function(response) {
                display_errors(response.responseJSON.errors);
            }
        });
    });


    $(document).on('click', '.deleteCity', function(event) {
        event.preventDefault();
        let cityId = $(this).data('id'); // Get the state ID
        let form = $('#delete-form-' + cityId); // Select the correct form
        console.log(form);
        confirmDeletion(function() {
            form.submit(); // Submit the form if confirmed
        });
    });


    function display_errors(errors) {
        // Clear previous error messages
        $("#adminForm .error-text").text('');

        // Iterate through each error
        $.each(errors, function(key, value) {
            // Handle input fields
            $('input[name=' + key + ']').each(function() {
                $(this).addClass('is-invalid'); // Add invalid class to the input field
                $('.' + key + '_error').text(value[0]).addClass(
                    'text-danger'); // Show the error message
            });

            // Handle select fields
            $('select[name=' + key + ']').each(function() {
                $(this).addClass('is-invalid'); // Add invalid class to the select field
                $('.' + key + '_error').text(value[0]).addClass(
                    'text-danger'); // Show the error message
            });
        });
    }



    /***** Bulk Delete *****/
    $('#select-all').change(function() {
        // Check/uncheck all checkboxes when the select-all checkbox is clicked
        $('.city_checkbox').prop('checked', this.checked);

    });

    $(document).on('change', '.city_checkbox', function() {
        let count = $('.city_checkbox:checked').length; // Count checked checkboxes
        $('#checked-count').text(count); // Display count in an element
        if (count > 0) {
            $('#bulk_delete_button').show();
        } else {
            $('#bulk_delete_button').hide();
        }
    });

    // Handle Bulk Delete button click
    $('#bulk_delete_button').click(function() {
        confirmDeletion(function() {
            var selectedIds = $('.city_checkbox:checked').map(function() {
                return $(this).data('id');
            }).get();

            if (selectedIds.length > 0) {
                // Make an AJAX request to delete the selected items
                $.ajax({
                    url: "{{ route('city.bulkDelete') }}",
                    method: 'POST',
                    data: {
                        ids: selectedIds, // Send the selected IDs
                        _token: '{{ csrf_token() }}' // CSRF token for security
                    },
                    success: function(response) {
                        // Swal.fire("Deleted!", response.message, "success");
                        show_success(response.message);
                        // Optionally, reload the page to reflect changes
                        city_table.ajax.reload();

                        $('#bulk_delete_button').hide();
                        // location.reload();
                    },
                    error: function(xhr, status, error) {
                        show_error('An error occurred while deleting.');
                        // alert("An error occurred while deleting.");
                    }
                });
            } else {
                alert("No items selected.");
            }
        });
        // Get the IDs of selected checkboxes
    });

    function confirmDeletion(callback) {
        Swal.fire({
            title: "Are you sure?",
            text: "You want to remove this City? Once deleted, it cannot be recovered.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            customClass: {
                popup: 'my-custom-popup',
                title: 'my-custom-title',
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-secondary',
                icon: 'my-custom-icon swal2-warning'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                callback(); // Execute callback function if confirmed
            }
        });
    }
</script>
@endsection
