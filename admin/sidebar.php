<?php
// Sidebar untuk halaman admin Adin Laundry
?>

<!-- Sidebar -->
<div class="sidebar">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <div class="logo-small">
            <img src="../assets/images/icon_logo.png" alt="Adin Laundry">

        </div>
        <button class="sidebar-toggle" id="sidebarCollapse">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>

    <!-- Sidebar Menu -->
    <ul class="sidebar-menu">
        <!-- General Section -->
        <li class="sidebar-section">
            <span class="section-label">General</span>
        </li>
        <li>
            <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <div class="menu-item">
                    <span class="menu-icon">
                        <i class="fas fa-th-large"></i>
                    </span>
                    <span class="menu-text">Dashboard</span>
                </div>
                <div class="menu-indicator"></div>
            </a>
        </li>

        <!-- Main Menu Section -->
        <li class="sidebar-section">
            <span class="section-label">Main Menu</span>
        </li>

        <li>
            <a href="orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                <div class="menu-item">
                    <span class="menu-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </span>
                    <span class="menu-text">Data Pesanan</span>
                </div>
                <div class="menu-indicator"></div>
            </a>
        </li>

        <li>
            <a href="services.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : ''; ?>">
                <div class="menu-item">
                    <span class="menu-icon">
                        <i class="fas fa-concierge-bell"></i>
                    </span>
                    <span class="menu-text">Data Layanan</span>
                </div>
                <div class="menu-indicator"></div>
            </a>
        </li>

        <li>
            <a href="pricing.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'pricing.php' ? 'active' : ''; ?>">
                <div class="menu-item">
                    <span class="menu-icon">
                        <i class="fas fa-tags"></i>
                    </span>
                    <span class="menu-text">Data Harga & Promo</span>
                </div>
                <div class="menu-indicator"></div>
            </a>
        </li>

        <li>
            <a href="customers.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>">
                <div class="menu-item">
                    <span class="menu-icon">
                        <i class="fas fa-users"></i>
                    </span>
                    <span class="menu-text">Data Pelanggan</span>
                </div>
                <div class="menu-indicator"></div>
            </a>
        </li>

        <li>
            <a href="couriers.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'couriers.php' ? 'active' : ''; ?>">
                <div class="menu-item">
                    <span class="menu-icon">
                        <i class="fas fa-motorcycle"></i>
                    </span>
                    <span class="menu-text">Data Kurir</span>
                </div>
                <div class="menu-indicator"></div>
            </a>
        </li>

        <!-- Reports Section -->
        <li class="sidebar-section">
            <span class="section-label">Laporan</span>
        </li>

        <li>
            <a href="feedback.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'active' : ''; ?>">
                <div class="menu-item">
                    <span class="menu-icon">
                        <i class="fas fa-chart-bar"></i>
                    </span>
                    <span class="menu-text">Analisis Feedback</span>
                </div>
                <div class="menu-indicator"></div>
            </a>
        </li>

        <!-- Logout -->
        <li class="logout-section">
            <a href="../logout.php">
                <div class="menu-item">
                    <span class="menu-icon">
                        <i class="fas fa-sign-out-alt"></i>
                    </span>
                    <span class="menu-text">Logout</span>
                </div>
                <div class="menu-indicator"></div>
            </a>
        </li>
    </ul>

    <!-- Sidebar Footer removed: hide/view controls disabled per request -->
</div>

<!-- Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- JavaScript untuk Sidebar Interaksi -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elements
        const sidebar = document.querySelector('.sidebar');
        const menuToggle = document.getElementById('menuToggle');
        const sidebarCollapse = document.getElementById('sidebarCollapse');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        // Toggle sidebar collapse/expand
        if (sidebarCollapse) {
            sidebarCollapse.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                // Save state to localStorage
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            });
        }

        // Note: hide/view features removed. Sidebar remains visible by default

        // Mobile menu toggle
        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
            });
        }

        // Close sidebar when clicking overlay
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            });
        }

        // Load saved sidebar state
        function loadSidebarState() {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
            }
        }

        // Initialize
        loadSidebarState();

        // Remove checkbox logic — using active link highlight and simple indicator instead
    });
</script>