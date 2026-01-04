<?php
require_once(__DIR__ . "/../includes/config.php");
require_once(__DIR__ . "/../includes/auth.php");
require_once(__DIR__ . "/../includes/functions.php");

requireLogin();
if (!isAdmin() && !isOwner()) {
    header('Location: ../index.php');
    exit();
}

// Ambil parameter tanggal dari form (kalau ada)
$startDate = $_GET['start_date'] ?? '';
$endDate   = $_GET['end_date'] ?? '';
// Ambil parameter sort (qty atau penjualan, asc/desc)
$sort = $_GET['sort'] ?? 'penjualan_desc';

// Siapkan WHERE dinamis
$whereClause = '';
$params = [];

if ($startDate !== '' && $endDate !== '') {
    $whereClause = "WHERE DATE(o.created_at) BETWEEN :start_date AND :end_date";
    $params[':start_date'] = $startDate;
    $params[':end_date']   = $endDate;
} elseif ($startDate !== '') {
    $whereClause = "WHERE DATE(o.created_at) >= :start_date";
    $params[':start_date'] = $startDate;
} elseif ($endDate !== '') {
    $whereClause = "WHERE DATE(o.created_at) <= :end_date";
    $params[':end_date'] = $endDate;
}

// Tentukan ORDER BY berdasarkan pilihan sort
switch ($sort) {
    case 'qty_asc':
        $orderBySQL = 'total_qty ASC';
        break;
    case 'qty_desc':
        $orderBySQL = 'total_qty DESC';
        break;
    case 'penjualan_asc':
        $orderBySQL = 'total_penjualan ASC';
        break;
    case 'penjualan_desc':
    default:
        $orderBySQL = 'total_penjualan DESC';
        break;
}

// Query laporan penjualan per layanan
$sql = "
SELECT
    s.id AS service_id,
    s.name AS nama_layanan,
    COALESCE(SUM(oi.quantity), 0)      AS total_qty,
    COALESCE(SUM(oi.total_price), 0)   AS total_penjualan
FROM services s
LEFT JOIN order_items oi ON oi.service_id = s.id
LEFT JOIN orders o       ON o.id = oi.order_id
LEFT JOIN payments p     ON p.order_id = o.id AND p.status = 'confirmed'
{$whereClause}
GROUP BY s.id, s.name
ORDER BY " . $orderBySQL . "
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total semua penjualan
$totalSemua = 0;
foreach ($rows as $r) {
    $totalSemua += $r['total_penjualan'];
}
// Helper untuk membangun URL dengan query string (untuk toggle sort)
function q_url($overrides = [])
{
    $qp = $_GET;
    foreach ($overrides as $k => $v) {
        $qp[$k] = $v;
    }
    return 'laporan_penjualan.php' . (count($qp) ? ('?' . http_build_query($qp)) : '');
}
?>
<!doctype html>
<html lang="id">

<head>
    <link rel="icon" href="/Adin-Laundry/assets/images/favicon.ico" type="image/x-icon">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Penjualan per Layanan - Adin Laundry</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <!-- Page-specific responsive fixes to avoid content clipping on small screens -->
    <style>
        /* Ensure the main content can shrink on small screens and allow table scrolling */
        .main-content { box-sizing: border-box; max-width: 1650px; margin: 0 auto; padding: 0 12px; }
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .table { width: 100%; min-width: 0; table-layout: auto; }

        /* Compact table styles */
        .compact-table th, .compact-table td {
            padding: 0.35rem 0.5rem;
            font-size: 0.95rem;
        }
        /* Also include Bootstrap's table-sm behavior */
        .table-sm th, .table-sm td {
            padding: 0.35rem 0.5rem;
        }

        /* Allow the service name column to wrap long text instead of forcing horizontal overflow */
        .table tbody td:nth-child(2), .table thead th:nth-child(2) {
            white-space: normal; word-break: break-word; max-width: 360px;
        }

        /* Reduce padding of card and fonts on small screens */
        @media (max-width: 768px) {
            .card .card-body { padding: 0.5rem; }
            .content-header h1 { font-size: 18px; }
            .table td, .table th { font-size: 0.9rem; }
        }
        @media (max-width: 576px) {
            .card .card-body { padding: 0.4rem; }
            .content-header h1 { font-size: 16px; }
            .table td, .table th { font-size: 0.85rem; }
        }
    </style>
