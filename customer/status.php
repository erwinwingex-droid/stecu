<?php
include '../includes/auth.php';
requireLogin();
include '../includes/functions.php';

$orders = getCustomerOrders($_SESSION['user_id']);

if (isset($_GET['success'])) {
    $success = "Pesanan berhasil dibuat! Silakan tunggu konfirmasi dari kami.";
}

// Mapping tracking icon & label (icon yang VALID)
$tracking_steps = [
    'pending'     => ['label' => 'Pesanan Dibuat',      'icon' => 'fa-file-lines'],
    'picked_up'   => ['label' => 'Kurir Menjemput',     'icon' => 'fa-motorcycle'],
    'washing'     => ['label' => 'Sedang Dicuci',       'icon' => 'fa-soap'],   // pakai icon valid
    'drying'      => ['label' => 'Pengeringan',         'icon' => 'fa-wind'],
    'ironing'     => ['label' => 'Penyetrikaan',        'icon' => 'fa-shirt'],
    'delivering'  => ['label' => 'Dalam Pengantaran',   'icon' => 'fa-truck-fast'],
    'completed'   => ['label' => 'Selesai',             'icon' => 'fa-circle-check'],
];

$tracking_sequence = ['pending', 'picked_up', 'washing', 'drying', 'ironing', 'delivering', 'completed'];

/**
 * Untuk badge status di pojok kanan atas card,
 * kita map dari tracking_status menjadi label + class warna.
 */
