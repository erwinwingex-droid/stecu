<?php
require_once(__DIR__ . "/../includes/config.php");
require_once(__DIR__ . "/../includes/auth.php");
require_once(__DIR__ . "/../includes/functions.php");

requireLogin();
if (!isOwner()) {
    header('Location: ../index.php');
    exit();
}

// Statistik Pendapatan Harian (Hari ini saja - 1 hari)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE DATE(created_at) = CURDATE()");
$stmt->execute();
$todayRevenue = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Statistik Pendapatan untuk grafik harian: Breakdown per jam untuk hari ini
$dailyRevenue = [];
$dailyLabels = [];
for ($h = 0; $h < 24; $h++) {
    $label = sprintf('%02d:00', $h);
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE DATE(created_at) = CURDATE() AND HOUR(created_at) = ?");
    $stmt->execute([$h]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $dailyRevenue[] = (float)$result['total'];
    $dailyLabels[] = $label;
}

// Statistik Pendapatan Mingguan (7 hari terakhir - bukan minggu kalender)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND DATE(created_at) < CURDATE()");
$stmt->execute();
$thisWeekRevenue = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Statistik Pendapatan Mingguan: 7 hari terakhir (per hari)
$weeklyRevenue = [];
$weeklyLabels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE DATE(created_at) = ?");
    $stmt->execute([$date]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $weeklyRevenue[] = (float)$result['total'];
    $weeklyLabels[] = date('d M', strtotime($date));
}

// Statistik Pendapatan Bulanan (30 hari terakhir)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND DATE(created_at) < CURDATE()");
$stmt->execute();
$thisMonthRevenue = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Statistik Pendapatan Bulanan: 30 hari terakhir (per hari)
$monthlyRevenue = [];
$monthlyLabels = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE DATE(created_at) = ?");
    $stmt->execute([$date]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $monthlyRevenue[] = (float)$result['total'];
    $monthlyLabels[] = date('d M', strtotime($date));
}

// Total pendapatan (dari SEMUA pesanan - terlepas dari status)
$stmt = $pdo->query("SELECT COALESCE(SUM(total_price), 0) as total FROM orders");
$totalRevenue = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Debug: Ambil breakdown semua status untuk referensi
$stmt = $pdo->query("SELECT tracking_status, COUNT(*) as count, COALESCE(SUM(total_price), 0) as total FROM orders GROUP BY tracking_status");
$statusBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
$thisMonthRevenue = $monthlyRevenue[11] ?? 0;

// Pesanan terbaru
$stmt = $pdo->query(
    "SELECT o.*, u.username AS customer_name FROM orders o JOIN users u ON o.customer_id = u.id ORDER BY o.created_at DESC LIMIT 5"
);
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// DEBUG: Cetak info breakdown status (untuk debugging)
// Uncomment baris berikut jika ingin melihat breakdown:
// echo "<!-- Total Completed: " . $totalRevenue . " | Breakdown: " . json_encode($statusBreakdown) . " -->";

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="icon" href="/Adin-Laundry/assets/images/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Owner - Adin Laundry</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .charts-section {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        .chart-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #ecf0f1;
            transition: all 0.3s ease;
        }

        .chart-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .chart-card h3 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            color: #2c3e50;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .stat-card .card-body {
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 18px;
        }

        /* Visual style matching provided mock: left colored accent + rounded icon box */
        .stat-card {
            border-radius: 12px;
        }

        .stat-card .stat-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.1rem;
            box-shadow: 0 6px 18px rgba(16, 24, 40, 0.06);
        }

        .stat-card .stat-label {
            font-size: 0.85rem;
            color: #6b7280;
            margin-bottom: 6px;
        }

        .stat-card .stat-value {
            font-size: 1.45rem;
            font-weight: 700;
            color: #0f172a;
        }

        .stat-card .accent-edge {
            position: absolute;
            left: 0;
            top: 12px;
            bottom: 12px;
            width: 6px;
            border-radius: 6px 0 0 6px;
        }

        .stat-card .card-body {
            position: relative;
        }

        .stat-info h3 {
            margin: 0;
            font-size: 1.8rem;
            color: #2c3e50;
            font-weight: 700;
        }

        .stat-info p {
            margin: 0;
            color: #7f8c8d;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .content-header {
            margin-bottom: 2rem;
        }

        .content-header h1 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .content-header p {
            color: #7f8c8d;
            font-size: 1rem;
        }
    </style>
