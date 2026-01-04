<?php
include '../includes/auth.php';
requireLogin();
include '../includes/config.php';
include '../includes/functions.php';

// Pastikan ada order_id
if (!isset($_GET['order_id'])) {
    header('Location: orders.php');
    exit;
}

$order_id = (int)$_GET['order_id'];

// Ambil data order
$stmt = $pdo->prepare("
    SELECT o.*, u.username 
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    WHERE o.id = ? AND o.customer_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Pesanan tidak ditemukan.");
}

// Ambil / buat data payment
$stmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ?");
$stmt->execute([$order_id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    // Kalau belum ada payment, buat dulu dengan method COD default
    $stmtIns = $pdo->prepare("
        INSERT INTO payments (order_id, method, amount, status, bukti, created_at)
        VALUES (?, 'cod', ?, 'pending', NULL, NOW())
    ");
    $stmtIns->execute([$order_id, $order['total_price']]);

    $stmt->execute([$order_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
}

$error = '';
$success = '';

// Proses submit metode pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = $_POST['payment_method'] ?? '';
    $allow_methods = ['cod', 'qris', 'transfer_bank'];

    if (!in_array($method, $allow_methods)) {
        $error = "Metode pembayaran tidak valid.";
    } else {

        $bukti_path = $payment['bukti'] ?? null;
        $status = ($method === 'cod') ? 'pending' : 'waiting_confirmation';

        // Jika bukan COD → wajib upload bukti
        if ($method !== 'cod') {
            if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] === UPLOAD_ERR_NO_FILE) {
                $error = "Silakan unggah bukti pembayaran untuk metode non-COD.";
            } else {
                $file = $_FILES['payment_proof'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $error = "Terjadi kesalahan saat mengunggah file.";
                } else {
                    // Validasi ukuran max 5MB
                    if ($file['size'] > 5 * 1024 * 1024) {
                        $error = "Ukuran file maksimal 5 MB.";
                    } else {
                        // Validasi ekstensi sederhana (jpg/png/pdf)
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];

                        if (!in_array($ext, $allowed_ext)) {
                            $error = "Format file harus JPG, JPEG, PNG, atau PDF.";
                        } else {
                            // Pastikan folder upload ada
                            $uploadDir = __DIR__ . '/../uploads/payments/';
                            if (!is_dir($uploadDir)) {
                                mkdir($uploadDir, 0777, true);
                            }

                            $newName = 'pay_' . $order_id . '_' . time() . '.' . $ext;
                            $targetPath = $uploadDir . $newName;

                            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                                // Simpan path relatif (misal: uploads/payments/xxxxxx.jpg)
                                $bukti_path = 'uploads/payments/' . $newName;
                            } else {
                                $error = "Gagal menyimpan file bukti pembayaran.";
                            }
                        }
                    }
                }
            }
        }

        if ($error === '') {
            // Update data pembayaran
            $stmtUpdate = $pdo->prepare("
                UPDATE payments 
                SET method = ?, status = ?, bukti = ?, amount = ?
                WHERE id = ?
            ");
            $stmtUpdate->execute([
                $method,
                $status,
                $bukti_path,
                $order['total_price'],
                $payment['id']
            ]);

            $success = "Metode pembayaran berhasil disimpan.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="/Adin-Laundry/assets/images/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <title>Pembayaran - Adin Laundry</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Poppins', sans-serif;
        }
        .payment-container {
            max-width: 800px;
            margin: 150px auto 50px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .payment-header {
            background: #007bff;
            color: #fff;
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .payment-body {
            padding: 1.5rem;
        }
        .order-summary {
            background: #e9f7ff;
            border-radius: 6px;
            padding: 1rem 1.2rem;
            margin-bottom: 1.5rem;
        }
        .order-summary h4 {
            margin: 0 0 0.5rem;
        }
        .payment-method-list {
            margin-bottom: 1.5rem;
        }
        .payment-option {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 0.8rem 1rem;
            margin-bottom: 0.7rem;
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        .payment-option input {
            margin-right: 0.8rem;
        }
        .payment-option-icon {
            margin-right: 0.8rem;
            font-size: 1.4rem;
        }
        .payment-option-title {
            font-weight: 600;
        }
        .payment-option-desc {
            font-size: 0.85rem;
            color: #777;
        }
        .payment-proof {
            margin-bottom: 1.5rem;
        }
        .btn-primary-full {
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: 4px;
            background: #28a745;
            color: #fff;
            font-size: 1rem;
            cursor: pointer;
        }
        .btn-secondary-full {
            width: 100%;
            padding: 0.6rem;
            margin-top: 0.5rem;
            border-radius: 4px;
            border: 1px solid #ccc;
            background: #fff;
            cursor: pointer;
        }
        .alert {
            padding: 0.8rem 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        .qris-box {
            margin-top: 1rem;
            text-align: center;
        }
        .qris-box img {
            max-width: 200px;
        }
        .qris-box small {
            display: block;
            margin-top: 0.5rem;
            color: #555;
        }
    </style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<div class="payment-container">
    <div class="payment-header">
        Pembayaran
    </div>
    <div class="payment-body">

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <div class="order-summary">
            <h4>Detail Pesanan</h4>
            <p><strong>ID Pesanan:</strong> #<?= $order['id']; ?></p>
            <p><strong>Pelanggan:</strong> <?= htmlspecialchars($order['username']); ?></p>
            <p><strong>Total:</strong> Rp <?= number_format($order['total_price'], 0, ',', '.'); ?></p>
            <p><strong>Tanggal Jemput:</strong> <?= date('d M Y', strtotime($order['pickup_date'])); ?> (<?= $order['pickup_time']; ?>)</p>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <h4>Pilih Metode Pembayaran</h4>
            <div class="payment-method-list">
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="cod"
                        <?= ($payment['method'] === 'cod') ? 'checked' : ''; ?>>
                    <div class="payment-option-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div>
                        <div class="payment-option-title">Cash On Delivery (COD)</div>
                        <div class="payment-option-desc">Bayar saat barang diterima</div>
                    </div>
                </label>

                <label class="payment-option">
                    <input type="radio" name="payment_method" value="qris"
                        <?= ($payment['method'] === 'qris') ? 'checked' : ''; ?>>
                    <div class="payment-option-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <div>
                        <div class="payment-option-title">QRIS</div>
                        <div class="payment-option-desc">Bayar dengan QRIS (Dana, OVO, dll)</div>
                    </div>
                </label>

                <label class="payment-option">
                    <input type="radio" name="payment_method" value="transfer_bank"
                        <?= ($payment['method'] === 'transfer_bank') ? 'checked' : ''; ?>>
                    <div class="payment-option-icon">
                        <i class="fas fa-university"></i>
                    </div>
                    <div>
                        <div class="payment-option-title">Transfer Bank</div>
                        <div class="payment-option-desc">Transfer ke rekening bank kami</div>
                    </div>
                </label>
            </div>

            <div class="payment-proof">
                <label><strong>Upload Bukti Pembayaran (untuk QRIS / Transfer Bank)</strong></label>
                <input type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf">
                <small>Maksimal 5 MB. Wajib diisi untuk metode non-COD.</small>

                <?php if (!empty($payment['bukti'])): ?>
                    <div style="margin-top:0.5rem;">
                        <small>File bukti tersimpan: <?= htmlspecialchars($payment['bukti']); ?></small>
                    </div>
                <?php endif; ?>
            </div>

            <!-- QRIS Code (opsional) -->
            <div class="qris-box" id="qrisBox" style="display: none;">
                <h5>Scan QRIS Berikut</h5>
                <img src="../assets/img/qris.png" alt="QRIS">
                <small>Silakan scan QR ini menggunakan aplikasi e-wallet Anda.</small>
            </div>

            <button type="submit" class="btn-primary-full">
                <i class="fas fa-check-circle"></i> Konfirmasi Pembayaran
            </button>

            <button type="button" class="btn-secondary-full" onclick="window.location.href='status.php';">
                &larr; Kembali
            </button>
        </form>
    </div>
</div>

<script>
    function toggleQrisBox() {
        const qrisBox = document.getElementById('qrisBox');
        const methodRadios = document.querySelectorAll('input[name="payment_method"]');
        let selected = '';
        methodRadios.forEach(r => { if (r.checked) selected = r.value; });

        if (selected === 'qris') {
            qrisBox.style.display = 'block';
        } else {
            qrisBox.style.display = 'none';
        }
    }

    document.querySelectorAll('input[name="payment_method"]').forEach(r => {
        r.addEventListener('change', toggleQrisBox);
    });

    // Inisialisasi tampilan QRIS sesuai pilihan awal
    toggleQrisBox();
</script>

</body>
</html>
