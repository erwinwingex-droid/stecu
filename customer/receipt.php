<?php
include '../includes/auth.php';
requireLogin();
include '../includes/functions.php';

if (!isset($_GET['order_id'])) {
    die("Order ID tidak ditemukan.");
}

$order_id = (int) $_GET['order_id'];
$customer_id = $_SESSION['user_id'];

// Ambil data orders
global $pdo;
$stmt = $pdo->prepare("
    SELECT 
        o.*,
        u.username AS customer_name,
        u.email AS customer_email,
        p.method AS payment_method,
        p.status AS payment_status,
        p.amount AS payment_amount,
        p.bukti  AS payment_proof
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    LEFT JOIN payments p ON o.id = p.order_id
    WHERE o.id = ? AND o.customer_id = ?
    LIMIT 1
");
$stmt->execute([$order_id, $customer_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Order tidak ditemukan atau bukan milik Anda.");
}

// Ambil item
$items = getOrderItems($order_id);

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="icon" href="/Adin-Laundry/assets/images/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <title>Struk Pembayaran - Adin Laundry</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f3f4f7;
            margin: 0;
            padding: 20px;
        }

        .print-container {
            max-width: 720px;
            margin: 80px auto;
            background: white;
            padding: 25px 30px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 10px;
        }

        .header-left h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }

        .header-left p {
            margin: 3px 0;
            font-size: 13px;
            color: #555;
        }

        .header-right {
            text-align: right;
            font-size: 13px;
        }

        .header-right .order-id {
            font-weight: 700;
            font-size: 14px;
        }

        .header-right .date {
            color: #555;
            margin-top: 4px;
        }

        /* INFO SECTION */
        .info {
            display: flex;
            justify-content: space-between;
            margin: 15px 0 20px 0;
            font-size: 13px;
        }

        .info-box {
            width: 48%;
            background: #f8f9fa;
            padding: 10px 12px;
            border-radius: 10px;
        }

        .info-box h4 {
            margin: 0 0 8px 0;
            font-size: 14px;
        }

        /* TABLE ITEMS */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-top: 10px;
        }

        .items-table th,
        .items-table td {
            border-bottom: 1px solid #e0e0e0;
            padding: 8px 4px;
        }

        .items-table th {
            text-align: left;
            background: #f3f4f7;
        }

        /* TOTAL */
        .total-box {
            margin-top: 15px;
            text-align: right;
            font-size: 14px;
        }

        .total-row {
            margin: 3px 0;
        }

        .total-row span:first-child {
            font-weight: 500;
            margin-right: 10px;
        }

        .total-row strong {
            font-size: 16px;
        }

        /* FOOTER & BUTTON */
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
            text-align: center;
        }

        .actions {
            margin-top: 20px;
            text-align: right;
        }

        .btn-print,
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }

        .btn-print {
            background: #00c896;
            color: white;
        }

        .btn-back {
            background: #b0bec5;
            color: #263238;
            margin-right: 6px;
        }

        .btn-print:hover,
        .btn-back:hover {
            filter: brightness(0.95);
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .print-container {
                margin: 0;
                box-shadow: none;
                border-radius: 0;
            }

            .actions {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="print-container">

        <div class="header">
            <div class="header-left">
                <h2>Adin Laundry</h2>
                <p>Jl. Contoh Alamat No. 123, Kota</p>
                <p>Telp: 08xx-xxxx-xxxx</p>
            </div>
            <div class="header-right">
                <div class="order-id">Struk #<?= htmlspecialchars($order['id']); ?></div>
                <div class="date">
                    Tanggal: <?= date('d M Y H:i', strtotime($order['created_at'])); ?><br>
                    Status: <?= ucfirst($order['tracking_status'] ?? 'pending'); ?>
                </div>
            </div>
        </div>

        <div class="info">
            <div class="info-box">
                <h4>Data Pelanggan</h4>
                <div><?= htmlspecialchars($order['customer_name']); ?></div>
                <?php if (!empty($order['customer_email'])): ?>
                    <div><?= htmlspecialchars($order['customer_email']); ?></div>
                <?php endif; ?>
                <div><strong>ID Pelanggan:</strong> <?= $order['customer_id']; ?></div>
            </div>

            <div class="info-box">
                <h4>Detail Pesanan</h4>
                <div><strong>Tgl Jemput:</strong> <?= date('d M Y', strtotime($order['pickup_date'])); ?></div>
                <div><strong>Waktu:</strong> <?= $order['pickup_time']; ?></div>
                <div><strong>Metode Bayar:</strong> <?= strtoupper($order['payment_method'] ?? '-'); ?></div>
                <?php if (!empty($order['payment_status'])): ?>
                    <div><strong>Status Bayar:</strong> <?= ucfirst($order['payment_status']); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div style="font-size:13px; margin-bottom:5px;">
            <strong>Alamat Jemput:</strong><br>
            <?= nl2br(htmlspecialchars($order['delivery_address'])); ?>
        </div>

        <?php if (!empty($order['notes'])): ?>
            <div style="font-size:13px; margin:5px 0 10px 0;">
                <strong>Catatan:</strong><br>
                <?= nl2br(htmlspecialchars($order['notes'])); ?>
            </div>
        <?php endif; ?>

        <table class="items-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Layanan</th>
                    <th>Qty</th>
                    <th>Harga</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                $total = 0;
                foreach ($items as $it):
                    $total += $it['total_price'];
                ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($it['service_name']); ?></td>
                        <td><?= $it['quantity']; ?></td>
                        <td>Rp <?= number_format($it['price'], 0, ',', '.'); ?></td>
                        <td>Rp <?= number_format($it['total_price'], 0, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-box">
            <div class="total-row">
                <span>Total</span>
                <strong>Rp <?= number_format($total, 0, ',', '.'); ?></strong>
            </div>
        </div>

        <div class="footer">
            Terima kasih telah menggunakan layanan Adin Laundry.<br>
            Simpan struk ini sebagai bukti pembayaran.
        </div>

        <div class="actions">
            <a href="status.php" class="btn-back">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
            <button class="btn-print" onclick="window.print()">
                <i class="fa fa-print"></i> Cetak
            </button>
        </div>

    </div>

</body>

</html>