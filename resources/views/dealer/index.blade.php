@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('content')


    <div class="card">
        <div class="card-header">
            <div class="row align-items-center sale-sec">
                {{-- Search --}}
                <div class="col-sm-12 col-lg-2 col-md-12 mb-3">
                    <div class="icon-form mb-4 mb-sm-0 mt-4">
                        <span class="form-icon"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control" id="customSearch" placeholder="Search">
                    </div>
                </div>

                @if (!auth()->user()->hasRole('broker'))
                    {{-- Broker filter --}}
                    <div class="col-sm-12 col-lg-2 col-md-12">
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

                    {{-- Start Date --}}
                    <div class="col-sm-12 col-lg-2 col-md-12">
                        <div class="mb-3">
                            <label class="col-form-label">Start Date</label>
                            <div class="icon-form">
                                <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                                <input type="text" name="start_date" value="{{ old('start_date') }}" id="startDate"
                                    class="form-control" placeholder="DD/MM/YY" onchange="applyFilter()">
                            </div>
                        </div>
                    </div>

                    {{-- End Date --}}
                    <div class="col-sm-12 col-lg-2 col-md-12">
                        <div class="mb-3">
                            <label class="col-form-label">End Date</label>
                            <div class="icon-form">
                                <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                                <input type="text" name="end_date" value="{{ old('end_date') }}" id="endDate"
                                    class="form-control" placeholder="DD/MM/YY" onchange="applyFilter()">
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Action Buttons --}}
                <div class="col-sm-12 col-lg-4 col-md-12">
                    <div class="d-flex align-items-center flex-wrap row-gap-2 column-gap-1 justify-content-sm-end btn-cls">
                        @if (auth()->user()->can('export-dealer'))
                            <button type="button" class="btn btn-primary" id="exportDealer">
                                <i class="ti ti-file-export me-2"></i>Export Dealer
                            </button>
                        @endif
                        @if (auth()->user()->can('add-dealer'))
                            <a href="{{ route('dealer.create') }}" class="btn btn-primary">
                                <i class="ti ti-square-rounded-plus me-2"></i>Add Dealer
                            </a>
                        @endif
                    </div>
                </div>

            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive custom-table">
                <table class="table dataTable no-footer" id="dealerTable">
                    <thead class="thead-light">
                        <tr>
                            <th hidden>ID</th>
                            <th class="no-sort">Sr No</th>
                            <th>Firm / Shop Name</th>
                            <th>Dealer Name</th>
                            <th>Broker</th>
                            <th>Mobile</th>
                            <th>City</th>
                            <th>Code No</th>
                            <th>Date</th>
                            @if (!auth()->user()->hasRole('sales'))
                                <th>Action</th>
                            @endif
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

