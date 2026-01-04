<?php
require_once(__DIR__ . "/../includes/config.php");
require_once(__DIR__ . "/../includes/auth.php");
require_once(__DIR__ . "/../includes/functions.php");

requireLogin();
if (!isOwner()) {
    header('Location: ../index.php');
    exit();
}

// ==============================
// Ambil filter tanggal (optional)
// ==============================
$startDate = $_GET['start_date'] ?? '';
$endDate   = $_GET['end_date'] ?? '';

// Untuk filter berdasarkan TANGGAL ORDER
$orderWhere = "";
$orderParams = [];

if ($startDate && $endDate) {
    $orderWhere = " AND DATE(o.created_at) BETWEEN :start AND :end ";
    $orderParams[':start'] = $startDate;
    $orderParams[':end']   = $endDate;
} elseif ($startDate) {
    $orderWhere = " AND DATE(o.created_at) >= :start ";
    $orderParams[':start'] = $startDate;
} elseif ($endDate) {
    $orderWhere = " AND DATE(o.created_at) <= :end ";
    $orderParams[':end'] = $endDate;
}

// Untuk filter pelanggan baru (TANGGAL DAFTAR)
$userWhere = "";
$userParams = [];

if ($startDate && $endDate) {
    $userWhere = " AND DATE(u.created_at) BETWEEN :start AND :end ";
    $userParams[':start'] = $startDate;
    $userParams[':end']   = $endDate;
} elseif ($startDate) {
    $userWhere = " AND DATE(u.created_at) >= :start ";
    $userParams[':start'] = $startDate;
} elseif ($endDate) {
    $userWhere = " AND DATE(u.created_at) <= :end ";
    $userParams[':end'] = $endDate;
}

// ==============================
// 1) Total pelanggan terdaftar
// ==============================
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
$totalPelanggan = (int) $stmt->fetchColumn();

// ==============================
// 2) Pelanggan baru (periode filter)
// ==============================
$sqlBaru = "SELECT COUNT(*) FROM users u WHERE u.role = 'customer' {$userWhere}";
$stmt = $pdo->prepare($sqlBaru);
$stmt->execute($userParams);
$pelangganBaru = (int) $stmt->fetchColumn();

// ==============================
// 3) Statistik order per pelanggan
// ==============================
$sqlStatsOrder = "
SELECT
    COUNT(*) AS pelanggan_aktif,
    SUM(CASE WHEN jml_order = 1 THEN 1 ELSE 0 END) AS pelanggan_satu_kali,
    SUM(CASE WHEN jml_order > 1 THEN 1 ELSE 0 END) AS pelanggan_repeat,
    SUM(jml_order) AS total_order
FROM (
    SELECT customer_id, COUNT(*) AS jml_order
    FROM orders o
    WHERE 1=1 {$orderWhere}
    GROUP BY customer_id
) t
";

$stmt = $pdo->prepare($sqlStatsOrder);
$stmt->execute($orderParams);
$statsOrder = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
    'pelanggan_aktif'     => 0,
    'pelanggan_satu_kali' => 0,
    'pelanggan_repeat'    => 0,
    'total_order'         => 0,
];

$pelangganAktif     = (int) ($statsOrder['pelanggan_aktif'] ?? 0);
$pelangganSatuKali  = (int) ($statsOrder['pelanggan_satu_kali'] ?? 0);
$pelangganRepeat    = (int) ($statsOrder['pelanggan_repeat'] ?? 0);
$totalOrder         = (int) ($statsOrder['total_order'] ?? 0);
$pelangganTidakAktif = max($totalPelanggan - $pelangganAktif, 0);

// ==============================
// 4) Rating global
// ==============================
$stmt = $pdo->query("SELECT AVG(rating) AS rata_rating, COUNT(*) AS total_ulasan FROM feedback");
$rating = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['rata_rating' => null, 'total_ulasan' => 0];

$rataRating   = $rating['rata_rating'] ? round($rating['rata_rating'], 2) : 0;
$totalUlasan  = (int) ($rating['total_ulasan'] ?? 0);

