<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$customer_id       = $_SESSION['user_id'];
$selected_services = $_POST['service_id'] ?? [];
$delivery_address  = $_POST['delivery_address'];
$pickup_date       = $_POST['pickup_date'];
$pickup_time       = $_POST['pickup_time'];
$notes             = $_POST['notes'] ?? '';
$payment_method    = $_POST['payment_method'] ?? 'cod'; // default COD
$payment_proof     = null;

// ====== Validasi ======
if (empty($selected_services)) {
    die("Tidak ada layanan yang dipilih!");
}

// ====== Hitung total harga lama (dipertahankan) ======
$total_price = 0;

foreach ($selected_services as $sid) {
    $qty = floatval($_POST['quantity_' . $sid]);

    $stmt = $pdo->prepare("SELECT base_price FROM services WHERE id = ?");
    $stmt->execute([$sid]);
    $srv = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$srv) continue;

    $subtotal = $srv['base_price'] * $qty;
    $total_price += $subtotal;
}

// =====================================================
// 🔥 Gunakan fungsi createOrder() dari functions.php
// Supaya tracking, payments & order_items terintegrasi rapi
// =====================================================

// Siapkan format items sesuai createOrder()
$items = [];
foreach ($selected_services as $sid) {
    $qty = floatval($_POST['quantity_' . $sid]);

    $stmt = $pdo->prepare("SELECT base_price FROM services WHERE id = ?");
    $stmt->execute([$sid]);
    $srv = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($srv) {
        $items[] = [
            'service_id' => $sid,
            'quantity'   => $qty,
            'price'      => $srv['base_price'],
        ];
    }
}

// 🔥 Panggil createOrder() — otomatis:
// - simpan orders
// - simpan order_items
// - simpan payments
// - tambah tracking awal
// - promo tetap jalan
$order_success = createOrder(
    $customer_id,
    $items,
    $pickup_date,
    $pickup_time,
    $delivery_address,
    $notes,
    $payment_method,
    $payment_proof
);

if ($order_success) {
    header("Location: orders.php?success=1");
    exit;
} else {
    echo "Gagal membuat pesanan!";
}
?>
