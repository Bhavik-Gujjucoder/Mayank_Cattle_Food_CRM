<div class="sidebar" id="sidebar">
    <div class="modern-profile p-3 pb-0">

        <div class="sidebar-nav mb-3">
            <ul class="nav nav-tabs nav-tabs-solid nav-tabs-rounded nav-justified bg-transparent" role="tablist">
                <li class="nav-item"><a class="nav-link active border-0" href="#">Menu</a></li>
                <li class="nav-item"><a class="nav-link border-0" href="chat.html">Chats</a></li>
                <li class="nav-item"><a class="nav-link border-0" href="email.html">Inbox</a></li>
            </ul>
        </div>
    </div>
    <div class="sidebar-header p-3 pb-0 pt-2">

        <div class="d-flex align-items-center justify-content-between menu-item mb-3">
            <div class="me-3">
                <a href="calendar.html" class="btn btn-icon border btn-menubar">
                    <i class="ti ti-layout-grid-remove"></i>
                </a>
            </div>
            <div class="me-3">
                <a href="chat.html" class="btn btn-icon border btn-menubar position-relative">
                    <i class="ti ti-brand-hipchat"></i>
                </a>
            </div>
            <div class="me-3 notification-item">
                <a href="activities.html" class="btn btn-icon border btn-menubar position-relative me-1">
                    <i class="ti ti-bell"></i>
                    <span class="notification-status-dot"></span>
                </a>
            </div>
            <div class="me-0">
                <a href="email.html" class="btn btn-icon border btn-menubar">
                    <i class="ti ti-message"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>
                <li class="clinicdropdown">
                    <a href="{{ route('dashboard') }}">
                        <img src="{{ auth()->user()->profile_picture
                            ? asset('storage/profile_pictures/' . auth()->user()->profile_picture)
                            : asset('images/default-user.png') }}"
                            class="img-fluid" alt="Profile">
                        <div class="user-names">
                            <h5>{{ Auth::user()->name }}</h5>
                            <h6>{{ auth()->user()->getRoleNames()->first() }}</h6>
                        </div>
                    </a>
                </li>
            </ul>
            <ul>
                <li>
                    <ul>
                        {{-- ------------------------------------------------------------------ */
                        /*  Dashboard
                        /* ------------------------------------------------------------------ --}}
                        <li>
                            <a href="{{ route('dashboard') }}">
                                <i class="ti ti-layout-2"></i><span>Dashboard</span>
                            </a>
                        </li>

                        {{-- ------------------------------------------------------------------ */
                        /*  Raw Material submenu (inventory + purchase order)
                        /* ------------------------------------------------------------------ --}}
                        @canany([
                            'view-raw-material-inventory', 'export-raw-material-inventory',
                            'add-raw-material-inventory', 'edit-raw-material-inventory', 'delete-raw-material-inventory',
                            'view-raw-material-purchas-order', 'export-raw-material-purchas-order',
                            'add-raw-material-purchas-order', 'edit-raw-material-purchas-order', 'delete-raw-material-purchas-order',
                            'view-raw-material-receive', 'export-raw-material-receive',
                            'add-raw-material-receive', 'edit-raw-material-receive', 'delete-raw-material-receive',
                        ])
                            <li class="submenu">
                                <a href="javascript:void(0);"
                                    class="@if (request()->routeIs('raw-material.*')) active subdrop @endif">
                                    <i class="ti ti-package"></i>
                                    <span>Raw Material</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <ul style="display: @if (request()->routeIs('raw-material.*')) block @else none @endif;">

                                    @canany([
                                        'view-raw-material-inventory', 'export-raw-material-inventory',
                                        'add-raw-material-inventory', 'edit-raw-material-inventory', 'delete-raw-material-inventory',
                                    ])
                                        <li>
                                            <a href="{{ route('raw-material.index') }}"
                                                class="@if (request()->routeIs('raw-material.index', 'raw-material.create', 'raw-material.edit', 'raw-material.show', 'raw-material.export')) active @endif">
                                                <span>Material</span>
                                            </a>
                                        </li>
                                    @endcanany

                                    @canany([
                                        'view-raw-material-purchas-order', 'export-raw-material-purchas-order',
                                        'add-raw-material-purchas-order', 'edit-raw-material-purchas-order', 'delete-raw-material-purchas-order',
                                    ])
                                        <li>
                                            <a href="{{ route('raw-material.order.index') }}"
                                                class="@if (request()->routeIs('raw-material.order.*')) active @endif">
                                                <span>Orders</span>
                                            </a>
                                        </li>
                                    @endcanany
                                    @canany([
                                        'view-raw-material-receive', 'export-raw-material-receive',
                                        'add-raw-material-receive', 'edit-raw-material-receive', 'delete-raw-material-receive',
                                    ])
                                        <li>
                                            <a href="{{ route('raw-material.receive.index') }}"
                                                class="@if (request()->routeIs('raw-material.receive.*')) active @endif">
                                                <span>Received</span>
                                            </a>
                                        </li>
                                    @endcanany
                                </ul>
                            </li>
                        @endcanany

                        {{-- ------------------------------------------------------------------ */
                        /*  Product (type: product)
                        /* ------------------------------------------------------------------ --}}
                        @canany(['add-product', 'edit-product', 'delete-product'])
                            <li class="submenu">
                                <a href="javascript:void(0);"
                                    class="@if (request()->routeIs('product*')) active subdrop @endif">
                                    <i class="ti ti-package"></i>
                                    <span>Production</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <ul style="display: @if (request()->routeIs('product*')) block @else none @endif;">
                                    <li>
                                        <a href="{{ route('product.index') }}"
                                            class="@if (request()->routeIs('product*')) active @endif">
                                            {{-- <i class="ti ti-package"></i> --}}
                                            <span>Products</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endcanany

                        {{-- ------------------------------------------------------------------ */
                        /*  Sales submenu (Soda / Order + Dispatch)
                        /* ------------------------------------------------------------------ --}}
                        @canany(['view-order', 'add-order', 'edit-order', 'delete-order', 'view-dispatch', 'add-dispatch', 'edit-dispatch',
                            'delete-dispatch', 'view-dispatch-pending-payments'])
                            <li class="submenu">
                                <a href="javascript:void(0);"
                                    class="@if (request()->routeIs('order*') || request()->routeIs('dispatch*') || request()->routeIs('delivery-pending-payments*')) active subdrop @endif">
                                    <i class="ti ti-list-check"></i>
                                    <span>Sales</span>
                                    <span class="menu-arrow"></span>
                                </a>

                                <ul style="display: @if (request()->routeIs('order*') || request()->routeIs('dispatch*') || request()->routeIs('delivery-pending-payments*')) block @else none @endif;">

                                    {{-- ------------------------------------------------------------------ */
                                    /*  Soda / Order (type: soda-order)
                                    /* ------------------------------------------------------------------ --}}
                                    @canany(['view-order'])
                                        <li>
                                            <a href="{{ route('order.index') }}"
                                                class="@if (request()->routeIs('order*')) active @endif">
                                                <span>Soda / Order</span>
                                            </a>
                                        </li>
                                    @endcanany

                                    {{-- ------------------------------------------------------------------ */
                                    /*  Dispatch (type: dispatch)
                                    /* ------------------------------------------------------------------ --}}
                                    @canany(['view-dispatch'])
                                        <li>
                                            <a href="{{ route('dispatch.index') }}"
                                                class="@if (request()->routeIs('dispatch*') && !request()->routeIs('delivery-pending-payments*')) active @endif">
                                                <span>Dispatch</span>
                                            </a>
                                        </li>
                                    @endcanany

                                    {{-- ------------------------------------------------------------------ */
                                    /*  Dispatch Pending Payments
                                    /* ------------------------------------------------------------------ --}}
                                    @can('view-dispatch-pending-payments')
                                        <li>
                                            <a href="{{ route('delivery-pending-payments.index') }}"
                                                class="@if (request()->routeIs('delivery-pending-payments*')) active @endif">
                                                <span>Dispatch Pending Payments</span>
                                            </a>
                                        </li>
                                    @endcan
                                </ul>
                            </li>
                        @endcanany

                        {{-- ------------------------------------------------------------------ */
                        /*  Oil Management — no permissions
                        /* ------------------------------------------------------------------ --}}
                        @canany(['add-oil', 'edit-oil', 'delete-oil'])
                            <li class="submenu">
                                <a href="javascript:void(0);"
                                    class="@if (request()->routeIs('oil*')) active subdrop @endif">
                                    <i class="ti ti-drop-circle"></i>
                                    <span>Oil</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <ul style="display: @if (request()->routeIs('oil*')) block @else none @endif;">
                                    <li>
                                        <a href="{{ route('oil.index') }}"
                                            class="@if (request()->routeIs('oil*')) active @endif">
                                            <span>Oil Management</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endcanany

                        {{-- ------------------------------------------------------------------ */
                        /*  Machine Inventory — no permissions
                        /* ------------------------------------------------------------------ --}}
                        @canany(['add-machine', 'edit-machine', 'delete-machine'])
                            <li class="submenu">
                                <a href="javascript:void(0);"
                                    class="@if (request()->routeIs('machine*')) active subdrop @endif">
                                    <i class="ti ti-building-factory"></i>
                                    <span>Machinery</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <ul style="display: @if (request()->routeIs('machine*')) block @else none @endif;">
                                    <li>
                                        <a href="{{ route('machine.index') }}"
                                            class="@if (request()->routeIs('machine*')) active @endif">
                                            <span>Inventory</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endcanany

                        {{-- ------------------------------------------------------------------ */
                        /*  Users & Permissions submenu
                        /* ------------------------------------------------------------------ --}}
                        @canany(['add-supplier', 'edit-supplier', 'delete-supplier', 'add-broker', 'edit-broker',
                            'delete-broker', 'add-dealer', 'edit-dealer', 'delete-dealer', 'add-transporter',
                            'edit-transporter', 'delete-transporter', 'add-truck', 'edit-truck', 'delete-truck', 'add-user',
                            'edit-user', 'delete-user'])
                            <li class="submenu">
                                <a href="javascript:void(0);"
                                    class="@if (request()->routeIs('users*') ||
                                            request()->routeIs('supplier*') ||
                                            request()->routeIs('dealer*') ||
                                            request()->routeIs('truck*') ||
                                            request()->routeIs('roles*')) active  
                                            subdrop @endif">
                                    <i class="ti ti-users"></i>
                                    <span>Users & Permissions</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <ul style="display: @if (request()->routeIs('users*') ||
                                        request()->routeIs('supplier*') ||
                                        request()->routeIs('dealer*') ||
                                        request()->routeIs('truck*') ||
                                        request()->routeIs('roles*')) block @else none @endif;">

                                    {{-- ------------------------------------------------------------------ */
                                /*  Supplier (type: supplier)
                                /* ------------------------------------------------------------------ --}}
                                    @canany(['add-supplier', 'edit-supplier', 'delete-supplier'])
                                        <li><a href="{{ route('supplier.index') }}">Supplier</a></li>
                                    @endcanany

                                    {{-- ------------------------------------------------------------------ */
                                /*  Broker (type: broker → UserController)
                                /* ------------------------------------------------------------------ --}}
                                    @canany(['add-broker', 'edit-broker', 'delete-broker'])
                                        <li><a href="{{ route('users.index', 'broker') }}">Broker</a></li>
                                    @endcanany

                                    {{-- ------------------------------------------------------------------ */
                                /*  Dealer (type: dealer)
                                /* ------------------------------------------------------------------ --}}
                                    @canany(['add-dealer', 'edit-dealer', 'delete-dealer'])
                                        <li><a href="{{ route('dealer.index') }}">Dealer</a></li>
                                    @endcanany

                                    {{-- ------------------------------------------------------------------ */
                                /*  Transporter (type: transporter → UserController)
                                /* ------------------------------------------------------------------ --}}
                                    @canany(['add-transporter', 'edit-transporter', 'delete-transporter'])
                                        <li><a href="{{ route('users.index', 'transporter') }}">Transporter</a></li>
                                    @endcanany

                                    {{-- ------------------------------------------------------------------ */
                                /*  Truck Management (type: truck-management)
                                /* ------------------------------------------------------------------ --}}
                                    @canany(['add-truck', 'edit-truck', 'delete-truck'])
                                        <li>
                                            <a href="{{ route('truck.index') }}">
                                                {{-- class="{{ request()->routeIs('truck*') ? 'active' : '' }}"> --}}
                                                Truck Management
                                            </a>
                                        </li>
                                    @endcanany

                                    {{-- ------------------------------------------------------------------ */
                                /*  User Management (type: user → UserController)
                                /* ------------------------------------------------------------------ --}}
                                    @canany(['add-user', 'edit-user', 'delete-user'])
                                        <li><a href="{{ route('users.index', 'user') }}">Admin & Staff</a></li>
                                    @endcanany

                                    {{-- ------------------------------------------------------------------ */
                                /*  Roles — super admin only
                                /* ------------------------------------------------------------------ --}}
                                    @hasanyrole('super admin')
                                        <li><a href="{{ route('roles.index') }}">Roles & Permissions</a></li>
                                    @endhasanyrole
                                </ul>
                            </li>
                        @endcanany

                        {{-- ------------------------------------------------------------------ */
                        /*  General Settings submenu
                        /* ------------------------------------------------------------------ --}}
                        @canany(['add-state', 'edit-state', 'delete-state', 'add-city', 'edit-city', 'delete-city'])
                            <li class="submenu">
                                <a href="javascript:void(0);"
                                    class="@if (request()->routeIs('state*') || request()->routeIs('city*') || request()->routeIs('generalsetting*')) active subdrop @endif">
                                    <i class="ti ti-settings"></i>
                                    <span>General</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <ul style="display: @if (request()->routeIs('state*') || request()->routeIs('city*') || request()->routeIs('generalsetting*')) block @else none @endif;">

                                    {{-- ------------------------------------------------------------------ */
                                /*  State (type: state)
                                /* ------------------------------------------------------------------ --}}
                                    @canany(['add-state', 'edit-state', 'delete-state'])
                                        <li><a href="{{ route('state.index') }}">State</a></li>
                                    @endcanany

                                    {{-- ------------------------------------------------------------------ */
                                /*  City (type: city)
                                /* ------------------------------------------------------------------ --}}
                                    @canany(['add-city', 'edit-city', 'delete-city'])
                                        <li><a href="{{ route('city.index') }}">City</a></li>
                                    @endcanany

                                    {{-- ------------------------------------------------------------------ */
                                /* General Settings — super admin| admin only
                                /* ------------------------------------------------------------------ --}}
                                    @hasanyrole('super admin|admin')
                                        <li><a href="{{ route('generalsetting.create') }}">Settings</a></li>
                                    @endhasanyrole
                                </ul>
                            </li>
                        @endcanany
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