</head>

<body>
    <!-- Header & Sidebar -->
    <?php include 'includes/header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <div class="main-content container-fluid">
        <div class="content-header">
            <h1>Laporan Penjualan per Layanan</h1>
            <p>Pantau penjualan layanan Anda</p>
        </div>

        <!-- Form Filter Tanggal -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-auto">
                        <label for="start_date" class="form-label mb-0">Dari Tanggal</label>
                        <input type="date" name="start_date" id="start_date"
                            value="<?= htmlspecialchars($startDate) ?>" class="form-control form-control-sm">
                    </div>
                    <div class="col-auto">
                        <label for="end_date" class="form-label mb-0">Sampai Tanggal</label>
                        <input type="date" name="end_date" id="end_date"
                            value="<?= htmlspecialchars($endDate) ?>" class="form-control form-control-sm">
                    </div>
                    <div class="col-auto">
                        <label for="sort" class="form-label mb-0">Urutkan</label>
                        <select name="sort" id="sort" class="form-select form-select-sm">
                            <option value="penjualan_desc" <?= $sort === 'penjualan_desc' ? 'selected' : '' ?>>Total Penjualan (terbesar)</option>
                            <option value="penjualan_asc" <?= $sort === 'penjualan_asc' ? 'selected' : '' ?>>Total Penjualan (terkecil)</option>
                            <option value="qty_desc" <?= $sort === 'qty_desc' ? 'selected' : '' ?>>Total Qty (terbesar)</option>
                            <option value="qty_asc" <?= $sort === 'qty_asc' ? 'selected' : '' ?>>Total Qty (terkecil)</option>
                        </select>
                    </div>
                    <div class="col-auto align-self-end">
                        <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
                        <a href="laporan_penjualan.php" class="btn btn-secondary btn-sm">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Info Periode -->
        <div class="mb-3">
            <?php if ($startDate || $endDate): ?>
                <strong>Periode:</strong>
                <?= $startDate ?: 'awal' ?> s/d <?= $endDate ?: 'sekarang' ?>
            <?php else: ?>
                <strong>Periode:</strong> Semua data
            <?php endif; ?>
        </div>

        <!-- Tabel Laporan -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm mb-0 compact-table">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 60px;">No</th>
                                <th>Layanan</th>
                                <th class="text-end">
                                    <?php $qtyToggle = ($sort === 'qty_asc' ? 'qty_desc' : 'qty_asc'); ?>
                                    <a href="<?= q_url(['sort' => $qtyToggle]) ?>">Total Qty
                                        <?php if (strpos($sort, 'qty') === 0): ?>
                                            <?= $sort === 'qty_asc' ? '▲' : '▼' ?>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="text-end">
                                    <?php $penToggle = ($sort === 'penjualan_asc' ? 'penjualan_desc' : 'penjualan_asc'); ?>
                                    <a href="<?= q_url(['sort' => $penToggle]) ?>">Total Penjualan (Rp)
                                        <?php if (strpos($sort, 'penjualan') === 0): ?>
                                            <?= $sort === 'penjualan_asc' ? '▲' : '▼' ?>
                                        <?php endif; ?>
                                    </a>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3">Belum ada data untuk periode ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; ?>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['nama_layanan']) ?></td>
                                        <td class="text-end"><?= (int)$row['total_qty'] ?></td>
                                        <td class="text-end">
                                            <?= number_format($row['total_penjualan'], 0, ',', '.') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-secondary">
                                    <th colspan="3" class="text-end">TOTAL</th>
                                    <th class="text-end">
                                        <?= number_format($totalSemua, 0, ',', '.') ?>
                                    </th>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>