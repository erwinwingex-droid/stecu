<?php
/**
 * API untuk menugaskan pesanan kepada kurir
 * 
 * Requirements:
 * - Order status harus "confirmation" (konfirmasi)
 * - Hanya admin yang bisa mengirim pesanan
 * - Pesanan dari hari ini saja
 * 
 * POST Parameters:
 * - order_id: ID pesanan
 * - courier_phone: Nomor WhatsApp kurir
 */

include '../includes/auth.php';
include '../includes/database.php';

header('Content-Type: application/json');

// Validasi: Hanya admin yang boleh
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

// Validasi: POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit;
}

// Validasi: Input
if (!isset($_POST['order_id']) || !isset($_POST['courier_phone'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap']);
    exit;
}

try {
    $db = new Database();
    $pdo = $db->pdo;

    // Pastikan tabel order_assignments ada (menciptakan jika belum)
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_assignments (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $order_id = (int)$_POST['order_id'];
    $courier_phone = trim($_POST['courier_phone']);
    // Normalisasi nomor: hapus karakter selain digit
    $courier_phone = preg_replace('/[^0-9]/', '', $courier_phone);

    // 1. Validasi: Order ada dan dari hari ini
    $checkOrder = $pdo->prepare("
        SELECT id, status, tracking_status, created_at 
        FROM orders 
        WHERE id = ? AND DATE(created_at) = CURDATE()
    ");
    $checkOrder->execute([$order_id]);
    $order = $checkOrder->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan atau bukan dari hari ini']);
        exit;
    }

    // 2. Validasi: Status pesanan harus "confirmation"
    // Catatan: tracking_status = 'pending' artinya masih menunggu konfirmasi admin
    if ($order['tracking_status'] !== 'pending') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Pesanan harus dalam status "Konfirmasi" untuk bisa dikirim ke kurir']);
        exit;
    }

    // 3. Validasi: Kurir ada
    $checkCourier = $pdo->prepare("
        SELECT id, name, phone 
        FROM couriers 
        WHERE phone = ? AND is_active = 1
    ");
    $checkCourier->execute([$courier_phone]);
    $courier = $checkCourier->fetch(PDO::FETCH_ASSOC);

    if (!$courier) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Kurir tidak ditemukan atau tidak aktif']);
        exit;
    }

    // 4. Cek apakah pesanan sudah di-assign ke kurir ini
    $checkAssignment = $pdo->prepare("
        SELECT id FROM order_assignments 
        WHERE order_id = ? AND courier_id = ?
    ");
    $checkAssignment->execute([$order_id, $courier['id']]);
    
    if ($checkAssignment->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Pesanan sudah dikirim ke kurir ini']);
        exit;
    }

    // 5. Simpan assignment di tabel order_assignments
    $insertAssignment = $pdo->prepare("
        INSERT INTO order_assignments (order_id, courier_id, assigned_by, assigned_at)
        VALUES (?, ?, ?, NOW())
    ");
    $insertAssignment->execute([$order_id, $courier['id'], $_SESSION['username'] ?? 'Admin']);

    // 6. Update tracking status menjadi "picked_up" (kurir mulai menjemput)
    $updateTracking = $pdo->prepare("
        UPDATE orders 
        SET tracking_status = 'picked_up', tracking_updated = NOW()
        WHERE id = ?
    ");
    $updateTracking->execute([$order_id]);

    // 7. Catat di tracking history
    $insertHistory = $pdo->prepare("
        INSERT INTO tracking_history (order_id, status, note, updated_by)
        VALUES (?, 'picked_up', ?, ?)
    ");
    $insertHistory->execute([
        $order_id, 
        'Pesanan dikirim ke kurir ' . $courier['name'],
        $_SESSION['username'] ?? 'Admin'
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true, 
        'message' => 'Pesanan berhasil dikirim ke kurir ' . $courier['name'],
        'courier_name' => $courier['name']
    ]);

} catch (Exception $e) {
    error_log("assign_order_to_courier ERROR: " . $e->getMessage());
    http_response_code(500);
    // Jika dijalankan di localhost, kembalikan pesan error eksplisit untuk debugging
    if (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false) {
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server: ' . $e->getMessage()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server']);
    }
}
?>
