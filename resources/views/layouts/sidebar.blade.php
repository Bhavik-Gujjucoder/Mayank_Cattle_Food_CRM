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

                        {{-- Dashboard — always visible --}}
                        <li>
                            <a href="{{ route('dashboard') }}">
                                <i class="ti ti-layout-2"></i><span>Dashboard</span>
                            </a>
                        </li>

                        {{-- Dealer (type: dealer) --}}
                        {{-- auth()->user()->hasAnyRole(['super admin', 'admin']) || --}}
                        @if(auth()->user()->canAny(['add-dealer', 'edit-dealer', 'delete-dealer']))
                        <li>
                            <a href="{{ route('dealer.index') }}"
                                class="@if(request()->routeIs('dealer*')) active @endif">
                                <i class="ti ti-bell-school"></i><span>Dealer</span>
                            </a>
                        </li>
                        @endif

                        {{-- Soda / Order (type: soda-order) --}}
                        @if(auth()->user()->canAny(['add-order', 'edit-order', 'delete-order']))
                        <li>
                            <a href="{{ route('order.index') }}"
                                class="@if(request()->routeIs('order*')) active @endif">
                                <i class="ti ti-list-check"></i><span>Soda/order</span>
                            </a>
                        </li>
                        @endif

                        {{-- Dispatch (type: dispatch) --}}
                        @if(auth()->user()->canAny(['add-dispatch', 'edit-dispatch', 'delete-dispatch']))
                        <li>
                            <a href="{{ route('dispatch.index') }}"
                                class="@if(request()->routeIs('dispatch*')) active @endif">
                                <i class="ti ti-report-money"></i><span>Dispatch</span>
                            </a>
                        </li>
                        @endif

                        {{-- Broker (type: broker → UserController) --}}
                        @if(auth()->user()->canAny(['add-broker', 'edit-broker', 'delete-broker']))
                        <li>
                            <a href="{{ route('users.index', 'broker') }}"
                                class="@if(request()->routeIs('users*') && request()->route('type') == 'broker') active @endif">
                                <i class="ti ti-user-up"></i><span>Broker</span>
                            </a>
                        </li>
                        @endif

                        {{-- Transporter (type: transporter → UserController) --}}
                        @if(auth()->user()->canAny(['add-transporter', 'edit-transporter', 'delete-transporter']))
                        <li>
                            <a href="{{ route('users.index', 'transporter') }}"
                                class="@if(request()->routeIs('users.index') && request()->route('type') == 'transporter') active @endif">
                                <i class="ti ti-tir"></i><span>Transporter</span>
                            </a>
                        </li>
                        @endif

                        {{-- Supplier (type: supplier) --}}
                        @if(auth()->user()->canAny(['add-supplier', 'edit-supplier', 'delete-supplier']))
                        <li>
                            <a href="{{ route('supplier.index') }}"
                                class="@if(request()->routeIs('supplier*')) active @endif">
                                <i class="ti ti-truck-delivery"></i><span>Supplier</span>
                            </a>
                        </li>
                        @endif

                        {{-- Raw Material submenu (inventory + purchase order) --}}
                        @if(auth()->user()->canAny([
                            'add-raw-material-inventory', 'edit-raw-material-inventory', 'delete-raw-material-inventory',
                            'add-raw-material-purchas-order', 'edit-raw-material-purchas-order', 'delete-raw-material-purchas-order'
                        ]))
                        <li class="submenu">
                            <a href="javascript:void(0);"
                                class="@if(request()->routeIs('raw-material*') || request()->routeIs('raw-material-order*')) active subdrop @endif">
                                <i class="ti ti-package"></i>
                                <span>Raw Material</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul style="display: @if(request()->routeIs('raw-material*') || request()->routeIs('raw-material-order*')) block @else none @endif;">

                                {{-- Raw Material Inventory --}}
                                @if(auth()->user()->canAny(['add-raw-material-inventory', 'edit-raw-material-inventory', 'delete-raw-material-inventory']))
                                <li>
                                    <a href="{{ route('raw-material.index') }}"
                                        class="@if(request()->routeIs('raw-material.index') || request()->routeIs('raw-material.create') || request()->routeIs('raw-material.edit')) active @endif">
                                        Inventory
                                    </a>
                                </li>
                                @endif

                                {{-- Raw Material Purchase Order --}}
                                @if(auth()->user()->canAny(['add-raw-material-purchas-order', 'edit-raw-material-purchas-order', 'delete-raw-material-purchas-order']))
                                <li>
                                    <a href="{{ route('raw-material-order.index') }}"
                                        class="@if(request()->routeIs('raw-material-order.index') || request()->routeIs('raw-material-order.create') || request()->routeIs('raw-material-order.edit')) active @endif">
                                        Purchase Order
                                    </a>
                                </li>
                                @endif

                            </ul>
                        </li>
                        @endif

                        {{-- Product (type: product) --}}
                        @if(auth()->user()->canAny(['add-product', 'edit-product', 'delete-product']))
                        <li>
                            <a href="{{ route('product.index') }}"
                                class="@if(request()->routeIs('product*')) active @endif">
                                <i class="ti ti-package"></i><span>Product</span>
                            </a>
                        </li>
                        @endif

                        {{-- Oil Management — no permissions in SQL, visible to all --}}
                        <li>
                            <a href="{{ route('oil.index') }}"
                                class="@if(request()->routeIs('oil*')) active @endif">
                                <i class="ti ti-drop-circle"></i><span>Oil</span>
                            </a>
                        </li>

                        {{-- Machine Inventory — no permissions in SQL, visible to all --}}
                        <li>
                            <a href="{{ route('machine.index') }}"
                                class="@if(request()->routeIs('machine*')) active @endif">
                                <i class="ti ti-building-factory"></i><span>Machine Inventory</span>
                            </a>
                        </li>

                        {{-- State (type: state) --}}
                        @if(auth()->user()->canAny(['add-state', 'edit-state', 'delete-state']))
                        <li>
                            <a href="{{ route('state.index') }}"
                                class="@if(request()->routeIs('state*')) active @endif">
                                <i class="ti ti-map-pin-pin"></i><span>State</span>
                            </a>
                        </li>
                        @endif

                        {{-- City (type: city) --}}
                        @if(auth()->user()->canAny(['add-city', 'edit-city', 'delete-city']))
                        <li>
                            <a href="{{ route('city.index') }}"
                                class="@if(request()->routeIs('city*')) active @endif">
                                <i class="ti ti-map-pin-pin"></i><span>City</span>
                            </a>
                        </li>
                        @endif

                        {{-- Roles — admin only --}}
                        @hasanyrole('super admin|admin')
                        <li>
                            <a href="{{ route('roles.index') }}"
                                class="@if(request()->routeIs('roles*')) active @endif">
                                <i class="ti ti-user-circle"></i><span>Role & Permissions</span>
                            </a>
                        </li>
                        @endhasanyrole

                        {{-- User Management (type: user → UserController) --}}
                        @if(auth()->user()->canAny(['add-user', 'edit-user', 'delete-user']))
                        <li>
                            <a href="{{ route('users.index', 'user') }}"
                                class="@if(request()->routeIs('users*') && request()->route('type') == 'user') active @endif">
                                <i class="ti ti-users"></i><span>User</span>
                            </a>
                        </li>
                        @endif

                        {{-- General Settings — admin only --}}
                        @hasanyrole('super admin|admin')
                        <li>
                            <a href="{{ route('generalsetting.create') }}"
                                class="@if(request()->routeIs('generalsetting*')) active @endif">
                                <i class="ti ti-settings"></i><span>General settings</span>
                            </a>
                        </li>
                        @endhasanyrole

                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
