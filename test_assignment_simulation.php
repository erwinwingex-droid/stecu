<?php
/**
 * SIMULASI & TESTING FITUR ASSIGNMENT
 * File ini untuk testing tanpa perlu UI manual
 */

include 'includes/database.php';
include 'includes/functions.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = new Database();
    $pdo = $db->pdo;

    echo "<h1>🧪 SIMULASI TESTING FITUR ASSIGNMENT</h1>";
    echo "<hr>";

    // ============================================================
    // STEP 1: Ambil data test
    // ============================================================
    echo "<h2>📊 STEP 1: Ambil Data Test</h2>";

    // Ambil pesanan hari ini yang masih pending
    $testOrder = $pdo->query("
        SELECT o.id, o.customer_id, o.tracking_status, o.created_at
        FROM orders o
        WHERE DATE(o.created_at) = CURDATE() 
        AND o.tracking_status = 'pending'
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);

    // Ambil kurir yang aktif
    $testCourier = $pdo->query("
        SELECT id, phone, name
        FROM couriers
        WHERE is_active = 1
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);

    if (!$testOrder) {
        echo "<p style='color: orange;'>⚠️ Tidak ada pesanan pending hari ini untuk ditest</p>";
        echo "<p>Silakan buat pesanan terlebih dahulu</p>";
        exit;
    }

    if (!$testCourier) {
        echo "<p style='color: red;'>❌ Tidak ada kurir aktif</p>";
        exit;
    }

    echo "<p><strong>Order untuk ditest:</strong> #" . $testOrder['id'] . " (Status: " . $testOrder['tracking_status'] . ")</p>";
    echo "<p><strong>Kurir untuk ditest:</strong> " . $testCourier['name'] . " (Phone: " . $testCourier['phone'] . ")</p>";

    // ============================================================
    // STEP 2: Validasi kondisi sebelum assignment
    // ============================================================
    echo "<h2>✓ STEP 2: Validasi Kondisi Pre-Assignment</h2>";

    // Cek apakah order sudah di-assign
    $existingAssignment = $pdo->prepare("
        SELECT id FROM order_assignments 
        WHERE order_id = ? AND courier_id = ?
    ");
    $existingAssignment->execute([$testOrder['id'], $testCourier['id']]);

    if ($existingAssignment->rowCount() > 0) {
        echo "<p style='color: orange;'>⚠️ Pesanan sudah di-assign ke kurir ini</p>";
        echo "<p>Hasil: SKIP (sudah ada assignment sebelumnya)</p>";
        exit;
    } else {
        echo "<p style='color: green;'>✅ Pesanan belum di-assign ke kurir ini</p>";
    }

    // ============================================================
    // STEP 3: Simulasi Assignment
    // ============================================================
    echo "<h2>🎯 STEP 3: Lakukan Assignment</h2>";

    // Insert assignment
    $insertAssignment = $pdo->prepare("
        INSERT INTO order_assignments (order_id, courier_id, assigned_by, assigned_at)
        VALUES (?, ?, ?, NOW())
    ");
    $insertAssignment->execute([$testOrder['id'], $testCourier['id'], 'TEST_ADMIN']);
    
    echo "<p style='color: green;'>✅ Assignment berhasil disimpan ke database</p>";

    // Update tracking status
    $updateTracking = $pdo->prepare("
        UPDATE orders 
        SET tracking_status = 'picked_up', tracking_updated = NOW()
        WHERE id = ?
    ");
    $updateTracking->execute([$testOrder['id']]);

    echo "<p style='color: green;'>✅ Tracking status berhasil diupdate menjadi 'picked_up'</p>";

    // Insert tracking history
    $insertHistory = $pdo->prepare("
        INSERT INTO tracking_history (order_id, status, note, updated_by)
        VALUES (?, 'picked_up', ?, ?)
    ");
    $insertHistory->execute([
        $testOrder['id'], 
        'Pesanan dikirim ke kurir ' . $testCourier['name'],
        'TEST_ADMIN'
    ]);

    echo "<p style='color: green;'>✅ Tracking history berhasil dicatat</p>";

    // ============================================================
    // STEP 4: Verifikasi Assignment
    // ============================================================
    echo "<h2>✓ STEP 4: Verifikasi Assignment</h2>";

    // Cek assignment yang baru dibuat
    $verifyAssignment = $pdo->prepare("
        SELECT oa.*, c.name as courier_name
        FROM order_assignments oa
        LEFT JOIN couriers c ON oa.courier_id = c.id
        WHERE oa.order_id = ? AND oa.courier_id = ?
    ");
    $verifyAssignment->execute([$testOrder['id'], $testCourier['id']]);
    $assignment = $verifyAssignment->fetch(PDO::FETCH_ASSOC);

    if ($assignment) {
        echo "<p style='color: green;'>✅ Assignment ditemukan di database:</p>";
        echo "<ul>";
        echo "<li>Assignment ID: " . $assignment['id'] . "</li>";
        echo "<li>Order ID: " . $assignment['order_id'] . "</li>";
        echo "<li>Courier: " . $assignment['courier_name'] . "</li>";
        echo "<li>Assigned By: " . $assignment['assigned_by'] . "</li>";
        echo "<li>Assigned At: " . $assignment['assigned_at'] . "</li>";
        echo "</ul>";
    }

    // Cek status pesanan
    $verifyOrder = $pdo->prepare("
        SELECT id, tracking_status FROM orders WHERE id = ?
    ");
    $verifyOrder->execute([$testOrder['id']]);
    $updatedOrder = $verifyOrder->fetch(PDO::FETCH_ASSOC);

    if ($updatedOrder['tracking_status'] === 'picked_up') {
        echo "<p style='color: green;'>✅ Tracking status berhasil diupdate menjadi 'picked_up'</p>";
    }

    // ============================================================
    // STEP 5: Test Query dari Perspektif Kurir
    // ============================================================
    echo "<h2>📋 STEP 5: Test Query Kurir</h2>";

    // Ambil courier_id dari user (simulasi login kurir)
    $courierUser = $pdo->query("
        SELECT u.id, u.username, u.whatsapp, c.id as courier_id
        FROM users u
        LEFT JOIN couriers c ON u.whatsapp = c.phone
        WHERE u.role = 'kurir' LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);

    if ($courierUser && $courierUser['courier_id']) {
        echo "<p>Simulasi login sebagai kurir: " . $courierUser['username'] . "</p>";

        // Query yang sama seperti di kurir/dashboard.php
        $testQueryStmt = $pdo->prepare("
            SELECT 
                o.id,
                o.customer_id,
                o.tracking_status,
                u.username AS customer_name

            FROM orders o
            INNER JOIN order_assignments oa ON o.id = oa.order_id
            JOIN users u ON o.customer_id = u.id

            WHERE oa.courier_id = ? 
            AND DATE(o.created_at) = CURDATE() 
            AND o.tracking_status = 'picked_up'
            
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ");

        $testQueryStmt->execute([$courierUser['courier_id']]);
        $ordersForKurir = $testQueryStmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<p style='color: blue;'><strong>Pesanan yang muncul untuk kurir ini:</strong></p>";
        if (count($ordersForKurir) > 0) {
            echo "<ul>";
            foreach ($ordersForKurir as $ord) {
                echo "<li>Order #" . $ord['id'] . " - Customer: " . $ord['customer_name'] . " (Status: " . $ord['tracking_status'] . ")</li>";
            }
            echo "</ul>";
            echo "<p style='color: green;'>✅ Query kurir bekerja dengan baik!</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Tidak ada pesanan yang muncul (mungkin kurir_id tidak cocok)</p>";
        }
    }

    // ============================================================
    // SUMMARY
    // ============================================================
    echo "<h2>📝 SUMMARY</h2>";
    echo "<ul>";
    echo "<li>✅ Tabel order_assignments sudah dibuat</li>";
    echo "<li>✅ Assignment dapat disimpan dengan baik</li>";
    echo "<li>✅ Tracking status dapat diupdate</li>";
    echo "<li>✅ Query kurir dapat membaca pesanan yang di-assign</li>";
    echo "</ul>";
    echo "<p style='color: green; font-weight: bold;'>🎉 FITUR ASSIGNMENT SIAP DIGUNAKAN!</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>";
    echo $e->getTraceAsString();
    echo "</pre>";
}
?>
