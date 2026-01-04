<?php
/**
 * TEST API ENDPOINT
 * File ini untuk test API assign_order_to_courier.php
 */

include 'includes/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🧪 TEST API: assign_order_to_courier.php</h1>";
echo "<hr>";

try {
    $db = new Database();
    $pdo = $db->pdo;

    // Ambil test data
    $testOrder = $pdo->query("
        SELECT id, tracking_status FROM orders 
        WHERE DATE(created_at) = CURDATE() AND tracking_status = 'pending'
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);

    $testCourier = $pdo->query("
        SELECT id, phone FROM couriers WHERE is_active = 1 LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);

    if (!$testOrder || !$testCourier) {
        echo "<p style='color: orange;'>⚠️ Data test tidak lengkap. Buat pesanan dan kurir terlebih dahulu.</p>";
        exit;
    }

    echo "<h2>📊 Test Data</h2>";
    echo "<p><strong>Order ID:</strong> " . $testOrder['id'] . "</p>";
    echo "<p><strong>Order Tracking Status:</strong> " . $testOrder['tracking_status'] . "</p>";
    echo "<p><strong>Courier Phone:</strong> " . $testCourier['phone'] . "</p>";
    echo "<hr>";

    // ============================================================
    // TEST CASE 1: Valid Assignment
    // ============================================================
    echo "<h2>TEST CASE 1: Valid Assignment</h2>";

    $_POST = [
        'order_id' => $testOrder['id'],
        'courier_phone' => $testCourier['phone']
    ];

    // Simulasi session admin
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['username'] = 'admin_test';

    // Include API
    ob_start();
    include 'Api/assign_order_to_courier.php';
    $output = ob_get_clean();

    echo "<p><strong>Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";

    // ============================================================
    // TEST CASE 2: Non-Pending Status
    // ============================================================
    echo "<h2>TEST CASE 2: Order with Non-Pending Status</h2>";

    // Cari order yang sudah di-assign atau bukan pending
    $nonPendingOrder = $pdo->query("
        SELECT id, tracking_status FROM orders 
        WHERE DATE(created_at) = CURDATE() AND tracking_status != 'pending'
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);

    if ($nonPendingOrder) {
        $_POST = [
            'order_id' => $nonPendingOrder['id'],
            'courier_phone' => $testCourier['phone']
        ];

        ob_start();
        include 'Api/assign_order_to_courier.php';
        $output = ob_get_clean();

        echo "<p><strong>Response (Expected Error):</strong></p>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    } else {
        echo "<p style='color: orange;'>⚠️ Tidak ada order dengan status non-pending</p>";
    }

    // ============================================================
    // TEST CASE 3: Non-Admin User
    // ============================================================
    echo "<h2>TEST CASE 3: Non-Admin User Access</h2>";

    $_SESSION['role'] = 'kurir';

    $_POST = [
        'order_id' => $testOrder['id'],
        'courier_phone' => $testCourier['phone']
    ];

    ob_start();
    include 'Api/assign_order_to_courier.php';
    $output = ob_get_clean();

    echo "<p><strong>Response (Expected 403 Forbidden):</strong></p>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";

    echo "<hr>";
    echo "<p style='color: green; font-weight: bold;'>✅ API TEST COMPLETED</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
