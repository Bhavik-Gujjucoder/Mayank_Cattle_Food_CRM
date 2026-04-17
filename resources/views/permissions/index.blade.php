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
                <button class="btn btn-primary" id="bulk_delete_button" style="display: none;">Delete Selected</button>
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
    $('#customSearch').on('keyup', function() {
        permission_table.search(this.value).draw();
    });


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


    // /***  Open Modal for Editing an Admin ***/
    // $(document).on('click', '.edit-btn', function() {
    //     let user_id = $(this).data('id');
    //     $("#adminForm .text-danger").text('');
    //     $('#adminForm').find('.is-invalid').removeClass('is-invalid');

    //     $.get('{{ route('grade.edit', ':id') }}'.replace(':id', user_id), function(user) {
    //         $('#modalTitle').text('Edit Permission');
    //         $('#submitBtn').text('Update');
    //         $('input[name="user_id"]').val(user_id);
    //         $('input[name="name"]').val(user.name);
    //         $('input[name="status"][value="' + user.status + '"]').prop('checked', true);
    //         $('#adminModal').modal('show');
    //     });
    // });


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


    /***** Bulk Delete *****/
    // $('#select-all').change(function() {
    //     // Check/uncheck all checkboxes when the select-all checkbox is clicked
    //     $('.grade_checkbox').prop('checked', this.checked);

    // });


    // $(document).on('change', '.permission_checkbox', function() {
    //     let count = $('.permission_checkbox:checked').length; // Count checked checkboxes
    //     $('#checked-count').text(count); // Display count in an element
    //     if (count > 0) {
    //         $('#bulk_delete_button').show();
    //     } else {
    //         $('#bulk_delete_button').hide();
    //     }
    // });


    // Handle Bulk Delete button click
    // $('#bulk_delete_button').click(function() {
    //     confirmDeletion(function() {
    //         var selectedIds = $('.grade_checkbox:checked').map(function() {
    //             return $(this).data('id');
    //         }).get();

    //         if (selectedIds.length > 0) {
    //             // Make an AJAX request to delete the selected items
    //             $.ajax({
    //                 url: "{{ route('grade.bulkDelete') }}",
    //                 method: 'POST',
    //                 data: {
    //                     ids: selectedIds, // Send the selected IDs
    //                     _token: '{{ csrf_token() }}' // CSRF token for security
    //                 },
    //                 success: function(response) {
    //                     // Swal.fire("Deleted!", response.message, "success");
    //                     show_success(response.message);
    //                     // Optionally, reload the page to reflect changes
    //                     permission_table.ajax.reload();
    //                     // location.reload();
    //                     $('#bulk_delete_button').hide();
    //                 },
    //                 error: function(xhr, status, error) {
    //                     show_error('An error occurred while deleting.');
    //                     // alert("An error occurred while deleting.");
    //                 }
    //             });
    //         } else {
    //             alert("No items selected.");
    //         }
    //     });
    // });


</script>
@endsection



{{-- @extends('layouts.main')

@section('content')
<div class="container">
    <h2>Permissions</h2>
    <a href="{{ route('permissions.create') }}" class="btn btn-primary mb-3">Add Permission</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($permissions as $permission)
            <tr>
                <td>{{ $permission->name }}</td>
                <td>
                    <form action="{{ route('permissions.destroy', $permission->id) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this permission?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection --}}
