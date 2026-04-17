@extends('layouts.main')
@section('content')
@section('title')
    {{ $page_title }}
@endsection

     {{-- <div class="" style="min-height: 945px;">
            <div class="content"> --}}
               {{-- <div class="row">
                  <div class="col-md-12">
                     <div class="page-header">
                        <div class="row align-items-center ">
                           <div class="col-md-4">
                              <h3 class="page-title">
                                 Super Admin Dashboard
                              </h3>
                           </div>
                        </div>
                     </div>
                  </div>
               </div> --}}
               <!-- Welcome Wrap -->
               <div class="welcome-wrap mb-4">
                  <div class=" d-flex align-items-center justify-content-between flex-wrap">
                     <div class="mb-3">
                        <h2 class="mb-1 text-white">Welcome Back, {{ Auth::user()->name }}</h2>
                        <p class="text-light"></p>
                     </div>
                  </div>
               </div>
               <div class="row detials-gc-user">
                  <div class="col-xl-3 col-sm-6 d-flex">
                     <div class="card flex-fill">
                        <div class="card-body">
                           <div class="d-flex align-items-center justify-content-between">
                              <span class="avatar avatar-md rounded bg-dark mb-3">
                              <i class="ti ti-medal fs-16"></i>
                              </span>
                           </div>
                           <div class="d-flex align-items-center justify-content-between">
                              <div>
                                 <h2 class="mb-1">0</h2>
                                 <p class="fs-13">Total Dealers</p>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
                  <div class="col-xl-3 col-sm-6 d-flex">
                     <div class="card flex-fill">
                        <div class="card-body">
                           <div class="d-flex align-items-center justify-content-between">
                              <span class="avatar avatar-md rounded bg-dark mb-3">
                              <i class="ti ti-user-up fs-16"></i>
                              </span>
                           </div>
                           <div class="d-flex align-items-center justify-content-between">
                              <div>
                                 <h2 class="mb-1">0</h2>
                                 <p class="fs-13">Total Distributors</p>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
                  <div class="col-xl-3 col-sm-6 d-flex">
                     <div class="card flex-fill">
                        <div class="card-body">
                           <div class="d-flex align-items-center justify-content-between">
                              <span class="avatar avatar-md rounded bg-dark mb-3">
                              <i class="ti ti-user-star fs-16"></i>
                              </span>
                           </div>
                           <div class="d-flex align-items-center justify-content-between">
                              <div>
                                 <h2 class="mb-1">4</h2>
                                 <p class="fs-13">Total Sales Persons</p>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
                  <div class="col-xl-3 col-sm-6 d-flex">
                     <div class="card flex-fill">
                        <div class="card-body">
                           <div class="d-flex align-items-center justify-content-between">
                              <span class="avatar avatar-md rounded bg-dark mb-3">
                              <i class="ti ti-businessplan fs-16"></i>
                              </span>
                           </div>
                           <div class="d-flex align-items-center justify-content-between">
                              <div>
                                 <h2 class="mb-1">0</h2>
                                 <p class="fs-13">Total Products</p>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
               <div class="row">
                  <div class="col-lg-6 d-flex">
                     <!--col-xxl-3 -->
                     <div class="card flex-fill">
                        <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap">
                           <h5 class="mb-2">Total Orders</h5>
                        </div>
                        <div class="card-body pb-0">
                           <div id="company-chart">
                           </div>
                           <div class="d-flex align-items-center justify-content-between flex-wrap">
                              <div class="mb-1">
                                 <h2 class="mb-1">0</h2>
                              </div>
                              <p class="fs-13 text-gray-9 d-flex align-items-center mb-1"><i class="ti ti-circle-filled me-1 fs-6 text-primary"></i>Orders</p>
                           </div>
                        </div>
                     </div>
                  </div>
                  <div class="col-lg-6 d-flex">
                     <div class="card flex-fill">
                        <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap">
                           <h5 class="mb-2">Revenue</h5>
                        </div>
                        <div class="card-body pb-0">
                           <div class="d-flex align-items-center justify-content-between flex-wrap">
                              <div class="mb-1">
                                 <h2 class="mb-1">₹0</h2>
                              </div>
                              <p class="fs-13 text-gray-9 d-flex align-items-center mb-1"><i class="ti ti-circle-filled me-1 fs-6 text-primary"></i>Revenue</p>
                           </div>
                           <div id="revenue-income"></div>
                        </div>
                     </div>
                  </div>
               </div>
               <div class="row">
                  <div class="col-xxl-4 col-xl-12 d-flex">
                     <div class="card flex-fill">
                        <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap">
                           <h5 class="mb-2">Recent Orders</h5>
                           <a href="https://app.nanogenagrochem.com/order_management" class="btn btn-light btn-md mb-2">View All</a>
                        </div>
                        <div class="card-body pb-2">
                        </div>
                     </div>
                  </div>
                  <div class="col-xxl-4 col-xl-6 d-flex">
                     <div class="card flex-fill">
                        <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap">
                           <h5 class="mb-2"> Recent Dealers </h5>
                           <a href="https://app.nanogenagrochem.com/distributors_dealers/index/1" class="btn btn-light btn-md mb-2">View All</a>
                        </div>
                        <div class="card-body pb-2">
                        </div>
                     </div>
                  </div>
                  <div class="col-xxl-4 col-xl-6 d-flex">
                     <div class="card flex-fill">
                        <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap">
                           <h5 class="mb-2">Recent Distributors</h5>
                           <a href="https://app.nanogenagrochem.com/distributors_dealers/index" class="btn btn-light btn-md mb-2">View All</a>
                        </div>
                        <div class="card-body pb-2">
                           <div>
                              <div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            {{-- </div>
         </div> --}}


@endsection
@section('script')

@endsection