</head>

<body>
    <!-- Header + Sidebar Owner -->
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-header d-flex justify-content-between align-items-start">
            <div>
                <h1>📊 Dashboard Pendapatan</h1>
                <p>Pantau statistik pendapatan Anda secara real-time</p>
            </div>
            <div class="d-flex gap-2 align-items-start">
                <div class="input-group input-group-sm me-2">
                    <span class="input-group-text">Dari</span>
                    <input type="date" id="exportStart" class="form-control form-control-sm" aria-label="Start date">
                    <span class="input-group-text">s/d</span>
                    <input type="date" id="exportEnd" class="form-control form-control-sm" aria-label="End date">
                </div>
                <button id="exportExcelBtn" class="btn btn-success btn-sm" title="Export penjualan, pelanggan, feedback ke Excel">
                    <i class="bi bi-download"></i> Export ke Excel
                </button>
            </div>
        </div>

        <!-- Statistik Ringkas (Bootstrap grid) -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card border-0 shadow-sm position-relative" style="border-left:4px solid #27ae60;">
                    <div class="accent-edge" style="background:#27ae60"></div>
                    <div class="card-body">
                        <div class="d-flex align-items-center w-100">
                            <div class="me-3">
                                <div class="stat-icon-box" style="background:#27ae60"><i class="fas fa-calendar-day"></i></div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="stat-label">Pendapatan Hari Ini</div>
                                <div class="stat-value" data-stat="today">Rp <?php echo number_format((float)$todayRevenue, 0, ',', '.'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card border-0 shadow-sm position-relative" style="border-left:4px solid #3498db;">
                    <div class="accent-edge" style="background:#3498db"></div>
                    <div class="card-body">
                        <div class="d-flex align-items-center w-100">
                            <div class="me-3">
                                <div class="stat-icon-box" style="background:#3498db"><i class="fas fa-calendar-week"></i></div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="stat-label">Pendapatan 7 Hari</div>
                                <div class="stat-value" data-stat="week">Rp <?php echo number_format((float)$thisWeekRevenue, 0, ',', '.'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card border-0 shadow-sm position-relative" style="border-left:4px solid #9b59b6;">
                    <div class="accent-edge" style="background:#9b59b6"></div>
                    <div class="card-body">
                        <div class="d-flex align-items-center w-100">
                            <div class="me-3">
                                <div class="stat-icon-box" style="background:#9b59b6"><i class="fas fa-calendar-alt"></i></div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="stat-label">Pendapatan 30 Hari</div>
                                <div class="stat-value" data-stat="month">Rp <?php echo number_format((float)$thisMonthRevenue, 0, ',', '.'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card border-0 shadow-sm position-relative" style="border-left:4px solid #e74c3c;">
                    <div class="accent-edge" style="background:#e74c3c"></div>
                    <div class="card-body">
                        <div class="d-flex align-items-center w-100">
                            <div class="me-3">
                                <div class="stat-icon-box bg-danger" style="background:#e74c3c"><i class="fas fa-chart-line"></i></div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="stat-label">Total Pendapatan</div>
                                <div class="stat-value" data-stat="total">Rp <?php echo number_format((float)$totalRevenue, 0, ',', '.'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pencarian Periode Pendapatan (modern) -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body">
                <div class="row g-2 align-items-center">
                    <div class="col-md-6">
                        <label class="form-label mb-1">Pilih Periode</label>
                        <div class="input-group">
                            <input type="date" id="revStart" class="form-control" aria-label="Periode dari">
                            <span class="input-group-text">s/d</span>
                            <input type="date" id="revEnd" class="form-control" aria-label="Periode sampai">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Preset</button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item preset-range" href="#" data-days="0">Hari Ini</a></li>
                                <li><a class="dropdown-item preset-range" href="#" data-days="7">7 Hari Terakhir</a></li>
                                <li><a class="dropdown-item preset-range" href="#" data-days="30">30 Hari Terakhir</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label mb-1">Aksi</label>
                        <div class="d-flex gap-2">
                            <button id="searchRevenueBtn" class="btn btn-primary w-50">Cari</button>
                            <button id="resetRevenueBtn" class="btn btn-outline-secondary w-50">Reset</button>
                        </div>
                    </div>

                    <div class="col-md-2 text-md-end">
                        <label class="form-label mb-1">Jumlah Periode</label>
                        <div class="badge bg-light text-dark p-3 w-100 shadow-sm" style="border-radius:12px;">
                            <div class="small text-muted">Total</div>
                            <div class="h5 mb-0" data-stat="period">-</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grafik Statistik (Bootstrap grid) -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i> Pendapatan Hari Ini (Per Jam)</h5>
                    </div>
                    <div class="card-body" style="min-height:300px;">
                        <canvas id="dailyChart" style="width:100%; height:100%;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0"><i class="fas fa-calendar-week me-2"></i> Pendapatan 7 Hari</h5>
                    </div>
                    <div class="card-body" style="min-height:300px;">
                        <canvas id="weeklyChart" style="width:100%; height:100%;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i> Pendapatan 30 Hari</h5>
                    </div>
                    <div class="card-body" style="min-height:300px;">
                        <canvas id="monthlyChart" style="width:100%; height:100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="recent-orders">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">Pesanan Terbaru</h5>
                <a href="../admin/orders.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Pelanggan</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $o): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($o['id']); ?></td>
                                <td><?php echo htmlspecialchars($o['customer_name']); ?></td>
                                <td>Rp <?php echo number_format($o['total_price']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($o['tracking_status'])); ?></td>
                                <td><?php echo htmlspecialchars($o['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
        // Warna gradient yang cantik
        const ctx1 = document.getElementById('dailyChart').getContext('2d');
        const gradient1 = ctx1.createLinearGradient(0, 0, 0, 300);
        gradient1.addColorStop(0, 'rgba(39, 174, 96, 0.4)');
        gradient1.addColorStop(1, 'rgba(39, 174, 96, 0)');

        const dailyChart = new Chart(ctx1, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dailyLabels); ?>,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: <?php echo json_encode($dailyRevenue); ?>,
                    borderColor: '#27ae60',
                    backgroundColor: gradient1,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#27ae60',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            usePointStyle: true,
                            padding: 15
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });

        // Grafik Mingguan
        const ctx2 = document.getElementById('weeklyChart').getContext('2d');
        const gradient2 = ctx2.createLinearGradient(0, 0, 0, 300);
        gradient2.addColorStop(0, 'rgba(52, 152, 219, 0.4)');
        gradient2.addColorStop(1, 'rgba(52, 152, 219, 0)');

        const weeklyChart = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($weeklyLabels); ?>,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: <?php echo json_encode($weeklyRevenue); ?>,
                    backgroundColor: [
                        '#3498db', '#5dade2', '#85c1e2', '#aad5e8',
                        '#3498db', '#5dade2', '#85c1e2', '#aad5e8',
                        '#3498db', '#5dade2', '#85c1e2', '#aad5e8'
                    ],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            usePointStyle: true,
                            padding: 15
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });

        // Grafik Bulanan
        const ctx3 = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(ctx3, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($monthlyLabels); ?>,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: <?php echo json_encode($monthlyRevenue); ?>,
                    borderColor: '#9b59b6',
                    backgroundColor: 'rgba(155, 89, 182, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointBackgroundColor: '#9b59b6',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            usePointStyle: true,
                            padding: 15
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    </script>

    <!-- Script untuk Real-time Update Statistik -->
    <script>
        // Update statistik pendapatan secara real-time setiap 30 detik
        function updateRevenueStats() {
            fetch('get_revenue_stats.php')
                .then(response => response.json())
                .then(data => {
                    // Update statistik ringkas
                    document.querySelector('[data-stat="today"]')?.textContent =
                        'Rp ' + parseInt(data.today_revenue || 0).toLocaleString('id-ID');
                    document.querySelector('[data-stat="week"]')?.textContent =
                        'Rp ' + parseInt(data.week_revenue || 0).toLocaleString('id-ID');
                    document.querySelector('[data-stat="month"]')?.textContent =
                        'Rp ' + parseInt(data.month_revenue || 0).toLocaleString('id-ID');
                    document.querySelector('[data-stat="total"]')?.textContent =
                        'Rp ' + parseInt(data.total_revenue || 0).toLocaleString('id-ID');

                    // Update charts if data present
                    if (typeof dailyChart !== 'undefined' && data.daily_labels && data.daily_data) {
                        dailyChart.data.labels = data.daily_labels;
                        dailyChart.data.datasets[0].data = data.daily_data.map(x => parseFloat(x));
                        dailyChart.update();
                    }

                    if (typeof weeklyChart !== 'undefined' && data.weekly_labels && data.weekly_data) {
                        weeklyChart.data.labels = data.weekly_labels;
                        weeklyChart.data.datasets[0].data = data.weekly_data.map(x => parseFloat(x));
                        weeklyChart.update();
                    }

                    if (typeof monthlyChart !== 'undefined' && data.monthly_labels && data.monthly_data) {
                        monthlyChart.data.labels = data.monthly_labels;
                        monthlyChart.data.datasets[0].data = data.monthly_data.map(x => parseFloat(x));
                        monthlyChart.update();
                    }
                })
                .catch(error => console.log('Update failed:', error));
        }

        // Update setiap 30 detik (30000 ms)
        setInterval(updateRevenueStats, 30000);

        // Juga update saat halaman pertama load untuk konsistensi
        updateRevenueStats();
    </script>
    <script>
        // Simpan data chart harian asli untuk restore
        const originalDailyLabels = Array.isArray(dailyChart.data.labels) ? dailyChart.data.labels.slice() : [];
        const originalDailyData = Array.isArray(dailyChart.data.datasets[0].data) ? dailyChart.data.datasets[0].data.slice() : [];

        async function fetchRevenuePeriod(start, end) {
            if (!start || !end) return;
            try {
                const resp = await fetch(`get_revenue_stats.php?start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}`);
                const data = await resp.json();
                // Update period total display
                if (typeof data.period_total !== 'undefined' && data.period_total !== null) {
                    document.querySelector('[data-stat="period"]').textContent = 'Rp ' + parseInt(data.period_total || 0).toLocaleString('id-ID');
                } else {
                    document.querySelector('[data-stat="period"]').textContent = '-';
                }

                // If period_labels/data present, update dailyChart to show period breakdown
                if (data.period_labels && data.period_data && Array.isArray(data.period_labels) && Array.isArray(data.period_data)) {
                    dailyChart.data.labels = data.period_labels;
                    dailyChart.data.datasets[0].data = data.period_data.map(x => parseFloat(x));
                    dailyChart.update();
                }
            } catch (err) {
                console.error('fetchRevenuePeriod error', err);
                alert('Gagal mengambil data periode.');
            }
        }

        document.getElementById('searchRevenueBtn').addEventListener('click', function() {
            const s = document.getElementById('revStart').value;
            const e = document.getElementById('revEnd').value;
            if (!s || !e) {
                alert('Silakan pilih tanggal mulai dan sampai.');
                return;
            }
            fetchRevenuePeriod(s, e);
        });

        document.getElementById('resetRevenueBtn').addEventListener('click', function() {
            // Restore original daily chart and clear period stat
            dailyChart.data.labels = originalDailyLabels.slice();
            dailyChart.data.datasets[0].data = originalDailyData.slice();
            dailyChart.update();
            document.querySelector('[data-stat="period"]').textContent = '-';
        });

        // Preset range selector handling (uses data-days attribute)
        document.querySelectorAll('.preset-range').forEach(function(el) {
            el.addEventListener('click', function(ev) {
                ev.preventDefault();
                const days = parseInt(this.getAttribute('data-days'));
                const end = new Date();
                const start = new Date();
                start.setDate(end.getDate() - (days === 0 ? 0 : (days - 1)));
                // Format as yyyy-mm-dd
                const fmt = d => d.toISOString().slice(0, 10);
                document.getElementById('revStart').value = fmt(start);
                document.getElementById('revEnd').value = fmt(end);
                // trigger search
                document.getElementById('searchRevenueBtn').click();
            });
        });

        // Export Excel button: build URL with optional start/end dates and open in new tab
        document.getElementById('exportExcelBtn')?.addEventListener('click', function(ev) {
            ev.preventDefault();
            const s = document.getElementById('exportStart').value;
            const e = document.getElementById('exportEnd').value;
            let url = 'export_excel.php';
            const params = new URLSearchParams();
            if (s) params.append('start_date', s);
            if (e) params.append('end_date', e);
            const q = params.toString();
            if (q) url += '?' + q;
            window.open(url, '_blank');
        });
    </script>
</body>

</html>