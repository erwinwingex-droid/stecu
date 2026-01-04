<?php
// Session sudah dijalankan di config.php, jadi kita hanya perlu auth
include_once(__DIR__ . '/../../includes/auth.php');

// Izinkan admin atau owner menggunakan header ini
if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

// Tentukan label role untuk ditampilkan
$roleLabel = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'User';
?>

<!-- Use the admin-style header so owner sees same header/sidebar behavior -->
<!DOCTYPE html>
<header class="admin-header">
    <div class="header-container">
        <div class="header-left">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="brand">
                <img src="<?= BASE_URL ?>/assets/images/icon_logo.png" alt="Adin Laundry" class="logo">
            </div>
        </div>

        <div class="header-center">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search orders, customers...">
            </div>
        </div>

        <div class="header-right">
            <div class="admin-info">
                <div class="admin-avatar"><i class="fas fa-user-tie"></i></div>
                <div class="admin-details">
                    <span class="admin-name"><?= htmlspecialchars($_SESSION['username']); ?></span>
                    <span class="admin-role"><?= htmlspecialchars($roleLabel); ?></span>
                </div>
            </div>
            <div class="header-actions">
                <button class="btn-notification"><i class="fas fa-bell"></i><span class="notification-badge">0</span></button>
                <a href="<?= BASE_URL ?>/logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</header>

<?php include_once(__DIR__ . '/../sidebar.php'); ?>

<!-- Load admin styles to keep header/sidebar consistent -->
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">