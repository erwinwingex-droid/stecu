<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once(__DIR__ . '/../../includes/auth.php');

requireLogin();

// Batasi hanya untuk role pegawai
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pegawai') {
    header('Location: ../index.php');
    exit();
}

// Label role
$roleLabel = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'User';
?>

<!-- ===== Header Admin (Seragam dengan Pelanggan) ===== -->
<header class="main-header">
    <div class="header-container">
        <!-- Logo kiri -->
        <div class="header-left">
            <img src="../assets/images/icon_logo.png" alt="Adin Laundry" class="logo">
        </div>

        <!-- Kanan: Info admin & tombol logout -->
        <div class="header-right">
            <div class="contact-info">
                <span><i class="fa fa-user-shield"></i> <?php echo htmlspecialchars(
                        
                        
                        
                        
                        
                        
                        
                        
                        $_SESSION['username']); ?> (<?php echo htmlspecialchars($roleLabel); ?>)</span>
                <span><i class="fa fa-envelope"></i> adinlaundry@gmail.com</span>
            </div>
            <div class="social-icons">
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
            </div>
            <div class="login-btn-area">
                <a href="../logout.php" class="btn-login">LOGOUT</a>
            </div>
        </div>
    </div>
</header>

<!-- ===== Sidebar Admin ===== -->
<?php include_once(__DIR__ . '/../sidebar.php'); ?>

<!-- ===== Import Font & Icon ===== -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* ===== GLOBAL STYLE ===== */
body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: #f5f6fa;
}

/* ===== HEADER ===== */
.main-header {
    background-color: #0d1b2a;
    color: #ffffff;
    padding: 25px 0;
    box-sizing: border-box;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 2000;
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
}

.header-container {
    width: 92%;
    margin: auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Logo kiri */
.header-left .logo {
    height: 80px;
    display: block;
}

/* Bagian kanan */
.header-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.contact-info {
    font-size: 16px;
    color: #ffffff;
    margin-bottom: 8px;
}
.contact-info i {
    margin-right: 6px;
    color: #f5a623;
}
.contact-info span {
    margin-right: 15px;
}

.social-icons a {
    color: #ffffff;
    font-size: 16px;
    margin-right: 8px;
    transition: 0.3s;
}
.social-icons a:hover {
    color: #f5a623;
}

.btn-login {
    background-color: #f5a623;
    color: #000;
    padding: 8px 20px;
    border-radius: 6px;
    font-weight: 600;
    text-decoration: none;
    transition: 0.3s;
}
.btn-login:hover {
    background-color: #ffaa33;
}

/* ===== SIDEBAR ===== */
.sidebar {
    position: fixed;
    top: 120px; /* di bawah header */
    left: 0;
    width: 230px;
    height: calc(100vh - 120px);
    background: #0d1b2a;
    color: #fff;
    padding-top: 15px;
    overflow-y: auto;
    box-sizing: border-box;
    z-index: 1000;
    box-shadow: 2px 0 10px rgba(0,0,0,0.2);
}

.sidebar-menu {
    list-style: none;
    margin: 0;
    padding: 0;
}

.sidebar-menu li {
    margin-bottom: 5px;
}

.sidebar-menu a {
    color: #ffffff;
    text-decoration: none;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 22px;
    border-radius: 6px;
    transition: all 0.3s;
}

.sidebar-menu a:hover,
.sidebar-menu a.active {
    background: #f5a623;
    color: #000;
}

/* Scrollbar sidebar */
.sidebar::-webkit-scrollbar {
    width: 6px;
}
.sidebar::-webkit-scrollbar-thumb {
    background-color: #f5a623;
    border-radius: 4px;
}

/* ===== MAIN CONTENT (hindari ketutupan) ===== */
.main-content {
    margin-top: 140px;
    margin-left: 250px;
    padding: 30px;
    box-sizing: border-box;
}
</style>
