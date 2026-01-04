<?php
// Header khusus untuk kurir — responsif namun tetap sederhana (Dashboard + Logout)
include_once __DIR__ . '/../../includes/auth.php';

requireLogin();
// Batasi akses hanya untuk role 'kurir'
if (!isKurir()) {
    header('Location: ../index.php');
    exit();
}

$username = $_SESSION['username'] ?? 'Kurir';
$roleLabel = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'User';
?>

<header class="kurir-header">
    <div class="kh-container">
        <div class="kh-left">
            <img src="../assets/images/icon_logo.png" alt="Adin Laundry" class="kh-logo">
            <button class="kh-toggle" aria-label="Toggle menu"><i class="fa fa-bars"></i></button>
        </div>

        <div class="kh-right">
            <div class="kh-user"><?php echo htmlspecialchars($username); ?> <small style="opacity:0.8;">(<?php echo htmlspecialchars($roleLabel); ?>)</small></div>
            <a href="../logout.php" class="kh-logout">LOGOUT</a>
        </div>
    </div>
</header>

<!-- Mobile menu for kurir (only dashboard + logout) -->
<div id="khMobileMenu" class="kh-mobile" aria-hidden="true">
    <div class="kh-mobile-inner">
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <img src="../assets/images/icon_logo.png" alt="logo" style="height:36px">
            <button id="khClose" class="kh-close" aria-label="Close menu"><i class="fa fa-times"></i></button>
        </div>

        <nav style="margin-top:18px;">
            <ul style="list-style:none;padding-left:0;">
                <li style="margin:10px 0;"><a href="../kurir/dashboard.php" style="color:#fff;text-decoration:none;font-weight:700;">Dashboard</a></li>
                <li style="margin:10px 0;"><a href="../logout.php" style="color:#fff;text-decoration:none;font-weight:700;">Logout</a></li>
            </ul>
        </nav>
    </div>
</div>

<!-- Styles for kurir header -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    .kurir-header {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        background: #0d1b2a;
        color: #fff;
        z-index: 2000;
    }

    .kh-container {
        width: 92%;
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 30px;
    }

    .kh-left {
        flex: 0 0 auto;
        padding-left: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .kh-logo {
        height: 120px
    }

    .kh-toggle {
        display: none;
        background: transparent;
        border: 0;
        color: #fff;
        font-size: 1.2rem;
        cursor: pointer
    }

    .kh-right {
        display: flex;
        align-items: center;
        gap: 12px
    }

    .kh-user {
        font-weight: 600
    }

    .kh-logout {
        background: #f5a623;
        color: #000;
        padding: 8px 12px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 700
    }

    /* Mobile menu panel */
    .kh-mobile {
        display: none;
        position: fixed;
        top: 0;
        right: 0;
        width: 260px;
        height: 100vh;
        background: #0d1b2a;
        color: #fff;
        z-index: 2500;
        padding: 18px;
        box-shadow: -6px 0 18px rgba(0, 0, 0, 0.2);
    }

    .kh-close {
        background: transparent;
        border: 0;
        color: #fff;
        font-size: 1.2rem
    }

    @media (max-width: 991px) {
        .kh-toggle {
            display: inline-block
        }

        .kh-right {
            display: none
        }

        body.kh-mobile-open {
            overflow: hidden
        }

        body.kh-mobile-open .kh-mobile {
            display: block
        }
    }

    @media (max-width: 768px) {
        .kh-logo {
            height: 70px;
            margin-bottom: 10px;
        }
    }
</style>

<!-- Toggle script -->
<script>
    (function() {
        const btn = document.querySelector('.kh-toggle');
        const mobile = document.getElementById('khMobileMenu');
        const closeBtn = document.getElementById('khClose');

        function open() {
            document.body.classList.add('kh-mobile-open');
            mobile.setAttribute('aria-hidden', 'false');
        }

        function close() {
            document.body.classList.remove('kh-mobile-open');
            mobile.setAttribute('aria-hidden', 'true');
        }

        if (btn) btn.addEventListener('click', open);
        if (closeBtn) closeBtn.addEventListener('click', close);
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') close();
        });
        window.addEventListener('resize', function() {
            if (window.innerWidth > 991) close();
        });
    })();
</script>