@endsection
@section('script')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.search-dropdown').select2({
                placeholder: 'Select'
            });
        });

        const isSales = @json(auth()->user()->hasRole('sales'));

        var dealerTable = $('#dealerTable').DataTable({
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
                url: "{{ route('dealer.index') }}",
                data: function(d) {
                    d.broker_id = $('#broker_id').val();
                    d.start_date = $('#startDate').val();
                    d.end_date = $('#endDate').val();
                }
            },
            columns: [{
                    data: 'id',
                    name: 'id',
                    visible: false,
                    searchable: false
                },
                {
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'firm_shop_name',
                    name: 'firm_shop_name',
                    searchable: true
                },
                {
                    data: 'applicant_name',
                    name: 'applicant_name',
                    searchable: true
                },
                {
                    data: 'broker_id',
                    name: 'broker_id',
                    searchable: true
                },
                {
                    data: 'mobile_no',
                    name: 'mobile_no',
                    searchable: true
                },
                {
                    data: 'city_id',
                    name: 'city_id',
                    searchable: true
                },
                {
                    data: 'code_no',
                    name: 'code_no',
                    searchable: true
                },
                {
                    data: 'created_at',
                    name: 'created_at',
                    searchable: false
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    visible: !isSales
                },
            ],
        });

        /* Custom search */
        $('#customSearch').on('keyup', function() {
            dealerTable.search(this.value).draw();
        });

        /* Broker / date filters */
        $('#broker_id, #startDate, #endDate').on('change', function() {
            dealerTable.draw();
        });

        /* Export filtered dealer list */
        /* Export filtered dealer list */
        // $('#exportDealer').on('click', function() {

        //     var rowCount = dealerTable.rows({
        //         filter: 'applied'
        //     }).data().length;

        //     if (rowCount === 0) {
        //         Swal.fire({
        //             title: 'Alert!',
        //             text: 'No data available to download.',
        //             icon: 'warning',
        //             confirmButtonText: 'OK'
        //         });
        //         return;
        //     }

        //     $('#screenLoader').show();

        //     const params = dealerTable.ajax.params();
        //     params.length = -1;
        //     params.start = 0;

        //     $.ajax({
        //         url: dealerTable.ajax.url(),
        //         data: params,
        //         type: 'GET',
        //         success: function(res) {
        //             $('#screenLoader').hide();

        //             if (!res.data || res.data.length === 0) {
        //                 Swal.fire('Alert!', 'No data available to download.', 'warning');
        //                 return;
        //             }

        //             // Build CSV manually — no temp DataTable, no blob URL
        //             var headers = ['Firm / Shop Name', 'Dealer Name', 'Mobile', 'Broker', 'City',
        //                 'Code No', 'Date'
        //             ];

        //             var csvRows = [headers];
        //             res.data.forEach(function(row) {
        //                 console.log(row);
        //                 csvRows.push([
        //                     stripHtml(row.firm_shop_name_export),
        //                     stripHtml(row.applicant_name_export),
        //                     stripHtml(row.mobile_no),
        //                     stripHtml(row.broker_id),
        //                     stripHtml(row.city_id),
        //                     stripHtml(row.code_no),
        //                     stripHtml(row.created_at),
        //                 ]);

        //             });

        //             var csvContent = csvRows.map(function(row) {
        //                 return row.map(function(cell) {
        //                     return '"' + String(cell || '').replace(/"/g, '""') + '"';
        //                 }).join(',');
        //             }).join('\n');

        //             // data: URI avoids blob:http:// warning entirely
        //             var dataUri = 'data:text/csv;charset=utf-8,\uFEFF' + encodeURIComponent(csvContent);
        //             var filename = 'Dealer List - ' + new Date().toISOString().slice(0, 10) + '.csv';

        //             var link = document.createElement('a');
        //             link.setAttribute('href', dataUri);
        //             link.setAttribute('download', filename);
        //             document.body.appendChild(link);
        //             link.click();
        //             document.body.removeChild(link);
        //         },
        //         error: function() {
        //             $('#screenLoader').hide();
        //             Swal.fire('Error', 'Unable to export dealer list. Please try again.', 'error');
        //         }
        //     });
        // });


        // Bind Export Button Click for download excel data
        $('#exportDealer').on('click', function() {
            var rowCount = dealerTable.rows({
                filter: 'applied'
            }).data().length;


            $("#screenLoader").show();
            const params = dealerTable.ajax.params();
            params.length = -1; // or large number
            params.start = 0;

            $.ajax({
                url: dealerTable.ajax.url(),
                data: params,
                type: 'GET',
                success: function(res) {
                    const $tempTable = $('<table id="temp-export-table" style="display:none;"></table>')
                        .appendTo('body');
                    const tempDT = $tempTable.DataTable({
                        data: res.data,
                        columns: [
                        {
                            data: 'firm_shop_name_export',
                            title: 'Firm / Shop Name',
                        }, {
                            data: 'applicant_name_export',
                            title: 'Dealer Name',
                        }, {
                            data: 'broker_id',
                            title: 'Broker',
                        },  {
                            data: 'mobile_no',
                            title: 'Mobile',
                        }, {
                            data: 'city_id',
                            title: 'City',
                        }, {
                            data: 'code_no',
                            title: 'Code No.',
                        }, {
                            data: 'created_at',
                            title: 'Created At',
                        }],
                        dom: 'Bfrtip',
                        buttons: [{
                            extend: 'excelHtml5',
                            title: 'Dealers',
                            filename: 'Dealers -' + new Date().toISOString().slice(
                                0, 10),
                            exportOptions: {
                                format: {
                                    body: function(data) {
                                        if (typeof data === 'string') {
                                            return data.replace(/<[^>]*>/g,
                                            ''); // strip HTML
                                        }
                                        return data;
                                    }
                                }
                            }
                        }],
                        paging: false,
                        searching: false,
                        ordering: false,
                        info: false,
                        destroy: true
                    });

                    setTimeout(() => {
                        $("#screenLoader").hide();
                        tempDT.button('.buttons-excel').trigger();

                        // Cleanup after short delay
                        setTimeout(() => {
                            tempDT.destroy();
                            $tempTable.remove();
                        }, 2000);
                    }, 500);
                },
                error: function(xhr, status, error) {
                    console.error('Failed to fetch data for export.', status, error);
                }
            });

        });

        function stripHtml(html) {
            return $('<div>').html(html).text();
        }
        /* Delete confirmation */
        $(document).on('click', '.delete_d_d', function(e) {
            e.preventDefault();
            let id = $(this).data('id');
            let form = $('#delete-form-' + id);

            Swal.fire({
                title: 'Are you sure?',
                text: 'you want to delete this dealer? Once deleted, it cannot be recovered.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-secondary',
                }
            }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        });

        /* Show popup dealer detail */
        $(document).on('click', '.open-popup-model', function(e) {
            e.preventDefault();
            let id = $(this).data('id');
            let url = '{{ route('dealer.show', ':id') }}'.replace(':id', id);

            Swal.fire({
                title: 'Loading...',
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    if (response.html) {
                        Swal.fire({
                            html: response.html,
                            showCloseButton: true,
                            showConfirmButton: false,
                            width: '70%'
                        });
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Could not load details.', 'error');
                }
            });
        });


        /*** datepicker ***/
        $(document).ready(function() {
            const startPicker = flatpickr("#startDate", {
                dateFormat: "d-m-Y",
                disableMobile: true,
                // maxDate: "today",
                // defaultDate: "{{ old('start_date', isset($detail) ? \Carbon\Carbon::parse($detail->start_date)->format('d-m-Y') : now()->format('d-m-Y')) }}",
                onChange: function(selectedDates, dateStr, instance) {
                    // Set selected start date as minDate for end date
                    endPicker.set('minDate', dateStr);
                    removeTodayHighlight(selectedDates, dateStr, instance);
                },
                onReady: removeTodayHighlight,
                onMonthChange: removeTodayHighlight,
                onYearChange: removeTodayHighlight,
                onOpen: removeTodayHighlight
            });

            const endPicker = flatpickr("#endDate", {
                dateFormat: "d-m-Y",
                disableMobile: true,
                // maxDate: "today",
                // defaultDate: "{{ old('end_date', isset($detail) ? \Carbon\Carbon::parse($detail->end_date)->format('d-m-Y') : now()->format('d-m-Y')) }}",
                onReady: removeTodayHighlight,
                onMonthChange: removeTodayHighlight,
                onYearChange: removeTodayHighlight,
                onOpen: removeTodayHighlight
            });

            function removeTodayHighlight(selectedDates, dateStr, instance) {
                const todayElem = instance.calendarContainer.querySelector(".flatpickr-day.today");
                if (todayElem && !todayElem.classList.contains("selected")) {
                    todayElem.classList.remove("today");
                }
            }
        });
        /*** END ***/

        /* Flatpickr date pickers */
        $(document).ready(function() {
            const endPicker = flatpickr('#endDate', {
                dateFormat: 'd-m-Y',
                disableMobile: true,
                onChange: function(_, dateStr) {
                    dealerTable.draw();
                }
            });

            flatpickr('#startDate', {
                dateFormat: 'd-m-Y',
                disableMobile: true,
                onChange: function(_, dateStr) {
                    endPicker.set('minDate', dateStr);
                    dealerTable.draw();
                }
            });
        });
    </script>
@endsection
