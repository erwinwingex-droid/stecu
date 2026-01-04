<?php
include '../includes/auth.php';
requireAdmin();
include '../includes/functions.php';
global $pdo;

// Tambah kurir
if (isset($_POST['add'])) {
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $email = sanitize($_POST['email']);
    $password = sanitize($_POST['password']);
    $confirm_password = sanitize($_POST['confirm_password']);

    // Validasi
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email tidak valid";
    } elseif ($password !== $confirm_password) {
        $error = "Password dan konfirmasi tidak cocok";
    } else {
        // Cek email sudah ada
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $error = "Email sudah terdaftar";
        } else {
            // Cek nomor WA sudah terdaftar di tabel couriers
            $chk = $pdo->prepare("SELECT id FROM couriers WHERE phone = ?");
            $chk->execute([$phone]);
            if ($chk->rowCount() > 0) {
                $error = "Nomor WhatsApp sudah terdaftar sebagai kurir";
            } else {
                // Buat akun di tabel users dengan role 'kurir'
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $createUserStmt = $pdo->prepare("INSERT INTO users (username, email, whatsapp, password, role) VALUES (?, ?, ?, ?, 'kurir')");
                $created = $createUserStmt->execute([$name, $email, $phone, $hashedPassword]);

                if ($created) {
                    // Insert ke tabel couriers (tetap menyimpan info kurir)
                    $stmt = $pdo->prepare("INSERT INTO couriers (name, phone) VALUES (?, ?)");
                    $stmt->execute([$name, $phone]);

                    $success = "Kurir berhasil ditambahkan dan akun dibuat";
                } else {
                    $error = "Gagal membuat akun kurir";
                }
            }
        }
    }
}

// Hapus kurir
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // Cari kurir untuk mendapatkan nomor WA (whatsapp)
    $stmt = $pdo->prepare("SELECT phone FROM couriers WHERE id = ?");
    $stmt->execute([$id]);
    $courier = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($courier) {
        $phone = $courier['phone'];
        // Hapus user yang memiliki whatsapp = phone dan role 'kurir'
        $pdo->prepare("DELETE FROM users WHERE whatsapp = ? AND role = 'kurir'")->execute([$phone]);
    }

    $pdo->prepare("DELETE FROM couriers WHERE id=?")->execute([$id]);
    $success = "Kurir berhasil dihapus!";
}

// Update kurir
if (isset($_POST['update_courier'])) {
    $id = (int)$_POST['id'];
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $email = sanitize($_POST['email']);
    $password = sanitize($_POST['password']);
    $original_phone = sanitize($_POST['original_phone']);

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email tidak valid";
    }

    if (empty($error)) {
        if ($phone !== $original_phone) {
            $chk = $pdo->prepare("SELECT id FROM couriers WHERE phone = ? AND id != ?");
            $chk->execute([$phone, $id]);
            if ($chk->rowCount() > 0) {
                $error = "Nomor WhatsApp sudah terdaftar sebagai kurir lain";
            }
        }
    }

    if (empty($error) && !empty($email)) {
        $chk = $pdo->prepare("SELECT id FROM users WHERE email = ? AND (whatsapp != ? OR role != 'kurir')");
        $chk->execute([$email, $original_phone]);
        if ($chk->rowCount() > 0) {
            $error = "Email sudah digunakan oleh akun lain";
        }
    }

    if (empty($error)) {
        try {
            $pdo->beginTransaction();

            // Update couriers table
            $upd = $pdo->prepare("UPDATE couriers SET name = ?, phone = ? WHERE id = ?");
            $upd->execute([$name, $phone, $id]);

            // Update or create users entry for this courier
            $u = $pdo->prepare("SELECT id FROM users WHERE whatsapp = ? AND role = 'kurir' LIMIT 1");
            $u->execute([$original_phone]);
            $user = $u->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if (!empty($password)) {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE users SET username = ?, email = ?, whatsapp = ?, password = ? WHERE id = ?")->execute([$name, $email, $phone, $hashed, $user['id']]);
                } else {
                    $pdo->prepare("UPDATE users SET username = ?, email = ?, whatsapp = ? WHERE id = ?")->execute([$name, $email, $phone, $user['id']]);
                }
            } else {
                // create user for courier (use provided password or random)
                $plain = !empty($password) ? $password : bin2hex(random_bytes(4));
                $hashed = password_hash($plain, PASSWORD_DEFAULT);
                $pdo->prepare("INSERT INTO users (username, email, whatsapp, password, role) VALUES (?, ?, ?, ?, 'kurir')")->execute([$name, $email, $phone, $hashed]);
            }

            $pdo->commit();
            $success = "Data kurir berhasil diupdate";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = "Gagal mengupdate kurir";
        }
    }
}

