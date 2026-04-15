<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>Dashboard | Mayank Cattle Food</title>

    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">

    <style>
        body { background: #f1f5f9; font-size: 14px; }

        /* Sidebar */
        .sidebar {
            width: 240px;
            height: 100vh;
            position: fixed;
            background: #0f172a;
            transition: 0.3s;
        }

        .sidebar.collapsed { width: 70px; }

        .sidebar a {
            color: #cbd5e1;
            display: block;
            padding: 12px 20px;
            text-decoration: none;
        }

        .sidebar a:hover, .sidebar a.active {
            background: #1e293b;
            color: #fff;
        }

        /* Content */
        .content {
            margin-left: 240px;
            padding: 20px;
            transition: 0.3s;
        }

        .content.expanded { margin-left: 70px; }

        /* Topbar */
        .topbar {
            background: #fff;
            padding: 12px 20px;
            border-radius: 10px;
        }

        /* KPI Cards */
        .kpi {
            border-radius: 12px;
            padding: 15px;
            color: #fff;
        }

        .kpi i { font-size: 22px; }

        .kpi small { opacity: 0.8; }

        .bg1 { background: linear-gradient(45deg,#3b82f6,#6366f1); }
        .bg2 { background: linear-gradient(45deg,#10b981,#059669); }
        .bg3 { background: linear-gradient(45deg,#f59e0b,#d97706); }
        .bg4 { background: linear-gradient(45deg,#ef4444,#dc2626); }

        .card { border: none; border-radius: 12px; }

        /* Timeline */
        .timeline p { margin-bottom: 8px; }
    </style>
</head>

<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <h5 class="text-white text-center py-3 border-bottom">Admin</h5>

    <a href="#" class="active"><i class="fas fa-home me-2"></i> Dashboard</a>
    <a href="#"><i class="fas fa-users me-2"></i> Users</a>
    <a href="#"><i class="fas fa-box me-2"></i> Products</a>
    <a href="#"><i class="fas fa-chart-line me-2"></i> Reports</a>
</div>

<!-- Content -->
<div class="content" id="content">

    <!-- Topbar -->
    <div class="topbar d-flex justify-content-between align-items-center mb-4 shadow-sm">

        <!-- Left -->
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-light btn-sm" id="toggleSidebar">
                <i class="fas fa-bars"></i>
            </button>

            <input type="text" class="form-control" placeholder="Search..." style="width:200px;">
        </div>

        <!-- Right -->
        <div class="dropdown">
            <button class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-user-circle me-1"></i> {{ Auth::user()->name }}
            </button>

            <div class="dropdown-menu dropdown-menu-end">
                <a class="dropdown-item" href="#"><i class="fas fa-user-circle me-1"></i> Profile</a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="dropdown-item"> <i class="fas fa-sign-out-alt me-1"></i>Logout</button>
                </form>
            </div>
        </div>

    </div>

    <!-- KPI -->
    <div class="row mb-4">

        <div class="col-md-3">
            <div class="kpi bg1 shadow">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6>Total Users</h6>
                        <h4>120</h4>
                        <small>+5% growth</small>
                    </div>
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="kpi bg2 shadow">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6>Orders</h6>
                        <h4>75</h4>
                        <small>+8% growth</small>
                    </div>
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="kpi bg3 shadow">
                <div>
                    <h6>Revenue</h6>
                    <h4>₹50K</h4>
                    <small>+10% growth</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="kpi bg4 shadow">
                <div>
                    <h6>Pending</h6>
                    <h4>8</h4>
                    <small>Needs attention</small>
                </div>
            </div>
        </div>

    </div>

    <!-- Chart + Activity -->
    <div class="row mb-4">

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">Sales Overview</div>
                <div class="card-body">
                    <canvas id="chart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header">Activity</div>
                <div class="card-body timeline">
                    <p>✔ New user registered</p>
                    <p>✔ Order placed</p>
                    <p>✔ Payment success</p>
                </div>
            </div>
        </div>

    </div>

    <!-- Table -->
    <div class="card shadow-sm">
        <div class="card-header">Recent Users</div>
        <div class="card-body">
            <table class="table table-hover">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                </tr>
                <tr>
                    <td>Bhavik</td>
                    <td>bhavik@example.com</td>
                    <td><span class="badge bg-success">Active</span></td>
                </tr>
            </table>
        </div>
    </div>

</div>

<!-- JS -->
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.getElementById('toggleSidebar').onclick = function () {
    document.getElementById('sidebar').classList.toggle('collapsed');
    document.getElementById('content').classList.toggle('expanded');
};

// Chart
new Chart(document.getElementById('chart'), {
    type: 'line',
    data: {
        labels: ['Jan','Feb','Mar','Apr'],
        datasets: [{
            label: 'Sales',
            data: [10,20,15,25],
            tension: 0.4
        }]
    }
});
</script>

</body>
</html>
