<?php
// includes/db.php
// Koneksi DB menggunakan PDO — update kredensial DB sesuai environment Anda.

$DB_HOST = 'localhost';
$DB_NAME = 'adin_laundry';   // <- sesuaikan nama database Anda
$DB_USER = 'root';           // <- sesuaikan user DB
$DB_PASS = '';               // <- sesuaikan password DB

try {
    $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // Jangan tampilkan error DB ke user. Log ke error log.
    error_log("DB CONNECTION ERROR: " . $e->getMessage());
    // Jika perlu, tampilkan pesan sederhana:
    die("Gagal terhubung ke database. Silakan cek konfigurasi.");
}