// Ambil daftar kurir (gabungkan email dari users jika ada)
$couriers = $pdo->query("SELECT c.*, u.email AS email FROM couriers c LEFT JOIN users u ON u.whatsapp = c.phone AND u.role = 'kurir' ORDER BY c.id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="icon" href="/Adin-Laundry/assets/images/icon_logo.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kurir - Admin Adin Laundry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .card {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.06);
            border-radius: .5rem;
        }

        .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }

        .form-label {
            font-weight: 500;
        }

        .table thead th {
            background: #f8f9fa;
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="content-header mb-4">
            <h1 class="h2 mb-2">Manajemen Kurir</h1>
            <p class="text-muted">Tambah dan kelola data kurir</p>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Card Tambah Kurir -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="h5 mb-0">Tambah Kurir Baru</h3>
                <i class="bi bi-person-plus text-primary"></i>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3 needs-validation" novalidate>
                    <div class="col-md-6">
                        <label class="form-label">Nama Kurir <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                        <div class="invalid-feedback">Masukkan nama kurir.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">No. WhatsApp <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" placeholder="08xxxxxxxxxx" required>
                        <div class="invalid-feedback">Masukkan nomor WhatsApp yang valid.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="kurir@email.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required>
                        <div class="invalid-feedback">Masukkan password.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Ulangi Password <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" class="form-control" required>
                        <div class="invalid-feedback">Konfirmasi password tidak cocok.</div>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" name="add" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Tambah Kurir</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Daftar Kurir -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="h5 mb-0">Daftar Kurir</h3>
                <span class="badge bg-primary rounded-pill"><?php echo count($couriers); ?> Kurir</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="30%">Nama</th>
                                <th width="25%">WhatsApp</th>
                                <th width="25%">Email</th>
                                <th width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($couriers as $c): ?>
                                <tr>
                                    <td><?= $c['id'] ?></td>
                                    <td><?= htmlspecialchars($c['name']) ?></td>
                                    <td><?= htmlspecialchars($c['phone']) ?></td>
                                    <td><?= !empty($c['email']) ? htmlspecialchars($c['email']) : '-' ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1" onclick='editCourier(<?php echo htmlspecialchars(json_encode($c), ENT_QUOTES, "UTF-8"); ?>)' data-bs-toggle="tooltip" title="Edit Kurir">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="?delete=<?= $c['id'] ?>" onclick="return confirm('Hapus kurir?')" class="btn btn-sm btn-outline-danger" title="Hapus Kurir">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Edit Kurir -->
    <div class="modal fade" id="courierModal" tabindex="-1" aria-labelledby="courierModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="courierModalLabel">Edit Kurir</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="courierForm" class="needs-validation" novalidate>
                        <input type="hidden" name="id" id="edit_id">
                        <input type="hidden" name="original_phone" id="original_phone">

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="edit_name" class="form-label">Nama Kurir <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                                <div class="invalid-feedback">Harap isi nama kurir.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_phone" class="form-label">No. WhatsApp <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_phone" name="phone" required>
                                <div class="invalid-feedback">Harap isi nomor WA.</div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="edit_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_password" class="form-label">Password (kosongkan jika tidak diubah)</label>
                                <input type="password" class="form-control" id="edit_password" name="password">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="update_courier" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function() {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()

        function editCourier(courier) {
            document.getElementById('edit_id').value = courier.id;
            document.getElementById('original_phone').value = courier.phone;
            document.getElementById('edit_name').value = courier.name;
            document.getElementById('edit_phone').value = courier.phone;
            document.getElementById('edit_email').value = courier.email || '';

            const form = document.getElementById('courierForm');
            form.classList.remove('was-validated');

            const modal = new bootstrap.Modal(document.getElementById('courierModal'));
            modal.show();
        }

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })
        });
    </script>
</body>

</html>