<?php
include '../includes/auth.php';
include '../includes/functions.php';

requireLogin();
if (!isKurir()) {
    header('Location: ../index.php');
    exit();
}

global $pdo;



/* ============================================================
   UPDATE TRACKING PESANAN
   ============================================================ */
if (isset($_POST['update_tracking'])) {
    $order_id = (int) $_POST['order_id'];
    $tracking = $_POST['tracking_status'];
    $note     = $_POST['tracking_note'] ?? '';

    $allowed = [
        'picked_up',
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

/* Pegawai tidak bisa update pembayaran */

/* ============================================================
   FETCH PESANAN BARU HARI INI (HANYA DARI ASSIGNMENT OLEH ADMIN)
   ============================================================ */
// Ambil courier_id dari user session
$courierStmt = $pdo->prepare("
    SELECT id FROM couriers 
    WHERE phone = (SELECT whatsapp FROM users WHERE id = ? AND role = 'kurir')
");
$courierStmt->execute([$_SESSION['user_id']]);
$courierData = $courierStmt->fetch(PDO::FETCH_ASSOC);
$courier_id = $courierData['id'] ?? null;

if ($courier_id) {
    $newOrdersStmt = $pdo->prepare("
        SELECT 
            o.id,
            o.customer_id,
            o.total_price,
            o.status,
            o.tracking_status,
            o.delivery_address,
            o.notes,
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
        INNER JOIN order_assignments oa ON o.id = oa.order_id
        JOIN users u ON o.customer_id = u.id
        LEFT JOIN payments p ON p.order_id = o.id
        LEFT JOIN order_items oi ON oi.order_id = o.id
        LEFT JOIN services si ON si.id = oi.service_id

        WHERE oa.courier_id = ? 
        AND DATE(o.created_at) = CURDATE() 
        AND o.tracking_status = 'picked_up'
        
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");

    $newOrdersStmt->execute([$courier_id]);
    $new_orders = $newOrdersStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $new_orders = [];
}

// Ambil data kurir (nama, phone) untuk ditampilkan di header/dashboard
$courier_info = null;
if ($courier_id) {
    $cStmt = $pdo->prepare("SELECT id, name, phone, is_active FROM couriers WHERE id = ? LIMIT 1");
    $cStmt->execute([$courier_id]);
    $courier_info = $cStmt->fetch(PDO::FETCH_ASSOC);
}

$new_orders_count = count($new_orders);

/* ============================================================
    FETCH PESANAN HARI INI (HANYA DARI ASSIGNMENT OLEH ADMIN)
    ============================================================ */
if ($courier_id) {
    $todaysOrdersStmt = $pdo->prepare("
        SELECT 
            o.id,
            o.customer_id,
            o.total_price,
            o.status,
            o.tracking_status,
            o.delivery_address,
            o.notes,
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
        INNER JOIN order_assignments oa ON o.id = oa.order_id
        JOIN users u ON o.customer_id = u.id
        LEFT JOIN payments p ON p.order_id = o.id
        LEFT JOIN order_items oi ON oi.order_id = o.id
        LEFT JOIN services si ON si.id = oi.service_id

        WHERE oa.courier_id = ? AND DATE(o.created_at) = CURDATE()
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");

    $todaysOrdersStmt->execute([$courier_id]);
    $todays_orders = $todaysOrdersStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $todays_orders = [];
}

$todays_orders_count = count($todays_orders);

/* ============================================================
   FETCH DATA PESANAN (HANYA DARI ASSIGNMENT OLEH ADMIN)
   ============================================================ */
if ($courier_id) {
    $stmt = $pdo->prepare("
        SELECT 
            o.id,
            o.customer_id,
            o.total_price,
            o.status,
            o.tracking_status,
            o.delivery_address,
            o.notes,
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
        INNER JOIN order_assignments oa ON o.id = oa.order_id
        JOIN users u ON o.customer_id = u.id
        LEFT JOIN payments p ON p.order_id = o.id
        LEFT JOIN order_items oi ON oi.order_id = o.id
        LEFT JOIN services si ON si.id = oi.service_id

        WHERE oa.courier_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");

    $stmt->execute([$courier_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $orders = [];
}

/* ============================================================
   LABEL TRACKING
   ============================================================ */
$tracking_map = [
    'picked_up' => 'Kurir Menjemput',
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
    <title>Data Pesanan - Pegawai</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        /* General layout adjustments similar to admin/orders.php */
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
            padding-top: 120px;
            font-family: 'Poppins', sans-serif;
        }

        .badge-track {
            padding: 4px 8px;
            font-size: 11px;
            border-radius: 6px;
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
            margin: 8% auto;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        }

        /* Action buttons styling */
        .action-buttons {
            display: flex;
            gap: 6px;
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
            padding: 10px 12px;
            border-bottom: 3px solid transparent;
            color: #666;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            white-space: nowrap;
        }

        .tab-button.active {
            border-bottom-color: #1976d2;
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
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
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
        }

        /* Column sizing hints */
        table thead th:nth-child(1) {
            min-width: 60px;
        }

        table thead th:nth-child(2) {
            min-width: 150px;
        }

        table thead th:nth-child(3) {
            min-width: 100px;
        }

        table thead th:nth-child(4) {
            min-width: 90px;
        }

        table thead th:nth-child(5) {
            min-width: 90px;
        }

        table thead th:nth-child(6) {
            min-width: 50px;
        }

        table thead th:nth-child(7) {
            min-width: 90px;
        }

        table thead th:nth-child(8) {
            min-width: 120px;
        }

        table thead th:nth-child(9) {
            min-width: 100px;
        }

        table thead th:nth-child(10) {
            min-width: 130px;
        }

        table thead th:nth-child(11) {
            min-width: 120px;
        }

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

        table small {
            display: block;
            color: #666;
            font-size: 12px;
            margin-top: 3px;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }

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

        /* Make main content full-width container (no sidebar) and center it */
        .main-content {
            margin-left: 0;
            margin-right: 0;
            padding: 30px 12px;
            max-width: 1200px;
            margin: 120px auto 40px auto;
            /* top margin to clear fixed header */
        }

        /* Allow tables to be narrow on small screens and enable horizontal scroll wrapper */
        @media (max-width: 991px) {
            table {
                min-width: 0 !important;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <div class="main-content">
        <div class="content-header">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                <div>
                    <h1>Data Pesanan</h1>
                    <p>Kelola semua pesanan pelanggan</p>
                </div>

                <div style="text-align:right; min-width:180px;">
                    <?php if (!empty($courier_info)): ?>
                        <div style="font-weight:700"><?= htmlspecialchars($courier_info['name']) ?> <small style="font-weight:600;opacity:0.85">(Kurir)</small></div>
                        <div style="font-size:13px;color:#666; margin-top:4px;">No. Telp: <?= htmlspecialchars($courier_info['phone']) ?></div>
                    <?php else: ?>
                        <div style="font-size:13px;color:#666;">Kurir belum terhubung</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <!-- Alert untuk pesanan baru siap diambil -->
        <?php if ($new_orders_count > 0): ?>
            <div class="alert alert-info" style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong style="color: #856404;">⚠ Pesanan Siap Diambil!</strong>
                    <p style="margin: 5px 0 0 0; color: #856404;">Ada <strong><?= $new_orders_count ?></strong> pesanan baru hari ini yang siap untuk diambil dan dikirim.</p>
                </div>
                <button onclick="filterNewOrders()" class="btn btn-warning" style="white-space: nowrap; margin-left: 10px;">
                    Lihat <?= $new_orders_count > 1 ? 'Pesanan' : 'Pesanan' ?>
                </button>
            </div>
        <?php endif; ?>

        <!-- Tabs untuk filter -->
        <div class="tab-container">
            <button onclick="showAllOrders()" id="tab-all" class="tab-button active">Semua Pesanan</button>
            <?php if ($new_orders_count > 0): ?>
                <button onclick="showNewOrders()" id="tab-new" class="tab-button">Siap Diambil (<?= $new_orders_count ?>)</button>
            <?php endif; ?>
            <button onclick="showTodaysOrders()" id="tab-today" class="tab-button">Hari Ini (<?= $todays_orders_count ?? 0 ?>)</button>
        </div>

        <!-- Tabel Semua Pesanan -->
        <div id="all-orders-section">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pelanggan</th>
                        <th class="col-items">Items</th>
                        <th class="col-address">Alamat</th>
                        <th>Tracking</th>
                        <th class="col-date">Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?= $order['id'] ?></td>
                            <td>
                                <div><?= htmlspecialchars($order['customer_name']) ?></div>
                                <small><?= htmlspecialchars($order['customer_email']) ?></small>

                                <!-- tampilkan whatsapp pelanggan jika tersedia -->
                                <?php if (!empty($order['customer_whatsapp'])): ?>
                                    <div style="margin-top:6px;">
                                        <small>
                                            WA: <?= htmlspecialchars($order['customer_whatsapp']) ?>
                                            &nbsp;
                                            <?php
                                            $rawPhone = preg_replace('/[^0-9]/', '', $order['customer_whatsapp']);
                                            if (substr($rawPhone, 0, 2) === '62') {
                                                $waPhone = $rawPhone;
                                            } elseif (substr($rawPhone, 0, 1) === '0') {
                                                $waPhone = '62' . substr($rawPhone, 1);
                                            } else {
                                                $waPhone = '62' . $rawPhone;
                                            }
                                            $waLink = 'https://wa.me/' . $waPhone;
                                            ?>
                                            <a href="<?= $waLink ?>"
                                                data-order='<?= htmlspecialchars(json_encode($order, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'
                                                onclick="sendToCustomerFromEvent(event, this); return false;"
                                                class="btn btn-sm btn-info"
                                                style="padding:4px 8px;border-radius:4px;font-size:12px;color:#fff;text-decoration:none;">
                                                <i class="fab fa-whatsapp"></i> Chat
                                            </a>
                                        </small>
                                    </div>
                                <?php else: ?>
                                    <div style="margin-top:6px;"><small><em>WA tidak tersedia</em></small></div>
                                <?php endif; ?>
                            </td>
                            <td class="col-items"><?= htmlspecialchars($order['item_list'] ?? '-') ?></td>
                            <td class="col-address"><?= nl2br(htmlspecialchars($order['delivery_address'])) ?></td>
                            <td>
                                <span class="badge-track <?= $order['tracking_status'] ?>">
                                    <?= $tracking_map[$order['tracking_status']] ?? $order['tracking_status'] ?>
                                </span>
                            </td>
                            <td class="col-date"><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                            <td>
                                <button class="btn btn-sm btn-info btn-view" onclick='openOrderDetails(<?= json_encode($order) ?>)'>
                                    <i class="fas fa-eye"></i> Lihat
                                </button>
                                <button class="btn btn-sm btn-warning btn-update" onclick='openTrackingModal(<?= json_encode($order) ?>)'>
                                    <i class="fas fa-route"></i> Update
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Tabel Pesanan Siap Diambil -->
        <?php if ($new_orders_count > 0): ?>
            <div id="new-orders-section" style="display: none;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pelanggan</th>
                            <th class="col-items">Items</th>
                            <th class="col-address">Alamat</th>
                            <th>Tracking</th>
                            <th class="col-date">Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($new_orders as $order): ?>
                            <tr style="background: #fffacd;">
                                <td>#<?= $order['id'] ?></td>
                                <td>
                                    <div><?= htmlspecialchars($order['customer_name']) ?></div>
                                    <small><?= htmlspecialchars($order['customer_email']) ?></small>

                                    <!-- tampilkan whatsapp pelanggan jika tersedia -->
                                    <?php if (!empty($order['customer_whatsapp'])): ?>
                                        <div style="margin-top:6px;">
                                            <small>
                                                WA: <?= htmlspecialchars($order['customer_whatsapp']) ?>
                                                &nbsp;
                                                <?php
                                                $rawPhone = preg_replace('/[^0-9]/', '', $order['customer_whatsapp']);
                                                if (substr($rawPhone, 0, 2) === '62') {
                                                    $waPhone = $rawPhone;
                                                } elseif (substr($rawPhone, 0, 1) === '0') {
                                                    $waPhone = '62' . substr($rawPhone, 1);
                                                } else {
                                                    $waPhone = '62' . $rawPhone;
                                                }
                                                $waLink = 'https://wa.me/' . $waPhone;
                                                ?>
                                                <a href="<?= $waLink ?>"
                                                    data-order='<?= htmlspecialchars(json_encode($order, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'
                                                    onclick="sendToCustomerFromEvent(event, this); return false;"
                                                    class="btn btn-sm btn-info"
                                                    style="padding:4px 8px;border-radius:4px;font-size:12px;color:#fff;text-decoration:none;">
                                                    <i class="fab fa-whatsapp"></i> Chat
                                                </a>
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <div style="margin-top:6px;"><small><em>WA tidak tersedia</em></small></div>
                                    <?php endif; ?>
                                </td>
                                <td class="col-items"><?= htmlspecialchars($order['item_list'] ?? '-') ?></td>
                                <td class="col-address"><?= nl2br(htmlspecialchars($order['delivery_address'])) ?></td>
                                <td>
                                    <span class="badge-track <?= $order['tracking_status'] ?>">
                                        <?= $tracking_map[$order['tracking_status']] ?? 'Siap Diambil' ?>
                                    </span>
                                </td>
                                <td class="col-date"><?= date('d M Y H:i', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info btn-view" onclick='openOrderDetails(<?= json_encode($order) ?>)'>
                                        <i class="fas fa-eye"></i> Lihat
                                    </button>
                                    <button class="btn btn-sm btn-warning btn-update" onclick='openTrackingModal(<?= json_encode($order) ?>)'>
                                        <i class="fas fa-route"></i> Update
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Tabel Pesanan Hari Ini -->
        <div id="todays-orders-section" style="display: none;">
            <?php if ($todays_orders_count > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pelanggan</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Metode</th>
                            <th>Status Bayar</th>
                            <th>Bukti</th>
                            <th>Status Pesanan</th>
                            <th>Alamat</th>
                            <th>Tracking</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($todays_orders as $order): ?>
                            <tr>
                                <td>#<?= $order['id'] ?></td>
                                <td>
                                    <div><?= htmlspecialchars($order['customer_name']) ?></div>
                                    <small><?= htmlspecialchars($order['customer_email']) ?></small>

                                    <!-- tampilkan whatsapp pelanggan jika tersedia -->
                                    <?php if (!empty($order['customer_whatsapp'])): ?>
                                        <div style="margin-top:6px;">
                                            <small>
                                                WA: <?= htmlspecialchars($order['customer_whatsapp']) ?>
                                                &nbsp;
                                                <?php
                                                $rawPhone = preg_replace('/[^0-9]/', '', $order['customer_whatsapp']);
                                                if (substr($rawPhone, 0, 2) === '62') {
                                                    $waPhone = $rawPhone;
                                                } elseif (substr($rawPhone, 0, 1) === '0') {
                                                    $waPhone = '62' . substr($rawPhone, 1);
                                                } else {
                                                    $waPhone = '62' . $rawPhone;
                                                }
                                                $waLink = 'https://wa.me/' . $waPhone;
                                                ?>
                                                <a href="<?= $waLink ?>"
                                                    data-order='<?= htmlspecialchars(json_encode($order, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'
                                                    onclick="sendToCustomerFromEvent(event, this); return false;"
                                                    class="btn btn-sm btn-info"
                                                    style="padding:4px 8px;border-radius:4px;font-size:12px;color:#fff;text-decoration:none;">
                                                    <i class="fab fa-whatsapp"></i> Chat
                                                </a>
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <div style="margin-top:6px;"><small><em>WA tidak tersedia</em></small></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($order['item_list'] ?? '-') ?></td>
                                <td>Rp <?= number_format($order['total_price'], 0, ',', '.') ?></td>
                                <td>
                                    <?= ['cod' => 'COD', 'transfer_bank' => 'Transfer Bank', 'ewallet' => 'E-Wallet', 'qris' => 'QRIS'][$order['payment_method']] ?? '-' ?>
                                </td>
                                <td><strong><?= strtoupper($order['payment_status'] ?? '-') ?></strong></td>
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
                                <td><?= nl2br(htmlspecialchars($order['delivery_address'])) ?></td>
                                <td>
                                    <span class="badge-track <?= $order['tracking_status'] ?>">
                                        <?= $tracking_map[$order['tracking_status']] ?? $order['tracking_status'] ?>
                                    </span>
                                </td>
                                <td><?= date('d M Y H:i', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick='openTrackingModal(<?= json_encode($order) ?>)'>
                                        <i class="fas fa-route"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Belum ada pesanan untuk hari ini</h3>
                    <p>Kembali periksa nanti</p>
                </div>
            <?php endif; ?>
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
                    <option value="picked_up">Kurir Menjemput</option>
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



    <script>
        /**
         * Kirim pesan template ke pelanggan via WhatsApp dengan detail pesanan
         * Includes: nama pelanggan, item pesanan, total harga, dan request share lokasi
         */
        const js_tracking_map = {
            'picked_up': 'Kurir Menjemput',
            'delivering': 'Dalam Pengantaran',
            'completed': 'Selesai'
        };

        function sendToCustomer(order) {
            let whatsappNum = order.customer_whatsapp;
            if (!whatsappNum) {
                alert("Nomor WhatsApp pelanggan tidak tersedia");
                return;
            }

            // Konversi ke format internasional +62 dengan validasi
            let cleaned = whatsappNum.replace(/\D/g, '');
            if (!cleaned) {
                alert("Nomor WhatsApp pelanggan tidak tersedia");
                return;
            }
            if (cleaned.startsWith('0')) cleaned = cleaned.substring(1);
            // Jika sudah termasuk 62, gunakan apa adanya
            let intlNum = cleaned;
            if (!intlNum.startsWith('62')) {
                intlNum = '62' + intlNum;
            }

            // Ambil data pesanan
            let custName = order.customer_name || "Pelanggan";
            let items = order.item_list ? order.item_list.split('|').map(i => '• ' + i.trim()).join('\n') : '-';
            let totalPrice = order.total_price ? 'Rp ' + new Intl.NumberFormat('id-ID').format(order.total_price) : '-';
            let trackingStatus = js_tracking_map[order.tracking_status] || order.tracking_status || 'Menunggu';

            // Format pesan dengan template yang informatif
            let msg =
                "**Halo**" + custName + "!\nSaya Kurir Dari Adin Laundry\n\n" +
                "*Kami akan segera menjemput pesanan laundry Anda. Berikut detail pesanan:*\n\n" +
                "**Detail Pesanan:**\n" +
                items + "\n\n" +
                "**Total Harga:** " + totalPrice + "\n" +
                "*Status: " + trackingStatus + "\n\n" +
                "----------------------------\n" +
                "**Mohon bagikan lokasi atau alamat rumah Anda untuk memudahkan kami menjemput pesanan.\n\n" +
                "Terima kasih!**";

            // Buka WhatsApp dengan pesan
            let url = "https://wa.me/" + intlNum + "?text=" + encodeURIComponent(msg);
            console.log("Opening WA URL:", url);
            window.open(url, "_blank");
        }

        function sendToCustomerFromEvent(e, el) {
            // Read order JSON from data-order attribute
            if (!el) return;
            let orderJson = el.getAttribute('data-order');
            if (!orderJson) {
                // fallback: open href directly
                let href = el.getAttribute('href');
                if (href) window.open(href, '_blank');
                return false;
            }
            let parsed;
            try {
                parsed = JSON.parse(orderJson);
            } catch (ex) {
                console.error('Failed to parse data-order', ex);
                let href = el.getAttribute('href');
                if (href) window.open(href, '_blank');
                return false;
            }
            sendToCustomer(parsed);
            return false;
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

        // Tab switching functions (supports All / New / Today)
        function showAllOrders() {
            document.getElementById("all-orders-section").style.display = "block";
            const newSection = document.getElementById("new-orders-section");
            if (newSection) newSection.style.display = "none";
            const todaySection = document.getElementById("todays-orders-section");
            if (todaySection) todaySection.style.display = "none";

            // Update tab styling
            document.getElementById("tab-all").classList.add('active');
            const tabNew = document.getElementById("tab-new");
            if (tabNew) tabNew.classList.remove('active');
            const tabToday = document.getElementById("tab-today");
            if (tabToday) tabToday.classList.remove('active');
        }

        function showNewOrders() {
            const newSection = document.getElementById("new-orders-section");
            if (newSection) {
                newSection.style.display = "block";
                document.getElementById("all-orders-section").style.display = "none";
                const todaySection = document.getElementById("todays-orders-section");
                if (todaySection) todaySection.style.display = "none";

                // Update tab styling
                document.getElementById("tab-all").classList.remove('active');
                document.getElementById("tab-new").classList.add('active');
                const tabToday = document.getElementById("tab-today");
                if (tabToday) tabToday.classList.remove('active');
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
                document.getElementById("tab-all").classList.remove('active');
                const tabNew = document.getElementById("tab-new");
                if (tabNew) tabNew.classList.remove('active');
                document.getElementById("tab-today").classList.add('active');
            }
        }

        function filterNewOrders() {
            showNewOrders();
        }

        window.onclick = function(e) {
            if (e.target === document.getElementById("modalTracking")) closeTrackingModal();
        }
    </script>

</body>

</html>