<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="author" content="Mayank Cattle Food PVT. LTD. " />
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.png') }}" />

    <!-- Title -->
    <title>{{ $page_title ?? config('app.name') }}</title>

    <!-- Themescript JS -->
    {{-- <script src="{{ asset('assets/js/theme-script.js') }}"></script> --}}

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('assets/images/favicon.png') }}" type="image/x-icon">
    <!-- <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.png"> -->

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">

    <!-- Tabler Icon CSS -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/tabler-icons/tabler-icons.css') }}">

    <!-- Fontawesome CSS -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">


    <!-- Datatable CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/dataTables.bootstrap5.min.css') }}">

    <!-- Daterangepicker CSS -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/daterangepicker/daterangepicker.css') }}">

    <!-- Select2 CSS -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">

    <!-- Bootstrap Tagsinput CSS -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/bootstrap-tagsinput/bootstrap-tagsinput.css') }}">

    <!-- Datetimepicker CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-datetimepicker.min.css') }}">

    <!-- Summernote CSS -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/summernote/summernote-lite.min.css') }}">

    <!-- Main CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

    <link href="{{ asset('assets/css/toastify.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/flatpickr.min.css') }}">

</head>

<body>

    <!-- Main Wrapper -->
    <div class="main-wrapper">

        <!-- Header -->
        <div class="header">

            <!-- Logo -->
            <div class="header-left active">
                <a href="{{ route('dashboard') }}" class="logo logo-normal">
                    {{-- <img src="{{ asset('assets/images/logo.png') }}" alt="Logo"> --}}
                    <img src="{{ asset('storage/company_logo/' . getSetting('company_logo')) }}" class="img-fluid"
                        alt="Logo">

                </a>
                <!-- <a href="index.html" class="logo-small">
                    <img src="assets/img/logo-small.svg" alt="Logo">
                </a> -->
                {{-- <a id="toggle_btn" href="javascript:void(0);">
                    <i class="ti ti-arrow-bar-to-left"></i>
                </a> --}}
            </div>
            <!-- /Logo -->

            <a id="mobile_btn" class="mobile_btn" href="#sidebar">
                <span class="bar-icon">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
            </a>

            <div class="header-user">
                <ul class="nav user-menu">

                    <!-- Search -->
                    <li class="nav-item nav-search-inputs me-auto">
                        <div class="top-nav-search">
                            <a href="javascript:void(0);" class="responsive-search">
                                <i class="fa fa-search"></i>
                            </a>
                            {{-- <form action="#" class="dropdown">
                                <div class="searchinputs" id="dropdownMenuClickable">
                                    <input type="text" placeholder="Search">
                                    <div class="search-addon">
                                        <button type="submit"><i class="ti ti-command"></i></button>
                                    </div>
                                </div>
                            </form> --}}
                        </div>
                    </li>
                    <!-- /Search -->



                    <!-- Nav List -->
                    <li class="nav-item nav-list">
                        <ul class="nav">
                            {{-- <li class="nav-item dropdown">
                                <a href="javascript:void(0);" class="btn btn-header-list" data-bs-toggle="dropdown">
                                    <i class="ti ti-layout-grid-add"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end menus-info">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <ul class="menu-list">
                                                <li>
                                                    <a href="contacts.html">
                                                        <div class="menu-details">
                                                            <span class="menu-list-icon bg-violet">
                                                                <i class="ti ti-user-up"></i>
                                                            </span>
                                                            <div class="menu-details-content">
                                                                <p>Contacts</p>
                                                                <span>Add New Contact</span>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="pipeline.html">
                                                        <div class="menu-details">
                                                            <span class="menu-list-icon bg-green">
                                                                <i class="ti ti-timeline-event-exclamation"></i>
                                                            </span>
                                                            <div class="menu-details-content">
                                                                <p>Pipline</p>
                                                                <span>Add New Pipline</span>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="activities.html">
                                                        <div class="menu-details">
                                                            <span class="menu-list-icon bg-pink">
                                                                <i class="ti ti-bounce-right"></i>
                                                            </span>
                                                            <div class="menu-details-content">
                                                                <p>Activities</p>
                                                                <span>Add New Activity</span>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="analytics.html">
                                                        <div class="menu-details">
                                                            <span class="menu-list-icon bg-info">
                                                                <i class="ti ti-analyze"></i>
                                                            </span>
                                                            <div class="menu-details-content">
                                                                <p>Analytics</p>
                                                                <span>Shows All Information</span>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="projects.html">
                                                        <div class="menu-details">
                                                            <span class="menu-list-icon bg-danger">
                                                                <i class="ti ti-atom-2"></i>
                                                            </span>
                                                            <div class="menu-details-content">
                                                                <p>Projects</p>
                                                                <span>Add New Project</span>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <ul class="menu-list">
                                                <li>
                                                    <a href="deals.html">
                                                        <div class="menu-details">
                                                            <span class="menu-list-icon bg-info">
                                                                <i class="ti ti-medal"></i>
                                                            </span>
                                                            <div class="menu-details-content">
                                                                <p>Deals</p>
                                                                <span>Add New Deals</span>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="leads.html">
                                                        <div class="menu-details">
                                                            <span class="menu-list-icon bg-secondary">
                                                                <i class="ti ti-chart-arcs"></i>
                                                            </span>
                                                            <div class="menu-details-content">
                                                                <p>Leads</p>
                                                                <span>Add New Leads</span>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="companies.html">
                                                        <div class="menu-details">
                                                            <span class="menu-list-icon bg-tertiary">
                                                                <i class="ti ti-building-community"></i>
                                                            </span>
                                                            <div class="menu-details-content">
                                                                <p>Company</p>
                                                                <span>Add New Company</span>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="tasks.html">
                                                        <div class="menu-details">
                                                            <span class="menu-list-icon bg-success">
                                                                <i class="ti ti-list-check"></i>
                                                            </span>
                                                            <div class="menu-details-content">
                                                                <p>Tasks</p>
                                                                <span>Add New Task</span>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="campaign.html">
                                                        <div class="menu-details">
                                                            <span class="menu-list-icon bg-purple">
                                                                <i class="ti ti-brand-campaignmonitor"></i>
                                                            </span>
                                                            <div class="menu-details-content">
                                                                <p>Campaign</p>
                                                                <span>Add New Campaign</span>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </li> --}}

                            {{-- <li class="nav-item">
                                <a href="lead-reports.html" class="btn btn-chart-pie">
                                    <i class="ti ti-chart-pie"></i>
                                </a>
                            </li> --}}
                        </ul>
                    </li>
                    <!-- /Nav List -->



                    <!-- Notifications -->
                    {{-- <li class="nav-item dropdown nav-item-box">
                        <a href="javascript:void(0);" class="nav-link" data-bs-toggle="dropdown">
                            <i class="ti ti-bell"></i>
                            <span class="badge rounded-pill">13</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                            <div class="topnav-dropdown-header">
                                <h4 class="notification-title">Notifications</h4>
                            </div>
                            <div class="noti-content">
                                <ul class="notification-list">
                                    <li class="notification-message">
                                        <a href="activities.html">
                                            <div class="media d-flex">
                                                <span class="avatar flex-shrink-0">
                                                    <img src="assets/images/avatar-02.jpg" alt="Profile">
                                                    <span class="badge badge-info rounded-pill"></span>
                                                </span>
                                                <div class="media-body flex-grow-1">
                                                    <p class="noti-details">Ray Arnold left 6 comments on Isla Nublar
                                                        SOC2 compliance report</p>
                                                    <p class="noti-time">Last Wednesday at 9:42 am</p>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li class="notification-message">
                                        <a href="activities.html">
                                            <div class="media d-flex">
                                                <span class="avatar flex-shrink-0">
                                                    <img src="assets/images/avatar-03.jpg" alt="Profile">
                                                </span>
                                                <div class="media-body flex-grow-1">
                                                    <p class="noti-details">Denise Nedry replied to Anna Srzand</p>
                                                    <p class="noti-sub-details">“Oh, I finished de-bugging the phones,
                                                        but the system's compiling for eighteen minutes, or twenty. So,
                                                        some minor systems may go on and off for a while.”</p>
                                                    <p class="noti-time">Last Wednesday at 9:42 am</p>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li class="notification-message">
                                        <a href="activities.html">
                                            <div class="media d-flex">
                                                <span class="avatar flex-shrink-0">
                                                    <img alt="" src="assets/images/avatar-06.jpg">
                                                </span>
                                                <div class="media-body flex-grow-1">
                                                    <p class="noti-details">John Hammond attached a file to Isla Nublar
                                                        SOC2 compliance report</p>
                                                    <div class="noti-pdf">
                                                        <div class="noti-pdf-icon">
                                                            <span><i class="ti ti-chart-pie"></i></span>
                                                        </div>
                                                        <div class="noti-pdf-text">
                                                            <p>EY_review.pdf</p>
                                                            <span>2mb</span>
                                                        </div>
                                                    </div>
                                                    <p class="noti-time">Last Wednesday at 9:42 am</p>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="topnav-dropdown-footer">
                                <a href="activities.html" class="view-link">View all</a>
                                <a href="javascript:void(0);" class="clear-link">Clear all</a>
                            </div>
                        </div>
                    </li> --}}
                    <!-- /Notifications -->

                    <!-- Profile Dropdown -->
                    <li class="nav-item dropdown has-arrow main-drop">
                        <a href="javascript:void(0);" class="nav-link userset" data-bs-toggle="dropdown">
                            <span class="user-info">
                                <span class="user-letter">
                                    <img src="{{ auth()->user()->profile_picture
                                        ? asset('storage/profile_pictures/' . auth()->user()->profile_picture)
                                        : asset('images/default-user.png') }}"
                                        class="img-fluid" alt="Profile">
                                </span>
                                <span class="badge badge-success rounded-pill"></span>
                            </span>
                        </a>
                        <div class="dropdown-menu menu-drop-user">
                            <div class="profilename">
                                <a class="dropdown-item" href="{{ route('dashboard') }}">
                                    <i class="ti ti-layout-2"></i> Dashboard
                                </a>
                                <a class="dropdown-item" href="{{ route('my_profile', auth()->user()->id) }}">
                                    <i class="ti ti-user-pin"></i> My Profile
                                </a>
                                {{-- <a class="dropdown-item" href="{{ route('logout') }}">
                                    <i class="ti ti-lock"></i> Logout
                                </a> --}}
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item"> <i class="ti ti-lock"></i>Logout</button>
                                </form>
                            </div>
                        </div>
                    </li>
                    <!-- /Profile Dropdown -->

                </ul>
            </div>

            <!-- Mobile Menu -->
            <div class="dropdown mobile-user-menu">
                <a href="javascript:void(0);" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"
                    aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="index.html">
                        <i class="ti ti-layout-2"></i> Dashboard
                    </a>
                    <a class="dropdown-item" href="profile.html">
                        <i class="ti ti-user-pin"></i> My Profile
                    </a>
                    <a class="dropdown-item" href="login.html">
                        <i class="ti ti-lock"></i> Logout
                    </a>
                </div>
            </div>
            <!-- /Mobile Menu -->

        </div>
        <!-- /Header -->

        <!-- Sidebar -->
        @include('layouts.sidebar')
        <!-- /Sidebar -->

        {{-- @yield('content') --}}
        <div class="page-wrapper">
            <div class="content">
                <div class="row">
                    <div class="col-md-12">
                        <div class="page-header">
                            <div class="row align-items-center ">
                                <div class="col-md-4">
                                    <h3 class="page-title">
                                        @yield('title')
                                    </h3>
                                </div>
                                {{-- <div class="col-md-8 float-end ms-auto">
                                    <div class="d-flex title-head">
                                        <div class="daterange-picker d-flex align-items-center justify-content-center">
                                            <div class="form-sort me-2">
                                                <i class="ti ti-calendar"></i>
                                                <input type="text" class="form-control  date-range bookingrange">
                                            </div>
                                        </div>
                                    </div>
                                </div> --}}
                            </div>
                        </div>
                    </div>
                </div>

                @yield('content')
            </div>
        </div>


    </div>
    <!-- /Main Wrapper -->




    <!-- jQuery -->
    <script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>

    <!-- Bootstrap Core JS -->
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Feather Icon JS -->
    <script src="{{ asset('assets/js/feather.min.js') }}"></script>

    <!-- Slimscroll JS -->
    <script src="{{ asset('assets/js/jquery.slimscroll.min.js') }}"></script>

    <!-- Datatable JS -->
    <script src="{{ asset('assets/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/js/dataTables.bootstrap5.min.js') }}"></script>

    <!-- Daterangepicker JS -->
    <script src="{{ asset('assets/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/daterangepicker/daterangepicker.js') }}"></script>

    <!-- Datetimepicker JS -->
    <script src="{{ asset('assets/js/bootstrap-datetimepicker.min.js') }}"></script>

    <!-- Bootstrap Tagsinput JS -->
    <script src="{{ asset('assets/plugins/bootstrap-tagsinput/bootstrap-tagsinput.js') }}"></script>

    <!-- Select2 JS -->

    <script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>

    <!-- Summernote JS -->
    <script src="{{ asset('assets/plugins/summernote/summernote-lite.min.js') }}"></script>

    <!-- Custom Json Js -->
    <script src="{{ asset('assets/js/jsonscript.js') }}"></script>

    <script src="{{ asset('assets/js/sweetalert2@11.js') }}"></script>
    <script src="{{ asset('assets/js/toastify.js') }}"></script>
    <script src="{{ asset('assets/js/flatpickr.js') }}"></script>

    <!-- Color Picker JS -->
    <!-- <script src="assets/plugins/@simonwep/pickr/pickr.es5.min.js"></script> -->

    <!--- Custom Js -->
    <!-- <script src="assets/js/theme-colorpicker.js"></script> -->
    <script src="{{ asset('assets/js/script.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>

    @if (session('success'))
        <script>
            Toastify({
                text: "{{ session('success') }}",
                duration: 3000,
                close: true,
                gravity: "top",
                position: "right",
                backgroundColor: "#4fbe87",
            }).showToast();
        </script>
    @endif

    @if (session('error'))
        <script>
            Toastify({
                text: "{{ session('error') }}",
                duration: 3000,
                close: true,
                gravity: "top",
                position: "right",
                backgroundColor: "#f27474",
            }).showToast();
        </script>
    @endif

    <script>
        function show_success(msg) {
            Toastify({
                text: msg,
                duration: 3000,
                close: true,
                gravity: "top",
                position: "right",
                backgroundColor: "#4fbe87",
            }).showToast();
        }

        function show_error(msg) {
            Toastify({
                text: msg,
                duration: 3000,
                close: true,
                gravity: "top",
                position: "right",
                backgroundColor: "#f27474",
            }).showToast();
        }
    </script>

    @yield('script')
</body>

</html>
