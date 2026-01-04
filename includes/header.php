<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/auth.php'); // untuk akses fungsi isLoggedIn()
?>

<script>
    window.addEventListener('scroll', function() {
        const header = document.querySelector('.main-header');
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
</script>

<!-- ===== Header/Navbar Seragam Adin Laundry ===== -->
<header class="main-header">
    <div class="header-container">
        <!-- Kiri: Logo -->
        <div class="header-left">
            <img src="/Adin-Laundry/assets/images/icon_logo.png" alt="Adin Laundry" class="logo">
            <!-- Mobile toggle button -->
            <button class="mobile-toggle" aria-label="Toggle menu">
                <i class="fa fa-bars"></i>
            </button>
        </div>

        <!-- Kanan: Dua baris (kontak atas, menu bawah) -->
        <div class="header-right">
            <!-- Baris atas: kontak -->
            <div class="header-top">
                <div class="contact-info">
                    <span><i class="fa fa-phone"></i> +62 821-4457-8921</span>
                    <span><i class="fa fa-envelope"></i> adinlaundry@gmail.com</span>
                </div>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                </div>
            </div>

            <!-- Baris bawah: menu navigasi -->
            <div class="header-bottom">
                <ul class="menu">
                    <li><a href="index.php">HOME</a></li>
                    <li><a href="pricing.php">HARGA</a></li>

                    <?php if (isLoggedIn()): ?>
                        <li><a href="/Adin-Laundry/customer/order.php">PEMESANAN</a></li>
                        <li><a href="/Adin-Laundry/customer/status.php">STATUS PESANAN</a></li>
                        <li><a href="/Adin-Laundry/customer/feedback.php">FEEDBACK</a></li>
                    <?php else: ?>
                        <li><a href="login.php">PEMESANAN</a></li>
                    <?php endif; ?>
                </ul>

                <div class="login-btn-area">
                    <?php if (isLoggedIn()): ?>
                        <a href="/Adin-Laundry/logout.php" class="btn-login">LOGOUT</a>
                    <?php else: ?>
                        <a href="/Adin-Laundry/login.php" class="btn-login">LOGIN</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>


</header>

<!-- ===== Link CSS & Fonts ===== -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/Adin-Laundry/assets/css/navbar.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- Inline responsive styles for header toggle (kept local for quick override) -->
<style>
    .mobile-toggle {
        display: none;
        background: transparent;
        border: none;
        font-size: 1.4rem;
        color: #fff;
        margin-left: 12px;
        cursor: pointer;
    }

    /* Mobile menu overlay */
    .mobile-menu {
        display: none;
        position: fixed;
        top: 0;
        right: 0;
        width: 280px;
        height: 100vh;
        background: #0e2433;
        color: #fff;
        z-index: 9999;
        padding: 24px;
        box-shadow: -6px 0 18px rgba(0, 0, 0, 0.2);
        overflow-y: auto;
    }

    .mobile-menu .close-btn {
        display: block;
        margin-left: auto;
        background: transparent;
        border: none;
        color: #fff;
        font-size: 1.2rem;
        cursor: pointer;
    }

    .mobile-menu .contact-info,
    .mobile-menu .menu {
        margin-top: 18px;
    }

    .mobile-menu .menu li {
        list-style: none;
        margin: 10px 0;
    }

    .mobile-menu .menu li a {
        color: #fff;
        text-decoration: none;
        font-weight: 600;
    }

    /* show mobile toggle on small screens; hide full header so it doesn't block content */
    @media (max-width: 991px) {
        .mobile-toggle {
            display: inline-block;
        }

        .header-top .contact-info span {
            display: none;
        }

        /* Hide the large header area on mobile — only show logo + hamburger */
        .header-right {
            display: none !important;
        }

        /* Keep header-left visible and aligned */
        .header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }
    }

    /* when mobile menu active */
    body.mobile-menu-open {
        overflow: hidden;
    }

    body.mobile-menu-open .mobile-menu {
        display: block;
    }
</style>

<!-- Mobile menu markup (rendered here so it's present on all pages) -->
<div class="mobile-menu" id="mobileMenu" aria-hidden="true">
    <div style="display:flex; align-items:center; gap:12px;">
        <img src="/Adin-Laundry/assets/images/icon_logo.png" alt="logo" style="height:36px">
        <button class="close-btn" id="mobileMenuClose" aria-label="Close menu"><i class="fa fa-times"></i></button>
    </div>

    <div class="contact-info" style="margin-top:12px;">
        <div style="display:flex; align-items:center; gap:8px;"><i class="fa fa-phone"></i> +62 821-4457-8921</div>
        <div style="display:flex; align-items:center; gap:8px;"><i class="fa fa-envelope"></i> adinlaundry@gmail.com</div>
    </div>

    <ul class="menu" style="margin-top:18px; padding-left:0;">
        <li><a href="/Adin-Laundry/index.php">HOME</a></li>
        <li><a href="/Adin-Laundry/pricing.php">HARGA</a></li>
        <?php if (isLoggedIn()): ?>
            <li><a href="/Adin-Laundry/customer/order.php">PEMESANAN</a></li>
            <li><a href="/Adin-Laundry/customer/status.php">STATUS PESANAN</a></li>
            <li><a href="/Adin-Laundry/customer/feedback.php">FEEDBACK</a></li>
            <li><a href="/Adin-Laundry/logout.php">LOGOUT</a></li>
        <?php else: ?>
            <li><a href="/Adin-Laundry/login.php">PEMESANAN</a></li>
            <li><a href="/Adin-Laundry/login.php">LOGIN</a></li>
        <?php endif; ?>
    </ul>
</div>

<!-- Toggle script -->
<script>
    (function() {
        const toggle = document.querySelector('.mobile-toggle');
        const mobileMenu = document.getElementById('mobileMenu');
        const closeBtn = document.getElementById('mobileMenuClose');

        function openMenu() {
            document.body.classList.add('mobile-menu-open');
            mobileMenu.setAttribute('aria-hidden', 'false');
        }

        function closeMenu() {
            document.body.classList.remove('mobile-menu-open');
            mobileMenu.setAttribute('aria-hidden', 'true');
        }

        if (toggle) toggle.addEventListener('click', openMenu);
        if (closeBtn) closeBtn.addEventListener('click', closeMenu);

        // close on ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeMenu();
        });

        // auto close when resizing to desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 991) closeMenu();
        });
    })();
</script>

<!-- Site favicon (central fallback) -->
<link rel="icon" href="/Adin-Laundry/assets/images/favicon.ico" type="image/x-icon">