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
                    <input type="text" class="form-control" id="customSearch" placeholder="Search Orders">
                </div>
            </div>
            {{-- Brand filter --}}
            <div class="col-sm-4 col-lg-2 col-md-12">
                <div class="mb-3">
                    <label class="col-form-label">Brand </label>
                    <select class="form-select select search-dropdown" name="brand_id" id="BrandId">
                        <option value="all">All Brand</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @if (!auth()->user()->hasRole('broker'))
                {{-- Broker filter --}}
                <div class="col-sm-4 col-lg-2 col-md-12">
                    <div class="mb-3">
                        <label class="col-form-label">Broker Person</label>
                        <select class="form-select select search-dropdown" name="broker_id" id="broker_id">
                            <option value="all">All Brokers</option>
                            @foreach ($brokers as $broker)
                                <option value="{{ $broker->id }}">{{ $broker->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif
            <div class="col-sm-4">
                <div class="d-flex align-items-center flex-wrap row-gap-2 justify-content-sm-end">

                    @can('add-order')
                        <a href="{{ route('order.create') }}" class="btn btn-primary">
                            <i class="ti ti-square-rounded-plus me-2"></i>Add Soda/Order
                        </a>
                    @endcan
                </div>
            </div>
        </div>
        <!-- /Search -->
    </div>
    <div class="card-body">
        <div class="table-responsive custom-table">
            <table class="table dataTable no-footer" id="order_table">
                <button class="btn btn-danger me-2" id="bulk_delete_button" style="display:none;">
                    <i class="ti ti-trash me-2"></i>Delete Selected
                </button>
                <thead class="thead-light">
                    <tr>
                        <th hidden>ID</th>
                        <th class="no-sort" scope="col">
                            <label class="checkboxs">
                                <input type="checkbox" id="select-all" class="order_checkbox">
                                <span class="checkmarks"></span>
                            </label>
                        </th>
                        <th class="no-sort" scope="col">Sr No</th>
                        <th scope="col">Order ID</th>
                        <th scope="col">Broker</th>
                        <th scope="col">Brand</th>
                        <th scope="col">Dealer</th>
                        <th scope="col">Order Date</th>
                        <th scope="col">Grand Total</th>
                        <th scope="col">Payment Status</th>
                        {{-- <th scope="col">Order Status</th> --}}
                        @canany(['edit-order', 'delete-order'])
                            <th scope="col">Action</th>
                        @endcanany
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@endsection
@section('script')
<script>
    $(document).ready(function() {
        $('.search-dropdown').select2({
            placeholder: 'Select'
        });
    });

    const isShowAction = {{ auth()->user()->canAny(['edit-order', 'delete-order'])? 'true': 'false' }};
    const isShowCheckbox = {{ auth()->user()->can('delete-order') ? 'true' : 'false' }};

    var order_table = $('#order_table').DataTable({
        pageLength: 10,
        deferRender: true,
        processing: true,
        serverSide: true,
        responsive: true,
        dom: 'lrtip',
        order: [
            [0, 'desc']
        ],
        ajax: {
            url: "{{ route('order.index') }}",
            data: function(d) {
                d.broker_id = $('#broker_id').val();
                d.brand_id = $('#BrandId').val();
            }
        },
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
                searchable: false,
                visible: isShowCheckbox
            },
            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            {
                data: 'unique_order_id',
                name: 'unique_order_id',
                searchable: true
            },
            {
                data: 'broker_name',
                name: 'broker_name',
                orderable: true,
                searchable: false
            },
            {
                data: 'brand_name',
                name: 'brand_name',
                orderable: true,
                searchable: false
            },
            {
                data: 'dealer_name',
                name: 'dealer_name',
                orderable: true,
                searchable: false
            },
            {
                data: 'order_date',
                name: 'order_date',
                searchable: false
            },
            {
                data: 'grand_total',
                name: 'grand_total',
                searchable: false
            },
            {
                data: 'payment_status',
                name: 'payment_status',
                orderable: false,
                searchable: true
            },
            // { data: 'order_status',   name: 'order_status',   orderable: false, searchable: false },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                visible: isShowAction
            },
        ]
    });

    /* Broker /Brand */
    $('#broker_id, #BrandId').on('change', function() {
        order_table.draw();
    });

    /* Custom search */
    $('#customSearch').on('keyup', function() {
        order_table.search(this.value).draw();
    });

    /* Delete single */
    $(document).on('click', '.deleteOrder', function(e) {
        e.preventDefault();
        let orderId = $(this).data('id');
        confirmDeletion(function() {
            $('#order-delete-form-' + orderId).submit();
        });
    });

    /* Bulk delete — select all */
    $('#select-all').on('change', function() {
        $('.order_checkbox').prop('checked', this.checked);
        toggleBulkBtn();
    });

    $(document).on('change', '.order_checkbox', function() {
        toggleBulkBtn();
    });

    function toggleBulkBtn() {
        let count = $('.order_checkbox:checked').not('#select-all').length;
        count > 0 ? $('#bulk_delete_button').show() : $('#bulk_delete_button').hide();
    }

    $('#bulk_delete_button').on('click', function() {
        confirmDeletion(function() {
            var selectedIds = $('.order_checkbox:checked').not('#select-all').map(function() {
                return $(this).data('id');
            }).get();

            if (selectedIds.length > 0) {
                $.ajax({
                    url: "{{ route('order.bulkDelete') }}",
                    method: 'POST',
                    data: {
                        ids: selectedIds,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        show_success(response.message);
                        order_table.ajax.reload();
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

    function confirmDeletion(callback) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'You want to remove this Order? Once deleted, it cannot be recovered.',
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
            if (result.isConfirmed) callback();
        });
    }
</script>
@endsection
