<?php
// Sidebar untuk role kurir — hanya menampilkan Dashboard, Kelola Layanan, Tracking, Logout
?>
<div class="sidebar">
    <ul class="sidebar-menu" style="list-style:none;padding:0;margin:0;">
        <li>
            <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </li>

        <li>
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</div>
