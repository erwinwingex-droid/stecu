<?php
// owner/sidebar.php
// Sidebar untuk role owner — tetap pakai style & tema yang ada di project
?>
<div class="sidebar">

    <!-- Sidebar Header (same structure as admin) -->
    <div class="sidebar-header">
        <div class="logo-small">
            <img src="<?= BASE_URL ?>/assets/images/icon_logo.png" alt="Adin Laundry">
        </div>
        <button class="sidebar-toggle" id="sidebarCollapse">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>

    <ul class="sidebar-menu">
        <li class="sidebar-section"><span class="section-label">Owner Menu</span></li>
        <li>
            <a href="<?= BASE_URL ?>/owner/dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <div class="menu-item">
                    <span class="menu-icon"><i class="fas fa-home"></i></span>
                    <span class="menu-text">Dashboard Owner</span>
                </div>
                <div class="menu-indicator"></div>
            </a>
        </li>

        <li>
            <a href="<?= BASE_URL ?>/owner/laporan_penjualan.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'laporan_penjualan.php' ? 'active' : ''; ?>">
                <div class="menu-item">
                    <span class="menu-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                    <span class="menu-text">Laporan Penjualan</span>
                </div>
                <div class="menu-indicator"></div>
            </a>
        </li>

        <li>
            <a href="<?= BASE_URL ?>/owner/laporan_pelanggan.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'laporan_pelanggan.php' ? 'active' : ''; ?>">
                <div class="menu-item">
                    <span class="menu-icon"><i class="fas fa-users"></i></span>
                    <span class="menu-text">Laporan Pelanggan</span>
                </div>
                <div class="menu-indicator"></div>
            </a>
        </li>

        <li>
            <a href="<?= BASE_URL ?>/owner/data_layanan.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'data_layanan.php' ? 'active' : ''; ?>">
                <div class="menu-item">
                    <span class="menu-icon"><i class="fas fa-concierge-bell"></i></span>
                    <span class="menu-text">Data Layanan</span>
                </div>
                <div class="menu-indicator"></div>
            </a>
        </li>

        <li>
            <a href="<?= BASE_URL ?>/owner/feedback.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'active' : ''; ?>">
                <div class="menu-item">
                    <span class="menu-icon"><i class="fas fa-comments"></i></span>
                    <span class="menu-text">Feedback Pelanggan</span>
                </div>
                <div class="menu-indicator"></div>
            </a>
        </li>

        <li class="logout-section">
            <a href="<?= BASE_URL ?>/logout.php">
                <div class="menu-item">
                    <span class="menu-icon"><i class="fas fa-sign-out-alt"></i></span>
                    <span class="menu-text">Logout</span>
                </div>
                <div class="menu-indicator"></div>
            </a>
        </li>
    </ul>
</div>

<!-- Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar script (same as admin) -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.querySelector('.sidebar');
        const menuToggle = document.getElementById('menuToggle');
        const sidebarCollapse = document.getElementById('sidebarCollapse');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        if (sidebarCollapse) {
            sidebarCollapse.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                // keep a body-level class in sync so layout rules work regardless of DOM order
                document.body.classList.toggle('sidebar-collapsed', sidebar.classList.contains('collapsed'));
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            });
        }

        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
            });
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            });
        }

        function loadSidebarState() {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
                document.body.classList.add('sidebar-collapsed');
            } else {
                document.body.classList.remove('sidebar-collapsed');
            }
        }

        loadSidebarState();
    });
</script>