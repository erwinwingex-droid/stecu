<?php
session_start();

// Konfigurasi database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'adin_laundry');

try {
    // Tambah charset supaya aman untuk text
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    // Koneksi database dengan PDO
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,      // lempar exception kalau error
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,            // fetch as associative array
        PDO::ATTR_EMULATE_PREPARES   => false,                       // gunakan prepared statement asli
    ]);
} catch (PDOException $e) {
    // Bisa diganti dengan halaman error khusus kalau mau
    die("Koneksi database gagal: " . $e->getMessage());
}

// Fungsi untuk mencegah SQL injection / XSS
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// BASE_URL: root-relative URL segment for this project (e.g. "/Adin-Laundry").
// This is computed dynamically from the filesystem location so includes can
// generate correct URLs regardless of the current request path.
if (!defined('BASE_URL')) {
    $projectRootName = basename(dirname(__DIR__));
    define('BASE_URL', '/' . $projectRootName);
}
?>