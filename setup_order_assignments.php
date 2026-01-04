<?php
/**
 * Database Migration File
 * Membuat tabel order_assignments untuk tracking assignment pesanan ke kurir
 */

include 'includes/database.php';

try {
    $db = new Database();
    $pdo = $db->pdo;

    // Cek apakah tabel sudah ada
    $checkTable = $pdo->query("SHOW TABLES LIKE 'order_assignments'")->rowCount();
    
    if ($checkTable === 0) {
        // Buat tabel order_assignments
        $sql = "
        CREATE TABLE order_assignments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            order_id INT NOT NULL,
            courier_id INT NOT NULL,
            assigned_by VARCHAR(100) NOT NULL,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            UNIQUE KEY unique_order_courier (order_id, courier_id),
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (courier_id) REFERENCES couriers(id) ON DELETE CASCADE,
            
            INDEX idx_courier_id (courier_id),
            INDEX idx_assigned_at (assigned_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($sql);
        echo "✅ Tabel 'order_assignments' berhasil dibuat!<br>";
    } else {
        echo "ℹ️ Tabel 'order_assignments' sudah ada.<br>";
    }

    // Verifikasi struktur tabel
    $verifyColumns = $pdo->query("DESCRIBE order_assignments")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    echo "Struktur Tabel order_assignments:\n";
    print_r($verifyColumns);
    echo "</pre>";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
