<?php
include_once __DIR__ . '/config.php';

/**
 * ==========================================
 *  LAYANAN & PROMO
 * ==========================================
 */

// ✅ Ambil daftar layanan aktif
function getServices()
{
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM services WHERE is_active = 1");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ✅ Ambil promo aktif
function getActivePromotions()
{
    global $pdo;
    $stmt = $pdo->query("
        SELECT p.*, s.name AS service_name 
        FROM promotions p
        LEFT JOIN services s ON p.service_id = s.id
        WHERE p.is_active = 1
        AND (p.start_date IS NULL OR p.start_date <= CURDATE())
        AND (p.end_date IS NULL OR p.end_date >= CURDATE())
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ==========================================
 *  PEMESANAN + PEMBAYARAN
 * ==========================================
 */

function createOrder(
    $customer_id,
    $items,
    $pickup_date,
    $pickup_time,
    $delivery_address,
    $notes,
    $payment_method = 'cod',
    $payment_proof_path = null
) {
    global $pdo;

    try {
        $pdo->beginTransaction();

        $grand_total = 0;

        // Hitung total dari items + promo
        foreach ($items as $item) {
            if (!isset($item['service_id'], $item['quantity'])) {
                throw new Exception('Format item tidak valid');
            }

            $service_id = $item['service_id'];
            $quantity   = $item['quantity'];

            $stmt = $pdo->prepare("SELECT base_price FROM services WHERE id = ?");
            $stmt->execute([$service_id]);
            $service = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$service) {
                throw new Exception("Layanan tidak ditemukan");
            }

            $base_price = (float)$service['base_price'];
            $subtotal   = $base_price * $quantity;

            // Terapkan promo jika ada
            $promo = checkPromotion($service_id, $quantity);
            if ($promo) {
                if ($promo['discount_type'] === 'percentage') {
                    $subtotal *= (1 - $promo['discount_value'] / 100);
                } else {
                    $subtotal -= $promo['discount_value'];
                }
            }

            $grand_total += $subtotal;
        }

        // Tentukan status order awal berdasarkan metode pembayaran (COD => diproses)
        $normalized_method = $payment_method ?: 'cod';
        $order_status = ($normalized_method === 'cod') ? 'processing' : 'pending';

        // Insert ke orders (simpan status juga)
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                customer_id,
                total_price,
                pickup_date,
                pickup_time,
                delivery_address,
                notes,
                status,
                tracking_status,
                created_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([
            $customer_id,
            $grand_total,
            $pickup_date,
            $pickup_time,
            $delivery_address,
            $notes,
            $order_status
        ]);

        $order_id = $pdo->lastInsertId();

        // Insert order_items
        foreach ($items as $item) {
            $service_id = $item['service_id'];
            $quantity   = $item['quantity'];

            $stmt = $pdo->prepare("SELECT base_price FROM services WHERE id = ?");
            $stmt->execute([$service_id]);
            $service = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$service) {
                throw new Exception("Layanan tidak ditemukan saat insert item");
            }

            $base_price = (float)$service['base_price'];
            $subtotal   = $base_price * $quantity;

            $promo = checkPromotion($service_id, $quantity);
            if ($promo) {
                if ($promo['discount_type'] === 'percentage') {
                    $subtotal *= (1 - $promo['discount_value'] / 100);
                } else {
                    $subtotal -= $promo['discount_value'];
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, service_id, quantity, price, total_price)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $order_id,
                $service_id,
                $quantity,
                $base_price,
                $subtotal
            ]);
        }

        // Insert pembayaran
        // Jika metode COD, anggap pembayaran sudah valid/terkonfirmasi otomatis
        $payment_status    = ($normalized_method === 'cod') ? 'confirmed' : 'waiting_confirmation';

        $stmt = $pdo->prepare("
            INSERT INTO payments (order_id, method, amount, status, bukti, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $order_id,
            $normalized_method,
            $grand_total,
            $payment_status,
            $payment_proof_path
        ]);

        // Tracking awal
        addTrackingHistory($order_id, 'pending', 'Pesanan dibuat', 'System');

        $pdo->commit();

        // 🔴 PENTING: kembalikan ID order supaya bisa dipakai redirect ke struk.php
        return $order_id;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("createOrder ERROR: " . $e->getMessage());
        return false;
    }
}

