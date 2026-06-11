@extends('layouts.main')
@section('content')
@section('title')
    <h3>{{ $page_title }}</h3>
@endsection
<!-- Welcome + quick actions -->
<div class="mb-4">
    <div class="welcome-wrap">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
            <div class="mb-3">
                <h2 class="mb-1 text-white">Welcome Back, {{ $user_name }}</h2>
                <p class="text-light"></p>
            </div>
        </div>
    </div>
    @can('add-order')
        <div class="d-flex justify-content-end mt-3">
            <a href="javascript:void(0)" class="btn btn-primary btn-md" data-bs-toggle="modal"
                data-bs-target="#dashboardDispatchModal">
                <i class="ti ti-truck me-1"></i>Soda/Order Dispatch
            </a>
        </div>
    @endcan
</div>
<div class="row detials-gc-user">

    @can('total-dealers')
        <div class="col-xl-3 col-sm-6 d-flex">
            <div class="card flex-fill total-dealers">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="avatar avatar-md rounded bg-dark mb-3">
                            <i class="ti ti-medal fs-16"></i>
                        </span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h2 class="mb-1">{{ $total_dealers }}</h2>
                            <p class="fs-13">Total Dealers</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    @can('total-brokers')
        <div class="col-xl-3 col-sm-6 d-flex">
            <div class="card flex-fill total-broker">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="avatar avatar-md rounded bg-dark mb-3">
                            <i class="ti ti-user-up fs-16"></i>
                        </span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h2 class="mb-1">{{ $total_broker }}</h2>
                            <p class="fs-13">Total Broker</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    @can('total-soda-order')
        <div class="col-xl-3 col-sm-6 d-flex">
            <div class="card flex-fill total-soda-order">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="avatar avatar-md rounded bg-dark mb-3">
                            <i class="ti ti-user-star fs-16"></i>
                        </span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h2 class="mb-1">{{ $total_soda_order }}</h2>
                            <p class="fs-13">Total Soda/Order</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    @can('total-dispatch-request')
        <div class="col-xl-3 col-sm-6 d-flex">
            <div class="card flex-fill total-dispatch-request">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="avatar avatar-md rounded bg-dark mb-3">
                            <i class="ti ti-businessplan fs-16"></i>
                        </span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h2 class="mb-1">{{ $total_dispatch_order }}</h2>
                            <p class="fs-13">Total Dispatch request</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan
</div>

<div class="row">
    <div class="col-lg-6 d-flex">
        <!--col-xxl-3 -->
        {{-- @can('total-orders')
            <div class="card flex-fill total-orders">
                <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap">
                    <h5 class="mb-2">Total Orders</h5>
                </div>
                <div class="card-body pb-0">
                    <div id="company-chart">
                    </div>
                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                        <div class="mb-1">
                            <h2 class="mb-1">{{ $total_soda_order }}</h2>
                        </div>
                        <p class="fs-13 text-gray-9 d-flex align-items-center mb-1"><i
                                class="ti ti-circle-filled me-1 fs-6 text-primary"></i>Orders</p>
                    </div>
                </div>
            </div>
        @endcan --}}
    </div>
    {{-- <div class="col-lg-6 d-flex">
        @can('revenue')
            <div class="card flex-fill revenue">
                <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap">
                    <h5 class="mb-2">Revenue</h5>
                </div>
                <div class="card-body pb-0">
                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                        <div class="mb-1">
                            <h2 class="mb-1">₹0</h2>
                        </div>
                        <p class="fs-13 text-gray-9 d-flex align-items-center mb-1"><i
                                class="ti ti-circle-filled me-1 fs-6 text-primary"></i>Revenue</p>
                    </div>
                    <div id="revenue-income"></div>
                </div>
            </div>
        @endcan
    </div> --}}
</div>

