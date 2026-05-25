@extends('layouts.main')
@section('content')
@section('title')
    <h3>{{ $page_title }}</h3>
@endsection
<!-- Welcome Wrap -->
<div class="welcome-wrap mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap">
        <div class="mb-3">
            <h2 class="mb-1 text-white">Welcome Back, {{ $user_name }}</h2>
            <p class="text-light"></p>
        </div>
    </div>
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
                <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap">
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
                                                <spa class="text-info">{{ $order->unique_order_id ?? '—' }}</spa>
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
                                                        <small>(bag/ton {{ $dispatch_order->no_of_bags }})</small>
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
@endsection
@section('script')
@endsection
