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
                    <input type="text" class="form-control" placeholder="Search Soda/Order">
                </div>
            </div>
            <div class="col-sm-8">
                <div class="d-flex align-items-center flex-wrap row-gap-2 justify-content-sm-end">
                    <div class="dropdown me-2">
                        <div class="dropdown-menu  dropdown-menu-end">
                            <ul>
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item"><i
                                            class="ti ti-file-type-pdf text-danger me-1"></i>Export
                                        as PDF</a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item"><i
                                            class="ti ti-file-type-xls text-green me-1"></i>Export
                                        as Excel </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <a href="{{ route('order.create') }}" class="btn btn-primary"><i class="ti ti-square-rounded-plus me-2"></i>Add
                        Soda/Order</a>
                </div>
            </div>
        </div>
        <!-- /Search -->
    </div>
    <div class="card-body">
        <!-- order management List -->
        <div class="table-responsive custom-table">

            <div id="order_management_wrapper" class="dataTables_wrapper table-responsive">
                <div class="dataTables_length" id="order_management_length">
                    <label>
                        Show
                        <select name="order_management_length" class="form-select form-select-sm">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        entries
                    </label>
                </div>

                <table class="table dataTable">
                    <thead class="thead-light">
                        <tr>
                            <th class="no-sort sorting_disabled">
                                <label class="checkboxs">
                                    <input type="checkbox" id="select-all" class="order_checkbox"><span
                                        class="checkmarks"></span></label>
                            </th>
                            <th class="no-sort" class="sorting">SR.No</th>
                            <th scope="col" class="sorting">Order ID</th>
                            <th scope="col" class="sorting">Broker</th>
                            <th scope="col" class="sorting">Dealer</th>
                            <th scope="col" class="sorting">Order Date</th>
                            <th scope="col" class="sorting">Delivery Date</th>
                            <th scope="col" class="sorting">Total</th>
                            <th scope="col" class="sorting">Discount</th>
                            <th scope="col" class="sorting">Priority</th>
                            <th scope="col" class="sorting">Order Status</th>
                            <th class="sorting_disabled" aria-label="Action">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="odd">
                            <td data-label="Select"><label class="checkboxs">
                                    <input type="checkbox" class="checkbox-item order_checkbox" data-id="99">
                                    <span class="checkmarks"></span>
                                </label>
                            </td>
                            <td data-label="Sr no">1</td>
                            <td data-label="Order ID"><a href="#" class="show-btn open-popup-model"
                                    data-id="99">
                                    <i class="ti ti-eye #1ecbe2"></i> ORD000099</a>
                            </td>
                            <td data-label="Broker">Balaji Agro (Dealer)</td>
                            <td data-label="Dealer">Radhe Dealers</td>
                            <td data-label="Order Date">26 Jan 2026</td>
                            <td data-label="Delivery Date">24 Feb 2026</td>
                            <td data-label="Total">₹4,070</td>
                            <td data-label="Order Status">50₹</td>
                            <td data-label="Priority">
                                <div class="dropdown table-action order_drpdown">
                                    <span class="badge badge-pill badge-status bg-secondary">Low</span>
                                </div>
                            </td>
                            <td data-label="Action">
                                <div class="dropdown table-action order_drpdown">
                                    <span class="badge badge-pill badge-status bg-secondary">Pending</span>
                                    <a href="#" class="action-icon" data-bs-toggle="dropdown"
                                        aria-expanded="false"><i class="fa fa-pencil"></i></a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a href="javascript:void(0)" class="dropdown-item change-status" data-id="99" data-status="2">
                                            <span class="badge bg-primary">Approved</span>
                                        </a>
                                        <a href="javascript:void(0)" class="dropdown-item change-status" data-id="99" data-status="2">
                                            <span class="badge bg-info">Dispatched</span>
                                        </a>
                                        <a href="javascript:void(0)" class="dropdown-item change-status" data-id="99" data-status="2">
                                            <span class="badge bg-success">Completed</span>
                                        </a>
                                        <a href="javascript:void(0)" class="dropdown-item change-status" data-id="99" data-status="2">
                                            <span class="badge bg-danger">Cancelled</span>
                                        </a>
                                        <a href="javascript:void(0)" class="dropdown-item change-status" data-id="99" data-status="2">
                                            <span class="badge bg-danger">Partial Cancelled</span>
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="dropdown table-action">
                                    <a href="#" class="action-icon " data-bs-toggle="dropdown"
                                        aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a href="#" class="dropdown-item"><i
                                                class="ti ti-edit text-warning"></i> Edit</a><a
                                            href="javascript:void(0)" class="dropdown-item deleteOrder"
                                            data-id="99"> <i class="ti ti-trash text-danger"></i> Delete</a>
                                        <form action="#" method="post" class="delete-form">
                                            <input type="hidden"><input type="hidden" name="_method"
                                                value="DELETE">
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="even">
                            <td data-label="Select"><label class="checkboxs">
                                    <input type="checkbox" class="checkbox-item order_checkbox">
                                    <span class="checkmarks"></span>
                                </label>
                            </td>
                            <td data-label="Sr no">2</td>
                            <td data-label="Order ID"><a href="" class="show-btn open-popup-model">
                                    <i class="ti ti-eye #1ecbe2"></i> ORD000098</a>
                            </td>
                            <td data-label="Broker">Balaji Agro (Dealer)</td>
                            <td data-label="Dealer">Radhe Dealers</td>
                            <td data-label="Order Date">26 Jan 2026</td>
                            <td data-label="Delivery Date">24 Feb 2026</td>
                            <td data-label="Total">₹4,120</td>
                            <td data-label="Order Status">50₹</td>
                            <td data-label="Priority">
                                <div class="dropdown table-action order_drpdown">
                                    <span class="badge badge-pill badge-status bg-secondary">Low</span>
                                </div>
                            </td>
                            <td data-label="Action">
                                <div class="dropdown table-action order_drpdown">
                                    <span class="badge badge-pill badge-status bg-secondary">Pending</span>
                                    <a href="#" class="action-icon" data-bs-toggle="dropdown"
                                        aria-expanded="false"><i class="fa fa-pencil"></i></a>
                                    <div class="dropdown-menu dropdown-menu-right"><a href="javascript:void(0)"
                                            class="dropdown-item change-status">
                                            <span class="badge bg-success">Complete</span>
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="dropdown table-action">
                                    <a href="#" class="action-icon " data-bs-toggle="dropdown"
                                        aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a href="" class="dropdown-item"><i
                                                class="ti ti-edit text-warning"></i> Edit</a><a
                                            href="javascript:void(0)" class="dropdown-item deleteOrder"
                                            data-id="98"> <i class="ti ti-trash text-danger"></i> Delete</a>
                                        <form action="" method="post" class="delete-form"><input
                                                type="hidden" name="_token"><input type="hidden" value="DELETE">
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="odd">
                            <td data-label="Select"><label class="checkboxs">
                                    <input type="checkbox" class="checkbox-item order_checkbox">
                                    <span class="checkmarks"></span>
                                </label>
                            </td>
                            <td data-label="Sr no">3</td>
                            <td data-label="Order ID"><a href="" class="show-btn open-popup-model">
                                    <i class="ti ti-eye #1ecbe2"></i> ORD000097</a>
                            </td>
                            <td data-label="Broker">Balaji Agro (Dealer)</td>
                            <td data-label="Dealer">Radhe Dealers</td>
                            <td data-label="Order Date">24 Jan 2026</td>
                            <td data-label="Delivery Date">24 Feb 2026</td>
                            <td data-label="Total">₹2,392</td>
                            <td data-label="Order Status">50₹</td>
                            <td data-label="Priority">
                                <div class="dropdown table-action order_drpdown">
                                    <span class="badge badge-pill badge-status bg-secondary">Low</span>
                                </div>
                            </td>
                            <td data-label="Action">
                                <div class="dropdown table-action order_drpdown">
                                    <span class="badge badge-pill badge-status bg-secondary">Pending</span>
                                    <a href="#" class="action-icon" data-bs-toggle="dropdown"
                                        aria-expanded="false"><i class="fa fa-pencil"></i></a>
                                    <div class="dropdown-menu dropdown-menu-right"><a href="javascript:void(0)"
                                            class="dropdown-item change-status" data-id="97" data-status="2">
                                            <span class="badge bg-success">Complete</span>
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="dropdown table-action">
                                    <a href="#" class="action-icon " data-bs-toggle="dropdown"
                                        aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a href="" class="dropdown-item"><i
                                                class="ti ti-edit text-warning"></i> Edit</a><a
                                            href="javascript:void(0)" class="dropdown-item deleteOrder"> <i
                                                class="ti ti-trash text-danger"></i> Delete</a>
                                        <form class="delete-form"><input type="hidden"><input type="hidden"
                                                name="_method" value="DELETE">
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>


                    </tbody>
                </table>
                <div class="dataTables_info" id="order_management_info" role="status" aria-live="polite">Showing 1
                    to 10 of 72 entries</div>
                <div class="dataTables_paginate paging_simple_numbers" id="order_management_paginate">
                    <ul class="pagination">
                        <li class="paginate_button page-item previous disabled" id="order_management_previous"><a
                                aria-controls="order_management" aria-disabled="true" role="link"
                                data-dt-idx="previous" tabindex="-1" class="page-link">Previous</a></li>
                        <li class="paginate_button page-item active"><a href="#" class="page-link">1</a></li>
                        <li class="paginate_button page-item "><a href="#" class="page-link">2</a></li>
                        <li class="paginate_button page-item "><a href="#" class="page-link">3</a></li>
                        <li class="paginate_button page-item "><a href="#" class="page-link">4</a></li>
                        <li class="paginate_button page-item "><a href="#" class="page-link">5</a></li>
                        <li class="paginate_button page-item disabled"><a aria-controls="order_management"
                                aria-disabled="true" role="link" data-dt-idx="ellipsis" tabindex="-1"
                                class="page-link">…</a></li>
                        <li class="paginate_button page-item "><a href="#" aria-controls="order_management"
                                role="link" data-dt-idx="7" tabindex="0" class="page-link">8</a></li>
                        <li class="paginate_button page-item next" id="order_management_next"><a href="#"
                                aria-controls="order_management" role="link" data-dt-idx="next" tabindex="0"
                                class="page-link">Next</a></li>
                    </ul>
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
    </div>
</div>

@endsection
@section('script')
<script></script>
@endsection
