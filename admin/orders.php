<?php
include '../includes/auth.php';
requireAdmin();
include '../includes/functions.php';
global $pdo;

/* ==========================
   FETCH LIST KURIR
   ========================== */
// Ambil semua kurir aktif
$courierStmt = $pdo->query("SELECT c.*, u.email AS email FROM couriers c LEFT JOIN users u ON u.whatsapp = c.phone AND u.role = 'kurir' WHERE c.is_active = 1");
$couriers = $courierStmt->fetchAll(PDO::FETCH_ASSOC);


/* ============================================================
   UPDATE STATUS PESANAN
   ============================================================ */
if (isset($_POST['update_status'])) {
    $order_id = sanitize($_POST['order_id']);
    $status   = sanitize($_POST['status']);

    if (updateOrderStatus($order_id, $status)) {
        $success = "Status pesanan berhasil diupdate!";
    } else {
        $error = "Gagal mengupdate status pesanan!";
    }
}

/* ============================================================
   UPDATE TRACKING PESANAN
   ============================================================ */
if (isset($_POST['update_tracking'])) {
    $order_id = (int) $_POST['order_id'];
    $tracking = $_POST['tracking_status'];
    $note     = $_POST['tracking_note'] ?? '';

    $allowed = [
        'pending',
        'picked_up',
        'washing',
        'drying',
        'ironing',
        'delivering',
        'completed'
    ];

    if (!in_array($tracking, $allowed)) {
        $error = "Tracking status tidak valid!";
    } else {

        $update = $pdo->prepare("
            UPDATE orders SET tracking_status=?, tracking_updated=NOW() WHERE id=?
        ");
        $update->execute([$tracking, $order_id]);

        $ins = $pdo->prepare("
            INSERT INTO tracking_history (order_id, status, note, updated_by)
            VALUES (?,?,?,?)
        ");
        $ins->execute([$order_id, $tracking, $note, $_SESSION['username'] ?? 'Admin']);

        $success = "Tracking pesanan berhasil diperbarui!";
    }
}

/* ============================================================
   UPDATE STATUS PEMBAYARAN (FIX BARU DITAMBAH)
   ============================================================ */
if (isset($_POST['update_payment'])) {

    $order_id = sanitize($_POST['order_id']);
    $payment_status = sanitize($_POST['payment_status']);

    if (!in_array($payment_status, ['waiting', 'waiting_confirmation', 'invalid', 'confirmed'])) {
        $error = "Status pembayaran tidak valid!";
    } else {

        $stmt = $pdo->prepare("UPDATE payments SET status=? WHERE order_id=?");
        if ($stmt->execute([$payment_status, $order_id])) {
            $success = "Status pembayaran berhasil diperbarui!";
        } else {
            $error = "Gagal mengupdate status pembayaran!";
        }
    }
}

/* ============================================================
   FETCH PESANAN BARU HARI INI (HANYA STATUS PENDING)
   ============================================================ */
$newOrdersStmt = $pdo->prepare("
    SELECT 
        o.id,
        o.customer_id,
        o.total_price,
        o.status,
        o.tracking_status,
        o.delivery_address,
        o.notes,
        o.pickup_date,
        o.pickup_time,
        o.created_at,

        u.username AS customer_name,
        u.email    AS customer_email,
        u.whatsapp AS customer_whatsapp,

        p.method   AS payment_method,
        p.amount   AS payment_amount,
        p.status   AS payment_status,
        p.bukti    AS payment_proof,

        -- Ambil item pesanan
        GROUP_CONCAT(
            CONCAT(
                si.name, ' (', oi.quantity, ' pcs) - Rp ', FORMAT(oi.price,0)
            )
            SEPARATOR ' | '
        ) AS item_list

    FROM orders o

    JOIN users u ON o.customer_id = u.id
    LEFT JOIN payments p ON p.order_id = o.id
    LEFT JOIN order_items oi ON oi.order_id = o.id
    LEFT JOIN services si ON si.id = oi.service_id

    WHERE DATE(o.created_at) = CURDATE() AND o.tracking_status = 'pending'
    GROUP BY o.id
    ORDER BY o.created_at DESC
");

$newOrdersStmt->execute();
$new_orders = $newOrdersStmt->fetchAll(PDO::FETCH_ASSOC);
$new_orders_count = count($new_orders);

/* ============================================================
   FETCH PESANAN HARI INI (SEMUA STATUS, TIDAK HANYA PENDING)
   ============================================================ */
$todaysOrdersStmt = $pdo->prepare("
    SELECT 
        o.id,
        o.customer_id,
        o.total_price,
        o.status,
        o.tracking_status,
        o.delivery_address,
        o.notes,
        o.pickup_date,
        o.pickup_time,
        o.created_at,

        u.username AS customer_name,
        u.email    AS customer_email,
        u.whatsapp AS customer_whatsapp,

        p.method   AS payment_method,
        p.amount   AS payment_amount,
        p.status   AS payment_status,
        p.bukti    AS payment_proof,

        -- Ambil item pesanan
        GROUP_CONCAT(
            CONCAT(
                si.name, ' (', oi.quantity, ' pcs) - Rp ', FORMAT(oi.price,0)
            )
            SEPARATOR ' | '
        ) AS item_list

    FROM orders o

    JOIN users u ON o.customer_id = u.id
    LEFT JOIN payments p ON p.order_id = o.id
    LEFT JOIN order_items oi ON oi.order_id = o.id
    LEFT JOIN services si ON si.id = oi.service_id

    WHERE DATE(o.created_at) = CURDATE()
    GROUP BY o.id
    ORDER BY o.created_at DESC
");

$todaysOrdersStmt->execute();
$todays_orders = $todaysOrdersStmt->fetchAll(PDO::FETCH_ASSOC);
$todays_orders_count = count($todays_orders);

/* ============================================================
   FETCH DATA PESANAN (REVISI: TAMBAH WHATSAPP)
   ============================================================ */
$stmt = $pdo->prepare("
    SELECT 
        o.id,
        o.customer_id,
        o.total_price,
        o.status,
        o.tracking_status,
        o.delivery_address,
        o.notes,
        o.pickup_date,
        o.pickup_time,
        o.created_at,

        u.username AS customer_name,
        u.email    AS customer_email,
        u.whatsapp AS customer_whatsapp,

        p.method   AS payment_method,
        p.amount   AS payment_amount,
        p.status   AS payment_status,
        p.bukti    AS payment_proof,

        -- Ambil item pesanan
        GROUP_CONCAT(
            CONCAT(
                si.name, ' (', oi.quantity, ' pcs) - Rp ', FORMAT(oi.price,0)
            )
            SEPARATOR ' | '
        ) AS item_list

    FROM orders o

    JOIN users u ON o.customer_id = u.id
    LEFT JOIN payments p ON p.order_id = o.id
    LEFT JOIN order_items oi ON oi.order_id = o.id
    LEFT JOIN services si ON si.id = oi.service_id

    GROUP BY o.id
    ORDER BY o.created_at DESC
");

$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   LABEL TRACKING
   ============================================================ */
$tracking_map = [
    'pending' => 'Pesanan Dibuat',
    'picked_up' => 'Kurir Menjemput',
    'washing' => 'Sedang Dicuci',
    'drying' => 'Pengeringan',
    'ironing' => 'Penyetrikaan',
    'delivering' => 'Dalam Pengantaran',
    'completed' => 'Selesai'
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="icon" href="/Adin-Laundry/assets/images/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pesanan - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        /* Ensure proper layout structure */
        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            overflow-x: hidden;
        }

        body {
            margin: 0;
            padding-top: 150px;
            font-family: 'Poppins', sans-serif;
        }

        .badge-track {
            padding: 4px 8px;
            font-size: 10px;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
        }

        .badge-track.pending {
            background: #eee;
            color: #444;
        }

        .badge-track.picked_up {
            background: #ffe8c8;
            color: #9a5b00;
        }

        .badge-track.washing {
            background: #cce5ff;
            color: #004085;
        }

        .badge-track.drying {
            background: #ffe6e6;
            color: #8b0000;
        }

        .badge-track.ironing {
            background: #f4d6ff;
            color: #62007a;
        }

        .badge-track.delivering {
            background: #d1f5d1;
            color: #1c7c1a;
        }

        .badge-track.completed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .modal-tracking {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            overflow-y: auto;
        }

        .modal-tracking .modal-content {
            width: 430px;
            max-width: 90%;
            margin: 10% auto;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        /* Courier selection modal (modern) */
        .courier-search {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            margin-bottom: 12px;
            box-sizing: border-box;
        }

        /* helper to limit text blocks to a couple of lines */
        .text-clamp-2 {
            /* preferred WebKit clamp */
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;

            /* modern/standard property (where supported) */
            line-clamp: 2;

            /* fallback for non-WebKit browsers: limit height based on line-height */
            line-height: 1.2em;
            max-height: calc(1.2em * 2);

            overflow: hidden;
            /* ensure wrapping and break long words so clamp works reliably */
            white-space: normal;
            word-break: break-word;
            hyphens: auto;
        }

        .courier-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
            max-height: 320px;
            overflow: auto;
            padding-right: 6px;
        }

        .courier-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px;
            border: 1px solid #eef2f5;
            border-radius: 8px;
            background: #fff;
        }

        .courier-card .courier-info {
            flex: 1;
        }

        .courier-card .courier-name {
            font-weight: 700;
            color: #333;
        }

        .courier-card .courier-meta {
            font-size: 12px;
            color: #666;
        }

        .courier-confirm-area {
            margin-top: 12px;
            padding: 10px;
            border-radius: 6px;
            background: #f7fbff;
            display: none;
        }

        /* Action buttons styling */
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }

        .action-buttons .btn {
            margin-right: 0;
        }

        .btn-sm {
            padding: 5px 8px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            font-size: 12px;
            transition: all 0.2s ease;
        }

        .btn-sm:hover {
            opacity: 0.8;
            transform: translateY(-1px);
        }

        .btn-primary {
            background: #1976d2;
            color: #fff;
        }

        .btn-warning {
            background: #ffb300;
            color: #111;
        }

        .btn-success {
            background: #2e7d32;
            color: #fff;
        }

        .btn-info {
            background: #0288d1;
            color: #fff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }

        /* Tab styling */
        .tab-container {
            display: flex;
            gap: 5px;
            margin-bottom: 15px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 0;
            overflow-x: auto;
            white-space: nowrap;
        }

        .tab-button {
            border: none;
            background: none;
            padding: 10px 15px;
            border-bottom: 3px solid transparent;
            color: #666;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            font-size: 14px;
        }

        .tab-button.active {
            border-bottom-color: #1976d2;
            color: #1976d2;
        }

        .tab-button:hover {
            color: #1976d2;
        }

        /* Table sections */
        #all-orders-section,
        #new-orders-section,
        #todays-orders-section {
            width: 100%;
            overflow-x: auto;
            border-radius: 8px;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border: 1px solid #e0e0e0;
            display: block;
        }

        #new-orders-section,
        #todays-orders-section {
            display: none;
        }

        #new-orders-section[style*="display: block"],
        #todays-orders-section[style*="display: block"] {
            display: block !important;
        }

        #all-orders-section table,
        #new-orders-section table,
        #todays-orders-section table {
            width: 100%;
            min-width: 1100px;
            margin: 0;
            border-radius: 0;
            box-shadow: none;
        }

        /* Column sizing */
        table thead th:nth-child(1) {
            min-width: 60px;
        }

        /* ID */
        table thead th:nth-child(2) {
            min-width: 150px;
        }

        /* Pelanggan */
        table thead th:nth-child(3) {
            min-width: 100px;
        }

        /* Total */
        table thead th:nth-child(4) {
            min-width: 90px;
        }

        /* Metode */
        table thead th:nth-child(5) {
            min-width: 90px;
        }

        /* Status Bayar */
        table thead th:nth-child(6) {
            min-width: 50px;
        }

        /* Bukti */
        table thead th:nth-child(7) {
            min-width: 90px;
        }

        /* Status Pesanan */
        table thead th:nth-child(8) {
            min-width: 120px;
        }

        /* Alamat */
        table thead th:nth-child(9) {
            min-width: 100px;
        }

        /* Tracking */
        table thead th:nth-child(10) {
            min-width: 130px;
        }

        /* Tanggal */
        table thead th:nth-child(11) {
            min-width: 120px;
        }

        /* Aksi */

        /* Alert styling */
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 4px solid;
            font-size: 14px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left-color: #27ae60;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #f5c6cb;
        }

        .alert-info {
            background: #fff3cd;
            border-color: #ffc107;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 15px;
        }

        /* Content header */
        .content-header {
            margin-bottom: 1.5rem;
        }

        .content-header h1 {
            margin: 0 0 0.3rem 0;
            font-size: 24px;
        }

        .content-header p {
            margin: 0;
            color: #666;
            font-size: 13px;
        }

        /* Small text in tables */
        table small {
            display: block;
            color: #666;
            font-size: 12px;
            margin-top: 3px;
        }

        /* Status badge */
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
        }

        /* Empty state styling */
        .empty-state {
            background: white;
            padding: 40px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .empty-state i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }

        .empty-state h3 {
            color: #999;
            margin: 0;
            font-size: 18px;
        }

        .empty-state p {
            color: #bbb;
            margin: 10px 0 0 0;
            font-size: 14px;
        }

        /* Responsive adjustments */
        @media (max-width: 1400px) {
            table {
                font-size: 12px;
            }

            th,
            td {
                padding: 0.6rem;
            }

            .btn-sm {
                padding: 4px 6px;
                font-size: 11px;
            }
        }

        @media (max-width: 1200px) {
            table {
                font-size: 11px;
                min-width: 1000px;
            }

            th,
            td {
                padding: 0.5rem;
            }

            .action-buttons {
                gap: 2px;
            }
        }

        @media (max-width: 768px) {
            .modal-tracking .modal-content {
                width: 95%;
                margin: 20% auto;
            }

            .content-header h1 {
                font-size: 20px;
            }

            table {
                font-size: 10px;
                min-width: 900px;
            }

            th,
            td {
                padding: 0.4rem;
            }

            .btn-sm {
                padding: 3px 5px;
                font-size: 10px;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="content-header">
            <h1>Data Pesanan</h1>
            <p>Kelola semua pesanan pelanggan</p>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <!-- Alert untuk pesanan baru -->
        <?php if ($new_orders_count > 0): ?>
            <div class="alert alert-info" style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong style="color: #856404;">⚠️ Pesanan Baru!</strong>
                    <p style="margin: 5px 0 0 0; color: #856404;">Ada <strong><?= $new_orders_count ?></strong> pesanan baru hari ini yang menunggu untuk diproses.</p>
                </div>
                <button onclick="filterNewOrders()" class="btn btn-warning" style="white-space: nowrap; margin-left: 10px;">
                    Lihat <?= $new_orders_count > 1 ? 'Pesanan' : 'Pesanan' ?>
                </button>
            </div>
        <?php endif; ?>

        <!-- Tabs untuk filter -->
        <div style="display: flex; gap: 5px; margin-bottom: 15px; border-bottom: 2px solid #ddd; padding-bottom: 0; overflow-x: auto;">
            <button onclick="showAllOrders()" id="tab-all" class="btn" style="border: none; background: none; padding: 10px 12px; border-bottom: 3px solid #1976d2; color: #1976d2; font-weight: 600; cursor: pointer; font-size: 14px; white-space: nowrap;">
                Semua Pesanan
            </button>
            <?php if ($new_orders_count > 0): ?>
                <button onclick="showNewOrders()" id="tab-new" class="btn" style="border: none; background: none; padding: 10px 12px; border-bottom: 3px solid transparent; color: #666; font-weight: 600; cursor: pointer; font-size: 14px; white-space: nowrap;">
                    Pesanan Baru (<?= $new_orders_count ?>)
                </button>
            <?php endif; ?>
            <button onclick="showTodaysOrders()" id="tab-today" class="btn" style="border: none; background: none; padding: 10px 12px; border-bottom: 3px solid transparent; color: #666; font-weight: 600; cursor: pointer; font-size: 14px; white-space: nowrap;">
                Hari Ini (<?= $todays_orders_count ?>)
            </button>
        </div>

        <!-- Tabel Semua Pesanan -->
        <div id="all-orders-section">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Pelanggan</th>
                            <th>Total</th>
                            <th>Metode</th>
                            <th>Status Bayar</th>
                            <th>Bukti</th>
                            <th>Status Pesanan</th>
                            <th>Alamat</th>
                            <th>Waktu Penjemputan</th>
                            <th>Catatan</th>
                            <th>Tracking</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <?php $order['items'] = getOrderItems($order['id']); ?>
                            <tr>

                                <td>#<?= $order['id'] ?></td>

                                <td style="min-width:200px;max-width:260px;">
                                    <div class="fw-bold text-clamp-2"><?= htmlspecialchars($order['customer_name']) ?></div>
                                    <div class="text-muted small text-clamp-2"><?= htmlspecialchars($order['customer_email']) ?></div>
                                    <div class="mt-1">
                                        <?php if (!empty($order['customer_whatsapp'])): ?>
                                            <a target="_blank" href="https://wa.me/<?= '62' . ltrim(preg_replace('/[^0-9]/', '', $order['customer_whatsapp']), '0') ?>" class="btn btn-sm btn-outline-success me-1">
                                                <i class="fab fa-whatsapp"></i>
                                            </a>
                                            <button class="btn btn-sm btn-primary" onclick='openCourierModal(<?= json_encode($order, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>); return false;'>
                                                <i class="fas fa-user-friends"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small">WA tidak tersedia</span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td>Rp <?= number_format($order['total_price'], 0, ',', '.') ?></td>

                                <td>
                                    <?= [
                                        'cod' => 'COD',
                                        'transfer_bank' => 'Transfer Bank',
                                        'ewallet' => 'E-Wallet',
                                        'qris' => 'QRIS'
                                    ][$order['payment_method']] ?? '-' ?>
                                </td>

                                <td>
                                    <strong><?= strtoupper($order['payment_status'] ?? '-') ?></strong>
                                </td>

                                <td>
                                    <?php if (!empty($order['payment_proof']) && $order['payment_method'] !== 'cod'): ?>
                                        <a href="../<?= $order['payment_proof'] ?>" target="_blank">Lihat</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="status-badge status-<?= $order['status'] ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </td>

                                <td style="max-width:220px;">
                                    <div class="text-clamp-2">
                                        <?= htmlspecialchars($order['delivery_address']) ?>
                                    </div>
                                </td>

                                <td>
                                    <?= ($order['pickup_date'] && $order['pickup_time']) ? date('d M Y H:i', strtotime($order['pickup_date'] . ' ' . $order['pickup_time'])) : ($order['pickup_date'] ? date('d M Y', strtotime($order['pickup_date'])) : '-') ?>
                                </td>

                                <td style="max-width:200px;">
                                    <div class="text-clamp-2">
                                        <?= htmlspecialchars($order['notes']) ?: '<span class="text-muted">-</span>' ?>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge-track <?= $order['tracking_status'] ?>">
                                        <?= $tracking_map[$order['tracking_status']] ?? $order['tracking_status'] ?>
                                    </span>
                                </td>

                                <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>

                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-primary"
                                            onclick='openOrderModal(<?= json_encode($order) ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <button class="btn btn-sm btn-warning"
                                            onclick='openTrackingModal(<?= json_encode($order) ?>)'>
                                            <i class="fas fa-route"></i>
                                        </button>

                                        <?php if ($order['payment_method'] !== 'cod'): ?>
                                            <button class="btn btn-sm btn-success"
                                                onclick='openPaymentModal(<?= json_encode($order) ?>)'>
                                                <i class="fas fa-money-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabel Pesanan Baru -->
        <?php if ($new_orders_count > 0): ?>
            <div id="new-orders-section" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Pelanggan</th>
                                <th>Total</th>
                                <th>Metode</th>
                                <th>Status Bayar</th>
                                <th>Bukti</th>
                                <th>Status Pesanan</th>
                                <th>Alamat</th>
                                <th>Waktu Penjemputan</th>
                                <th>Catatan</th>
                                <th>Tracking</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($new_orders as $order): ?>
                                <tr style="background: #fffacd;">

                                    <td>#<?= $order['id'] ?></td>

                                    <td style="min-width:200px;max-width:260px;">
                                        <div class="fw-bold text-clamp-2"><?= htmlspecialchars($order['customer_name']) ?></div>
                                        <div class="text-muted small text-clamp-2"><?= htmlspecialchars($order['customer_email']) ?></div>
                                        <div class="mt-1">
                                            <?php if (!empty($order['customer_whatsapp'])): ?>
                                                <a target="_blank" href="https://wa.me/<?= '62' . ltrim(preg_replace('/[^0-9]/', '', $order['customer_whatsapp']), '0') ?>" class="btn btn-sm btn-outline-success me-1">
                                                    <i class="fab fa-whatsapp"></i>
                                                </a>
                                                <button class="btn btn-sm btn-primary" onclick='openCourierModal(<?= json_encode($order, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>); return false;'>
                                                    <i class="fas fa-user-friends"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted small">WA tidak tersedia</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td>Rp <?= number_format($order['total_price'], 0, ',', '.') ?></td>

                                    <td>
                                        <?= [
                                            'cod' => 'COD',
                                            'transfer_bank' => 'Transfer Bank',
                                            'ewallet' => 'E-Wallet',
                                            'qris' => 'QRIS'
                                        ][$order['payment_method']] ?? '-' ?>
                                    </td>

                                    <td>
                                        <strong><?= strtoupper($order['payment_status'] ?? '-') ?></strong>
                                    </td>

                                    <td>
                                        <?php if (!empty($order['payment_proof']) && $order['payment_method'] !== 'cod'): ?>
                                            <a href="../<?= $order['payment_proof'] ?>" target="_blank">Lihat</a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="status-badge status-<?= $order['status'] ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>

                                    <td style="max-width:220px;">
                                        <div class="text-clamp-2">
                                            <?= htmlspecialchars($order['delivery_address']) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?= ($order['pickup_date'] && $order['pickup_time']) ? date('d M Y H:i', strtotime($order['pickup_date'] . ' ' . $order['pickup_time'])) : ($order['pickup_date'] ? date('d M Y', strtotime($order['pickup_date'])) : '-') ?>
                                    </td>

                                    <td style="max-width:200px;">
                                        <div class="text-truncate" style="white-space:normal;">
                                            <?= htmlspecialchars($order['notes']) ?: '<span class="text-muted">-</span>' ?>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="badge-track <?= $order['tracking_status'] ?>">
                                            <?= $tracking_map[$order['tracking_status']] ?? $order['tracking_status'] ?>
                                        </span>
                                    </td>

                                    <td><?= date('d M Y H:i', strtotime($order['created_at'])) ?></td>

                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-primary"
                                                onclick='openOrderModal(<?= json_encode($order) ?>)'>
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <button class="btn btn-sm btn-warning"
                                                onclick='openTrackingModal(<?= json_encode($order) ?>)'>
                                                <i class="fas fa-route"></i>
                                            </button>

                                            <?php if ($order['payment_method'] !== 'cod'): ?>
                                                <button class="btn btn-sm btn-success"
                                                    onclick='openPaymentModal(<?= json_encode($order) ?>)'>
                                                    <i class="fas fa-money-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tabel Pesanan Hari Ini -->
        <div id="todays-orders-section" style="display: none;">
            <?php if ($todays_orders_count > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Pelanggan</th>
                                <th>Total</th>
                                <th>Metode</th>
                                <th>Status Bayar</th>
                                <th>Bukti</th>
                                <th>Status Pesanan</th>
                                <th>Alamat</th>
                                <th>Waktu Penjemputan</th>
                                <th>Catatan</th>
                                <th>Tracking</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($todays_orders as $order): ?>
                                <tr>

                                    <td>#<?= $order['id'] ?></td>

                                    <td style="min-width:200px;max-width:260px;">
                                        <div class="fw-bold text-clamp-2"><?= htmlspecialchars($order['customer_name']) ?></div>
                                        <div class="text-muted small text-clamp-2"><?= htmlspecialchars($order['customer_email']) ?></div>
                                        <div class="mt-1">
                                            <?php if (!empty($order['customer_whatsapp'])): ?>
                                                <a target="_blank" href="https://wa.me/<?= '62' . ltrim(preg_replace('/[^0-9]/', '', $order['customer_whatsapp']), '0') ?>" class="btn btn-sm btn-outline-success me-1">
                                                    <i class="fab fa-whatsapp"></i>
                                                </a>
                                                <button class="btn btn-sm btn-primary" onclick='openCourierModal(<?= json_encode($order, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>); return false;'>
                                                    <i class="fas fa-user-friends"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted small">WA tidak tersedia</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td>Rp <?= number_format($order['total_price'], 0, ',', '.') ?></td>

                                    <td>
                                        <?= [
                                            'cod' => 'COD',
                                            'transfer_bank' => 'Transfer Bank',
                                            'ewallet' => 'E-Wallet',
                                            'qris' => 'QRIS'
                                        ][$order['payment_method']] ?? '-' ?>
                                    </td>

                                    <td>
                                        <strong><?= strtoupper($order['payment_status'] ?? '-') ?></strong>
                                    </td>

                                    <td>
                                        <?php if (!empty($order['payment_proof']) && $order['payment_method'] !== 'cod'): ?>
                                            <a href="../<?= $order['payment_proof'] ?>" target="_blank">Lihat</a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="status-badge status-<?= $order['status'] ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>

                                    <td style="max-width:220px;">
                                        <div class="text-truncate" style="white-space:normal;">
                                            <?= htmlspecialchars($order['delivery_address']) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?= ($order['pickup_date'] && $order['pickup_time']) ? date('d M Y H:i', strtotime($order['pickup_date'] . ' ' . $order['pickup_time'])) : ($order['pickup_date'] ? date('d M Y', strtotime($order['pickup_date'])) : '-') ?>
                                    </td>

                                    <td style="max-width:200px;">
                                        <div class="text-clamp-2">
                                            <?= htmlspecialchars($order['notes']) ?: '<span class="text-muted">-</span>' ?>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="badge-track <?= $order['tracking_status'] ?>">
                                            <?= $tracking_map[$order['tracking_status']] ?? $order['tracking_status'] ?>
                                        </span>
                                    </td>

                                    <td><?= date('d M Y H:i', strtotime($order['created_at'])) ?></td>

                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-primary"
                                                onclick='openOrderModal(<?= json_encode($order) ?>)'>
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <button class="btn btn-sm btn-warning"
                                                onclick='openTrackingModal(<?= json_encode($order) ?>)'>
                                                <i class="fas fa-route"></i>
                                            </button>

                                            <?php if ($order['payment_method'] !== 'cod'): ?>
                                                <button class="btn btn-sm btn-success"
                                                    onclick='openPaymentModal(<?= json_encode($order) ?>)'>
                                                    <i class="fas fa-money-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Belum ada pesanan untuk hari ini</h3>
                    <p>Kembali periksa nanti</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ======================= MODAL PILIH KURIR (MODERN) ======================= -->
    <div id="modalCourierSelect" class="modal-tracking">
        <div class="modal-content">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <h3 style="margin:0;font-size:18px">Pilih Kurir untuk Pesanan <span id="courier_modal_order_id"></span></h3>
                <button class="btn btn-secondary" onclick="closeCourierModal()">Tutup</button>
            </div>

            <input id="courier_search" class="courier-search" placeholder="Cari nama, email atau no. telepon" oninput="filterCouriers()">

            <div class="courier-list" id="courier_list">
                <?php foreach ($couriers as $c): ?>
                    <div class="courier-card" data-phone="<?= htmlspecialchars($c['phone']) ?>">
                        <div class="courier-info">
                            <div class="courier-name"><?= htmlspecialchars($c['name']) ?></div>
                            <div class="courier-meta"><?= htmlspecialchars($c['email'] ?? '') ?> &middot; <?= htmlspecialchars($c['phone']) ?></div>
                        </div>
                        <div class="courier-actions">
                            <button class="btn btn-sm btn-primary" onclick="showCourierConfirm('<?= htmlspecialchars(addslashes($c['phone'])) ?>','<?= htmlspecialchars(addslashes($c['name'])) ?>')">Kirim</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="courier_confirm_area" class="courier-confirm-area"></div>
        </div>
    </div>

    <!-- ======================= MODAL UPDATE PEMBAYARAN ======================= -->
    <div id="modalPayment" class="modal-tracking">
        <div class="modal-content">
            <h3>Verifikasi Pembayaran</h3>
            <hr>

            <form method="POST">
                <input type="hidden" id="pay_order_id" name="order_id">

                <label>Status Pembayaran</label>
                <select name="payment_status" id="payment_status" class="form-control" required>
                    <option value="waiting">Menunggu Verifikasi</option>
                    <option value="invalid">Tidak Valid</option>
                    <option value="confirmed">Valid / Diterima</option>
                </select>

                <br>
                <button type="submit" name="update_payment" class="btn btn-primary">Simpan</button>
                <button type="button" class="btn btn-secondary" onclick="closePaymentModal()">Batal</button>
            </form>
        </div>
    </div>

    <!-- ======================= MODAL TRACKING ======================= -->
    <div id="modalTracking" class="modal-tracking">
        <div class="modal-content">
            <h3>Update Tracking Pesanan</h3>
            <hr>

            <form method="POST">
                <input type="hidden" name="order_id" id="track_order_id">

                <label>Status Tracking</label>
                <select class="form-control" name="tracking_status" id="tracking_status" required>
                    <option value="pending">Pesanan Dibuat</option>
                    <option value="picked_up">Kurir Menjemput</option>
                    <option value="washing">Sedang Dicuci</option>
                    <option value="drying">Pengeringan</option>
                    <option value="ironing">Penyetrikaan</option>
                    <option value="delivering">Dalam Pengantaran</option>
                    <option value="completed">Selesai</option>
                </select>

                <br>
                <label>Catatan (Opsional)</label>
                <textarea name="tracking_note" id="tracking_note" class="form-control" rows="2"></textarea>

                <br>
                <button type="submit" name="update_tracking" class="btn btn-primary">Simpan</button>
            </form>
        </div>
    </div>

    <!-- ======================= MODAL STATUS PESANAN ======================= -->
    <div id="orderModal" class="modal-tracking">
        <div class="modal-content">
            <h3>Edit Status Pesanan</h3>
            <hr>

            <form method="POST">
                <input type="hidden" name="order_id" id="modal_order_id">

                <label>Status Pesanan</label>
                <select class="form-control" id="status" name="status" required>
                    <option value="pending">Menunggu Konfirmasi</option>
                    <option value="processing">Sedang Diproses</option>
                    <option value="ready">Siap Diambil</option>
                    <option value="completed">Selesai</option>
                    <option value="cancelled">Dibatalkan</option>
                </select>

                <br>
                <button type="submit" name="update_status" class="btn btn-primary">Simpan</button>
            </form>
        </div>
    </div>

    <script>
        function openOrderModal(order) {
            document.getElementById("modal_order_id").value = order.id;
            document.getElementById("status").value = order.status;
            document.getElementById("orderModal").style.display = "block";
        }

        function closeOrderModal() {
            document.getElementById("orderModal").style.display = "none";
        }

        function openTrackingModal(order) {
            document.getElementById("track_order_id").value = order.id;
            document.getElementById("tracking_status").value = order.tracking_status;
            document.getElementById("tracking_note").value = "";
            document.getElementById("modalTracking").style.display = "block";
        }

        function closeTrackingModal() {
            document.getElementById("modalTracking").style.display = "none";
        }

        function openPaymentModal(order) {
            document.getElementById("pay_order_id").value = order.id;
            document.getElementById("payment_status").value = order.payment_status;
            document.getElementById("modalPayment").style.display = "block";
        }

        function closePaymentModal() {
            document.getElementById("modalPayment").style.display = "none";
        }

        window.onclick = function(e) {
            if (e.target === document.getElementById("modalTracking")) closeTrackingModal();
            if (e.target === document.getElementById("orderModal")) closeOrderModal();
            if (e.target === document.getElementById("modalPayment")) closePaymentModal();
            if (e.target === document.getElementById("modalCourierSelect")) closeCourierModal();
        }

        /**
         * Kirim pesanan ke kurir via AJAX call
         * - Melakukan validasi di backend (status harus pending/confirmation)
         * - Menyimpan assignment di database
         * - Menghindari data bentrok antara kurir
         */
        // Current order selected for courier modal
        let currentOrderForCourier = null;

        function openCourierModal(order) {
            currentOrderForCourier = order;
            document.getElementById('courier_modal_order_id').textContent = '#' + order.id;
            document.getElementById('courier_search').value = '';
            filterCouriers();
            document.getElementById('courier_confirm_area').style.display = 'none';
            document.getElementById('modalCourierSelect').style.display = 'block';
            setTimeout(() => {
                try {
                    document.getElementById('courier_search').focus();
                } catch (e) {}
            }, 100);
        }

        function closeCourierModal() {
            currentOrderForCourier = null;
            document.getElementById('modalCourierSelect').style.display = 'none';
        }

        function filterCouriers() {
            const q = document.getElementById('courier_search').value.toLowerCase().trim();
            document.querySelectorAll('.courier-card').forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(q) ? 'flex' : 'none';
            });
        }

        function showCourierConfirm(phone, name) {
            if (!currentOrderForCourier) return alert('Tidak ada pesanan terpilih');
            const area = document.getElementById('courier_confirm_area');
            area.innerHTML = "<div>Kirim pesanan <strong>#" + currentOrderForCourier.id + "</strong> ke <strong>" + name + "</strong>?<div style='margin-top:10px'><button class='btn btn-primary' onclick=\"assignCourier('" + phone + "')\">Ya, Kirim</button> <button class='btn btn-secondary' onclick='clearCourierConfirm()'>Batal</button></div></div>";
            area.style.display = 'block';
        }

        function clearCourierConfirm() {
            const area = document.getElementById('courier_confirm_area');
            area.style.display = 'none';
            area.innerHTML = '';
        }

        function assignCourier(phone) {
            if (!currentOrderForCourier) return alert('Tidak ada pesanan terpilih');
            // disable confirm area while sending
            const area = document.getElementById('courier_confirm_area');
            area.innerHTML = '<div style="opacity:0.9">Mengirim ke kurir...</div>';
            // call existing sendToCourier but skip built-in confirm
            sendToCourier(currentOrderForCourier, phone, true);
        }

        /**
         * Kirim pesanan ke kurir via AJAX call
         * - Melakukan validasi di backend (status harus pending/confirmation)
         * - Menyimpan assignment di database
         * - Menghindari data bentrok antara kurir
         *
         * Parameter skipConfirm (boolean): jika true, jangan tampilkan confirm()
         */
        function sendToCourier(order, courierPhone, skipConfirm) {
            if (!courierPhone) {
                alert("Silakan pilih kurir.");
                return;
            }

            // Cek apakah pesanan masih dalam status "pending" (konfirmasi)
            if (order.tracking_status !== 'pending') {
                alert("⚠️ Pesanan hanya bisa dikirim ke kurir jika status masih 'Konfirmasi' (Pending).\n\nStatus saat ini: " + order.tracking_status);
                return;
            }

            if (!skipConfirm) {
                if (!confirm("Kirim pesanan #" + order.id + " ke kurir ini?\n\nPesanan akan ditambahkan ke daftar kurir dan tracking status akan diubah menjadi 'Kurir Menjemput'.")) {
                    return;
                }
            }

            // Kirim AJAX request ke API
            const formData = new FormData();
            formData.append('order_id', order.id);
            formData.append('courier_phone', courierPhone);

            fetch('../Api/assign_order_to_courier.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("✅ " + data.message);
                        // close modal and reload halaman untuk refresh data
                        try {
                            closeCourierModal();
                        } catch (e) {}
                        location.reload();
                    } else {
                        alert("❌ Error: " + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("❌ Terjadi kesalahan saat mengirim pesanan ke kurir.");
                });
        }

        // Tab switching functions
        function showAllOrders() {
            document.getElementById("all-orders-section").style.display = "block";
            const newSection = document.getElementById("new-orders-section");
            if (newSection) newSection.style.display = "none";
            const todaySection = document.getElementById("todays-orders-section");
            if (todaySection) todaySection.style.display = "none";

            // Update tab styling
            document.getElementById("tab-all").style.borderBottomColor = "#1976d2";
            document.getElementById("tab-all").style.color = "#1976d2";
            const tabNew = document.getElementById("tab-new");
            if (tabNew) {
                tabNew.style.borderBottomColor = "transparent";
                tabNew.style.color = "#666";
            }
            const tabToday = document.getElementById("tab-today");
            if (tabToday) {
                tabToday.style.borderBottomColor = "transparent";
                tabToday.style.color = "#666";
            }
        }

        function showNewOrders() {
            const newSection = document.getElementById("new-orders-section");
            if (newSection) {
                newSection.style.display = "block";
                document.getElementById("all-orders-section").style.display = "none";
                const todaySection = document.getElementById("todays-orders-section");
                if (todaySection) todaySection.style.display = "none";

                // Update tab styling
                document.getElementById("tab-all").style.borderBottomColor = "transparent";
                document.getElementById("tab-all").style.color = "#666";
                document.getElementById("tab-new").style.borderBottomColor = "#1976d2";
                document.getElementById("tab-new").style.color = "#1976d2";
                const tabToday = document.getElementById("tab-today");
                if (tabToday) {
                    tabToday.style.borderBottomColor = "transparent";
                    tabToday.style.color = "#666";
                }
            }
        }

        function showTodaysOrders() {
            const todaySection = document.getElementById("todays-orders-section");
            if (todaySection) {
                todaySection.style.display = "block";
                document.getElementById("all-orders-section").style.display = "none";
                const newSection = document.getElementById("new-orders-section");
                if (newSection) newSection.style.display = "none";

                // Update tab styling
                document.getElementById("tab-all").style.borderBottomColor = "transparent";
                document.getElementById("tab-all").style.color = "#666";
                const tabNew = document.getElementById("tab-new");
                if (tabNew) {
                    tabNew.style.borderBottomColor = "transparent";
                    tabNew.style.color = "#666";
                }
                document.getElementById("tab-today").style.borderBottomColor = "#1976d2";
                document.getElementById("tab-today").style.color = "#1976d2";
            }
        }

        function filterNewOrders() {
            showNewOrders();
        }
    </script>

</body>

</html>