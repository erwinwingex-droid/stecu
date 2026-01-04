<?php
include '../includes/auth.php';
requireAdmin();
include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="icon" href="/Adin-Laundry/assets/images/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Adin Laundry</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Dashboard CSS -->
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <div class="admin-container">
        <!-- Sidebar Admin -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header Dashboard -->
            <div class="dashboard-header">
                <div>
                    <h1 class="dashboard-title">Dashboard Admin</h1>
                    <p class="dashboard-subtitle">Selamat datang, <span class="username"><?php echo $_SESSION['username']; ?></span>!</p>
                </div>
                <div class="dashboard-actions">
                    <button class="btn btn-outline-secondary btn-sm" id="refresh-dashboard">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear"></i> Pengaturan
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" id="toggle-recent-orders"><i class="bi bi-eye-slash"></i> Sembunyikan Pesanan Terbaru</a></li>
                            <li><a class="dropdown-item" href="#" id="export-stats"><i class="bi bi-download"></i> Ekspor Statistik</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-lg-6">
                    <div class="stat-card card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Total Pesanan</h6>
                                    <h3 class="mb-0" id="total-orders">0</h3>
                                </div>
                                <div class="stat-icon bg-primary">
                                    <i class="bi bi-cart-check"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="text-success"><i class="bi bi-arrow-up"></i> 12%</span>
                                <span class="text-muted ms-2">dari bulan lalu</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6">
                    <div class="stat-card card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Total Pelanggan</h6>
                                    <h3 class="mb-0" id="total-customers">0</h3>
                                </div>
                                <div class="stat-icon bg-success">
                                    <i class="bi bi-people"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="text-success"><i class="bi bi-arrow-up"></i> 8%</span>
                                <span class="text-muted ms-2">dari bulan lalu</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6">
                    <div class="stat-card card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Total Pendapatan</h6>
                                    <h3 class="mb-0" id="total-revenue">Rp 0</h3>
                                </div>
                                <div class="stat-icon bg-warning">
                                    <i class="bi bi-currency-dollar"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="text-success"><i class="bi bi-arrow-up"></i> 15%</span>
                                <span class="text-muted ms-2">dari bulan lalu</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6">
                    <div class="stat-card card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Rating Rata-rata</h6>
                                    <h3 class="mb-0" id="avg-rating">0.0</h3>
                                </div>
                                <div class="stat-icon bg-info">
                                    <i class="bi bi-star"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="text-success"><i class="bi bi-arrow-up"></i> 0.2</span>
                                <span class="text-muted ms-2">dari bulan lalu</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0">
                            <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Pendapatan Bulanan</h5>
                            <div class="chart-period-selector">
                                <select class="form-select form-select-sm" id="revenue-period">
                                    <option value="monthly">Bulan ini</option>
                                    <option value="quarterly">3 Bulan Terakhir</option>
                                    <option value="yearly">Tahun ini</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="revenueChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0">
                            <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Layanan Terpopuler</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="servicesChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Section -->
            <div class="recent-orders-section" id="recent-orders-section">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Pesanan Terbaru</h5>
                        <button class="btn btn-outline-secondary btn-sm" id="toggle-orders-visibility">
                            <i class="bi bi-eye-slash"></i> Sembunyikan
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="recent-orders-table">
                                <thead>
                                    <tr>
                                        <th>ID Pesanan</th>
                                        <th>Pelanggan</th>
                                        <th>Layanan</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data akan dimuat via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="orders.php" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-list-ul me-1"></i>Lihat Semua Pesanan
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="row g-4 mt-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0">
                            <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Status Pesanan Hari Ini</h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="p-3">
                                        <h4 class="text-primary mb-1" id="today-pending">0</h4>
                                        <small class="text-muted">Pending</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-3">
                                        <h4 class="text-info mb-1" id="today-processing">0</h4>
                                        <small class="text-muted">Diproses</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-3">
                                        <h4 class="text-success mb-1" id="today-completed">0</h4>
                                        <small class="text-muted">Selesai</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0">
                            <h6 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Pesanan Mendatang</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span>Hari ini</span>
                                <span class="badge bg-primary" id="upcoming-today">0</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span>Besok</span>
                                <span class="badge bg-info" id="upcoming-tomorrow">0</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <span>7 hari ke depan</span>
                                <span class="badge bg-warning" id="upcoming-week">0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Dashboard JS -->
    <script src="../assets/js/dashboard.js"></script>
</body>

</html>