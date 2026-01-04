<?php
include '../includes/auth.php';
requireLogin();
include '../includes/functions.php';

$order_id = $_GET['order_id'] ?? 0;
$order = getOrderByIdAndUser($order_id, $_SESSION['user_id']);
$items = getOrderItems($order_id);

if (!$order) {
    die("Pesanan tidak ditemukan.");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="/Adin-Laundry/assets/images/icon_logo.png" type="image/png">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Struk Pembayaran - Adin Laundry</title>

<style>
    body {
        font-family: 'Inter', sans-serif;
        background: #f5f7fb;
        margin: 0;
        padding: 30px;
    }

    .receipt-wrapper {
        max-width: 900px;
        margin: auto;
        background: #ffffff;
        padding: 35px;
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        border: 1px solid #eee;
    }

    .receipt-header {
        border-bottom: 2px solid #e7e7e7;
        padding-bottom: 15px;
        margin-bottom: 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .receipt-header h2 {
        font-size: 24px;
        margin: 0;
        color: #1a1a1a;
    }

    .order-id {
        font-size: 15px;
        color: #666;
    }

    .info-box {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
        margin-bottom: 30px;
    }

    .info-section {
        background: #fafafa;
        padding: 18px 20px;
        border-radius: 12px;
        border: 1px solid #e5e5e5;
    }

    .info-title {
        font-weight: 600;
        margin-bottom: 8px;
        color: #444;
        font-size: 15px;
    }

    .info-value {
        color: #111;
        font-size: 16px;
        font-weight: 500;
    }

    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 15px;
    }

    table thead {
        background: #f2f4f7;
    }

    table th {
        padding: 14px 10px;
        text-align: left;
        font-size: 14px;
        color: #555;
        font-weight: 600;
        border-bottom: 2px solid #e4e4e4;
    }

    table td {
        padding: 13px 10px;
        font-size: 15px;
        border-bottom: 1px solid #ececec;
        color: #333;
    }

    .total-row td {
        font-size: 17px;
        font-weight: 700;
        color: #111;
        border-bottom: none;
    }

    .footer-note {
        margin-top: 18px;
        font-size: 13px;
        color: #777;
        text-align: center;
    }

    .btn-status {
        margin-top: 25px;
        display: block;
        width: 100%;
        padding: 14px;
        text-align: center;
        background: #2d3954;
        color: white;
        border-radius: 10px;
        text-decoration: none;
        font-size: 16px;
        font-weight: 600;
        transition: 0.2s;
    }

    .btn-status:hover {
        background: #1f2a3d;
    }

</style>
</head>

<body>

<div class="receipt-wrapper">

    <div class="receipt-header">
        <h2>Struk Pesanan</h2>
        <div class="order-id">Order ID: <b>#<?= $order_id ?></b></div>
    </div>

    <div class="info-box">

        <div class="info-section">
            <div class="info-title">Pelanggan</div>
            <div class="info-value"><?= htmlspecialchars($order['customer_name']) ?></div>

            <div class="info-title" style="margin-top:10px;">Alamat Penjemputan</div>
            <div class="info-value"><?= nl2br(htmlspecialchars($order['delivery_address'])) ?></div>
        </div>

        <div class="info-section">
            <div class="info-title">Tanggal Jemput</div>
            <div class="info-value"><?= $order['pickup_date'] ?> (<?= $order['pickup_time'] ?>)</div>

            <div class="info-title" style="margin-top:10px;">Metode Pembayaran</div>
            <div class="info-value" style="font-weight:700; color:#007b55;">
                <?= strtoupper($order['payment_method']) ?>
            </div>
        </div>

    </div>

    <h3 style="margin-bottom:10px; color:#222;">Detail Layanan</h3>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Layanan</th>
                <th>Harga</th>
                <th>Qty</th>
                <th>Subtotal</th>
            </tr>
        </thead>

        <tbody>
        <?php 
        $i = 1;
        foreach ($items as $item): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= $item['service_name'] ?></td>
                <td>Rp <?= number_format($item['price'], 0, ',', '.') ?></td>
                <td><?= $item['quantity'] ?></td>
                <td>Rp <?= number_format($item['total_price'], 0, ',', '.') ?></td>
            </tr>
        <?php endforeach; ?>

        <tr class="total-row">
            <td colspan="4" style="text-align:right;">Total</td>
            <td>Rp <?= number_format($order['total_price'], 0, ',', '.') ?></td>
        </tr>
        </tbody>
    </table>

    <p class="footer-note">* Struk ini dibuat otomatis oleh sistem Adin Laundry.</p>

    <a href="status.php" class="btn-status">Lanjut ke Status Pesanan</a>

</div>

</body>
</html>