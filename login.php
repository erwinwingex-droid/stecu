<?php
require_once(__DIR__ . '/includes/config.php');
require_once(__DIR__ . '/includes/auth.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = sanitize($_POST['password']);

    if (login($email, $password)) {
        if (isAdmin()) {
            header('Location: admin/dashboard.php');
        } elseif (isOwner()) {
            header('Location: owner/dashboard.php');
        } elseif (isKurir()) {
            header('Location: kurir/dashboard.php');
        } elseif (isPegawai()) {
            header('Location: pegawai/dashboard.php');
        } else {
            header('Location: customer/index.php');
        }
        exit();
    } else {
        $error = "Email atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="/Adin-Laundry/assets/images/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Adin Laundry</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Login CSS -->
    <link rel="stylesheet" href="assets/css/login.css">
</head>

<body class="login-page">

<div class="login-card card">
    <div class="card-body">

        <div class="text-center mb-3">
            <img src="assets/images/logo.png" class="login-logo" alt="Adin Laundry"
                 onerror="this.style.display='none'">
            <h4 class="mb-1">Masuk ke Akun Anda</h4>
            <p class="login-helper">Masukkan email dan kata sandi untuk melanjutkan.</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email"
                       class="form-control"
                       name="email"
                       placeholder="Masukan email"
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="password-wrapper">
                    <input type="password"
                           class="form-control"
                           id="password"
                           name="password"
                           placeholder="Masukan password"
                           required>
                    <button type="button" class="btn-show-pass">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary btn-lg">
                    Masuk
                </button>
            </div>
        </form>

        <div class="text-center mb-3">
            <a href="register.php">Belum punya akun? Daftar</a>
        </div>

        <!-- SOCIAL MEDIA -->
        <div class="social-login text-center">
            <p class="mb-2 fw-semibold">Ikuti Kami</p>
            <a href="#" class="social-icon ig"><i class="fab fa-instagram"></i></a>
            <a href="#" class="social-icon wa"><i class="fab fa-whatsapp"></i></a>
            <a href="#" class="social-icon fb"><i class="fab fa-facebook-f"></i></a>
        </div>

    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Toggle Password -->
<script>
document.querySelectorAll('.btn-show-pass').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = btn.previousElementSibling;
        if (input.type === 'password') {
            input.type = 'text';
            btn.innerHTML = '<i class="fa fa-eye-slash"></i>';
        } else {
            input.type = 'password';
            btn.innerHTML = '<i class="fa fa-eye"></i>';
        }
    });
});
</script>

</body>
</html>
