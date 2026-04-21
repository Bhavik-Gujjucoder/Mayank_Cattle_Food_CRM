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
                    <input type="text" class="form-control" placeholder="Search">
                </div>
            </div>
            <!-- <div class="col-sm-8">
          <div
           class="d-flex align-items-center flex-wrap row-gap-2 justify-content-sm-end">

           <a href="javascript:void(0);" class="btn btn-primary"
            data-bs-toggle="offcanvas" data-bs-target="#offcanvas_add"><i
             class="ti ti-square-rounded-plus me-2"></i>Add Broker</a>
          </div>
         </div> -->
        </div>
        <!-- /Search -->
    </div>

    <div class="card-body">

        Comming Soon...

    </div>
</div>


@endsection
@section('script')
<script></script>
@endsection
