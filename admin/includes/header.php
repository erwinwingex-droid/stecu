<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once(__DIR__ . '/../../includes/auth.php');
requireAdmin();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <!-- ===== Header Admin ===== -->
    <header class="admin-header">
        <div class="header-container">
            <!-- Left: Menu Toggle & Brand -->
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="brand">
                    <img src="../assets/images/icon_logo.png" alt="Adin Laundry" class="logo">
                </div>
            </div>

            <!-- Center: Search -->
            <div class="header-center">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search orders, customers...">
                </div>
            </div>

            <!-- Right: Admin Info & Actions -->
            <div class="header-right">
                <div class="admin-info">
                    <div class="admin-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="admin-details">
                        <span class="admin-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <span class="admin-role">Administrator</span>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="btn-notification">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </button>
                    <a href="../logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>
</body>

</html>