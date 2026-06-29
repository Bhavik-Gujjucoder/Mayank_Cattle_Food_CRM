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

                    <a href="javascript:void(0)" id="openModal" class="btn btn-primary"><i
                            class="ti ti-square-rounded-plus me-2"></i>Add Permission</a>
                </div>
            </div>
        </div>
        <!-- /Search -->
    </div>
    <div class="card-body">

        <!-- Manage Users List -->
        <div class="table-responsive custom-table">
            <table class="table dataTable no-footer" id="permission">
                <button class="btn btn-primary" id="bulk_delete_button" style="display: none;"> <i class="ti ti-trash me-1"></i>Delete Selected</button>
                <thead class="thead-light">
                    <tr>
                        <th hidden>ID</th>
                        <th scope="col"></th>
                        <th scope="col">Name</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
        <!-- /Manage Users List -->
    </div>
</div>

<!--  Single Modal for Add & Edit -->
<div class="modal fade" id="adminModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Create Permission</h5>
                <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>

            </div>
            <div class="modal-body">
                <form id="adminForm">
                    @csrf
                    <input type="hidden" name="permission_id">
                    <div class="mb-3">
                        <label class="col-form-label">Permission Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter Grade name">
                        <span class="name_error"></span>
                    </div>

                    <div class="float-end">
                        <button type="button" class="btn btn-light me-2 close_poup"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
@section('script')
<script>
    var permission_table = $('#permission').DataTable({
        "pageLength": 10,
        deferRender: true, // Prevents unnecessary DOM rendering
        processing: true,
        serverSide: true,
        responsive: true,
        dom: 'lrtip',
        order: [[0, 'desc']], // Order by 'id' in descending order
        ajax: "{{ route('permissions.index') }}",
        columns: [
            { data: 'id', name: 'id', visible: false, searchable: false },
            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            {
                data: 'name',
                name: 'name',
                searchable: true
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            },
        ],

    });


    /*** Custom Search Box ***/
    bindDebouncedDataTableSearch('#customSearch', permission_table);


    /***  Open Modal for Adding a New Permission ***/
    $('#openModal').click(function() {
        $('#adminForm')[0].reset();
        $('#modalTitle').text('Create Permission');
        $('#submitBtn').text('Create');
        $('input[name="user_id"]').val('');
        $('#adminModal').modal('show');
        $("#adminForm .text-danger").text('');
        $('#adminForm').find('.is-invalid').removeClass('is-invalid');

    });


    /***  Handle Add & Edit Form Submission ***/
    $('#adminForm').submit(function(e) {
        e.preventDefault();
        let user_id = $('input[name="permission_id"]').val();
        let url = user_id ? '{{ route('permissions.update', ':id') }}'.replace(':id', user_id) :
            "{{ route('permissions.store') }}";
        let method = user_id ? "PUT" : "POST";

        $.ajax({
            url: url,
            type: "POST",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Add this line
            },
            data: $(this).serialize() + "&_method=" + method,
            success: function(response) {
                $('#adminModal').modal('hide');
                permission_table.ajax.reload();
                show_success(response.message);
            },
            error: function(response) {
                display_errors(response.responseJSON.errors);
                // show_error('Error occurred!');
            }
        });
    });


    /***** conformation *****/
    function confirmDeletion(callback) {
        Swal.fire({
            title: "Are you sure?",
            text: "You want to remove this Permission? Once deleted, it cannot be recovered.",
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

    /*** single grade delete ***/
    $(document).on('click', '.deletePermission', function(event) {
        event.preventDefault();
        let userId = $(this).data('id');        // Get the user ID
        let form = $('#delete-form-' + userId); // Select the correct form
        console.log(form);

        confirmDeletion(function() {
            form.submit(); // Submit the form if confirmed
        });
    });


    function display_errors(errors) {
        $("#adminForm .error-text").text('');
        $.each(errors, function(key, value) {
            $('input[name=' + key + ']').addClass('is-invalid');
            $('.' + key + '_error').text(value[0]).addClass('text-danger');
        });
    }


</script>
@endsection



