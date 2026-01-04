<?php
include 'includes/config.php';
include 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']); // DITAMBAHKAN
    $password = sanitize($_POST['password']);
    $confirm_password = sanitize($_POST['confirm_password']);

    // Validasi password
    if ($password !== $confirm_password) {
        $error = "Password dan konfirmasi password tidak cocok!";
    }
    // Validasi nomor telepon
    else if (!preg_match('/^[0-9+ ]{8,20}$/', $phone)) {
        $error = "Nomor telepon tidak valid! Gunakan angka saja.";
    } 
    else {
        // Kirim ke fungsi register()
        // Register harus menerima parameter baru ($phone)
        $result = register($username, $email, $password, $phone);

        if ($result === true) {
            header('Location: login.php?success=1');
            exit();
        } else {
            $error = $result;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="/Adin-Laundry/assets/images/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Adin Laundry</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Site CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Reuse login styles for forms -->
    <link rel="stylesheet" href="assets/css/login.css">
    <style>
        .phone-desc{ font-size:12px; color:#777; margin-top:4px; }
    </style>
</head>
<body class="login-page">

    <div class="login-card card">
        <div class="card-body">
            <div class="text-center mb-3">
                <img src="assets/images/logo.png" alt="Adin Laundry" class="login-logo" onerror="this.style.display='none'">
                <h4 class="mb-1">Daftar Akun Baru</h4>
                <p class="login-helper">Isi data berikut untuk membuat akun baru.</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success" role="alert">Registrasi berhasil! Silakan login.</div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Nama Pengguna</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Masukan nama pengguna" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Masukan email" required>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Nomor Telepon / WhatsApp</label>
                    <input type="text" class="form-control" id="phone" name="phone" placeholder="Contoh: 081234567890" required>
                    <div class="phone-desc">Pastikan nomor aktif agar admin dapat menghubungi Anda.</div>
                </div>

                <div class="mb-3">
                    <label for="password_reg" class="form-label">Password</label>
                    <div class="password-wrapper">
                        <input type="password" class="form-control" id="password_reg" name="password" placeholder="Masukan password" required>
                        <button type="button" class="btn-show-pass" id="togglePassReg" title="Tampilkan kata sandi"><i class="fa fa-eye"></i></button>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="confirm_password_reg" class="form-label">Ulangi Password</label>
                    <div class="password-wrapper">
                        <input type="password" class="form-control" id="confirm_password_reg" name="confirm_password" placeholder="Ulangi password" required>
                        <button type="button" class="btn-show-pass" id="togglePassConfirm" title="Tampilkan kata sandi"><i class="fa fa-eye"></i></button>
                    </div>
                </div>

                <div class="d-grid mb-2">
                    <button type="submit" class="btn btn-primary btn-lg">Daftar</button>
                </div>
            </form>

            <div class="text-center">
                <a href="login.php">Sudah punya akun? Login</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Unified toggle: find all .btn-show-pass and toggle the related input nearby
        (function(){
            document.querySelectorAll('.btn-show-pass').forEach(function(btn){
                btn.addEventListener('click', function(){
                    // find input: prefer input inside same wrapper, else sibling input in parent
                    var inp = null;
                    var wrapper = btn.closest('.password-wrapper');
                    if(wrapper) inp = wrapper.querySelector('input');
                    if(!inp) inp = btn.parentElement ? btn.parentElement.querySelector('input') : null;
                    if(!inp) inp = btn.previousElementSibling;
                    if(!inp) return;
                    if(inp.type === 'password'){
                        inp.type = 'text';
                        btn.innerHTML = '<i class="fa fa-eye-slash"></i>';
                        btn.title = 'Sembunyikan kata sandi';
                    } else {
                        inp.type = 'password';
                        btn.innerHTML = '<i class="fa fa-eye"></i>';
                        btn.title = 'Tampilkan kata sandi';
                    }
                });
            });
        })();
    </script>
</body>
</html>