function mapTrackingToBadge($tracking_status)
{
    switch ($tracking_status) {
        case 'pending':
            return ['class' => 'pending',   'label' => 'Menunggu Konfirmasi'];
        case 'picked_up':
        case 'washing':
        case 'drying':
        case 'ironing':
            return ['class' => 'processing', 'label' => 'Sedang Diproses'];
        case 'delivering':
            return ['class' => 'ready',     'label' => 'Sedang Diantar'];
        case 'completed':
            return ['class' => 'completed', 'label' => 'Selesai'];
        case 'cancelled':
            return ['class' => 'cancelled', 'label' => 'Dibatalkan'];
        default:
            return ['class' => 'pending',   'label' => ucfirst($tracking_status)];
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="icon" href="/Adin-Laundry/assets/images/icon_logo.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pesanan - Adin Laundry</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">

    <style>
        body {
            background: #f3f4f7;
            font-family: 'Poppins', sans-serif;
        }

        .page-title {
            text-align: center;
            margin-top: 160px;
            font-size: 32px;
            font-weight: 700;
            color: #333;
        }

        /* SUCCESS BANNER */
        .success-banner {
            background: #d6f5e2;
            padding: 15px 20px;
            border-radius: 10px;
            color: #0d7d4d;
            font-weight: 500;
            margin: 25px auto;
            width: 100%;
            max-width: 900px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        /* ORDER CARD */
        .order-card {
            background: white;
            padding: 25px;
            border-radius: 18px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            width: 100%;
            max-width: 900px;
            margin: auto;
            backdrop-filter: blur(10px);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .order-header h3 {
            font-size: 22px;
            margin: 0;
            font-weight: 700;
        }

        /* BADGE */
        .order-status {
            padding: 8px 14px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            color: white;
        }

        .order-status.pending {
            background: #ffb74d;
        }

        .order-status.processing {
            background: #42a5f5;
        }

        .order-status.ready {
            background: #7e57c2;
        }

        .order-status.completed {
            background: #66bb6a;
        }

        .order-status.cancelled {
            background: #ef5350;
        }

        /* TRACKING */
        .tracking-container {
            margin-top: 25px;
            margin-bottom: 30px;
            position: relative;
        }

        .tracking-line {
            position: absolute;
            top: 30px;
            left: 0;
            right: 0;
            height: 5px;
            background: #ddd;
            border-radius: 5px;
            z-index: 1;
        }

        .tracking-line-active {
            position: absolute;
            top: 30px;
            left: 0;
            height: 5px;
            background: #00c896;
            border-radius: 5px;
            z-index: 2;
            transition: 0.4s;
        }

        .tracking-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            z-index: 10;
        }

        .step {
            text-align: center;
            width: 14.2%;
            z-index: 3;
        }

        .step .icon {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #dfe6e9;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 20px;
            margin: auto;
            color: #555;
            transition: 0.3s;
        }

        .step.active .icon {
            background: #00c896;
            color: white;
            transform: scale(1.18);
            box-shadow: 0 4px 12px rgba(0, 200, 150, 0.4);
        }

        .step p {
            font-size: 11px;
            font-weight: 600;
            margin-top: 8px;
            color: #444;
        }

        /* DETAILS */
        .order-details {
            background: #fafafa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .detail-item {
            margin-bottom: 10px;
            font-size: 14px;
        }

        .detail-item .label {
            font-weight: 600;
            color: #555;
        }

        .detail-item .value {
            float: right;
            color: #333;
        }

        /* HISTORY */
        .history-box {
            background: #f1f3f5;
            padding: 15px;
            border-radius: 10px;
        }

        .history-item {
            background: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .history-item .status {
            color: #0d6efd;
            font-weight: 600;
        }

        .history-item .time {
            font-size: 12px;
            color: #777;
            margin-top: 5px;
        }

        /* BUTTON */
        .order-actions {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }

        .order-actions .btn {
            padding: 10px 18px;
            border-radius: 8px;
            border: none;
            color: white;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
        }

        .btn-feedback {
            background: #ffb74d;
        }

        .btn-struk {
            background: #00c896;
        }

        .btn-feedback:hover {
            filter: brightness(0.95);
        }

        .btn-struk:hover {
            filter: brightness(0.95);
        }
    </style>
</head>

<body>

    <?php include '../includes/header.php'; ?>

    <h1 class="page-title">Status Pesanan</h1>

    <?php if (isset($success)): ?>
        <div class="success-banner"><?= $success; ?></div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>

        <div style="text-align:center; margin-top:50px;">
            <i class="fa-solid fa-box-open" style="font-size:70px; color:#ccc;"></i>
            <h3 style="margin-top:15px;">Belum ada pesanan</h3>
            <p>Silakan buat pesanan pertama Anda</p>
            <a href="order.php" class="btn btn-primary">Pesan Sekarang</a>
        </div>

    <?php else: ?>

        <?php foreach ($orders as $order): ?>
            <?php
            $track = $order['tracking_status'] ?? 'pending';
            $history = getTrackingHistory($order['id']);

            // index untuk progress bar
            $current_index = array_search($track, $tracking_sequence);
            if ($current_index === false) {
                $current_index = 0;
            }
            $progress_width = ($current_index / (count($tracking_sequence) - 1)) * 100;

            // mapping badge dari tracking_status
            $badge = mapTrackingToBadge($track);
            ?>
            <div class="order-card">

                <!-- HEADER -->
                <div class="order-header">
                    <h3>#<?= $order['id']; ?></h3>

                    <div class="order-status <?= htmlspecialchars($badge['class']); ?>">
                        <?= htmlspecialchars($badge['label']); ?>
                    </div>
                </div>

                <!-- TRACKING -->
                <div class="tracking-container">

                    <div class="tracking-line"></div>

                    <div class="tracking-line-active" style="width: <?= $progress_width; ?>%;"></div>

                    <div class="tracking-steps">
                        <?php foreach ($tracking_sequence as $step):
                            $step_index = array_search($step, $tracking_sequence);
                            $is_active = $step_index !== false && $step_index <= $current_index;
                        ?>
                            <div class="step <?= $is_active ? 'active' : '' ?>">
                                <div class="icon">
                                    <i class="fa <?= $tracking_steps[$step]['icon']; ?>"></i>
                                </div>
                                <p><?= $tracking_steps[$step]['label']; ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>

                <!-- DETAILS -->
                <div class="order-details">
                    <div class="detail-item">
                        <span class="label">Total Item</span>
                        <span class="value"><?= $order['total_items']; ?></span>
                    </div>

                    <div class="detail-item">
                        <span class="label">Total Harga</span>
                        <span class="value">Rp <?= number_format($order['total_price'], 0, ',', '.'); ?></span>
                    </div>

                    <div class="detail-item">
                        <span class="label">Tanggal Jemput</span>
                        <span class="value">
                            <?= date('d M Y', strtotime($order['pickup_date'])); ?> (<?= $order['pickup_time']; ?>)
                        </span>
                    </div>

                    <div class="detail-item">
                        <span class="label">Pembayaran</span>
                        <span class="value">
                            <?= strtoupper($order['payment_method'] ?? '-'); ?>
                            <?php if (!empty($order['payment_status'])): ?>
                                (<?= ucfirst($order['payment_status']); ?>)
                            <?php endif; ?>
                        </span>
                    </div>

                    <?php if (!empty($order['notes'])): ?>
                        <div class="detail-item">
                            <span class="label">Catatan</span>
                            <span class="value"><?= $order['notes']; ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($track === 'completed' && !empty($order['delivery_proof'])): ?>
                        <div class="detail-item">
                            <span class="label">Bukti Pengiriman</span>
                            <span class="value">
                                <button onclick="openProofModal('<?= htmlspecialchars($order['delivery_proof']); ?>')" class="btn btn-struk" style="padding: 6px 12px;">
                                    <i class="fa fa-image"></i> Lihat Bukti
                                </button>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- HISTORY -->
                <?php if (!empty($history)): ?>
                    <div class="history-box">
                        <h6 style="font-weight:700; margin-bottom:10px;">Riwayat Tracking</h6>

                        <?php foreach ($history as $h): ?>
                            <div class="history-item">
                                <div class="status">
                                    <i class="fa fa-clock"></i> <?= ucfirst($h['status']); ?>
                                </div>

                                <?php if (!empty($h['note'])): ?>
                                    <div class="note"><strong>Catatan:</strong> <?= $h['note']; ?></div>
                                <?php endif; ?>

                                <div class="time">
                                    <?= date('d M Y H:i', strtotime($h['created_at'])); ?>
                                    — <em><?= $h['updated_by']; ?></em>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- ACTION BUTTONS -->
                <div class="order-actions">
                    <!-- Tombol Struk selalu ada setelah order berhasil dibuat -->
                    <a href="receipt.php?order_id=<?= $order['id']; ?>" target="_blank" class="btn btn-struk">
                        <i class="fa fa-receipt"></i> Lihat Struk
                    </a>

                    <?php if ($track === 'completed'): ?>
                        <a href="feedback.php?order_id=<?= $order['id']; ?>" class="btn btn-feedback">
                            <i class="fa fa-star"></i> Beri Feedback
                        </a>
                    <?php endif; ?>
                </div>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</body>

</html>