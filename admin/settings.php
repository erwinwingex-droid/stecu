<?php
include '../includes/auth.php';
requireAdmin();
include '../includes/functions.php';

// Update pengaturan umum
if (isset($_POST['update_settings'])) {
    $site_name = sanitize($_POST['site_name']);
    $contact_phone = sanitize($_POST['contact_phone']);
    $contact_email = sanitize($_POST['contact_email']);
    $business_hours = sanitize($_POST['business_hours']);
    $delivery_info = sanitize($_POST['delivery_info']);
    
    // Simpan ke database atau file config
    $settings = [
        'site_name' => $site_name,
        'contact_phone' => $contact_phone,
        'contact_email' => $contact_email,
        'business_hours' => $business_hours,
        'delivery_info' => $delivery_info
    ];
    
    file_put_contents('../includes/settings.json', json_encode($settings));
    $success = "Pengaturan berhasil diupdate!";
}

// Update password admin
if (isset($_POST['update_password'])) {
    $current_password = sanitize($_POST['current_password']);
    $new_password = sanitize($_POST['new_password']);
    $confirm_password = sanitize($_POST['confirm_password']);
    
    // Verifikasi password saat ini
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                $password_success = "Password berhasil diupdate!";
            } else {
                $password_error = "Gagal mengupdate password!";
            }
        } else {
            $password_error = "Password baru dan konfirmasi tidak cocok!";
        }
    } else {
        $password_error = "Password saat ini salah!";
    }
}

// Load pengaturan
$settings_file = '../includes/settings.json';
if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
} else {
    $settings = [
        'site_name' => 'Adin Laundry',
        'contact_phone' => '+52-932-4567-8900',
        'contact_email' => 'adinlaundry123@gmail.com',
        'business_hours' => '07:00 AM - 05:00 PM',
        'delivery_info' => 'Gratis penjemputan dan pengantaran untuk area tertentu'
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Admin Adin Laundry</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="content-header">
                <h1>Pengaturan Sistem</h1>
                <p>Kelola pengaturan website dan akun</p>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($password_success)): ?>
                <div class="alert alert-success"><?php echo $password_success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($password_error)): ?>
                <div class="alert alert-error"><?php echo $password_error; ?></div>
            <?php endif; ?>

            <div class="settings-grid">
                <!-- Pengaturan Umum -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-cog"></i> Pengaturan Umum</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label for="site_name">Nama Website</label>
                                <input type="text" class="form-control" id="site_name" name="site_name" 
                                       value="<?php echo $settings['site_name']; ?>" required>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="contact_phone">Nomor Telepon</label>
                                    <input type="text" class="form-control" id="contact_phone" name="contact_phone" 
                                           value="<?php echo $settings['contact_phone']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="contact_email">Email</label>
                                    <input type="email" class="form-control" id="contact_email" name="contact_email" 
                                           value="<?php echo $settings['contact_email']; ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="business_hours">Jam Operasional</label>
                                <input type="text" class="form-control" id="business_hours" name="business_hours" 
                                       value="<?php echo $settings['business_hours']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="delivery_info">Info Pengiriman</label>
                                <textarea class="form-control" id="delivery_info" name="delivery_info" 
                                          rows="3" required><?php echo $settings['delivery_info']; ?></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="update_settings" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Simpan Pengaturan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Ubah Password -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-key"></i> Ubah Password</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label for="current_password">Password Saat Ini</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">Password Baru</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Konfirmasi Password Baru</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="update_password" class="btn btn-primary">
                                    <i class="fas fa-key"></i> Ubah Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Informasi Sistem -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle"></i> Informasi Sistem</h3>
                    </div>
                    <div class="card-body">
                        <div class="system-info">
                            <div class="info-item">
                                <span class="label">Versi Sistem:</span>
                                <span class="value">v1.0.0</span>
                            </div>
                            <div class="info-item">
                                <span class="label">PHP Version:</span>
                                <span class="value"><?php echo phpversion(); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Database:</span>
                                <span class="value">MySQL</span>
                            </div>
                            <div class="info-item">
                                <span class="label">Total Pengguna:</span>
                                <span class="value">
                                    <?php 
                                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
                                    echo $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                    ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="label">Total Pesanan:</span>
                                <span class="value">
                                    <?php 
                                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
                                    echo $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .system-info {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .label {
            font-weight: 600;
            color: #7f8c8d;
        }
        
        .value {
            color: #2c3e50;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>