<div class="row">
    <!------------ Recent Dealers ---------------->
    @can('recent-dealers')
        <div class="col-xxl-4 col-xl-6 d-flex">
            <div class="card flex-fill recent-cards">
                <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap">
                    <h5 class="mb-2"> Recent Dealers </h5>
                    <a href="{{ route('dealer.index') }}" class="btn btn-light btn-md mb-2">View All</a>
                </div>
                <div class="card-body pb-2">
                    @foreach ($dealers->take(5) as $d)
                        <div class="d-flex justify-content-between flex-wrap dashboard-card">
                            <div class="d-flex align-items-center mb-2">
                                <a href="{{ !empty($d->user) && !empty($d->user->profile_picture)
                                    ? asset('storage/profile_pictures/' . $d->user->profile_picture)
                                    : asset('images/default-user.png') }}"
                                    class="avatar avatar-sm border flex-shrink-0" target="_blank">

                                    <img id="profilePreview"
                                        src="{{ !empty($d->user) && !empty($d->user->profile_picture)
                                            ? asset('storage/profile_pictures/' . $d->user->profile_picture)
                                            : asset('images/default-user.png') }}"
                                        alt="Profile Image" class="img-thumbnail mb-2">
                                </a>
                                <div class="ms-2 flex-fill">
                                    <h6 class="fs-medium text-truncate mb-1">
                                        <a href="{{ route('dealer.edit', $d->id) }}">
                                            {{ $d->user->name }}
                                        </a>
                                    </h6>
                                    <p class="fs-13">{{ $d->city->city_name }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endcan
    <!------------ Recent Soda/Orders ------------>
    @can('recent-orders')
        <div class="col-xxl-4 col-xl-12 d-flex">
            <div class="card flex-fill recent-cards">
                <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="mb-2">Recent Soda/Orders</h5>
                    <a href="{{ route('order.index') }}" class="btn btn-light btn-md mb-2">View All</a>
                </div>
                <div class="card-body pb-2">
                    @if ($soda_order->isEmpty())
                        <p class="text-muted center">No recent soda orders found.</p>
                    @else
                        @foreach ($soda_order->sortByDesc('created_at')->take(5) as $order)
                            <div class="d-flex justify-content-between flex-wrap dashboard-card">
                                <div class="d-flex align-items-center mb-2">
                                    {{-- <a href="#" class="avatar avatar-sm border flex-shrink-0" target="_blank">
                                        <img id="profilePreview" src="assets/images/avatar-14.png" alt="Profile Image"
                                            class="img-thumbnail mb-2">
                                    </a> --}}
                                    <div class="ms-2 flex-fill">
                                        <h6 class="fs-medium text-truncate mb-1">
                                            <a href="{{ route('order.edit', $order->id) }}">
                                                {{ $order->dealer->user->name ?? '—' }}
                                            </a>
                                        </h6>
                                        <p class="fs-13 d-inline-flex align-items-center">
                                            <a href="{{ route('order.edit', $order->id) }}">
                                                <span class="text-info">{{ $order->unique_order_id ?? '—' }}</span>
                                            </a>
                                            <i class="ti ti-circle-filled fs-4 text-primary mx-1"></i>
                                            {{ $order->order_date->format('d M Y') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-sm-end mb-2">
                                    <h6 class="mb-1">{{-- $order->totalAmount() --}}</h6>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    @endcan

    <!------------ Recent Dispatch Request ------->
    @can('recent-dispatch-request')
        <div class="col-xxl-4 col-xl-6 d-flex">
            <div class="card flex-fill recent-cards">
                <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap">
                    <h5 class="mb-2">Recent Dispatch Request</h5>
                    <a href="{{ route('dispatch.index') }}" class="btn btn-light btn-md mb-2">View All</a>
                </div>
                <div class="card-body pb-2">
                    @if ($dispatch_order->isEmpty())
                        <p class="text-muted center">No recent dispatch requests found.</p>
                    @else
                        <div>
                            @foreach ($dispatch_order->sortByDesc('created_at')->take(5) as $dispatch_order)
                                <div class="d-flex justify-content-between flex-wrap dashboard-card">
                                    <div class="d-flex align-items-center mb-2">
                                        {{-- <a href="assets/images/avatar-14.png" class="avatar avatar-sm border flex-shrink-0"
                                            target="_blank">
                                            <img id="profilePreview" src="assets/images/avatar-14.png" alt="Profile Image"
                                            class="img-thumbnail mb-2">
                                    </a> --}}
                                        <div class="ms-2 flex-fill">
                                            <h6 class="fs-medium text-truncate mb-1">
                                                <a href="{{ route('dispatch.orderHistory', $dispatch_order->order_id) }}">
                                                    {{ $dispatch_order->product->name }}
                                                    <span class="text-info">
                                                        <small>({{ \App\Support\ProductUnit::formatWithUnit($dispatch_order->no_of_bags, $dispatch_order->product?->unit) }})</small>
                                                    </span>
                                                </a>
                                            </h6>
                                            <p class="fs-13">
                                                <a href="{{ route('dispatch.orderHistory', $dispatch_order->order_id) }}">
                                                    <span class="text-info">
                                                        {{ $dispatch_order->order->unique_order_id }}
                                                    </span>
                                                </a>
                                                <i class="ti ti-circle-filled fs-4 text-primary mx-1"></i>
                                                {{ $dispatch_order->dispatch_date->format('d M Y') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
@endcan

<!------------ Recent Broker ----------------->
{{-- @can('recent-broker')
        <div class="col-xxl-4 col-xl-6 d-flex">
            <div class="card flex-fill recent-cards">
                <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap">
                    <h5 class="mb-2">Recent Broker</h5>
                    <a href="{{ route('users.index', ['type' => 'broker']) }}" class="btn btn-light btn-md mb-2">View
                        All</a>
                </div>
                <div class="card-body pb-2">
                    <div>
                        <div>
                            @foreach ($brokers->take(5) as $b)
                                <div class="d-flex justify-content-between flex-wrap dashboard-card">
                                    <div class="d-flex align-items-center mb-2">
                                        <a href="{{ !empty($b->profile_picture)
                                            ? asset('storage/profile_pictures/' . $b->profile_picture)
                                            : asset('images/default-user.png') }}"
                                            class="avatar avatar-sm border flex-shrink-0" target="_blank">
                                            <img id="profilePreview"
                                                src="{{ !empty($b->profile_picture)
                                                    ? asset('storage/profile_pictures/' . $b->profile_picture)
                                                    : asset('images/default-user.png') }}"
                                                alt="Profile Image" class="img-thumbnail mb-2">
                                        </a>
                                        <div class="ms-2 flex-fill">
                                            <h6 class="fs-medium text-truncate mb-1"><a
                                                    href="{{ route('users.edit', ['type' => 'broker', 'id' => $b->id]) }}">
                                                    {{ $b->name }}</a>
                                            </h6>
                                            <p class="fs-13">{{ $b->email }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan --}}

<!------------ Recent Transporter ------------>
{{-- @can('recent-transporter')
        <div class="col-xxl-4 col-xl-6 d-flex">
            <div class="card flex-fill recent-cards">
                <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap">
                    <h5 class="mb-2">Recent Transporter </h5>
                    <a href="{{ route('users.index', ['type' => 'transporter']) }}"
                        class="btn btn-light btn-md mb-2">View All</a>
                </div>
                <div class="card-body pb-2">
                    <div>
                        <div>
                            @foreach ($transporters->take(5) as $t)
                                <div class="d-flex justify-content-between flex-wrap dashboard-card">
                                    <div class="d-flex align-items-center mb-2">
                                        <a href="{{ !empty($t->profile_picture)
                                            ? asset('storage/profile_pictures/' . $t->profile_picture)
                                            : asset('images/default-user.png') }}"
                                            class="avatar avatar-sm border flex-shrink-0" target="_blank">
                                            <img id="profilePreview"
                                                src="{{ !empty($t->profile_picture)
                                                    ? asset('storage/profile_pictures/' . $t->profile_picture)
                                                    : asset('images/default-user.png') }}"
                                                alt="Profile Image" class="img-thumbnail mb-2">
                                        </a>
                                        <div class="ms-2 flex-fill">
                                            <h6 class="fs-medium text-truncate mb-1"><a
                                                    href="{{ route('users.edit', ['type' => 'transporter', 'id' => $t->id]) }}">
                                                    {{ $t->name }}</a>
                                            </h6>
                                            <p class="fs-13">{{ $t->email }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan --}}
