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
                    <input type="text" class="form-control" placeholder="Search User">
                </div>
            </div>
            <div class="col-sm-8">
                <div class="d-flex align-items-center flex-wrap row-gap-2 justify-content-sm-end">

                    <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvas_add"><i class="ti ti-square-rounded-plus me-2"></i>Add New Dispatch
                        User</a>
                </div>
            </div>
        </div>
        <!-- /Search -->
    </div>

    <div class="card-body">

        <!-- Filter -->
        <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-2 mb-4">
            <div class="d-flex align-items-center flex-wrap row-gap-2">
                <div class="dropdown me-2">
                    <a href="javascript:void(0);" class="dropdown-toggle" data-bs-toggle="dropdown"><i
                            class="ti ti-sort-ascending-2 me-2"></i>Sort </a>
                    <div class="dropdown-menu  dropdown-menu-start">
                        <ul>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item">
                                    <i class="ti ti-circle-chevron-right me-1"></i>Ascending
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item">
                                    <i class="ti ti-circle-chevron-right me-1"></i>Descending
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item">
                                    <i class="ti ti-circle-chevron-right me-1"></i>Recently
                                    Viewed
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item">
                                    <i class="ti ti-circle-chevron-right me-1"></i>Recently
                                    Added
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="icon-form">
                    <span class="form-icon"><i class="ti ti-calendar"></i></span>
                    <input type="text" class="form-control bookingrange" placeholder="">
                </div>
            </div>

        </div>
        <!-- /Filter -->

        <!-- Manage Users List -->
        <div class="table-responsive custom-table">
            <div id="manage-users-list_wrapper" class="dataTables_wrapper dt-bootstrap5 no-footer">

                <div class="row">
                    <div class="col-sm-12 col-md-6"></div>
                    <div class="col-sm-12 col-md-6"></div>
                </div>

                <div class="row dt-row">
                    <div class="col-sm-12 table-responsive">

                        <table class="table dataTable no-footer">

                            <thead class="thead-light">
                                <tr>


                                    <th>SR.No</th>
                                    <th>Order number</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Location</th>
                                    <th>Created</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <!-- Row -->
                                <tr>
                                    <td>1</td>

                                    <td>
                                        <h2 class="d-flex align-items-center">
                                            <!-- <a href="#" class="avatar avatar-sm me-2">
                    <img src="assets/images/avatar-19.png" alt="User Image">
                  </a> -->
                                            <a href="#" class="d-flex flex-column">
                                                0123456789
                                                <!-- <span class="text-default">Facility Manager</span> -->
                                            </a>
                                        </h2>
                                    </td>

                                    <td>1234567890</td>
                                    <td>robertson@example.com</td>
                                    <td>Germany</td>
                                    <td>25 Sep 2023, 12:12 pm</td>


                                    <td>
                                        <span class="badge badge-pill badge-status bg-success">
                                            Active
                                        </span>
                                    </td>

                                    <td class="text-end">
                                        <div class="dropdown table-action">
                                            <a href="#" class="action-icon" data-bs-toggle="dropdown">
                                                <i class="fa fa-ellipsis-v"></i>
                                            </a>

                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item" href="#" data-bs-toggle="offcanvas"
                                                    data-bs-target="#offcanvas_edit">
                                                    <i class="ti ti-edit text-blue"></i> Edit
                                                </a>

                                                <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                    data-bs-target="#delete_contact">
                                                    <i class="ti ti-trash text-danger"></i> Delete
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>2</td>

                                    <td>
                                        <h2 class="d-flex align-items-center">
                                            <!-- <a href="#" class="avatar avatar-sm me-2">
                    <img src="assets/images/avatar-19.png" alt="User Image">
                  </a> -->
                                            <a href="#" class="d-flex flex-column">
                                                0123456789
                                                <!-- <span class="text-default">Facility Manager</span> -->
                                            </a>
                                        </h2>
                                    </td>

                                    <td>1234567890</td>
                                    <td>robertson@example.com</td>
                                    <td>Germany</td>
                                    <td>25 Sep 2023, 12:12 pm</td>


                                    <td>
                                        <span class="badge badge-pill badge-status bg-success">
                                            Active
                                        </span>
                                    </td>

                                    <td class="text-end">
                                        <div class="dropdown table-action">
                                            <a href="#" class="action-icon" data-bs-toggle="dropdown">
                                                <i class="fa fa-ellipsis-v"></i>
                                            </a>

                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item" href="#" data-bs-toggle="offcanvas"
                                                    data-bs-target="#offcanvas_edit">
                                                    <i class="ti ti-edit text-blue"></i> Edit
                                                </a>

                                                <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                    data-bs-target="#delete_contact">
                                                    <i class="ti ti-trash text-danger"></i> Delete
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td>3</td>

                                    <td>
                                        <h2 class="d-flex align-items-center">
                                            <!-- <a href="#" class="avatar avatar-sm me-2">
                    <img src="assets/images/avatar-19.png" alt="User Image">
                  </a> -->
                                            <a href="#" class="d-flex flex-column">
                                                0123456789
                                                <!-- <span class="text-default">Facility Manager</span> -->
                                            </a>
                                        </h2>
                                    </td>

                                    <td>1234567890</td>
                                    <td>robertson@example.com</td>
                                    <td>Germany</td>
                                    <td>25 Sep 2023, 12:12 pm</td>


                                    <td>
                                        <span class="badge badge-pill badge-status bg-success">
                                            Active
                                        </span>
                                    </td>

                                    <td class="text-end">
                                        <div class="dropdown table-action">
                                            <a href="#" class="action-icon" data-bs-toggle="dropdown">
                                                <i class="fa fa-ellipsis-v"></i>
                                            </a>

                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item" href="#" data-bs-toggle="offcanvas"
                                                    data-bs-target="#offcanvas_edit">
                                                    <i class="ti ti-edit text-blue"></i> Edit
                                                </a>

                                                <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                    data-bs-target="#delete_contact">
                                                    <i class="ti ti-trash text-danger"></i> Delete
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td>4</td>

                                    <td>
                                        <h2 class="d-flex align-items-center">
                                            <!-- <a href="#" class="avatar avatar-sm me-2">
                    <img src="assets/images/avatar-19.png" alt="User Image">
                  </a> -->
                                            <a href="#" class="d-flex flex-column">
                                                0123456789
                                                <!-- <span class="text-default">Facility Manager</span> -->
                                            </a>
                                        </h2>
                                    </td>

                                    <td>1234567890</td>
                                    <td>robertson@example.com</td>
                                    <td>Germany</td>
                                    <td>25 Sep 2023, 12:12 pm</td>


                                    <td>
                                        <span class="badge badge-pill badge-status bg-success">
                                            Active
                                        </span>
                                    </td>

                                    <td class="text-end">
                                        <div class="dropdown table-action">
                                            <a href="#" class="action-icon" data-bs-toggle="dropdown">
                                                <i class="fa fa-ellipsis-v"></i>
                                            </a>

                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item" href="#" data-bs-toggle="offcanvas"
                                                    data-bs-target="#offcanvas_edit">
                                                    <i class="ti ti-edit text-blue"></i> Edit
                                                </a>

                                                <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                    data-bs-target="#delete_contact">
                                                    <i class="ti ti-trash text-danger"></i> Delete
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td>5</td>

                                    <td>
                                        <h2 class="d-flex align-items-center">
                                            <!-- <a href="#" class="avatar avatar-sm me-2">
                    <img src="assets/images/avatar-19.png" alt="User Image">
                  </a> -->
                                            <a href="#" class="d-flex flex-column">
                                                0123456789
                                                <!-- <span class="text-default">Facility Manager</span> -->
                                            </a>
                                        </h2>
                                    </td>

                                    <td>1234567890</td>
                                    <td>robertson@example.com</td>
                                    <td>Germany</td>
                                    <td>25 Sep 2023, 12:12 pm</td>


                                    <td>
                                        <span class="badge badge-pill badge-status bg-success">
                                            Active
                                        </span>
                                    </td>

                                    <td class="text-end">
                                        <div class="dropdown table-action">
                                            <a href="#" class="action-icon" data-bs-toggle="dropdown">
                                                <i class="fa fa-ellipsis-v"></i>
                                            </a>

                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item" href="#" data-bs-toggle="offcanvas"
                                                    data-bs-target="#offcanvas_edit">
                                                    <i class="ti ti-edit text-blue"></i> Edit
                                                </a>

                                                <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                    data-bs-target="#delete_contact">
                                                    <i class="ti ti-trash text-danger"></i> Delete
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td>6</td>

                                    <td>
                                        <h2 class="d-flex align-items-center">
                                            <!-- <a href="#" class="avatar avatar-sm me-2">
                    <img src="assets/images/avatar-19.png" alt="User Image">
                  </a> -->
                                            <a href="#" class="d-flex flex-column">
                                                0123456789
                                                <!-- <span class="text-default">Facility Manager</span> -->
                                            </a>
                                        </h2>
                                    </td>

                                    <td>1234567890</td>
                                    <td>robertson@example.com</td>
                                    <td>Germany</td>
                                    <td>25 Sep 2023, 12:12 pm</td>


                                    <td>
                                        <span class="badge badge-pill badge-status bg-success">
                                            Active
                                        </span>
                                    </td>

                                    <td class="text-end">
                                        <div class="dropdown table-action">
                                            <a href="#" class="action-icon" data-bs-toggle="dropdown">
                                                <i class="fa fa-ellipsis-v"></i>
                                            </a>

                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item" href="#" data-bs-toggle="offcanvas"
                                                    data-bs-target="#offcanvas_edit">
                                                    <i class="ti ti-edit text-blue"></i> Edit
                                                </a>

                                                <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                    data-bs-target="#delete_contact">
                                                    <i class="ti ti-trash text-danger"></i> Delete
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <!-- Repeat rows same structure -->
                            </tbody>

                        </table>

                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-12 col-md-5"></div>
                    <div class="col-sm-12 col-md-7"></div>
                </div>

            </div>
        </div>
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="datatable-length"></div>
            </div>
            <div class="col-md-6">
                <div class="datatable-paginate"></div>
            </div>
        </div>
        <!-- /Manage Users List -->

    </div>
</div>


@endsection
@section('script')
<script></script>
@endsection
