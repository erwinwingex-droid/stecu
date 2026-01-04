<?php
/**
 * Debug file untuk memastikan relasi kurir-user bekerja
 */

include 'includes/database.php';

try {
    $db = new Database();
    $pdo = $db->pdo;

    echo "<h2>🔍 DEBUG: Relasi Kurir - User</h2>";

    // 1. Lihat struktur tabel users
    echo "<h3>Struktur Tabel USERS</h3>";
    $userColumns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($userColumns);
    echo "</pre>";

    // 2. Lihat struktur tabel couriers
    echo "<h3>Struktur Tabel COURIERS</h3>";
    $courierColumns = $pdo->query("DESCRIBE couriers")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($courierColumns);
    echo "</pre>";

    // 3. Lihat daftar user dengan role kurir
    echo "<h3>User dengan Role KURIR</h3>";
    $kurirs = $pdo->query("
        SELECT id, username, email, whatsapp, role 
        FROM users 
        WHERE role = 'kurir'
    ")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($kurirs);
    echo "</pre>";

    // 4. Lihat daftar couriers
    echo "<h3>Data Couriers</h3>";
    $couriers = $pdo->query("
        SELECT id, name, phone, is_active 
        FROM couriers
    ")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($couriers);
    echo "</pre>";

    // 5. Try join - matching kurir dengan users
    echo "<h3>Matching User Kurir dengan Couriers</h3>";
    $matchings = $pdo->query("
        SELECT 
            u.id as user_id,
            u.username,
            u.whatsapp,
            c.id as courier_id,
            c.name,
            c.phone
        FROM users u
        LEFT JOIN couriers c ON u.whatsapp = c.phone
        WHERE u.role = 'kurir'
    ")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($matchings);
    echo "</pre>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
