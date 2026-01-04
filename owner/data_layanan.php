<?php
require_once(__DIR__ . "/../includes/config.php");
require_once(__DIR__ . "/../includes/auth.php");
require_once(__DIR__ . "/../includes/functions.php");

requireLogin();
if (!isAdmin() && !isOwner()) {
    header('Location: ../index.php');
    exit();
}

// Tambah layanan baru
if (isset($_POST['add_service'])) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $base_price = sanitize($_POST['base_price']);
    $duration = sanitize($_POST['duration']);

    $stmt = $pdo->prepare("INSERT INTO services (name, description, base_price, duration) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$name, $description, $base_price, $duration])) {
        $success = "Layanan berhasil ditambahkan!";
    } else {
        $error = "Gagal menambahkan layanan!";
    }
}

// Update layanan
if (isset($_POST['update_service'])) {
    $id = sanitize($_POST['id']);
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $base_price = sanitize($_POST['base_price']);
    $duration = sanitize($_POST['duration']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE services SET name = ?, description = ?, base_price = ?, duration = ?, is_active = ? WHERE id = ?");
    if ($stmt->execute([$name, $description, $base_price, $duration, $is_active, $id])) {
        $success = "Layanan berhasil diupdate!";
    } else {
        $error = "Gagal mengupdate layanan!";
    }
}

// Dapatkan semua layanan
$services = getServices();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="icon" href="/Adin-Laundry/assets/images/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Layanan - Owner Adin Laundry</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* Small helpers to match admin look */
        .card {
            border: none;
            border-radius: .5rem;
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .08);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 1.25rem;
        }

        .status-badge {
            display: inline-block;
            padding: .25rem .75rem;
            border-radius: 50rem;
            font-weight: 600;
            font-size: .8rem;
        }

        .status-active {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-inactive {
            background: #f8d7da;
            color: #842029;
        }

        /* Batasi lebar halaman/tabel hanya untuk halaman ini */
        .main-content {
            max-width: 1650px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        .table-container {
            max-width: 1650px;
            margin: 0 auto;
            overflow-x: auto;
        }

        .table-container table {
            width: 100%;
            table-layout: auto;
        }

        @media (max-width: 992px) {
            .table-container {
                max-width: 100%;
                padding: 0 12px;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <div class="main-content container-fluid">
        <div class="content-header my-4">
            <h1 class="h3 mb-1">Data Layanan</h1>
            <p class="text-muted">Kelola layanan laundry yang tersedia</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Tambah Layanan -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Tambah Layanan Baru</h5>
                <i class="bi bi-plus-circle text-primary"></i>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Nama Layanan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback">Harap isi nama layanan.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="base_price" class="form-label">Harga Dasar (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="base_price" name="base_price" required min="0" step="1000">
                            </div>
                            <div class="invalid-feedback">Harap isi harga dasar yang valid.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="duration" class="form-label">Durasi Pengerjaan <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="duration" name="duration" required placeholder="Contoh: 2-3 hari">
                                <span class="input-group-text"><i class="bi bi-clock"></i></span>
                            </div>
                            <div class="form-text">Gunakan format seperti: 2-3 hari, 1 hari, 4-5 hari</div>
                            <div class="invalid-feedback">Harap isi durasi pengerjaan.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="description" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                            <div class="invalid-feedback">Harap isi deskripsi layanan.</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" name="add_service" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Tambah Layanan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Daftar Layanan -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Layanan</h5>
                <span class="badge bg-primary rounded-pill"><?php echo count($services); ?> Layanan</span>
            </div>
            <div class="card-body">
                <div class="table-responsive table-container">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nama Layanan</th>
                                <th>Deskripsi</th>
                                <th>Harga</th>
                                <th>Durasi</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $service): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($service['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($service['description']); ?></td>
                                    <td>Rp <?php echo number_format($service['base_price'], 0, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($service['duration']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $service['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $service['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1"
                                            onclick="editService(<?php echo htmlspecialchars(json_encode($service)); ?>)"
                                            data-bs-toggle="tooltip" title="Edit Layanan">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Hapus layanan ini? Aksi ini tidak dapat dibatalkan.');">
                                            <input type="hidden" name="id" value="<?php echo $service['id']; ?>">
                                            <button type="submit" name="delete_service" class="btn btn-sm btn-outline-danger" title="Hapus Layanan">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Layanan -->
    <div class="modal fade" id="serviceModal" tabindex="-1" aria-labelledby="serviceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="serviceModalLabel">Edit Layanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="serviceForm" class="needs-validation" novalidate>
                        <input type="hidden" name="id" id="edit_id">

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="edit_name" class="form-label">Nama Layanan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                                <div class="invalid-feedback">Harap isi nama layanan.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_base_price" class="form-label">Harga Dasar (Rp) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="edit_base_price" name="base_price" required min="0" step="1000">
                                </div>
                                <div class="invalid-feedback">Harap isi harga dasar yang valid.</div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="edit_duration" class="form-label">Durasi Pengerjaan <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="edit_duration" name="duration" required>
                                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                </div>
                                <div class="invalid-feedback">Harap isi durasi pengerjaan.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_description" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="edit_description" name="description" rows="3" required></textarea>
                                <div class="invalid-feedback">Harap isi deskripsi layanan.</div>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1">
                            <label class="form-check-label" for="edit_is_active">Aktifkan layanan ini</label>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="update_service" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Update Layanan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
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

        // Edit service function
        function editService(service) {
            document.getElementById('edit_id').value = service.id;
            document.getElementById('edit_name').value = service.name;
            document.getElementById('edit_description').value = service.description;
            document.getElementById('edit_base_price').value = service.base_price;
            document.getElementById('edit_duration').value = service.duration;
            document.getElementById('edit_is_active').checked = service.is_active == 1;

            // Reset validation state
            const form = document.getElementById('serviceForm');
            form.classList.remove('was-validated');

            // Show modal using Bootstrap
            const modal = new bootstrap.Modal(document.getElementById('serviceModal'));
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