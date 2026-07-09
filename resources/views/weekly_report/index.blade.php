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
                <div class="common-hed-form">
                    <label class="col-form-label">From</label>
                    <input type="date" class="form-control" id="filterDateFrom">
                </div>
                <div class="common-hed-form">
                    <label class="col-form-label">To</label>
                    <input type="date" class="form-control" id="filterDateTo">
                </div>
            </div>
            <div class="cls-form-right">
                <div class="comm-header-right-btn">
                    @can('add-weekly-report')
                        <a href="{{ route('weekly-report.create') }}" class="btn btn-primary">
                            <i class="ti ti-square-rounded-plus me-2"></i>Generate Report
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive custom-table">
            <table class="table dataTable no-footer" id="weekly_report_table">
                <thead class="thead-light">
                    <tr>
                        <th hidden>ID</th>
                        <th class="no-sort" scope="col">Sr No</th>
                        <th scope="col">Report Date</th>
                        <th scope="col">Rows</th>
                        <th scope="col">Total Qty (bags)</th>
                        <th scope="col">Already Produced</th>
                        <th scope="col">Difference</th>
                        <th scope="col">Hours</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@endsection

@push('datatable-scripts')
    @include('partials.datatable-scripts')
@endpush

@section('script')
<script>
withDataTable(function () {
    var weeklyAjax = buildDataTableAjax("{{ route('weekly-report.index') }}", {
        data: function (d) {
            d.date_from = $('#filterDateFrom').val();
            d.date_to = $('#filterDateTo').val();
        }
    });

    var table = $('#weekly_report_table').DataTable({
        pageLength: 10,
        deferRender: true,
        processing: true,
        serverSide: true,
        responsive: true,
        dom: 'lrtip',
        ajax: weeklyAjax,
        columns: [
            { data: 'id', name: 'id', visible: false, searchable: false },
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'report_date', name: 'report_date' },
            { data: 'items_summary', name: 'items_summary', orderable: false, searchable: false },
            { data: 'total_qty', name: 'total_qty', orderable: false, searchable: false },
            { data: 'already_produced_col', name: 'already_produced_col', orderable: false, searchable: false },
            { data: 'difference', name: 'difference', orderable: false, searchable: false },
            { data: 'hours', name: 'hours', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[0, 'desc']],
    });
    weeklyAjax._bindTable(table);

    bindDebouncedDataTableSearch('#customSearch', table);

    $('#filterDateFrom, #filterDateTo').on('change', function () {
        table.ajax.reload();
    });

    $(document).on('click', '.delete-weekly-report', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Delete report?',
            text: 'This will remove the day report and all pending rows.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-secondary',
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                $('#delete-weekly-report-form-' + id).submit();
            }
        });
    });
});
</script>
@endsection