/**
 * Promo check
 */
function checkPromotion($service_id, $quantity)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM promotions
        WHERE service_id = ? 
          AND min_quantity <= ? 
          AND is_active = 1
          AND (start_date IS NULL OR start_date <= CURDATE())
          AND (end_date   IS NULL OR end_date   >= CURDATE())
        ORDER BY discount_value DESC
        LIMIT 1
    ");
    $stmt->execute([$service_id, $quantity]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * ==========================================
 *  DATA PESANAN (CUSTOMER)
 * ==========================================
 */

function getCustomerOrders($customer_id)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            COUNT(oi.id) AS total_items,
            SUM(oi.quantity) AS quantity,
            p.method AS payment_method,
            p.status AS payment_status,
            p.bukti AS payment_proof
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN payments p ON o.id = p.order_id
        WHERE o.customer_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$customer_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getOrderItems($order_id)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT oi.*, s.name AS service_name
        FROM order_items oi
        JOIN services s ON oi.service_id = s.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * 🔹 DIGUNAKAN UNTUK STRUK (customer only)
 */
function getOrderByIdAndUser($order_id, $customer_id)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            u.username AS customer_name
        FROM orders o
        JOIN users u ON o.customer_id = u.id
        WHERE o.id = ? AND o.customer_id = ?
        LIMIT 1
    ");
    $stmt->execute([$order_id, $customer_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * ==========================================
 *  UPDATE STATUS PESANAN (LAMA)
 * ==========================================
 */

// Kalau masih dipakai di bagian lama yang pakai kolom 'status'
function updateOrderStatus($order_id, $status)
{
    global $pdo;
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $order_id]);
}

/**
 * ==========================================
 *  FITUR BARU: TRACKING PESANAN
 * ==========================================
 */

// 🟦 Tambah Riwayat Tracking
function addTrackingHistory($order_id, $status, $note, $updated_by)
{
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO tracking_history (order_id, status, note, updated_by)
        VALUES (?, ?, ?, ?)
    ");
    return $stmt->execute([$order_id, $status, $note, $updated_by]);
}

// 🟦 Update tracking_status di tabel orders
function updateTrackingStatus($order_id, $status, $note = '', $updated_by = 'Admin')
{
    global $pdo;

    $stmt = $pdo->prepare("UPDATE orders SET tracking_status = ?, tracking_updated = NOW() WHERE id = ?");
    $stmt->execute([$status, $order_id]);

    addTrackingHistory($order_id, $status, $note, $updated_by);
    return true;
}

// 🟦 Ambil riwayat tracking untuk halaman customer
function getTrackingHistory($order_id)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM tracking_history
        WHERE order_id = ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$order_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ==========================================
 *  STATISTIK & DASHBOARD ADMIN
 * ==========================================
 */

function getAdminStats()
{
    global $pdo;
    $stats = [];

    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM orders");
    $stats['total_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM users WHERE role='customer'");
    $stats['total_customers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Jika di tabel orders tidak ada kolom status='completed', sesuaikan query ini
    $stmt = $pdo->query("SELECT SUM(total_price) AS total FROM orders WHERE tracking_status='completed'");
    $stats['total_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $stmt = $pdo->query("SELECT AVG(rating) AS avg FROM feedback WHERE rating IS NOT NULL");
    $stats['avg_rating'] = number_format($stmt->fetch(PDO::FETCH_ASSOC)['avg'] ?? 0, 1);

    return $stats;
}

function getRecentOrders($limit = 5)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            u.username AS customer_name,
            p.method AS payment_method,
            p.status AS payment_status
        FROM orders o
        JOIN users u ON o.customer_id = u.id
        LEFT JOIN payments p ON o.id = p.order_id
        ORDER BY o.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
