<?php
/**
 * TESTING & VERIFICATION FILE
 * Untuk memverifikasi fitur assignment pesanan ke kurir bekerja dengan baik
 */

include 'includes/database.php';
include 'includes/functions.php';

// ============================================================
// 1. CEK STRUKTUR TABEL
// ============================================================
echo "<h2>✓ Verifikasi Struktur Tabel</h2>";

try {
    $db = new Database();
    $pdo = $db->pdo;

    // Cek tabel order_assignments
    $tables = $pdo->query("SHOW TABLES LIKE '%assignment%'")->fetchAll();
    if (count($tables) > 0) {
        echo "<p style='color: green;'>✅ Tabel order_assignments ada</p>";
        
        $columns = $pdo->query("DESCRIBE order_assignments")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>❌ Tabel order_assignments tidak ditemukan. Jalankan setup_order_assignments.php terlebih dahulu</p>";
    }

    // ============================================================
    // 2. CEK DATA KURIR
    // ============================================================
    echo "<h2>✓ Data Kurir</h2>";
    $couriers = $pdo->query("SELECT id, name, phone, is_active FROM couriers")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($couriers);
    echo "</pre>";

    // ============================================================
    // 3. CEK DATA PESANAN HARI INI
    // ============================================================
    echo "<h2>✓ Data Pesanan Hari Ini (Status Pending/Konfirmasi)</h2>";
    $todayOrders = $pdo->query("
        SELECT o.id, o.customer_id, o.status, o.tracking_status, o.created_at,
               u.username, p.status as payment_status
        FROM orders o
        JOIN users u ON o.customer_id = u.id
        LEFT JOIN payments p ON p.order_id = o.id
        WHERE DATE(o.created_at) = CURDATE() AND o.tracking_status = 'pending'
        ORDER BY o.id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($todayOrders);
    echo "</pre>";

    // ============================================================
    // 4. CEK ASSIGNMENT YANG ADA
    // ============================================================
    echo "<h2>✓ Assignment Pesanan ke Kurir</h2>";
    $assignments = $pdo->query("
        SELECT oa.id, oa.order_id, oa.courier_id, c.name as courier_name, 
               oa.assigned_by, oa.assigned_at
        FROM order_assignments oa
        LEFT JOIN couriers c ON oa.courier_id = c.id
        ORDER BY oa.assigned_at DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($assignments);
    echo "</pre>";

    // ============================================================
    // 5. INSTRUKSI TESTING
    // ============================================================
    echo "<h2>📝 LANGKAH TESTING</h2>";
    echo "<ol>";
    echo "<li>Login ke akun Admin</li>";
    echo "<li>Buka menu 'Data Pesanan' atau tab 'Pesanan Baru'</li>";
    echo "<li>Cari pesanan dengan status 'Konfirmasi' (tracking_status = pending)</li>";
    echo "<li>Pada bagian WhatsApp pelanggan, akan ada dropdown 'Pilih Kurir'</li>";
    echo "<li>Pilih kurir dan klik. Sistem akan menampilkan konfirmasi</li>";
    echo "<li>Setelah dikonfirmasi, pesanan akan di-assign ke kurir terpilih</li>";
    echo "<li>Tracking status akan berubah menjadi 'Kurir Menjemput'</li>";
    echo "<li>Login ke akun Kurir yang di-assign pesanan</li>";
    echo "<li>Pesanan hanya akan muncul di halaman kurir yang di-assign</li>";
    echo "</ol>";

    // ============================================================
    // 6. PERIKSA KOLOM DALAM ORDERS TABEL
    // ============================================================
    echo "<h2>✓ Struktur Tabel Orders</h2>";
    $orderColumns = $pdo->query("DESCRIBE orders")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($orderColumns);
    echo "</pre>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