</div>

@can('add-dispatch')
    @include('dispatch_management.partials.dashboard_dispatch_modal')
@endcan

@endsection
@section('script')
    @can('add-dispatch')
        @include('dispatch_management.partials.status-field-script')
        <script>
            $(document).ready(function() {
                var TRUCKS_URL = '{{ route('dispatch.transporterTrucks', ':id') }}';
                var dashboardDispatchEligible = true;

                function loadTrucksForTransporter(transporterId, $truckSelect, $contactInput, opts) {
                    opts = opts || {};
                    if (!transporterId) {
                        $truckSelect.html('<option value="">-- Select Transporter First --</option>').prop('disabled', true);
                        return;
                    }
                    $truckSelect.html('<option value="">Loading trucks…</option>').prop('disabled', true);
                    $.get(TRUCKS_URL.replace(':id', transporterId))
                        .done(function(data) {
                            var html = '<option value="">-- Select Truck Number --</option>';
                            if (data.trucks && data.trucks.length) {
                                $.each(data.trucks, function(i, truck) {
                                    html += '<option value="' + $('<span>').text(truck.truck_number).html() + '">' +
                                        $('<span>').text(truck.truck_number).html() + '</option>';
                                });
                            } else {
                                html += '<option value="" disabled>No trucks found for this transporter</option>';
                            }
                            $truckSelect.html(html).prop('disabled', false);
                            if (opts.setTruckNumber) {
                                if ($truckSelect.find('option[value="' + opts.setTruckNumber + '"]').length === 0) {
                                    $truckSelect.append('<option value="' + $('<span>').text(opts.setTruckNumber)
                                        .html() + '">' + $('<span>').text(opts.setTruckNumber).html() + '</option>');
                                }
                                $truckSelect.val(opts.setTruckNumber);
                            }
                            if (opts.setDriverContact !== undefined && opts.setDriverContact !== null) {
                                $contactInput.val(opts.setDriverContact);
                            } else if (opts.autoFillContact && data.phone) {
                                $contactInput.val(data.phone);
                            }
                        })
                        .fail(function() {
                            $truckSelect.html('<option value="">-- Select Truck Number --</option>').prop('disabled', false);
                        });
                }

                function resetProductSelect(message) {
                    $('#dashboardDispatchOrderItemId')
                        .html('<option value="">' + (message || '-- Select Order First --') + '</option>')
                        .prop('disabled', true)
                        .val('');
                    $('#dashboardDispatchProductId').val('');
                    $('#dashboardDispatchPendingHint').text('');
                }

                function updateDashboardQtyLabel(unit) {
                    var label = unit ? ('No of ' + unit) : @json(\App\Support\ProductUnit::quantityFieldLabel());
                    $('#dashboardDispatchQtyLabel').text(label);
                }

                function populateProductSelect(items) {
                    var $sel = $('#dashboardDispatchOrderItemId');
                    var html = '<option value="">-- Select Product --</option>';
                    $.each(items, function(i, item) {
                        var disabled = item.disabled ? ' disabled' : '';
                        html += '<option value="' + item.id + '" data-product-id="' + item.product_id +
                            '" data-product-unit="' + (item.product_unit || '') +
                            '" data-pending="' + item.pending + '"' + disabled + '>' +
                            item.product_name + ' — Ordered: ' + item.qty + ', Pending: ' + item.pending +
                            '</option>';
                    });
                    $sel.html(html).prop('disabled', false);
                    updateDashboardQtyLabel('');
                }

                function showBlockedAlert(blocking) {
                    var msg = 'Order <strong>' + blocking.unique_order_id + '</strong> (' + blocking.order_date +
                        ') must be fully dispatched first. ' +
                        '<a href="' + blocking.history_url + '" class="alert-link">Go to pending order</a>';
                    $('#dashboardDispatchBlockedAlert').html(msg).removeClass('d-none');
                    dashboardDispatchEligible = false;
                    resetProductSelect('Dispatch blocked for this order');
                    $('#dashboardSaveDispatchBtn').prop('disabled', true);
                }

                function hideBlockedAlert() {
                    $('#dashboardDispatchBlockedAlert').addClass('d-none').empty();
                    dashboardDispatchEligible = true;
                    $('#dashboardSaveDispatchBtn').prop('disabled', false);
                }

                function loadOrderItems(orderId) {
                    hideBlockedAlert();
                    resetProductSelect(orderId ? 'Loading products…' : '-- Select Order First --');
                    if (!orderId) {
                        return;
                    }
                    var formUrl = $('#dashboardDispatchOrderId option:selected').data('form-url');
                    $.get(formUrl)
                        .done(function(data) {
                            if (!data.eligible) {
                                showBlockedAlert(data.blocking_order);
                                return;
                            }
                            populateProductSelect(data.items || []);
                            var savedItem = '{{ old('order_item_id') }}';
                            if (savedItem) {
                                $('#dashboardDispatchOrderItemId').val(savedItem).trigger('change');
                            }
                        })
                        .fail(function() {
                            resetProductSelect('Could not load products');
                        });
                }

                flatpickr('#dashboardDispatchDate', {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd-m-Y',
                    allowInput: true,
                    defaultDate: '{{ old('dispatch_date') }}' || 'today',
                    onChange: function() {
                        $('#dispatch_date-error').text('');
                        $('#dashboardDispatchDate').next('.flatpickr-input').removeClass('is-invalid');
                    }
                });

                $('#dashboardDispatchOrderId').on('change', function() {
                    loadOrderItems($(this).val());
                });

                $('#dashboardDispatchOrderItemId').on('change', function() {
                    var $opt = $(this).find(':selected');
                    var pending = parseInt($opt.data('pending')) || 0;
                    var unit = $opt.data('product-unit') || '';
                    $('#dashboardDispatchProductId').val($opt.data('product-id') || '');
                    updateDashboardQtyLabel(unit);
                    $('#dashboardDispatchPendingHint').text($opt.val() ? 'Available pending qty: ' + pending + (unit ? ' ' + unit : '') : '');
                });

                $('#dashboardDispatchTransport').on('change', function() {
                    loadTrucksForTransporter($(this).val(), $('#dashboardDispatchTruckNumber'),
                        $('#dashboardDispatchDriverContact'), {
                            autoFillContact: true
                        });
                });

                $('#dashboardDispatchModal').on('show.bs.modal', function() {
                    $('#dashboardDispatchTruckNumber')
                        .html('<option value="">-- Select Transporter First --</option>')
                        .prop('disabled', true);
                });

                $.validator.addMethod('maxDashboardPending', function(value) {
                    if (!dashboardDispatchEligible) return false;
                    var $opt = $('#dashboardDispatchOrderItemId').find(':selected');
                    if (!$opt.val()) return true;
                    return parseInt(value) <= (parseInt($opt.data('pending')) || 0);
                }, 'The entered quantity cannot exceed the pending quantity.');

                $('#dashboardDispatchForm').validate({
                    ignore: ':hidden:not(#dashboardDispatchDate)',
                    rules: {
                        order_id: {
                            required: true
                        },
                        order_item_id: {
                            required: true
                        },
                        no_of_bags: {
                            required: true,
                            number: true,
                            min: 1,
                            maxDashboardPending: true
                        },
                        dispatch_date: {
                            required: true
                        },
                        transport_id: {
                            required: true
                        },
                        truck_number: {
                            required: true
                        },
                        driver_contact: {
                            required: true
                        },
                        status: {
                            required: true
                        },
                        partial_paid_amount: {
                            dispatchPartialAmount: true
                        },
                    },
                    messages: {
                        order_id: {
                            required: 'Please select an order.'
                        },
                        order_item_id: {
                            required: 'Please select a product.'
                        },
                        no_of_bags: {
                            required: @json(\App\Support\ProductUnit::requiredMessage())
                        },
                        dispatch_date: {
                            required: 'Please select a dispatch date.'
                        },
                        transport_id: {
                            required: 'Please select a transporter.'
                        },
                        truck_number: {
                            required: 'Please select a truck number.'
                        },
                        driver_contact: {
                            required: 'Driver contact is required.'
                        },
                        status: {
                            required: 'Please select a payment status.'
                        },
                        partial_paid_amount: {
                            dispatchPartialAmount: 'Please enter the paid amount.'
                        },
                    },
                    errorElement: 'span',
                    errorClass: 'text-danger small d-block mt-1',
                    errorPlacement: function(error, element) {
                        if (element.attr('name') === 'partial_paid_amount') {
                            error.appendTo('#dashboard_partial_paid_amount-error');
                            return;
                        }
                        var $target = $('#' + element.attr('name') + '-error');
                        if ($target.length) {
                            $target.html(error);
                        } else if (element.attr('name') === 'status') {
                            error.appendTo('#status-error');
                        } else if (element.attr('id') === 'dashboardDispatchDate') {
                            error.appendTo('#dispatch_date-error');
                        } else {
                            error.insertAfter(element);
                        }
                    },
                    highlight: function(el) {
                        var $e = $(el);
                        $e.attr('id') === 'dashboardDispatchDate' ? $e.next('.flatpickr-input').addClass(
                            'is-invalid') : $e.addClass('is-invalid');
                    },
                    unhighlight: function(el) {
                        var $e = $(el);
                        $e.attr('id') === 'dashboardDispatchDate' ? $e.next('.flatpickr-input').removeClass(
                            'is-invalid') : $e.removeClass('is-invalid');
                    },
                    submitHandler: function(form) {
                        if (!dashboardDispatchEligible) {
                            return false;
                        }
                        form.submit();
                    },
                });

                @if (session('open_dashboard_dispatch_modal') || ($errors->any() && old('from_dashboard')))
                    (function() {
                        var savedTransporter = '{{ old('transport_id') }}';
                        var savedTruck = '{{ old('truck_number') }}';
                        var savedContact = '{{ old('driver_contact') }}';
                        var savedOrder = '{{ old('order_id') }}';

                        if (savedOrder) {
                            $('#dashboardDispatchOrderId').val(savedOrder);
                            loadOrderItems(savedOrder);
                        }

                        if (savedTransporter) {
                            $('#dashboardDispatchTransport').val(savedTransporter);
                            loadTrucksForTransporter(savedTransporter, $('#dashboardDispatchTruckNumber'),
                                $('#dashboardDispatchDriverContact'), {
                                    setTruckNumber: savedTruck,
                                    setDriverContact: savedContact || null
                                });
                        }

                        (new bootstrap.Modal(document.getElementById('dashboardDispatchModal'))).show();
                    })();
                @endif
            });
        </script>
    @endcan
@endsection
