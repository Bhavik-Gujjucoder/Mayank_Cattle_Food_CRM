
<div class="sidebar" id="sidebar">
			<div class="modern-profile p-3 pb-0">

				<div class="sidebar-nav mb-3">
					<ul class="nav nav-tabs nav-tabs-solid nav-tabs-rounded nav-justified bg-transparent"
						role="tablist">
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
                           <li>
                              <a href="{{ route('dashboard') }}"><i class="ti ti-layout-2"></i><span>Dashboard</span></a>
                           </li>
                           <li>
                              <a href="#"><i class="ti ti-bell-school"></i><span>Dealer Management</span></a>
                           </li>
                           <li>
                              <a href="#"><i class="ti ti-list-check"></i><span>Soda/order Management</span></a>
                           </li>
                           <li>
                              <a href="#"><i class="ti ti-report-money"></i><span>Dispatch Management</span></a>
                           </li>
                           <li>
                              <a href="{{ route('users.index','broker') }}" class="@if(request()->routeIs('users*') && request()->route('type') == 'broker') active @endif"><i class="ti ti-user-up"></i><span>Broker management</span></a>
                           </li>
                           {{-- <li><a href="#"><i class="ti ti-list-check"></i><span>Transporter management</span></a></li> --}}
                           <li>
                              <a href="{{ route('users.index','transporter') }}" class="@if(request()->routeIs('users.index') && request()->route('type') == 'transporter') active @endif"><i class="ti ti-tir"></i><span>Transporter management</span></a>
                           </li>
                           <li>
                              <a href="#"><i class="ti ti-drop-circle"></i><span>Oil management</span></a>
                           </li>
                           <li>
                              <a href="#"><i class="ti ti-building-factory"></i><span>Machine Inventory</span></a>
                           </li>
                           <li>
                              <a href="{{ route('roles.index') }}" class="@if(request()->routeIs('roles*')) active @endif"><i class="ti ti-user-circle"></i><span>Role management</span></a>
                           </li>
                           <li>
                              <a href="{{ route('users.index','user') }}" class="@if(request()->routeIs('users*') && request()->route('type') == 'user') active @endif"><i class="ti ti-users"></i><span>User Management</span></a>
                           </li>
                           <li>
                              <a href="{{ route('generalsetting.create') }}" class="@if(request()->routeIs('generalsetting*')) active @endif"><i class="ti ti-settings"></i><span>General settings</span></a>
                           </li>
                        </ul>
                     </li>

                  </ul>
				</div>
			</div>
		</div>












{{-- <div class="sidebar" id="sidebar">
			<div class="modern-profile p-3 pb-0">

				<div class="sidebar-nav mb-3">
					<ul class="nav nav-tabs nav-tabs-solid nav-tabs-rounded nav-justified bg-transparent"
						role="tablist">
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
							<h6 class="submenu-hdr">Sales Management</h6>
							<ul>
								<li class="submenu">
									<a href="javascript:void(0);">
										<i class="ti ti-layout-2"></i><span>Sales Management</span><span class="menu-arrow"></span>
									</a>
									<ul>
										<li><a href="order-management.html" class="active">Order Management</a></li>
										<li><a href="catalogue-management.html">Catalogue Management</a></li>
										<li><a href="sales-persons.html">Sales Persons</a></li>
										<li><a href="targets.html">Targets</a></li>
									</ul>
								</li>
							</ul>
						</li>

						<li>
							 <h6 class="submenu-hdr">Customer Type Management</h6>
							<ul>
								<li class="submenu">
									<a href="javascript:void(0);">
										<i class="ti ti-layout-2"></i><span>Customer Management</span><span class="menu-arrow"></span>
									</a>
									<ul>
										<li><a href="{{ route('roles.index') }}" class="active">Roles & Permissions</a></li>
										<li><a href="{{ route('users.index') }}">Manage Users</a></li>
									</ul>
								</li>
							</ul>
						</li>

						<li>
							<ul>


								<li>
									<a href="dealership-form.html"><i class="ti ti-chart-arcs"></i><span>Dealership Form</span></a>
								</li>

								<li>
									<a href="distributor-form.html"><i class="ti ti-file-invoice"></i><span>Distributor Form</span></a>
								</li>
								 <li>
									<a href="payments.html"><i class="ti ti-report-money"></i><span>Payments</span></a>
								</li>
								<li>
									<a href="analytics.html"><i class="ti ti-chart-bar"></i><span>Analytics</span></a>
								</li>

								<li>
									<a href="contacts.html"><i class="ti ti-user-up"></i><span>Contacts</span></a>
								</li>
								<li>
									<a href="tasks.html"><i class="ti ti-user-up"></i><span>Task</span></a>
								</li>

							</ul>
						</li>
						<li>
							<h6 class="submenu-hdr">Report</h6>
							<ul>
								<li><a href="sales-person.html"><i class="ti ti-users"></i><span>Sales reports</span></a></li>
								<li><a href=""><i class="ti ti-navigation-cog"></i><span>Trend analysis</span></a></li>

							</ul>
						</li>

					</ul>
				</div>
			</div>
		</div> --}}
