<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();
if (!isAdmin() && !isOwner()) {
    header('Location: index.php');
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
//    - pelanggan aktif
//    - 1x order
//    - repeat
//    - total order
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

// ==============================
// 5) Pelanggan baru per bulan (6 bulan terakhir)
// ==============================
$sqlPerBulan = "
SELECT DATE_FORMAT(u.created_at, '%Y-%m') AS bulan, COUNT(*) AS jml
FROM users u
WHERE u.role = 'customer'
GROUP BY DATE_FORMAT(u.created_at, '%Y-%m')
ORDER BY bulan DESC
LIMIT 6
";
$stmt = $pdo->query($sqlPerBulan);
$perBulan = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">
<head>
    <link rel="icon" href="/Adin-Laundry/assets/images/favicon.ico" type="image/x-icon">
    <meta charset="utf-8">
    <title>Statistik Pelanggan - Adin Laundry</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container py-4">
    <h1 class="mb-4">Statistik Pelanggan</h1>

    <!-- Filter Tanggal -->
    <form method="get" class="row g-3 mb-4">
        <div class="col-auto">
            <label class="form-label mb-0">Periode Order: Dari</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="form-control form-control-sm">
        </div>
        <div class="col-auto">
            <label class="form-label mb-0">Sampai</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="form-control form-control-sm">
        </div>
        <div class="col-auto align-self-end">
            <button class="btn btn-primary btn-sm">Terapkan</button>
            <a href="statistik_pelanggan.php" class="btn btn-secondary btn-sm">Reset</a>
        </div>
    </form>

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
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Total Pelanggan</h6>
                    <h3 class="mb-0"><?= $totalPelanggan ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Pelanggan Aktif (punya order)</h6>
                    <h3 class="mb-0"><?= $pelangganAktif ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Pelanggan Tidak Aktif</h6>
                    <h3 class="mb-0"><?= $pelangganTidakAktif ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Pelanggan Baru (periode daftar)</h6>
                    <h3 class="mb-0"><?= $pelangganBaru ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Kartu statistik order & rating -->
    <div class="row g-3 mb-4">
        <div class="col-md-4 col-sm-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Total Order (periode order)</h6>
                    <h3 class="mb-1"><?= $totalOrder ?></h3>
                    <small class="text-muted">Dari semua pelanggan aktif</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Pelanggan Repeat</h6>
                    <h3 class="mb-1"><?= $pelangganRepeat ?></h3>
                    <small class="text-muted">Pelanggan dengan &gt; 1 order</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Pelanggan 1x Order</h6>
                    <h3 class="mb-1"><?= $pelangganSatuKali ?></h3>
                    <small class="text-muted">Perlu di-follow up?</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Rating -->
    <div class="row g-3 mb-4">
        <div class="col-md-4 col-sm-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Rata-rata Rating</h6>
                    <h3 class="mb-1"><?= number_format($rataRating, 2, ',', '.') ?> / 5</h3>
                    <small class="text-muted">Dari <?= $totalUlasan ?> ulasan</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel pelanggan baru per bulan -->
    <div class="card mb-4">
        <div class="card-header">
            Pelanggan Baru per Bulan (max 6 bulan terakhir)
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Bulan</th>
                            <th class="text-end">Jumlah Pelanggan Baru</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($perBulan)): ?>
                        <tr>
                            <td colspan="2" class="text-center py-3">Belum ada data pelanggan baru.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($perBulan as $b): ?>
                            <tr>
                                <td><?= htmlspecialchars($b['bulan']) ?></td>
                                <td class="text-end"><?= (int)$b['jml'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

</body>
</html>