// Dapatkan semua pelanggan
$stmt = $pdo->query("
    SELECT u.*, COUNT(o.id) as total_orders, 
           SUM(o.total_price) as total_spent,
           MAX(o.created_at) as last_order
    FROM users u 
    LEFT JOIN orders o ON u.id = o.customer_id 
    WHERE u.role = 'customer' 
    GROUP BY u.id 
    ORDER BY u.created_at DESC
");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="icon" href="/Adin-Laundry/assets/images/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pelanggan - Owner Adin Laundry</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* Stat card small helpers */
        .stat-card .stat-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.1rem;
        }

        .stat-card .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .stat-card .stat-value {
            font-size: 1.25rem;
            font-weight: 700;
        }

        @media (max-width:576px) {
            .stat-card .stat-value {
                font-size: 1rem
            }
        }

        .main-content { max-width: 1650px; margin: 0 auto; box-sizing: border-box; }

        /* Ensure the table area respects the page max-width and table fills it */
        .table-container { max-width: 1650px; margin: 0 auto; overflow-x: auto; }
        .table-container table { width: 100%; table-layout: auto; }

        /* Responsive fallback: allow table container to shrink on smaller screens */
        @media (max-width: 992px) {
            .table-container { max-width: 100%; padding: 0 12px; }
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <div class="main-content container-fluid">
        <div class="content-header">
            <h1>Data & Statistik Pelanggan</h1>
            <p>Kelola data pelanggan dan analisis statistik</p>
        </div>

        <!-- Filter Tanggal -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-auto">
                        <label class="form-label mb-0">Periode Order: Dari</label>
                        <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="form-control form-control-sm">
                    </div>
                    <div class="col-auto">
                        <label class="form-label mb-0">Sampai</label>
                        <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="form-control form-control-sm">
                    </div>
                    <div class="col-auto align-self-end">
                        <button type="submit" class="btn btn-primary btn-sm">Terapkan</button>
                        <a href="data_pelanggan.php" class="btn btn-secondary btn-sm">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Info periode -->
        <div class="mb-3">
            <?php if ($startDate || $endDate): ?>
                <strong>Periode order:</strong> <?= $startDate ?: 'awal' ?> s/d <?= $endDate ?: 'sekarang' ?>
            <?php else: ?>
                <strong>Periode order:</strong> semua data
            <?php endif; ?>
        </div>

        <!-- Kartu statistik utama -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon-box bg-primary me-3"><i class="fas fa-users"></i></div>
                        <div>
                            <div class="stat-value"><?php echo $totalPelanggan; ?></div>
                            <div class="stat-label">Total Pelanggan</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon-box bg-success me-3"><i class="fas fa-user-check"></i></div>
                        <div>
                            <div class="stat-value"><?php echo $pelangganAktif; ?></div>
                            <div class="stat-label">Pelanggan Aktif</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon-box bg-warning me-3"><i class="fas fa-user-clock"></i></div>
                        <div>
                            <div class="stat-value"><?php echo $pelangganTidakAktif; ?></div>
                            <div class="stat-label">Pelanggan Tidak Aktif</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon-box bg-info me-3"><i class="fas fa-user-plus"></i></div>
                        <div>
                            <div class="stat-value"><?php echo $pelangganBaru; ?></div>
                            <div class="stat-label">Pelanggan Baru</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kartu statistik order & rating -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon-box bg-secondary me-3"><i class="fas fa-shopping-cart"></i></div>
                        <div>
                            <div class="stat-value"><?php echo $totalOrder; ?></div>
                            <div class="stat-label">Total Order</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon-box bg-purple me-3" style="background:#6f42c1"><i class="fas fa-redo"></i></div>
                        <div>
                            <div class="stat-value"><?php echo $pelangganRepeat; ?></div>
                            <div class="stat-label">Pelanggan Repeat</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon-box bg-dark me-3"><i class="fas fa-user-tag"></i></div>
                        <div>
                            <div class="stat-value"><?php echo $pelangganSatuKali; ?></div>
                            <div class="stat-label">Pelanggan 1x Order</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon-box bg-warning me-3"><i class="fas fa-star"></i></div>
                        <div>
                            <div class="stat-value"><?php echo number_format($rataRating, 2, ',', '.'); ?> / 5</div>
                            <div class="stat-label">Rata-rata Rating (<?php echo $totalUlasan; ?> ulasan)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Daftar Pelanggan</h3>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Total Pesanan</th>
                                <th>Total Belanja</th>
                                <th>Pesanan Terakhir</th>
                                <th>Tanggal Bergabung</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><strong><?php echo $customer['username']; ?></strong></td>
                                    <td><?php echo $customer['email']; ?></td>
                                    <td>
                                        <a href="#" class="badge bg-primary order-link" data-customer-id="<?= $customer['id'] ?>">
                                            <?= $customer['total_orders'] ?> pesanan
                                        </a>
                                    </td>
                                    <td>Rp <?php echo number_format($customer['total_spent'] ?? 0, 0, ',', '.'); ?></td>
                                    <td>
                                        <?php if ($customer['last_order']): ?>
                                            <?php echo date('d M Y', strtotime($customer['last_order'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Belum ada pesanan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($customer['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal untuk menampilkan detail pesanan pelanggan (Bootstrap) -->
    <div class="modal fade" id="ordersModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Daftar Pesanan Pelanggan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="ordersModalBody">
                    <p class="text-muted">Memuat...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function fetchCustomerOrders(customerId) {
            const url = `../Api/admin_customer_orders.php?customer_id=${customerId}&limit=100`;
            const res = await fetch(url, {
                credentials: 'same-origin'
            });
            if (!res.ok) throw new Error('Response not ok: ' + res.status);
            return res.json();
        }

        function formatCurrency(n) {
            return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
        }

        function formatDate(dt) {
            try {
                return new Date(dt).toLocaleString('id-ID', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            } catch (e) {
                return dt;
            }
        }

        async function openOrdersModal(customerId) {
            const body = document.getElementById('ordersModalBody');
            body.innerHTML = '<p class="text-muted">Memuat...</p>';

            try {
                const orders = await fetchCustomerOrders(customerId);
                if (!Array.isArray(orders) || orders.length === 0) {
                    body.innerHTML = '<p class="text-muted">Belum ada pesanan untuk pelanggan ini.</p>';
                    const modalElEmpty = new bootstrap.Modal(document.getElementById('ordersModal'));
                    modalElEmpty.show();
                    return;
                }

                let html = '<div class="table-responsive" style="max-height:60vh; overflow:auto;"><table class="table table-sm"><thead class="table-light"><tr><th>#</th><th>Tanggal</th><th class="text-end">Total</th><th>Status</th><th>Items</th></tr></thead><tbody>';

                orders.forEach((o, idx) => {
                    const items = Array.isArray(o.items) ? o.items : [];
                    const itemsHtml = items.length ? items.map(it => `${it.service_name} (${it.quantity}x)`).join('<br>') : '-';
                    html += `<tr><td>#${o.id}</td>` +
                        `<td>${formatDate(o.created_at)}</td>` +
                        `<td class="text-end">${formatCurrency(o.total_price)}</td>` +
                        `<td>${o.tracking_status || o.status || '-'}</td>` +
                        `<td>${itemsHtml}</td></tr>`;
                });

                html += '</tbody></table></div>';
                body.innerHTML = html;
                const modalEl = new bootstrap.Modal(document.getElementById('ordersModal'));
                modalEl.show();
            } catch (err) {
                console.error(err);
                body.innerHTML = `<p class="text-danger">Gagal memuat pesanan: ${err.message}</p>`;
                const modalElErr = new bootstrap.Modal(document.getElementById('ordersModal'));
                modalElErr.show();
            }
        }

        document.addEventListener('click', function(e) {
            if (e.target.closest('.order-link')) {
                e.preventDefault();
                const el = e.target.closest('.order-link');
                const id = el.dataset.customerId;
                openOrdersModal(id);
            }
        });
    </script>
</body>

</html>